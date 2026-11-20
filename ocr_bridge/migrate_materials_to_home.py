#!/usr/bin/env python3
"""Migrate existing home-storage materials in place. Dry-run unless --yes is given."""

import argparse
import json
import os
import sqlite3
import urllib.error
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB = os.path.join(ROOT, "exam.db")
BRIDGE = "http://127.0.0.1:1201"


def bridge_storage(path, body, timeout=310):
    request = urllib.request.Request(
        f"{BRIDGE}{path}", data=json.dumps(body).encode("utf-8"),
        headers={"Content-Type": "application/json"}, method="POST",
    )
    try:
        with urllib.request.urlopen(request, timeout=timeout) as response:
            return json.loads(response.read().decode("utf-8"))
    except urllib.error.HTTPError as error:
        try:
            return json.loads(error.read().decode("utf-8"))
        except (UnicodeDecodeError, json.JSONDecodeError):
            return {"ok": False, "error": f"HTTP {error.code}"}
    except Exception as error:
        return {"ok": False, "error": str(error)}


def is_pdf(row):
    return (str(row["mime_type"] or "").lower() == "application/pdf"
            or str(row["filename"] or "").lower().endswith(".pdf"))


def main():
    global BRIDGE
    parser = argparse.ArgumentParser()
    parser.add_argument("--yes", action="store_true", help="执行迁移；默认仅检查家里云文件")
    parser.add_argument("--db", default=DB)
    parser.add_argument("--bridge", default=BRIDGE)
    args = parser.parse_args()
    BRIDGE = args.bridge.rstrip("/")

    connection = sqlite3.connect(args.db)
    connection.row_factory = sqlite3.Row
    rows = connection.execute("SELECT * FROM materials ORDER BY id").fetchall()

    candidates = []
    missing = errors = 0
    for row in rows:
        ready_at_home = (row["storage_node"] == "home"
                         and (not is_pdf(row)
                              or (row["platform_watermark_status"] == "ready"
                                  and int(row["platform_watermark_version"] or 0) == 1)))
        if ready_at_home:
            continue
        inspected = bridge_storage("/storage/inspect", {"stored_name": row["stored_name"]}, timeout=70)
        if not inspected.get("ok"):
            if inspected.get("exists") is False:
                print(f"MISSING id={row['id']} 家里云文件不存在: {row['stored_name']}")
                missing += 1
            else:
                print(f"ERROR id={row['id']} 家里云文件读取失败: "
                      f"{row['stored_name']} ({inspected.get('error', '未知错误')})")
                errors += 1
            continue
        candidates.append(row)
        kind = "PDF平台水印" if is_pdf(row) else "登记家里云存储"
        print(f"FOUND id={row['id']} {kind} {row['filename']} ({inspected.get('size', 0)} bytes)")

    print(f"待处理 {len(candidates)} 条，家里云缺失 {missing} 条，读取异常 {errors} 条；"
          f"模式={'执行' if args.yes else 'dry-run'}")
    if not args.yes:
        connection.close()
        print("dry-run 完成；传入 --yes 才会在家里云原地加水印并更新数据库。")
        return

    done = failed = 0
    for row in candidates:
        pdf = is_pdf(row)
        response = bridge_storage("/storage/migrate-existing", {
            "stored_name": row["stored_name"],
            "watermark_platform": pdf,
            "title": row["filename"],
        })
        source_sha = str(response.get("source_sha256") or "")
        final_sha = str(response.get("sha256") or "")
        final_size = int(response.get("size") or 0)
        watermark_version = int(response.get("watermark_version") or 0)
        if (not response.get("ok") or not source_sha or not final_sha or final_size <= 0
                or (pdf and watermark_version != 1)
                or (not pdf and (final_sha != source_sha or watermark_version != 0))):
            print(f"FAIL id={row['id']} 家里云迁移或校验失败: {response.get('error', response)}")
            failed += 1
            continue

        try:
            connection.execute("BEGIN IMMEDIATE")
            connection.execute(
                "UPDATE materials SET storage_node='home', file_path='', source_sha256=?, "
                "sha256=?, file_size=?, revision=COALESCE(revision,1)+1, "
                "platform_watermark_version=?, platform_watermark_status=?, "
                "platform_watermarked_at=CASE WHEN ?='ready' THEN CURRENT_TIMESTAMP ELSE NULL END, "
                "processing_error=NULL, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                (source_sha, final_sha, final_size, 1 if pdf else None,
                 "ready" if pdf else "not_applicable", "ready" if pdf else "not_applicable", row["id"]),
            )
            connection.commit()
        except Exception as error:
            connection.rollback()
            print(f"FAIL id={row['id']} 数据库更新失败: {error}")
            failed += 1
            continue

        print(f"OK id={row['id']} final_sha={final_sha[:16]} size={final_size}")
        done += 1

    connection.close()
    print(f"完成: success={done} failed={failed} missing={missing}")


if __name__ == "__main__":
    main()
