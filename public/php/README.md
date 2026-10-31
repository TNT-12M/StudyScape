# StudyScape API 模块化架构

`public/api.php` 薄入口 + `public/php/core/` + `public/php/modules/`。
原始 6616 行单体已按职责域拆分为 17 个独立文件，每个文件 `php -l` 可单独解析。

## 目录结构

```
public/
├── api.php                      ← 薄入口（~40 行）：display_errors=0 + require 顺序
├── php/
│   ├── core/                    ← 核心基础设施（按加载序，数字前缀即顺序）
│   │   ├── 00_bootstrap.php        错误报告 + PSR-4 autoloader (Gregwar/Captcha, MIT)
│   │   ├── 01_security_rates.php    4 类漏桶速率检测器（标准/突发/令牌桶/滑动窗口）
│   │   ├── 02_session.php           session 安全加固（HttpOnly/Secure/SameSite）
│   │   ├── 03_security_scan.php     安全扫描：密码泄漏/钓鱼/注入/速率阈值 + 伪 cron 调度
│   │   ├── 04_config_and_auth.php   密码加密 (libsodium) + SMTP + 邮箱验证码
│   │   ├── 05_db_schema.php         SQLite 初始化 + 10 张 CREATE TABLE
│   │   ├── 06_db_utils.php          dbQuery/ensureTable + MinerU 辅助函数
│   │   ├── 07_captcha.php           Gregwar CaptchaBuilder + CSRF token 生成
│   │   ├── 08_site_and_ocr.php      站点统计 + OCR Bridge (TCP 120.79.161.207:1200)
│   │   ├── 09_inline_actions.php    record_visit + captcha 内联 action（完整 try/catch）
│   │   ├── 10_json_and_sanitize.php jsonOut + sanitizeHtmlContent + 各类 validate*
│   │   ├── 11_auth.php              isLoggedIn / requireAdmin / requireTeacher / requireStudent
│   │   ├── 12_user_and_db.php       用户辅助 + 登录速率限制 + dbQuery 封装
│   │   └── 13_csrf.php              $csrfBypass 白名单 + CSRF 校验
│   └── modules/                 ← 业务 action 路由
│       ├── 20_action_router.php     完整 if($action) { try { switch { ... } catch {} exit; }
│       ├── 00_fallback.php          独立 fallback：未匹配 action → jsonOut(false) + exit
│       └── 21_import_helpers.php    importExtractQuestions + pcvl* + examB* 导入流水线
└── vendor/Gregwar/Captcha/      ← 第三方库（MIT，手动 vendor 引入，避开 composer）
```

## 架构原则

1. **依赖序严格**：core/ 数字前缀即加载顺序，薄入口按序 `require_once`，循环依赖不允许
2. **每个文件独立可解析**：`php -l` 必须零错误，`try/catch`、`if`、`switch` 块不得跨文件
3. **display_errors = 0**：生产环境入口和 bootstrap 均设为 0，错误详情不暴露给访客
4. **$action 路由集中**：所有 `switch case` 在 `modules/20_action_router.php` 内闭合，fallback 独立
5. **Session 在 02_session 启动**，DB 在 05_db_schema 初始化，之后所有 `global $db` 直取
6. **CSRF 白名单** `00_bypass` 在 13_csrf.php 定义，captcha/login 等 POST 走白名单不校验
7. **Nginx 安全层**：生产 Nginx 需对 `/php/` 下所有请求返回 404（片段不可直访）

## 验证清单（提交前必须跑）

```bash
# 1. 全量 PHP 语法检查
for f in public/api.php public/php/core/*.php public/php/modules/*.php; do php -l "$f" || exit 1; done

# 2. 确认无 display_errors=1
grep -rn "display_errors.*1" public/ || echo "OK: no display_errors=1"

# 3. 确认所有文件都以 <?php 开头
for f in public/api.php public/php/core/*.php public/php/modules/*.php; do head -1 "$f" | grep -q '<?php' || { echo "MISSING <?php: $f"; exit 1; }; done
```

## 数据库约定

- 位置：`__DIR__ . '/../../../exam.db'`（SQLite3，core/ 下模块用 3 层 ../ 达项目根；modules/ 同理）
- 打开时机：`05_db_schema.php` 首次 `new SQLite3()` 并执行 CREATE TABLE IF NOT EXISTS
- 全局变量：`global $db` 在所有函数内直取，无依赖注入
- 事务：写操作用 `BEGIN IMMEDIATE`，rollback 包 `try/catch` 嵌套防异常吞掉

## 安全层

| 层 | 位置 | 作用 |
|---|---|---|
| 速率检测 | 01_security_rates + 12_user_and_db | 登录/验证码/OCR 导入/站点访问各独立桶 |
| 安全扫描 | 03_security_scan | 每次请求触发伪 cron（60s 间隔） |
| CSRF | 07_captcha + 13_csrf | token 注入所有 JSON 响应，白名单豁免 |
| Session 加固 | 02_session | 全 HTTPS + HttpOnly + SameSite=Lax |
| 密码加密 | 04_config_and_auth | libsodium crypto_secretbox，密钥外置 |
| 输入过滤 | 10_json_and_sanitize | sanitizeHtmlContent 白标签 + on* 属性剥离 |
| Nginx 封禁 | 生产 nginx.conf | `location ^~ /php/ { return 404; }` |
