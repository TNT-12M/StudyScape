<?php
/**
 * 初高中在线考试系统 - API接口
 * db文件: exam.db 自动生成
 * 表：users, exam_data
 * 【警告：密码明文存储，仅用于本地测试，禁止外网部署】
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== P0-A5: HTTP 安全头 =====
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

// ===== P0-A3: Session 安全加固 =====
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secure,
]);
session_start();

// ===================== SQLite 初始化 =====================
$dbFile = __DIR__ . '/../exam.db';
try {
    $db = new SQLite3($dbFile);
    $db->enableExceptions(true);
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => '数据库连接失败: ' . $e->getMessage()]));
}

// 创建表
$createSql = "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    is_admin INTEGER DEFAULT 0,
    is_approved INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
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
function tableHasColumn(SQLite3 $db, string $table, string $column): bool {
    $result = $db->query("PRAGMA table_info(" . preg_replace('/[^A-Za-z0-9_]/', '', $table) . ")");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (strcasecmp($row['name'], $column) === 0) return true;
    }
    return false;
}
try {
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    if (!tableHasColumn($db, 'questions', 'education_level')) {
        $db->exec("ALTER TABLE questions ADD COLUMN education_level TEXT NOT NULL DEFAULT 'junior'");
    }
    if (!tableHasColumn($db, 'questions', 'category')) {
        $db->exec("ALTER TABLE questions ADD COLUMN category TEXT NOT NULL DEFAULT 'single'");
    }
    if (!tableHasColumn($db, 'questions', 'is_html')) {
        // 富 HTML 题干（含 base64 图片/表格等 PaperCutter-VL 输出）：1=是，0=否
        $db->exec("ALTER TABLE questions ADD COLUMN is_html INTEGER NOT NULL DEFAULT 0");
    }
    // OCR 流水线对接：匹配码 + 原始题号（用于题目-答案精准匹配）
    if (!tableHasColumn($db, 'questions', 'match_key')) {
        $db->exec("ALTER TABLE questions ADD COLUMN match_key TEXT NOT NULL DEFAULT ''");
    }
    if (!tableHasColumn($db, 'questions', 'source_qid')) {
        $db->exec("ALTER TABLE questions ADD COLUMN source_qid TEXT NOT NULL DEFAULT ''");
    }
    if (!tableHasColumn($db, 'materials', 'education_level')) {
        $db->exec("ALTER TABLE materials ADD COLUMN education_level TEXT NOT NULL DEFAULT 'junior'");
    }
    if (!tableHasColumn($db, 'materials', 'updated_at')) {
        // SQLite 的 ALTER ADD COLUMN 不允许 CURRENT_TIMESTAMP 这类非字面量默认值，故省略 DEFAULT（列允许 NULL，写入时由应用层填充）
        $db->exec("ALTER TABLE materials ADD COLUMN updated_at DATETIME");
    }
    $migration = dbFetchOne($db, "SELECT version FROM schema_migrations WHERE version='education_level_category_v1'");
    if (!$migration) {
        $db->exec('BEGIN IMMEDIATE');
        $db->exec("UPDATE questions SET education_level='junior'");
        $db->exec("UPDATE questions SET category=question_type WHERE question_type IN ('single','multiple','judge','fill','multi_fill','short')");
        $db->exec("UPDATE materials SET education_level='junior'");
        $db->exec("INSERT INTO schema_migrations(version) VALUES('education_level_category_v1')");
        $db->exec('COMMIT');
    }
    $db->exec("UPDATE questions SET education_level='junior' WHERE education_level IS NULL OR education_level='' OR education_level NOT IN ('junior','senior')");
    $db->exec("UPDATE questions SET category=question_type WHERE category IS NULL OR category='' OR category NOT IN ('single','multiple','judge','fill','multi_fill','short')");
    $db->exec("UPDATE materials SET education_level='junior' WHERE education_level IS NULL OR education_level='' OR education_level NOT IN ('junior','senior')");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_questions_education_level ON questions(education_level)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_questions_level_subject ON questions(education_level, subject)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_questions_match_key ON questions(match_key)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_questions_source_qid ON questions(source_qid)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_materials_education_level ON materials(education_level)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_materials_level_subject ON materials(education_level, subject)");
} catch (Exception $e) {
    // ROLLBACK 自身可能因无活跃事务而抛异常（enableExceptions(true) 时 @ 无法抑制），用内部 try-catch 兜底，避免掩盖原始错误
    try { $db->exec('ROLLBACK'); } catch (Exception $rb) {}
    error_log('education_level migration failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => '学段字段迁移失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// ==========================================================
// import_batches 表：导入批次暂存（用于题目-答案自动匹配）
// ==========================================================
try {
    $db->exec("CREATE TABLE IF NOT EXISTS import_batches (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        match_key TEXT NOT NULL DEFAULT '',
        batch_type TEXT NOT NULL DEFAULT 'questions',
        file_name TEXT NOT NULL DEFAULT '',
        subject TEXT NOT NULL DEFAULT '',
        education_level TEXT NOT NULL DEFAULT 'junior',
        question_count INTEGER NOT NULL DEFAULT 0,
        answer_count INTEGER NOT NULL DEFAULT 0,
        questions_json TEXT,
        answers_json TEXT,
        matched_batch_id INTEGER DEFAULT NULL,
        match_status TEXT NOT NULL DEFAULT 'pending',
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_import_batches_match_key ON import_batches(match_key)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_import_batches_status ON import_batches(match_status)");
} catch (Exception $e) {
    error_log('import_batches table init failed: ' . $e->getMessage());
}

// 生成匹配码：从文件名中提取核心标识
function generateMatchKey(string $fileName, string $extraHint = ''): string {
    $name = pathinfo($fileName, PATHINFO_FILENAME);
    $removeWords = [
        '试卷', '试题', '考题', '题目', '练习', '训练',
        '答案', '解析', '解答', '参考答案', '答案及解析', '真题及答案', '真题答案',
        '真题', '模拟卷', '模拟题', '冲刺卷', '预测卷', '押题卷',
        '及答案', '及解析', '含答案', '含解析',
        'PDF', 'pdf',
    ];
    foreach ($removeWords as $w) {
        $name = str_replace($w, '', $name);
    }
    $name = preg_replace('/[\s\-_—–·.()（）【】\[\]【】]+/u', '', $name);
    $name = mb_strtolower($name);
    if ($name === '') {
        $name = substr(md5($extraHint), 0, 16);
    }
    return $name;
}

// 解析答案文本，返回 {题号 => 答案}
function parseAnswerText(string $text): array {
    $answers = [];
    // 模式1：区间格式 "1-5: DBCCB"
    preg_match_all('/(\d{1,3})\s*[-~—]\s*(\d{1,3})\s*[:：]\s*([A-Za-z]+)/', $text, $rangeMatches, PREG_SET_ORDER);
    foreach ($rangeMatches as $m) {
        $start = (int)$m[1];
        $end = (int)$m[2];
        $letters = strtoupper($m[3]);
        $len = strlen($letters);
        for ($i = 0; $i < $len && $start + $i <= $end; $i++) {
            $answers[$start + $i] = $letters[$i];
        }
    }
    // 模式2：单行 "1. A"
    preg_match_all('/^\s*(\d{1,3})\s*[.、)）:：]\s*([A-Za-z]+)\s*$/m', $text, $singleMatches, PREG_SET_ORDER);
    foreach ($singleMatches as $m) {
        $num = (int)$m[1];
        $ans = strtoupper($m[2]);
        if (!isset($answers[$num])) $answers[$num] = $ans;
    }
    // 模式3：内联 "1 A 2 B"
    preg_match_all('/(\d{1,3})\s+([A-Za-z])/', $text, $inlineMatches, PREG_SET_ORDER);
    foreach ($inlineMatches as $m) {
        $num = (int)$m[1];
        $ans = strtoupper($m[2]);
        if (!isset($answers[$num]) && $num > 0 && $num <= 200) {
            $answers[$num] = $ans;
        }
    }
    ksort($answers);
    return $answers;
}

// 将答案回填到题目数组中（按题号匹配）
function applyAnswersToQuestions(array $questions, array $answers): array {
    $result = $questions;
    $filled = 0;
    foreach ($result as &$q) {
        $qNum = $q['number'] ?? $q['question_id'] ?? 0;
        if ($qNum > 0 && isset($answers[$qNum])) {
            $ansLetter = $answers[$qNum];
            if (!empty($q['options']) && is_array($q['options'])) {
                foreach ($q['options'] as $opt) {
                    if (preg_match('/^\s*' . preg_quote($ansLetter, '/') . '\s*[.、)）:：]\s*/i', $opt)) {
                        $q['correct_answer'] = $opt;
                        $filled++;
                        break;
                    }
                }
            }
            if (empty($q['correct_answer'])) {
                $q['correct_answer'] = $ansLetter;
                $filled++;
            }
        }
    }
    unset($q);
    return ['questions' => $result, 'filled_count' => $filled];
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 生成验证码
function createCaptcha() {
    $code = strtoupper(substr(md5(microtime()), 0, 4));
    $_SESSION['captcha_code'] = $code;
    return $code;
}

// ===================== API路由 =====================
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 验证码图片
if ($action === 'captcha') {
    $code = createCaptcha();
    header("Content-type: image/png");
    $im = imagecreate(120, 40);
    $bg = imagecolorallocate($im, 240, 240, 240);
    $txtcolor = imagecolorallocate($im, 40, 40, 40);
    $linecolor = imagecolorallocate($im, 180, 180, 180);
    imageline($im, 0, rand(0, 40), 120, rand(0, 40), $linecolor);
    imageline($im, 0, rand(0, 40), 120, rand(0, 40), $linecolor);
    imagestring($im, 5, 30, 8, $code, $txtcolor);
    imagepng($im);
    imagedestroy($im);
    exit;
}

// 输出json辅助
function jsonOut($success, $msg = '', $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $msg,
        'data' => $data,
        'csrf_token' => $_SESSION['csrf_token'] ?? ''
    ]);
    exit;
}

// 工具函数
function sanitizeInput($v) {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

function validateEducationLevel($value, string $default = 'junior'): string {
    $value = strtolower(trim((string)$value));
    if ($value === '') return $default;
    if (!in_array($value, ['junior', 'senior'], true)) {
        jsonOut(false, '学段无效，只能选择初中或高中');
    }
    return $value;
}

function validateQuestionCategory($value, string $default = 'single'): string {
    $value = strtolower(trim((string)$value));
    if ($value === '') return $default;
    if (!in_array($value, ['single', 'multiple', 'judge', 'fill', 'multi_fill', 'short'], true)) {
        jsonOut(false, '分类无效');
    }
    return $value;
}

/**
 * 统一科目名称，防止同义科目因名称不同而分裂
 * 例：道法 → 道德与法治，政治 → 政治（不合并，保留独立科目）
 */
function normalizeSubject(string $subject): string {
    $subject = trim($subject);
    if ($subject === '') return $subject;
    // 别名 → 标准名
    $aliases = [
        '道法' => '道德与法治',
        '思想政治' => '道德与法治',
        '思想品德' => '道德与法治',
        '政史' => '道德与法治',  // 粗略处理，实际应该拆分
        '信息' => '信息技术',
        '计算机' => '信息技术',
        '英语（本）' => '英语',
        '语文（本）' => '语文',
        '数学（本）' => '数学',
    ];
    if (isset($aliases[$subject])) return $aliases[$subject];
    return $subject;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    global $db;
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0 || !isset($db)) return false;

    $user = dbFetchOne($db, "SELECT id, username, is_admin, is_active, is_approved FROM users WHERE id=?", [$uid]);
    if (!$user || !(int)$user['is_active']) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['is_admin'], $_SESSION['is_approved']);
        return false;
    }

    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = (bool)$user['is_admin'];
    $_SESSION['is_approved'] = (bool)$user['is_approved'];
    return (bool)$user['is_admin'];
}

function requireAdmin() {
    if (!isLoggedIn()) jsonOut(false, "请先登录");
    if (!isAdmin()) jsonOut(false, "权限不足，仅管理员可操作");
    return (int)$_SESSION['user_id'];
}

function invalidateSession() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function getUid() {
    return $_SESSION['user_id'] ?? null;
}

function createDangerChallenge(string $target): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 12; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    $_SESSION['danger_challenge'] = [
        'action' => $target,
        'user_id' => (int)$_SESSION['user_id'],
        'hash' => hash('sha256', $code),
        'expires_at' => time() + 300,
    ];
    return $code;
}

function consumeDangerChallenge(string $target, string $code, bool $confirmed): bool {
    $challenge = $_SESSION['danger_challenge'] ?? null;
    unset($_SESSION['danger_challenge']);
    if (!$confirmed || !$challenge || !is_array($challenge)) return false;
    if ($challenge['action'] !== $target || (int)$challenge['user_id'] !== (int)$_SESSION['user_id']) return false;
    if (time() > (int)$challenge['expires_at']) return false;
    return hash_equals((string)$challenge['hash'], hash('sha256', strtoupper(trim($code))));
}

// SQLite CURRENT_TIMESTAMP 使用 UTC 保存；用户界面统一显示中国标准时间。
function formatDbUtcTimestamp($value) {
    if ($value === null || $value === '') return $value;

    $utc = new DateTimeZone('UTC');
    $china = new DateTimeZone('Asia/Shanghai');
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string)$value, $utc);
    if (!$dt) return $value;

    return $dt->setTimezone($china)->format('Y-m-d H:i:s');
}

function formatUserTimestamps(array $user): array {
    foreach (['created_at', 'last_login'] as $field) {
        if (array_key_exists($field, $user)) {
            $user[$field] = formatDbUtcTimestamp($user[$field]);
        }
    }
    return $user;
}

// ===== P0-A6: 管理员操作审计日志 =====
function log_admin_action(string $action, $target_uid = null): void {
    $logDir = __DIR__ . '/../data/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $line = json_encode([
        'time'       => gmdate('Y-m-d H:i:s'),
        'action'     => $action,
        'admin_id'   => $_SESSION['user_id'] ?? null,
        'admin_name' => $_SESSION['username'] ?? null,
        'target_uid' => $target_uid,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ], JSON_UNESCAPED_UNICODE);
    @file_put_contents($logDir . '/admin_access.log', $line . PHP_EOL, FILE_APPEND);
}

// ===== P0-A4: 登录限流（Session + IP 文件双通道） =====
define('LOGIN_THROTTLE_SESSION_MAX', 5);     // 会话级：5 次失败
define('LOGIN_THROTTLE_SESSION_LOCK', 300);  // 会话级：锁 5 分钟
define('LOGIN_THROTTLE_IP_MAX', 20);         // IP 级：5 分钟内 20 次
define('LOGIN_THROTTLE_IP_LOCK', 1800);      // IP 级：锁 30 分钟

