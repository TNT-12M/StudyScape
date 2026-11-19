<?php

// ===================== SQLite 初始化 =====================
$dbFile = __DIR__ . '/../../../exam.db';
try {
    $db = new SQLite3($dbFile, SQLITE3_OPEN_READWRITE);
    $db->enableExceptions(true);
} catch (Exception $e) {
    // 锁被占时最多等 30s, 每次 sleep 0.1s
    for ($tries = 0; $tries < 300; $tries++) {
        usleep(100000);
        try {
            $db = new SQLite3($dbFile, SQLITE3_OPEN_READWRITE);
            $db->enableExceptions(true);
            break;
        } catch (\Throwable $_) {}
    }
    if (!$db) {
        die(json_encode(['success' => false, 'message' => '数据库连接失败: ' . $e->getMessage()]));
    }
}

// SQLite 并发优化 (PRAGMA 轻量, 每次执行是查询状态)
$db->exec('PRAGMA busy_timeout=30000;');
try {
    $db->exec('PRAGMA journal_mode=WAL;');
} catch (Exception $e) { /* 并发下可能拿不到锁, WAL 已持久化, 跳过即可 */ }
$db->exec('PRAGMA synchronous=NORMAL;');
$db->exec('PRAGMA cache_size=-64000;');

try {
    $passwordKey = loadPasswordKey();
} catch (Throwable $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => '密码密钥初始化失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// ===== 题库 DB 路径解析 (快速, 不做 mkdir) =====
if (!function_exists('_resolveQuestionsDbPath')) {
    function _resolveQuestionsDbPath(): string {
        $configured = env('QUESTIONS_DB_PATH');
        if ($configured && is_file($configured)) return $configured;
        foreach (['/data/StudyScape/questions.db', '/data/study_questions.db', '/mnt/data/StudyScape/questions.db'] as $candidate) {
            if (is_file($candidate)) return $candidate;
        }
        return __DIR__ . '/../../../data/study_questions.db';
    }
}

$questionsDbFile = _resolveQuestionsDbPath();

// ===== ATTACH 题库 DB =====
$safeQPath = str_replace("'", "''", $questionsDbFile);
try {
    $db->exec("ATTACH DATABASE '{$safeQPath}' AS qdb");
} catch (Exception $e) {
    error_log("⚠️ ATTACH qdb 失败，题库将和业务数据共库: " . $e->getMessage());
    $questionsDbFile = $dbFile;
}

// ===== MAIN SCHEMA (exam.db — 系统盘, 高频读写) =====
// PHP-FPM 每个 worker 进程内 static 变量共享, 初始化只跑一次
if (!isset($GLOBALS['_ss_main_schema_done'])) {
    $GLOBALS['_ss_main_schema_done'] = false;
}
if (!$GLOBALS['_ss_main_schema_done']) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    } catch (\Throwable $_) {}

    // 只有还没完成过的进程才跑 CREATE TABLE/INDEX (SQLite IF NOT EXISTS 也快, 但少跑一次少一次)
    $mainSchemaDone = false;
    try { $mainSchemaDone = (bool)$db->querySingle("SELECT 1 FROM schema_migrations WHERE version = 'main_schema_v1'"); } catch (\Throwable $_) {}

    if (!$mainSchemaDone) {
        $createMainSql = "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    is_initial_root INTEGER NOT NULL DEFAULT 0,
    is_admin INTEGER DEFAULT 0,
    is_approved INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    nickname TEXT DEFAULT NULL,
    avatar TEXT DEFAULT NULL,
    gender TEXT DEFAULT 'secret',
    grade TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
);
CREATE TABLE IF NOT EXISTS exam_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    parent_id INTEGER DEFAULT 0,
    json_content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS exam_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_type TEXT NOT NULL,
    paper_id INTEGER,
    user_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'in_progress',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME,
    duration_minutes INTEGER NOT NULL DEFAULT 0,
    self_score_total REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_attempts_user ON exam_attempts(user_id);
CREATE INDEX IF NOT EXISTS idx_attempts_paper ON exam_attempts(paper_id);
CREATE INDEX IF NOT EXISTS idx_attempts_type ON exam_attempts(attempt_type);
CREATE TABLE IF NOT EXISTS exam_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    student_answer TEXT,
    self_correct INTEGER,
    self_score REAL,
    auto_correct INTEGER,
    auto_score REAL,
    judged_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_answers_attempt ON exam_answers(attempt_id);
