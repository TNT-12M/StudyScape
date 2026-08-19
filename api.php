<?php
/**
 * 初高中在线考试系统 - API接口
 * db文件: exam.db 自动生成
 * 表：users, exam_data
 * 【警告：密码明文存储，仅用于本地测试，禁止外网部署】
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ===================== SQLite 初始化 =====================
$dbFile = __DIR__ . '/exam.db';
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
        $db->exec("ALTER TABLE questions ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP");
    }
} catch (Exception $e) {
    // 静默：表不存在等已被 CREATE 兜底
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

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function getUid() {
    return $_SESSION['user_id'] ?? null;
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

// ==================== CSRF验证（重要修复） ====================
// 所有不需要CSRF验证的操作列表
$csrfBypass = [
    'login',
    'register',
    'create_admin',
    'make_admin',
    'delete_user',
    'clear_users',
    'reset_db',
    'get_admin_panel',
    'check_session',
    'captcha',
    'list_questions',     // 题目浏览（GET性质）
    'get_question',
    'get_subjects',
    'question_stats'
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

                dbQuery($db, "INSERT INTO users(username, email, password, is_approved) VALUES(?, ?, ?, 0)", 
                    [$username, $email, $password]);
                jsonOut(true, "注册成功，请等待管理员审核");
                break;

            // ==================== 用户登录 ====================
            case 'login':
                $username = sanitizeInput($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    jsonOut(false, "请输入用户名密码");
                }
                
                $u = dbFetchOne($db, "SELECT * FROM users WHERE username=?", [$username]);
                
                if (!$u || $u['password'] !== $password) {
                    jsonOut(false, "用户名或密码错误");
                }
                
                if (!$u['is_active']) {
                    jsonOut(false, "账号已被禁用");
                }

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

            // ==================== 检查会话 ====================
            case 'check_session':
                if (isLoggedIn()) {
                    $u = dbFetchOne($db, "SELECT id, username, email, is_admin, is_approved FROM users WHERE id=?", [getUid()]);
                    if ($u) {
                        jsonOut(true, "", ['user' => $u]);
                    } else {
                        jsonOut(false, "未登录");
                    }
                } else {
                    jsonOut(false, "未登录");
                }
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
                    'user' => $user,
                    'stats' => [
                        'exam_count' => $examCount,
                        'submitted_count' => $submitCount
                    ],
                    'recent_results' => array_map(function ($r) {
                        return json_decode($r['json_content'], true);
                    }, $resList)
                ]);
                break;

            // ==================== 管理员面板（获取所有数据） ====================
            case 'get_admin_panel':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                // 不再返回 password 字段
                $users = dbFetchAll($db, "SELECT id, username, email, is_admin, is_approved, is_active, created_at, last_login FROM users ORDER BY id DESC");
                $examsRaw = dbFetchAll($db, "SELECT * FROM exam_data WHERE type='exam' ORDER BY id DESC");
                $exams = array_map(function ($i) {
                    return json_decode($i['json_content'], true);
                }, $examsRaw);
                $userTotal = dbFetchOne($db, "SELECT count(*) as c FROM users")['c'];
                $pending = dbFetchOne($db, "SELECT count(*) as c FROM users WHERE is_approved=0")['c'];
                jsonOut(true, "", [
                    'users' => $users,
                    'exams' => $exams,
                    'stats' => ['user_count' => $userTotal, 'pending_count' => $pending]
                ]);
                break;

            // ==================== 审核用户 ====================
            case 'approve_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "UPDATE users SET is_approved=1 WHERE id=?", [$uid]);
                jsonOut(true, "已通过审核");
                break;

            // ==================== 拒绝用户 ====================
            case 'reject_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "DELETE FROM users WHERE id=? AND is_approved=0", [$uid]);
                jsonOut(true, "已拒绝用户");
                break;

            // ==================== 启用/禁用用户 ====================
            case 'toggle_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "UPDATE users SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?", [$uid]);
                jsonOut(true, "状态已更新");
                break;

            // ==================== 创建管理员（无需登录，但密码哈希化） ====================
            case 'create_admin':
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($email) || empty($password)) {
                    jsonOut(false, "请完整填写用户名、邮箱和密码");
                }
                if (!validateEmail($email)) {
                    jsonOut(false, "邮箱格式不正确");
                }
                if (strlen($password) < 4) {
                    jsonOut(false, "密码长度不能少于4位");
                }
                
                $exist = dbFetchOne($db, "SELECT id FROM users WHERE username=? OR email=?", [$username, $email]);
                if ($exist) {
                    jsonOut(false, "用户名或邮箱已被注册");
                }
                
                dbQuery($db, "INSERT INTO users(username, email, password, is_admin, is_approved, is_active) VALUES(?, ?, ?, 1, 1, 1)", 
                    [$username, $email, $password]);
                jsonOut(true, "管理员创建成功");
                break;

            // ==================== 设为管理员 ====================
            case 'make_admin':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "UPDATE users SET is_admin=1 WHERE id=?", [$uid]);
                jsonOut(true, "已设为管理员");
                break;

            // ==================== 删除用户 ====================
            case 'delete_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "DELETE FROM users WHERE id=?", [$uid]);
                jsonOut(true, "用户已删除");
                break;

            // ==================== 清空所有用户 ====================
            case 'clear_users':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                dbQuery($db, "DELETE FROM users");
                jsonOut(true, "所有用户已删除");
                break;

            // ==================== 重置数据库 ====================
            case 'reset_db':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                $db->exec("DROP TABLE IF EXISTS users");
                $db->exec("DROP TABLE IF EXISTS exam_data");
                $db->exec("DROP TABLE IF EXISTS questions");
                $db->exec($createSql);
                jsonOut(true, "数据库已重置");
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
                $rows = dbFetchAll($db, "SELECT DISTINCT subject FROM questions ORDER BY subject ASC");
                $subjects = array_map(function($r){ return $r['subject']; }, $rows);
                jsonOut(true, "", ['subjects' => $subjects]);
                break;

            // ---------- 题目列表（分页+筛选） ----------
            case 'list_questions':
                if (!isLoggedIn()) jsonOut(false, "请先登录");

                $subject   = isset($_GET['subject'])   ? sanitizeInput($_GET['subject'])   : (isset($_POST['subject'])   ? sanitizeInput($_POST['subject'])   : '');
                $qtype     = isset($_GET['qtype'])     ? sanitizeInput($_GET['qtype'])     : (isset($_POST['qtype'])     ? sanitizeInput($_POST['qtype'])     : '');
                $keyword   = isset($_GET['keyword'])   ? sanitizeInput($_GET['keyword'])   : (isset($_POST['keyword'])   ? sanitizeInput($_POST['keyword'])   : '');
                $page      = max(1, (int)($_GET['page']   ?? $_POST['page']   ?? 1));
                $page_size = max(1, min(100, (int)($_GET['page_size'] ?? $_POST['page_size'] ?? 10)));

                $where = [];
                $params = [];
                if ($subject !== '') { $where[] = 'subject = ?'; $params[] = $subject; }
                if ($qtype !== '')   { $where[] = 'question_type = ?'; $params[] = $qtype; }
                if ($keyword !== '')  { $where[] = '(content LIKE ? OR explanation LIKE ?)'; $params[] = "%$keyword%"; $params[] = "%$keyword%"; }
                $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

                $total = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM questions $whereSql", $params)['c'];
                $offset = ($page - 1) * $page_size;
                $rows = dbFetchAll($db, "SELECT id, subject, question_type, content, options, correct_answer, explanation, difficulty, points, created_at FROM questions $whereSql ORDER BY id DESC LIMIT ? OFFSET ?", array_merge($params, [$page_size, $offset]));

                // 反序列化 options
                $items = array_map(function($r){
                    $r['options']     = $r['options'] ? json_decode($r['options'], true) : null;
                    $r['difficulty']  = (int)$r['difficulty'];
                    $r['points']      = (float)$r['points'];
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
                jsonOut(true, "", ['question' => $row]);
                break;

            // ---------- 新增题目 ----------
            case 'add_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");

                $subject     = sanitizeInput($_POST['subject'] ?? '');
                $qtype       = sanitizeInput($_POST['question_type'] ?? '');
                $content     = sanitizeInput($_POST['content'] ?? '');
                $optionsRaw  = $_POST['options'] ?? '[]';
                $answer      = sanitizeInput($_POST['correct_answer'] ?? '');
                $explanation = sanitizeInput($_POST['explanation'] ?? '');
                $difficulty  = (int)($_POST['difficulty'] ?? 1);
                $points      = (float)($_POST['points'] ?? 1.0);

                // 校验
                $validTypes = ['single', 'multiple', 'judge', 'fill'];
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
                dbQuery($db, "INSERT INTO questions (subject, question_type, content, options, correct_answer, explanation, difficulty, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$subject, $qtype, $content, $optionsJson, $answer, $explanation, $difficulty, $points]);
                $newId = (int)$db->lastInsertRowID();
                jsonOut(true, "题目已保存", ['id' => $newId]);
                break;

            // ---------- 修改题目 ----------
            case 'update_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");

                $qid = (int)($_POST['id'] ?? 0);
                if ($qid <= 0) jsonOut(false, "题目ID无效");
                $existing = dbFetchOne($db, "SELECT id FROM questions WHERE id=?", [$qid]);
                if (!$existing) jsonOut(false, "题目不存在");

                $subject     = sanitizeInput($_POST['subject'] ?? '');
                $qtype       = sanitizeInput($_POST['question_type'] ?? '');
                $content     = sanitizeInput($_POST['content'] ?? '');
                $optionsRaw  = $_POST['options'] ?? '[]';
                $answer      = sanitizeInput($_POST['correct_answer'] ?? '');
                $explanation = sanitizeInput($_POST['explanation'] ?? '');
                $difficulty  = (int)($_POST['difficulty'] ?? 1);
                $points      = (float)($_POST['points'] ?? 1.0);

                $validTypes = ['single', 'multiple', 'judge', 'fill'];
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
                dbQuery($db, "UPDATE questions SET subject=?, question_type=?, content=?, options=?, correct_answer=?, explanation=?, difficulty=?, points=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                    [$subject, $qtype, $content, $optionsJson, $answer, $explanation, $difficulty, $points, $qid]);
                jsonOut(true, "题目已更新");
                break;

            // ---------- 删除题目 ----------
            case 'delete_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");
                $qid = (int)($_POST['id'] ?? 0);
                if ($qid <= 0) jsonOut(false, "题目ID无效");
                dbQuery($db, "DELETE FROM questions WHERE id=?", [$qid]);
                jsonOut(true, "题目已删除");
                break;

            // ---------- 批量导入（兼容 import_exam.json 结构） ----------
            case 'import_questions_json':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");

                $rawJson = $_POST['json_data'] ?? '';
                if (trim($rawJson) === '') jsonOut(false, "请提供JSON数据");
                $data = json_decode($rawJson, true);
                if (!is_array($data)) jsonOut(false, "JSON解析失败: " . json_last_error_msg());

                $subjectOverride = sanitizeInput($_POST['subject'] ?? '');

                // 自动识别科目
                $subjectMap = [
                    '生物' => '生物', '化学' => '化学', '物理' => '物理',
                    '数学' => '数学', '英语' => '英语', '语文' => '语文',
                    '历史' => '历史', '地理' => '地理', '政治' => '政治',
                    '信息技术' => '信息技术', '计算机' => '信息技术'
                ];
                $detectedSubject = $subjectOverride;
                if ($detectedSubject === '') {
                    $title = $data['试卷名称'] ?? $data['title'] ?? '';
                    foreach ($subjectMap as $k => $v) {
                        if (str_contains($title, $k)) { $detectedSubject = $v; break; }
                    }
                    if ($detectedSubject === '') $detectedSubject = '综合';
                }

                $questions = [];
                importExtractQuestions($data, $detectedSubject, $questions);

                $stats = ['inserted' => 0, 'skipped' => 0, 'errors' => 0];
                foreach ($questions as $q) {
                    try {
                        // 查重：相同题干 + 答案
                        $dup = dbFetchOne($db, "SELECT id FROM questions WHERE content=? AND correct_answer=? AND subject=? LIMIT 1",
                            [$q['content'], $q['correct_answer'], $q['subject']]);
                        if ($dup) { $stats['skipped']++; continue; }
                        $optionsJson = !empty($q['options']) ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null;
                        dbQuery($db, "INSERT INTO questions (subject, question_type, content, options, correct_answer, explanation, difficulty, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                            [$q['subject'], $q['question_type'], $q['content'], $optionsJson, $q['correct_answer'], $q['explanation'] ?? null, $q['difficulty'], $q['points']]);
                        $stats['inserted']++;
                    } catch (Exception $ex) {
                        $stats['errors']++;
                    }
                }
                jsonOut(true, "导入完成: 新增 {$stats['inserted']} / 跳过重复 {$stats['skipped']} / 失败 {$stats['errors']}", [
                    'stats' => $stats,
                    'subject' => $detectedSubject,
                    'parsed_count' => count($questions)
                ]);
                break;

            // ---------- 文档智能导入（解析阶段，不写库） ----------
            case 'extract_document':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                if (!isAdmin())    jsonOut(false, "权限不足，仅管理员可操作");

                // 1. 文件存在性校验
                if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                    jsonOut(false, "未收到文件或上传失败（错误码: " . ($_FILES['document']['error'] ?? -1) . "）");
                }

                $file = $_FILES['document'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                // 2. 解析器注册表：扩展名 → 处理器
                $parsers = getParsers();
                if (!isset($parsers[$ext])) {
                    jsonOut(false, "不支持的文件类型: .$ext，当前支持: " . implode('/', array_keys($parsers)));
                }

                // 3. 大小校验（10MB）
                if ($file['size'] > 10 * 1024 * 1024) {
                    jsonOut(false, "文件过大（" . round($file['size']/1024/1024, 2) . "MB），最大支持 10MB");
                }

                // 4. MIME 简单校验（防伪造扩展名）
                $allowedMime = [
                    'pdf'  => ['application/pdf', 'application/x-pdf'],
                    'doc'  => ['application/msword'],
                    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    'txt'  => ['text/plain', 'application/octet-stream'],
                    'md'   => ['text/plain', 'text/markdown', 'application/octet-stream'],
                ];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($file['tmp_name']);
                if (!empty($allowedMime[$ext]) && !in_array($mime, $allowedMime[$ext], true)) {
                    jsonOut(false, "文件内容与扩展名不匹配（检测到 MIME: $mime）");
                }

                // 5. 保存到 uploads/（自动建目录）
                $uploadsDir = __DIR__ . '/uploads';
                if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0755, true);
                $tmpFile = $uploadsDir . '/import_' . uniqid('doc_', true) . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
                    jsonOut(false, "临时文件保存失败，请检查 uploads 目录权限");
                }

                try {
                    $parsed = call_user_func($parsers[$ext], $tmpFile, $file['name']);
                } catch (Exception $e) {
                    @unlink($tmpFile);
                    jsonOut(false, "解析异常: " . $e->getMessage());
                }
                @unlink($tmpFile); // 用完即删

                if (empty($parsed['success'])) {
                    jsonOut(false, $parsed['message'] ?? '解析失败');
                }

                // 6. 归一化为扁平数组（与 import_questions_json 兼容）
                $subjectOverride = sanitizeInput($_POST['subject'] ?? '');
                $flat = normalizeExtractedQuestions(
                    $parsed['questions'] ?? [],
                    $subjectOverride,
                    $file['name'],
                    $parsed['text'] ?? ''
                );

                jsonOut(true, "解析完成: 共 " . count($flat) . " 道题（method=" . ($parsed['method'] ?? 'unknown') . "）", [
                    'questions'         => $flat,
                    'subject'           => $subjectOverride !== '' ? $subjectOverride : detectSubjectFromName($file['name'], $parsed['text'] ?? ''),
                    'method'            => $parsed['method'] ?? '',
                    'file_type'         => $parsed['type'] ?? $ext,
                    'raw_text_preview'  => mb_substr($parsed['text'] ?? '', 0, 500),
                    'parsed_count'      => count($flat)
                ]);
                break;

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
    $answer = $q['correct_answer'] ?? ($q['答案'] ?? ($q['参考答案'] ?? ''));
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
        'subject'        => $q['subject'] ?? $subject,
        'question_type'  => $type,
        'content'        => $q['content'] ?? ($q['题干'] ?? ''),
        'options'        => $options,
        'correct_answer' => $answer,
        'explanation'    => $q['explanation'] ?? ($q['解析'] ?? null),
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
// ============== 文档智能导入辅助函数 =================
// =====================================================
// 设计目标：
//   1) 解析器注册表模式：新增格式只需在 getParsers() 注册一项
//   2) 不直接依赖具体实现（Python / PHP 原生均可），保证可替换
//   3) 输出格式与 import_questions_json 兼容，可无缝回填入库

/**
 * 解析器注册表
 * 新增格式时只需在此注册一项：'扩展名' => '处理器函数名'
 * 每个 parser 必须返回:
 *   ['success'=>bool, 'message'=>?, 'method'=>?, 'text'=>?, 'questions'=>[], 'type'=>?]
 */