function loginThrottleIpFile(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $safeIp = preg_replace('/[^a-fA-F0-9.:_-]/', '_', $ip);
    $logDir = __DIR__ . '/../data/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    return $logDir . '/login_throttle_' . $safeIp . '.json';
}

function checkLoginThrottle(): bool {
    // 会话级检查
    if (!empty($_SESSION['login_lock_until']) && time() < $_SESSION['login_lock_until']) {
        return false;
    }
    // IP 级检查
    $file = loginThrottleIpFile();
    if (is_file($file)) {
        $raw = @json_decode(@file_get_contents($file), true);
        if (is_array($raw)) {
            if (!empty($raw['lock_until']) && time() < $raw['lock_until']) return false;
        }
    }
    return true;
}

function recordLoginFail(): void {
    // 会话级计数
    $_SESSION['login_fail_count'] = ($_SESSION['login_fail_count'] ?? 0) + 1;
    if ($_SESSION['login_fail_count'] >= LOGIN_THROTTLE_SESSION_MAX) {
        $_SESSION['login_lock_until'] = time() + LOGIN_THROTTLE_SESSION_LOCK;
    }
    // IP 级（文件）
    $file = loginThrottleIpFile();
    $now  = time();
    $data = ['fails' => [], 'lock_until' => 0];
    if (is_file($file)) {
        $raw = @json_decode(@file_get_contents($file), true);
        if (is_array($raw)) {
            $data = array_replace($data, $raw);
            if (!is_array($data['fails'])) $data['fails'] = [];
        }
    }
    // 清理过期记录（5 分钟内）
    $data['fails'] = array_values(array_filter($data['fails'], function ($t) use ($now) {
        return ($now - $t) < 300;
    }));
    $data['fails'][] = $now;
    if (count($data['fails']) >= LOGIN_THROTTLE_IP_MAX) {
        $data['lock_until'] = $now + LOGIN_THROTTLE_IP_LOCK;
    }
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
}

function clearLoginFail(): void {
    $_SESSION['login_fail_count'] = 0;
    unset($_SESSION['login_lock_until']);
    $file = loginThrottleIpFile();
    if (is_file($file)) @unlink($file);
}

// SQLite 操作
function dbQuery($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    if ($stmt === false) {
        throw new Exception("SQL准备失败: " . $db->lastErrorMsg());
    }
    foreach ($params as $k => $v) {
        $stmt->bindValue($k + 1, $v);
    }
    $result = $stmt->execute();
    if ($result === false) {
        throw new Exception("SQL执行失败: " . $db->lastErrorMsg());
    }
    return $result;
}

function dbFetchAll($db, $sql, $params = []) {
    $res = dbQuery($db, $sql, $params);
    $list = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $list[] = $row;
    }
    return $list;
}

function dbFetchOne($db, $sql, $params = []) {
    $arr = dbFetchAll($db, $sql, $params);
    return $arr[0] ?? null;
}

// ==================== CSRF验证（P0-A1：白名单收窄） ====================
// 登录前拿不到 token 的接口（login/register/captcha/check_session）+ 读接口（题库浏览/考试列表/刷题列表/资料列表）+ 首装专用 create_admin（内部再加首装保护）
$csrfBypass = [
    'login',
    'register',
    'captcha',
    'check_session',
    'create_admin',           // 首装专用；内部会判断，若已有管理员则强制登录管理员 + CSRF
    'question_stats',
    'get_subjects',
    'list_questions',
    'get_question',
    // B：考试/刷题读接口（P1 新增时自动免 CSRF，提前占位避免破坏顺序）
    'paper_list',
    'paper_get',
    'attempt_list_mine',
    'attempt_result',
    'practice_subjects',
    'practice_result',
    // C：资料读接口
    'material_list',
    'material_download',      // GET 直链，无法带 CSRF，另有一次性 token 鉴权
    // 首页公开概览（未登录即可调用）
    'public_overview',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($action, $csrfBypass)) {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            jsonOut(false, "CSRF验证失败");
        }
    }
}

