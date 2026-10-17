# StudyScape OCR Bridge 部署文档

> **版本**: v1.0 | **更新**: 2026-10-18 | **适用**: Ubuntu 22.04

---

## 0. 架构总览

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        公网服务器 120.79.161.207                       │
│                        (阿里云 / 腾讯云, 2GB+ RAM)                     │
│                                                                         │
│  Nginx ──▶ PHP-FPM ──▶ api.php                                          │
│                          │                                              │
│                          │ curl POST 127.0.0.1:1201/upload              │
│                          ▼                                              │
│               ┌──────────────────────────────┐                          │
│               │ bridge_server.py (asyncio)    │                          │
│               │ ── 0.0.0.0:1200  TCP relay ──◀── Challenge-Response   │
│               │ ── 127.0.0.1:1201 HTTP API ──▶ PHP-FPM               │
│               └──────────────▲───────────────┘                          │
└──────────────────────────────┼──────────────────────────────────────────┘
                               │ TCP 长连接 (加密 HMAC, 防火墙放行 1200)
                               │
┌──────────────────────────────┼──────────────────────────────────────────┐
│                        家里云 ssh home                                   │
│                        (16GB RAM, 8 核 CPU)                             │
│                              │                                          │
│               ┌──────────────┴───────────────┐                          │
│               │ bridge_client.py (asyncio)   │                          │
│               │ ── 主动连 120.79.161.207:1200│                          │
│               │ ── 指数退避自动重连            │                          │
│               │ ── 心跳保活                    │                          │
│               └──────────────▲───────────────┘                          │
│                              │                                          │
│               ┌──────────────┴───────────────┐                          │
│               │ MinerU Flash Tier parse-server│                          │
│               │ systemd: mineru-flash.service │                          │
│               │ 127.0.0.1:16580               │                          │
│               │ PP-DocLayoutV2 + PP-OCRv6    │                          │
│               └──────────────▲───────────────┘                          │
│                              │                                          │
│               ┌──────────────┴───────────────┐                          │
│               │ exam_pipeline.py (Python CLI) │                          │
│               │ PDF → Contract B JSON        │                          │
│               │ (11/12 单选 91.7% 准确率)     │                          │
│               └──────────────────────────────┘                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 两台机器角色分工

| | 公网服务器 (120.79.161.207) | 家里云 ssh home |
|---|---|---|
| **职责** | PHP 前端 + MariaDB + **bridge_server** | **MinerU OCR + bridge_client + pipeline** |
| **内存要求** | ≥ 2GB（bridge_server 几乎不吃内存） | ≥ 8GB（MinerU ONNX 需要 2-4GB） |
| **Python 依赖** | 标准库即可（asyncio, hmac, hashlib, http.server） | MinerU 4.x + onnxruntime ~500MB |
| **需要公网** | ✅ 需要（bridge_server 监听公网） | ❌ 不需要（主动连公网） |
| **运行服务** | Nginx, PHP-FPM, MariaDB, **studyscape-bridge** | SSH, **mineru-flash**, **studyscape-bridge-client** |

---

## 1. 公网服务器部署 (120.79.161.207)

### 1.1 前置：代码同步

```bash
cd /home/lian/StudyScape
git pull origin main
```

### 1.2 前置：Python 版本

bridge_server 只用到 Python 标准库，需要 ≥ 3.8。

```bash
python3 --version   # 应该 >= 3.8
# 没有就: sudo apt install python3
```

### 1.3 生成共享密钥

**密钥必须在公网服务器和家里云上完全一致，且不能提交到 Git。**

```bash
# 生成一个 64 hex (32 字节) 的安全密钥
python3 -c "import secrets; print(secrets.token_hex(32))"
# 示例输出: 80b2b9e8944b724b8201b268838a1d234879aa710ce5b6cf02521fdcb7ab3bf8
```

写入文件（**不要手动输入错误**，复制粘贴）：

```bash
echo '把上一步生成的密钥粘贴这里' > /home/lian/StudyScape/ocr_bridge/bridge_key.txt
chmod 600 /home/lian/StudyScape/ocr_bridge/bridge_key.txt

# 验证
cat /home/lian/StudyScape/ocr_bridge/bridge_key.txt
# → 64 个 hex 字符, 无空格无换行
```

