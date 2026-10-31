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

// ===== 按依赖序加载 =====
// (每个模块开头都自带 <?php, 这里只负责 require 顺序)

require_once __DIR__ . '/php/core/00_bootstrap.php';
require_once __DIR__ . '/php/core/01_security_rates.php';
require_once __DIR__ . '/php/core/02_session.php';
require_once __DIR__ . '/php/core/03_security_scan.php';
require_once __DIR__ . '/php/core/04_config_and_auth.php';
require_once __DIR__ . '/php/core/05_db_schema.php';
require_once __DIR__ . '/php/core/06_db_utils.php';
require_once __DIR__ . '/php/core/07_captcha.php';
require_once __DIR__ . '/php/core/08_site_and_ocr.php';
require_once __DIR__ . '/php/core/09_inline_actions.php';
require_once __DIR__ . '/php/core/10_json_and_sanitize.php';
require_once __DIR__ . '/php/core/11_auth.php';
require_once __DIR__ . '/php/core/12_user_and_db.php';
require_once __DIR__ . '/php/core/13_csrf.php';

// ===== 业务模块 =====
require_once __DIR__ . '/php/modules/20_action_router.php';
require_once __DIR__ . '/php/modules/00_fallback.php';
require_once __DIR__ . '/php/modules/21_import_helpers.php';