// ===================== API处理 =====================
if ($action) {
    try {
        switch ($action) {
            // ==================== 用户注册 ====================
            case 'register':
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));

                if (empty($username) || empty($email) || empty($password) || empty($captcha)) {
                    jsonOut(false, "请完整填写注册信息+验证码");
                }
                
                if (!isset($_SESSION['captcha_code']) || $_SESSION['captcha_code'] !== $captcha) {
                    unset($_SESSION['captcha_code']);
                    jsonOut(false, "验证码错误，请刷新验证码");
                }
                unset($_SESSION['captcha_code']);

                if (!validateEmail($email)) {
                    jsonOut(false, "邮箱格式不正确");
                }

                $exist = dbFetchOne($db, "SELECT id FROM users WHERE username=? OR email=?", [$username, $email]);
                if ($exist) {
                    jsonOut(false, "用户名或邮箱已被注册");
                }

                // 首位用户继续自动成为管理员；后续用户注册后直接生效，无需审批。
                $adminRow = dbFetchOne($db, "SELECT COUNT(*) AS c FROM users WHERE is_admin=1");
                $isFirstAdmin = (int)($adminRow['c'] ?? 0) === 0;

                if ($isFirstAdmin) {
                    dbQuery($db, "INSERT INTO users(username, email, password, is_approved, is_admin, is_active, created_at) VALUES(?, ?, ?, 1, 1, 1, CURRENT_TIMESTAMP)",
                        [$username, $email, $password]);
                    $newId = $db->lastInsertRowID();
                    log_admin_action('first_install_auto_admin', (int)$newId);
                    jsonOut(true, "注册成功：您已成为首位管理员，可直接登录");
                }

                dbQuery($db, "INSERT INTO users(username, email, password, is_approved, is_admin, is_active) VALUES(?, ?, ?, 1, 0, 1)",
                    [$username, $email, $password]);
                jsonOut(true, "注册成功，可直接登录");
                break;

            // ==================== 用户登录（P0-A3+A4：登录限流+会话再生） ====================
            case 'login':
                // 先限流（统一错误信息，不枚举）
                if (!checkLoginThrottle()) {
                    recordLoginFail(); // 记一次，避免重置计时
                    jsonOut(false, "用户名或密码错误或登录过于频繁，请稍后再试");
                }
                $username = sanitizeInput($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    recordLoginFail();
                    jsonOut(false, "用户名或密码错误或登录过于频繁，请稍后再试");
                }
                
                $u = dbFetchOne($db, "SELECT * FROM users WHERE username=?", [$username]);
                
                if (!$u || $u['password'] !== $password) {
                    recordLoginFail();
                    jsonOut(false, "用户名或密码错误或登录过于频繁，请稍后再试");
                }
                
                if (!$u['is_active']) {
                    recordLoginFail();
                    jsonOut(false, "用户名或密码错误或登录过于频繁，请稍后再试");
                }

                // 注册审批已取消：历史账号即使保存为未审批状态，也不再阻止登录或自动升权。
                // 账号是否可用仅由 is_active 控制，管理员身份仅由 is_admin 控制。

                // 登录成功：会话再生 + 清限流 + 写 session
                session_regenerate_id(true);
                clearLoginFail();

                $_SESSION['user_id'] = $u['id'];
                $_SESSION['username'] = $u['username'];
                $_SESSION['is_admin'] = (bool)$u['is_admin'];
                $_SESSION['is_approved'] = (bool)$u['is_approved'];
                
                dbQuery($db, "UPDATE users SET last_login=CURRENT_TIMESTAMP WHERE id=?", [$u['id']]);

                jsonOut(true, "登录成功", [
                    'user' => [
                        'id' => $u['id'],
                        'username' => $u['username'],
                        'email' => $u['email'],
                        'is_admin' => (bool)$u['is_admin'],
                        'is_approved' => (bool)$u['is_approved']
                    ]
                ]);
                break;

            // ==================== 退出登录 ====================
            case 'logout':
                $_SESSION = [];
                session_destroy();
                jsonOut(true, "已退出登录");
                break;

            // ==================== 首页公开概览（未登录即可访问，访客展示平台信息） ====================
            case 'public_overview':
                $typeLabels = [
                    'single'   => '单选题',
                    'multiple' => '多选题',
                    'fill'     => '填空题',
                    'essay'    => '简答题'
                ];
                // 1) 统计
                $question_count = (int)(dbFetchOne($db, "SELECT COUNT(*) AS c FROM questions")['c'] ?? 0);
                $row = dbFetchOne($db, "SELECT COUNT(DISTINCT subject) AS c FROM questions");
                $subject_count = (int)($row['c'] ?? 0);
                $row = dbFetchOne($db, "SELECT COUNT(*) AS c FROM papers WHERE is_published=1");
                $published_paper_count = (int)($row['c'] ?? 0);
                $row = dbFetchOne($db, "SELECT COUNT(*) AS c FROM materials");
                $material_count = (int)($row['c'] ?? 0);

                // 2) 题型分布
                $rows = dbFetchAll($db, "SELECT question_type, COUNT(*) AS c FROM questions GROUP BY question_type ORDER BY c DESC");
                $types = [];
                foreach ($rows as $r) {
                    $types[] = [
                        'key'   => $r['question_type'],
                        'label' => $typeLabels[$r['question_type']] ?? $r['question_type'],
                        'count' => (int)$r['c']
                    ];
                }
                // 补全没出现的题型为 0
                foreach ($typeLabels as $k => $l) {
                    $found = false;
                    foreach ($types as $t) { if ($t['key'] === $k) { $found = true; break; } }
                    if (!$found) $types[] = ['key' => $k, 'label' => $l, 'count' => 0];
                }

                // 3) 科目列表
                $rows = dbFetchAll($db, "SELECT DISTINCT subject FROM questions WHERE subject IS NOT NULL AND subject<>'' ORDER BY subject ASC");
                $subjects = array_map(function($r){ return $r['subject']; }, $rows);

                // 4) 已发布试卷（最多 6 份，按 id 倒序 = 最近发布）
                // 注意：papers 表没有 subject 列，科目从关联题目中派生（取该试卷题量最多的科目）
                $rows = dbFetchAll($db, "SELECT id, name, description, duration_minutes, created_at
                                          FROM papers WHERE is_published=1 ORDER BY id DESC LIMIT 6");
                $papers = [];
                foreach ($rows as $r) {
                    $pid = (int)$r['id'];
                    $cnt = dbFetchOne($db, "SELECT COUNT(*) AS c FROM paper_questions WHERE paper_id=?", [$pid]);
                    // 从关联题目的科目中派生（出现次数最多的 subject）
                    $subjRow = dbFetchOne($db,
                        "SELECT q.subject AS s, COUNT(*) AS c
                         FROM paper_questions pq JOIN questions q ON q.id=pq.question_id
                         WHERE pq.paper_id=? GROUP BY q.subject ORDER BY c DESC LIMIT 1",
                        [$pid]
                    );
                    $papers[] = [
                        'id' => $pid,
                        'name' => $r['name'],
                        'description' => $r['description'],
                        'subject' => $subjRow['s'] ?? null,
                        'duration_minutes' => (int)$r['duration_minutes'],
                        'question_count' => (int)($cnt['c'] ?? 0),
                        'created_at' => $r['created_at']
                    ];
                }

                // 5) 资料按科目统计（可选，丰富展示）
                $rows = dbFetchAll($db, "SELECT subject, COUNT(*) AS c FROM materials GROUP BY subject ORDER BY c DESC LIMIT 8");
                $materials = array_map(function($r){
                    return ['subject' => $r['subject'] ?: '未分类', 'count' => (int)$r['c']];
                }, $rows);

                jsonOut(true, '', [
                    'stats' => [
                        'question_count'        => $question_count,
                        'subject_count'         => $subject_count,
                        'published_paper_count' => $published_paper_count,
                        'material_count'        => $material_count
                    ],
                    'types'            => $types,
                    'subjects'         => $subjects,
                    'published_papers' => $papers,
                    'materials'        => $materials
                ]);
                break;

            // ==================== 检查会话 ====================
            case 'check_session':
                if (isLoggedIn()) {
                    $u = dbFetchOne($db, "SELECT id, username, email, is_admin, is_approved, is_active, created_at, last_login FROM users WHERE id=?", [getUid()]);
                    if ($u && (int)$u['is_active']) {
                        $_SESSION['username'] = $u['username'];
                        $_SESSION['is_admin'] = (bool)$u['is_admin'];
                        $_SESSION['is_approved'] = (bool)$u['is_approved'];
                        jsonOut(true, "", ['user' => formatUserTimestamps($u)]);
                    }
                    invalidateSession();
                }
                jsonOut(false, "未登录");
                break;

            // ==================== 用户控制台 ====================
            case 'get_dashboard':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                // 不返回 password 字段
                $user = dbFetchOne($db, "SELECT id, username, email, is_admin, is_approved, is_active, created_at, last_login FROM users WHERE id=?", [$uid]);
                $examList = dbFetchAll($db, "SELECT json_content FROM exam_data WHERE type='exam'");
                $examCount = count($examList);
                $resList = dbFetchAll($db, "SELECT json_content FROM exam_data WHERE type='result'");
                $submitCount = count($resList);
                jsonOut(true, "", [
                    'user' => formatUserTimestamps($user),
                    'stats' => [
                        'exam_count' => $examCount,
                        'submitted_count' => $submitCount
                    ],
                    'recent_results' => array_map(function ($r) {
                        return json_decode($r['json_content'], true);
                    }, $resList)
                ]);
                break;

            // ==================== 危险操作授权码 ====================
            case 'admin_danger_challenge':
                $uid = requireAdmin();
                $target = $_POST['target'] ?? '';
                if (!in_array($target, ['clear_users', 'reset_db'], true)) {
                    jsonOut(false, "危险操作类型无效");
                }
                $code = createDangerChallenge($target);
                log_admin_action('danger_challenge_' . $target, $uid);
                jsonOut(true, "授权码已生成，5分钟内有效且只能使用一次", [
                    'target' => $target,
                    'code' => $code,
                    'expires_in' => 300
                ]);
                break;

            // ==================== 管理员面板（P0-A6：返回密码+审计日志） ====================
            case 'get_admin_panel':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                log_admin_action('get_admin_panel'); // 管理员查看用户列表（含密码）必须审计
                // 明确返回 password（admin.html 需展示明文密码，作为密码找回机制）
                $users = dbFetchAll($db, "SELECT id, username, email, password, is_admin, is_approved, is_active, created_at, last_login FROM users ORDER BY id DESC");
                $users = array_map('formatUserTimestamps', $users);
                $examsRaw = dbFetchAll($db, "SELECT * FROM exam_data WHERE type='exam' ORDER BY id DESC");
                $exams = array_map(function ($i) {
                    return json_decode($i['json_content'], true);
                }, $examsRaw);
                $userTotal = dbFetchOne($db, "SELECT count(*) as c FROM users")['c'];
                $activeTotal = dbFetchOne($db, "SELECT count(*) as c FROM users WHERE is_active=1")['c'];
                // 审批流程已取消，保留 pending_count 仅为兼容旧版前端协议。
                jsonOut(true, "", [
                    'users' => $users,
                    'exams' => $exams,
                    'stats' => [
                        'user_count' => $userTotal,
                        'active_count' => $activeTotal,
                        'pending_count' => 0
                    ]
                ]);
                break;

            // ==================== 兼容旧客户端：注册审批已取消 ====================
            case 'approve_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "UPDATE users SET is_approved=1 WHERE id=?", [$uid]);
                jsonOut(true, "注册后无需审批，账号已保持可用");
                break;

            case 'reject_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                jsonOut(false, "注册审批已取消，请使用启用/禁用管理账号");
                break;

            // ==================== 启用/禁用用户 ====================
            case 'toggle_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "UPDATE users SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?", [$uid]);
                jsonOut(true, "状态已更新");
                break;

            // ==================== 创建管理员（P0-A1：首装保护） ====================
            case 'create_admin':
                // 首装保护：若已存在任何管理员，则必须是已登录管理员 + CSRF 手动校验
                $has_admin = (bool)dbFetchOne($db, "SELECT 1 FROM users WHERE is_admin=1 LIMIT 1");
                if ($has_admin) {
                    if (!isLoggedIn() || !isAdmin()) jsonOut(false, "创建失败");
                    // 手动 CSRF（create_admin 在 bypass 列表，但此时必须强制）
                    $csrf_token = $_POST['csrf_token'] ?? '';
                    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
                        jsonOut(false, "创建失败");
                    }
                }
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($email) || empty($password)) {
                    jsonOut(false, "创建失败");
                }
                if (!validateEmail($email)) {
                    jsonOut(false, "创建失败");
                }
                if (strlen($password) < 4) {
                    jsonOut(false, "创建失败");
                }
                
                $exist = dbFetchOne($db, "SELECT id FROM users WHERE username=? OR email=?", [$username, $email]);
                if ($exist) {
                    jsonOut(false, "创建失败");
                }
                
                dbQuery($db, "INSERT INTO users(username, email, password, is_admin, is_approved, is_active) VALUES(?, ?, ?, 1, 1, 1)", 
                    [$username, $email, $password]);
                jsonOut(true, "管理员创建成功");
                break;

            // ==================== 设为管理员 ====================
            case 'make_admin':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "UPDATE users SET is_admin=1 WHERE id=?", [$uid]);
                jsonOut(true, "已设为管理员");
                break;

            // ==================== 删除用户 ====================
            case 'delete_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "DELETE FROM users WHERE id=?", [$uid]);
                jsonOut(true, "用户已删除");
                break;

            // ==================== 清空所有用户 ====================
            case 'clear_users':
                $uid = requireAdmin();
                $code = (string)($_POST['authorization_code'] ?? '');
                $confirmed = ($_POST['final_confirm'] ?? '') === 'DELETE ALL USERS';
                if (!consumeDangerChallenge('clear_users', $code, $confirmed)) {
                    log_admin_action('danger_clear_users_rejected', $uid);
                    jsonOut(false, "授权码错误、已过期或最终确认无效，未执行删除");
                }
                try {
                    $db->exec('BEGIN IMMEDIATE');
                    dbQuery($db, "DELETE FROM users");
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    log_admin_action('danger_clear_users_failed', $uid);
                    jsonOut(false, "删除失败：" . $e->getMessage());
                }
                log_admin_action('danger_clear_users_success', $uid);
                invalidateSession();
                jsonOut(true, "所有用户已永久删除，请重新登录");
                break;

            // ==================== 重置数据库 ====================
            case 'reset_db':
                $uid = requireAdmin();
                $code = (string)($_POST['authorization_code'] ?? '');
                $confirmed = ($_POST['final_confirm'] ?? '') === 'RESET DATABASE';
                if (!consumeDangerChallenge('reset_db', $code, $confirmed)) {
                    log_admin_action('danger_reset_db_rejected', $uid);
                    jsonOut(false, "授权码错误、已过期或最终确认无效，未执行重置");
                }
                $backupDir = __DIR__ . '/../data/backups';
                if (!is_dir($backupDir)) @mkdir($backupDir, 0750, true);
                $backupPath = $backupDir . '/exam_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.db';
                if (!copy($dbFile, $backupPath)) {
                    log_admin_action('danger_reset_db_backup_failed', $uid);
                    jsonOut(false, "数据库备份失败，未执行重置");
                }
                try {
                    $db->exec('BEGIN IMMEDIATE');
                    foreach (['users', 'exam_data', 'questions', 'papers', 'paper_questions', 'exam_attempts', 'exam_answers', 'materials'] as $table) {
                        $db->exec("DELETE FROM $table");
                    }
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    log_admin_action('danger_reset_db_failed', $uid);
                    jsonOut(false, "重置失败：" . $e->getMessage());
                }
                $matDir = __DIR__ . '/../materials';
                if (is_dir($matDir)) {
                    foreach (glob($matDir . '/mat_*') ?: [] as $file) {
                        if (is_file($file)) @unlink($file);
                    }
                }
                log_admin_action('danger_reset_db_success', $uid);
                invalidateSession();
                jsonOut(true, "数据库及资料已永久清空，请重新登录");
                break;

            // =====================================================
            // ============== 题目管理模块 =========================
            // =====================================================

            // ---------- 题库统计 ----------
            case 'question_stats':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $total = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM questions")['c'];
                $bySubject = dbFetchAll($db, "SELECT subject, COUNT(*) AS c FROM questions GROUP BY subject ORDER BY c DESC");
                $byType = dbFetchAll($db, "SELECT question_type, COUNT(*) AS c FROM questions GROUP BY question_type");
                $typeLabels = [
                    'single' => '单选题', 'multiple' => '多选题',
                    'judge' => '判断题', 'fill' => '填空题'
                ];
                $typeData = [];
                foreach ($byType as $r) {
                    $typeData[] = [
                        'type' => $r['question_type'],
                        'label' => $typeLabels[$r['question_type']] ?? $r['question_type'],
                        'count' => (int)$r['c']
                    ];
                }
                $subjData = [];
                foreach ($bySubject as $r) {
                    $subjData[] = ['subject' => $r['subject'], 'count' => (int)$r['c']];
                }
                jsonOut(true, "", [
                    'total' => $total,
                    'by_subject' => $subjData,
                    'by_type' => $typeData
                ]);
                break;

            // ---------- 科目列表 ----------
            case 'get_subjects':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $level = validateEducationLevel($_POST['education_level'] ?? $_GET['education_level'] ?? '', '');
                $sql = $level !== '' ? "SELECT DISTINCT subject FROM questions WHERE education_level=? ORDER BY subject ASC" : "SELECT DISTINCT subject FROM questions ORDER BY subject ASC";
                $rows = dbFetchAll($db, $sql, $level !== '' ? [$level] : []);
                $subjects = array_map(function($r){ return $r['subject']; }, $rows);
                jsonOut(true, "", ['subjects' => $subjects]);
                break;

            // ---------- 题目列表（分页+筛选） ----------
            case 'list_questions':
                if (!isLoggedIn()) jsonOut(false, "请先登录");

                $subject   = isset($_GET['subject'])   ? sanitizeInput($_GET['subject'])   : (isset($_POST['subject'])   ? sanitizeInput($_POST['subject'])   : '');
                $qtype     = isset($_GET['qtype'])     ? sanitizeInput($_GET['qtype'])     : (isset($_POST['qtype'])     ? sanitizeInput($_POST['qtype'])     : '');
                $keyword   = isset($_GET['keyword'])   ? sanitizeInput($_GET['keyword'])   : (isset($_POST['keyword'])   ? sanitizeInput($_POST['keyword'])   : '');
                $educationLevel = validateEducationLevel($_GET['education_level'] ?? $_POST['education_level'] ?? '', '');
                // 仅在“题目管理”范围（scope=manage_all）要求管理员必须选定学段；
                // 浏览 Tab 中管理员也允许“全部”跨学段查看，避免下拉“全部”直接报错。
                $scope = isset($_GET['scope']) ? (string)$_GET['scope'] : (isset($_POST['scope']) ? (string)$_POST['scope'] : '');
                
                $category  = validateQuestionCategory($_GET['category'] ?? $_POST['category'] ?? '', '');
                $page      = max(1, (int)($_GET['page']   ?? $_POST['page']   ?? 1));
                $page_size = max(1, min(100, (int)($_GET['page_size'] ?? $_POST['page_size'] ?? 10)));

                $where = [];
                $params = [];
                if ($subject !== '') { $where[] = 'subject = ?'; $params[] = $subject; }
                if ($qtype !== '')   { $where[] = 'question_type = ?'; $params[] = $qtype; }
                if ($educationLevel !== '') { $where[] = 'education_level = ?'; $params[] = $educationLevel; }
                if ($category !== '') { $where[] = 'category = ?'; $params[] = $category; }
                if ($keyword !== '')  { $where[] = '(content LIKE ? OR explanation LIKE ?)'; $params[] = "%$keyword%"; $params[] = "%$keyword%"; }
                $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

                $total = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM questions $whereSql", $params)['c'];
                $offset = ($page - 1) * $page_size;
                $rows = dbFetchAll($db, "SELECT id, subject, question_type, category, education_level, content, options, correct_answer, explanation, difficulty, points, created_at, is_html FROM questions $whereSql ORDER BY id DESC LIMIT ? OFFSET ?", array_merge($params, [$page_size, $offset]));

                // 反序列化 options
                $items = array_map(function($r){
                    $r['options']     = $r['options'] ? json_decode($r['options'], true) : null;
                    $r['difficulty']  = (int)$r['difficulty'];
                    $r['points']      = (float)$r['points'];
                    $r['is_html']     = !empty($r['is_html']) ? 1 : 0;
                    if (!isAdmin()) {
                        unset($r['correct_answer'], $r['explanation']);
                    }
                    return $r;
                }, $rows);

                jsonOut(true, "", [
                    'items' => $items,
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $page_size,
                    'total_pages' => (int)ceil($total / $page_size)
                ]);
                break;

            // ---------- 题目详情 ----------
            case 'get_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $qid = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
                if ($qid <= 0) jsonOut(false, "题目ID无效");
                $row = dbFetchOne($db, "SELECT * FROM questions WHERE id=?", [$qid]);
                if (!$row) jsonOut(false, "题目不存在");
                $row['options']    = $row['options'] ? json_decode($row['options'], true) : null;
                $row['difficulty'] = (int)$row['difficulty'];
                $row['points']     = (float)$row['points'];
                $row['is_html']    = !empty($row['is_html']) ? 1 : 0;
                if (!isAdmin()) {
                    unset($row['correct_answer'], $row['explanation']);
                }
                jsonOut(true, "", ['question' => $row]);
                break;

            // ---------- 新增题目 ----------
            case 'add_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();

                $subject     = normalizeSubject(sanitizeInput($_POST['subject'] ?? ''));
                $qtype       = sanitizeInput($_POST['question_type'] ?? '');
                $content     = sanitizeInput($_POST['content'] ?? '');
                $optionsRaw  = $_POST['options'] ?? '[]';
                $answer      = sanitizeInput($_POST['correct_answer'] ?? '');
                $explanation = sanitizeInput($_POST['explanation'] ?? '');
                $difficulty  = (int)($_POST['difficulty'] ?? 1);
                $points      = (float)($_POST['points'] ?? 1.0);
                $rawEducationLevel = trim((string)($_POST['education_level'] ?? ''));
                if ($rawEducationLevel === '') jsonOut(false, '请选择所属学段');
                $educationLevel = validateEducationLevel($rawEducationLevel);
                $category = validateQuestionCategory($_POST['category'] ?? $qtype, $qtype);

                // 校验
                $validTypes = ['single', 'multiple', 'judge', 'fill', 'multi_fill', 'short'];
                if ($subject === '' || $qtype === '' || $content === '' || $answer === '') {
                    jsonOut(false, "科目、题型、题干、答案均不能为空");
                }
                if (!in_array($qtype, $validTypes, true)) {
                    jsonOut(false, "题型不合法，仅支持: " . implode('/', $validTypes));
                }
                if ($difficulty < 1 || $difficulty > 5) $difficulty = 1;
                if ($points < 0) $points = 0;

                // 选项校验：非填空题必须至少2个选项
                $options = json_decode($optionsRaw, true);
                if (!is_array($options)) {
                    jsonOut(false, "选项数据格式错误，需为JSON数组");
                }
                if ($qtype !== 'fill') {
                    $optFiltered = array_values(array_filter($options, function($o){
                        return is_string($o) && trim($o) !== '';
                    }));
                    if (count($optFiltered) < 2) {
                        jsonOut(false, "选择题/判断题至少需要2个非空选项");
                    }
                    $options = $optFiltered;
                } else {
                    // 填空题无选项
                    $options = null;
                }

                $optionsJson = $options ? json_encode($options, JSON_UNESCAPED_UNICODE) : null;
                dbQuery($db, "INSERT INTO questions (subject, question_type, category, education_level, content, options, correct_answer, explanation, difficulty, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$subject, $qtype, $category, $educationLevel, $content, $optionsJson, $answer, $explanation, $difficulty, $points]);
                $newId = (int)$db->lastInsertRowID();
                jsonOut(true, "题目已保存", ['id' => $newId]);
                break;

            // ---------- 修改题目 ----------
            case 'update_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();

                $qid = (int)($_POST['id'] ?? 0);
                if ($qid <= 0) jsonOut(false, "题目ID无效");
                $existing = dbFetchOne($db, "SELECT id FROM questions WHERE id=?", [$qid]);
                if (!$existing) jsonOut(false, "题目不存在");

                $subject     = normalizeSubject(sanitizeInput($_POST['subject'] ?? ''));
                $qtype       = sanitizeInput($_POST['question_type'] ?? '');
                $content     = sanitizeInput($_POST['content'] ?? '');
                $optionsRaw  = $_POST['options'] ?? '[]';
                $answer      = sanitizeInput($_POST['correct_answer'] ?? '');
                $explanation = sanitizeInput($_POST['explanation'] ?? '');
                $difficulty  = (int)($_POST['difficulty'] ?? 1);
                $points      = (float)($_POST['points'] ?? 1.0);
                $rawEducationLevel = trim((string)($_POST['education_level'] ?? ''));
                if ($rawEducationLevel === '') jsonOut(false, '请选择所属学段');
                $educationLevel = validateEducationLevel($rawEducationLevel);
                $category = validateQuestionCategory($_POST['category'] ?? $qtype, $qtype);

                $validTypes = ['single', 'multiple', 'judge', 'fill', 'multi_fill', 'short'];
                if ($subject === '' || $qtype === '' || $content === '' || $answer === '') {
                    jsonOut(false, "科目、题型、题干、答案均不能为空");
                }
                if (!in_array($qtype, $validTypes, true)) {
                    jsonOut(false, "题型不合法");
                }
                if ($difficulty < 1 || $difficulty > 5) $difficulty = 1;
                if ($points < 0) $points = 0;

                $options = json_decode($optionsRaw, true);
                if (!is_array($options)) {
                    jsonOut(false, "选项数据格式错误，需为JSON数组");
                }
                if ($qtype !== 'fill') {
                    $optFiltered = array_values(array_filter($options, function($o){
                        return is_string($o) && trim($o) !== '';
                    }));
                    if (count($optFiltered) < 2) {
                        jsonOut(false, "选择题/判断题至少需要2个非空选项");
                    }
                    $options = $optFiltered;
                } else {
                    $options = null;
                }

                $optionsJson = $options ? json_encode($options, JSON_UNESCAPED_UNICODE) : null;
                dbQuery($db, "UPDATE questions SET subject=?, question_type=?, category=?, education_level=?, content=?, options=?, correct_answer=?, explanation=?, difficulty=?, points=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                    [$subject, $qtype, $category, $educationLevel, $content, $optionsJson, $answer, $explanation, $difficulty, $points, $qid]);
                jsonOut(true, "题目已更新");
                break;

            // ---------- 删除题目 ----------
            case 'delete_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $qid = (int)($_POST['id'] ?? 0);
                if ($qid <= 0) jsonOut(false, "题目ID无效");
                if (!dbFetchOne($db, "SELECT id FROM questions WHERE id=?", [$qid])) jsonOut(false, "题目不存在");
                try {
                    $db->exec('BEGIN IMMEDIATE');
                    dbQuery($db, "DELETE FROM paper_questions WHERE question_id=?", [$qid]);
                    dbQuery($db, "DELETE FROM exam_answers WHERE question_id=?", [$qid]);
                    dbQuery($db, "DELETE FROM exam_attempts WHERE id NOT IN (SELECT DISTINCT attempt_id FROM exam_answers)", []);
                    dbQuery($db, "DELETE FROM questions WHERE id=?", [$qid]);
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "删除失败：" . $e->getMessage());
                }
                jsonOut(true, "题目及关联记录已删除");
                break;

            // ---------- 批量移动学段 ----------
            case 'bulk_move_questions':
                requireAdmin();
                $ids = json_decode($_POST['ids'] ?? '[]', true);
                if (!is_array($ids) || count($ids) < 1 || count($ids) > 500) jsonOut(false, '请选择1-500道题目');
                $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
                if (!$ids) jsonOut(false, '题目ID无效');
                $level = validateEducationLevel($_POST['education_level'] ?? '', '');
                $in = implode(',', array_fill(0, count($ids), '?'));
                $params = array_merge([$level], $ids);
                dbQuery($db, "UPDATE questions SET education_level=?, updated_at=CURRENT_TIMESTAMP WHERE id IN ($in)", $params);
                jsonOut(true, '批量移动学段成功', ['updated' => $db->changes()]);
                break;

            // ---------- 批量修改分类 ----------
            case 'bulk_update_question_category':
                requireAdmin();
                $ids = json_decode($_POST['ids'] ?? '[]', true);
                if (!is_array($ids) || count($ids) < 1 || count($ids) > 500) jsonOut(false, '请选择1-500道题目');
                $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
                if (!$ids) jsonOut(false, '题目ID无效');
                $category = validateQuestionCategory($_POST['category'] ?? '', '');
                $in = implode(',', array_fill(0, count($ids), '?'));
                dbQuery($db, "UPDATE questions SET category=?, updated_at=CURRENT_TIMESTAMP WHERE id IN ($in)", array_merge([$category], $ids));
                jsonOut(true, '批量修改分类成功', ['updated' => $db->changes()]);
                break;

            // ---------- 批量删除题目及其关联记录 ----------
            case 'bulk_delete_questions':
                requireAdmin();
                $ids = json_decode($_POST['ids'] ?? '[]', true);
                if (!is_array($ids) || count($ids) < 1 || count($ids) > 500) jsonOut(false, '请选择1-500道题目');
                $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
                if (!$ids) jsonOut(false, '题目ID无效');
                $in = implode(',', array_fill(0, count($ids), '?'));
                try {
                    $db->exec('BEGIN IMMEDIATE');
                    dbQuery($db, "DELETE FROM paper_questions WHERE question_id IN ($in)", $ids);
                    dbQuery($db, "DELETE FROM exam_answers WHERE question_id IN ($in)", $ids);
                    dbQuery($db, "DELETE FROM exam_attempts WHERE id NOT IN (SELECT DISTINCT attempt_id FROM exam_answers)", []);
                    dbQuery($db, "DELETE FROM questions WHERE id IN ($in)", $ids);
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, '批量删除失败：' . $e->getMessage());
                }
                jsonOut(true, '题目及关联记录已批量删除', ['deleted' => count($ids)]);
                break;

            // ---------- 批量导入（兼容 import_exam.json 结构） ----------
            case 'import_questions_json':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();

                $rawJson = $_POST['json_data'] ?? '';
                if (trim($rawJson) === '') jsonOut(false, "请提供JSON数据");
                $data = json_decode($rawJson, true);
                if (!is_array($data)) jsonOut(false, "JSON解析失败: " . json_last_error_msg());

                $subjectOverride = normalizeSubject(sanitizeInput($_POST['subject'] ?? ''));
                $rawEducationLevel = trim((string)($_POST['education_level'] ?? ''));
                // OCR 流水线适配：优先用 JSON 中的 education_level，其次用用户选择
                if ($rawEducationLevel === '') {
                    if (!empty($data['education_level'])) {
                        $rawEducationLevel = trim((string)$data['education_level']);
                    }
                }
                if ($rawEducationLevel === '') {
                    // 从 questions 数组里检测
                    if (isset($data['questions']) && is_array($data['questions'])) {
                        foreach ($data['questions'] as $q) {
                            if (!empty($q['education_level'])) {
                                $rawEducationLevel = trim((string)$q['education_level']);
                                break;
                            }
                        }
                    }
                }
                if ($rawEducationLevel === '') $rawEducationLevel = 'junior';
                $educationLevel = validateEducationLevel($rawEducationLevel);
                $categoryOverride = validateQuestionCategory($_POST['category'] ?? '', '');

                // 自动识别科目
                $subjectMap = [
                    '生物' => '生物', '化学' => '化学', '物理' => '物理',
                    '数学' => '数学', '英语' => '英语', '语文' => '语文',
                    '历史' => '历史', '地理' => '地理', '政治' => '政治',
                    '信息技术' => '信息技术', '计算机' => '信息技术',
                    '道德与法治' => '道德与法治', '道法' => '道德与法治'
                ];
                $detectedSubject = $subjectOverride;
                if ($detectedSubject === '') {
                    $title = $data['试卷名称'] ?? $data['title'] ?? $data['paper_name'] ?? $data['match_key'] ?? '';
                    foreach ($subjectMap as $k => $v) {
                        if (str_contains($title, $k)) { $detectedSubject = $v; break; }
                    }
                    // 也从 questions 数组的 subject 字段检测
                    if ($detectedSubject === '' && isset($data['subject'])) {
                        $detectedSubject = trim($data['subject']);
                    }
                    if ($detectedSubject === '') $detectedSubject = '综合';
                }

                $questions = [];
                importExtractQuestions($data, $detectedSubject, $questions);

                // OCR 流水线适配：提取 match_key 并归一化，用于题目-答案精准匹配
                // 归一化：去掉"答案/试卷/真题/原卷完整版"等后缀，使题目和答案文件的 match_key 一致
                $rawMatchKey = '';
                if (!empty($data['match_key'])) {
                    $rawMatchKey = trim((string)$data['match_key']);
                } elseif (!empty($data['paper_name'])) {
                    $rawMatchKey = trim((string)$data['paper_name']);
                }
                $matchKey = generateMatchKey($rawMatchKey !== '' ? $rawMatchKey : 'import_' . count($questions), $rawMatchKey);

                foreach ($questions as &$importQuestion) {
                    $importQuestion['education_level'] = $educationLevel;
                    $importQuestion['match_key'] = $matchKey;
                    $importQuestion['category'] = $categoryOverride !== ''
                        ? $categoryOverride
                        : validateQuestionCategory($importQuestion['category'] ?? ($importQuestion['question_type'] ?? 'single'), 'single');
                }
                unset($importQuestion);

                $stats = ['inserted' => 0, 'skipped' => 0, 'errors' => 0, 'updated' => 0, 'answer_matched' => 0, 'skip_no_match' => 0, 'skip_dup' => 0];
                foreach ($questions as $q) {
                    try {
                        $pcvlQid = (string)($q['_pcvl_qid'] ?? '');
                        $newAns = $q['correct_answer'] ?? '';
                        $newExp = $q['explanation'] ?? '';
                        $hasAnswer = ($newAns !== '' || $newExp);
                        $qMatchKey = $q['match_key'] ?? '';

                        // OCR 流水线适配：答案文件按 match_key + source_qid 精准匹配已有题目
                        $isAnswerOnly = trim($q['content'] ?? '') === '' || ($pcvlQid !== '' && mb_strlen(trim($q['content'] ?? '')) <= 30 && $hasAnswer);
                        if ($isAnswerOnly) {
                            if ($hasAnswer && $pcvlQid !== '') {
                                // 优先用 match_key + source_qid 精准匹配（OCR 流水线新方案）
                                $match = null;
                                if ($qMatchKey !== '') {
                                    $match = dbFetchOne($db,
                                        "SELECT id, correct_answer, explanation FROM questions
                                         WHERE match_key=? AND source_qid=? LIMIT 1",
                                        [$qMatchKey, (string)$pcvlQid]);
                                }
                                // 兜底1：用 source_qid 跨 match_key 匹配（同科目同学段同题号）
                                if (!$match && $pcvlQid !== '') {
                                    $match = dbFetchOne($db,
                                        "SELECT id, correct_answer, explanation, match_key FROM questions
                                         WHERE subject=? AND education_level=? AND source_qid=?
                                         LIMIT 1",
                                        [$q['subject'], $q['education_level'], (string)$pcvlQid]);
                                }
                                // 兜底2：按顺序匹配同科目同学段无答案的题（兼容旧逻辑）
                                if (!$match) {
                                    $match = dbFetchOne($db,
                                        "SELECT id, correct_answer, explanation FROM questions
                                         WHERE subject=? AND education_level=?
                                         AND (correct_answer IS NULL OR trim(correct_answer)='')
                                         ORDER BY id ASC LIMIT 1",
                                        [$q['subject'], $q['education_level']]);
                                }
                                if ($match) {
                                    $updParts = []; $updArgs = [];
                                    if ($newAns !== '' && trim($match['correct_answer'] ?? '') === '') {
                                        $updParts[] = 'correct_answer = ?'; $updArgs[] = $newAns;
                                    }
                                    if ($newExp && trim($match['explanation'] ?? '') === '') {
                                        $updParts[] = 'explanation = ?'; $updArgs[] = $newExp;
                                    }
                                    if (!empty($updParts)) {
                                        $updArgs[] = (int)$match['id'];
                                        dbQuery($db, "UPDATE questions SET " . implode(', ', $updParts) . " WHERE id = ?", $updArgs);
                                        $stats['updated']++;
                                        $stats['answer_matched']++;
                                    } else {
                                        $stats['skipped']++;
                                        $stats['skip_dup']++;
                                    }
                                } else {
                                    $stats['skipped']++;
                                    $stats['skip_no_match']++;
                                }
                            } else {
                                $stats['skipped']++;
                                $stats['skip_no_match']++;
                            }
                            continue;
                        }
                        // 查重：相同题干 + 科目 + 学段
                        $dup = dbFetchOne($db, "SELECT id, correct_answer, explanation FROM questions WHERE content=? AND subject=? AND education_level=? LIMIT 1",
                            [$q['content'], $q['subject'], $q['education_level']]);
                        if ($dup) {
                            // 如果新导入的数据有答案而旧数据没有，更新答案
                            $needUpdate = false;
                            $newAns = $q['correct_answer'] ?? '';
                            $newExp = $q['explanation'] ?? '';
                            if ($newAns !== '' && trim($dup['correct_answer'] ?? '') === '') {
                                $needUpdate = true;
                            }
                            if ($newExp && trim($dup['explanation'] ?? '') === '') {
                                $needUpdate = true;
                            }
                            if ($needUpdate) {
                                $updParts = [];
                                $updArgs = [];
                                if ($newAns !== '' && trim($dup['correct_answer'] ?? '') === '') {
                                    $updParts[] = 'correct_answer = ?';
                                    $updArgs[] = $newAns;
                                }
                                if ($newExp && trim($dup['explanation'] ?? '') === '') {
                                    $updParts[] = 'explanation = ?';
                                    $updArgs[] = $newExp;
                                }
                                if (!empty($updParts)) {
                                    $updArgs[] = (int)$dup['id'];
                                    dbQuery($db, "UPDATE questions SET " . implode(', ', $updParts) . " WHERE id = ?", $updArgs);
                                    $stats['updated']++;
                                } else {
                                    $stats['skipped']++;
                                }
                            } else {
                                $stats['skipped']++;
                            }
                            continue;
                        }
                        $optionsJson = !empty($q['options']) ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null;
                        $isHtml = !empty($q['is_html']) ? 1 : 0;
                        $sourceQid = (string)($q['_pcvl_qid'] ?? '');
                        $qMk = $q['match_key'] ?? '';
                        dbQuery($db, "INSERT INTO questions (subject, question_type, category, education_level, content, options, correct_answer, explanation, difficulty, points, is_html, match_key, source_qid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$q['subject'], $q['question_type'], $q['category'], $q['education_level'], $q['content'], $optionsJson, $q['correct_answer'], $q['explanation'] ?? null, $q['difficulty'], $q['points'], $isHtml, $qMk, $sourceQid]);
                        $stats['inserted']++;
                    } catch (Exception $ex) {
                        $stats['errors']++;
                    }
                }
                $skipDetail = '';
                if (!empty($stats['skip_no_match']) && $stats['skip_no_match'] > 0) {
                    $skipDetail .= "（{$stats['skip_no_match']}条答案未匹配到题目，请先导入题目文件）";
                }
                if (!empty($stats['skip_dup']) && $stats['skip_dup'] > 0) {
                    $skipDetail .= "（{$stats['skip_dup']}条已有答案，重复跳过）";
                }
                $msg = "导入完成: 新增 {$stats['inserted']} / 更新 {$stats['updated']} / 跳过 {$stats['skipped']} / 失败 {$stats['errors']}{$skipDetail}";
                jsonOut(true, $msg, [
                    'stats' => $stats,
                    'subject' => $detectedSubject,
                    'parsed_count' => count($questions),
                    'match_key' => $matchKey
                ]);
                break;

            // ============== P1-B1：组卷（管理员） =================
            // =====================================================

            // ---------- 试卷列表 ----------
            case 'paper_list':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (isAdmin()) {
                    $rows = dbFetchAll($db, "SELECT * FROM papers ORDER BY id DESC");
                } else {
                    $rows = dbFetchAll($db, "SELECT * FROM papers WHERE is_published=1 ORDER BY id DESC");
                }
                foreach ($rows as &$r) {
                    $r['duration_minutes'] = (int)$r['duration_minutes'];
                    $r['is_published'] = (int)$r['is_published'];
                    $r['created_by'] = $r['created_by'] ? (int)$r['created_by'] : null;
                    $cnt = dbFetchOne($db, "SELECT COUNT(*) AS c FROM paper_questions WHERE paper_id=?", [(int)$r['id']]);
                    $r['question_count'] = (int)($cnt['c'] ?? 0);
                }
                jsonOut(true, "", ['papers' => $rows]);
                break;

            // ---------- 试卷详情（含关联题目） ----------
            case 'paper_get':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $pid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
                if ($pid <= 0) jsonOut(false, "试卷ID无效");
                $paper = dbFetchOne($db, "SELECT * FROM papers WHERE id=?", [$pid]);
                if (!$paper) jsonOut(false, "试卷不存在");

                // 学生只能看已发布的
                if (!isAdmin() && !(int)$paper['is_published']) {
                    jsonOut(false, "试卷不存在或未发布");
                }

                $paper['duration_minutes'] = (int)$paper['duration_minutes'];
                $paper['is_published'] = (int)$paper['is_published'];

                // 关联题目（按 sort_order 排序；学生端不传 correct_answer，管理员传）
                $pq = dbFetchAll($db,
                    "SELECT pq.question_id, pq.sort_order, pq.points
                     FROM paper_questions pq
                     WHERE pq.paper_id=?
                     ORDER BY pq.sort_order ASC, pq.id ASC",
                    [$pid]
                );
                $question_ids = array_column($pq, 'question_id');
                $questionsById = [];
                if ($question_ids) {
                    $in = implode(',', array_fill(0, count($question_ids), '?'));
                    $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in)", $question_ids);
                    foreach ($qrows as $q) {
                        $q['options']    = $q['options'] ? json_decode($q['options'], true) : null;
                        $q['difficulty'] = (int)$q['difficulty'];
                        $q['points']     = (float)$q['points'];
                        // 学生查看试卷详情时不返回 correct_answer
                        if (!isAdmin()) unset($q['correct_answer'], $q['explanation']);
                        $questionsById[(int)$q['id']] = $q;
                    }
                }
                $questions = [];
                foreach ($pq as $link) {
                    $qid = (int)$link['question_id'];
                    if (!isset($questionsById[$qid])) continue;
                    $item = $questionsById[$qid];
                    $item['paper_points'] = (float)$link['points'];
                    $item['sort_order'] = (int)$link['sort_order'];
                    $questions[] = $item;
                }
                jsonOut(true, "", ['paper' => $paper, 'questions' => $questions]);
                break;

            // ---------- 新建 / 更新试卷（同时重建关联） ----------
            case 'paper_save':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();

                $pid        = (int)($_POST['paper_id'] ?? 0);
                $name       = trim(sanitizeInput($_POST['name'] ?? ''));
                $desc       = sanitizeInput($_POST['description'] ?? '');
                $duration   = (int)($_POST['duration_minutes'] ?? 0);
                $qidsRaw    = $_POST['question_ids'] ?? ''; // JSON 数组：[{id, points}] 或 [id,id,...]

                if ($name === '') jsonOut(false, "试卷名称不能为空");
                if ($duration <= 0) jsonOut(false, "考试时长必须大于0分钟");

                $qids = json_decode($qidsRaw, true);
                if (!is_array($qids) || count($qids) === 0) jsonOut(false, "请至少选择一道题目");

                // 归一化：支持两种结构
                $normalized = [];
                $idx = 0;
                foreach ($qids as $entry) {
                    if (is_array($entry) && isset($entry['id'])) {
                        $id = (int)$entry['id'];
                        $pts = isset($entry['points']) ? (float)$entry['points'] : 1.0;
                    } else {
                        $id = (int)$entry;
                        $pts = 1.0;
                    }
                    if ($id <= 0) continue;
                    $normalized[] = ['id' => $id, 'points' => $pts, 'sort' => $idx++];
                }
                if (count($normalized) === 0) jsonOut(false, "请至少选择一道有效题目");

                // 校验题目都存在
                $idList = array_column($normalized, 'id');
                $in = implode(',', array_fill(0, count($idList), '?'));
                $existRows = dbFetchAll($db, "SELECT id FROM questions WHERE id IN ($in)", $idList);
                if (count($existRows) !== count($idList)) {
                    jsonOut(false, "部分题目不存在，请刷新后重试");
                }

                try {
                    $db->exec('BEGIN');
                    if ($pid > 0) {
                        // 更新
                        dbQuery($db,
                            "UPDATE papers SET name=?, description=?, duration_minutes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                            [$name, $desc, $duration, $pid]
                        );
                        dbQuery($db, "DELETE FROM paper_questions WHERE paper_id=?", [$pid]);
                    } else {
                        dbQuery($db,
                            "INSERT INTO papers(name, description, duration_minutes, is_published, created_by) VALUES(?,?,?,0,?)",
                            [$name, $desc, $duration, getUid()]
                        );
                        $pid = (int)$db->lastInsertRowID();
                    }
                    foreach ($normalized as $n) {
                        dbQuery($db,
                            "INSERT INTO paper_questions(paper_id, question_id, sort_order, points) VALUES(?,?,?,?)",
                            [$pid, $n['id'], $n['sort'], $n['points']]
                        );
                    }
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "保存失败：" . $e->getMessage());
                }
                jsonOut(true, "试卷已保存", ['paper_id' => $pid]);
                break;

            // ---------- 发布 / 下架 / 删除 ----------
            case 'paper_publish':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $pid = (int)($_POST['paper_id'] ?? 0);
                if ($pid <= 0) jsonOut(false, "试卷ID无效");
                // 必须至少有1道题
                $cnt = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM paper_questions WHERE paper_id=?", [$pid])['c'];
                if ($cnt <= 0) jsonOut(false, "请先添加至少一道题目再发布");
                dbQuery($db, "UPDATE papers SET is_published=1, updated_at=CURRENT_TIMESTAMP WHERE id=?", [$pid]);
                jsonOut(true, "已发布");
                break;

            case 'paper_unpublish':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $pid = (int)($_POST['paper_id'] ?? 0);
                if ($pid <= 0) jsonOut(false, "试卷ID无效");
                // 若已有已提交的 attempt，不允许下架（避免学生做了一半看不到）
                $cnt = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM exam_attempts WHERE paper_id=? AND status='submitted'", [$pid])['c'];
                if ($cnt > 0) jsonOut(false, "已有学生完成此试卷，无法下架");
                dbQuery($db, "UPDATE papers SET is_published=0, updated_at=CURRENT_TIMESTAMP WHERE id=?", [$pid]);
                jsonOut(true, "已下架");
                break;

            case 'paper_delete':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $pid = (int)($_POST['paper_id'] ?? 0);
                if ($pid <= 0) jsonOut(false, "试卷ID无效");
                $cnt = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM exam_attempts WHERE paper_id=?", [$pid])['c'];
                if ($cnt > 0) jsonOut(false, "已有学生作答记录，仅允许下架，不允许删除");
                try {
                    $db->exec('BEGIN');
                    dbQuery($db, "DELETE FROM paper_questions WHERE paper_id=?", [$pid]);
                    dbQuery($db, "DELETE FROM papers WHERE id=?", [$pid]);
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "删除失败：" . $e->getMessage());
                }
                jsonOut(true, "已删除");
                break;

            // =====================================================
            // ============== P1-B1：学生组卷答题 ===================
            // =====================================================

            // ---------- 开始考试（创建 attempt，返回题目） ----------
            case 'attempt_start_exam':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $pid = (int)($_POST['paper_id'] ?? 0);
                if ($pid <= 0) jsonOut(false, "试卷ID无效");

                $paper = dbFetchOne($db, "SELECT * FROM papers WHERE id=?", [$pid]);
                if (!$paper || !(int)$paper['is_published']) jsonOut(false, "试卷不存在或未发布");

                $links = dbFetchAll($db,
                    "SELECT question_id, sort_order, points FROM paper_questions WHERE paper_id=? ORDER BY sort_order ASC, id ASC",
                    [$pid]
                );
                if (!$links) jsonOut(false, "此试卷暂无题目");

                $duration = (int)$paper['duration_minutes'];
                try {
                    $db->exec('BEGIN');
                    dbQuery($db,
                        "INSERT INTO exam_attempts(attempt_type, paper_id, user_id, duration_minutes, status) VALUES('exam',?,?,?, 'in_progress')",
                        [$pid, $uid, $duration]
                    );
                    $aid = (int)$db->lastInsertRowID();

                    // 预插每题作答行（student_answer 先 NULL），便于 attempt_get 直接读
                    $qids = array_column($links, 'question_id');
                    foreach ($qids as $qid) {
                        dbQuery($db,
                            "INSERT INTO exam_answers(attempt_id, question_id) VALUES(?, ?)",
                            [$aid, (int)$qid]
                        );
                    }
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "开始失败：" . $e->getMessage());
                }

                $questions = examB_loadQuestionsForStudent($db, $links);
                $attempt = examB_getAttemptRow($db, $aid);
                jsonOut(true, "", [
                    'attempt_id' => $aid,
                    'paper'      => examB_sanitizePaper($paper),
                    'attempt'    => $attempt,
                    'questions'  => $questions,
                ]);
                break;

            // ---------- 获取 attempt（继续做 / 看草稿） ----------
            case 'attempt_get':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $aid = (int)($_POST['attempt_id'] ?? 0);
                if ($aid <= 0) jsonOut(false, "参数错误");
                $att = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=? AND user_id=?", [$aid, $uid]);
                if (!$att) jsonOut(false, "无权限或作答不存在");

                if ($att['status'] === 'submitted') {
                    // 已提交：走 attempt_result 逻辑
                    jsonOut(true, "", examB_buildResult($db, $aid, $uid, true));
                    break;
                }

                // 进行中：获取题目 + 本人作答草稿（不含 correct_answer）
                $paper = null;
                if ($att['paper_id']) {
                    $paper = examB_sanitizePaper(dbFetchOne($db, "SELECT * FROM papers WHERE id=?", [(int)$att['paper_id']]));
                    $links = dbFetchAll($db,
                        "SELECT question_id, sort_order, points FROM paper_questions WHERE paper_id=? ORDER BY sort_order ASC, id ASC",
                        [(int)$att['paper_id']]
                    );
                } else {
                    // practice 场景：从 exam_answers 按 id 排序读 question_id
                    $ans = dbFetchAll($db, "SELECT id, question_id FROM exam_answers WHERE attempt_id=? ORDER BY id ASC", [$aid]);
                    $links = array_map(function ($r) { return ['question_id' => (int)$r['question_id'], 'sort_order' => (int)$r['id'], 'points' => 0.0]; }, $ans);
                }
                $questions = examB_loadQuestionsForStudent($db, $links);

                // 填写作答草稿
                $ansRows = dbFetchAll($db, "SELECT question_id, student_answer FROM exam_answers WHERE attempt_id=?", [$aid]);
                $ansMap = [];
                foreach ($ansRows as $a) { $ansMap[(int)$a['question_id']] = $a['student_answer']; }
                foreach ($questions as &$q) {
                    $q['student_answer'] = $ansMap[(int)$q['id']] ?? null;
                }
                jsonOut(true, "", [
                    'attempt_id' => $aid,
                    'paper'      => $paper,
                    'attempt'    => examB_getAttemptRow($db, $aid),
                    'questions'  => $questions,
                ]);
                break;

            // ---------- 提交作答（B1 通用 + B2 practice 复用） ----------
            case 'attempt_submit':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $aid = (int)($_POST['attempt_id'] ?? 0);
                if ($aid <= 0) jsonOut(false, "参数错误");

                $att = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=? AND user_id=?", [$aid, $uid]);
                if (!$att) jsonOut(false, "无权限或作答不存在");
                if ($att['status'] === 'submitted') jsonOut(false, "已提交，不可重复提交");

                $answersRaw = $_POST['answers'] ?? '';
                $answers = json_decode($answersRaw, true);
                if (!is_array($answers)) {
                    // 也兼容 FormData 里每个 question_id_x（但方案要求统一 JSON，这里做兜底）
                    $answers = [];
                    foreach ($_POST as $k => $v) {
                        if (str_starts_with($k, 'answer_')) {
                            $qid = (int)substr($k, 7);
                            if ($qid > 0) $answers[] = ['question_id' => $qid, 'student_answer' => $v];
                        }
                    }
                }

                // ===== 时长校验（服务端） =====
                $duration = (int)$att['duration_minutes'];
                $overdue = false;
                if ($duration > 0) {
                    $start = strtotime($att['started_at']);
                    $now = time();
                    $limit = $start + $duration * 60 + 60; // 60s 宽限
                    if ($now > $limit) $overdue = true;
                }

                try {
                    $db->exec('BEGIN');
                    $nowDT = gmdate('Y-m-d H:i:s');
                    // 写每题作答
                    $map = [];
                    foreach ($answers as $entry) {
                        if (!is_array($entry) || !isset($entry['question_id'])) continue;
                        $qid = (int)$entry['question_id'];
                        $sa  = $entry['student_answer'] ?? '';
                        // 数组（多空多选）转 JSON
                        if (is_array($sa)) $sa = json_encode($sa, JSON_UNESCAPED_UNICODE);
                        else $sa = (string)$sa;
                        $map[$qid] = $sa;
                    }
                    foreach ($map as $qid => $sa) {
                        dbQuery($db,
                            "UPDATE exam_answers SET student_answer=? WHERE attempt_id=? AND question_id=?",
                            [$sa, $aid, $qid]
                        );
                    }
                    // 标记已提交
                    dbQuery($db,
                        "UPDATE exam_attempts SET status='submitted', submitted_at=? WHERE id=?",
                        [$nowDT, $aid]
                    );
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "提交失败：" . $e->getMessage());
                }
                $msg = $overdue ? "已超时，系统强制提交（以提交时间为准）" : "提交成功";
                jsonOut(true, $msg, ['attempt_id' => $aid, 'overdue' => $overdue]);
                break;

            // ---------- 提交后：结果页（含正确答案 + 已有自评） ----------
            case 'attempt_result':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $aid = (int)($_POST['attempt_id'] ?? $_GET['attempt_id'] ?? 0);
                if ($aid <= 0) jsonOut(false, "参数错误");
                $att = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=? AND user_id=?", [$aid, $uid]);
                if (!$att) jsonOut(false, "无权限或作答不存在");
                if ($att['status'] !== 'submitted') jsonOut(false, "尚未提交，请先完成作答");
                jsonOut(true, "", examB_buildResult($db, $aid, $uid, true));
                break;

            // ---------- 学生自评 ----------
            case 'attempt_self_grade':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $aid = (int)($_POST['attempt_id'] ?? 0);
                if ($aid <= 0) jsonOut(false, "参数错误");
                $att = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=? AND user_id=?", [$aid, $uid]);
                if (!$att) jsonOut(false, "无权限或作答不存在");
                if ($att['status'] !== 'submitted') jsonOut(false, "尚未提交，无法自评");

                $gradesRaw = $_POST['grades'] ?? '';
                $grades = json_decode($gradesRaw, true);
                if (!is_array($grades) || count($grades) === 0) jsonOut(false, "请提供自评数据");

                $nowDT = gmdate('Y-m-d H:i:s');
                $total = 0.0;
                try {
                    $db->exec('BEGIN');
                    foreach ($grades as $g) {
                        if (!is_array($g) || !isset($g['question_id'])) continue;
                        $qid   = (int)$g['question_id'];
                        $corr  = isset($g['self_correct']) ? (int)$g['self_correct'] : null;
                        $score = isset($g['self_score']) ? (float)$g['self_score'] : null;
                        if ($corr !== null && $corr !== 0 && $corr !== 1) $corr = null;
                        dbQuery($db,
                            "UPDATE exam_answers SET self_correct=?, self_score=?, judged_at=? WHERE attempt_id=? AND question_id=?",
                            [$corr, $score, $nowDT, $aid, $qid]
                        );
                        if ($score !== null) $total += $score;
                    }
                    dbQuery($db, "UPDATE exam_attempts SET self_score_total=? WHERE id=?", [$total, $aid]);
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "自评失败：" . $e->getMessage());
                }
                jsonOut(true, "已保存自评", ['attempt_id' => $aid, 'self_score_total' => $total]);
                break;

            // ---------- 我的 attempt 历史（B1+B2 共用） ----------
            case 'attempt_list_mine':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $rows = dbFetchAll($db, "SELECT * FROM exam_attempts WHERE user_id=? ORDER BY id DESC LIMIT 200", [$uid]);
                $list = [];
                foreach ($rows as $r) {
                    $r['duration_minutes'] = (int)$r['duration_minutes'];
                    $r['paper_id'] = $r['paper_id'] ? (int)$r['paper_id'] : null;
                    $r['self_score_total'] = $r['self_score_total'] !== null ? (float)$r['self_score_total'] : null;
                    if ($r['paper_id']) {
                        $pname = dbFetchOne($db, "SELECT name FROM papers WHERE id=?", [$r['paper_id']]);
                        $r['paper_name'] = $pname ? $pname['name'] : '（已删除试卷）';
                    } else {
                        $r['paper_name'] = '自由刷题';
                        // 找出刷题用的科目（从首题 subject）
                        $sq = dbFetchOne($db,
                            "SELECT q.subject FROM exam_answers ea JOIN questions q ON q.id=ea.question_id WHERE ea.attempt_id=? LIMIT 1",
                            [(int)$r['id']]
                        );
                        $r['subject'] = $sq ? $sq['subject'] : '综合';
                    }
                    $cnt = dbFetchOne($db, "SELECT COUNT(*) AS c FROM exam_answers WHERE attempt_id=?", [(int)$r['id']]);
                    $r['question_count'] = (int)($cnt['c'] ?? 0);
                    $list[] = $r;
                }
                jsonOut(true, "", ['attempts' => $list]);
                break;

            // =====================================================
            // ============== P1-B2：自由刷题（学生） ==============
            // =====================================================

            // ---------- 有题的科目列表 ----------
            case 'practice_subjects':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $rawLevel = trim((string)($_POST['education_level'] ?? $_GET['education_level'] ?? ''));
                if ($rawLevel === '') jsonOut(false, '请先选择学段');
                $level = validateEducationLevel($rawLevel);
                $rows = dbFetchAll($db, "SELECT DISTINCT subject FROM questions WHERE education_level=? ORDER BY subject ASC", [$level]);
                $subjects = array_map(function($r){ return $r['subject']; }, $rows);
                jsonOut(true, "", ['subjects' => $subjects, 'education_level' => $level]);
                break;

            // ---------- 开始自由刷题 ----------
            case 'practice_start':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $rawEducationLevel = trim((string)($_POST['education_level'] ?? ''));
                if ($rawEducationLevel === '') jsonOut(false, '请先选择学段');
                $educationLevel = validateEducationLevel($rawEducationLevel);
                $subject = sanitizeInput($_POST['subject'] ?? '');
                $count   = (int)($_POST['count'] ?? 10);
                $dur     = (int)($_POST['duration_minutes'] ?? 0);

                if ($subject === '') jsonOut(false, "请选择科目");
                if ($count < 1 || $count > 50) jsonOut(false, "题目数量必须在1-50之间");
                if ($dur < 0) $dur = 0;

                $rows = dbFetchAll($db,
                    "SELECT id FROM questions WHERE education_level=? AND subject=? ORDER BY RANDOM() LIMIT ?",
                    [$educationLevel, $subject, $count]
                );
                if (!$rows) jsonOut(false, "该科目暂无题目");

                try {
                    $db->exec('BEGIN');
                    dbQuery($db,
                        "INSERT INTO exam_attempts(attempt_type, paper_id, user_id, duration_minutes, status) VALUES('practice', NULL, ?, ?, 'in_progress')",
                        [$uid, $dur]
                    );
                    $aid = (int)$db->lastInsertRowID();
                    foreach ($rows as $r) {
                        dbQuery($db, "INSERT INTO exam_answers(attempt_id, question_id) VALUES(?, ?)", [$aid, (int)$r['id']]);
                    }
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "开始失败：" . $e->getMessage());
                }

                // 返回题目（不含 correct_answer）
                $ids = array_column($rows, 'id');
                $in = implode(',', array_fill(0, count($ids), '?'));
                $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in) ORDER BY id DESC LIMIT 0", []);
                // 保持抽题顺序（按 ids 数组顺序恢复）
                $qById = [];
                $idOrder = $ids;
                $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in)", $ids);
                foreach ($qrows as $q) {
                    $q['options']    = $q['options'] ? json_decode($q['options'], true) : null;
                    $q['difficulty'] = (int)$q['difficulty'];
                    $q['points']     = (float)$q['points'];
                    unset($q['correct_answer'], $q['explanation']);
                    $qById[(int)$q['id']] = $q;
                }
                $questions = [];
                foreach ($idOrder as $id) {
                    if (isset($qById[(int)$id])) $questions[] = $qById[(int)$id];
                }
                jsonOut(true, "", [
                    'attempt_id' => $aid,
                    'attempt'    => examB_getAttemptRow($db, $aid),
                    'subject'    => $subject,
                    'questions'  => $questions,
                ]);
                break;

            // ---------- 自由刷题提交（复用 attempt_submit，兼容 B2 不限时时长） ----------
            case 'practice_submit':
                // 直接复用 attempt_submit（时长校验已覆盖 duration=0 的不限时场景）
                // 只是参数名与 attempt_submit 相同；这里做个薄壳
                $_POST['attempt_id'] = $_POST['attempt_id'] ?? '';
                $_POST['answers']    = $_POST['answers'] ?? '';
                $uid = null;
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $aid = (int)($_POST['attempt_id'] ?? 0);
                if ($aid <= 0) jsonOut(false, "参数错误");
                $att = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=? AND user_id=?", [$aid, $uid]);
                if (!$att) jsonOut(false, "无权限或作答不存在");
                if ($att['attempt_type'] !== 'practice') jsonOut(false, "此作答非自由刷题，请走考试提交流程");
                if ($att['status'] === 'submitted') jsonOut(false, "已提交，不可重复提交");

                $answersRaw = $_POST['answers'] ?? '';
                $answers = json_decode($answersRaw, true);
                if (!is_array($answers)) {
                    $answers = [];
                    foreach ($_POST as $k => $v) {
                        if (str_starts_with($k, 'answer_')) {
                            $qid = (int)substr($k, 7);
                            if ($qid > 0) $answers[] = ['question_id' => $qid, 'student_answer' => $v];
                        }
                    }
                }
                // B2 时长校验：duration>0 才校验
                $duration = (int)$att['duration_minutes'];
                $overdue = false;
                if ($duration > 0) {
                    $start = strtotime($att['started_at']);
                    if ((time() - $start) > $duration * 60 + 60) $overdue = true;
                }

                try {
                    $db->exec('BEGIN');
                    $nowDT = gmdate('Y-m-d H:i:s');
                    foreach ($answers as $entry) {
                        if (!is_array($entry) || !isset($entry['question_id'])) continue;
                        $qid = (int)$entry['question_id'];
                        $sa  = $entry['student_answer'] ?? '';
                        if (is_array($sa)) $sa = json_encode($sa, JSON_UNESCAPED_UNICODE);
                        else $sa = (string)$sa;
                        dbQuery($db,
                            "UPDATE exam_answers SET student_answer=? WHERE attempt_id=? AND question_id=?",
                            [$sa, $aid, $qid]
                        );
                    }
                    dbQuery($db,
                        "UPDATE exam_attempts SET status='submitted', submitted_at=? WHERE id=?",
                        [$nowDT, $aid]
                    );
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "提交失败：" . $e->getMessage());
                }
                $msg = $overdue ? "已超时，系统强制提交" : "提交成功";
                jsonOut(true, $msg, ['attempt_id' => $aid, 'overdue' => $overdue]);
                break;

            // ---------- 自由刷题结果 / 自评（别名复用 attempt_result / attempt_self_grade） ----------
            case 'practice_result':
                // 直接复用 attempt_result 逻辑
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $aid = (int)($_POST['attempt_id'] ?? $_GET['attempt_id'] ?? 0);
                if ($aid <= 0) jsonOut(false, "参数错误");
                $att = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=? AND user_id=?", [$aid, $uid]);
                if (!$att) jsonOut(false, "无权限或作答不存在");
                if ($att['attempt_type'] !== 'practice') jsonOut(false, "此作答不是自由刷题，请走考试结果流程");
                if ($att['status'] !== 'submitted') jsonOut(false, "尚未提交，请先完成作答");
                jsonOut(true, "", examB_buildResult($db, $aid, $uid, false));
                break;

            case 'practice_self_grade':
                // 与 attempt_self_grade 完全同构（attempt_type 由 attempt_id 自行判断）
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                $aid = (int)($_POST['attempt_id'] ?? 0);
                if ($aid <= 0) jsonOut(false, "参数错误");
                $att = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=? AND user_id=?", [$aid, $uid]);
                if (!$att) jsonOut(false, "无权限或作答不存在");
                if ($att['status'] !== 'submitted') jsonOut(false, "尚未提交，无法自评");
                $gradesRaw = $_POST['grades'] ?? '';
                $grades = json_decode($gradesRaw, true);
                if (!is_array($grades) || count($grades) === 0) jsonOut(false, "请提供自评数据");
                $nowDT = gmdate('Y-m-d H:i:s');
                $total = 0.0;
                try {
                    $db->exec('BEGIN');
                    foreach ($grades as $g) {
                        if (!is_array($g) || !isset($g['question_id'])) continue;
                        $qid   = (int)$g['question_id'];
                        $corr  = isset($g['self_correct']) ? (int)$g['self_correct'] : null;
                        $score = isset($g['self_score']) ? (float)$g['self_score'] : null;
                        if ($corr !== null && $corr !== 0 && $corr !== 1) $corr = null;
                        dbQuery($db,
                            "UPDATE exam_answers SET self_correct=?, self_score=?, judged_at=? WHERE attempt_id=? AND question_id=?",
                            [$corr, $score, $nowDT, $aid, $qid]
                        );
                        if ($score !== null) $total += $score;
                    }
                    dbQuery($db, "UPDATE exam_attempts SET self_score_total=? WHERE id=?", [$total, $aid]);
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "自评失败：" . $e->getMessage());
                }
                jsonOut(true, "已保存自评", ['attempt_id' => $aid, 'self_score_total' => $total]);
                break;

            // =====================================================
            // ============== P2-C：资料下载 ======================
            // =====================================================

            // ---------- 资料列表（登录即可；按 subject/keyword 过滤） ----------
            case 'material_list':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $subject = sanitizeInput($_POST['subject'] ?? $_GET['subject'] ?? '');
                $keyword = sanitizeInput($_POST['keyword'] ?? $_GET['keyword'] ?? '');
                $educationLevel = validateEducationLevel($_POST['education_level'] ?? $_GET['education_level'] ?? '', '');
                // 管理员在资料中心可选择"全部学段"查看所有资料（不再强制必须选具体学段）；
                // 若需限制学段仅用于筛选，空值即代表跨学段浏览。
                $where = ['1=1']; $args = [];
                if ($educationLevel !== '') { $where[] = 'education_level=?'; $args[] = $educationLevel; }
                if ($subject !== '') { $where[] = "subject=?"; $args[] = $subject; }
                if ($keyword !== '') { $where[] = "(filename LIKE ? OR description LIKE ?)"; $args[] = "%$keyword%"; $args[] = "%$keyword%"; }
                $wSql = implode(' AND ', $where);
                $rows = dbFetchAll($db, "SELECT id, filename, file_size, mime_type, subject, description, uploaded_at, updated_at, downloads, education_level FROM materials WHERE $wSql ORDER BY id DESC", $args);
                $subjects = dbFetchAll($db, "SELECT DISTINCT subject FROM materials WHERE subject IS NOT NULL AND subject <> '' ORDER BY subject ASC");
                foreach ($rows as &$r) {
                    $r['file_size'] = (int)$r['file_size'];
                    $r['downloads'] = (int)$r['downloads'];
                    $r['uploaded_at'] = formatDbUtcTimestamp($r['uploaded_at']);
                    $r['updated_at'] = formatDbUtcTimestamp($r['updated_at']);
                }
                unset($r);
                jsonOut(true, "", [
                    'materials' => $rows,
                    'subjects' => array_column($subjects, 'subject'),
                    'education_levels' => ['junior', 'senior'],
                    'can_upload' => isAdmin(),
                ]);
                break;

            // ---------- 管理员上传 ----------
            case 'material_upload':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                if (empty($_FILES['file'])) jsonOut(false, "请选择文件");
                $f = $_FILES['file'];
                if (!is_uploaded_file($f['tmp_name']) || $f['error'] !== UPLOAD_ERR_OK) {
                    jsonOut(false, "文件上传失败，错误码：" . ($f['error'] ?? 'unknown'));
                }
                // 50MB
                $max = 50 * 1024 * 1024;
                if ((int)$f['size'] > $max) jsonOut(false, "文件超过 50MB 上限");
                // 扩展名白名单
                $allowedExts = ['pdf','doc','docx','txt','md','xls','xlsx','ppt','pptx'];
                $origName = (string)$f['name'];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts, true)) {
                    jsonOut(false, "不允许的扩展名：$ext。仅允许：" . implode('/', $allowedExts));
                }
                // MIME 粗略过滤（不强制，仅做参考）
                $mime = '';
                if (function_exists('finfo_open')) {
                    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $mime = @finfo_file($finfo, $f['tmp_name']);
                        @finfo_close($finfo);
                    }
                }
                if (!$mime && function_exists('mime_content_type')) {
                    $mime = @mime_content_type($f['tmp_name']);
                }
                if (!$mime) {
                    $cmd = sprintf('file --mime-type -b %s', escapeshellarg($f['tmp_name']));
                    $mime = trim((string)@shell_exec($cmd));
                }

                $subject = sanitizeInput($_POST['subject'] ?? '');
                $desc    = sanitizeInput($_POST['description'] ?? '');
                $rawEducationLevel = trim((string)($_POST['education_level'] ?? ''));
                if ($rawEducationLevel === '') jsonOut(false, '请选择所属学段');
                $educationLevel = validateEducationLevel($rawEducationLevel);

                // 安全存储文件名（api.php 位于项目根，materials 同级）
                $matDir = __DIR__ . '/../materials';
                if (!is_dir($matDir)) @mkdir($matDir, 0755, true);
                $stored = uniqid('mat_', true) . '.' . $ext;
                $stored = preg_replace('/[^A-Za-z0-9._-]/', '', $stored); // 安全化
                $relPath = 'materials/' . $stored;
                $absPath = $matDir . DIRECTORY_SEPARATOR . $stored;
                if (!move_uploaded_file($f['tmp_name'], $absPath)) {
                    @unlink($f['tmp_name']);
                    jsonOut(false, "文件保存失败，请检查 materials/ 目录权限");
                }
                @chmod($absPath, 0644);

                dbQuery($db,
                    "INSERT INTO materials(filename, stored_name, file_path, file_size, mime_type, subject, description, uploaded_by, education_level) VALUES(?,?,?,?,?,?,?,?,?)",
                    [$origName, $stored, $relPath, (int)$f['size'], $mime, $subject, $desc, getUid(), $educationLevel]
                );
                $newId = (int)$db->lastInsertRowID();
                jsonOut(true, "上传成功", ['material_id' => $newId, 'filename' => $origName, 'size' => (int)$f['size'], 'education_level' => $educationLevel]);
                break;

            // ---------- 管理员编辑资料元数据 ----------
            case 'material_update':
                requireAdmin();
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) jsonOut(false, '参数错误');
                if (!dbFetchOne($db, 'SELECT id FROM materials WHERE id=?', [$id])) jsonOut(false, '资料不存在');
                $filename = trim((string)($_POST['filename'] ?? ''));
                $subject = sanitizeInput($_POST['subject'] ?? '');
                $desc = sanitizeInput($_POST['description'] ?? '');
                $rawEducationLevel = trim((string)($_POST['education_level'] ?? ''));
                if ($rawEducationLevel === '') jsonOut(false, '请选择所属学段');
                $educationLevel = validateEducationLevel($rawEducationLevel);
                if ($filename === '') jsonOut(false, '文件名不能为空');
                dbQuery($db, 'UPDATE materials SET filename=?, subject=?, description=?, education_level=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$filename, $subject, $desc, $educationLevel, $id]);
                jsonOut(true, '资料信息已更新');
                break;

            // ---------- 管理员删除 ----------
            case 'material_delete':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireAdmin();
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) jsonOut(false, "参数错误");
                $mat = dbFetchOne($db, "SELECT * FROM materials WHERE id=?", [$id]);
                if (!$mat) jsonOut(false, "资料不存在");
                $absPath = __DIR__ . '/../' . trim(str_replace('/', DIRECTORY_SEPARATOR, $mat['file_path']), '\\/');
                try {
                    $db->exec('BEGIN');
                    dbQuery($db, "DELETE FROM materials WHERE id=?", [$id]);
                    if (file_exists($absPath)) @unlink($absPath);
                    $db->exec('COMMIT');
                } catch (Exception $e) {
                    @$db->exec('ROLLBACK');
                    jsonOut(false, "删除失败：" . $e->getMessage());
                }
                jsonOut(true, "已删除");
                break;

            // ---------- 申请下载 token（登录即可；一次性；5 分钟有效） ----------
            case 'material_get_token':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) jsonOut(false, "参数错误");
                $mat = dbFetchOne($db, "SELECT id, filename, file_path FROM materials WHERE id=?", [$id]);
                if (!$mat) jsonOut(false, "资料不存在");
                if (!isset($_SESSION['download_tokens']) || !is_array($_SESSION['download_tokens'])) {
                    $_SESSION['download_tokens'] = [];
                }
                // 清理过期 token
                $now = time();
                foreach ($_SESSION['download_tokens'] as $t => $info) {
                    if ($info['expires'] < $now) unset($_SESSION['download_tokens'][$t]);
                }
                $token = bin2hex(random_bytes(16));
                $_SESSION['download_tokens'][$token] = [
                    'material_id' => (int)$mat['id'],
                    'filename'    => $mat['filename'],
                    'file_path'   => $mat['file_path'],
                    'expires'     => $now + 300, // 5 分钟
                    'uid'         => getUid(),
                ];
                jsonOut(true, "", ['download_token' => $token]);
                break;

            // ---------- 下载直链（GET 携带 token；一次性消耗；过期失效） ----------
            case 'material_download':
                // GET 直链：用 $_GET 取
                $token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
                if ($token === '' || !isset($_SESSION['download_tokens']) || !isset($_SESSION['download_tokens'][$token])) {
                    http_response_code(403);
                    die("下载链接无效或已过期，请返回资料中心重新获取");
                }
                $info = $_SESSION['download_tokens'][$token];
                if (!is_array($info) || ($info['expires'] ?? 0) < time()) {
                    unset($_SESSION['download_tokens'][$token]);
                    http_response_code(410);
                    die("下载链接已过期，请返回资料中心重新获取");
                }
                if (!isLoggedIn() || getUid() !== (int)($info['uid'] ?? 0)) {
                    http_response_code(403);
                    die("下载链接与当前登录用户不匹配");
                }
                // 一次性
                unset($_SESSION['download_tokens'][$token]);
                $rel = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$info['file_path']), DIRECTORY_SEPARATOR);
                $abs = __DIR__ . '/../' . $rel;
                // 目录穿越防御
                $baseDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR;
                $realAbs = realpath($abs);
                if ($realAbs === false || strpos($realAbs, $baseDir) !== 0 || !is_file($realAbs)) {
                    http_response_code(404);
                    die("文件不存在");
                }
                $mid = (int)($info['material_id'] ?? 0);
                if ($mid > 0) {
                    dbQuery($db, "UPDATE materials SET downloads = COALESCE(downloads,0) + 1 WHERE id=?", [$mid]);
                }
                // 输出
                $filename = (string)($info['filename'] ?? 'download');
                header('Content-Type: application/octet-stream');
                header('Content-Length: ' . filesize($realAbs));
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                if (preg_match('/MSIE|Trident|Edge/i', $ua)) {
                    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
                } else {
                    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
                }
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Pragma: no-cache');
                $fh = fopen($realAbs, 'rb');
                if ($fh) {
                    while (!feof($fh)) { echo fread($fh, 65536); if (connection_status() !== 0) break; }
                    fclose($fh);
                }
                exit;

            // ==================== 默认 ====================
            default:
                jsonOut(false, "未知操作");
        }
    } catch (Exception $e) {
        jsonOut(false, "服务器异常：" . $e->getMessage());
    }
    exit;
}

