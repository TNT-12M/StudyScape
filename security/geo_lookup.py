#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
地理位置补全脚本（IP → 地址）
- 从 security_ips 表中找出没有地理位置的异常 IP
- 通过 ip-api.com 免费接口查询地理位置
- 更新数据库中的 location 字段
- 配合 PHP 快速扫描使用：PHP 负责检测异常，Python 负责补地理位置
- 用法：python geo_lookup.py <数据库文件路径>
"""
import sys
import json
import sqlite3
import time
import urllib.request
import urllib.error
from pathlib import Path


def query_location(ip):
    """查询单个 IP 的地理位置，失败返回空字符串"""
    url = f"http://ip-api.com/json/{ip}?lang=zh-CN&fields=status,country,regionName,city,isp,message"
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'StudyScape-Security/1.0'})
        with urllib.request.urlopen(req, timeout=3) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            if data.get('status') == 'success':
                country = data.get('country', '')
                region = data.get('regionName', '')
                city = data.get('city', '')
                isp = data.get('isp', '')
                parts = [p for p in [country, region, city] if p]
                loc = ' '.join(parts)
                if isp:
                    loc += f' ({isp})'
                return loc
    except Exception as e:
        print(f"  查询 {ip} 失败: {e}", file=sys.stderr)
    return ''


def main():
    if len(sys.argv) < 2:
        print("用法: geo_lookup.py <数据库文件路径>")
        sys.exit(1)

    db_path = Path(sys.argv[1])
    if not db_path.exists():
        print(f"数据库文件不存在: {db_path}")
        sys.exit(1)

    print(f"开始补全地理位置，数据库: {db_path}")

    conn = sqlite3.connect(str(db_path))
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()

    # 查询所有没有地理位置的异常 IP（限制每次最多 100 个，ip-api 免费版限速 45次/分钟）
    cursor.execute(
        "SELECT ip FROM security_ips WHERE location = '' OR location IS NULL ORDER BY risk_level DESC, api_count DESC LIMIT 100"
    )
    rows = cursor.fetchall()

    if not rows:
        print("所有 IP 都已有地理位置，无需查询")
        conn.close()
        return

    ip_list = [row['ip'] for row in rows]
    print(f"待查询 IP 数量: {len(ip_list)}")

    success = 0
    failed = 0
    # ip-api 免费版限速 45次/分钟，这里稍微保守一点，每秒 1 次
    # 实际上 45次/分钟 = 1.33秒/次，用 1.5秒间隔更安全
    interval = 1.5

    for i, ip in enumerate(ip_list):
        print(f"[{i+1}/{len(ip_list)}] 查询 {ip} ... ", end='', flush=True)
        location = query_location(ip)
        if location:
            cursor.execute(
                "UPDATE security_ips SET location = ? WHERE ip = ?",
                (location, ip)
            )
            success += 1
            print(f"✓ {location}")
        else:
            failed += 1
            print("✗ 失败")

        # 最后一个不用等
        if i < len(ip_list) - 1:
            time.sleep(interval)

    conn.commit()
    conn.close()

    print(f"\n完成：成功 {success} 个，失败 {failed} 个")


if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        print(f"致命错误: {e}", file=sys.stderr)
        sys.exit(1)
