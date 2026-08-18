# 智学在线 · 初高中在线刷题与考试平台（PHP版）

零依赖（PHP 7.4+ 自带 PDO-SQLite 即可），单入口 MVC，开箱即用。

## ✨ 核心功能

| 模块 | 说明 |
| --- | --- |
| 🔐 认证安全 | 密码 Bcrypt 哈希、Session httponly、CSRF token、XSS 输出编码、首次/重置强制改密 |
| 👥 用户体系 | 管理员/学生双角色，**学生注册必须管理员审核通过**方可登录 |
| 📚 题库管理 | 支持单选/多选/判断/填空 4 类题，支持批量 JSON 导入、筛选、增删改 |
| 📝 考试管理 | 自建考试→加题→设密码→发布；可打乱题序/选项、可设重考次数、及格分 |
| 🏁 在线考试 | 学生需输入考试密码 → 答题页带答题卡、上/下/提 → 自动评卷、逐题解析 |
| 📊 考试记录 | 管理员可查看全部考生记录、逐题对错；学生只看自己的 |
| 📋 审计日志 | **每次页面/接口请求都留痕**（用户/动作/方法/路径/IP/UA），支持筛选翻页 |
| 🎯 刷题练习 | 按科目/年级随机抽题，即时判对错，个人历史记录 30 条 |
| 👑 管理员概览 Dashboard | 4 大统计卡片 + 最近 10 条考试记录 |

## ⚡ 快速上手

### 1. 环境要求
- PHP ≥ **7.4**（推荐 8.0+）
- 必须启用：**pdo_sqlite**（绝大多数环境默认自带）
- 可选：Apache（`.htaccess` 现成）或 Nginx（`nginx.example.conf` 现成）

### 2. 上传到服务器
把整个 `exam-site-php` 目录上传到站点根目录即可。确保：
```
chmod -R 755 exam-site-php
chmod -R 777 exam-site-php/data     # 读写数据库
chmod -R 777 exam-site-php/uploads  # 可选：批量上传临时目录
```

### 3. 通过浏览器访问 `index.php`
首次访问会 **自动**：
- 在 `data/exam.db` 创建 SQLite 数据库
- 初始化管理员账号 👇

### 4. 默认管理员账号
| 用户名 | 默认密码 |
| --- | --- |
| `admin` | `Admin@123456` |

⚠️ **首次登录会强制要求修改密码。**

## 🔧 生产环境（推荐）

### 通过环境变量覆盖敏感配置
```bash
export APP_SECRET="一串很长的随机字符串"
export ADMIN_USER="your-admin"
export ADMIN_PASS="你自己的强密码"
```

部署时**优先**使用这些环境变量，避免修改 bootstrap.php 源码。

### Nginx
参考仓库里的 `nginx.example.conf`，把 `root` 指向本项目目录 + `php-fpm` sock。

### Apache
自带 `.htaccess`，开启 `mod_rewrite` 后即可使用。

### 批量导入示例题库
管理员登录 → 【题库管理】→ 右上角 `📥 批量导入JSON`，选择 `sample_questions.json`（9 科 15 道示例题）导入。

## 🎯 典型流程

### 管理员
1. 用 admin 账号登录 → 强制改密 → 进入 Dashboard
2. 【题库管理】批量导入 JSON 或手动新增
3. 【考试管理】创建考试 → 设置密码/时长/重考/打乱 → 【题目】从题库加题 → 【发布】
4. 把考试密码发给学生

### 学生
1. 先注册 → 等管理员通过审核（否则登录会被拦截提示）
2. 审核通过后登录 → 首页看到已发布考试
3. 输入考试密码 → 答题 → 提交 → 查看分数+逐题解析

## 🧩 技术结构
- **单入口 `index.php`**：action=xxx 做路由（不依赖 PATH_INFO，所有 PHP 部署环境都能跑）
- **`bootstrap.php`**：配置 + DB 初始化 + CSRF/Session/审计工具函数
- **`views/`**：`layout` / `auth` / `admin` / `student` / `errors` 分层模板
- **`sample_questions.json`**：示例 15 道题覆盖 9 学科 3 题型
- **SQLite 单文件**：无需 MySQL/Redis，拷项目即走，迁移无忧

## 📁 目录结构
```
exam-site-php/
├── index.php            # 单入口（所有路由）
├── bootstrap.php        # 配置 & DB & 工具函数
├── sample_questions.json
├── nginx.example.conf
├── .htaccess
├── data/                # SQLite 库（自动生成）
├── uploads/
└── views/
    ├── layout/          # header + footer（导航+闪屏+样式）
    ├── auth/            # login / register
    ├── admin/           # 8 个管理端视图
    ├── student/         # 8 个学生端视图
    ├── profile.php
    └── errors/404.php
```

## 🔒 安全清单
✅ 密码 Bcrypt  
✅ Session httponly + SameSite=Lax  
✅ 全站 CSRF token  
✅ 全局 HTML 转义 `e()` 函数  
✅ 所有 SQL 查询均使用 Prepared Statement  
✅ 上传 JSON 只解析文件内容，无执行风险  
✅ 学生注册必须人工审核  
✅ 管理员/重置后强制改密  
✅ 考试密码 Bcrypt 哈希  
✅ HTTP 安全头示例（XCTO / XFO / Referrer）  
✅ 所有访问写入审计日志

---
📚 智学在线 © 初高中刷题与在线考试 PHP 版