CREATE INDEX IF NOT EXISTS idx_answers_question ON exam_answers(question_id);
CREATE TABLE IF NOT EXISTS materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type TEXT,
    subject TEXT,
    description TEXT,
    uploaded_by INTEGER,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    downloads INTEGER DEFAULT 0,
    source_sha256 TEXT,
    revision INTEGER DEFAULT 1,
    platform_watermark_version INTEGER,
    platform_watermark_status TEXT DEFAULT 'pending',
    platform_watermarked_at DATETIME,
    processing_error TEXT
);
CREATE INDEX IF NOT EXISTS idx_materials_subject ON materials(subject);
";
        try {
            $db->exec($createMainSql);
            $db->exec("INSERT OR IGNORE INTO schema_migrations (version) VALUES ('main_schema_v1')");
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => '创建主表失败: ' . $e->getMessage()]));
        }
    }

    // ===== QDB SCHEMA =====
    $qdbSchemaDone = false;
    try { $qdbSchemaDone = (bool)$db->querySingle("SELECT 1 FROM schema_migrations WHERE version = 'qdb_schema_v1'"); } catch (\Throwable $_) {}
    if (!$qdbSchemaDone) {
        $createQSql = "
CREATE TABLE IF NOT EXISTS qdb.questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject TEXT NOT NULL,
    question_type TEXT NOT NULL,
    content TEXT NOT NULL,
    options TEXT,
    correct_answer TEXT NOT NULL,
    explanation TEXT,
    difficulty INTEGER DEFAULT 1,
    points REAL DEFAULT 1.0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS qdb.idx_questions_subject ON questions(subject);
CREATE INDEX IF NOT EXISTS qdb.idx_questions_type ON questions(question_type);
CREATE TABLE IF NOT EXISTS qdb.papers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    duration_minutes INTEGER NOT NULL DEFAULT 60,
    is_published INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS qdb.idx_papers_published ON papers(is_published);
CREATE TABLE IF NOT EXISTS qdb.paper_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paper_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    points REAL NOT NULL DEFAULT 1.0
);
CREATE INDEX IF NOT EXISTS qdb.idx_pq_paper ON paper_questions(paper_id);
CREATE INDEX IF NOT EXISTS qdb.idx_pq_question ON paper_questions(question_id);
";
        try {
            $db->exec($createQSql);
            $db->exec("INSERT OR IGNORE INTO schema_migrations (version) VALUES ('qdb_schema_v1')");
        } catch (Exception $e) {
            error_log("⚠️ 创建题库表失败: " . $e->getMessage() . " — 题库功能可能不可用");
        }
    }

    $GLOBALS['_ss_main_schema_done'] = true;
}

// ===== 只跑一次的 migration 版本 =====
// 用 schema_migrations 跟踪已执行版本号, 避免每次请求都跑全量 UPDATE/ALTER
$MIGRATION_VERSION = 14;

function _runOnce($db, int $version, string $label, callable $fn): void {
    // static 数组: 同一 worker 进程内第一次查完 DB 就记住了, 后续请求 0 DB 查询
    static $done = [];
    if (isset($done[$version])) return;

    // 精确查本版本号是否存在即可 — version 列是 TEXT, 不能 ORDER BY 数值排序
    try {
        if ($db->querySingle("SELECT 1 FROM schema_migrations WHERE version = '$version'")) {
            $done[$version] = true;
            return;
        }
    } catch (\Throwable $_) { /* schema_migrations 表可能还没, 继续跑 */ }

    try {
        $fn();
        $db->exec("INSERT OR IGNORE INTO schema_migrations (version) VALUES ('$version')");
        $done[$version] = true;
        error_log("✅ Migration v$version: $label");
    } catch (\Throwable $e) {
        error_log("⚠️ Migration v$version 失败: " . $e->getMessage());
    }
}