### 1.4 防火墙放行 1200 端口

bridge_server 在公网监听 TCP 1200，家里云要能连进来。

```bash
# Ubuntu UFW
sudo ufw allow 1200/tcp
sudo ufw reload

# 或 iptables
sudo iptables -A INPUT -p tcp --dport 1200 -j ACCEPT
sudo iptables-save | sudo tee /etc/iptables/rules.v4
```

云服务商安全组也要放行：
- 阿里云 → 安全组 → 入方向 → TCP 1200 允许 0.0.0.0/0
- 腾讯云同

### 1.5 systemd 服务安装

```bash
# 复制 unit 文件
sudo cp /home/lian/StudyScape/ocr_bridge/studyscape-bridge.service /etc/systemd/system/

# 重载 + 开机自启 + 立刻启动
sudo systemctl daemon-reload
sudo systemctl enable studyscape-bridge
sudo systemctl start studyscape-bridge

# 检查状态
sudo systemctl status studyscape-bridge
# → Active (running)
```

### 1.6 验证公网服务器端

```bash
# ① HTTP 本地接口（1201 应该在线）
curl -s http://127.0.0.1:1201/health
# → {"bridge":"ok","worker_online":false}
#   worker_online=false 正常！因为家里云还没连进来

# ② TCP 1200 端口监听
ss -tlnp | grep 1200
# → LISTEN 0 128 0.0.0.0:1200

# ③ 伪装 404 验证（不带密钥扫端口会被返回 HTTP 404）
curl -sv http://127.0.0.1:1200/ 2>&1 | head -20
# → 应该看到 HTTP/1.1 404 Not Found, 然后连接关闭

# ④ 实时日志
sudo journalctl -u studyscape-bridge -f
```

---

## 2. 家里云部署 (ssh home)

### 2.1 前置：MinerU Flash Tier

家里云需要先装好 MinerU 并跑起 parse-server。

```bash
# 检查 MinerU 版本
python3 -c "import mineru; print(mineru.__version__)"
# → 应该 >= 4.0

# 检查 parse-server systemd
sudo systemctl status mineru-flash.service
# → Active (running)

# 手动测试 parse-server
curl -s http://127.0.0.1:16580/v1/parse/jobs -X POST \
  -H "Content-Type: application/json" \
  -d '{"files":[{"source":{"type":"local","path":"/home/lian/StudyScape/文科数学试卷.pdf"}}],"tier":"flash","output_formats":["middle_json"]}'
# → 应该返回 job_id
```

如果 mineru-flash.service 还没配置：

```bash
# 创建 systemd unit
cat <<'EOF' | sudo tee /etc/systemd/system/mineru-flash.service
[Unit]
Description=MinerU Flash Tier Parse Server
After=network.target

[Service]
Type=simple
User=lian
WorkingDirectory=/home/lian/StudyScape
Environment=LD_LIBRARY_PATH=/home/lian/.local/lib/python3.12/site-packages/mineru_llama_cpp/bin
ExecStart=/usr/bin/python3 -m mineru.parser.api_server --tier flash --host 127.0.0.1 --port 16580 --allow-local-source --log-level warning
Restart=on-failure
RestartSec=3
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable mineru-flash
sudo systemctl start mineru-flash
```

### 2.2 前置：exam_pipeline.py

```bash
cd /home/lian/StudyScape
git pull origin main

# 手动跑一次 pipeline 验证
python3 exam_pipeline.py "文科数学试卷.pdf" -o /tmp/test_result.json \
  --subject 数学 --education_level 高中 --paper_name "测试卷"
# 应该看到 [MinerU] ✅ PageJSON: 3 页, ... 然后输出 JSON
```

### 2.3 写入共享密钥

**必须和公网服务器上那份完全一致！**

