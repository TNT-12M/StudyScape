#!/usr/bin/env python3
"""Migrate existing materials to home storage. Dry-run unless --yes is given."""

import argparse
import base64
import hashlib
import json
import mimetypes
import os
import sqlite3
import urllib.error
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB = os.path.join(ROOT, "exam.db")
MAT_DIR = os.path.join(ROOT, "materials")
BRIDGE = "http://127.0.0.1:1201"


def sha256_file(path):
    digest = hashlib.sha256()
    with open(path, "rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def bridge_storage(path, body):
    request = urllib.request.Request(
        f"{BRIDGE}{path}", data=json.dumps(body).encode("utf-8"),
        headers={"Content-Type": "application/json"}, method="POST",
    )
    try:
        with urllib.request.urlopen(request, timeout=190) as response:
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
    parser.add_argument("--yes", action="store_true", help="执行迁移；默认仅 dry-run")
    parser.add_argument("--db", default=DB)
    parser.add_argument("--materials-dir", default=MAT_DIR)
    parser.add_argument("--bridge", default=BRIDGE)
    args = parser.parse_args()
    BRIDGE = args.bridge.rstrip("/")

    connection = sqlite3.connect(args.db)
    connection.row_factory = sqlite3.Row
    rows = connection.execute("SELECT * FROM materials ORDER BY id").fetchall()

    candidates = []
    for row in rows:
        ready_at_home = (row["storage_node"] == "home"
                         and (not is_pdf(row)
                              or (row["platform_watermark_status"] == "ready"
                                  and int(row["platform_watermark_version"] or 0) == 1)))
        if ready_at_home:
            continue
        path = os.path.join(args.materials_dir, row["stored_name"])
        if os.path.isfile(path):
            candidates.append((row, path))
        else:
            print(f"SKIP id={row['id']} 本地原件不存在: {path}")

    print(f"待处理 {len(candidates)} 条；模式={'执行' if args.yes else 'dry-run'}")
    for row, path in candidates:
        kind = "PDF平台水印" if is_pdf(row) else "原样上传"
        print(f"  id={row['id']} {kind} {row['filename']} ({os.path.getsize(path)} bytes)")
    if not args.yes:
        print("dry-run 完成；传入 --yes 才会上传、更新数据库并删除旧本地原件。")
        return

    done = failed = 0
    for row, path in candidates:
        source_sha = sha256_file(path)
        source_size = os.path.getsize(path)
        pdf = is_pdf(row)
        try:
            with open(path, "rb") as stream:
                encoded = base64.b64encode(stream.read()).decode("ascii")
        except OSError as error:
            print(f"FAIL id={row['id']} 读取失败: {error}")
            failed += 1
            continue

        response = bridge_storage("/storage/store", {
            "stored_name": row["stored_name"], "sha256": source_sha,
            "size": source_size, "mime": row["mime_type"] or mimetypes.guess_type(path)[0] or "",
            "file_b64": encoded, "watermark_platform": pdf, "title": row["filename"],
        })
        final_sha = str(response.get("sha256") or "")
        final_size = int(response.get("size") or 0)
        home_source_sha = str(response.get("source_sha256") or "")
        watermark_version = int(response.get("watermark_version") or 0)
        if (not response.get("ok") or home_source_sha != source_sha or not final_sha
                or final_size <= 0 or (pdf and watermark_version != 1)
                or (not pdf and (final_sha != source_sha or final_size != source_size))):
            print(f"FAIL id={row['id']} 上传或校验失败: {response.get('error', response)}")
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
            print(f"FAIL id={row['id']} 数据库更新失败，保留本地原件: {error}")
            failed += 1
            continue

        try:
            os.remove(path)
            print(f"OK id={row['id']} final_sha={final_sha[:16]} size={final_size}")
        except OSError as error:
            print(f"WARN id={row['id']} 已提交数据库，但删除旧本地原件失败: {error}")
        done += 1

    connection.close()
    print(f"完成: success={done} failed={failed}")


if __name__ == "__main__":
    main()
