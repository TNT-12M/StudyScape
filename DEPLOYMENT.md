# StudyScape 部署说明

本文描述当前仓库的生产部署方式：Ubuntu 22.04、Nginx、PHP-FPM、SQLite、Python OCR Worker，以及同源部署在 `/admin/` 的 Vue 管理端。

## 1. 部署前提

建议使用独立 Linux 用户和 PHP-FPM 用户运行服务。下面示例使用：

```text
代码目录：/var/www/studyscape
Web 根目录：/var/www/studyscape/public
PHP-FPM 用户：www-data
域名：study.example.com
```

不要把 `exam.db`、`materials/`、`uploads/`、`data/` 或密码密钥提交到 Git。

## 2. 安装系统依赖

```bash
sudo apt update
sudo apt install -y nginx git curl unzip \
  php8.3-fpm php8.3-cli php8.3-sqlite3 php8.3-gd \
  php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip \
  php8.3-fileinfo php8.3-sodium \
  python3.13 python3.13-venv python3.13-dev \
  poppler-utils antiword
```

确认 PHP 扩展：

```bash
php -m | grep -E 'sqlite3|pdo_sqlite|gd|fileinfo|curl|mbstring|zip|xml|sodium'
```

## 3. 获取项目并准备目录

```bash
sudo mkdir -p /var/www/studyscape
sudo git clone <repository-url> /var/www/studyscape
cd /var/www/studyscape

sudo mkdir -p data/logs data/ocr_batches materials uploads
sudo chown -R www-data:www-data /var/www/studyscape
sudo chmod 750 data data/logs data/ocr_batches materials uploads
```

首次请求 API 时会自动创建 `exam.db` 和数据库表。正式切换前建议先准备数据库备份目录：

```bash
sudo mkdir -p /var/backups/studyscape
sudo chown www-data:www-data /var/backups/studyscape
sudo chmod 700 /var/backups/studyscape
```

## 4. 配置密码密钥

密码采用 PHP libsodium `secretbox` 可逆加密保存。默认密钥文件是：

```text
/etc/studyscape/password.key
```

创建目录并授权 PHP-FPM：

```bash
sudo install -d -o www-data -g www-data -m 700 /etc/studyscape
```

如果密钥文件不存在，`public/api.php` 首次执行时会自动生成 32 字节密钥，并设置为 `0600`。也可以提前生成：

```bash
sudo -u www-data python3 -c 'import secrets; open("/etc/studyscape/password.key", "wb").write(secrets.token_bytes(32))'
sudo chown www-data:www-data /etc/studyscape/password.key
sudo chmod 600 /etc/studyscape/password.key
```

数据库和密钥必须成对备份：

```bash
sudo cp exam.db /var/backups/studyscape/exam-$(date +%Y%m%d-%H%M%S).db
sudo cp /etc/studyscape/password.key /var/backups/studyscape/password-$(date +%Y%m%d-%H%M%S).key
sudo chmod 600 /var/backups/studyscape/*
```

密钥丢失后无法解密已有密码。不要把密钥写入 `.env`、Git、SQLite、日志或网页响应。

## 5. 安装 OCR Worker

```bash
cd /var/www/studyscape
sudo -u www-data python3.13 -m venv /opt/studyscape-venv
sudo -u www-data /opt/studyscape-venv/bin/pip install --upgrade pip
sudo -u www-data /opt/studyscape-venv/bin/pip install -r ocr/requirements.txt
```

`ocr/worker.py` 读取项目外的 `AccessKey .env`。生产环境不要把 AccessKey 写入仓库；建议由部署系统写入受限文件：

```bash
sudo install -o www-data -g www-data -m 600 /secure/studyscape/AccessKey.env \
  "/var/www/studyscape/AccessKey .env"
```

Worker 由 PHP 异步启动。PHP-FPM 配置中指定 Python 路径：

```ini
; /etc/php/8.3/fpm/pool.d/www.conf
env[PYTHON_BIN] = /opt/studyscape-venv/bin/python
env[PATH] = /usr/local/bin:/usr/bin:/bin
```

修改后重启：

```bash
sudo systemctl restart php8.3-fpm
```

## 6. 构建 Vue 管理端

Vue 管理端源码位于 `vue-naive-admin/`，生产环境已配置为：

```text
VITE_USE_HASH=true
VITE_PUBLIC_PATH=/admin/
VITE_PHP_API_URL=/api.php
```

构建命令：

```bash
cd /var/www/studyscape/vue-naive-admin
pnpm install --frozen-lockfile
pnpm build
```

同步到 PHP 文档根：

```bash
cd /var/www/studyscape
sudo rm -rf public/admin
sudo mkdir -p public/admin
sudo cp -R vue-naive-admin/dist/. public/admin/
sudo chown -R www-data:www-data public/admin
```