// 如果直接访问api.php，返回错误信息
jsonOut(false, "请通过API接口调用");

// =====================================================
// ============== 批量导入辅助函数 =====================
// =====================================================
// 兼容 import_exam.json 结构：
//   {试卷名称, 第I卷选择题: {题目列表:[{题号,题干,选项:{A:..}, 答案}]},
//    第II卷非选择题:{题目列表:[{题号,题干,分值,小问:[{小问序号,问题,参考答案}]}]}}
// 也支持扁平数组：[{subject, question_type, content, options, correct_answer, explanation, ...}]
function importExtractQuestions(array $data, string $subject, array &$questions): void {
    // 模式0a：PaperCutter-VL 包装格式（{match_key, paper_name, subject, education_level, questions: [...]}）
    if (isset($data['questions']) && is_array($data['questions'])) {
        // 从包装层提取科目（覆盖"综合"默认值）
        if (!empty($data['subject']) && ($subject === '' || $subject === '综合')) {
            $subject = trim($data['subject']);
        }
        $data = $data['questions'];
    }
    // 模式0b：PaperCutter-VL 扁平格式（含 question_id/question_content/question_options/question_images 等）
    if (pcvl_isPaperCutterVL($data)) {
        $pcvlSubject = $subject !== '' ? $subject : pcvl_detectSubject($data);
        pcvlConvertPaperCutterVL($data, $pcvlSubject, $questions);
        return;
    }
    // 模式1：扁平数组（每条已是标准结构）
    if (isset($data[0]) && is_array($data[0]) && (isset($data[0]['content']) || isset($data[0]['题干']))) {
        foreach ($data as $q) {
            $questions[] = importNormalizeFlat($q, $subject);
        }
        return;
    }
    // 模式2：按"卷"分组（import_exam.json 标准结构）
    foreach ($data as $key => $section) {
        if (!is_array($section) || !isset($section['题目列表'])) continue;
        if (!is_string($key)) continue;
        $isChoice    = str_contains($key, '选择');
        $isNonChoice = str_contains($key, '非选择') || str_contains($key, '主观') || str_contains($key, '填空') || str_contains($key, '简答');
        $defaultPts  = $section['小题分值'] ?? 2;
        $list = $section['题目列表'];
        foreach ($list as $q) {
            if (isset($q['小问'])) {
                // 非选择题：每个小问拆为独立题目
                importBuildNonChoice($q, $subject, $questions);
            } elseif ($isChoice || isset($q['选项'])) {
                $questions[] = importBuildChoice($q, $subject, (float)$defaultPts);
            } elseif ($isNonChoice) {
                importBuildNonChoice($q, $subject, $questions);
            }
        }
    }
}

