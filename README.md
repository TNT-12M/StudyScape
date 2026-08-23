# 学补在线刷题平台

> 面向初高中学生的公益在线刷题与考试系统
> 技术栈：PHP + SQLite + RapidOCR + 原生前端

---

## 📋 功能概览

| 模块 | 说明 |
|------|------|
| 👤 注册登录 | 独立认证页，注册后可直接登录；首位注册用户自动成为管理员 |
| 📘 组卷考试 | 管理员从题库组卷，学生在线答题、自动评分 |
| 🎯 自由刷题 | 按科目/知识点分类刷题，即时反馈 |
| 📁 资料中心 | 上传/下载试卷资料，支持 DOC/DOCX/PDF 智能导入 |
| 🔍 智能导入 | 扫描件 PDF 自动 OCR 识别题目（默认 onnxruntime+v5-MOBILE） |
| 🛡️ 管理后台 | 用户管理、题库管理、考试管理、数据统计 |

---

## 📁 项目结构

```
StudyScape/
├── public/                          # Web 根目录（Nginx 文档根）
│   ├── index.html                   # 首页 + 已登录控制台
│   ├── auth.html                    # 登录/注册页
│   ├── admin.html                   # 管理后台
│   ├── questions.html               # 题库组卷
│   ├── exam.html                    # 组卷考试
│   ├── practice.html                # 自由刷题
│   ├── materials.html               # 资料中心
│   ├── api.php                      # 所有 API 接口
│   └── assets/
│       └── app.css                  # 全局样式
├── extract.py                       # 文档解析 + OCR（被 api.php 调用）
├── import_questions.php            # 命令行 JSON 题库导入
├── data/
│   ├── import_exam.json            # 导入题目格式示例
│   └── logs/                        # 运行日志
├── test/
│   └── 2025成都中考生物真题及答案解析.pdf  # OCR 测试用 PDF
├── legacy/
│   └── Email_Verification.php       # 旧邮件验证代码（保留）
├── materials/                       # 资料永久存储（.htaccess 禁直接访问）
├── uploads/                         # 临时上传目录（解析后清理）
├── exam.db                          # SQLite 数据库（自动生成）
├── start_server.bat                 # Windows 一键启动
├── php.ini                          # 本地开发 PHP 配置
└── .gitignore
```

---

## 🚀 本地开发（Windows）

### 环境需求

- PHP 8.0+（需扩展：sqlite3、gd、fileinfo、curl、mbstring、zip、xml）
- Python 3.10+（OCR 功能）
- 可选：antiword（DOC 解析）、poppler-utils（pdftotext）

### 快速启动

```bash
# 1. 双击 start_server.bat
# 或手动执行：
php -S 127.0.0.1:8080 -t public/

# 2. 访问
# 首页: http://127.0.0.1:8080/index.html
# API:  http://127.0.0.1:8080/api.php?action=public_overview
```

### Python 依赖（OCR 必需）

```bash
pip install rapidocr onnxruntime pymupdf python-docx pillow
```

### 首次使用

1. 打开首页，点击「注册」
2. 首位注册用户自动成为管理员
3. 管理员进入后台 → 资料中心 → 上传 PDF → 智能导入 → 题目自动入库

---

## 🌐 线上部署（阿里云 Ubuntu 22.04）

### 1. 安装依赖

```bash
# Nginx + PHP 8.3
sudo apt update && sudo apt -y upgrade
sudo apt -y install nginx-light php8.3-fpm php8.3-cli \
    php8.3-sqlite3 php8.3-gd php8.3-fileinfo php8.3-curl php8.3-mbstring \
    php8.3-opcache php8.3-zip php8.3-xml \
    antiword poppler-utils software-properties-common

# Python 3.13
sudo add-apt-repository -y ppa:deadsnakes/ppa
sudo apt -y install python3.13 python3.13-venv python3.13-dev
sudo python3.13 -m venv /opt/xb-pyvenv
sudo chown -R www-data:www-data /opt/xb-pyvenv
sudo /opt/xb-pyvenv/bin/pip install -i https://pypi.tuna.tsinghua.edu.cn/simple \
    'rapidocr>=3.9.2' 'onnxruntime>=1.20' 'pymupdf>=1.24' 'python-docx>=1.1' 'pillow>=10.0'
```

### 2. 部署代码

```bash
sudo mkdir -p /var/www/xb && cd /var/www/xb
sudo git clone <repo-url> .
sudo chown -R www-data:www-data /var/www/xb
sudo chmod 750 /var/www/xb/{data,uploads,materials}
```

### 3. PHP-FPM 配置

```ini
; /etc/php/8.3/fpm/pool.d/www.conf
env[PYTHON_BIN] = /opt/xb-pyvenv/bin/python
env[PATH] = /usr/local/bin:/usr/bin:/bin
```

### 4. Nginx 配置

