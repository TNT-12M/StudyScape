#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Nginx 访问日志安全监控脚本
- 逐行读取 Nginx combined 格式日志
- 统计每个 IP 的 api.php 请求次数
- 标记异常 IP（24h 内 API 请求超阈值）
- 标记扫描特征（常见漏洞路径、大量 404）
- 结果写入 SQLite 的 security_ips 表
- 新增异常 IP 时给管理员发站内通知
"""
import re
import sys
import os
import json
import sqlite3
import time
import urllib.request
import urllib.parse
from datetime import datetime, timedelta
from pathlib import Path

# ====== 配置 ======
API_THRESHOLD = 3000          # 24h 内 API 请求阈值（提高到 3000，避免正常用户误报）
SCAN_PATH_THRESHOLD = 50      # 扫描特征路径数量阈值，超过才标记 high
WINDOW_HOURS = 24             # 统计时间窗口（小时）
HIGH_404_RATIO = 0.40         # 404 比例阈值（超过这个比例且 >200 次才算 high）
MIN_404_FOR_RATIO = 200       # 404 比例判断的最小请求数

# IP 白名单：这些 IP 不参与异常检测（如管理员自己的 IP、内网 IP 等）
# 可通过 security.env 文件中的 SECURITY_WHITELIST_IPS 配置，逗号分隔
WHITELIST_IPS = set()
env_path = Path(__file__).resolve().parent.parent / 'security.env'
if env_path.exists():
    try:
        with open(env_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line.startswith('SECURITY_WHITELIST_IPS='):
                    ips = line[len('SECURITY_WHITELIST_IPS='):].split(',')
                    for ip in ips:
                        ip = ip.strip()
                        if ip:
                            WHITELIST_IPS.add(ip)
    except Exception:
        pass

# 常见扫描/漏洞探测路径特征（只保留明显的攻击特征，避免误匹配正常业务）
SCAN_PATTERNS = [
    r'\.env', r'\.git', r'\.svn', r'\.htaccess', r'\.htpasswd',
    r'phpmyadmin', r'pma[_\-]', r'myadmin', r'phpinfo',
    r'wp-admin', r'wp-login', r'wordpress', r'wp-content',
    r'admin\.php', r'config\.php', r'install\.php', r'setup\.php',
    r'/etc/passwd', r'/proc/self', r'\.\./', r'%2e%2e',
    r'xmlrpc\.php', r'actuator', r'jenkins',
    r'\.bak', r'\.swp', r'\.sql',
    r'union\s+select', r'<script', r'eval\(', r'base64_decode',
    r'fckeditor', r'kindeditor', r'ueditor', r'ckeditor',
]

# Nginx combined 格式正则
# 127.0.0.1 - - [05/Oct/2026:14:30:00 +0800] "GET /api.php?action=xxx HTTP/1.1" 200 1234 "-" "Mozilla/5.0 ..."
LOG_PATTERN = re.compile(
    r'^(?P<ip>\S+)\s+\S+\s+\S+\s+\[(?P<time>[^\]]+)\]\s+'
    r'"(?P<method>\S+)\s+(?P<path>\S+)\s+\S+"\s+(?P<status>\d+)\s+(?P<size>\S+)\s+'
    r'"(?P<referer>[^"]*)"\s+"(?P<ua>[^"]*)"'
)


def parse_log_time(time_str):
    """解析 Nginx 日志时间：05/Oct/2026:14:30:00 +0800"""
    try:
        # 去掉时区部分，简化处理
        dt_str = time_str.split()[0]
        return datetime.strptime(dt_str, "%d/%b/%Y:%H:%M:%S")
    except Exception:
        return None


def is_api_request(path):
    """判断是否为 API 请求（只统计 api.php）"""
    return '/api.php' in path or path.startswith('/api.php')


def has_scan_pattern(path):
    """检查路径是否包含扫描特征"""
    path_lower = path.lower()
    for pat in SCAN_PATTERNS:
        if re.search(pat, path_lower, re.IGNORECASE):
            return True
    return False


def get_ip_location(ip):
    """查询 IP 地理位置，失败返回空字符串。
    使用 ip-api.com 免费接口（非商业用途，每分钟 45 次限制）。
    超时 2 秒，避免拖慢扫描。
    """
    try:
        url = f"http://ip-api.com/json/{urllib.parse.quote(ip)}?lang=zh-CN&fields=status,country,regionName,city,isp"
        req = urllib.request.Request(url, headers={'User-Agent': 'StudyScape-Security/1.0'})
        with urllib.request.urlopen(req, timeout=2) as resp:
            data = json.loads(resp.read().decode('utf-8'))
        if data.get('status') == 'success':
            parts = []
            if data.get('country'): parts.append(data['country'])
            if data.get('regionName'): parts.append(data['regionName'])
            if data.get('city'): parts.append(data['city'])
            if data.get('isp'): parts.append(f"({data['isp']})")
            return ' '.join(parts)
    except Exception:
        pass
    return ''


def update_scan_status(db_path, status, result_info='', total_ip_count=0, abnormal_ip_count=0):
    """更新扫描状态到数据库（失败时也调用，确保前端能看到错误）"""
    try:
        conn = sqlite3.connect(str(db_path), timeout=5)
        cursor = conn.cursor()
        cursor.execute("SELECT id FROM security_scan_log WHERE scan_type='nginx_log' AND status='running' ORDER BY id DESC LIMIT 1")
        row = cursor.fetchone()
        if row:
            cursor.execute(f'''
                UPDATE security_scan_log
                SET status=?, finished_at=?, result_info=?,
                    total_ip_count=?, abnormal_ip_count=?
                WHERE id=?
            ''', [
                status,
                datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                result_info,
                total_ip_count,
                abnormal_ip_count,
                row[0]
            ])
            conn.commit()
        conn.close()
    except Exception as e:
        print(f"更新扫描状态失败: {e}")


def main():
    if len(sys.argv) < 3:
        print("用法: log_monitor.py <日志文件路径> <数据库文件路径>")
        sys.exit(1)

    log_path = Path(sys.argv[1])
    db_path = Path(sys.argv[2])

    if not log_path.exists():
        print(f"日志文件不存在: {log_path}")
        update_scan_status(db_path, 'failed', f'日志文件不存在: {log_path}')
        sys.exit(1)
    if not db_path.exists():
        print(f"数据库文件不存在: {db_path}")
        sys.exit(1)

    try:
        _main(log_path, db_path)
    except Exception as e:
        import traceback
        error_msg = f'{e}\n{traceback.format_exc()}'
        print(f"扫描出错: {error_msg}")
        update_scan_status(db_path, 'failed', f'脚本异常: {e}')
        sys.exit(1)


def _main(log_path, db_path):

    # 时间窗口：最近 24 小时
    cutoff = datetime.now() - timedelta(hours=WINDOW_HOURS)

    # IP 统计：ip -> {api_count, scan_count, total_count, status_404, last_seen}
    ip_stats = {}

    # 逐行读取，避免一次性加载大文件
    print(f"开始扫描日志: {log_path}")
    line_count = 0
    matched = 0
    try:
        with open(log_path, 'r', encoding='utf-8', errors='ignore') as f:
            for line in f:
                line_count += 1
                if line_count % 100000 == 0:
                    print(f"已处理 {line_count} 行...")

                m = LOG_PATTERN.match(line.strip())
                if not m:
                    continue

                ip = m.group('ip')
                time_str = m.group('time')
                path = m.group('path')
                status = m.group('status')

                # 解析时间
                dt = parse_log_time(time_str)
                if not dt or dt < cutoff:
                    continue

                matched += 1

                # 初始化统计
                if ip not in ip_stats:
                    ip_stats[ip] = {
                        'api_count': 0,
                        'scan_count': 0,
                        'total_count': 0,
                        'status_404': 0,
                        'last_seen': dt,
                    }

                stat = ip_stats[ip]
                stat['total_count'] += 1
                if dt > stat['last_seen']:
                    stat['last_seen'] = dt

                # 统计 API 请求
                if is_api_request(path):
                    stat['api_count'] += 1

                # 统计 404
                if status == '404':
                    stat['status_404'] += 1

                # 统计扫描特征
                if has_scan_pattern(path):
                    stat['scan_count'] += 1
    except Exception as e:
        print(f"读取日志出错: {e}")
        sys.exit(1)

    print(f"日志处理完成: {line_count} 行, {matched} 条匹配, {len(ip_stats)} 个唯一 IP")

    # 筛选异常 IP
    suspicious_ips = []
    for ip, stat in ip_stats.items():
        # 跳过白名单 IP
        if ip in WHITELIST_IPS:
            continue

        # 跳过内网 IP
        if ip.startswith('127.') or ip.startswith('192.168.') or ip.startswith('10.') or ip.startswith('172.16.'):
            continue
        if ip.startswith('::1') or ip.startswith('fe80:'):
            continue

        is_abnormal = stat['api_count'] >= API_THRESHOLD
        # 404 比例异常高（超过 40% 且 404 次数 > 200）也是高风险
        high_404_ratio = (stat['total_count'] >= MIN_404_FOR_RATIO and
                          stat['status_404'] / stat['total_count'] >= HIGH_404_RATIO)
        # 只有扫描特征多 OR 404 比例高才算 high，否则只是 medium
        is_high_risk = (stat['scan_count'] >= SCAN_PATH_THRESHOLD) or high_404_ratio

        if is_abnormal:
            suspicious_ips.append({
                'ip': ip,
                'api_count': stat['api_count'],
                'scan_count': stat['scan_count'],
                'total_count': stat['total_count'],
                'status_404': stat['status_404'],
                'last_seen': stat['last_seen'].strftime('%Y-%m-%d %H:%M:%S'),
                'risk_level': 'high' if is_high_risk else 'medium',
            })

    # 按 API 请求数降序
    suspicious_ips.sort(key=lambda x: x['api_count'], reverse=True)
    print(f"发现异常 IP: {len(suspicious_ips)} 个")

    # 写入数据库
    conn = sqlite3.connect(str(db_path))
    cursor = conn.cursor()

    # 确保表存在
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS security_ips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL UNIQUE,
            api_count INTEGER DEFAULT 0,
            scan_count INTEGER DEFAULT 0,
            total_count INTEGER DEFAULT 0,
            status_404 INTEGER DEFAULT 0,
            risk_level TEXT DEFAULT 'medium',
            location TEXT DEFAULT '',
            last_seen TEXT,
            first_detected TEXT,
            updated_at TEXT,
            notified INTEGER DEFAULT 0
        )
    ''')
    conn.commit()

    # ===== 第一步：先写入所有异常 IP（不查位置，快速完成扫描）=====
    new_abnormal_count = 0
    pending_notify_ips = []  # 需要发通知的 IP，后面批量查位置

    for item in suspicious_ips:
        # 检查是否已存在，顺便看有没有位置信息
        cursor.execute('SELECT id, risk_level, notified, location FROM security_ips WHERE ip=?', [item['ip']])
        row = cursor.fetchone()

        if row:
            # 已存在，更新统计数据
            cursor.execute('''
                UPDATE security_ips SET
                    api_count=?, scan_count=?, total_count=?, status_404=?,
                    risk_level=?, last_seen=?, updated_at=?
                WHERE ip=?
            ''', [
                item['api_count'], item['scan_count'], item['total_count'],
                item['status_404'], item['risk_level'], item['last_seen'],
                datetime.now().strftime('%Y-%m-%d %H:%M:%S'), item['ip']
            ])
            # 只有 high 级别才发通知，medium 只记录不打扰管理员
            was_high = (row[1] == 'high')
            is_high_now = (item['risk_level'] == 'high')
            if is_high_now and (not was_high or row[2] == 0):
                cursor.execute('UPDATE security_ips SET notified=1 WHERE ip=?', [item['ip']])
                pending_notify_ips.append((item, row[3] or ''))  # (ip_data, existing_location)
        else:
            # 新异常 IP（先不查位置，快速写入）
            # 只有 high 级别才发通知
            is_high = (item['risk_level'] == 'high')
            cursor.execute('''
                INSERT INTO security_ips
                    (ip, api_count, scan_count, total_count, status_404,
                     risk_level, location, last_seen, first_detected, updated_at, notified)
                VALUES (?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?)
            ''', [
                item['ip'], item['api_count'], item['scan_count'], item['total_count'],
                item['status_404'], item['risk_level'], item['last_seen'],
                datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                1 if is_high else 0,  # 只有 high 才标记已通知
            ])
            if is_high:
                pending_notify_ips.append((item, ''))

    # 清理不再异常的 IP（如果这次扫描里没出现，且最后出现时间超过 48 小时）
    if suspicious_ips:
        placeholders = ','.join(['?'] * len(suspicious_ips))
        ip_list = [s['ip'] for s in suspicious_ips]
        cursor.execute(f'''
            DELETE FROM security_ips
            WHERE ip NOT IN ({placeholders})
            AND last_seen < ?
        ''', ip_list + [(datetime.now() - timedelta(hours=48)).strftime('%Y-%m-%d %H:%M:%S')])

    conn.commit()

    # ===== 第二步：立即更新扫描状态为 finished（让用户立刻看到结果）=====
    cursor.execute("SELECT id FROM security_scan_log WHERE scan_type='nginx_log' AND status='running' ORDER BY id DESC LIMIT 1")
    row = cursor.fetchone()
    if row:
        cursor.execute('''
            UPDATE security_scan_log
            SET status='finished', finished_at=?,
                total_ip_count=?, abnormal_ip_count=?,
                result_info=?
            WHERE id=?
        ''', [
            datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            len(ip_stats),
            len(suspicious_ips),
            f'处理 {line_count} 行日志，发现 {len(suspicious_ips)} 个异常 IP，正在补充地理位置信息...',
            row[0]
        ])
        conn.commit()

    print(f"扫描主流程完成，发现 {len(suspicious_ips)} 个异常 IP，开始补充地理位置和发送通知...")

    # ===== 第三步：补充地理位置 + 发送通知（后台慢慢处理）=====
    # 注意：ip-api.com 免费版限制 45 次/分钟，所以这里控制速率
    for idx, (item, existing_location) in enumerate(pending_notify_ips):
        try:
            # 已有位置就直接用
            location = existing_location
            if not location:
                location = get_ip_location(item['ip'])
                # 更新位置到数据库
                cursor.execute('UPDATE security_ips SET location=? WHERE ip=?', [location, item['ip']])
                conn.commit()

            # 发送通知
            cursor.execute('UPDATE security_ips SET notified=1 WHERE ip=?', [item['ip']])
            _insert_notification(cursor, item, location)
            conn.commit()
            new_abnormal_count += 1

            # 免费接口限速：45次/分钟 ≈ 每1.3秒一个，这里留余量每1.5秒
            # 但只在需要查询时才限速，已有位置的不用等
            if not existing_location:
                import time
                time.sleep(1.5)
        except Exception as e:
            print(f"处理 IP {item['ip']} 通知时出错: {e}")
            continue

    # 更新扫描结果详情
    if row:
        cursor.execute('''
            UPDATE security_scan_log
            SET result_info=?
            WHERE id=?
        ''', [
            f'处理 {line_count} 行日志，发现 {len(suspicious_ips)} 个异常 IP，已发送 {new_abnormal_count} 条通知',
            row[0]
        ])
        conn.commit()

    conn.close()

    print(f"新增/更新异常 IP 通知: {new_abnormal_count} 个")
    print("扫描完成")


def _insert_notification(cursor, item, location):
    """插入站内通知给 root 用户（lian）。"""
    risk_label = '高度可疑' if item['risk_level'] == 'high' else '异常'
    title = f'安全预警：检测到{risk_label}IP {item["ip"]}'
    content = (
        f'IP 地址：{item["ip"]}\n'
        f'风险等级：{risk_label}\n'
        f'地理位置：{location or "未知"}\n'
        f'24h API 请求：{item["api_count"]} 次\n'
        f'扫描特征请求：{item["scan_count"]} 次\n'
        f'总请求数：{item["total_count"]} 次\n'
        f'404 次数：{item["status_404"]} 次\n'
        f'最近活跃：{item["last_seen"]}'
    )

    # 找 root 用户（is_initial_root=1 或 username='lian'）
    cursor.execute("SELECT id FROM users WHERE is_initial_root=1 OR username='lian' LIMIT 1")
    row = cursor.fetchone()
    if not row:
        return
    user_id = row[0]

    # 检查通知表是否存在
    cursor.execute("SELECT name FROM sqlite_master WHERE type='table' AND name='notifications'")
    if not cursor.fetchone():
        return

    cursor.execute('''
        INSERT INTO notifications (user_id, title, body, type, created_at)
        VALUES (?, ?, ?, 'security', ?)
    ''', [user_id, title, content, datetime.now().strftime('%Y-%m-%d %H:%M:%S')])


if __name__ == '__main__':
    main()