function importNormalizeFlat(array $q, string $subject): array {
    $type = $q['question_type'] ?? ($q['题型'] ?? 'single');
    $options = $q['options'] ?? ($q['选项'] ?? null);
    if (is_array($options) && !isset($options[0])) {
        // 关联数组 {A:..,B:..} → 索引数组 ['A. ..','B. ..']
        $arr = [];
        foreach ($options as $k => $v) { $arr[] = strtoupper($k) . '. ' . $v; }
        $options = $arr;
    }
    $answer = $q['correct_answer'] ?? ($q['answer'] ?? ($q['答案'] ?? ($q['参考答案'] ?? '')));
    if (is_array($options) && is_string($answer) && strlen($answer) > 0) {
        $letter = strtoupper(trim($answer));
        // 若只填了字母，扩展为 "A. 选项内容"
        if (strlen($letter) === 1 && preg_match('/^[A-Z]$/', $letter)) {
            foreach ($options as $opt) {
                if (str_starts_with($opt, $letter . '.')) { $answer = $opt; break; }
            }
        }
    }
    return [
        'subject'        => normalizeSubject($q['subject'] ?? $subject),
        'question_type'  => $type,
        'category'       => $q['category'] ?? $type,
        'education_level'=> $q['education_level'] ?? 'junior',
        'content'        => $q['content'] ?? ($q['题干'] ?? ''),
        'options'        => $options,
        'correct_answer' => $answer,
        'explanation'    => $q['explanation'] ?? ($q['resolve'] ?? ($q['解析'] ?? null)),
        'difficulty'     => importEstimateDifficulty($q['content'] ?? ($q['题干'] ?? '')),
        'points'         => (float)($q['points'] ?? 1.0)
    ];
}