```nginx
server {
    listen 80;
    server_name xb.yourdomain.com;
    root /var/www/xb/public;
    index index.html;

    # 禁止直接访问数据库和上传目录
    location ~* \.(db|sqlite|log)$ { deny all; }
    location ^~ /materials/ { deny all; }
    location ^~ /uploads/  { deny all; }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 900s;
        client_max_body_size 128m;
    }

    # 静态资源缓存
    location ~* \.(css|js|png|jpg|svg|ico|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### 5. HTTPS（推荐）

```bash
sudo apt -y install certbot python3-certbot-nginx
sudo certbot --nginx -d xb.yourdomain.com
```

### 6. 目录权限

```bash
cd /var/www/xb
sudo chown -R www-data:www-data data uploads materials exam.db
sudo chmod 750 data uploads materials
sudo chmod 660 exam.db
```

---

## ⚙️ OCR 配置

### 默认方案：onnxruntime + PP-OCRv5 MOBILE

经实测在 2核2G 服务器上表现最优：

| 指标 | 数值 |
|------|------|
| 初始化 | ~1.1s |
| 单页 OCR | ~8s |
| 9 页 PDF | ~1.5 min |
| 内存 Δ | ~38MB |
| 中文有效率 | 100% |

配置位于 `extract.py` 顶部：

```python
OCR_CONFIG = {
    "engine": "onnxruntime",       # 引擎
    "det_model": "mobile",         # 检测模型
    "rec_model": "mobile",         # 识别模型
    "ocr_version": "v5",           # 版本
    "dpi_rapidocr": 120,           # 分辨率
    "timeout_per_page": 180,       # 单页超时
}
```

### 备选：MNN 引擎（Linux）

若 onnxruntime 兼容性有问题可切换：

```bash
pip install MNN
# 修改 extract.py: OCR_CONFIG["engine"] = "mnn"
```

### 降级策略

每页 OCR 流程：RapidOCR → Tesseract CLI 兜底（内存 ~50MB，精度较低但稳定）

---

## 🔌 API 接口

所有接口通过 `api.php?action=<name>` 调用，返回 JSON。

### 公共接口

| 接口 | 说明 |
|------|------|
| `public_overview` | 首页概览（统计数据） |
| `register` | 用户注册 |
| `login` | 用户登录 |
| `logout` | 登出 |
| `captcha` | 获取验证码 |

### 学生接口（需登录）

| 接口 | 说明 |
|------|------|
| `exam_list` | 获取可用考试列表 |
| `start_exam` | 开始考试 |
| `submit_answer` | 提交答题 |
| `practice_list` | 自由刷题题目 |
| `practice_submit` | 刷题提交 |

### 管理接口（需管理员）

| 接口 | 说明 |
|------|------|
| `admin_panel` | 管理面板数据 |
| `toggle_user` | 启用或禁用用户 |
| `admin_danger_challenge` | 生成危险操作一次性授权码 |
| `clear_users` | 输入授权码后永久删除所有用户 |
| `reset_db` | 输入授权码后备份并清空业务数据 |
| `material_upload` | 上传资料 |
| `extract_document` | 智能导入（OCR + 入库） |
| `import_questions` | JSON 批量导入题目 |

### 文件上传

- 支持格式：PDF / DOC / DOCX / TXT
- 单文件上限：60MB（配置在 `api.php`）
- 权限目录：`uploads/`（临时）、`materials/`（永久）

---

## 🏗️ 技术架构

```
Nginx (HTTPS + 静态缓存)
  │
  ├── PHP-FPM 8.3 (api.php)
  │     ├── SQLite3 数据层
  │     ├── Session + CSRF
  │     └── shell_exec → Python extract.py
  │           ├── RapidOCR (onnxruntime + v5-MOBILE)
  │           ├── PyMuPDF (PDF 渲染)
  │           ├── antiword / catdoc (DOC 解析)
  │           └── Tesseract CLI (兜底 OCR)
  │
  └── 原生 HTML/CSS/JS (无框架依赖)
```

### 关键设计

- **子进程逐页 OCR**：每页独立 Python 进程，防止单页 OOM 影响全局
- **CSRF 防护**：所有 POST 接口需携带 token
- **权限分离**：学生/管理员接口严格区分
- **首用户自动升权**：首次注册用户自动成为管理员
- **统一管理员入口**：管理员登录后进入 `public/admin.html`，首页仅保留普通用户控制台
- **危险操作保护**：删除所有用户或重置数据库前，必须输入服务端生成的一次性英文授权码，并完成最终确认；授权码 5 分钟有效且只能使用一次
- **危险操作备份**：重置数据库前自动备份 SQLite 数据库，成功后当前管理员会话失效并要求重新登录

---

## 💾 数据备份

```bash
# SQLite 备份（用 .backup 命令）
sqlite3 /var/www/xb/exam.db ".backup /backup/exam_$(date +%Y%m%d).db"

# 全量备份
tar czf /backup/xb_$(date +%Y%m%d).tar.gz /var/www/xb/ \
    --exclude=/var/www/xb/uploads/*

# 保留 30 天
find /backup -name "*.tar.gz" -mtime +30 -delete
```

---

## 🔧 常见问题

| 问题 | 排查 |
|------|------|
| OCR 报 OOM | 降低 `dpi_rapidocr` 或切换到 Tesseract 兜底 |
| PHP 调不到 Python | 检查 `www.conf` 中 `env[PYTHON_BIN]` 并重启 FPM |
| 中文乱码 | 确认 antiword 版本或改用 `antiword -m UTF-8` |
| 文件上传失败 | 检查 `uploads/` 和 `materials/` 权限（750 www-data） |
| SQLite 只读 | 确认 `exam.db` 所有者为 www-data 且 660 权限 |

---

## 📄 License

公益项目，仅供学习交流使用。