```bash
# 方式 A: scp 从公网服务器拷过来（推荐，避免手敲错误）
scp lian@120.79.161.207:/home/lian/StudyScape/ocr_bridge/bridge_key.txt \
     /home/lian/StudyScape/ocr_bridge/bridge_key.txt

# 方式 B: 手动粘贴（要非常小心）
mkdir -p /home/lian/StudyScape/ocr_bridge
echo '和公网服务器上完全一样的 64 hex' > /home/lian/StudyScape/ocr_bridge/bridge_key.txt

chmod 600 /home/lian/StudyScape/ocr_bridge/bridge_key.txt
```

### 2.4 systemd 服务安装

```bash
# 复制 unit 文件
sudo cp /home/lian/StudyScape/ocr_bridge/studyscape-bridge-client.service /etc/systemd/system/

# 重载 + 开机自启 + 启动
sudo systemctl daemon-reload
sudo systemctl enable studyscape-bridge-client
sudo systemctl start studyscape-bridge-client

# 检查状态
sudo systemctl status studyscape-bridge-client
# → Active (running)
```

### 2.5 验证家里云端

```bash
# ① 日志里应该看到认证成功
sudo journalctl -u studyscape-bridge-client -f
# 正常输出:
#   连接 120.79.161.207:1200 ...
#   ✅ 认证通过, 等待任务 ...

# 如果看到 "HMAC 不匹配": 密钥和公网服务器不一致, 检查两边 bridge_key.txt
# 如果看到 "连接失败 Connection refused": 公网 1200 端口没开或防火墙挡了
# 如果看到指数退避: 服务器可达但认证失败, 查密钥

# ② 公网服务器端也应该显示在线
curl -s http://120.79.161.207:1201/health
# → {"bridge":"ok","worker_online":true}
```

---

## 3. 端到端测试

两边都部署好后，走一次完整链路：

```
步骤 1: 公网服务器 PHP 端提交任务 (模拟)
步骤 2: 家里云 bridge_client 收到 → 调 MinerU pipeline
步骤 3: 结果回传公网 bridge_server
步骤 4: PHP 轮询 status → 拿到 JSON
```

### 3.1 手动 API 测试

在公网服务器上（文件已 base64 编码好，用现有的 PDF）：

```bash
# 把测试 PDF 转成 base64 (一行)
B64=$(base64 -w0 /home/lian/StudyScape/文科数学试卷.pdf)

# 提交任务到 bridge_server
curl -s -X POST http://127.0.0.1:1201/upload \
  -H "Content-Type: application/json" \
  -d "{
    \"filename\": \"文科数学试卷.pdf\",
    \"file_b64\": \"${B64}\",
    \"subject\": \"数学\",
    \"education_level\": \"高中\"
  }"
# → {"job_id":"job_xxx","status":"queued"}
```

### 3.2 家里云日志观察

```bash
sudo journalctl -u studyscape-bridge-client -f
# 应该看到:
#   📥 收到任务 job_xxx: 文科数学试卷.pdf
#   处理 文科数学试卷.pdf (xxxKB base64) → /home/lian/StudyScape/ocr_bridge/_tmp_jobs/job_xxx_...
#   [MinerU] ✅ PageJSON: 3 页, ...
#   ✅ job_xxx 完成 → 15 道题
```

### 3.3 公网服务器查结果

```bash
# 等待家里云处理完 (约 20-60 秒)
curl -s http://127.0.0.1:1201/status/job_xxx | python3 -m json.tool
# → {
#     "job_id": "job_xxx",
#     "status": "done",
#     "filename": "文科数学试卷.pdf",
#     "result": { ... Contract B JSON ... }
#   }
```

---

## 4. 日常运维

### 4.1 启动/停止/重启

```bash
# 公网服务器
sudo systemctl {start|stop|restart|status} studyscape-bridge

# 家里云
sudo systemctl {start|stop|restart|status} mineru-flash
sudo systemctl {start|stop|restart|status} studyscape-bridge-client
```

### 4.2 查看日志

```bash
# 公网 bridge_server
sudo journalctl -u studyscape-bridge -f --since "10 min ago"

# 家里云 bridge_client
sudo journalctl -u studyscape-bridge-client -f --since "10 min ago"

# MinerU parse-server (家里云)
sudo journalctl -u mineru-flash -f --since "30 min ago"
```

