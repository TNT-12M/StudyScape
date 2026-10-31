        switch ($action) {
            // ==================== 找回密码: 邮箱 → 用户名自动匹配 ====================
            case 'lookup_reset_email':
                $email = sanitizeInput($_POST['email'] ?? '');
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));
                if (!validateEmail($email)) jsonOut(false, '邮箱格式不正确');
                if (empty($captcha)) jsonOut(false, '请填写图片验证码');
                // 图片验证码 (和 send_email_code 共用)
                if (!isset($_SESSION['captcha_code']) || $_SESSION['captcha_code'] !== $captcha) {
                    unset($_SESSION['captcha_code']);
                    jsonOut(false, '图片验证码错误');
                }
                unset($_SESSION['captcha_code']);
                $u = dbFetchOne($db, 'SELECT username, nickname FROM users WHERE email=?', [$email]);
                if (!$u) jsonOut(false, '该邮箱未注册，请确认后再试');
                jsonOut(true, '请确认是该账号', [
                    'username' => $u['username'],
                    'nickname' => $u['nickname'] ?? '',
                ]);

            // ==================== 发送邮箱验证码 ====================
            case 'send_email_code':
                $email = sanitizeInput($_POST['email'] ?? '');
                $purpose = sanitizeInput($_POST['purpose'] ?? 'register'); // register | reset
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));
                $ip = getClientIp();

                // --- 第一层：基础格式 ---
                if (!in_array($purpose, ['register', 'reset'], true)) {
                    jsonOut(false, '非法用途');
                }
                if (empty($email)) {
                    jsonOut(false, '请填写邮箱');
                }
                if ($purpose === 'register' && empty($captcha)) {
                    jsonOut(false, '请填写图片验证码');
                }
                if (!validateEmail($email)) {
                    jsonOut(false, '邮箱格式不正确');
                }

                // --- 第二层：IP 级总频率（captcha 过了再查，省资源） ---
                if (!checkEmailIpRate($ip)) {
                    jsonOut(false, '请求过于频繁，请稍后再试');
                }

                // --- 第三层：captcha 校验 (reset 流程已在 lookup_reset_email 消耗过 captcha, 这里跳过) ---
                if ($purpose === 'register') {
                    $captchaExpired = empty($_SESSION['captcha_expires']) || (int)$_SESSION['captcha_expires'] < time();
                    $captchaOk = !$captchaExpired && isset($_SESSION['captcha_code']) && hash_equals((string)$_SESSION['captcha_code'], $captcha);
                    unset($_SESSION['captcha_code'], $_SESSION['captcha_expires']);
                    if (!$captchaOk) {
                        jsonOut(false, '图片验证码错误或已过期');
                    }
                }

                // --- 第四层：per-IP 去重邮箱检查（防轮换伪造邮箱绕过 per-email 冷却） ---
                $div = checkEmailIpDiversity($ip, $email);
                if (!$div['ok']) {
                    error_log("send_email_code blocked by diversity limit: ip={$ip} count={$div['unique_count']}");
                    jsonOut(false, '当前 IP 在短时间内尝试注册过多不同邮箱，请稍后再试');
                }

                // --- 第五层：reset 用途专属检查 ---
                if ($purpose === 'reset') {
                    // 不枚举邮箱（统一返回成功，但月度限流仍然生效）
                    if (!canResetPassword($email)) {
                        jsonOut(false, '该邮箱本月重置密码次数已达上限（5次），请下月再试');
                    }
                }

                // --- 第六层：register 用途专属检查 ---
                if ($purpose === 'register') {
                    $exist = dbFetchOne($db, "SELECT id FROM users WHERE email=?", [$email]);
                    if ($exist) {
                        jsonOut(false, '该邮箱已被注册');
                    }
                    if (!canSendRegisterEmailCode($email)) {
                        jsonOut(false, '该邮箱本月注册验证码发送次数已达上限（10次），请下月再试');
                    }
                }

                // --- 第七层：邮箱每日上限 ---
                $daily = canSendEmailCodeDaily($email, $purpose);
                if (!$daily['ok']) {
                    jsonOut(false, '该邮箱今日验证码发送次数已达上限，请明日再试');
                }

                // --- 第八层：60 秒冷却 ---
                if (!canSendEmailCode($email, $purpose)) {
                    jsonOut(false, '验证码发送太频繁，请 60 秒后再试');
                }

                // 生成验证码并发送
                $code = generateEmailCode();
                $purposeText = $purpose === 'register' ? '注册账号' : '重置密码';
                $subject = "【学境StudyScape】{$purposeText}验证码";
                $body = "尊敬的用户，您好！\n\n"
                    . "您正在学境 StudyScape 进行「{$purposeText}」操作。\n"
                    . "您的验证码为： {$code}\n\n"
                    . "验证码 5 分钟内有效，为了您的账号安全，请勿将验证码告知他人。\n"
                    . "如非本人操作，请忽略此邮件，您的账号不会受到任何影响。\n\n"
                    . "此邮件由系统自动发送，请勿直接回复。\n\n"
                    . "学境 StudyScape 团队\n"
                    . date('Y年m月d日');

                [$ok, $err] = smtpSendMail($email, $subject, $body);
                if (!$ok) {
                    error_log("SMTP 发送失败 [{$email}]: {$err}");
                    jsonOut(false, '邮件发送失败，请稍后重试');
                }

                saveEmailCode($email, $code, $purpose);
                jsonOut(true, '验证码已发送，请注意查收');
                break;

            // ==================== 用户注册 ====================
            case 'register':
                $username = validateUsername((string)($_POST['username'] ?? ''));
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));
                $emailCode = trim($_POST['email_code'] ?? '');

                if (empty($username) || empty($email) || empty($password) || empty($captcha) || empty($emailCode)) {
                    jsonOut(false, "请完整填写注册信息+验证码");
                }
                
                if (!isset($_SESSION['captcha_code']) || $_SESSION['captcha_code'] !== $captcha) {
                    unset($_SESSION['captcha_code']);
                    jsonOut(false, "图片验证码错误，请刷新");
                }
                unset($_SESSION['captcha_code']);

                if (!validateEmail($email)) {
                    jsonOut(false, "邮箱格式不正确");
                }

                $exist = dbFetchOne($db, "SELECT id FROM users WHERE username=? OR email=?", [$username, $email]);
                if ($exist) {
                    jsonOut(false, "用户名或邮箱已被注册");
                }

                // 校验邮箱验证码（验证码只可使用一次，校验成功即标记已用）
                if (!verifyEmailCode($email, $emailCode, 'register')) {
                    jsonOut(false, "邮箱验证码错误或已过期");
                }

                // 初始 root 已在启动迁移阶段固定创建为 lian，普通注册不再自动升权。
                dbQuery($db, "INSERT INTO users(username, email, password, role, is_initial_root, is_approved, is_admin, is_active, email_verified, last_login_ip, last_login_time) VALUES(?, ?, ?, 'user', 0, 1, 0, 1, 1, ?, ?)",
                    [$username, $email, encryptPassword($password), $_SERVER['REMOTE_ADDR'] ?? '', date('Y-m-d H:i:s')]);
                jsonOut(true, "注册成功，可直接登录");
                break;

            // ==================== 用户登录（P0-A3+A4：登录限流+会话再生） ====================
            case 'login':
                // 先限流（统一错误信息，不枚举）
                if (!checkLoginThrottle()) {
                    recordLoginFail(); // 记一次，避免重置计时
                    $status = getLoginThrottleStatus();
                    $mins = (int)ceil($status['lock_left'] / 60);
                    jsonOut(false, "登录尝试次数过多，请 {$mins} 分钟后再试", [
                        'throttle' => $status,
                    ]);
                }
                $username = trim((string)($_POST['username'] ?? ''));
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    recordLoginFail();
                    $status = getLoginThrottleStatus();
                    jsonOut(false, "用户名或密码错误，还可尝试 {$status['remaining']} 次", [
                        'throttle' => $status,
                    ]);
                }
                // 快速校验用户名格式（失败也算一次错误尝试，防止枚举）
                // 允许中英文、数字、下划线、emoji，以及邮箱登录的 @ 和 .
                if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_@.\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]+$/u', $username)) {
                    recordLoginFail();
                    $status = getLoginThrottleStatus();
                    $msg = $status['locked']
                        ? "登录尝试次数过多，请 " . (int)ceil($status['lock_left'] / 60) . " 分钟后再试"
                        : "用户名或密码错误，还可尝试 {$status['remaining']} 次";
                    jsonOut(false, $msg, ['throttle' => $status]);
                }
                
                $u = dbFetchOne($db, "SELECT * FROM users WHERE username=?", [$username]);
                
                if (!$u || !passwordMatches((string)$u['password'], $password)) {
                    recordLoginFail();
                    $status = getLoginThrottleStatus();
                    $msg = $status['locked']
                        ? "登录尝试次数过多，请 " . (int)ceil($status['lock_left'] / 60) . " 分钟后再试"
                        : "用户名或密码错误，还可尝试 {$status['remaining']} 次";
                    jsonOut(false, $msg, ['throttle' => $status]);
                }
                // 兼容历史明文账号：验证成功后立即改写为密文。
                if (!str_starts_with((string)$u['password'], 'enc:v1:')) {
                    dbQuery($db, "UPDATE users SET password=? WHERE id=?", [encryptPassword($password), (int)$u['id']]);
                }

                if (!$u['is_active']) {
                    recordLoginFail();
                    $status = getLoginThrottleStatus();
                    jsonOut(false, "账号已被禁用，请联系管理员", ['throttle' => $status]);
                }

                // 注册审批已取消：历史账号即使保存为未审批状态，也不再阻止登录或自动升权。
                // 账号是否可用仅由 is_active 控制，管理员身份仅由 is_admin 控制。

                // 登录成功：会话再生 + 清限流 + 写 session
                session_regenerate_id(true);
                clearLoginFail();

                $_SESSION['user_id'] = $u['id'];
                $_SESSION['username'] = $u['username'];
                $_SESSION['role'] = $u['username'] === 'lian' ? 'root' : ($u['role'] ?? ((int)$u['is_admin'] ? 'root' : 'user'));
                $_SESSION['is_admin'] = in_array($_SESSION['role'], ['root', 'content_admin'], true);
                $_SESSION['is_approved'] = (bool)$u['is_approved'];

                // 管理员登录时生成会话指纹，绑定 UA+IP，防止 Session 劫持/伪造
                if ($_SESSION['is_admin']) {
                    $_SESSION['admin_fp'] = generateAdminFingerprint();
                }
                
                dbQuery($db, "UPDATE users SET last_login=CURRENT_TIMESTAMP, last_login_ip=?, last_login_time=? WHERE id=?", [
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    date('Y-m-d H:i:s'),
                    $u['id']
                ]);

                jsonOut(true, "登录成功", [
                    'user' => [
                        'id' => $u['id'],
                        'username' => $u['username'],
                        'email' => $u['email'],
                        'nickname' => $u['nickname'] ?? null,
                        'avatar' => $u['avatar'] ?? null,
                        'gender' => $u['gender'] ?? 'secret',
                        'grade' => $u['grade'] ?? null,
                        'role' => $_SESSION['role'],
                        'is_initial_root' => (bool)($u['is_initial_root'] ?? false),
                        'is_admin' => in_array($_SESSION['role'], ['root', 'content_admin'], true),
                        'is_approved' => (bool)$u['is_approved']
                    ]
                ]);
                break;

            // ==================== 重置密码 ====================
            case 'reset_password':
                $email = sanitizeInput($_POST['email'] ?? '');
                $emailCode = trim($_POST['email_code'] ?? '');
                $newPassword = $_POST['new_password'] ?? '';
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));

                if (empty($email) || empty($emailCode) || empty($newPassword) || empty($captcha)) {
                    jsonOut(false, '请完整填写所有信息');
                }

                // 先过图片验证码
                if (!isset($_SESSION['captcha_code']) || $_SESSION['captcha_code'] !== $captcha) {
                    unset($_SESSION['captcha_code']);
                    jsonOut(false, '图片验证码错误');
                }
                unset($_SESSION['captcha_code']);

                if (!validateEmail($email)) {
                    jsonOut(false, '邮箱格式不正确');
                }

                // 查找用户：仅靠邮箱（前端已自动匹配显示用户名）
                $u = dbFetchOne($db, "SELECT * FROM users WHERE email=?", [$email]);
                if (!$u) {
                    // 统一提示，避免枚举
                    jsonOut(false, '该邮箱未注册');
                }

                // 校验邮箱验证码（一次性）
                if (!verifyEmailCode($email, $emailCode, 'reset')) {
                    jsonOut(false, '邮箱验证码错误或已过期');
                }

                // 密码强度校验（至少6位）
                if (strlen($newPassword) < 6) {
                    jsonOut(false, '密码长度不能少于 6 位');
                }

                // 更新密码
                dbQuery($db, "UPDATE users SET password=? WHERE id=?", [encryptPassword($newPassword), (int)$u['id']]);

                // 登出该用户所有会话（简单起见，只清除当前会话中的用户信息）
                jsonOut(true, '密码重置成功，请使用新密码登录');
                break;

            // ==================== 退出登录 ====================
            case 'logout':
                if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') jsonOut(false, '请使用 POST 退出登录');
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

                // 5) 题目按科目分布（用于饼图展示）
                $rows = dbFetchAll($db, "SELECT subject, COUNT(*) AS c FROM questions WHERE subject IS NOT NULL AND subject<>'' GROUP BY subject ORDER BY c DESC");
                $bySubject = array_map(function($r){
                    return ['subject' => $r['subject'] ?: '未分类', 'count' => (int)$r['c']];
                }, $rows);

                // 6) 资料按科目统计（可选，丰富展示）
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
                    'by_subject'       => $bySubject,
                    'subjects'         => $subjects,
                    'published_papers' => $papers,
                    'materials'        => $materials
                ]);
                break;

            // ==================== 检查会话 ====================
            case 'check_session':
                if (isLoggedIn()) {
                    $u = dbFetchOne($db, "SELECT id, username, email, nickname, avatar, gender, grade, role, is_initial_root, is_admin, is_approved, is_active, created_at, last_login FROM users WHERE id=?", [getUid()]);
                    if ($u && (int)$u['is_active']) {
                        $u['role'] = $u['username'] === 'lian' ? 'root' : ($u['role'] ?? ((int)$u['is_admin'] ? 'root' : 'user'));
                        $u['is_initial_root'] = $u['username'] === 'lian' ? 1 : (int)($u['is_initial_root'] ?? 0);
                        $u['is_admin'] = in_array($u['role'], ['root', 'content_admin'], true) ? 1 : 0;
                        $_SESSION['username'] = $u['username'];
                        $_SESSION['role'] = $u['role'];
                        $_SESSION['is_admin'] = (bool)$u['is_admin'];
                        $_SESSION['is_approved'] = (bool)$u['is_approved'];
                        // 管理员会话必须通过指纹校验，否则撤销管理员状态（防止 Session 伪造）
                        if ((bool)$u['is_admin'] && !verifyAdminFingerprint()) {
                            $_SESSION['is_admin'] = false;
                            $_SESSION['role'] = 'user';
                            $u['is_admin'] = 0;
                            $u['role'] = 'user';
                        }
                        jsonOut(true, "", ['user' => formatUserTimestamps($u)]);
                    }
                    invalidateSession();
                }
                jsonOut(false, "未登录");
                break;

            // ==================== 更新当前用户资料 ====================
            case 'update_profile':
                if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') jsonOut(false, '请使用 POST 更新资料');
                if (!isLoggedIn()) jsonOut(false, '请先登录');
                $uid = (int)getUid();
                $nickname = profileNickname((string)($_POST['nickname'] ?? ''));
                $avatar = profileAvatar((string)($_POST['avatar'] ?? ''));
                $gender = profileGender((string)($_POST['gender'] ?? 'secret'));
                $grade = profileGrade((string)($_POST['grade'] ?? ''));

                // 用户名修改（可选）
                $newUsername = trim((string)($_POST['username'] ?? ''));
                $currentUser = dbFetchOne($db, 'SELECT username FROM users WHERE id=?', [$uid]);
                $usernameChanged = false;
                if ($newUsername !== '' && $currentUser && $newUsername !== $currentUser['username']) {
                    // root 账号用户名不可修改
                    if ($currentUser['username'] === 'lian') {
                        jsonOut(false, 'root 账号用户名不可修改');
                    }
                    // 校验格式
                    $newUsername = validateUsername($newUsername);
                    // 检查本月修改次数（自然月，最多 5 次）
                    [$monthStart, $monthEnd] = currentMonthUtcBounds();
                    $changeCount = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM username_change_log WHERE user_id=? AND created_at>=? AND created_at<?', [$uid, $monthStart, $monthEnd])['c'] ?? 0);
                    if ($changeCount >= 5) {
                        jsonOut(false, '本月修改用户名次数已达上限（每月最多 5 次），请下月再试');
                    }
                    // 检查重名
                    $exist = dbFetchOne($db, 'SELECT id FROM users WHERE username=?', [$newUsername]);
                    if ($exist) {
                        jsonOut(false, '该用户名已被占用');
                    }
                    // 执行修改 + 记录日志
                    $db->exec('BEGIN IMMEDIATE');
                    try {
                        dbQuery($db, 'UPDATE users SET username=? WHERE id=?', [$newUsername, $uid]);
                        dbQuery($db, 'INSERT INTO username_change_log(user_id, old_username, new_username) VALUES(?,?,?)', [$uid, $currentUser['username'], $newUsername]);
                        $db->exec('COMMIT');
                        $usernameChanged = true;
                    } catch (Throwable $e) {
                        @$db->exec('ROLLBACK');
                        jsonOut(false, '用户名修改失败：' . $e->getMessage());
                    }
                }

                // nickname 为 null 表示前端没传 (不再改昵称), 保持原值
                $setParts = ['avatar=?', 'gender=?', 'grade=?'];
                $params = [$avatar, $gender, $grade];
                if ($nickname !== null) {
                    $setParts[] = 'nickname=?';
                    $params[] = $nickname;
                }
                $params[] = $uid;
                dbQuery($db, 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id=?', $params);
                $updated = dbFetchOne($db, "SELECT id, username, email, nickname, avatar, gender, grade, role, is_initial_root, is_admin, is_approved, is_active, created_at, last_login FROM users WHERE id=?", [$uid]);
                if (!$updated || !(int)$updated['is_active']) jsonOut(false, '用户资料更新失败');
                $updated['role'] = $updated['username'] === 'lian' ? 'root' : ($updated['role'] ?? ((int)$updated['is_admin'] ? 'root' : 'user'));
                $updated['is_initial_root'] = $updated['username'] === 'lian' ? 1 : (int)($updated['is_initial_root'] ?? 0);
                $updated['is_admin'] = in_array($updated['role'], ['root', 'content_admin'], true) ? 1 : 0;
                $_SESSION['username'] = $updated['username'];
                $_SESSION['role'] = $updated['role'];
                $_SESSION['is_admin'] = (bool)$updated['is_admin'];
                $_SESSION['is_approved'] = (bool)$updated['is_approved'];
                // 返回本月剩余修改次数
                [$monthStart, $monthEnd] = currentMonthUtcBounds();
                $changeCount = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM username_change_log WHERE user_id=? AND created_at>=? AND created_at<?', [$uid, $monthStart, $monthEnd])['c'] ?? 0);
                jsonOut(true, $usernameChanged ? '资料已保存，用户名已更新' : '资料已保存', [
                    'user' => formatUserTimestamps($updated),
                    'username_change_remaining' => max(0, 5 - $changeCount),
                ]);
                break;

            // ==================== 修改当前用户密码 ====================
            case 'change_password':
                if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') jsonOut(false, '请使用 POST');
                if (!isLoggedIn()) jsonOut(false, '请先登录');
                $uid = (int)getUid();
                $oldPassword = (string)($_POST['old_password'] ?? '');
                $newPassword = (string)($_POST['new_password'] ?? '');
                $confirmPassword = (string)($_POST['confirm_password'] ?? '');

                if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                    jsonOut(false, '请完整填写所有密码字段');
                }
                if ($newPassword !== $confirmPassword) {
                    jsonOut(false, '两次输入的新密码不一致');
                }
                if (strlen($newPassword) < 6) {
                    jsonOut(false, '新密码至少 6 位');
                }
                if ($newPassword === $oldPassword) {
                    jsonOut(false, '新密码不能与旧密码相同');
                }

                // 验证旧密码
                $userRow = dbFetchOne($db, 'SELECT password, username FROM users WHERE id=?', [$uid]);
                if (!$userRow) jsonOut(false, '用户不存在');
                $storedPwd = $userRow['password'] ?? '';
                $oldPwdValid = false;
                if (str_starts_with($storedPwd, 'enc:v1:')) {
                    $decrypted = @decryptPassword($storedPwd);
                    if ($decrypted !== false && $decrypted === $oldPassword) $oldPwdValid = true;
                } elseif ($storedPwd !== '') {
                    if ($storedPwd === $oldPassword) $oldPwdValid = true;
                }
                if (!$oldPwdValid) {
                    jsonOut(false, '旧密码不正确');
                }

                // 更新密码
                $encryptedNew = encryptPassword($newPassword);
                dbQuery($db, 'UPDATE users SET password=? WHERE id=?', [$encryptedNew, $uid]);

                // 修改密码后，重新生成 session id 并刷新管理员指纹
                session_regenerate_id(true);
                $_SESSION['user_id'] = $uid;
                if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin']) {
                    $_SESSION['admin_fp'] = generateAdminFingerprint();
                }

                jsonOut(true, '密码修改成功');
                break;

            // ==================== 用户控制台 ====================
            case 'get_dashboard':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = getUid();
                // 不返回 password 字段
                $user = dbFetchOne($db, "SELECT id, username, email, nickname, avatar, gender, grade, role, is_initial_root, is_admin, is_approved, is_active, created_at, last_login FROM users WHERE id=?", [$uid]);
                $rootDashboard = isRoot();
                $examSql = $rootDashboard
                    ? "SELECT json_content FROM exam_data WHERE type='exam'"
                    : "SELECT json_content FROM exam_data WHERE type='exam' AND json_valid(json_content) AND (json_extract(json_content, '$.user_id')=? OR json_extract(json_content, '$.user.id')=?)";
                $examList = dbFetchAll($db, $examSql, $rootDashboard ? [] : [$uid, $uid]);
                $examCount = count($examList);
                $resultSql = $rootDashboard
                    ? "SELECT json_content FROM exam_data WHERE type='result'"
                    : "SELECT json_content FROM exam_data WHERE type='result' AND json_valid(json_content) AND (json_extract(json_content, '$.user_id')=? OR json_extract(json_content, '$.user.id')=?)";
                $resList = dbFetchAll($db, $resultSql, $rootDashboard ? [] : [$uid, $uid]);
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

            // ==================== 用户反馈 ====================
            case 'feedback_submit':
                if (!isLoggedIn()) jsonOut(false, '请先登录');
                $uid = (int)getUid();
                $content = trim((string)($_POST['content'] ?? ''));
                $contact = trim((string)($_POST['contact'] ?? ''));
                // 过滤 HTML/PHP 标签，防止 XSS
                $content = strip_tags($content);
                $contact = strip_tags($contact);
                if ($content === '' || mb_strlen($content, 'UTF-8') < 2) jsonOut(false, '反馈内容至少 2 个字符');
                if (mb_strlen($content, 'UTF-8') > 300) jsonOut(false, '反馈内容不能超过 300 个字符');
                if (mb_strlen($contact, 'UTF-8') > 50) jsonOut(false, '联系方式不能超过 50 个字符');
                // 禁止纯特殊符号 / 控制字符
                if (preg_match('/[\\x00-\\x1F\\x7F]/u', $content)) jsonOut(false, '反馈内容包含非法字符');
                if (preg_match('/[\\x00-\\x1F\\x7F]/u', $contact)) jsonOut(false, '联系方式包含非法字符');
                $user = currentUser();
                [$dayStart, $dayEnd] = chinaTodayUtcBounds();
                try {
                    $db->exec('BEGIN IMMEDIATE');
                    if (!$user || !in_array($user['role'], ['root', 'content_admin'], true)) {
                        $count = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM user_feedback WHERE user_id=? AND created_at>=? AND created_at<?', [$uid, $dayStart, $dayEnd])['c'] ?? 0);
                        if ($count >= 2) {
                            $db->exec('ROLLBACK');
                            jsonOut(false, '每位用户每天最多提交 2 条反馈，请明天再试');
                        }
                    }
                    dbQuery($db, 'INSERT INTO user_feedback(user_id, content, contact) VALUES(?,?,?)', [$uid, $content, $contact]);
                    $feedbackId = (int)$db->lastInsertRowID();
                    $db->exec('COMMIT');
                } catch (Throwable $e) {
                    try { $db->exec('ROLLBACK'); } catch (Throwable $ignored) {}
                    jsonOut(false, '反馈提交失败：' . $e->getMessage());
                }
                jsonOut(true, '反馈已提交', ['id' => $feedbackId]);
                break;

            case 'feedback_list_mine':
                if (!isLoggedIn()) jsonOut(false, '请先登录');
                $uid = (int)getUid();
                $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
                $size = max(1, min(50, (int)($_POST['page_size'] ?? $_GET['page_size'] ?? 20)));
                $total = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM user_feedback WHERE user_id=?', [$uid])['c'] ?? 0);
                $items = dbFetchAll($db, 'SELECT id, content, contact, status, reply_content, replied_at, created_at, updated_at FROM user_feedback WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?', [$uid, $size, ($page - 1) * $size]);
                foreach ($items as &$item) {
                    foreach (['created_at', 'updated_at', 'replied_at'] as $field) if ($item[$field] !== null) $item[$field] = formatDbUtcTimestamp($item[$field]);
                }
                unset($item);
                jsonOut(true, '', ['items' => $items, 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total]]);
                break;

            case 'admin_feedback_list':
                $adminUid = requireRoot();
                $page = max(1, (int)($_POST['page'] ?? 1));
                $size = max(1, min(100, (int)($_POST['page_size'] ?? 20)));
                $status = trim((string)($_POST['status'] ?? ''));
                $keyword = trim((string)($_POST['keyword'] ?? ''));
                $where = ['1=1']; $args = [];
                if (in_array($status, ['open', 'processing', 'resolved', 'closed'], true)) { $where[] = 'f.status=?'; $args[] = $status; }
                if ($keyword !== '') { $where[] = '(f.content LIKE ? OR f.contact LIKE ? OR u.username LIKE ?)'; $like = '%' . $keyword . '%'; array_push($args, $like, $like, $like); }
                $whereSql = implode(' AND ', $where);
                $total = (int)(dbFetchOne($db, "SELECT COUNT(*) AS c FROM user_feedback f JOIN users u ON u.id=f.user_id WHERE $whereSql", $args)['c'] ?? 0);
                $items = dbFetchAll($db, "SELECT f.id, f.user_id, u.username, f.content, f.contact, f.status, f.admin_note, f.reply_content, f.replied_at, f.handled_by, f.handled_at, f.created_at, f.updated_at FROM user_feedback f LEFT JOIN users u ON u.id=f.user_id WHERE $whereSql ORDER BY f.id DESC LIMIT ? OFFSET ?", array_merge($args, [$size, ($page - 1) * $size]));
                foreach ($items as &$item) {
                    foreach (['created_at', 'updated_at', 'handled_at', 'replied_at'] as $field) if ($item[$field] !== null) $item[$field] = formatDbUtcTimestamp($item[$field]);
                }
                unset($item);
                log_admin_action('list_feedback', $adminUid);
                jsonOut(true, '', ['items' => $items, 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total]]);
                break;

            case 'admin_feedback_update':
                $adminUid = requireRoot();
                $id = (int)($_POST['id'] ?? 0);
                $actionName = trim((string)($_POST['feedback_action'] ?? $_POST['action_type'] ?? ''));
                $status = trim((string)($_POST['status'] ?? ''));
                $note = trim(strip_tags((string)($_POST['admin_note'] ?? '')));
                if ($id <= 0 || mb_strlen($note, 'UTF-8') > 500) jsonOut(false, '参数无效或备注过长');
                $feedback = dbFetchOne($db, 'SELECT id, user_id, status FROM user_feedback WHERE id=?', [$id]);
                if (!$feedback) jsonOut(false, '反馈不存在');
                if ($actionName === 'adopt') {
                    $status = 'resolved';
                    dbQuery($db, 'UPDATE user_feedback SET status=?, admin_note=?, reply_content=?, replied_by=?, replied_at=CURRENT_TIMESTAMP, handled_by=?, handled_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$status, $note, '感谢您的建议，我们已采纳。', $adminUid, $adminUid, $id]);
                } elseif ($actionName === 'ignore') {
                    $status = 'closed';
                    dbQuery($db, 'UPDATE user_feedback SET status=?, admin_note=?, reply_content=NULL, replied_by=NULL, replied_at=NULL, handled_by=?, handled_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$status, $note, $adminUid, $id]);
                } elseif ($actionName === 'reopen') {
                    $status = 'open';
                    dbQuery($db, 'UPDATE user_feedback SET status=?, reply_content=NULL, replied_by=NULL, replied_at=NULL, handled_by=?, handled_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$status, $adminUid, $id]);
                } elseif (in_array($status, ['open', 'closed'], true)) {
                    dbQuery($db, 'UPDATE user_feedback SET status=?, admin_note=?, handled_by=?, handled_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$status, $note, $adminUid, $id]);
                } elseif ($status === 'resolved') {
                    jsonOut(false, '请使用“采纳并回复”按钮完成采纳');
                } else {
                    jsonOut(false, '反馈操作无效');
                }
                log_admin_action('update_feedback_' . ($actionName ?: $status), $id);
                jsonOut(true, '反馈状态已更新');
                break;

            case 'notification_list':
            case 'notification_unread_count':
                if (!isLoggedIn()) jsonOut(false, '请先登录');
                $uid = (int)getUid();
                // 未读数 = 个人未读通知（排除旧的公告通知） + 未读的已发布公告
                $personalUnread = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM notifications WHERE user_id=? AND read_at IS NULL AND type <> ?', [$uid, 'announcement'])['c'] ?? 0);
                $announcementUnread = (int)(dbFetchOne($db, "
                    SELECT COUNT(*) AS c FROM announcements a
                    WHERE a.is_published = 1
                      AND NOT EXISTS (
                        SELECT 1 FROM announcement_reads ar
                        WHERE ar.user_id = ? AND ar.announcement_id = a.id
                      )
                ", [$uid])['c'] ?? 0);
                $unread = $personalUnread + $announcementUnread;
                if ($action === 'notification_unread_count') jsonOut(true, '', ['unread_count' => $unread]);

                $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
                $size = max(1, min(50, (int)($_POST['page_size'] ?? $_GET['page_size'] ?? 20)));

                // 合并个人通知和公告，用 UNION ALL + 外层排序分页
                // 注意：排除 notifications 里 type='announcement' 的旧数据
                // 公告统一从 announcements 表动态读取，避免重复显示
                $items = dbFetchAll($db, "
                    SELECT id, type, title, body, related_id, read_at, created_at
                    FROM (
                        SELECT id, type, title, body, related_id, read_at, created_at
                        FROM notifications
                        WHERE user_id = ? AND type <> 'announcement'

                        UNION ALL

                        SELECT
                            a.id * -1 AS id,
                            'announcement' AS type,
                            a.title,
                            a.content AS body,
                            a.id AS related_id,
                            ar.read_at,
                            a.published_at AS created_at
                        FROM announcements a
                        LEFT JOIN announcement_reads ar
                          ON ar.announcement_id = a.id AND ar.user_id = ?
                        WHERE a.is_published = 1
                    ) combined
                    ORDER BY datetime(created_at) DESC, id DESC
                    LIMIT ? OFFSET ?
                ", [$uid, $uid, $size, ($page - 1) * $size]);

                foreach ($items as &$item) {
                    foreach (['read_at', 'created_at'] as $field) {
                        if ($item[$field] !== null) $item[$field] = formatDbUtcTimestamp($item[$field]);
                    }
                    // id 为负数表示是公告，转回正数（保持唯一标识）
                    $item['id'] = (int)$item['id'];
                }
                unset($item);
                jsonOut(true, '', ['items' => $items, 'unread_count' => $unread]);
                break;

            case 'notification_mark_read':
                if (!isLoggedIn()) jsonOut(false, '请先登录');
                $uid = (int)(getUid());
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    // 正 id 是个人通知
                    dbQuery($db, 'UPDATE notifications SET read_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?', [$id, $uid]);
                } elseif ($id < 0) {
                    // 负 id 是公告（related_id 是公告 id），插入已读记录
                    $announcementId = (int)($_POST['related_id'] ?? 0);
                    if ($announcementId > 0) {
                        dbQuery($db, 'INSERT OR IGNORE INTO announcement_reads(user_id, announcement_id, read_at) VALUES(?, ?, CURRENT_TIMESTAMP)', [$uid, $announcementId]);
                    }
                } else {
                    // 全部已读：个人通知（排除旧公告通知） + 所有已发布公告
                    dbQuery($db, 'UPDATE notifications SET read_at=CURRENT_TIMESTAMP WHERE user_id=? AND read_at IS NULL AND type <> ?', [$uid, 'announcement']);
                    dbQuery($db, "
                        INSERT OR IGNORE INTO announcement_reads(user_id, announcement_id, read_at)
                        SELECT ?, id, CURRENT_TIMESTAMP FROM announcements WHERE is_published = 1
                    ", [$uid]);
                }
                jsonOut(true, '通知已读');
                break;

            // ---------- 公告管理（仅 root） ----------
            case 'admin_announcement_list':
                $adminUid = requireRoot();
                $page = max(1, (int)($_POST['page'] ?? 1));
                $size = max(1, min(100, (int)($_POST['page_size'] ?? 20)));
                $status = trim((string)($_POST['status'] ?? ''));
                $where = ['1=1']; $args = [];
                if ($status === 'published') { $where[] = 'is_published=1'; }
                elseif ($status === 'draft') { $where[] = 'is_published=0'; }
                $whereSql = implode(' AND ', $where);
                $total = (int)(dbFetchOne($db, "SELECT COUNT(*) AS c FROM announcements WHERE $whereSql", $args)['c'] ?? 0);
                $items = dbFetchAll($db, "SELECT a.id, a.title, a.content, a.is_published, a.published_at, a.created_by, u.username AS creator_name, a.created_at, a.updated_at FROM announcements a LEFT JOIN users u ON u.id=a.created_by WHERE $whereSql ORDER BY a.id DESC LIMIT ? OFFSET ?", array_merge($args, [$size, ($page - 1) * $size]));
                foreach ($items as &$item) {
                    $item['is_published'] = (bool)$item['is_published'];
                    foreach (['published_at', 'created_at', 'updated_at'] as $field) if ($item[$field] !== null) $item[$field] = formatDbUtcTimestamp($item[$field]);
                }
                unset($item);
                log_admin_action('list_announcements', $adminUid);
                jsonOut(true, '', ['items' => $items, 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total]]);
                break;

            case 'admin_announcement_create':
                $adminUid = requireRoot();
                $title = trim((string)($_POST['title'] ?? ''));
                $content = trim((string)($_POST['content'] ?? ''));
                if ($title === '' || mb_strlen($title) > 100) jsonOut(false, '标题不能为空且不超过 100 字');
                if (mb_strlen($content) > 10000) jsonOut(false, '内容不能超过 10000 字');
                dbQuery($db, 'INSERT INTO announcements(title, content, is_published, created_by) VALUES(?, ?, 0, ?)', [$title, $content, $adminUid]);
                $aid = (int)$db->lastInsertRowID();
                log_admin_action('create_announcement', $adminUid, ['id' => $aid, 'title' => $title]);
                jsonOut(true, '公告已创建', ['id' => $aid]);
                break;

            case 'admin_announcement_update':
                $adminUid = requireRoot();
                $id = (int)($_POST['id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $content = trim((string)($_POST['content'] ?? ''));
                if ($id <= 0) jsonOut(false, '参数错误');
                if ($title === '' || mb_strlen($title) > 100) jsonOut(false, '标题不能为空且不超过 100 字');
                if (mb_strlen($content) > 10000) jsonOut(false, '内容不能超过 10000 字');
                $ann = dbFetchOne($db, 'SELECT id, is_published FROM announcements WHERE id=?', [$id]);
                if (!$ann) jsonOut(false, '公告不存在');
                if ($ann['is_published']) jsonOut(false, '已发布的公告不能修改');
                dbQuery($db, 'UPDATE announcements SET title=?, content=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$title, $content, $id]);
                log_admin_action('update_announcement', $adminUid, ['id' => $id]);
                jsonOut(true, '公告已更新');
                break;

            case 'admin_announcement_delete':
                $adminUid = requireRoot();
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) jsonOut(false, '参数错误');
                $ann = dbFetchOne($db, 'SELECT id, is_published FROM announcements WHERE id=?', [$id]);
                if (!$ann) jsonOut(false, '公告不存在');
                dbQuery($db, 'DELETE FROM announcements WHERE id=?', [$id]);
                log_admin_action('delete_announcement', $adminUid, ['id' => $id]);
                jsonOut(true, '公告已删除');
                break;

            case 'admin_announcement_publish':
                $adminUid = requireRoot();
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) jsonOut(false, '参数错误');
                $ann = dbFetchOne($db, 'SELECT id, title, content, is_published FROM announcements WHERE id=?', [$id]);
                if (!$ann) jsonOut(false, '公告不存在');
                if ($ann['is_published']) jsonOut(false, '该公告已发布');

                // 标记为已发布（公告是全局的，通过 notification_list 动态合并展示，
                // 不再给每个用户物理插入通知记录，新注册用户也能看到历史公告）
                dbQuery($db, 'UPDATE announcements SET is_published=1, published_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
                $userCount = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM users WHERE is_active=1')['c'] ?? 0);
                log_admin_action('publish_announcement', $adminUid, ['id' => $id, 'active_users' => $userCount]);
                jsonOut(true, "公告已发布，所有用户均可在通知中心查看", ['active_users' => $userCount]);
                break;

            case 'admin_access_stats':
                requireAdmin();
                $tz = new DateTimeZone('Asia/Shanghai');
                $today = new DateTimeImmutable('now', $tz);
                $metrics = [
                    'question_count' => (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM questions')['c'] ?? 0),
                    'subject_count' => (int)(dbFetchOne($db, 'SELECT COUNT(DISTINCT subject) AS c FROM questions')['c'] ?? 0),
                    'material_count' => (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM materials')['c'] ?? 0),
                ];
                if (isRoot()) {
                    $metrics = array_merge($metrics, [
                        'user_count' => (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM users')['c'] ?? 0),
                        'active_count' => (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM users WHERE is_active=1')['c'] ?? 0),
                        'published_paper_count' => (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM papers WHERE is_published=1')['c'] ?? 0),
                        'download_count' => (int)(dbFetchOne($db, 'SELECT COALESCE(SUM(downloads),0) AS c FROM materials')['c'] ?? 0),
                    ]);
                }
                $trend = [];
                $todayUv = 0; $uv7d = 0; $peak = ['date' => null, 'uv' => 0];
                // 题库分布（科目维度）
                $bySubjectRows = dbFetchAll($db, 'SELECT subject, COUNT(*) AS c FROM questions GROUP BY subject ORDER BY c DESC LIMIT 8');
                $by_subject = [];
                foreach ($bySubjectRows as $r) {
                    $by_subject[] = ['subject' => $r['subject'] ?: '未分类', 'count' => (int)$r['c']];
                }
                // 题库分布（题型维度）
                $byTypeRows = dbFetchAll($db, "SELECT question_type, COUNT(*) AS c FROM questions GROUP BY question_type");
                $typeLabels = ['single' => '单选题', 'multiple' => '多选题', 'judge' => '判断题', 'fill' => '填空题'];
                $by_type = [];
                foreach ($byTypeRows as $r) {
                    $by_type[] = ['type' => $r['question_type'], 'label' => $typeLabels[$r['question_type']] ?? $r['question_type'], 'count' => (int)$r['c']];
                }
                // 资料分布（科目维度）
                $matSubjRows = dbFetchAll($db, 'SELECT subject, COUNT(*) AS c FROM materials GROUP BY subject ORDER BY c DESC LIMIT 8');
                $material_by_subject = [];
                foreach ($matSubjRows as $r) {
                    $material_by_subject[] = ['subject' => $r['subject'] ?: '未分类', 'count' => (int)$r['c']];
                }
                if (isRoot()) {
                    for ($i = 6; $i >= 0; $i--) {
                        $date = $today->modify('-' . $i . ' days')->format('Y-m-d');
                        $row = dbFetchOne($db, 'SELECT uv_count FROM site_visit_daily WHERE stat_date=?', [$date]);
                        $trend[] = ['date' => $date, 'label' => substr($date, 5), 'uv' => (int)($row['uv_count'] ?? 0)];
                    }
                    $todayUv = (int)($trend[6]['uv'] ?? 0);
                    $uv7d = array_sum(array_column($trend, 'uv'));
                    foreach ($trend as $point) { if ((int)$point['uv'] > (int)$peak['uv']) $peak = $point; }
                    $open = dbFetchOne($db, "SELECT COUNT(*) AS c FROM user_feedback WHERE status IN ('open','processing')");
                    $metrics['feedback_open_count'] = (int)($open['c'] ?? 0);
                }
                jsonOut(true, '', array_merge($metrics, ['timezone' => 'Asia/Shanghai', 'today' => ['date' => $today->format('Y-m-d'), 'uv' => $todayUv], 'summary' => ['uv_7d' => $uv7d, 'average_uv' => round($uv7d / 7, 2), 'peak_uv' => (int)$peak['uv'], 'peak_date' => $peak['date']], 'trend' => $trend, 'by_subject' => $by_subject, 'by_type' => $by_type, 'material_by_subject' => $material_by_subject]));
                break;

            // ==================== 安全监控 ====================
            case 'security_scan_status':
                requireAdmin();
                $lastScan = dbFetchOne($db, "SELECT * FROM security_scan_log WHERE scan_type='nginx_log' ORDER BY id DESC LIMIT 1");
                $ipCount = dbFetchOne($db, "SELECT COUNT(*) AS c FROM security_ips WHERE risk_level IN ('high','medium')");
                $highCount = dbFetchOne($db, "SELECT COUNT(*) AS c FROM security_ips WHERE risk_level='high'");
                $totalIpCount = dbFetchOne($db, "SELECT COUNT(*) AS c FROM security_ips");
                jsonOut(true, '', [
                    'last_scan' => $lastScan,
                    'total_abnormal' => (int)($ipCount['c'] ?? 0),
                    'high_risk' => (int)($highCount['c'] ?? 0),
                    'total_ip_count' => (int)($totalIpCount['c'] ?? 0),
                ]);
                break;

            case 'security_scan_force':
                requireAdmin();
                $result = triggerSecurityScan(true);
                $lastScan = dbFetchOne($db, "SELECT * FROM security_scan_log WHERE scan_type='nginx_log' ORDER BY id DESC LIMIT 1");

                // 额外的调试信息（帮助排查启动问题）
                $debug = [
                    'os' => PHP_OS,
                    'php_os' => PHP_OS_FAMILY ?? 'unknown',
                    'python3_path' => '',
                    'python3_version' => '',
                    'script_exists' => file_exists(dirname(__DIR__) . '/security/log_monitor.py'),
                    'log_exists' => file_exists('/www/wwwlogs/120.79.161.207.log'),
                    'db_exists' => file_exists(dirname(__DIR__) . '/exam.db'),
                    'functions' => [
                        'popen' => function_exists('popen'),
                        'pclose' => function_exists('pclose'),
                        'shell_exec' => function_exists('shell_exec'),
                        'exec' => function_exists('exec'),
                        'proc_open' => function_exists('proc_open'),
                    ],
                ];

                // 测试 python3 是否可用
                $pythonBin = 'python3';
                if (function_exists('shell_exec')) {
                    $pythonPath = @shell_exec('which python3 2>/dev/null || which python 2>/dev/null');
                    $debug['python3_path'] = trim($pythonPath ?? '');
                    $ver = @shell_exec('python3 --version 2>&1');
                    if (!$ver) $ver = @shell_exec('python --version 2>&1');
                    $debug['python3_version'] = trim($ver ?? '');
                }

                jsonOut($result['success'], $result['message'], [
                    'last_scan' => $lastScan,
                    'scan_id' => $result['scan_id'],
                    'skipped' => $result['skipped'],
                    'launch_info' => $result['message'],
                    'debug' => $debug,
                ]);
                break;

            // 快速扫描（纯 PHP 模式，同步执行，秒级出结果）
            case 'security_scan_quick':
                requireAdmin();
                $logPath = '/www/wwwlogs/120.79.161.207.log';
                $envFile = dirname(__DIR__) . '/security.env';
                if (file_exists($envFile)) {
                    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($envLines as $line) {
                        if (str_starts_with(trim($line), '#')) continue;
                        if (str_starts_with($line, 'NGINX_LOG_PATH=')) {
                            $logPath = trim(substr($line, 15));
                        }
                    }
                }

                // 先插入一条 running 记录
                dbQuery($db, "INSERT INTO security_scan_log(scan_type, started_at, status) VALUES('nginx_log', ?, 'running')", [
                    date('Y-m-d H:i:s')
                ]);
                $scanId = $db->lastInsertRowID();

                $result = phpSecurityScan($logPath, $scanId);
                $lastScan = dbFetchOne($db, "SELECT * FROM security_scan_log WHERE id=?", [$scanId]);

                // 异步触发地理位置补全（后台 Python 脚本，不阻塞返回）
                _triggerGeoLookup();

                jsonOut($result['success'], $result['message'], [
                    'last_scan' => $lastScan,
                    'scan_id' => $scanId,
                    'mode' => 'php',
                ]);
                break;

            // 安全扫描自检：检查环境、路径是否正确
            case 'security_scan_check':
                requireAdmin();
                $logPath = '/www/wwwlogs/120.79.161.207.log';
                $envFile = dirname(__DIR__) . '/security.env';
                if (file_exists($envFile)) {
                    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($envLines as $line) {
                        if (str_starts_with(trim($line), '#')) continue;
                        if (str_starts_with($line, 'NGINX_LOG_PATH=')) {
                            $logPath = trim(substr($line, 15));
                        }
                    }
                }
                $dbPath = dirname(__DIR__) . '/exam.db';
                $scriptPath = dirname(__DIR__) . '/security/log_monitor.py';
                $pythonBin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'python' : 'python3';

                $checks = [
                    'log_path' => $logPath,
                    'log_exists' => file_exists($logPath),
                    'log_size' => file_exists($logPath) ? filesize($logPath) : 0,
                    'script_path' => $scriptPath,
                    'script_exists' => file_exists($scriptPath),
                    'db_path' => $dbPath,
                    'db_exists' => file_exists($dbPath),
                    'php_os' => PHP_OS,
                    'python_bin' => $pythonBin,
                    'popen_available' => function_exists('popen'),
                    'shell_exec_available' => function_exists('shell_exec'),
                    'python_version' => '',
                ];

                // 尝试检测 python 是否可用
                if (function_exists('shell_exec')) {
                    $ver = @shell_exec($pythonBin . ' --version 2>&1');
                    $checks['python_version'] = trim($ver ?: '');
                }

                jsonOut(true, '', $checks);
                break;

            case 'security_ip_list':
                requireAdmin();
                $page = max(1, (int)($_POST['page'] ?? 1));
                $pageSize = min(100, max(10, (int)($_POST['page_size'] ?? 20)));
                $offset = ($page - 1) * $pageSize;
                $risk = $_POST['risk_level'] ?? '';

                $where = "WHERE risk_level IN ('high','medium')";
                $params = [];
                if ($risk === 'high' || $risk === 'medium') {
                    $where = "WHERE risk_level=?";
                    $params[] = $risk;
                } elseif ($risk === 'all') {
                    $where = '';
                    $params = [];
                }

                $totalRow = dbFetchOne($db, "SELECT COUNT(*) AS c FROM security_ips $where", $params);
                $total = (int)($totalRow['c'] ?? 0);

                $rows = dbFetchAll($db, "SELECT * FROM security_ips $where ORDER BY api_count DESC LIMIT ? OFFSET ?", array_merge($params, [$pageSize, $offset]));

                // 关联用户（通过 last_login_ip 匹配）
                $ips = array_column($rows, 'ip');
                $userMap = [];
                if ($ips) {
                    $placeholders = implode(',', array_fill(0, count($ips), '?'));
                    $userRows = dbFetchAll($db, "SELECT id, username, nickname, email, last_login_ip FROM users WHERE last_login_ip IN ($placeholders)", $ips);
                    foreach ($userRows as $u) {
                        $ip = $u['last_login_ip'];
                        if (!isset($userMap[$ip])) $userMap[$ip] = [];
                        $userMap[$ip][] = [
                            'id' => (int)$u['id'],
                            'username' => $u['username'],
                            'nickname' => $u['nickname'] ?? '',
                            'email' => $u['email'] ?? '',
                        ];
                    }
                }

                $list = [];
                foreach ($rows as $r) {
                    $scenarios = [];
                    if (!empty($r['scenarios'])) {
                        $decoded = json_decode($r['scenarios'], true);
                        if (is_array($decoded)) $scenarios = $decoded;
                    }
                    $list[] = [
                        'id' => (int)$r['id'],
                        'ip' => $r['ip'],
                        'api_count' => (int)$r['api_count'],
                        'scan_count' => (int)$r['scan_count'],
                        'total_count' => (int)$r['total_count'],
                        'status_404' => (int)$r['status_404'],
                        'risk_level' => $r['risk_level'],
                        'location' => $r['location'] ?? '',
                        'last_seen' => $r['last_seen'] ?? '',
                        'first_detected' => $r['first_detected'] ?? '',
                        'scenarios' => $scenarios,
                        'users' => $userMap[$r['ip']] ?? [],
                    ];
                }

                jsonOut(true, '', [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize,
                ]);
                break;

            // ==================== OCR 批次审核 ====================
            case 'ocr_batch_create':
                $uid = requireContentPermission('question_import');
                if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) jsonOut(false, '请选择有效文件');
                $file = $_FILES['file'];
                $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'bmp', 'tiff'], true)) jsonOut(false, '仅支持 PDF 或图片文件');
                if ((int)$file['size'] > 50 * 1024 * 1024) jsonOut(false, '文件不能超过 50MB');
                $level = validateEducationLevel($_POST['education_level'] ?? 'junior');
                $subject = normalizeSubject(sanitizeInput($_POST['subject'] ?? ''));
                $dir = __DIR__ . '/../data/ocr_batches';
                if (!is_dir($dir)) @mkdir($dir, 0750, true);
                $stored = 'ocr_' . bin2hex(random_bytes(16)) . '.' . $ext;
                $path = $dir . DIRECTORY_SEPARATOR . $stored;
                if (!move_uploaded_file($file['tmp_name'], $path)) jsonOut(false, 'OCR 文件保存失败');
                $resultName = $stored . '.json';
                dbQuery($db, 'INSERT INTO ocr_import_batches(file_name, stored_name, subject, education_level, created_by, status) VALUES(?,?,?,?,?,\'queued\')', [(string)$file['name'], $stored, $subject, $level, $uid]);
                $batchId = (int)$db->lastInsertRowID();
                $batch = dbFetchOne($db, 'SELECT * FROM ocr_import_batches WHERE id=?', [$batchId]);
                startOcrWorker($batch ?: ['id' => $batchId, 'stored_name' => $stored, 'subject' => $subject, 'education_level' => $level]);
                jsonOut(true, '文件已上传，OCR 处理中', ['batch_id' => $batchId, 'status' => 'running']);
                break;

            // ==================== 直接导入已有 JSON（跳过 OCR） ====================
            case 'ocr_batch_import_json':
                $uid = requireContentPermission('question_import');
                if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) jsonOut(false, '请选择 JSON 文件');
                $file = $_FILES['file'];
                $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
                if ($ext !== 'json') jsonOut(false, '仅支持 .json 文件');
                if ((int)$file['size'] > 20 * 1024 * 1024) jsonOut(false, 'JSON 不能超过 20MB');

                $raw = @file_get_contents((string)$file['tmp_name']);
                if ($raw === '' || $raw === false) jsonOut(false, 'JSON 文件读取失败');
                $data = json_decode($raw, true);
                if (!is_array($data)) jsonOut(false, 'JSON 格式无效');

                $subject = normalizeSubject(sanitizeInput($_POST['subject'] ?? ($data['subject'] ?? '')));
                $level = validateEducationLevel($_POST['education_level'] ?? ($data['education_level'] ?? 'junior'));
                $paperName = sanitizeInput($_POST['paper_name'] ?? ($data['paper_name'] ?? pathinfo((string)$file['name'], PATHINFO_FILENAME)));

                $rawQuestions = null;
                if (isset($data['questions']) && is_array($data['questions'])) {
                    $rawQuestions = $data['questions'];
                } elseif (isset($data['result_json']['questions']) && is_array($data['result_json']['questions'])) {
                    $rawQuestions = $data['result_json']['questions'];
                } elseif (isset($data['data']['result_json']['questions']) && is_array($data['data']['result_json']['questions'])) {
                    $rawQuestions = $data['data']['result_json']['questions'];
                }
                if (!$rawQuestions) jsonOut(false, 'JSON 中未找到 questions 数组');

                $questions = [];
                foreach ($rawQuestions as $idx => $q) {
                    if (!is_array($q)) continue;
                    $content = trim((string)($q['question_content'] ?? $q['content'] ?? ''));
                    $options = is_array($q['question_options'] ?? null)
                        ? array_values(array_filter(array_map('strval', $q['question_options'])))
                        : (is_array($q['options'] ?? null)
                            ? array_values(array_filter(array_map('strval', $q['options'])))
                            : []);
                    $qtype = (string)($q['question_type'] ?? $q['type'] ?? '');
                    $answer = (string)($q['answer'] ?? $q['correct_answer'] ?? '');
                    $resolve = (string)($q['resolve'] ?? $q['explanation'] ?? '');
                    $qid = (string)($q['question_id'] ?? $q['source_qid'] ?? ($idx + 1));
                    $difficulty = (int)($q['difficulty'] ?? 3);
                    $points = (int)($q['points'] ?? 1);
                    $typeMap = [
                        '单选题' => 'single', '单选' => 'single',
                        '多选题' => 'multiple', '多选' => 'multiple',
                        '判断题' => 'judge', '判断' => 'judge',
                        '填空题' => 'fill', '填空' => 'fill',
                        '简答题' => 'essay', '简答' => 'essay', '解答题' => 'essay', '解答' => 'essay',
                        '选择题' => count($options) >= 4 ? 'single' : 'choice',
                    ];
                    $qtype = $typeMap[$qtype] ?? ($qtype ?: 'single');
                    if ($content === '') continue;
                    $questions[] = [
                        'source_qid' => $qid,
                        'question_type' => $qtype,
                        'content' => $content,
                        'options' => $options,
                        'correct_answer' => $answer,
                        'explanation' => $resolve,
                        'difficulty' => max(1, min(5, $difficulty)),
                        'points' => max(0, $points),
                        'included' => true,
                        'images' => [],
                    ];
                }
                if (!$questions) jsonOut(false, 'JSON 中没有有效题目');

                $dir = __DIR__ . '/../data/ocr_batches';
                if (!is_dir($dir)) @mkdir($dir, 0750, true);
                $stored = 'ocr_import_' . bin2hex(random_bytes(16)) . '.json';
                file_put_contents($dir . DIRECTORY_SEPARATOR . $stored, json_encode(['questions' => $questions], JSON_UNESCAPED_UNICODE));

                dbQuery($db, 'INSERT INTO ocr_import_batches(file_name, stored_name, subject, education_level, created_by, status, result_json) VALUES(?,?,?,?,?,\'review\',?)', [$paperName, $stored, $subject, $level, $uid, json_encode(['questions' => $questions], JSON_UNESCAPED_UNICODE)]);
                $batchId = (int)$db->lastInsertRowID();
                jsonOut(true, "已导入 " . count($questions) . " 道题目，进入审核", ['batch_id' => $batchId, 'status' => 'review', 'question_count' => count($questions)]);
                break;

            case 'ocr_batch_list':
                requireContentPermission('question_import');
                $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
                $size = max(1, min(50, (int)($_POST['page_size'] ?? $_GET['page_size'] ?? 20)));
                $total = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM ocr_import_batches')['c'] ?? 0);
                $items = dbFetchAll($db, 'SELECT id, file_name, stored_name, status, subject, education_level, bridge_job_id, error_message, retry_count, created_at, updated_at, finished_at, json_extract(result_json, "$.questions") AS questions_json FROM ocr_import_batches ORDER BY id DESC LIMIT ? OFFSET ?', [$size, ($page - 1) * $size]);
                foreach ($items as &$item) {
                    $item = ocrRefreshBatch($item);
                    $raw = (string)($item['result_json'] ?? $item['questions_json'] ?? '');
                    $decoded = json_decode($raw, true);
                    // result_json 是完整对象 {"questions":[...]}；questions_json 是 json_extract 出的数组
                    $qs = isset($item['result_json']) ? ($decoded['questions'] ?? []) : $decoded;
                    $item['question_count'] = is_array($qs) ? count($qs) : 0;
                    unset($item['questions_json'], $item['result_json'], $item['stored_name']);
                }
                unset($item);
                jsonOut(true, '', ['items' => $items, 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total]]);
                break;

            case 'ocr_batch_get':
                requireContentPermission('question_import');
                $id = (int)($_POST['batch_id'] ?? $_GET['batch_id'] ?? 0);
                $batch = dbFetchOne($db, 'SELECT id, file_name, stored_name, status, subject, education_level, bridge_job_id, result_json, error_message, retry_count, created_at, updated_at, finished_at FROM ocr_import_batches WHERE id=?', [$id]);
                if (!$batch) jsonOut(false, 'OCR 批次不存在');
                $batch = ocrRefreshBatch($batch);
                $batch['result'] = $batch['result_json'] ? json_decode($batch['result_json'], true) : null;
                unset($batch['result_json'], $batch['stored_name']);
                jsonOut(true, '', ['batch' => $batch]);
                break;

            case 'ocr_batch_retry':
                $uid = requireContentPermission('question_import');
                $id = (int)($_POST['batch_id'] ?? 0);
                $batch = dbFetchOne($db, 'SELECT * FROM ocr_import_batches WHERE id=?', [$id]);
                if (!$batch) jsonOut(false, 'OCR 批次不存在');
                if (!in_array($batch['status'], ['failed', 'queued'], true)) jsonOut(false, '当前状态不可重试');
                dbQuery($db, 'UPDATE ocr_import_batches SET status=\'queued\', retry_count=retry_count+1, error_message=NULL, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
                $retryBatch = dbFetchOne($db, 'SELECT * FROM ocr_import_batches WHERE id=?', [$id]);
                if ($retryBatch) startOcrWorker($retryBatch);
                jsonOut(true, '已重新排队', ['batch_id' => $id, 'status' => 'queued']);
                break;

            case 'ocr_batch_delete':
                requireContentPermission('question_import');
                $id = (int)($_POST['batch_id'] ?? 0);
                $batch = dbFetchOne($db, 'SELECT stored_name FROM ocr_import_batches WHERE id=?', [$id]);
                if (!$batch) jsonOut(false, 'OCR 批次不存在');
                @unlink(__DIR__ . '/../data/ocr_batches/' . basename((string)$batch['stored_name']));
                dbQuery($db, 'DELETE FROM ocr_import_batches WHERE id=?', [$id]);
                jsonOut(true, 'OCR 批次已删除');
                break;

            case 'ocr_batch_set_meta':
                // 审核时标记批次学科/学段 (commit 时 questions 继承批次的 subject/education_level)
                requireContentPermission('question_import');
                $id = (int)($_POST['batch_id'] ?? 0);
                $subject = normalizeSubject(sanitizeInput($_POST['subject'] ?? ''));
                $level = validateEducationLevel($_POST['education_level'] ?? '', '');
                if ($id <= 0) jsonOut(false, '批次 ID 无效');
                $batch = dbFetchOne($db, 'SELECT id FROM ocr_import_batches WHERE id=?', [$id]);
                if (!$batch) jsonOut(false, '批次不存在');
                $fields = []; $params = [];
                if ($subject !== '')   { $fields[] = 'subject=?';   $params[] = $subject; }
                if ($level !== '')     { $fields[] = 'education_level=?'; $params[] = $level; }
                if (!$fields) jsonOut(false, '至少填写学科或学段');
                $params[] = $id;
                dbQuery($db, 'UPDATE ocr_import_batches SET ' . implode(', ', $fields) . ', updated_at=CURRENT_TIMESTAMP WHERE id=?', $params);
                jsonOut(true, '批次标签已更新', ['subject' => $subject, 'education_level' => $level]);
                break;

            case 'ocr_batch_commit':
                requireContentPermission('question_import');
                $id = (int)($_POST['batch_id'] ?? 0);
                $json = (string)($_POST['result_json'] ?? '');
                $batch = dbFetchOne($db, 'SELECT * FROM ocr_import_batches WHERE id=?', [$id]);
                if (!$batch) jsonOut(false, 'OCR 批次不存在');
                if ($batch['status'] === 'committed') jsonOut(false, '该批次已入库');
                $data = json_decode($json !== '' ? $json : (string)$batch['result_json'], true);
                if (!is_array($data)) jsonOut(false, '审核数据格式无效');
                $questions = is_array($data['questions'] ?? null) ? $data['questions'] : [];
                $kept = [];
                foreach ($questions as $q) {
                    if (!is_array($q) || ($q['included'] ?? true) === false) continue;
                    $content = trim((string)($q['content'] ?? $q['text'] ?? ''));
                    if ($content === '' || mb_strlen($content) > 100000) continue;
                    $options = is_array($q['options'] ?? null) ? array_values(array_filter(array_map('strval', $q['options']))) : [];
                    // OCR 裁切的题目图片（worker 输出 images: [{base64}]，审核页可保留/编辑），
                    // 透传为 question_images，由 pcvlConvert 去重后内嵌进题干 HTML，避免入库丢图
                    $questionImages = [];
                    if (is_array($q['images'] ?? null)) {
                        foreach ($q['images'] as $img) {
                            $b64 = is_array($img) ? trim((string)($img['base64'] ?? '')) : trim((string)$img);
                            if ($b64 !== '') $questionImages[] = $b64;
                        }
                    }
                    $kept[] = ['question_id' => (string)($q['source_qid'] ?? count($kept) + 1), 'question_type' => (string)($q['question_type'] ?? 'single'), 'question_content' => $content, 'question_options' => $options, 'question_images' => $questionImages, 'answer' => (string)($q['correct_answer'] ?? ''), 'resolve' => (string)($q['explanation'] ?? ''), 'difficulty' => max(1, min(5, (int)($q['difficulty'] ?? 3))), 'points' => max(0, (float)($q['points'] ?? 1)), 'is_html' => !empty($q['is_html']) ? 1 : 0, 'subject' => $batch['subject'], 'education_level' => $batch['education_level']];
                }
                if (!$kept) jsonOut(false, '没有可入库的题目');
                $importData = ['paper_name' => $batch['file_name'], 'subject' => $batch['subject'], 'education_level' => $batch['education_level'], 'questions' => $kept];
                $normalized = [];
                importExtractQuestions($importData, $batch['subject'] ?: '综合', $normalized, $batch['education_level'] ?: '');
                $inserted = 0; $skipped = 0;
                foreach ($normalized as $q) {
                    $exists = dbFetchOne($db, 'SELECT id FROM questions WHERE content=? AND subject=? AND education_level=? LIMIT 1', [$q['content'], $q['subject'], $q['education_level']]);
                    if ($exists) { $skipped++; continue; }
                    $opts = !empty($q['options']) ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null;
                    dbQuery($db, 'INSERT INTO questions(subject, question_type, category, education_level, content, options, correct_answer, explanation, difficulty, points, is_html, match_key, source_qid) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)', [$q['subject'], $q['question_type'], $q['category'] ?? $q['question_type'], $q['education_level'], $q['content'], $opts, $q['correct_answer'] ?? '', $q['explanation'] ?? '', $q['difficulty'] ?? 3, $q['points'] ?? 1, !empty($q['is_html']) ? 1 : 0, '', $q['_pcvl_qid'] ?? '']);
                    $inserted++;
                }
                dbQuery($db, 'UPDATE ocr_import_batches SET status=\'committed\', result_json=?, updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=?', [json_encode(['questions' => $questions], JSON_UNESCAPED_UNICODE), $id]);
                jsonOut(true, '审核题目已入库', ['inserted' => $inserted, 'skipped' => $skipped, 'total' => count($kept)]);
                break;

            // ==================== OCR 导出: AI 提示词纯文本 ====================
            case 'ocr_batch_export':
                requireContentPermission('question_import');
                $id = (int)($_POST['batch_id'] ?? $_GET['batch_id'] ?? 0);
                $batch = dbFetchOne($db, 'SELECT id, file_name, result_json, status FROM ocr_import_batches WHERE id=?', [$id]);
                if (!$batch) jsonOut(false, 'OCR 批次不存在');
                $data = json_decode($batch['result_json'] ?? '', true);
                $questions = $data['questions'] ?? [];
                if (!$questions) jsonOut(false, '该批次还没有识别结果');

                $paperName = pathinfo((string)$batch['file_name'], PATHINFO_FILENAME);

                // 组装 export_input (bridge_server 传给家里云 PDF 生成脚本)
                $exportInput = [
                    'paper_name' => $paperName,
                    'subject' => $data['subject'] ?? '',
                    'questions' => $questions,
                ];

                // ① 提交给 bridge_server (公网机本机)
                $bridgePort = 1201;
                $ch = curl_init("http://127.0.0.1:{$bridgePort}/export_pdf");
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode(['export_input' => $exportInput], JSON_UNESCAPED_UNICODE),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                ]);
                $resp = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 202) jsonOut(false, 'Bridge 提交失败: ' . $resp);
                $submit = json_decode($resp, true);
                $jobId = $submit['job_id'] ?? '';
                if (!$jobId) jsonOut(false, 'Bridge 返回异常');

                // ② 轮询状态 (最多 60s)
                $pdfCacheDir = __DIR__ . '/../ocr_bridge/_pdf_cache';
                $pdfPath = $pdfCacheDir . "/{$jobId}.pdf";

                for ($i = 0; $i < 30; $i++) {
                    // 文件存在 = 家里云已经生成好 + bridge_server 落盘了
                    if (file_exists($pdfPath) && filesize($pdfPath) > 100) {
                        break;
                    }

                    $ch2 = curl_init("http://127.0.0.1:{$bridgePort}/status/{$jobId}");
                    curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
                    $sresp = curl_exec($ch2);
                    $scode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                    curl_close($ch2);

                    if ($scode === 200) {
                        $s = json_decode($sresp, true);
                        $sstatus = $s['status'] ?? '';
                        if ($sstatus === 'failed') jsonOut(false, 'PDF 生成失败: ' . ($s['error'] ?? 'unknown'));
                    }

                    usleep(2000000); // 2s
                }

                // ③ 读 PDF 输出给浏览器
                if (!file_exists($pdfPath)) {
                    jsonOut(false, 'PDF 生成超时, 请稍后重试');
                }

                $pdfRaw = file_get_contents($pdfPath);
                $filename = "{$paperName}_AI提示词_" . date('Ymd_His') . ".pdf";

                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
                header('Content-Length: ' . strlen($pdfRaw));
                header('Cache-Control: no-cache, must-revalidate');
                echo $pdfRaw;
                exit;

            // ==================== 危险操作已移除 ====================
            case 'admin_danger_challenge':
            case 'clear_users':
            case 'reset_db':
                jsonOut(false, '功能已移除');
                break;

            // ==================== 管理员面板（不返回密码） ====================
            case 'get_admin_panel':
                requireRoot();
                $users = dbFetchAll($db, "SELECT id, username, email, role, is_initial_root, is_admin, is_approved, is_active, created_at, last_login FROM users ORDER BY id DESC");
                $users = array_map('formatUserTimestamps', $users);
                $userTotal = dbFetchOne($db, "SELECT count(*) as c FROM users")['c'];
                $activeTotal = dbFetchOne($db, "SELECT count(*) as c FROM users WHERE is_active=1")['c'];
                jsonOut(true, "", [
                    'users' => $users,
                    'stats' => ['user_count' => $userTotal, 'active_count' => $activeTotal, 'pending_count' => 0]
                ]);
                break;

            // ==================== 兼容旧客户端：注册审批已取消 ====================
            case 'approve_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireRoot();
                $uid = (int)$_POST['user_id'];
                dbQuery($db, "UPDATE users SET is_approved=1 WHERE id=?", [$uid]);
                jsonOut(true, "注册后无需审批，账号已保持可用");
                break;

            case 'reject_user':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireRoot();
                jsonOut(false, "注册审批已取消，请使用启用/禁用管理账号");
                break;

            // ==================== 启用/禁用用户 ====================
            case 'toggle_user':
                $operator = requireRoot();
                $uid = (int)($_POST['user_id'] ?? 0);
                $target = dbFetchOne($db, "SELECT id, username, role, is_initial_root, is_active FROM users WHERE id=?", [$uid]);
                if (!$target) jsonOut(false, '用户不存在');
                if ((int)$target['is_initial_root'] === 1 || $target['username'] === 'lian') jsonOut(false, '初始 root 不可禁用');
                if ($target['role'] === 'root' && !isInitialRoot()) jsonOut(false, '只有初始 lian 可以禁用其他 root');
                dbQuery($db, "UPDATE users SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?", [$uid]);
                log_admin_action('toggle_user', $uid);
                jsonOut(true, "状态已更新");
                break;

            // ==================== 创建 root（仅初始 lian） ====================
            case 'create_admin':
            case 'create_root':
                requireRoot();
                if (!isInitialRoot()) jsonOut(false, '仅初始 lian 可以创建 root');
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = (string)($_POST['password'] ?? '');
                if ($username === '' || $email === '' || $password === '' || !validateEmail($email) || strlen($password) < 4) jsonOut(false, '创建信息无效');
                if (dbFetchOne($db, "SELECT id FROM users WHERE username=? OR email=?", [$username, $email])) jsonOut(false, '用户名或邮箱已存在');
                dbQuery($db, "INSERT INTO users(username,email,password,role,is_initial_root,is_admin,is_approved,is_active) VALUES(?,?,?,'root',0,1,1,1)", [$username, $email, encryptPassword($password)]);
                $newRootId = (int)$db->lastInsertRowID();
                createAuthorizationNotification($newRootId, 'root', '已创建为 root');
                log_admin_action('create_root', $newRootId);
                jsonOut(true, 'root 创建成功');
                break;

            // ==================== 授权/撤销角色 ====================
            case 'grant_root':
                requireRoot();
                if (!isInitialRoot()) jsonOut(false, '仅初始 lian 可以授权 root');
                $uid = (int)($_POST['user_id'] ?? 0);
                $target = dbFetchOne($db, "SELECT id, username, role FROM users WHERE id=?", [$uid]);
                if (!$target) jsonOut(false, '用户不存在');
                dbQuery($db, "UPDATE users SET role='root', is_admin=1 WHERE id=? AND username<>'lian'", [$uid]);
                createAuthorizationNotification($uid, 'root', '已授予 root 权限');
                jsonOut(true, '已授予 root 权限');
                break;
            case 'revoke_root':
                requireRoot();
                if (!isInitialRoot()) jsonOut(false, '仅初始 lian 可以撤销 root');
                $uid = (int)($_POST['user_id'] ?? 0);
                $target = dbFetchOne($db, "SELECT id, username, role, is_initial_root FROM users WHERE id=?", [$uid]);
                if (!$target) jsonOut(false, '用户不存在');
                if ((int)$target['is_initial_root'] === 1 || $target['username'] === 'lian') jsonOut(false, '初始 root 不可撤销');
                dbQuery($db, "UPDATE users SET role='user', is_admin=0 WHERE id=?", [$uid]);
                createAuthorizationNotification($uid, 'root_revoked', 'root 权限已撤销');
                jsonOut(true, '已撤销 root 权限');
                break;
            case 'grant_content_admin':
                requireRoot();
                $uid = (int)($_POST['user_id'] ?? 0);
                $target = dbFetchOne($db, "SELECT id, username, role FROM users WHERE id=?", [$uid]);
                if (!$target) jsonOut(false, '用户不存在');
                if ($target['role'] === 'root') jsonOut(false, 'root 无需授予内容管理员权限');
                dbQuery($db, "UPDATE users SET role='content_admin', is_admin=0 WHERE id=?", [$uid]);
                createAuthorizationNotification($uid, 'content_admin', '已授予内容管理员权限');
                jsonOut(true, '已授予内容管理员权限');
                break;
            case 'revoke_content_admin':
                requireRoot();
                $uid = (int)($_POST['user_id'] ?? 0);
                $target = dbFetchOne($db, "SELECT id, username, role FROM users WHERE id=?", [$uid]);
                if (!$target) jsonOut(false, '用户不存在');
                dbQuery($db, "UPDATE users SET role='user', is_admin=0 WHERE id=? AND role='content_admin'", [$uid]);
                createAuthorizationNotification($uid, 'content_admin_revoked', '内容管理员权限已撤销');
                jsonOut(true, '已撤销内容管理员权限');
                break;

            // 兼容旧客户端：旧“设为管理员”不再允许绕过 root 授权规则。
            case 'make_admin':
                jsonOut(false, '请使用 root 授权功能');
                break;

            // ==================== 删除用户 ====================
            case 'delete_user':
                requireRoot();
                $uid = (int)($_POST['user_id'] ?? 0);
                $target = dbFetchOne($db, "SELECT id, username, role, is_initial_root FROM users WHERE id=?", [$uid]);
                if (!$target) jsonOut(false, '用户不存在');
                if ((int)$target['is_initial_root'] === 1 || $target['username'] === 'lian') jsonOut(false, '初始 root 不可删除');
                if ($target['role'] === 'root' && !isInitialRoot()) jsonOut(false, '只有初始 lian 可以删除其他 root');
                dbQuery($db, "DELETE FROM users WHERE id=?", [$uid]);
                log_admin_action('delete_user', $uid);
                jsonOut(true, "用户已删除");
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
                // 仅在“题目管理”范围（scope=manage_all）要求内容管理员权限；
                // 普通用户只能浏览题目，不能通过伪造 scope 进入管理数据范围。
                $scope = isset($_GET['scope']) ? (string)$_GET['scope'] : (isset($_POST['scope']) ? (string)$_POST['scope'] : '');
                if ($scope === 'manage_all') requireContentPermission('question_view');

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

            // ---------- 题目搜索（快捷搜索） ----------
            case 'question_search':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $keyword = trim($_POST['keyword'] ?? $_GET['keyword'] ?? '');
                $subject = trim($_POST['subject'] ?? $_GET['subject'] ?? '');
                $limit   = max(1, min(100, (int)($_POST['limit'] ?? $_GET['limit'] ?? 50)));
                $educationLevel = validateEducationLevel($_POST['education_level'] ?? $_GET['education_level'] ?? '', '');

                $where = [];
                $params = [];
                if ($keyword !== '') {
                    $where[] = '(content LIKE ? OR explanation LIKE ?)';
                    $params[] = "%{$keyword}%";
                    $params[] = "%{$keyword}%";
                }
                if ($subject !== '') {
                    $where[] = 'subject = ?';
                    $params[] = $subject;
                }
                if ($educationLevel !== '') {
                    $where[] = 'education_level = ?';
                    $params[] = $educationLevel;
                }
                $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

                $rows = dbFetchAll($db, "SELECT id, subject, question_type, content, difficulty, points, created_at
                    FROM questions {$whereSql} ORDER BY id DESC LIMIT ?", array_merge($params, [$limit]));

                $items = array_map(function($r){
                    $r['difficulty'] = (int)$r['difficulty'];
                    $r['points']     = (float)$r['points'];
                    // 普通用户不返回答案/解析相关
                    if (!isAdmin()) {
                        // content 里可能含答案，这里至少确保不泄露 explanation
                    }
                    return $r;
                }, $rows);

                jsonOut(true, "", ['items' => $items]);
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
                requireContentPermission('question_edit');

                $subject     = normalizeSubject(sanitizeInput($_POST['subject'] ?? ''));
                $qtype       = sanitizeInput($_POST['question_type'] ?? '');
                $content     = sanitizeHtmlContent($_POST['content'] ?? '');
                $optionsRaw  = $_POST['options'] ?? '[]';
                $answer      = sanitizeInput($_POST['correct_answer'] ?? '');
                $explanation = sanitizeHtmlContent($_POST['explanation'] ?? '');
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
                $isHtml = preg_match('/<[a-z][\s\S]*>/i', $content) ? 1 : 0;
                dbQuery($db, "INSERT INTO questions (subject, question_type, category, education_level, content, options, correct_answer, explanation, difficulty, points, is_html) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$subject, $qtype, $category, $educationLevel, $content, $optionsJson, $answer, $explanation, $difficulty, $points, $isHtml]);
                $newId = (int)$db->lastInsertRowID();
                jsonOut(true, "题目已保存", ['id' => $newId]);
                break;

            // ---------- 修改题目 ----------
            case 'update_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireContentPermission('question_edit');

                $qid = (int)($_POST['id'] ?? 0);
                if ($qid <= 0) jsonOut(false, "题目ID无效");
                $existing = dbFetchOne($db, "SELECT id FROM questions WHERE id=?", [$qid]);
                if (!$existing) jsonOut(false, "题目不存在");
                $usedInAttempts = (int)(dbFetchOne($db, "SELECT COUNT(*) AS c FROM exam_answers WHERE question_id=?", [$qid])['c'] ?? 0);
                if ($usedInAttempts > 0) jsonOut(false, '题目已有作答记录，不能修改');

                $subject     = normalizeSubject(sanitizeInput($_POST['subject'] ?? ''));
                $qtype       = sanitizeInput($_POST['question_type'] ?? '');
                $content     = sanitizeHtmlContent($_POST['content'] ?? '');
                $optionsRaw  = $_POST['options'] ?? '[]';
                $answer      = sanitizeInput($_POST['correct_answer'] ?? '');
                $explanation = sanitizeHtmlContent($_POST['explanation'] ?? '');
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
                $isHtml = preg_match('/<[a-z][\s\S]*>/i', $content) ? 1 : 0;
                dbQuery($db, "UPDATE questions SET subject=?, question_type=?, category=?, education_level=?, content=?, options=?, correct_answer=?, explanation=?, difficulty=?, points=?, is_html=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                    [$subject, $qtype, $category, $educationLevel, $content, $optionsJson, $answer, $explanation, $difficulty, $points, $isHtml, $qid]);
                jsonOut(true, "题目已更新");
                break;

            // ---------- 删除题目 ----------
            case 'delete_question':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireContentPermission('question_edit');
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
                requireContentPermission('question_edit');
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
                requireContentPermission('question_edit');
                $ids = json_decode($_POST['ids'] ?? '[]', true);
                if (!is_array($ids) || count($ids) < 1 || count($ids) > 500) jsonOut(false, '请选择1-500道题目');
                $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
                if (!$ids) jsonOut(false, '题目ID无效');
                $category = validateQuestionCategory($_POST['category'] ?? '', '');
                $in = implode(',', array_fill(0, count($ids), '?'));
                dbQuery($db, "UPDATE questions SET category=?, updated_at=CURRENT_TIMESTAMP WHERE id IN ($in)", array_merge([$category], $ids));
                jsonOut(true, '批量修改分类成功', ['updated' => $db->changes()]);
                break;

            case 'bulk_update_question_subject':
                requireContentPermission('question_edit');
                $ids = json_decode($_POST['ids'] ?? '[]', true);
                if (!is_array($ids) || count($ids) < 1 || count($ids) > 500) jsonOut(false, '请选择1-500道题目');
                $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
                if (!$ids) jsonOut(false, '题目ID无效');
                $subject = trim((string)($_POST['subject'] ?? ''));
                if ($subject === '') jsonOut(false, '学科不能为空');
                $subject = normalizeSubject($subject);
                $in = implode(',', array_fill(0, count($ids), '?'));
                dbQuery($db, "UPDATE questions SET subject=?, updated_at=CURRENT_TIMESTAMP WHERE id IN ($in)", array_merge([$subject], $ids));
                jsonOut(true, '批量修改学科成功', ['updated' => $db->changes()]);
                break;

            // ---------- 批量删除题目及其关联记录 ----------
            case 'bulk_delete_questions':
                requireContentPermission('question_edit');
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
                requireContentPermission('question_import');

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
                importExtractQuestions($data, $detectedSubject, $questions, $rawEducationLevel);

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
                if (isRoot()) {
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

                // 只有 root 可以查看草稿，其他用户只能查看已发布试卷。
                if (!isRoot() && !(int)$paper['is_published']) {
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
                        // 非 root 端不返回正确答案与解析。
                        if (!isRoot()) unset($q['correct_answer'], $q['explanation']);
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
                requireRoot();

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
                    if (array_key_exists($id, array_column($normalized, 'id', 'id'))) jsonOut(false, '试卷中不能重复选择同一道题');
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

                if ($pid > 0) {
                    if (!dbFetchOne($db, "SELECT id FROM papers WHERE id=?", [$pid])) {
                        jsonOut(false, "试卷不存在，请刷新后重试");
                    }
                    $attemptCount = (int)(dbFetchOne($db, "SELECT COUNT(*) AS c FROM exam_attempts WHERE paper_id=?", [$pid])['c'] ?? 0);
                    if ($attemptCount > 0) jsonOut(false, "此试卷已有作答记录，不能修改题目");
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
                requireRoot();
                $pid = (int)($_POST['paper_id'] ?? 0);
                if ($pid <= 0) jsonOut(false, "试卷ID无效");
                if (!dbFetchOne($db, "SELECT id FROM papers WHERE id=?", [$pid])) jsonOut(false, "试卷不存在，请刷新后重试");
                // 必须至少有1道题
                $cnt = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM paper_questions WHERE paper_id=?", [$pid])['c'];
                if ($cnt <= 0) jsonOut(false, "请先添加至少一道题目再发布");
                dbQuery($db, "UPDATE papers SET is_published=1, updated_at=CURRENT_TIMESTAMP WHERE id=?", [$pid]);
                $published = dbFetchOne($db, "SELECT is_published FROM papers WHERE id=?", [$pid]);
                if (!$published || (int)$published['is_published'] !== 1) jsonOut(false, "发布失败，请稍后重试");
                jsonOut(true, "已发布", ['paper_id' => $pid]);
                break;

            case 'paper_unpublish':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireRoot();
                $pid = (int)($_POST['paper_id'] ?? 0);
                if ($pid <= 0) jsonOut(false, "试卷ID无效");
                if (!dbFetchOne($db, "SELECT id FROM papers WHERE id=?", [$pid])) jsonOut(false, "试卷不存在，请刷新后重试");
                // 若已有已提交的 attempt，不允许下架（避免学生做了一半看不到）
                $cnt = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM exam_attempts WHERE paper_id=? AND status='submitted'", [$pid])['c'];
                if ($cnt > 0) jsonOut(false, "已有学生完成此试卷，无法下架");
                dbQuery($db, "UPDATE papers SET is_published=0, updated_at=CURRENT_TIMESTAMP WHERE id=?", [$pid]);
                jsonOut(true, "已下架");
                break;

            case 'paper_delete':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireRoot();
                $pid = (int)($_POST['paper_id'] ?? 0);
                if ($pid <= 0) jsonOut(false, "试卷ID无效");
                if (!dbFetchOne($db, "SELECT id FROM papers WHERE id=?", [$pid])) jsonOut(false, "试卷不存在，请刷新后重试");
                try {
                    $db->exec('BEGIN');
                    // 删除学生作答记录（含答案明细）
                    $attemptIds = array_column(dbFetchAll($db, "SELECT id FROM exam_attempts WHERE paper_id=?", [$pid]), 'id');
                    if (!empty($attemptIds)) {
                        $idsPlaceholders = implode(',', array_fill(0, count($attemptIds), '?'));
                        dbQuery($db, "DELETE FROM exam_answers WHERE attempt_id IN ($idsPlaceholders)", $attemptIds);
                        dbQuery($db, "DELETE FROM exam_attempts WHERE paper_id=?", [$pid]);
                    }
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

                $allowedQuestionPoints = examB_attemptQuestionPoints($db, $att);
                $answerMap = [];
                foreach ($answers as $entry) {
                    if (!is_array($entry) || !isset($entry['question_id'])) continue;
                    $qid = (int)$entry['question_id'];
                    if (!array_key_exists($qid, $allowedQuestionPoints)) jsonOut(false, '提交中包含不属于本次作答的题目');
                    $sa = $entry['student_answer'] ?? '';
                    $answerMap[$qid] = is_array($sa) ? json_encode($sa, JSON_UNESCAPED_UNICODE) : (string)$sa;
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
                    // 写每题作答；题号已在事务外按本次 attempt 白名单校验。
                    foreach ($answerMap as $qid => $sa) {
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
                $allowedQuestionPoints = examB_attemptQuestionPoints($db, $att);
                $gradeMap = [];
                foreach ($grades as $g) {
                    if (!is_array($g) || !isset($g['question_id'])) continue;
                    $qid = (int)$g['question_id'];
                    if (!array_key_exists($qid, $allowedQuestionPoints)) jsonOut(false, '自评中包含不属于本次作答的题目');
                    $corr = isset($g['self_correct']) ? (int)$g['self_correct'] : null;
                    $score = isset($g['self_score']) ? (float)$g['self_score'] : null;
                    if ($corr !== null && $corr !== 0 && $corr !== 1) $corr = null;
                    if ($score !== null && ($score < 0 || $score > $allowedQuestionPoints[$qid])) jsonOut(false, '自评分数超出题目分值');
                    $gradeMap[$qid] = ['correct' => $corr, 'score' => $score];
                }
                try {
                    $db->exec('BEGIN');
                    foreach ($gradeMap as $qid => $grade) {
                        $corr = $grade['correct'];
                        $score = $grade['score'];
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
                $allowedQuestionPoints = examB_attemptQuestionPoints($db, $att);
                $answerMap = [];
                foreach ($answers as $entry) {
                    if (!is_array($entry) || !isset($entry['question_id'])) continue;
                    $qid = (int)$entry['question_id'];
                    if (!array_key_exists($qid, $allowedQuestionPoints)) jsonOut(false, '提交中包含不属于本次作答的题目');
                    $sa = $entry['student_answer'] ?? '';
                    $answerMap[$qid] = is_array($sa) ? json_encode($sa, JSON_UNESCAPED_UNICODE) : (string)$sa;
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
                    foreach ($answerMap as $qid => $sa) {
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
                $allowedQuestionPoints = examB_attemptQuestionPoints($db, $att);
                $gradeMap = [];
                foreach ($grades as $g) {
                    if (!is_array($g) || !isset($g['question_id'])) continue;
                    $qid = (int)$g['question_id'];
                    if (!array_key_exists($qid, $allowedQuestionPoints)) jsonOut(false, '自评中包含不属于本次作答的题目');
                    $corr = isset($g['self_correct']) ? (int)$g['self_correct'] : null;
                    $score = isset($g['self_score']) ? (float)$g['self_score'] : null;
                    if ($corr !== null && $corr !== 0 && $corr !== 1) $corr = null;
                    if ($score !== null && ($score < 0 || $score > $allowedQuestionPoints[$qid])) jsonOut(false, '自评分数超出题目分值');
                    $gradeMap[$qid] = ['correct' => $corr, 'score' => $score];
                }
                try {
                    $db->exec('BEGIN');
                    foreach ($gradeMap as $qid => $grade) {
                        $corr = $grade['correct'];
                        $score = $grade['score'];
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

            // ---------- 资料列表（登录即可；按 subject/category/keyword 过滤） ----------
            case 'material_list':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $subject = sanitizeInput($_POST['subject'] ?? $_GET['subject'] ?? '');
                $keyword = sanitizeInput($_POST['keyword'] ?? $_GET['keyword'] ?? '');
                $educationLevel = validateEducationLevel($_POST['education_level'] ?? $_GET['education_level'] ?? '', '');
                $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : (isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0);
                $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
                $pageSize = (int)($_POST['page_size'] ?? $_GET['page_size'] ?? 0);
                if ($pageSize <= 0) $pageSize = 0; // 0 表示不分页
                // 管理员在资料中心可选择"全部学段"查看所有资料（不再强制必须选具体学段）；
                // 若需限制学段仅用于筛选，空值即代表跨学段浏览。
                $where = ['1=1']; $args = [];
                if ($educationLevel !== '') { $where[] = 'education_level=?'; $args[] = $educationLevel; }
                if ($subject !== '') { $where[] = "subject=?"; $args[] = $subject; }
                if ($categoryId > 0) { $where[] = 'category_id=?'; $args[] = $categoryId; }
                if ($keyword !== '') { $where[] = "(filename LIKE ? OR description LIKE ?)"; $args[] = "%$keyword%"; $args[] = "%$keyword%"; }
                $wSql = implode(' AND ', $where);

                // 总数
                $totalRow = dbFetchOne($db, "SELECT COUNT(*) AS c FROM materials WHERE $wSql", $args);
                $total = (int)($totalRow['c'] ?? 0);

                $limitSql = ''; $limitArgs = [];
                if ($pageSize > 0) {
                    $offset = ($page - 1) * $pageSize;
                    $limitSql = " LIMIT ? OFFSET ?";
                    $limitArgs = [$pageSize, $offset];
                }

                $rows = dbFetchAll($db, "SELECT id, filename, file_size, mime_type, subject, description, uploaded_at, updated_at, downloads, education_level, category_id FROM materials WHERE $wSql ORDER BY id DESC{$limitSql}", array_merge($args, $limitArgs));
                $subjects = dbFetchAll($db, "SELECT DISTINCT subject FROM materials WHERE subject IS NOT NULL AND subject <> '' ORDER BY subject ASC");
                // 分类列表：如果选了学段就只返回该学段的分类
                $catWhere = ''; $catArgs = [];
                if ($educationLevel !== '') { $catWhere = 'WHERE education_level=?'; $catArgs[] = $educationLevel; }
                $categories = dbFetchAll($db, "SELECT id, name, education_level, sort_order FROM material_categories {$catWhere} ORDER BY sort_order ASC, id ASC", $catArgs);
                foreach ($rows as &$r) {
                    $r['file_size'] = (int)$r['file_size'];
                    $r['downloads'] = (int)$r['downloads'];
                    // 保持 UTC 字符串，由前端根据本地时区转换显示
                }
                unset($r);
                jsonOut(true, "", [
                    'materials' => $rows,
                    'subjects' => array_column($subjects, 'subject'),
                    'categories' => $categories,
                    'education_levels' => ['junior', 'senior'],
                    'can_upload' => isAdmin(),
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize > 0 ? $pageSize : $total,
                ]);
                break;

            // ---------- 管理员上传 ----------
            case 'material_upload':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireContentPermission('material_manage');
                if (empty($_FILES['file'])) jsonOut(false, "请选择文件");
                $f = $_FILES['file'];
                if (!is_uploaded_file($f['tmp_name']) || $f['error'] !== UPLOAD_ERR_OK) {
                    jsonOut(false, "文件上传失败，错误码：" . ($f['error'] ?? 'unknown'));
                }
                // 50MB
                $max = 50 * 1024 * 1024;
                if ((int)$f['size'] > $max) jsonOut(false, "文件超过 50MB 上限");
                // 扩展名白名单（以实际上传文件为准，防止伪造）
                $allowedExts = ['pdf','doc','docx','txt','md','xls','xlsx','ppt','pptx'];
                $realName = (string)$f['name'];
                $ext = strtolower(pathinfo($realName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts, true)) {
                    jsonOut(false, "不允许的扩展名：$ext。仅允许：" . implode('/', $allowedExts));
                }
                // 显示文件名：优先用前端传入的 filename，否则用原始文件名
                $customName = trim((string)($_POST['filename'] ?? ''));
                $origName = $customName !== '' ? $customName : $realName;
                // 确保扩展名一致（安全：扩展名始终以实际上传文件为准）
                $origExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if ($origExt !== $ext) {
                    $origName = preg_replace('/\.[^.]+$/', '', $origName) . '.' . $ext;
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
                $categoryId = (int)($_POST['category_id'] ?? 0);
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
                    "INSERT INTO materials(filename, stored_name, file_path, file_size, mime_type, subject, description, uploaded_by, education_level, category_id) VALUES(?,?,?,?,?,?,?,?,?,?)",
                    [$origName, $stored, $relPath, (int)$f['size'], $mime, $subject, $desc, getUid(), $educationLevel, $categoryId > 0 ? $categoryId : null]
                );
                $newId = (int)$db->lastInsertRowID();
                jsonOut(true, "上传成功", ['material_id' => $newId, 'filename' => $origName, 'size' => (int)$f['size'], 'education_level' => $educationLevel]);
                break;

            // ---------- 管理员编辑资料元数据 ----------
            case 'material_update':
                requireContentPermission('material_manage');
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) jsonOut(false, '参数错误');
                if (!dbFetchOne($db, 'SELECT id FROM materials WHERE id=?', [$id])) jsonOut(false, '资料不存在');
                $filename = trim((string)($_POST['filename'] ?? ''));
                $subject = sanitizeInput($_POST['subject'] ?? '');
                $desc = sanitizeInput($_POST['description'] ?? '');
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $rawEducationLevel = trim((string)($_POST['education_level'] ?? ''));
                if ($rawEducationLevel === '') jsonOut(false, '请选择所属学段');
                $educationLevel = validateEducationLevel($rawEducationLevel);
                if ($filename === '') jsonOut(false, '文件名不能为空');
                dbQuery($db, 'UPDATE materials SET filename=?, subject=?, description=?, education_level=?, category_id=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$filename, $subject, $desc, $educationLevel, $categoryId > 0 ? $categoryId : null, $id]);
                jsonOut(true, '资料信息已更新');
                break;

            // ---------- 管理员删除 ----------
            case 'material_delete':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireContentPermission('material_manage');
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

            // ---------- 资料分类列表 ----------
            case 'material_category_list':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $educationLevel = validateEducationLevel($_POST['education_level'] ?? $_GET['education_level'] ?? '', '');
                $where = []; $args = [];
                if ($educationLevel !== '') { $where[] = 'education_level=?'; $args[] = $educationLevel; }
                $wSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
                $rows = dbFetchAll($db, "SELECT id, name, education_level, sort_order FROM material_categories {$wSql} ORDER BY sort_order ASC, id ASC", $args);
                foreach ($rows as &$r) {
                    $r['id'] = (int)$r['id'];
                    $r['sort_order'] = (int)$r['sort_order'];
                    // 每个分类下的资料数量
                    $cnt = dbFetchOne($db, "SELECT COUNT(*) AS c FROM materials WHERE category_id=?", [$r['id']]);
                    $r['material_count'] = (int)($cnt['c'] ?? 0);
                }
                unset($r);
                jsonOut(true, "", ['categories' => $rows]);
                break;

            // ---------- 资料分类新增 ----------
            case 'material_category_add':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireContentPermission('material_manage');
                $name = trim((string)($_POST['name'] ?? ''));
                $educationLevel = validateEducationLevel($_POST['education_level'] ?? 'junior');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                if ($name === '') jsonOut(false, "分类名称不能为空");
                // 同一学段内名称不能重复
                $exist = dbFetchOne($db, "SELECT id FROM material_categories WHERE name=? AND education_level=?", [$name, $educationLevel]);
                if ($exist) jsonOut(false, "该学段下已有同名分类");
                dbQuery($db, "INSERT INTO material_categories(name, education_level, sort_order) VALUES(?,?,?)", [$name, $educationLevel, $sortOrder]);
                jsonOut(true, "分类已创建", ['id' => (int)$db->lastInsertRowID()]);
                break;

            // ---------- 资料分类编辑 ----------
            case 'material_category_update':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireContentPermission('material_manage');
                $id = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                if ($id <= 0) jsonOut(false, "参数错误");
                if ($name === '') jsonOut(false, "分类名称不能为空");
                $cat = dbFetchOne($db, "SELECT * FROM material_categories WHERE id=?", [$id]);
                if (!$cat) jsonOut(false, "分类不存在");
                // 检查重名
                $exist = dbFetchOne($db, "SELECT id FROM material_categories WHERE name=? AND education_level=? AND id<>?", [$name, $cat['education_level'], $id]);
                if ($exist) jsonOut(false, "该学段下已有同名分类");
                dbQuery($db, "UPDATE material_categories SET name=?, sort_order=? WHERE id=?", [$name, $sortOrder, $id]);
                jsonOut(true, "分类已更新");
                break;

            // ---------- 资料分类删除 ----------
            case 'material_category_delete':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                requireContentPermission('material_manage');
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) jsonOut(false, "参数错误");
                $cat = dbFetchOne($db, "SELECT * FROM material_categories WHERE id=?", [$id]);
                if (!$cat) jsonOut(false, "分类不存在");
                // 有资料的分类不能删
                $cnt = (int)dbFetchOne($db, "SELECT COUNT(*) AS c FROM materials WHERE category_id=?", [$id])['c'];
                if ($cnt > 0) jsonOut(false, "该分类下还有 {$cnt} 份资料，无法删除");
                dbQuery($db, "DELETE FROM material_categories WHERE id=?", [$id]);
                jsonOut(true, "分类已删除");
                break;

            // ---------- 资料需求反馈：用户提交 ----------
            case 'material_request_submit':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = (int)getUid();
                $title = trim(strip_tags((string)($_POST['title'] ?? '')));
                $content = trim(strip_tags((string)($_POST['content'] ?? '')));
                $educationLevel = validateEducationLevel($_POST['education_level'] ?? '', '');
                $subject = trim(strip_tags((string)($_POST['subject'] ?? '')));
                $contact = trim(strip_tags((string)($_POST['contact'] ?? '')));
                if ($content === '' || mb_strlen($content, 'UTF-8') < 2) jsonOut(false, '需求描述至少 2 个字符');
                if (mb_strlen($content, 'UTF-8') > 500) jsonOut(false, '需求描述不能超过 500 个字符');
                if (mb_strlen($title, 'UTF-8') > 50) jsonOut(false, '标题不能超过 50 个字符');
                if (mb_strlen($subject, 'UTF-8') > 30) jsonOut(false, '科目不能超过 30 个字符');
                if (mb_strlen($contact, 'UTF-8') > 50) jsonOut(false, '联系方式不能超过 50 个字符');
                if (preg_match('/[\x00-\x1F\x7F]/u', $content)) jsonOut(false, '内容包含非法字符');
                $user = currentUser();
                [$dayStart, $dayEnd] = chinaTodayUtcBounds();
                try {
                    $db->exec('BEGIN IMMEDIATE');
                    if (!$user || !in_array($user['role'], ['root', 'content_admin'], true)) {
                        $count = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM material_requests WHERE user_id=? AND created_at>=? AND created_at<?', [$uid, $dayStart, $dayEnd])['c'] ?? 0);
                        if ($count >= 5) {
                            $db->exec('ROLLBACK');
                            jsonOut(false, '每位用户每天最多提交 5 条资料需求，请明天再试');
                        }
                    }
                    dbQuery($db,
                        'INSERT INTO material_requests(user_id, title, content, education_level, subject, contact) VALUES(?,?,?,?,?,?)',
                        [$uid, $title, $content, $educationLevel, $subject, $contact]
                    );
                    $newId = (int)$db->lastInsertRowID();
                    $db->exec('COMMIT');
                } catch (Throwable $e) {
                    try { $db->exec('ROLLBACK'); } catch (Throwable $ignored) {}
                    jsonOut(false, '提交失败：' . $e->getMessage());
                }
                jsonOut(true, '需求已提交，我们会尽快处理', ['id' => $newId]);
                break;

            // ---------- 资料需求反馈：我的列表 ----------
            case 'material_request_list_mine':
                if (!isLoggedIn()) jsonOut(false, "请先登录");
                $uid = (int)getUid();
                $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
                $size = max(1, min(50, (int)($_POST['page_size'] ?? $_GET['page_size'] ?? 20)));
                $total = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM material_requests WHERE user_id=?', [$uid])['c'] ?? 0);
                $items = dbFetchAll($db,
                    'SELECT id, title, content, education_level, subject, contact, status, admin_note, handled_at, created_at, updated_at FROM material_requests WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?',
                    [$uid, $size, ($page - 1) * $size]
                );
                foreach ($items as &$item) {
                    foreach (['created_at', 'updated_at', 'handled_at'] as $field) if ($item[$field] !== null) $item[$field] = formatDbUtcTimestamp($item[$field]);
                }
                unset($item);
                jsonOut(true, '', ['items' => $items, 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total]]);
                break;

            // ---------- 资料需求反馈：管理员列表 ----------
            case 'admin_material_request_list':
                $adminUid = requireContentPermission('material_manage');
                $page = max(1, (int)($_POST['page'] ?? 1));
                $size = max(1, min(100, (int)($_POST['page_size'] ?? 20)));
                $status = trim((string)($_POST['status'] ?? ''));
                $keyword = trim((string)($_POST['keyword'] ?? ''));
                $educationLevel = validateEducationLevel($_POST['education_level'] ?? '', '');
                $where = ['1=1']; $args = [];
                if (in_array($status, ['pending', 'processing', 'fulfilled', 'rejected'], true)) { $where[] = 'r.status=?'; $args[] = $status; }
                if ($educationLevel !== '') { $where[] = 'r.education_level=?'; $args[] = $educationLevel; }
                if ($keyword !== '') {
                    $where[] = '(r.title LIKE ? OR r.content LIKE ? OR r.subject LIKE ? OR u.username LIKE ?)';
                    $like = '%' . $keyword . '%';
                    array_push($args, $like, $like, $like, $like);
                }
                $whereSql = implode(' AND ', $where);
                $total = (int)(dbFetchOne($db, "SELECT COUNT(*) AS c FROM material_requests r JOIN users u ON u.id=r.user_id WHERE $whereSql", $args)['c'] ?? 0);
                $items = dbFetchAll($db,
                    "SELECT r.id, r.user_id, u.username, r.title, r.content, r.education_level, r.subject, r.contact, r.status, r.admin_note, r.handled_by, r.handled_at, r.created_at, r.updated_at
                     FROM material_requests r LEFT JOIN users u ON u.id=r.user_id
                     WHERE $whereSql ORDER BY r.id DESC LIMIT ? OFFSET ?",
                    array_merge($args, [$size, ($page - 1) * $size])
                );
                foreach ($items as &$item) {
                    foreach (['created_at', 'updated_at', 'handled_at'] as $field) if ($item[$field] !== null) $item[$field] = formatDbUtcTimestamp($item[$field]);
                }
                unset($item);
                log_admin_action('list_material_requests', $adminUid);
                jsonOut(true, '', ['items' => $items, 'pagination' => ['page' => $page, 'page_size' => $size, 'total' => $total]]);
                break;

            // ---------- 资料需求反馈：管理员处理 ----------
            case 'admin_material_request_update':
                $adminUid = requireContentPermission('material_manage');
                $id = (int)($_POST['id'] ?? 0);
                $action = trim((string)($_POST['request_action'] ?? ''));
                $note = trim(strip_tags((string)($_POST['admin_note'] ?? '')));
                if ($id <= 0 || mb_strlen($note, 'UTF-8') > 500) jsonOut(false, '参数无效或备注过长');
                $req = dbFetchOne($db, 'SELECT id, status FROM material_requests WHERE id=?', [$id]);
                if (!$req) jsonOut(false, '需求不存在');
                $status = $req['status'];
                switch ($action) {
                    case 'fulfill':
                        $status = 'fulfilled';
                        dbQuery($db,
                            'UPDATE material_requests SET status=?, admin_note=?, handled_by=?, handled_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?',
                            [$status, $note, $adminUid, $id]
                        );
                        break;
                    case 'reject':
                        $status = 'rejected';
                        dbQuery($db,
                            'UPDATE material_requests SET status=?, admin_note=?, handled_by=?, handled_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?',
                            [$status, $note, $adminUid, $id]
                        );
                        break;
                    case 'processing':
                        $status = 'processing';
                        dbQuery($db,
                            'UPDATE material_requests SET status=?, admin_note=?, handled_by=?, handled_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?',
                            [$status, $note, $adminUid, $id]
                        );
                        break;
                    case 'reopen':
                        $status = 'pending';
                        dbQuery($db,
                            'UPDATE material_requests SET status=?, handled_by=NULL, handled_at=NULL, updated_at=CURRENT_TIMESTAMP WHERE id=?',
                            [$status, $id]
                        );
                        break;
                    default:
                        jsonOut(false, '操作无效');
                }
                log_admin_action('update_material_request_' . $action, $id);
                jsonOut(true, '状态已更新');
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
                // ★ token 不一次性消费 — 留 5 分钟宽限让 iOS Safari 重试/预检
                // unset($_SESSION['download_tokens'][$token]);  // 之前注释掉, 改为过期自动清理
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
                header('Accept-Ranges: bytes');          // iOS Safari 需要, 支持断点续传
                header('X-Content-Type-Options: nosniff'); // 防 MIME-sniffing, iOS 不强行预览
                header('Content-Length: ' . filesize($realAbs));
                // ★ 优先用 RFC 5987 UTF-8 (iOS/Chrome/Firefox 支持), 老 IE 走 rawurlencode
                if (preg_match('/MSIE|Trident|Edge/i', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
                    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
                } else {
                    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
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
