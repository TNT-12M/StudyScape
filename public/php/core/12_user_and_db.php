<?php

function chinaTodayUtcBounds(): array {
    $china = new DateTimeZone('Asia/Shanghai');
    $utc = new DateTimeZone('UTC');
    $start = new DateTimeImmutable('today', $china);
    $end = $start->modify('+1 day');
    return [$start->setTimezone($utc)->format('Y-m-d H:i:s'), $end->setTimezone($utc)->format('Y-m-d H:i:s')];
}

// 当前自然月（北京时间）的 UTC 起止时间
function currentMonthUtcBounds(): array {
    $china = new DateTimeZone('Asia/Shanghai');
    $utc = new DateTimeZone('UTC');
    $start = new DateTimeImmutable('first day of this month midnight', $china);
    $end = new DateTimeImmutable('first day of next month midnight', $china);
    return [$start->setTimezone($utc)->format('Y-m-d H:i:s'), $end->setTimezone($utc)->format('Y-m-d H:i:s')];
}

function createAuthorizationNotification(int $targetUid, string $type, string $label): void {
    global $db;
    $lian = dbFetchOne($db, "SELECT id FROM users WHERE username='lian' LIMIT 1");
    $target = dbFetchOne($db, "SELECT username FROM users WHERE id=?", [$targetUid]);
    if (!$lian || !$target) return;
    $operator = (string)($_SESSION['username'] ?? '管理员');
    $title = '授权动态';
    $body = sprintf('%s 将用户 %s %s。', $operator, (string)$target['username'], $label);
    try {
        dbQuery($db, "INSERT INTO notifications(user_id,type,title,body,related_id) VALUES(?,?,?,?,?)", [(int)$lian['id'], $type, $title, $body, $targetUid]);
    } catch (Throwable $e) {
        error_log('authorization notification failed: ' . $e->getMessage());
    }
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
    $logDir = __DIR__ . '/../../../data/logs';
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

// 邮箱验证码发码防护常量
define('EMAIL_CODE_DAILY_MAX', 15);           // 每邮箱每用途每天最多 15 次
define('EMAIL_CODE_IP_HOURLY_MAX', 10);       // 每 IP 每小时总共最多 10 次发码请求
define('EMAIL_CODE_IP_DIVERSITY_MAX', 5);     // 每 IP 在窗口内发往不同邮箱最多 5 个
define('EMAIL_CODE_IP_DIVERSITY_WINDOW', 3600); // 去重窗口 1 小时

function loginThrottleIpFile(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $safeIp = preg_replace('/[^a-fA-F0-9.:_-]/', '_', $ip);
    $logDir = __DIR__ . '/../../../data/logs';
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

// 获取当前登录失败状态（剩余次数、是否锁定、锁定剩余秒数）
function getLoginThrottleStatus(): array {
    $sessionFail = (int)($_SESSION['login_fail_count'] ?? 0);
    $sessionLockLeft = 0;
    if (!empty($_SESSION['login_lock_until'])) {
        $sessionLockLeft = max(0, (int)$_SESSION['login_lock_until'] - time());
    }
    $ipFail = 0;
    $ipLockLeft = 0;
    $file = loginThrottleIpFile();
    if (is_file($file)) {
        $raw = @json_decode(@file_get_contents($file), true);
        if (is_array($raw)) {
            $now = time();
            $fails = is_array($raw['fails'] ?? null) ? $raw['fails'] : [];
            $fails = array_values(array_filter($fails, function ($t) use ($now) {
                return ($now - $t) < 300;
            }));
            $ipFail = count($fails);
            if (!empty($raw['lock_until'])) {
                $ipLockLeft = max(0, (int)$raw['lock_until'] - $now);
            }
        }
    }
    $sessionRemaining = max(0, LOGIN_THROTTLE_SESSION_MAX - $sessionFail);
    $ipRemaining = max(0, LOGIN_THROTTLE_IP_MAX - $ipFail);
    $remaining = min($sessionRemaining, $ipRemaining);
    $lockLeft = max($sessionLockLeft, $ipLockLeft);
    return [
        'remaining' => $remaining,
        'lock_left' => $lockLeft,
        'locked' => $lockLeft > 0,
    ];
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