// v1-6: 旧 migration (schema_migrations 表里已有, 跳过)
_runOnce($db, 7, 'main.questions → qdb.questions 数据迁移 + DROP main.questions', function() use ($db) {
    $qTables = ['questions', 'papers', 'paper_questions'];
    foreach ($qTables as $t) {
        $res = $db->query("SELECT name FROM main.sqlite_master WHERE type='table' AND name='" . SQLite3::escapeString($t) . "'");
        if ($res && $res->fetchArray()) {
            $count = (int)$db->querySingle("SELECT COUNT(*) FROM main.{$t}");
            if ($count > 0 && (int)$db->querySingle("SELECT COUNT(*) FROM qdb.{$t}") === 0) {
                $db->exec("INSERT INTO qdb.{$t} SELECT * FROM main.{$t}");
            }
            $db->exec("DROP TABLE main.{$t}");
        }
    }
});

_runOnce($db, 8, 'qdb.questions 补 updated_at 列', function() use ($db) {
    $res = $db->query("PRAGMA qdb.table_info(questions)");
    $has = false;
    if ($res) while ($c = $res->fetchArray(SQLITE3_ASSOC)) {
        if (strcasecmp($c['name'], 'updated_at') === 0) { $has = true; break; }
    }
    if (!$has) $db->exec("ALTER TABLE qdb.questions ADD COLUMN updated_at DATETIME");
});

_runOnce($db, 9, 'materials 补 sha256 / storage_node', function() use ($db) {
    $res = $db->query("PRAGMA table_info(materials)");
    $cols = [];
    if ($res) while ($c = $res->fetchArray(SQLITE3_ASSOC)) $cols[] = strtolower($c['name']);
    if (!in_array('sha256', $cols)) $db->exec("ALTER TABLE materials ADD COLUMN sha256 TEXT");
    if (!in_array('storage_node', $cols)) {
        $db->exec("ALTER TABLE materials ADD COLUMN storage_node TEXT DEFAULT 'local'");
        $db->exec("UPDATE materials SET storage_node='local' WHERE storage_node IS NULL");
    }
});

_runOnce($db, 10, '注册审批取消 — 批量 UPDATE is_approved', function() use ($db) {
    $db->exec("UPDATE users SET is_approved=1 WHERE is_approved=0");
});

_runOnce($db, 11, '反馈多轮回复表 feedback_replies', function() use ($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS feedback_replies (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        feedback_id INTEGER NOT NULL,
        author_id INTEGER NOT NULL,
        author_role TEXT NOT NULL DEFAULT 'user',
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_feedback_replies_feedback ON feedback_replies(feedback_id, id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_feedback_replies_author ON feedback_replies(feedback_id, author_id, id DESC)");
});

_runOnce($db, 12, '用户个性化设置表 user_settings', function() use ($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS user_settings (
        user_id INTEGER PRIMARY KEY,
        wallpaper TEXT NOT NULL DEFAULT '',
        guide_seen INTEGER NOT NULL DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
});

_runOnce($db, 13, '站点配置键值表 app_settings', function() use ($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS app_settings (
        k TEXT PRIMARY KEY,
        v TEXT NOT NULL DEFAULT '',
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
});

_runOnce($db, 14, '资料两阶段水印与用户下载变体缓存', function() use ($db) {
    $res = $db->query("PRAGMA table_info(materials)");
    $cols = [];
    if ($res) while ($c = $res->fetchArray(SQLITE3_ASSOC)) $cols[] = strtolower($c['name']);
    $definitions = [
        'source_sha256' => 'TEXT',
        'revision' => 'INTEGER DEFAULT 1',
        'platform_watermark_version' => 'INTEGER',
        'platform_watermark_status' => "TEXT DEFAULT 'pending'",
        'platform_watermarked_at' => 'DATETIME',
        'processing_error' => 'TEXT',
    ];
    foreach ($definitions as $name => $definition) {
        if (!in_array($name, $cols, true)) {
            $db->exec("ALTER TABLE materials ADD COLUMN {$name} {$definition}");
        }
    }
    $db->exec("UPDATE materials SET revision=1 WHERE revision IS NULL OR revision < 1");
    $db->exec("UPDATE materials SET platform_watermark_status='pending' WHERE platform_watermark_status IS NULL OR platform_watermark_status='' ");
    $db->exec("CREATE TABLE IF NOT EXISTS material_download_variants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        material_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        username TEXT NOT NULL,
        material_revision INTEGER NOT NULL,
        variant_name TEXT NOT NULL,
        sha256 TEXT NOT NULL,
        file_size INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(material_id, user_id, username, material_revision)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_material_variants_material ON material_download_variants(material_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_material_variants_user ON material_download_variants(user_id, username)");
});
