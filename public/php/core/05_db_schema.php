<?php

// ===================== SQLite 初始化 =====================
$dbFile = __DIR__ . '/../../../exam.db';
try {
    $db = new SQLite3($dbFile);
    $db->enableExceptions(true);
    // SQLite 并发优化: WAL 模式 + busy_timeout 30s (PHP-FPM 多进程时必加)
    $db->exec('PRAGMA journal_mode=WAL;');
    $db->exec('PRAGMA busy_timeout=30000;');
    $db->exec('PRAGMA synchronous=NORMAL;');
    $db->exec('PRAGMA cache_size=-64000;');  // 64MB 缓存
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => '数据库连接失败: ' . $e->getMessage()]));
}

try {
    $passwordKey = loadPasswordKey();
} catch (Throwable $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => '密码密钥初始化失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// ===== 题库 DB 路径解析 =====
// 优先级: 环境变量 > server.env 的 QUESTIONS_DB_PATH > 数据盘探测 > fallback 项目内
// 分布式架构下, 公网机本地有 questions.db 副本 (定期 rsync 从家里云拉取)
if (!function_exists('_resolveQuestionsDbPath')) {
    function _resolveQuestionsDbPath(): string {
        // 1. 最高优先: 环境变量 / server.env
        $configured = env('QUESTIONS_DB_PATH');
        if ($configured) {
            if (is_file($configured)) return $configured;
            // 目录不存在就创建 (首次部署)
            $dir = dirname($configured);
            if (!is_dir($dir) && @mkdir($dir, 0755, true) && is_dir($dir)) {
                return $configured;
            }
        }
        // 2. 探测数据盘 (家里云有 /data, 公网机可能没有)
        foreach (['/data/StudyScape/questions.db', '/data/study_questions.db', '/mnt/data/StudyScape/questions.db'] as $candidate) {
            if (is_dir(dirname($candidate)) || @mkdir(dirname($candidate), 0755, true)) {
                return $candidate;
            }
        }
        // 3. fallback: 系统盘项目内 data/
        $fallback = __DIR__ . '/../../../data/study_questions.db';
        @mkdir(dirname($fallback), 0755, true);
        return $fallback;
    }
}

$questionsDbFile = _resolveQuestionsDbPath();

// ===== ATTACH 题库 DB =====
// SQLite ATTACH 后, action_router 里的所有 SQL 一行不改!
// 查询 "SELECT * FROM questions" 自动命中 qdb schema (因为 main 里已不存在 questions 表)
$safeQPath = str_replace("'", "''", $questionsDbFile);
try {
    $db->exec("ATTACH DATABASE '{$safeQPath}' AS qdb");
    // 记住路径给调试
    $db->exec("PRAGMA application_id = 0x53544442");
} catch (Exception $e) {
    error_log("⚠️ ATTACH qdb 失败，题库将和业务数据共库: " . $e->getMessage());
    $questionsDbFile = $dbFile; // fallback: 和主库同一个文件
}

// ===== MAIN SCHEMA (exam.db — 系统盘, 高频读写) =====
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
    downloads INTEGER DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_materials_subject ON materials(subject);
";
try {
    $db->exec($createMainSql);
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => '创建主表失败: ' . $e->getMessage()]));
}

// ===== QDB SCHEMA (questions.db — 数据盘, 静态题库资产) =====
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
} catch (Exception $e) {
    error_log("⚠️ 创建题库表失败: " . $e->getMessage() . " — 题库功能可能不可用");
}

// ===== 升级迁移: main.questions → qdb.questions =====
// 旧 exam.db 里 questions/papers/paper_questions 可能在 main schema 里
// 如果 qdb.questions 还没数据, 就把 main 的数据迁过去再 DROP main 的表
$qTables = ['questions', 'papers', 'paper_questions'];
foreach ($qTables as $t) {
    try {
        // main 里有没有?
        $res = $db->query("SELECT name FROM main.sqlite_master WHERE type='table' AND name='" . SQLite3::escapeString($t) . "'");
        if ($res && $row = $res->fetchArray()) {
            $count = (int)$db->querySingle("SELECT COUNT(*) FROM main.{$t}");
            if ($count > 0) {
                // 检查 qdb 里是不是空 (避免覆盖已有)
                $qcount = (int)$db->querySingle("SELECT COUNT(*) FROM qdb.{$t}");
                if ($qcount === 0) {
                    error_log("📦 升级迁移 main.{$t} ({$count} 行) → qdb.{$t}");
                    $db->exec("INSERT INTO qdb.{$t} SELECT * FROM main.{$t}");
                } else {
                    error_log("⚠️ main.{$t} 有 {$count} 行但 qdb.{$t} 已有 {$qcount} 行 — 跳过迁移");
                }
            }
            // DROP main 的表 (无论有没有数据)
            $db->exec("DROP TABLE main.{$t}");
        }
    } catch (Exception $e) {
        error_log("升级迁移 {$t} 失败: " . $e->getMessage());
    }
}

// ===== 兼容旧库: qdb.questions 补 updated_at 列 =====
try {
    $res = $db->query("PRAGMA qdb.table_info(questions)");
    $hasUpdated = false;
    if ($res) {
        while ($c = $res->fetchArray(SQLITE3_ASSOC)) {
            if (strcasecmp($c['name'], 'updated_at') === 0) { $hasUpdated = true; break; }
        }
    }
    if (!$hasUpdated) {
        $db->exec("ALTER TABLE qdb.questions ADD COLUMN updated_at DATETIME");
    }
} catch (Exception $e) {
    // 静默
}

// ===== 兼容旧库: materials 补 sha256 / storage_node =====
try {
    $res = $db->query("PRAGMA table_info(materials)");
    $cols = [];
    if ($res) {
        while ($c = $res->fetchArray(SQLITE3_ASSOC)) $cols[] = strtolower($c['name']);
    }
    if (!in_array('sha256', $cols)) $db->exec("ALTER TABLE materials ADD COLUMN sha256 TEXT");
    if (!in_array('storage_node', $cols)) {
        $db->exec("ALTER TABLE materials ADD COLUMN storage_node TEXT DEFAULT 'local'");
        $db->exec("UPDATE materials SET storage_node='local' WHERE storage_node IS NULL");
    }
} catch (Exception $e) {
    // 静默
}

// 注册审批已取消：兼容历史账号，统一视为已注册账号。
try {
    $db->exec("UPDATE users SET is_approved=1 WHERE is_approved=0");
} catch (Exception $e) {
    // 静默
}

// 学段与题库分类迁移（继续读文件剩余部分...）