构建产物的 `index.html` 应引用 `/admin/assets/...`。如果引用的资源不存在，说明 `dist` 与 `public/admin` 不是同一次构建结果，应重新完整同步。

## 7. Nginx 配置

创建 `/etc/nginx/sites-available/studyscape`：

```nginx
server {
    listen 80;
    server_name study.example.com;
    root /var/www/studyscape/public;
    index index.html;

    client_max_body_size 128m;

    # 数据库、运行日志和密钥不能通过 Web 访问
    location ~* \.(db|sqlite|sqlite3|log|key)$ {
        deny all;
    }
    location ^~ /data/ { deny all; }
    location ^~ /materials/ { deny all; }
    location ^~ /uploads/ { deny all; }

    # Vue 管理端。生产构建使用 Hash 路由，目录入口直接返回 index.html
    location = /admin { try_files /admin/index.html =404; }
    location ^~ /admin/ {
        try_files $uri $uri/ /admin/index.html;
    }

    # PHP API
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 900s;
    }

    # 静态资源缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff2?|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
}
```

启用并检查：

```bash
sudo ln -s /etc/nginx/sites-available/studyscape /etc/nginx/sites-enabled/studyscape
sudo nginx -t
sudo systemctl reload nginx
```

正式环境建议通过 Certbot 开启 HTTPS：

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d study.example.com
```

## 8. 首次初始化

打开：

```text
https://study.example.com/auth.html
```

系统首次访问 API 时会初始化 `lian` root：

```text
用户名：lian
邮箱：44175149@qq.com
初始密码：lian120208
```

登录后建议立即确认：

- `/admin/` 可以正常打开；
- 用户管理不返回密码字段；
- 题库和资料菜单对 content_admin 可见；
- 考试管理、反馈、用户授权只对 root 可见；
- `lian` 不能被删除、禁用或撤销；
- root 授权后 `lian` 能收到授权动态；
- root 保存、发布试卷后普通用户能看到试卷。

## 9. 发布和内容管理流程

### 题库 JSON 导入

1. 使用 root 或 content_admin 登录 `/admin/`。
2. 打开“题库管理”。
3. 上传标准 JSON 或 PaperCutter-VL JSON。
4. 确认学段、科目和题型。
5. 执行导入，系统会按题干、科目和学段做查重。

### OCR 导入

1. 打开“智能 OCR 审核”。
2. 上传 PDF 或图片。
3. 等待 OCR 批次变为“待审核”。
4. 编辑题干、选项、答案、解析，勾选需要保留的题目。
5. 点击“统一入库”。

### 组卷发布

1. 只有 root 可以打开组卷管理。
2. 从题库选择题目并保存试卷。
3. 确认返回试卷 ID 和题目数量。
4. 点击发布。
5. 发布后学生端的在线考试列表会显示试卷。
6. 已有作答记录的试卷不能修改题目、下架或删除，以保护历史结果。

## 10. 备份和升级

升级前至少备份：

```bash
sudo cp /var/www/studyscape/exam.db /var/backups/studyscape/exam-before-upgrade.db
sudo cp /etc/studyscape/password.key /var/backups/studyscape/password-before-upgrade.key
sudo tar czf /var/backups/studyscape/materials-before-upgrade.tar.gz \
  -C /var/www/studyscape materials
```

升级代码和前端后：

```bash
cd /var/www/studyscape
sudo git pull --ff-only
cd vue-naive-admin && pnpm install --frozen-lockfile && pnpm build
cd ..
sudo rm -rf public/admin
sudo mkdir -p public/admin
sudo cp -R vue-naive-admin/dist/. public/admin/
sudo chown -R www-data:www-data public/admin
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx
```

升级后检查 `php -l public/api.php`、`nginx -t`、登录、`/admin/` 和一次隔离的组卷发布流程。

## 11. 故障排查

| 现象 | 检查项 |
|---|---|
| API 返回密码密钥初始化失败 | `/etc/studyscape` 权限、`password.key` 长度、PHP-FPM 用户读权限、sodium 扩展 |
| `/admin/` 白屏 | `public/admin/index.html` 引用的每个 `/admin/assets/...` 是否存在；重新同步同一次 `dist` |
| content_admin 看不到题库 | 检查用户 `role` 是否为 `content_admin`，并重新登录刷新 Session |
| 无法发布试卷 | 确认当前账号是 root、试卷保存成功且至少关联一道题 |
| OCR 一直失败 | 检查 `data/ocr_batches/`、Python Worker 日志、`PYTHON_BIN`、阿里云凭据和文件权限 |
| 下载资料返回 403 | 先调用 `material_get_token`，再使用同一 Session 的一次性下载 URL |
| 密码全部无法登录 | 检查数据库和 `/etc/studyscape/password.key` 是否来自同一份备份 |
