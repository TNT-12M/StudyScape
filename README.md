# StudyScape

> **智能题库管理系统** — 从 PDF 试卷 OCR 识别 → 题目审核 → 入库管理，全流程自托管方案

[![Python](https://img.shields.io/badge/python-3.10%2B-blue)](https://www.python.org/)
[![PHP](https://img.shields.io/badge/php-8.1%2B-purple)](https://www.php.net/)
[![Vue](https://img.shields.io/badge/vue-3-42b883)](https://vuejs.org/)
[![SQLite](https://img.shields.io/badge/sqlite-3-003b57)](https://www.sqlite.org/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## ✨ 特色亮点

| 亮点 | 说明 |
|------|------|
| 🏠 **分布式 OCR 架构** | 公网服务器只做展示/DB，家里云（本地大机器）跑 MinerU OCR — 省钱、效果好、可横向扩展 |
| 🔑 **HMAC-SHA256 安全隧道** | 家里云主动连公网，Challenge-Response 认证；无密钥连接伪装成 HTTP 404 扫不到 |
| 🧠 **MinerU Flash Tier OCR** | PP-DocLayoutV2 版面识别 + PP-OCRv6 文字识别 + PP-FormulaNet 公式识别 — 纯 ONNX CPU 推理 |
| 📄 **PDF/图片 → 结构化 JSON** | 11/12 单选完整率 ~92%，自动切题、识别题型、提取选项、关联图片 |
| 🖼️ **图片内嵌进题干** | 题目里的图表/公式图片自动转 base64 塞进 `question_images`，前端直接预览 |
| ✅ **审核页预览 + 统一入库** | OCR 结果先过人工审核，确认无误再入库 — 避免脏数据污染题库 |
| 💰 **零云服务依赖** | 彻底废弃阿里云/腾讯云 OCR API，全部本地跑 — 无 API 费用、无数据外泄风险 |
| 🧩 **纯 OCR JSON 导入** | 自己的 pipeline 跑完后可直接上传 JSON，跳过 OCR 秒入审核 |

---

## 🏗️ 架构总览

```
┌──────────────────────────────────────────────────────────────────────────┐
│                           公网服务器 (小机器)                              │
│                           PHP-FPM + SQLite + Nginx                        │
│                                                                          │
│  前端 Vue3 ──▶ api.php ──▶ bridge_server.py (127.0.0.1:1201)             │
│                              │                                           │
│                              │ TCP HMAC 长连接 (0.0.0.0:1200)            │
└──────────────────────────────┼───────────────────────────────────────────┘
                               │
┌──────────────────────────────┼───────────────────────────────────────────┐
│                           家里云/本地大机器                                │
│                                                                          │
│  bridge_client.py ──▶ exam_pipeline.py ──▶ MinerU Flash Tier ONNX        │
│                       (PDF→JSON)         (版面识别 + OCR + 公式)          │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 🚀 两套部署方案

### 方案 A：分布式架构（推荐 ⭐）

> **适合**: 公网服务器 ≤ 4GB 内存、有本地闲置大机器 (≥ 8GB)、追求性价比

| 机器 | 最低配置 | 职责 |
|------|---------|------|
| 公网服务器 | 2GB RAM, 2核 CPU, Ubuntu 22.04 | Nginx + PHP-FPM + SQLite + bridge_server |
| 家里云/本地 | 8GB RAM, 4核 CPU, Ubuntu 22.04 | MinerU parse-server + bridge_client + exam_pipeline |

**公网服务器**：
```bash
# 1. 拉代码
cd /var/www
git clone https://github.com/YOUR_USER/StudyScape.git && cd StudyScape

# 2. 安装 PHP 依赖
sudo apt install php-fpm php-sqlite3 php-curl php-gd php-mbstring -y

# 3. 前端构建
cd vue-naive-admin && npm install && npm run build && cd ..

# 4. MinerU 不需要在公网装! bridge_server 只用 Python 标准库
python3 --version  # >= 3.8 即可

# 5. 生成共享密钥 (32字节 hex)
python3 -c "import secrets; print(secrets.token_hex(32))"
echo '把上面生成的密钥粘贴这里' > ocr_bridge/bridge_key.txt
chmod 600 ocr_bridge/bridge_key.txt

# 6. systemd 安装 bridge_server
sudo cp ocr_bridge/studyscape-bridge.service /etc/systemd/system/
sudo systemctl enable --now studyscape-bridge
sudo ufw allow 1200/tcp  # 防火墙放行 Worker 连接

# 7. Nginx 配置 → 把 public/ 作为 root 指向
```

**家里云/本地**：
```bash
# 1. 拉代码
cd ~
git clone https://github.com/YOUR_USER/StudyScape.git && cd StudyScape

# 2. 装 MinerU (Flash Tier — 纯 ONNX, 不需要 GPU)
pip3 install "mineru>=4.0"

# 3. 启动 MinerU parse-server (systemd)
sudo cp ocr_bridge/studyscape-bridge-client.service /etc/systemd/system/
# 注意: 家里云还需要一个 mineru-flash.service (手动写, 见下方)

# mineru-flash.service (家里云自己写):
# [Unit]
# Description=MinerU Flash Tier Parse Server
# [Service]
# User=$USER
# ExecStart=/usr/bin/python3 -m mineru.parser.api_server --tier flash --host 127.0.0.1 --port 16580 --allow-local-source
# [Install]
# WantedBy=multi-user.target

sudo systemctl daemon-reload
sudo systemctl enable --now mineru-flash studyscape-bridge-client

# 4. 写入共享密钥 (和公网服务器完全一致!)
#    推荐: scp 从公网服务器拷过来
scp user@公网IP:/path/to/StudyScape/ocr_bridge/bridge_key.txt ocr_bridge/
chmod 600 ocr_bridge/bridge_key.txt

# 5. 验证
sudo journalctl -u studyscape-bridge-client -f
# 应该看到: ✅ 认证通过, 等待任务 ...
```

### 方案 B：单机全栈

> **适合**: 公网服务器 ≥ 8GB RAM、不想维护两台机器、追求简单

一台机器干所有事——把家里云那套 MinerU + bridge_client 也装在公网服务器上：

```bash
# 1. 基础环境 (同方案 A 公网部分)
# 2. 额外装 MinerU
pip3 install "mineru>=4.0" onnxruntime

# 3. bridge_server + bridge_client 都装 (同一密钥文件)
sudo cp ocr_bridge/studyscape-bridge.service /etc/systemd/system/
sudo cp ocr_bridge/studyscape-bridge-client.service /etc/systemd/system/

# 4. mineru-flash.service (手动创建, 同上)
sudo systemctl daemon-reload
sudo systemctl enable --now mineru-flash studyscape-bridge studyscape-bridge-client
```

---

## 📁 目录结构

```
StudyScape/
├── public/                    # PHP 后端 + 构建好的前端
│   ├── api.php               # 所有 HTTP 接口 (统一入口)
│   ├── admin/                # Vue3 前端构建产物 (dist)
│   └── ...                   
├── vue-naive-admin/           # Vue3 + Naive UI 前端源码
│   ├── src/
│   │   └── views/admin/ocr/  # OCR 审核页
│   └── ...
├── ocr_bridge/               # 分布式 OCR 桥接层 (核心!)
│   ├── bridge_server.py      # 公网: TCP relay + localhost HTTP
│   ├── bridge_client.py      # 家里云: 主动连 + 自动重连 + 调 pipeline
│   ├── studyscape-bridge.service         # 公网 systemd
│   └── studyscape-bridge-client.service  # 家里云 systemd
├── exam_pipeline.py           # MinerU flash tier → Contract B JSON pipeline
├── security/                  # 安全监控脚本 (geo_lookup, log_monitor)
├── materials/                 # 上传资料存储
├── .gitignore                # bridge_key.txt 不入库!
└── README.md
```

---

## 🛠️ 技术栈

| 层 | 技术 |
|----|------|
| 前端 | Vue 3 + Naive UI + Vite |
| 后端 | PHP 8.1+ (单文件 api.php, SQLite) |
| OCR | MinerU 4.x Flash Tier — PP-DocLayoutV2 + PP-OCRv6 + PP-FormulaNet |
| 桥接 | asyncio (TCP) + HMAC-SHA256 认证 + 指数退避重连 |
| 存储 | SQLite 3 (单文件, 零依赖) |
| 部署 | systemd (自启守护) + Nginx (静态+PHP) |

---

## 🔐 安全说明

1. **bridge_key.txt 永远不入库** — `.gitignore` 排除，手动写入，权限 600
2. **Challenge-Response 认证** — 密钥永远不出现在网络上，只传 HMAC 签名
3. **无密钥连接 → HTTP 404 伪装** — 扫端口的人以为这是个普通 web 服务器
4. **bridge_server HTTP 只绑 localhost** — 127.0.0.1:1201 不上公网
5. **OCR 零云 API 依赖** — 数据不出局域网，彻底无泄露风险

---

## 🧪 快速测试

部署好后，在前端 OCR 审核页：

1. **Tab1 上传 PDF** → 走 bridge → 家里云 MinerU 处理 → 自动回填审核页
2. **Tab2 导入已有 JSON** → 跳过 OCR，秒入审核
3. 审核确认后点 **统一入库** → 进题库

---

## 📄 License

MIT — 自由使用、修改、分发。