### 4.3 更新 (Git pull)

两边都要 pull：

```bash
# 公网服务器
cd /home/lian/StudyScape
git pull && sudo systemctl restart studyscape-bridge

# 家里云
cd /home/lian/StudyScape
git pull && sudo systemctl restart studyscape-bridge-client mineru-flash
```

密钥文件不会被 Git 覆盖（已 gitignore）。

### 4.4 清理临时文件

家里云 pipeline 运行会在 `ocr_bridge/_tmp_jobs/` 生成临时文件，正常完成后会自动清理。如果有残留：

```bash
rm -rf /home/lian/StudyScape/ocr_bridge/_tmp_jobs/*
```

---

## 5. 故障排查

| 症状 | 可能原因 | 排查方法 |
|------|---------|---------|
| `worker_online: false` 一直不变 | 家里云没连上来 | ① 家里云 `systemctl status studyscape-bridge-client`<br>② 公网 `ss -tlnp \| grep 1200` 确认监听<br>③ 家里云 `curl -v telnet://120.79.161.207:1200` 测试连通性 |
| 家里云日志 "HMAC 不匹配" | 两边密钥不一致 | ① `cat ocr_bridge/bridge_key.txt` 对比两边<br>② 用 `scp` 从公网拷一份过来确保一致 |
| 家里云无限重连 | 公网 1200 未监听 / 防火墙挡了 | ① 公网 `sudo systemctl status studyscape-bridge`<br>② 公网 `sudo ufw status`<br>③ 云服务商安全组 |
| 提交任务返回 503 `worker offline` | 家里云不在线 | 先排查上面 worker_online 问题 |
| 任务超时 / 失败 | MinerU parse-server 挂了 | ① 家里云 `systemctl status mineru-flash`<br>② `curl http://127.0.0.1:16580/v1/health` |
| 密钥文件不存在 | git clone 后忘记手动创建 | 按 1.3 / 2.3 步骤写入，密钥**不入库** |

---

## 6. 文件清单

### 代码库 (git 管理)

| 路径 | 说明 |
|------|------|
| `ocr_bridge/bridge_server.py` | 公网 relay (asyncio TCP 1200 + HTTP 1201) |
| `ocr_bridge/bridge_client.py` | 家里云 worker (主动连 + 重连 + 调 pipeline) |
| `ocr_bridge/studyscape-bridge.service` | 公网 systemd unit |
| `ocr_bridge/studyscape-bridge-client.service` | 家里云 systemd unit |
| `exam_pipeline.py` | PDF → Contract B JSON (MinerU flash tier) |
| `docs/OCR_BRIDGE_DEPLOY.md` | 本文档 |
| `.gitignore` | 排除 `ocr_bridge/bridge_key.txt` + `ocr_bridge/_tmp_jobs/` |

### 运行时 (不在 git 里)

| 路径 | 在哪台 | 说明 |
|------|--------|------|
| `ocr_bridge/bridge_key.txt` | 两边都有 | **密钥文件**，64 hex，权限 600 |
| `ocr_bridge/_tmp_jobs/` | 家里云 | pipeline 临时输入/输出文件 |
| systemd unit | 两边 `/etc/systemd/system/` | 由 `cp` 手动复制 |

---

## 7. 安全注意事项

1. **密钥永不入库** — `.gitignore` 已排除 `bridge_key.txt`，任何人不要 commit 它
2. **密钥长度 ≥ 32 字符** — 256-bit HMAC-SHA256 签名空间
3. **无密钥连接 → HTTP 404** — 不暴露 bridge_server 的存在，扫端口的人会以为 1200 是个 web 服务器但路径错了
4. **1201 HTTP 只绑 localhost** — PHP 接口不对外网开放
5. **bridge_key.txt 权限 600** — 只有 owner 能读
6. **不要把密钥写进邮件/聊天** — 用 `scp` 或 SSH 传递

---

## 8. 版本历史

| 版本 | 日期 | 变更 |
|------|------|------|
| v1.0 | 2026-10-18 | 初版：bridge_server + bridge_client + HMAC 认证 + systemd + 本文档 |