function getParsers(): array {
    return [
        'pdf'   => 'parserPythonExtract',
        'doc'   => 'parserPythonExtract',
        'docx'  => 'parserPythonExtract',
        'txt'   => 'parserPythonExtract',
        'md'    => 'parserPythonExtract',
        // 扩展示例（未启用，预留接口）：
        // 'csv'  => 'parserCsv',
        // 'xlsx' => 'parserXlsx',
    ];
}

/**
 * 调用 extract.py 解析文档（兼容 PDF/DOC/DOCX/TXT/MD）
 * 依赖：Python 3 + extract.py 同目录 + (可选) pdftotext / pdf2image / pytesseract / python-docx / antiword
 */
function parserPythonExtract(string $tmpFile, string $origName): array {
    $pyBin = getPythonBinary();
    if ($pyBin === null) {
        return [
            'success' => false,
            'message' => '服务器未安装 Python 或不可调用（python/python3 均失败）。请联系运维安装 Python 3，或继续使用 JSON 导入。',
        ];
    }
    $script = __DIR__ . '/extract.py';
    if (!file_exists($script)) {
        return ['success' => false, 'message' => 'extract.py 脚本缺失'];
    }

    // 命令三段全部 escapeshellarg 包裹，防止注入
    $cmd = escapeshellarg($pyBin) . ' ' . escapeshellarg($script)
         . ' ' . escapeshellarg($tmpFile) . ' --parse 2>&1';

    // 临时抬高执行时间，避免 OCR 大文件超时
    $origLimit = @ini_get('max_execution_time');
    @set_time_limit(180);

    $output = shell_exec($cmd);

    if ($origLimit) @set_time_limit((int)$origLimit);

    if ($output === null || $output === '') {
        return ['success' => false, 'message' => '调用 extract.py 无输出（可能 shell_exec 被 php.ini disable_functions 禁用）'];
    }
    $data = json_decode($output, true);
    if (!is_array($data)) {
        return ['success' => false, 'message' => 'extract.py 输出非 JSON: ' . mb_substr($output, 0, 200)];
    }
    return $data;
}

