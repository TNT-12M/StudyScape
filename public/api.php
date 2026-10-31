<?php
/**
 * StudyScape API — 薄入口 (thin entry)
 *
 * 6000+ 行原始单体文件已按业务域拆分为 php/core/ + php/modules/。
 * 本文件仅保留 bootstrap + require 顺序 + action 分发调度。
 *
 * 架构:
 *   public/api.php           ← 本文件 (薄入口)
 *   public/php/core/*.php    ← 核心基础设施 (安全/Session/DB/Captcha/OCR...)
 *   public/php/modules/*.php ← 业务模块 (switch case 路由 / 批量导入辅助)
 *   public/vendor/Gregwar/   ← 第三方库 (Captcha, MIT)
 *
 * 原始单体备份: public/api.php.bak_split
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================
// Load order matters! Each module relies on globals ($db, $_SESSION)
// and functions defined in earlier modules.
// ============================================================

// --- 00. Autoload + bootstrap (error_reporting already set above) ---
require_once __DIR__ . '/php/core/00_bootstrap_header.php';

// --- 01. Security rate-limit buckets ---
require_once __DIR__ . '/php/core/01_security_rates.php';

// --- 02. Session hardening ---
require_once __DIR__ . '/php/core/02_session.php';

// --- 03. PHP security scan ---
require_once __DIR__ . '/php/core/03_security_scan.php';

// --- 04. Password / SMTP / email captcha config ---
require_once __DIR__ . '/php/core/04_config_and_auth.php';

// --- 05. SQLite schema init ---
require_once __DIR__ . '/php/core/05_db_schema.php';

// --- 06. DB + OCR helper utilities ---
require_once __DIR__ . '/php/core/05_db_utils.php';

// --- 07. Captcha generator ---
require_once __DIR__ . '/php/core/06_captcha.php';

// --- 08. Site stats + OCR bridge ---
require_once __DIR__ . '/php/core/07_site_and_ocr.php';

// --- 09. record_visit + captcha inline actions ---
require_once __DIR__ . '/php/core/08_inline_actions.php';

// --- 10. jsonOut / sanitize ---
require_once __DIR__ . '/php/core/09_json_and_sanitize.php';

// --- 11. Auth / session / permission helpers ---
require_once __DIR__ . '/php/core/10_auth.php';

// --- 12. User profile + login throttle + dbQuery wrappers ---
require_once __DIR__ . '/php/core/11_user_and_db.php';

// --- 13. CSRF bypass list + CSRF check ---
require_once __DIR__ . '/php/core/12_csrf.php';

// === Switch case router (all action handlers) ===
require_once __DIR__ . '/php/modules/20_switch_cases.php';

// === Fallback when no $action ===
require_once __DIR__ . '/php/modules/00_fallback.php';

// === Batch import helpers ===
require_once __DIR__ . '/php/modules/21_import_helpers.php';
