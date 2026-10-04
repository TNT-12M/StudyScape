# 学补在线刷题平台

面向初高中学生的在线刷题、组卷考试、资料管理和智能题目导入平台。

当前项目采用轻量单体架构：PHP 提供 API 和 Session，SQLite 保存业务数据，Vue 3 管理端负责正式的用户和管理界面，Python Worker 负责 PDF/图片 OCR。

## 功能

- 用户注册、登录、验证码和 Session 会话。
- 初始账号 `lian` 自动初始化为不可删除的 root。
- root、内容管理员、普通用户三级权限。
- 题库浏览、题目编辑、批量 JSON 导入和 PaperCutter-VL 导入。
- PDF/图片 OCR 批次处理、审核、编辑、剔除和入库。
- root 组卷、发布、下架和管理考试。
- 学生在线考试、自由刷题、提交、自评和结果查看。
- 资料上传、编辑、下载和删除。
- 用户反馈每日限制、管理员快捷回复、忽略和重新处理。
- root 授权操作通知。

## 技术栈和架构

```text
浏览器
  ├── Vue 3 + Vite + Naive UI 管理端（/admin）
  └── 原生用户页面（public/*.html，逐步迁移到 Vue）
          │ Fetch / Axios + PHP Session + CSRF
          ▼
      public/api.php
          │
          ├── SQLite exam.db
          ├── materials/、uploads/ 文件存储
          ├── data/ OCR 批次和运行数据
          └── ocr/worker.py
                  ├── 阿里云教育试卷结构化 OCR
                  ├── PyMuPDF
                  └── Pillow
```

主要技术：

| 层次 | 技术 |
|---|---|
| 后端 | PHP 8.2+、SQLite3 |
| 会话和安全 | PHP Session、CSRF、登录限流、HTTP 安全头 |
| 密码存储 | PHP libsodium `secretbox`，密钥位于项目外 |
| 管理端 | Vue 3、Vite、Pinia、Vue Router、Naive UI、Axios |
| OCR | Python、阿里云 OCR SDK、PyMuPDF、Pillow |
| Web 服务 | 本地 PHP 内置服务器，生产 Nginx + PHP-FPM |

## 目录结构

```text
.
├── public/
│   ├── api.php                 # PHP API 唯一入口
│   ├── admin/                  # Vue 管理端生产构建结果
│   ├── admin.html              # 旧入口，仅跳转 /admin
│   ├── index.html              # 原生首页
│   ├── auth.html               # 原生登录/注册页
│   ├── questions.html          # 原生题库页
│   ├── exam.html               # 原生考试页
│   ├── practice.html           # 原生刷题页
│   ├── materials.html          # 原生资料页
│   └── assets/app.css
├── vue-naive-admin/            # Vue 管理端源码，保留原项目 LICENSE 和作者信息
├── ocr/
│   ├── worker.py               # OCR Worker
│   └── requirements.txt
├── materials/                  # 永久资料存储
├── uploads/                    # 临时上传目录
├── data/                       # OCR 批次和运行数据，不应提交到 Git
├── exam.db                     # SQLite 数据库，首次访问时自动创建
├── php.ini                     # Windows 本地 PHP 配置
├── start_server.bat            # Windows 启动脚本
├── DEPLOYMENT.md               # 独立部署说明
├── OCR流水线对接文档.md
├── 题目答案自动匹配功能说明.md
└── .gitignore
```

`exam.db`、上传资料、OCR 结果、日志和外部密码密钥属于运行数据，不应提交到代码仓库。

## 本地开发

### 环境要求

- PHP 8.2+，扩展：`sqlite3`、`pdo_sqlite`、`gd`、`fileinfo`、`curl`、`mbstring`、`zip`、`xml`、`sodium`。
- Python 3.10+，用于 OCR Worker。
- 可选：Poppler、antiword 等文档转换工具。
- Vue 管理端构建需要 Node.js 和 pnpm，或使用项目已有的 Bun 运行环境。

### 启动 PHP 服务

Windows 可以编辑 `start_server.bat` 中的 `PHP_BIN` 后双击运行。也可以手动执行：

```bash
php -c php.ini -S 127.0.0.1:8080 -t public/
```

访问地址：

```text
首页：http://127.0.0.1:8080/index.html
登录：http://127.0.0.1:8080/auth.html
管理端：http://127.0.0.1:8080/admin/
API：http://127.0.0.1:8080/api.php?action=public_overview
```

### 安装 OCR 依赖

```bash
python -m pip install -r ocr/requirements.txt
```