/**
 * 探测可用 Python 二进制
 * 优先级：环境变量 PYTHON_BIN > python > python3
 * 结果在单次请求内缓存
 */
function getPythonBinary(): ?string {
    static $cached = null;
    if ($cached !== null) return $cached === '' ? null : $cached;

    $env = getenv('PYTHON_BIN');
    if ($env && isPythonBinaryReady($env)) {
        $cached = $env;
        return $cached;
    }
    foreach (['python', 'python3'] as $c) {
        if (isPythonBinaryReady($c)) {
            $cached = $c;
            return $cached;
        }
    }
    $cached = '';
    return null;
}

function isPythonBinaryReady(string $bin): bool {
    $test = @shell_exec(escapeshellarg($bin) . ' --version 2>&1');
    return $test !== null && stripos($test, 'Python') !== false;
}

/**
 * 归一化 extract.py 输出为扁平数组（与 import_questions_json 兼容）
 * 缺省字段按 importNormalizeFlat() 的同款规则补齐
 */
function normalizeExtractedQuestions(array $questions, string $subjectOverride, string $fileName, string $rawText): array {
    $subject = $subjectOverride !== '' ? $subjectOverride : detectSubjectFromName($fileName, $rawText);
    $flat = [];
    foreach ($questions as $q) {
        $content = $q['content'] ?? '';
        if (trim($content) === '') continue;
        $flat[] = [
            'subject'        => $subject,
            'question_type'  => $q['question_type'] ?? 'single',
            'content'        => $content,
            'options'        => $q['options'] ?? null,
            'correct_answer' => $q['correct_answer'] ?? '',
            'explanation'    => null,
            'difficulty'     => $q['difficulty'] ?? importEstimateDifficulty($content),
            'points'         => (float)($q['points'] ?? 2.0),
        ];
    }
    return $flat;
}

/**
 * 从文件名 + 文本片段识别科目（与 import_questions_json 共用同一份 subjectMap）
 */
function detectSubjectFromName(string $fileName, string $rawText = ''): string {
    $subjectMap = [
        '生物' => '生物', '化学' => '化学', '物理' => '物理', '数学' => '数学',
        '英语' => '英语', '语文' => '语文', '历史' => '历史', '地理' => '地理',
        '政治' => '政治', '信息技术' => '信息技术', '计算机' => '信息技术',
    ];
    $haystack = $fileName . ' ' . mb_substr($rawText, 0, 500);
    foreach ($subjectMap as $k => $v) {
        if (str_contains($haystack, $k)) return $v;
    }
    return '综合';
}
?>