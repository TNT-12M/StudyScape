# StudyScape 后端 PHP 核心

本目录是 StudyScape 项目的**核心后端逻辑**，全部 PHP API 的基础设施、安全、DB、OCR、业务路由等模块都在这里。

## 目录结构

```
php/
├── README.md                         ← 本文件
├── core/                             ← 核心基础设施（被 api.php 按顺序 require）
│   ├── 00_bootstrap_header.php       ← PSR-4 autoloader + error_reporting 配置
│   ├── 01_security_rates.php         ← 4 个安全漏桶类（LeakyBucket / UniqBucket / CounterBucket / RatioBucket）
│   ├── 02_session.php                ← Session 安全加固（httponly / samesite / secure）
│   ├── 03_security_scan.php          ← PHP 安全扫描函数群（triggerSecurityScan / phpSecurityScan / _insert*）
│   ├── 04_config_and_auth.php        ← 密码 key / SMTP 配置 / 加密解密 / 邮箱验证码函数群（30+ 函数）
│   ├── 05_db_schema.php              ← SQLite 连接 + CREATE TABLE schema + 升级迁移
│   ├── 05_db_utils.php               ← DB 工具函数 + MinerU 辅助
│   ├── 06_captcha.php                ← Gregwar/Captcha 集成（createCaptcha）
│   ├── 07_site_and_ocr.php           ← 站点统计 + OCR bridge（ocrBridgeCall / ocrRefreshBatch / startOcrWorker）
│   ├── 08_inline_actions.php          ← record_visit + captcha 两个 inline action 处理
│   ├── 09_json_and_sanitize.php      ← jsonOut / sanitizeInput / sanitizeHtmlContent
│   ├── 10_auth.php                   ← 权限检查函数群（isLoggedIn / requireAdmin / requireRoot / requireContentPermission）
│   ├── 11_user_and_db.php            ← 用户资料 + 登录限流 + dbQuery / dbFetchAll / dbFetchOne
│   └── 12_csrf.php                   ← $csrfBypass 白名单 + CSRF token 校验
└── modules/                          ← 业务模块
    ├── 00_fallback.php               ← 直接访问 api.php 时的 fallback 提示
    ├── 20_switch_cases.php           ← switch ($action) 全部 case 路由（~100+ action handler）
    └── 21_import_helpers.php         ← 批量导入辅助函数（importExtractQuestions / pcvlConvertPaperCutterVL 等）
```

## 入口

所有请求统一通过 [api.php](../api.php) → 薄入口，仅做：

```php
error_reporting(E_ALL);
// 按依赖顺序 require 所有 core + modules
require_once __DIR__ . '/php/core/00_bootstrap_header.php';
require_once __DIR__ . '/php/core/01_security_rates.php';
// ... 16 个模块 ...
require_once __DIR__ . '/php/modules/20_switch_cases.php';
```

## 调用约定

- 所有模块文件**不能**包含 `<?php` / `?>` 开闭合标签（薄入口已提供）
- `global $db` 在 core/04_db_schema.php 创建，后续所有模块通过 `global $db` 引用
- `$_SESSION` 在 core/02_session.php 初始化，后续直接使用
- 模块按数字前缀 `00-12 / 20-21` 排序即加载顺序，新增模块请追加到合适位置

## 原始单体备份

拆分前的 6616 行单体备份：[api.php.bak_split](../api.php.bak_split)（纯归档，不参与生产路径）