function importBuildChoice(array $q, string $subject, float $defaultPts): array {
    $rawOptions = $q['选项'] ?? [];
    $letters    = array_keys($rawOptions);
    $count      = count($rawOptions);
    if ($count === 2)       $type = 'judge';
    elseif ($count === 4)  $type = 'single';
    else                   $type = 'multiple';
    $optionArray = [];
    foreach ($rawOptions as $letter => $text) {
        $optionArray[] = strtoupper($letter) . '. ' . $text;
    }
    $answerLetter = strtoupper($q['答案'] ?? '');
    $correctText  = $answerLetter;
    foreach ($rawOptions as $letter => $text) {
        if (strtoupper($letter) === $answerLetter) {
            $correctText = strtoupper($letter) . '. ' . $text;
            break;
        }
    }
    return [
        'subject'        => $subject,
        'question_type'  => $type,
        'category'       => $q['category'] ?? $type,
        'education_level'=> $q['education_level'] ?? 'junior',
        'content'        => $q['题干'] ?? '',
        'options'        => $optionArray,
        'correct_answer' => $correctText,
        'explanation'    => $q['解析'] ?? null,
        'difficulty'     => importEstimateDifficulty($q['题干'] ?? ''),
        'points'         => (float)($q['分值'] ?? $defaultPts)
    ];
}