OCR 需要阿里云 AccessKey。Worker 从项目外的 `AccessKey .env` 读取凭据；生产环境应通过服务器安全配置提供，禁止把密钥写入源码或提交 Git。

### 构建 Vue 管理端

```bash
cd vue-naive-admin
pnpm install
pnpm build
```

生产环境变量已经配置为：

```text
VITE_USE_HASH=true
VITE_PUBLIC_PATH=/admin/
VITE_PHP_API_URL=/api.php
```

构建后将 `vue-naive-admin/dist/` 内容同步到 `public/admin/`：

```bash
rm -rf ../public/admin
mkdir -p ../public/admin
cp -R dist/. ../public/admin/
```

## 权限模型

| 角色 | 权限 |
|---|---|
| `root` | 全部平台权限，包括用户授权、反馈处理、题库、OCR、资料和考试发布 |
| `content_admin` | 题库查看/增删改、JSON 导入、OCR 审核入库、资料上传/编辑/删除/下载 |
| `user` | 刷题、考试、资料下载、提交反馈和查看自己的反馈回复 |

特殊规则：

- 用户名 `lian` 是初始 root，不能删除、禁用或降权。
- 只有初始 `lian` 可以新增或撤销其他 root。
- 所有 root 可以授权或撤销 `content_admin`。
- content_admin 无法组卷、发布/下架考试、管理用户或处理反馈。
- 所有权限在 PHP 后端实时校验，前端菜单隐藏不构成安全边界。

首次初始化会创建：

```text
用户名：lian
邮箱：44175149@qq.com
初始密码：lian120208
```

密码不会明文保存。部署后应按运维流程管理初始密码；当前系统不提供网页端密码读取接口。

## 密码密钥

默认密钥路径：

```text
/etc/studyscape/password.key
```

也可以通过环境变量覆盖：

```text
PASSWORD_KEY_FILE=/path/to/password.key
```

密钥由 PHP 首次运行时自动生成，使用 32 字节随机值和 libsodium `secretbox`。密钥必须与 `exam.db` 成对备份；密钥丢失后无法解密已有密码。密钥不能写入项目、数据库、日志或 HTTP 响应。

## 主要 API

所有 API 使用 `public/api.php?action=<action>`。

公开接口：

- `public_overview`、`captcha`、`register`、`login`、`logout`、`check_session`。

用户接口：

- `get_dashboard`
- `paper_list`、`paper_get`
- `attempt_start_exam`、`attempt_get`、`attempt_submit`、`attempt_result`、`attempt_self_grade`、`attempt_list_mine`
- `practice_subjects`、`practice_start`、`practice_submit`、`practice_result`、`practice_self_grade`
- `material_list`、`material_get_token`、`material_download`
- `feedback_submit`、`feedback_list_mine`
- `notification_list`、`notification_unread_count`、`notification_mark_read`

内容管理接口：

- `question_stats`、`get_subjects`、`list_questions`、`get_question`
- `add_question`、`update_question`、`delete_question`
- `bulk_move_questions`、`bulk_update_question_category`、`bulk_delete_questions`
- `import_questions_json`
- `ocr_batch_create`、`ocr_batch_list`、`ocr_batch_get`、`ocr_batch_retry`、`ocr_batch_delete`、`ocr_batch_commit`
- `material_upload`、`material_update`、`material_delete`

root 接口：

- `get_admin_panel`
- `create_root`、`grant_root`、`revoke_root`
- `grant_content_admin`、`revoke_content_admin`
- `admin_feedback_list`、`admin_feedback_update`
- `admin_access_stats`
- `paper_save`、`paper_publish`、`paper_unpublish`、`paper_delete`

以下危险接口已经移除，历史请求统一返回“功能已移除”：

- `admin_danger_challenge`
- `clear_users`
- `reset_db`

## 数据和备份

运行时目录需要由 PHP-FPM 用户读写：

```text
exam.db
materials/
uploads/
data/
/etc/studyscape/password.key
```

备份时必须同时备份 `exam.db` 和 `password.key`。不要把 `data/`、上传资料或生产密钥加入 Git。

## 相关文档

- [DEPLOYMENT.md](DEPLOYMENT.md)：Ubuntu/Nginx/PHP-FPM/Vue/OCR 完整部署步骤。
- [OCR流水线对接文档.md](OCR流水线对接文档.md)：OCR Worker 与 PaperCutter-VL JSON 对接。
- [题目答案自动匹配功能说明.md](题目答案自动匹配功能说明.md)：题目和答案导入匹配规则。

## 开源许可

Vue 管理端保留上游项目的 `LICENSE` 文件、作者署名和 MIT 协议。项目自身代码和文档按仓库现有授权约定使用。
