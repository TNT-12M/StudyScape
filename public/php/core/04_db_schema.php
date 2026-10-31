
// ===================== SQLite 初始化 =====================
$dbFile = __DIR__ . '/../exam.db';
try {
    $db = new SQLite3($dbFile);
    $db->enableExceptions(true);
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => '数据库连接失败: ' . $e->getMessage()]));
}
try {
    $passwordKey = loadPasswordKey();
} catch (Throwable $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => '密码密钥初始化失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// 创建表
$createSql = "
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

-- 题目表：题干、选项、答案、解析、题型、科目、难度、分值
CREATE TABLE IF NOT EXISTS questions (
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
CREATE INDEX IF NOT EXISTS idx_questions_subject ON questions(subject);
CREATE INDEX IF NOT EXISTS idx_questions_type ON questions(question_type);

-- ===== P1-B: 方案B 组卷考试 / 自由刷题（4 张新表，不动现有表） =====

-- 试卷表（组卷考试用）
CREATE TABLE IF NOT EXISTS papers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    duration_minutes INTEGER NOT NULL DEFAULT 60,
    is_published INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_papers_published ON papers(is_published);

-- 试卷-题目关联表
CREATE TABLE IF NOT EXISTS paper_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paper_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    points REAL NOT NULL DEFAULT 1.0
);
CREATE INDEX IF NOT EXISTS idx_pq_paper ON paper_questions(paper_id);
CREATE INDEX IF NOT EXISTS idx_pq_question ON paper_questions(question_id);

-- 考试/刷题尝试记录（B1 + B2 共用）
CREATE TABLE IF NOT EXISTS exam_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_type TEXT NOT NULL,           -- 'exam' 组卷 / 'practice' 自由刷题
    paper_id INTEGER,                     -- B1时为试卷ID；B2时为NULL
    user_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'in_progress', -- 'in_progress' / 'submitted'
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME,
    duration_minutes INTEGER NOT NULL DEFAULT 0, -- B1来自试卷；B2用户输入，0=不限时
    self_score_total REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_attempts_user ON exam_attempts(user_id);
CREATE INDEX IF NOT EXISTS idx_attempts_paper ON exam_attempts(paper_id);
CREATE INDEX IF NOT EXISTS idx_attempts_type ON exam_attempts(attempt_type);

-- 作答明细表（B1 + B2 共用，每题一条）
CREATE TABLE IF NOT EXISTS exam_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    student_answer TEXT,                 -- 多空填空存 JSON 数组；单选/多选/判断存字符串
    self_correct INTEGER,               -- 1=对,0=错,NULL=未评
    self_score REAL,
    auto_correct INTEGER,               -- 预留：自动判分（本期恒 NULL）
    auto_score REAL,                    -- 预留：自动判分（本期恒 NULL）
    judged_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_answers_attempt ON exam_answers(attempt_id);
CREATE INDEX IF NOT EXISTS idx_answers_question ON exam_answers(question_id);

-- ===== P2-C: 资料下载 =====
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
    $db->exec($createSql);
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => '创建表失败: ' . $e->getMessage()]));
}

// 兼容旧库：若 questions 表缺少 updated_at 列则补上
try {
    $res = $db->query("PRAGMA table_info(questions)");
    $hasUpdated = false;
    if ($res) {
        while ($c = $res->fetchArray(SQLITE3_ASSOC)) {
            if (strcasecmp($c['name'], 'updated_at') === 0) { $hasUpdated = true; break; }
        }
    }
    if (!$hasUpdated) {
        $db->exec("ALTER TABLE questions ADD COLUMN updated_at DATETIME");
    }
} catch (Exception $e) {
    // 静默：表不存在等已被 CREATE 兜底
}

// 注册审批已取消：兼容历史账号，统一视为已注册账号。
try {
    $db->exec("UPDATE users SET is_approved=1 WHERE is_approved=0");
} catch (Exception $e) {
    // 静默：用户表已由 CREATE TABLE 兜底
}

// 学段与题库分类迁移：只在缺列时添加，历史记录统一回填为 junior。
