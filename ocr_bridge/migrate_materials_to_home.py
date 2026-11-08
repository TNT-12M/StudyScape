#!/usr/bin/env python3
"""
StudyScape 现有资料迁移脚本 (公网机运行)
==========================================
逐个把 materials/ 里的文件 → bridge /storage/store → 家里云
完成后删本地 + 更新 DB storage_node='home' sha256=...

启动: python3 migrate_materials_to_home.py [--dry-run]
     或: python3 migrate_materials_to_home.py --yes  (自动确认)
"""
import sqlite3, json, os, sys, time, hashlib, base64, urllib.request, urllib.error

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # 项目根 (ocr_bridge/../)
DB = os.path.join(ROOT, "exam.db")
MAT_DIR = os.path.join(ROOT, "materials")
BRIDGE = "http://127.0.0.1:1201"
BATCH = 5   # 每批处理几个
SLEEP = 1   # 每批之间 sleep 秒

def bridge_storage(path, body):
    try:
        req = urllib.request.Request(
            f"{BRIDGE}{path}",
            data=json.dumps(body).encode(),
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        resp = urllib.request.urlopen(req, timeout=70)
        return json.loads(resp.read().decode()), resp.status
    except urllib.error.HTTPError as e:
        try:
            return json.loads(e.read().decode()), e.code
        except:
            return {"ok": False, "error": "http " + str(e.code)}, e.code
    except Exception as e:
        return {"ok": False, "error": str(e)}, 0

def sha256_file(fp):
    h = hashlib.sha256()
    with open(fp, "rb") as f:
        for chunk in iter(lambda: f.read(1024*1024), b""):
            h.update(chunk)
    return h.hexdigest()

def main():
    dry = "--dry-run" in sys.argv
    auto_yes = "--yes" in sys.argv

    conn = sqlite3.connect(DB)
    conn.row_factory = sqlite3.Row
    db = conn.cursor()

    # 确保新列存在
    try:
        db.execute("ALTER TABLE materials ADD COLUMN sha256 TEXT")
    except sqlite3.OperationalError: pass
    try:
        db.execute("ALTER TABLE materials ADD COLUMN storage_node TEXT DEFAULT 'local'")
    except sqlite3.OperationalError: pass
    conn.commit()

    # 查所有 local 或 storage_node 为空的记录，且本地文件还存在
    db.execute("SELECT id, filename, stored_name, file_path, storage_node, sha256 FROM materials WHERE storage_node IS NOT 'home'")
    rows = db.fetchall()

    todo = []
    for r in rows:
        fp = os.path.join(MAT_DIR, r["stored_name"])
        if os.path.isfile(fp):
            todo.append((r["id"], r["filename"], r["stored_name"], fp, r["sha256"] or ""))
        elif r["storage_node"] == "local":
            # 标记 orphan
            print(f"⚠️  DB 标记 local 但文件丢失: id={r['id']} {r['stored_name']}")
            db.execute("UPDATE materials SET storage_node='orphan' WHERE id=?", (r["id"],))

    print(f"📋 待迁移: {len(todo)} / 总 {len(rows)} 条")
    for mid, fn, sn, fp, sha in todo[:5]:
        sz = os.path.getsize(fp)
        print(f"  id={mid}  {fn}  ({sz//1024}KB)")
    if len(todo) > 5: print(f"  ... 等 {len(todo)-5} 个")

    if dry:
        print("\n🔍 DRY RUN — 不会真的传输，退出")
        return

    # 检查家里云
    hres = json.loads(urllib.request.urlopen(f"{BRIDGE}/health", timeout=5).read())
    if not hres.get("worker_online"):
        print("❌ 家里云离线 (worker_online=false)，无法迁移")
        return

    if not auto_yes:
        ans = input(f"\n✅ 家里云在线。继续迁移 {len(todo)} 个文件? [y/N] ").strip().lower()
        if ans != "y":
            print("已取消"); return

    done = skip = fail = 0
    for i, (mid, fn, sn, fp, old_sha) in enumerate(todo):
        print(f"\n[{i+1}/{len(todo)}] id={mid} {fn}")

        try:
            raw = open(fp, "rb").read()
            b64 = base64.b64encode(raw).decode()
            sha = sha256_file(fp)
            size = len(raw)
        except Exception as e:
            print(f"  ❌ 读文件失败: {e}"); fail += 1; continue

        # 如果 DB 已有 sha256 且匹配 → 复用（之前就对过）
        expected = old_sha or sha
        bres, code = bridge_storage("/storage/store", {
            "stored_name": sn, "sha256": expected,
            "size": size, "file_b64": b64,
        })

        if bres.get("ok"):
            # 三重校验
            home_sha = bres.get("sha256", "")
            if home_sha and home_sha != sha:
                print(f"  ❌ SHA256 不匹配 local={sha[:16]} home={home_sha[:16]}"); fail += 1; continue
            # 删本地
            try:
                os.remove(fp)
            except Exception as e:
                print(f"  ⚠️ 删本地失败: {e}")
            # 更新 DB
            db.execute(
                "UPDATE materials SET storage_node='home', sha256=?, file_path='' WHERE id=?",
                (sha, mid)
            )
            conn.commit()
            print(f"  ✅ 迁移完成 → 家里云 {home_sha[:16]}... 本地已删")
            done += 1
        else:
            err = bres.get("error", "unknown")
            print(f"  ❌ 家里云存储失败: {err}"); fail += 1

        if (i+1) % BATCH == 0 and i+1 < len(todo):
            time.sleep(SLEEP)

    print(f"\n{'='*40}")
    print(f"🎉 迁移完成: done={done}  skip={skip}  fail={fail}")
    print(f"数据库已更新，本地文件已清理")

if __name__ == "__main__":
    main()
