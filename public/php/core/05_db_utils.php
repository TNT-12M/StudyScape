function tableHasColumn(SQLite3 $db, string $table, string $column): bool {
    $result = $db->query("PRAGMA table_info(" . preg_replace('/[^A-Za-z0-9_]/', '', $table) . ")");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (strcasecmp($row['name'], $column) === 0) return true;
    }
    return false;
}
try {
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    if (!tableHasColumn($db, 'users', 'role')) {
        $db->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'user'");
    }
    if (!tableHasColumn($db, 'users', 'is_initial_root')) {
        $db->exec("ALTER TABLE users ADD COLUMN is_initial_root INTEGER NOT NULL DEFAULT 0");
    }
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

    // ===== 资料分类 =====
    $db->exec("CREATE TABLE IF NOT EXISTS material_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        education_level TEXT NOT NULL DEFAULT 'junior',
        sort_order INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    if (!tableHasColumn($db, 'materials', 'category_id')) {
        $db->exec("ALTER TABLE materials ADD COLUMN category_id INTEGER DEFAULT NULL");
    }
    $db->exec("CREATE INDEX IF NOT EXISTS idx_materials_category ON materials(category_id)");
    // 默认分类
    $catCount = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM material_categories")['c'];
    if ($catCount === 0) {
        dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", ['试卷', 'junior', 1]);
        dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", ['讲义', 'junior', 2]);
        dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", ['练习题', 'junior', 3]);
        dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", ['知识点总结', 'junior', 4]);
        dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", ['试卷', 'senior', 1]);
        dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", ['讲义', 'senior', 2]);
        dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", ['练习题', 'senior', 3]);
        dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", ['知识点总结', 'senior', 4]);
    }
    // 资料需求反馈表
    $db->exec("CREATE TABLE IF NOT EXISTS material_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL DEFAULT '',
        content TEXT NOT NULL,
        education_level TEXT DEFAULT '',
        subject TEXT DEFAULT '',
        contact TEXT DEFAULT '',
        status TEXT NOT NULL DEFAULT 'pending',
        admin_note TEXT,
        handled_by INTEGER,
        handled_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_material_requests_status ON material_requests(status, created_at DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_material_requests_user ON material_requests(user_id, created_at DESC)");
} catch (Exception $e) {
    // ROLLBACK 自身可能因无活跃事务而抛异常（enableExceptions(true) 时 @ 无法抑制），用内部 try-catch 兜底，避免掩盖原始错误
    try { $db->exec('ROLLBACK'); } catch (Exception $rb) {}
    error_log('education_level migration failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => '学段字段迁移失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// 用户资料迁移：兼容已有 SQLite 数据库，旧用户资料保持为空。
try {
    $profileColumns = [
        'nickname' => 'TEXT DEFAULT NULL',
        'avatar' => 'TEXT DEFAULT NULL',
        'gender' => "TEXT DEFAULT 'secret'",
        'grade' => 'TEXT DEFAULT NULL',
    ];
    foreach ($profileColumns as $column => $definition) {
        if (!tableHasColumn($db, 'users', $column)) {
            $db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
        }
    }
    $db->exec("UPDATE users SET gender='secret' WHERE gender IS NULL OR gender NOT IN ('secret','male','female')");
    $migration = dbFetchOne($db, "SELECT version FROM schema_migrations WHERE version='user_profile_v1'");
    if (!$migration) {
        $db->exec("INSERT INTO schema_migrations(version) VALUES('user_profile_v1')");
    }
} catch (Throwable $e) {
    error_log('user profile migration failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => '用户资料字段迁移失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// 角色、初始 root、反馈回复与通知迁移。旧 is_admin=1 账号兼容迁移为 root。
try {
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    // 反馈表可能是旧库中尚未初始化的表，先建立基础结构再补充回复字段。
    $db->exec("CREATE TABLE IF NOT EXISTS user_feedback (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        contact TEXT DEFAULT '',
        status TEXT NOT NULL DEFAULT 'open',
        admin_note TEXT,
        handled_by INTEGER,
        handled_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    if (!tableHasColumn($db, 'user_feedback', 'reply_content')) {
        $db->exec("ALTER TABLE user_feedback ADD COLUMN reply_content TEXT");
    }
    if (!tableHasColumn($db, 'user_feedback', 'replied_by')) {
        $db->exec("ALTER TABLE user_feedback ADD COLUMN replied_by INTEGER");
    }
    if (!tableHasColumn($db, 'user_feedback', 'replied_at')) {
        $db->exec("ALTER TABLE user_feedback ADD COLUMN replied_at DATETIME");
    }
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        type TEXT NOT NULL DEFAULT 'system',
        title TEXT NOT NULL,
        body TEXT NOT NULL,
        related_id INTEGER,
        read_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, read_at, id DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_feedback_user_date ON user_feedback(user_id, created_at)");

    // ---------- 公告表 ----------
    $db->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL DEFAULT '',
        is_published INTEGER NOT NULL DEFAULT 0,
        published_at DATETIME,
        created_by INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_announcements_published ON announcements(is_published, published_at DESC)");

    // ---------- 公告已读记录表 ----------
    // 公告是全局的，不每人插一条通知。通过此表记录用户读过哪些公告。
    $db->exec("CREATE TABLE IF NOT EXISTS announcement_reads (
        user_id INTEGER NOT NULL,
        announcement_id INTEGER NOT NULL,
        read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, announcement_id)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_announcement_reads_user ON announcement_reads(user_id, read_at)");

    // ---------- 用户名修改记录表 ----------
    // 每月最多 5 次，防止恶意刷用户名占用
    $db->exec("CREATE TABLE IF NOT EXISTS username_change_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        old_username TEXT NOT NULL,
        new_username TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_username_change_user ON username_change_log(user_id, created_at DESC)");
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_username_change_old ON username_change_log(old_username, created_at)");

    $migration = dbFetchOne($db, "SELECT version FROM schema_migrations WHERE version='roles_password_feedback_v1'");
    if (!$migration) {
        $db->exec('BEGIN IMMEDIATE');
        $db->exec("UPDATE users SET role='root', is_admin=1 WHERE is_admin=1 OR role='root'");
        $db->exec("UPDATE users SET role='user' WHERE role IS NULL OR role NOT IN ('user','content_admin','root')");
        $lian = dbFetchOne($db, "SELECT id FROM users WHERE username='lian' LIMIT 1");
        if ($lian) {
            $emailOwner = dbFetchOne($db, "SELECT id FROM users WHERE email=? AND id<>? LIMIT 1", ['44175149@qq.com', (int)$lian['id']]);
            if ($emailOwner) {
                dbQuery($db, "UPDATE users SET email=? WHERE id=?", ['legacy_' . (int)$emailOwner['id'] . '@system.local', (int)$emailOwner['id']]);
            }
            dbQuery($db, "UPDATE users SET email=?, role='root', is_initial_root=1, is_admin=1, is_approved=1, is_active=1 WHERE id=?", ['44175149@qq.com', (int)$lian['id']]);
        } else {
            // 按安装约定：只要初始 lian 不存在，就自动补建为 root。
            dbQuery($db, "INSERT INTO users(username,email,password,role,is_initial_root,is_admin,is_approved,is_active) VALUES(?,?,?,?,1,1,1,1)", ['lian', '44175149@qq.com', encryptPassword('lian120208'), 'root']);
        }
        $db->exec("INSERT INTO schema_migrations(version) VALUES('roles_password_feedback_v1')");
        $db->exec('COMMIT');
    }
    $db->exec("UPDATE users SET role='root', is_admin=1, is_initial_root=1 WHERE username='lian'");
    $db->exec("UPDATE users SET role='root' WHERE is_admin=1 AND role='user'");
    // 历史数据库中的裸密码只在启动迁移时读取一次，之后统一改写为密文。
    $legacyUsers = dbFetchAll($db, "SELECT id, password FROM users WHERE password NOT LIKE 'enc:v1:%'");
    if ($legacyUsers) {
        $db->exec('BEGIN IMMEDIATE');
        foreach ($legacyUsers as $legacyUser) {
            dbQuery($db, "UPDATE users SET password=? WHERE id=?", [encryptPassword((string)$legacyUser['password']), (int)$legacyUser['id']]);
        }
        $db->exec('COMMIT');
    }
} catch (Throwable $e) {
    try { $db->exec('ROLLBACK'); } catch (Throwable $ignored) {}
    error_log('roles/feedback migration failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => '角色迁移失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// ==========================================================
// 邮箱验证字段与验证码表迁移
// ==========================================================
try {
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $migration = dbFetchOne($db, "SELECT version FROM schema_migrations WHERE version='email_verification_v1'");
    if (!$migration) {
        $db->exec('BEGIN');

        // 给 users 表加 email_verified 字段
        $cols = @dbFetchAll($db, "PRAGMA table_info(users)");
        $hasCol = false;
        foreach ($cols as $c) { if ($c['name'] === 'email_verified') { $hasCol = true; break; } }
        if (!$hasCol) {
            $db->exec("ALTER TABLE users ADD COLUMN email_verified INTEGER NOT NULL DEFAULT 0");
            // 已存在的用户默认标记为已验证（历史兼容）
            $db->exec("UPDATE users SET email_verified=1 WHERE email IS NOT NULL AND email != ''");
        }

        // 创建邮箱验证码表
        $db->exec("CREATE TABLE IF NOT EXISTS email_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            code TEXT NOT NULL,
            purpose TEXT NOT NULL,
            ip TEXT NOT NULL DEFAULT '',
            created_at INTEGER NOT NULL,
            expires_at INTEGER NOT NULL,
            used INTEGER NOT NULL DEFAULT 0
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_email_codes_email ON email_codes(email)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_email_codes_purpose ON email_codes(purpose)");

        $db->exec("INSERT INTO schema_migrations(version) VALUES('email_verification_v1')");
        $db->exec('COMMIT');
    }
} catch (Throwable $e) {
    try { $db->exec('ROLLBACK'); } catch (Throwable $ignored) {}
    error_log('email verification migration failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => '邮箱验证迁移失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// ==========================================================
// security_v1 迁移：登录 IP 记录 + 安全 IP 表
// ==========================================================
try {
    $migration = dbFetchOne($db, "SELECT version FROM schema_migrations WHERE version='security_v1'");
    if (!$migration) {
        $db->exec('BEGIN');

        // 给 users 表加 last_login_ip / last_login_time 字段
        $cols = @dbFetchAll($db, "PRAGMA table_info(users)");
        $hasIp = false; $hasTime = false;
        foreach ($cols as $c) {
            if ($c['name'] === 'last_login_ip') $hasIp = true;
            if ($c['name'] === 'last_login_time') $hasTime = true;
        }
        if (!$hasIp) {
            $db->exec("ALTER TABLE users ADD COLUMN last_login_ip TEXT NOT NULL DEFAULT ''");
        }
        if (!$hasTime) {
            $db->exec("ALTER TABLE users ADD COLUMN last_login_time TEXT DEFAULT ''");
        }

        // 安全 IP 表（异常/可疑 IP）
        $db->exec("CREATE TABLE IF NOT EXISTS security_ips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL UNIQUE,
            api_count INTEGER NOT NULL DEFAULT 0,
            scan_count INTEGER NOT NULL DEFAULT 0,
            total_count INTEGER NOT NULL DEFAULT 0,
            status_404 INTEGER NOT NULL DEFAULT 0,
            risk_level TEXT NOT NULL DEFAULT 'medium',
            location TEXT NOT NULL DEFAULT '',
            last_seen TEXT,
            first_detected TEXT,
            updated_at TEXT,
            notified INTEGER NOT NULL DEFAULT 0
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_security_ips_risk ON security_ips(risk_level)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_security_ips_last_seen ON security_ips(last_seen)");

        // 安全扫描状态表（记录上次扫描时间等）
        $db->exec("CREATE TABLE IF NOT EXISTS security_scan_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            scan_type TEXT NOT NULL DEFAULT 'nginx_log',
            started_at TEXT,
            finished_at TEXT,
            status TEXT NOT NULL DEFAULT 'idle',
            result_info TEXT NOT NULL DEFAULT '',
            total_ip_count INTEGER NOT NULL DEFAULT 0,
            abnormal_ip_count INTEGER NOT NULL DEFAULT 0
        )");

        $db->exec("INSERT INTO schema_migrations(version) VALUES('security_v1')");
        $db->exec('COMMIT');
    }
} catch (Throwable $e) {
    try { $db->exec('ROLLBACK'); } catch (Throwable $ignored) {}
    error_log('security migration failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => '安全模块迁移失败'], JSON_UNESCAPED_UNICODE));
}

// ==========================================================
// security_v2 迁移：漏桶场景检测 + scenarios 字段
// ==========================================================
try {
    $migration = dbFetchOne($db, "SELECT version FROM schema_migrations WHERE version='security_v2'");
    if (!$migration) {
        $db->exec('BEGIN');

        // 给 security_ips 加 scenarios 字段（JSON 格式，存储触发的场景ID列表）
        $db->exec("ALTER TABLE security_ips ADD COLUMN scenarios TEXT NOT NULL DEFAULT ''");

        $db->exec("INSERT INTO schema_migrations(version) VALUES('security_v2')");
        $db->exec('COMMIT');
    }
} catch (Throwable $e) {
    try { $db->exec('ROLLBACK'); } catch (Throwable $ignored) {}
    error_log('security_v2 migration failed: ' . $e->getMessage());
    die(json_encode(['success' => false, 'message' => '安全场景迁移失败'], JSON_UNESCAPED_UNICODE));
}

// ===== 伪 Cron 触发：Nginx 日志安全扫描（每 30 分钟一次） =====
// 迁移完成后才触发，确保表已存在
try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    if ($action !== 'captcha' && $action !== 'static' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
        @triggerSecurityScan(false);
    }
} catch (Throwable $ignored) {
    // 扫描失败不影响主流程
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

// ==========================================================
// 平台访问统计与用户反馈（独立表，不改变既有业务表）
// ==========================================================
try {
    $db->exec("CREATE TABLE IF NOT EXISTS site_visit_daily (
        stat_date TEXT PRIMARY KEY,
        uv_count INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS site_visit_daily_visitors (
        stat_date TEXT NOT NULL,
        visitor_hash TEXT NOT NULL,
        first_seen_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (stat_date, visitor_hash)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_site_visit_visitors_date ON site_visit_daily_visitors(stat_date)");
    $db->exec("CREATE TABLE IF NOT EXISTS site_visit_rate_limits (
        bucket_key TEXT PRIMARY KEY,
        window_start INTEGER NOT NULL,
        request_count INTEGER NOT NULL DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS user_feedback (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        contact TEXT DEFAULT '',
        status TEXT NOT NULL DEFAULT 'open',
        admin_note TEXT,
        handled_by INTEGER,
        handled_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_feedback_status_created ON user_feedback(status, created_at DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_feedback_user_created ON user_feedback(user_id, created_at DESC)");
} catch (Exception $e) {
    error_log('site statistics tables init failed: ' . $e->getMessage());
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS ocr_import_batches (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        file_name TEXT NOT NULL,
        stored_name TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'queued',
        subject TEXT NOT NULL DEFAULT '',
        education_level TEXT NOT NULL DEFAULT 'junior',
        result_json TEXT,
        error_message TEXT,
        bridge_job_id TEXT,
        created_by INTEGER NOT NULL,
        retry_count INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        finished_at DATETIME
    )");
    // ALTER TABLE 兼容: 老库补 bridge_job_id
    try { $db->exec('ALTER TABLE ocr_import_batches ADD COLUMN bridge_job_id TEXT'); } catch (Throwable $ignored) {}
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ocr_batches_owner ON ocr_import_batches(created_by, id DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ocr_batches_status ON ocr_import_batches(status, updated_at)");
} catch (Exception $e) {
    error_log('OCR batch table init failed: ' . $e->getMessage());
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