function importBuildNonChoice(array $q, string $subject, array &$questions): void {
    $background   = $q['题干'] ?? '';
    $totalPoints  = (float)($q['分值'] ?? 5);
    $subQuestions = $q['小问'] ?? [];
    $count        = count($subQuestions);
    $perSub       = $count > 0 ? round($totalPoints / $count, 1) : $totalPoints;
    foreach ($subQuestions as $sub) {
        $qText = $sub['问题'] ?? '';
        $ref   = $sub['参考答案'] ?? '';
        $subNo = $sub['小问序号'] ?? '';
        $content = $background;
        if ($subNo !== '') $content .= "\n\n" . $subNo . ' ';
        $content .= $qText;
        $questions[] = [
            'subject'        => $subject,
            'question_type'  => 'fill',
            'category'       => 'fill',
            'education_level'=> $q['education_level'] ?? 'junior',
            'content'        => $content,
            'options'        => null,
            'correct_answer' => $ref,
            'explanation'    => $ref,
            'difficulty'     => importEstimateDifficulty($background . $qText),
            'points'         => $perSub
        ];
    }
    // 没有小问结构，整题作为一道填空
    if (empty($subQuestions)) {
        $ref = $q['参考答案'] ?? ($q['答案'] ?? '');
        $questions[] = [
            'subject'        => $subject,
            'question_type'  => 'fill',
            'content'        => $background,
            'options'        => null,
            'correct_answer' => $ref,
            'explanation'    => $ref,
            'difficulty'     => importEstimateDifficulty($background),
            'points'         => $totalPoints
        ];
    }
}

function importEstimateDifficulty(string $text): int {
    $len = mb_strlen($text);
    if ($len < 50)  return 1;
    if ($len < 150) return 2;
    if ($len < 300) return 3;
    if ($len < 500) return 4;
    return 5;
}


