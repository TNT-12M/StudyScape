<?php
/**
 * StudyScape API — 薄入口 (thin entry)
 *
 * 6000+ 行单体已按业务域拆分为 php/core/ + php/modules/。
 * 本文件仅保留 bootstrap + require 顺序。
 *
 * 架构:
 *   public/api.php           ← 本文件 (薄入口, 仅 bootstrap)
 *   public/php/core/*.php    ← 核心基础设施
 *   public/php/modules/*.php ← 业务模块
 *   public/vendor/Gregwar/   ← 第三方库 (Captcha, MIT)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ===== 依赖序加载（单文件 PHP 是 parse-first-execute-later，多文件 require 是逐个 parse+execute）=====
// 每个模块开头都自带 <?php, 这里只负责 require 顺序。
// ★ 顶层代码调用的函数必须在更早的文件中已定义（无跨文件前向引用）。

require_once __DIR__ . '/php/core/00_bootstrap.php';         // autoloader (Gregwar/Captcha), display_errors=0
require_once __DIR__ . '/php/core/01_security_rates.php';    // 4 类漏桶速率检测器
require_once __DIR__ . '/php/core/02_session.php';           // session_start()
require_once __DIR__ . '/php/core/04_config_and_auth.php';   // loadPasswordKey, encryptPassword, smtpConfigPath
require_once __DIR__ . '/php/core/05_db_schema.php';         // TOP: new SQLite3 + CREATE TABLE (needs loadPasswordKey ✅)
require_once __DIR__ . '/php/core/12_user_and_db.php';       // dbQuery/dbFetchAll/dbFetchOne + 用户辅助
require_once __DIR__ . '/php/core/06_db_utils.php';          // TOP: schema migrations (needs dbQuery ✅, $db ✅)
require_once __DIR__ . '/php/core/03_security_scan.php';     // triggerSecurityScan (lazy function body)
require_once __DIR__ . '/php/core/07_captcha.php';           // createCaptcha
require_once __DIR__ . '/php/core/10_json_and_sanitize.php'; // jsonOut, sanitizeInput, validate*
require_once __DIR__ . '/php/core/09_inline_actions.php';    // TOP: record_visit/captcha (needs jsonOut ✅)
require_once __DIR__ . '/php/core/08_site_and_ocr.php';     // site stats + OCR bridge (lazy, uses dbQuery)
require_once __DIR__ . '/php/core/11_auth.php';              // isLoggedIn / requireAdmin (lazy)
require_once __DIR__ . '/php/core/13_csrf.php';              // CSRF bypass + 校验 (lazy)

// ===== 业务模块 =====
require_once __DIR__ . '/php/modules/21_import_helpers.php'; // importExtractQuestions, pcvl*, examB*
require_once __DIR__ . '/php/modules/20_action_router.php';  // TOP: switch (needs EVERYTHING above ✅)
require_once __DIR__ . '/php/modules/00_fallback.php';       // fallback (needs jsonOut ✅)