// =====================================================
// ======== P1-B 模块：公共帮助函数 ====================
// =====================================================

/**
 * 学生端加载 questions：统一处理 options JSON、去掉 correct_answer / explanation
 * 输入 $links: [[question_id, sort_order, points], ...]
 */
function examB_loadQuestionsForStudent($db, array $links): array {
    $ids = array_filter(array_column($links, 'question_id'));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in)", $ids);
    $byId = [];
    foreach ($qrows as $q) {
        $q['options']    = $q['options'] ? json_decode($q['options'], true) : null;
        $q['difficulty'] = (int)$q['difficulty'];
        $q['points']     = (float)$q['points'];
        // 答题中：永不返回 correct_answer / explanation
        unset($q['correct_answer'], $q['explanation']);
        $byId[(int)$q['id']] = $q;
    }
    $result = [];
    foreach ($links as $link) {
        $qid = (int)($link['question_id'] ?? 0);
        if (!isset($byId[$qid])) continue;
        $item = $byId[$qid];
        if (isset($link['points'])) $item['paper_points'] = (float)$link['points'];
        $result[] = $item;
    }
    return $result;
}

/**
 * 学生端加载 questions：**带正确答案**（仅用于结果页）
 */
function examB_loadQuestionsWithAnswer($db, array $qids): array {
    $ids = array_values(array_filter(array_map('intval', $qids)));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in)", $ids);
    $byId = [];
    foreach ($qrows as $q) {
        $q['options']        = $q['options'] ? json_decode($q['options'], true) : null;
        $q['difficulty']     = (int)$q['difficulty'];
        $q['points']         = (float)$q['points'];
        $q['correct_answer'] = $q['correct_answer'] ?? '';
        $byId[(int)$q['id']] = $q;
    }
    $out = [];
    foreach ($ids as $id) { if (isset($byId[$id])) $out[] = $byId[$id]; }
    return $out;
}

function examB_sanitizePaper($paper): ?array {
    if (!$paper) return null;
    $paper['duration_minutes'] = (int)$paper['duration_minutes'];
    $paper['is_published'] = (int)$paper['is_published'];
    $paper['created_by'] = $paper['created_by'] ? (int)$paper['created_by'] : null;
    return $paper;
}

function examB_getAttemptRow($db, int $aid): ?array {
    $r = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=?", [$aid]);
    if (!$r) return null;
    $r['duration_minutes'] = (int)$r['duration_minutes'];
    $r['paper_id'] = $r['paper_id'] ? (int)$r['paper_id'] : null;
    $r['self_score_total'] = $r['self_score_total'] !== null ? (float)$r['self_score_total'] : null;
    return $r;
}

/**
 * 构建提交后的结果页 payload（供 attempt_result / practice_result 调用）
 * $isExam: true=组卷（含试卷信息 + paper_points）；false=自由刷题
 */
function examB_buildResult($db, int $aid, int $uid, bool $isExam): array {
    $attempt = examB_getAttemptRow($db, $aid);
    $paper = null;
    $questionsOrdered = [];
    if ($attempt['paper_id']) {
        $paper = examB_sanitizePaper(dbFetchOne($db, "SELECT * FROM papers WHERE id=?", [(int)$attempt['paper_id']]));
        $links = dbFetchAll($db,
            "SELECT question_id, sort_order, points FROM paper_questions WHERE paper_id=? ORDER BY sort_order ASC, id ASC",
            [(int)$attempt['paper_id']]
        );
        $ids = array_column($links, 'question_id');
        $questionsById = [];
        foreach (examB_loadQuestionsWithAnswer($db, $ids) as $q) { $questionsById[(int)$q['id']] = $q; }
        foreach ($links as $link) {
            $qid = (int)$link['question_id'];
            if (!isset($questionsById[$qid])) continue;
            $item = $questionsById[$qid];
            $item['paper_points'] = (float)$link['points'];
            $item['sort_order'] = (int)$link['sort_order'];
            $questionsOrdered[] = $item;
        }
    } else {
        // practice：按 exam_answers 写入顺序
        $ans = dbFetchAll($db, "SELECT id, question_id FROM exam_answers WHERE attempt_id=? ORDER BY id ASC", [$aid]);
        $ids = array_column($ans, 'question_id');
        $questionsById = [];
        foreach (examB_loadQuestionsWithAnswer($db, $ids) as $q) { $questionsById[(int)$q['id']] = $q; }
        foreach ($ans as $row) {
            $qid = (int)$row['question_id'];
            if (isset($questionsById[$qid])) $questionsOrdered[] = $questionsById[$qid];
        }
    }

    // 读取作答 + 自评
    $ansRows = dbFetchAll($db, "SELECT question_id, student_answer, self_correct, self_score, judged_at FROM exam_answers WHERE attempt_id=?", [$aid]);
    $ansMap = [];
    foreach ($ansRows as $a) {
        $qid = (int)$a['question_id'];
        $a['self_correct'] = $a['self_correct'] !== null ? (int)$a['self_correct'] : null;
        $a['self_score']   = $a['self_score']   !== null ? (float)$a['self_score'] : null;
        $ansMap[$qid] = $a;
    }

    $maxPossible = 0.0;
    $answeredCount = 0;
    $graded = 0; // 已自评题数（self_correct 非空）
    $correctCount = 0;
    foreach ($questionsOrdered as &$q) {
        $qid = (int)$q['id'];
        $a = $ansMap[$qid] ?? null;
        $sa = $a['student_answer'] ?? null;
        // 多空 / 多选：如果是 JSON 数组字符串，返回数组（便于前端渲染）
        if (is_string($sa) && in_array($q['question_type'], ['multi_fill', 'fill', 'multi'], true)) {
            $dec = json_decode($sa, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) $sa = $dec;
        }
        $q['student_answer'] = $sa;
        $q['self_correct']   = $a['self_correct'] ?? null;
        $q['self_score']     = $a['self_score'] ?? null;
        $q['judged_at']      = $a['judged_at'] ?? null;
        $q['max_points']     = (float)($q['paper_points'] ?? $q['points'] ?? 1.0);
        $maxPossible += $q['max_points'];
        if ($sa !== null && $sa !== '' && $sa !== []) $answeredCount++;
        if ($q['self_correct'] !== null) {
            $graded++;
            if ($q['self_correct'] === 1) $correctCount++;
        }
    }

    return [
        'attempt'           => $attempt,
        'paper'             => $paper,
        'questions'         => $questionsOrdered,
        'summary'           => [
            'total_count'       => count($questionsOrdered),
            'answered_count'    => $answeredCount,
            'graded_count'      => $graded,
            'correct_count'     => $correctCount,
            'max_possible'      => $maxPossible,
            'self_score_total'  => $attempt['self_score_total'],
        ],
    ];
}

// =====================================================
// ============== PaperCutter-VL 格式识别与转换 ==============
// =====================================================

/**
 * 判断是否为 PaperCutter-VL 输出格式
 * 特征：顶层数组每条均为对象，含 question_content / question_options / question_type 等字段
 */
function pcvl_isPaperCutterVL(array $data): bool {
    if (!is_array($data) || isset($data[0]) && !is_array($data[0])) return false;
    if (!isset($data[0]) || !is_array($data[0])) return false;
    $keys = array_keys($data[0]);
    $required = ['question_content', 'question_type'];
    $hit = 0;
    foreach ($required as $r) {
        if (in_array($r, $keys, true)) $hit++;
    }
    // 至少命中 2 个 PaperCutter 特有字段即判定为真
    if ($hit < 2) return false;
    // 且必须具备 PaperCutter 独特字段之一
    $paperCutterOnly = ['question_id', 'question_images', 'analysis_images', 'source_province', 'source_year'];
    foreach ($paperCutterOnly as $k) {
        if (in_array($k, $keys, true)) return true;
    }
    return false;
}

/**
 * 从整份 PaperCutter-VL 数据推断科目
 */
function pcvl_detectSubject(array $data): string {
    // 先看是否有 subject 字段非空
    foreach ($data as $q) {
        if (!empty($q['subject']) && is_string($q['subject'])) {
            $s = trim($q['subject']);
            if ($s !== '') return $s;
        }
    }
    // 从 source 里猜（例如 "2025年安徽省初中学业水平考试 道德与法治（开卷）"）
    $subjectMap = [
        '道德与法治' => '道德与法治', '政治' => '政治',
        '历史' => '历史', '地理' => '地理',
        '语文' => '语文', '数学' => '数学', '英语' => '英语',
        '物理' => '物理', '化学' => '化学', '生物' => '生物',
        '信息技术' => '信息技术',
    ];
    foreach ($data as $q) {
        $src = $q['source'] ?? '';
        $cnt = $q['question_content'] ?? '';
        $blob = $src . $cnt;
        foreach ($subjectMap as $k => $v) {
            if ($k !== '政治' && mb_strpos($blob, $k) !== false) return $v;
        }
    }
    return '综合';
}

/**
 * PaperCutter-VL 格式 → 系统扁平数组
 * 主要处理：题型识别、选项（去除 A./B. 前缀）、题干 HTML 富化（base64 图片嵌入、表格内嵌）
 */
function pcvlConvertPaperCutterVL(array $data, string $subject, array &$questions): void {
    $typeMap = [
        '单选题' => 'single', '单选题（四选一）' => 'single',
        '多选题' => 'multiple', '多选题（不定项）' => 'multiple',
        '判断题' => 'judge', '对错题' => 'judge',
        '填空题' => 'fill', '多空填空题' => 'multi_fill',
        '简答题' => 'short', '问答题' => 'short', '论述题' => 'short',
        '选择题' => 'single',
    ];

    foreach ($data as $q) {
        if (!is_array($q)) continue;
        $rawType = $q['question_type'] ?? '';
        $typeKey = $typeMap[$rawType] ?? 'single';

        // 选项处理
        $rawOpts = $q['question_options'] ?? [];
        $options = [];
        if (is_array($rawOpts) && !empty($rawOpts)) {
            foreach ($rawOpts as $opt) {
                if (!is_string($opt)) continue;
                // 去掉 "A. " / "A、" / "A)" / "A)" 这类前缀，保留内容
                $clean = preg_replace('/^\s*[A-Z][.、)）:：\s]+/', '', $opt);
                // 如果只剩了一个字母（比如 "A"），就把原串留下
                if ($clean === '' && preg_match('/^\s*([A-Z])\s*$', $opt, $m)) {
                    $options[] = $opt;
                } else {
                    $options[] = $clean !== '' ? $clean : $opt;
                }
            }
        }

        // 根据选项数推断类型（若只有 "选择题" 这种含糊标签）
        if ($rawType === '选择题') {
            $cnt = count($options);
            if ($cnt === 2) $typeKey = 'judge';
            elseif ($cnt >= 4) $typeKey = 'single';
        }

        // 答案处理（优先使用 answer 字段，兼容 correct_answer 字段）
        $answerText = $q['answer'] ?? ($q['correct_answer'] ?? '');
        $explanation = $q['resolve'] ?? ($q['explanation'] ?? '');

        // 题干富化：把 question_images、analysis_images 里的 base64 补到 HTML 中
        $content = $q['question_content'] ?? '';
        $tables = $q['question_tables'] ?? [];
        $qImages = $q['question_images'] ?? [];
        $aImages = $q['analysis_images'] ?? [];
        $isHtml = false;

        // 若 content 本身已含 HTML（PaperCutter-VL 常常把 base64 <img> 直接塞在 content 里），直接保留
        if (preg_match('/<[a-zA-Z][^>]*>/', $content)) {
            $isHtml = true;
        }
        // 若选项中含有 HTML 标签，也标记为富文本
        if (!empty($options)) {
            foreach ($options as $opt) {
                if (is_string($opt) && preg_match('/<[a-zA-Z][^>]*>/', $opt)) {
                    $isHtml = true;
                    break;
                }
            }
        }
        // 把附加表格追加到 content
        if (is_array($tables) && !empty($tables)) {
            foreach ($tables as $t) {
                if (is_string($t) && trim($t) !== '') {
                    $content .= "\n\n" . $t;
                    $isHtml = true;
                }
            }
        }
        // 图片去重：记录已出现过的 base64 摘要（前 64 字符），避免同一张图重复插入
        $seenImgHash = [];

        // 把题目图片追加到题干 content；解析图片追加到 explanation
        $appendImgs = function (array $imgs, string &$target, bool &$htmlFlag) use (&$seenImgHash) {
            foreach ($imgs as $b64) {
                if (!is_string($b64) || trim($b64) === '') continue;
                // 归一化：去掉 data: 前缀，只取 base64 部分做去重
                $pure = preg_replace('/^data:image\/[a-z]+;base64,/i', '', $b64);
                $pure = ltrim($pure);
                // 用前 80 字符做指纹去重（足够区分不同图，又不会太慢）
                $fingerprint = substr($pure, 0, 80);
                if ($fingerprint === '' || isset($seenImgHash[$fingerprint])) continue;
                $seenImgHash[$fingerprint] = true;

                // 检查 target 中是否已内嵌了这张图（PaperCutter-VL 有时 content 里已有 <img src="data:..."> 又在 images 数组里重复列）
                if (strpos($target, $fingerprint) !== false) continue;

                $src = 'data:image/jpeg;base64,' . $pure;
                $target .= "\n\n<div style=\"text-align:center\"><img src=\"" . htmlspecialchars($src, ENT_QUOTES) . "\" alt=\"图片\" style=\"max-width:100%;height:auto;\"></div>";
                $htmlFlag = true;
            }
        };
        // 题目图片 → 题干
        $appendImgs($qImages, $content, $isHtml);
        // 解析图片 → 解析（不是题干！）
        $appendImgs($aImages, $explanation, $isHtml);

        // 推断难度
        $diff = intval($q['difficulty'] ?? 0);
        if ($diff <= 0) $diff = importEstimateDifficulty($content);

        // 题干太长时用 content-box 展示全文（不再只截断 200 字）
        $points = 2.0; // 默认分
        $sourceLabel = $q['source'] ?? '';
        if ($sourceLabel !== '') {
            $explanation = trim($explanation . "\n（来源：" . $sourceLabel . "）");
        }

        $questions[] = [
            'subject'        => normalizeSubject($subject),
            'question_type'  => $typeKey,
            'category'       => $typeKey,
            'education_level'=> '',   // 由外层按当前选择的学段覆盖
            'content'        => $content,
            'options'        => !empty($options) ? $options : null,
            'correct_answer' => $answerText,
            'explanation'    => $explanation !== '' ? $explanation : null,
            'difficulty'     => $diff,
            'points'         => $points,
            'is_html'        => $isHtml ? 1 : 0,
            '_pcvl_qid'      => $q['question_id'] ?? 0,  // 保留原始题号用于答案匹配
        ];
    }
}
