<?php
function generateAdminFingerprint(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // 取 IP 前三段（IPv4），避免动态 IP 导致频繁失效
    $ipPrefix = implode('.', array_slice(explode('.', $ip), 0, 3));
    return hash('sha256', 'admin_fingerprint_' . $ua . '_' . $ipPrefix . '_' . session_id());
}

function verifyAdminFingerprint(): bool {
    if (empty($_SESSION['admin_fp'])) return false;
    return hash_equals($_SESSION['admin_fp'], generateAdminFingerprint());
}

// ===================== 密码密钥（项目目录之外） =====================
function passwordKeyPath(): string {
    $configured = getenv('PASSWORD_KEY_FILE');
    return $configured !== false && trim($configured) !== ''
        ? trim($configured)
        : '/etc/studyscape/password.key';
}

function loadPasswordKey(): string {
    if (!function_exists('sodium_crypto_secretbox')) {
        throw new RuntimeException('服务器缺少 libsodium，无法安全保存密码');
    }
    $path = passwordKeyPath();
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('密码密钥目录无法创建: ' . $dir);
    }
    if (!is_file($path)) {
        $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $handle = @fopen($path, 'x');
        if ($handle !== false) {
            @fwrite($handle, $key);
            @fflush($handle);
            @fclose($handle);
            @chmod($path, 0600);
        }
    }
    $key = @file_get_contents($path);
    if (!is_string($key) || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RuntimeException('密码密钥不存在、不可读或长度无效: ' . $path);
    }
    return $key;
}

// ===================== SMTP 邮件配置（项目根目录 smtp.env） =====================
function smtpConfigPath(): string {
    $configured = getenv('SMTP_ENV_FILE');
    if ($configured !== false && trim($configured) !== '') return trim($configured);
    return dirname(dirname(__DIR__)) . '/smtp.env';
}

function loadSmtpConfig(): ?array {
    static $config = null;
    if ($config !== null) return $config;
    $path = smtpConfigPath();
    if (!is_file($path)) { $config = false; return null; }
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) { $config = false; return null; }
    $cfg = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $eqPos = strpos($line, '=');
        if ($eqPos === false) continue;
        $key = trim(substr($line, 0, $eqPos));
        $val = trim(substr($line, $eqPos + 1));
        if (str_starts_with($val, '"') && str_ends_with($val, '"')) $val = substr($val, 1, -1);
        if (str_starts_with($val, "'") && str_ends_with($val, "'")) $val = substr($val, 1, -1);
        $cfg[$key] = $val;
    }
    $required = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS'];
    foreach ($required as $k) { if (empty($cfg[$k])) { $config = false; return null; } }
    $config = $cfg;
    return $cfg;
}

/**
 * 使用 SMTP 发送纯文本邮件（支持 STARTTLS）
 * 返回 [true, ''] 或 [false, '错误信息']
 */
function smtpSendMail(string $to, string $subject, string $body): array {
    $cfg = loadSmtpConfig();
    if (!$cfg) return [false, '邮件服务未配置'];

    $host = $cfg['SMTP_HOST'];
    $port = (int)($cfg['SMTP_PORT'] ?? 587);
    $user = $cfg['SMTP_USER'];
    $pass = $cfg['SMTP_PASS'];
    $secure = strtolower($cfg['SMTP_SECURE'] ?? 'tls');
    $fromName = $cfg['SMTP_FROM_NAME'] ?? 'StudyScape';
    $replyTo = $cfg['SMTP_REPLY_TO'] ?? $user;

    // 提取发件邮箱域名，用于 Message-ID 和 EHLO（这是降低垃圾箱概率的关键）
    $emailDomain = substr(strrchr($user, '@'), 1) ?: 'qq.com';
    // EHLO 必须是一个真实存在的域名
    $ehloDomain = $emailDomain;

    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromEncoded = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $date = date('r');
    // Message-ID 域名必须与发件邮箱域名一致
    $messageId = '<' . md5(uniqid((string)mt_rand(), true)) . '.' . time() . '@' . $emailDomain . '>';

    $headers = [];
    $headers[] = "Date: $date";
    $headers[] = "From: $fromEncoded <$user>";
    $headers[] = "To: $to";
    $headers[] = "Subject: $subject";
    $headers[] = "Message-ID: $messageId";
    $headers[] = "Reply-To: $fromEncoded <$replyTo>";
    $headers[] = "Return-Path: <$user>";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $headers[] = "Content-Transfer-Encoding: base64";
    $headers[] = "X-Mailer: StudyScape/1.0";
    // 通知类邮件标识，降低垃圾箱评分
    $headers[] = "Precedence: bulk";
    $headers[] = "Auto-Submitted: auto-generated";
    $headers[] = "X-Auto-Response-Suppress: All";

    $bodyEncoded = chunk_split(base64_encode($body));
    $data = implode("\r\n", $headers) . "\r\n\r\n" . $bodyEncoded;

    try {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);
        $socket = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) return [false, "无法连接 SMTP 服务器: $errstr ($errno)"];
        stream_set_timeout($socket, 30);

        function _smtpRead($socket) {
            $reply = '';
            while ($line = fgets($socket, 515)) {
                $reply .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $reply;
        }
        function _smtpCmd($socket, $cmd, $expect = '250') {
            fputs($socket, $cmd . "\r\n");
            $reply = _smtpRead($socket);
            if (str_starts_with(trim($reply), $expect) === false) {
                throw new RuntimeException("SMTP 命令失败 [$cmd]: " . trim($reply));
            }
            return $reply;
        }

        $banner = _smtpRead($socket);
        if (!str_starts_with(trim($banner), '220')) {
            fclose($socket);
            return [false, 'SMTP 服务器无响应'];
        }

        _smtpCmd($socket, "EHLO $ehloDomain", '250');

        if ($secure === 'tls') {
            _smtpCmd($socket, 'STARTTLS', '220');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return [false, 'TLS 加密失败'];
            }
            _smtpCmd($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '250');
        }

        _smtpCmd($socket, "AUTH LOGIN", '334');
        _smtpCmd($socket, base64_encode($user), '334');
        _smtpCmd($socket, base64_encode($pass), '235');

        _smtpCmd($socket, "MAIL FROM:<$user>", '250');
        _smtpCmd($socket, "RCPT TO:<$to>", '250');
        _smtpCmd($socket, 'DATA', '354');
        fputs($socket, $data . "\r\n.\r\n");
        $reply = _smtpRead($socket);
        if (!str_starts_with(trim($reply), '250')) {
            fclose($socket);
            return [false, '邮件发送失败: ' . trim($reply)];
        }

        _smtpCmd($socket, 'QUIT', '221');
        fclose($socket);
        return [true, ''];
    } catch (Throwable $e) {
        return [false, $e->getMessage()];
    }
}

// ===================== 邮箱验证码通用逻辑 =====================
// 验证码存在 email_codes 表中，按用途（register/reset）区分
function generateEmailCode(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function emailCodeTableExists(): bool {
    global $db;
    static $exists = null;
    if ($exists !== null) return $exists;
    $row = @dbFetchOne($db, "SELECT name FROM sqlite_master WHERE type='table' AND name='email_codes'");
    $exists = !empty($row);
    return $exists;
}

function ensureEmailCodeTable(): void {
    global $db;
    if (emailCodeTableExists()) return;
    @dbQuery($db, "CREATE TABLE email_codes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        code TEXT NOT NULL,
        purpose TEXT NOT NULL,
        ip TEXT NOT NULL DEFAULT '',
        created_at INTEGER NOT NULL,
        expires_at INTEGER NOT NULL,
        used INTEGER NOT NULL DEFAULT 0
    )");
    @dbQuery($db, "CREATE INDEX idx_email_codes_email ON email_codes(email)");
    @dbQuery($db, "CREATE INDEX idx_email_codes_purpose ON email_codes(purpose)");
}

/**
 * 检查同一邮箱是否在冷却期内（60秒），可以发送返回 true
 */
function canSendEmailCode(string $email, string $purpose): bool {
    global $db;
    ensureEmailCodeTable();
    $row = @dbFetchOne($db, "SELECT created_at FROM email_codes WHERE email=? AND purpose=? AND used=0 ORDER BY id DESC LIMIT 1", [$email, $purpose]);
    if (!$row) return true;
    return (time() - (int)$row['created_at']) >= 60;
}

/**
 * 保存邮箱验证码
 */
function saveEmailCode(string $email, string $code, string $purpose): void {
    global $db;
    ensureEmailCodeTable();
    $now = time();
    $expires = $now + 300; // 5分钟
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    @dbQuery($db, "INSERT INTO email_codes(email,code,purpose,ip,created_at,expires_at,used) VALUES(?,?,?,?,?,?,0)", [$email, $code, $purpose, $ip, $now, $expires]);
    // 清理过期记录（每次插入顺便清理，避免表膨胀）
    @dbQuery($db, "DELETE FROM email_codes WHERE expires_at < ?", [time() - 86400]);
}

/**
 * 验证邮箱验证码，成功返回 true 并标记已使用
 */
function verifyEmailCode(string $email, string $code, string $purpose): bool {
    global $db;
    ensureEmailCodeTable();
    $now = time();
    $row = @dbFetchOne($db, "SELECT id, code, expires_at, used FROM email_codes WHERE email=? AND purpose=? AND used=0 ORDER BY id DESC LIMIT 1", [$email, $purpose]);
    if (!$row) return false;
    if ((int)$row['expires_at'] < $now) return false;
    if (!hash_equals($row['code'], $code)) return false;
    @dbQuery($db, "UPDATE email_codes SET used=1 WHERE id=?", [(int)$row['id']]);
    return true;
}

/**
 * 检查重置密码频率：每月每邮箱不超过 5 次
 */
function canResetPassword(string $email): bool {
    global $db;
    ensureEmailCodeTable();
    $startOfMonth = strtotime(date('Y-m-01 00:00:00'));
    $row = @dbFetchOne($db, "SELECT COUNT(*) AS c FROM email_codes WHERE email=? AND purpose='reset' AND created_at >= ?", [$email, $startOfMonth]);
    return ((int)($row['c'] ?? 0)) < 5;
}

/**
 * 检查注册用途月度上限：每邮箱每月不超过 10 次发码请求（防止单邮箱反复刷）
 */
function canSendRegisterEmailCode(string $email): bool {
    global $db;
    ensureEmailCodeTable();
    $startOfMonth = strtotime(date('Y-m-01 00:00:00'));
    $row = @dbFetchOne($db, "SELECT COUNT(*) AS c FROM email_codes WHERE email=? AND purpose='register' AND created_at >= ?", [$email, $startOfMonth]);
    return ((int)($row['c'] ?? 0)) < 10;
}

/**
 * 每日上限：同一邮箱同一用途每天不超过 EMAIL_CODE_DAILY_MAX 次
 * 返回 ['ok'=>bool, 'count'=>int]
 */
function canSendEmailCodeDaily(string $email, string $purpose): array {
    global $db;
    ensureEmailCodeTable();
    $todayStart = strtotime(date('Y-m-d 00:00:00'));
    $row = @dbFetchOne($db, "SELECT COUNT(*) AS c FROM email_codes WHERE email=? AND purpose=? AND created_at >= ?", [$email, $purpose, $todayStart]);
    $count = (int)($row['c'] ?? 0);
    return ['ok' => $count < EMAIL_CODE_DAILY_MAX, 'count' => $count];
}

/**
 * 获取客户端真实 IP（处理 Cloudflare/CDN 代理头，兜底 REMOTE_ADDR）
 */
function getClientIp(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * IP 级发码总频率检查：每 IP 每小时不超过 EMAIL_CODE_IP_HOURLY_MAX 次请求
 * 使用 site_visit_rate_limits 表，失败放行（降级）
 */
function checkEmailIpRate(string $ip): bool {
    try {
        return siteRateAllowed('email_code_ip_' . $ip, EMAIL_CODE_IP_HOURLY_MAX, 3600);
    } catch (Throwable $e) {
        error_log('checkEmailIpRate error: ' . $e->getMessage());
        return true;
    }
}

/**
 * per-IP 去重邮箱计数：同一 IP 在 EMAIL_CODE_IP_DIVERSITY_WINDOW 秒内发往不同邮箱数量上限
 * 防止攻击者轮换大量不同伪造邮箱绕过 per-email 冷却
 * 返回 ['ok'=>bool, 'unique_count'=>int, 'limit'=>int]
 */
function checkEmailIpDiversity(string $ip, string $email): array {
    global $db;
    try {
        ensureEmailCodeTable();
        $cutoff = time() - EMAIL_CODE_IP_DIVERSITY_WINDOW;
        $row = @dbFetchOne($db, "SELECT COUNT(DISTINCT email) AS c FROM email_codes WHERE ip=? AND created_at >= ?", [$ip, $cutoff]);
        $count = (int)($row['c'] ?? 0);
        // 如果当前 email 已在之前出现过，不算新的"新增邮箱"
        $isNew = $count === 0 || !@dbFetchOne($db, "SELECT 1 FROM email_codes WHERE ip=? AND email=? AND created_at >= ? LIMIT 1", [$ip, $email, $cutoff]);
        $effectiveCount = $count + ($isNew ? 1 : 0);
        return ['ok' => $effectiveCount <= EMAIL_CODE_IP_DIVERSITY_MAX, 'unique_count' => $effectiveCount, 'limit' => EMAIL_CODE_IP_DIVERSITY_MAX];
    } catch (Throwable $e) {
        error_log('checkEmailIpDiversity error: ' . $e->getMessage());
        return ['ok' => true, 'unique_count' => 0, 'limit' => EMAIL_CODE_IP_DIVERSITY_MAX];
    }
}

function encryptPassword(string $password): string {
    global $passwordKey;
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = sodium_crypto_secretbox($password, $nonce, $passwordKey);
    return 'enc:v1:' . base64_encode($nonce . $cipher);
}

function decryptPassword(string $stored): ?string {
    global $passwordKey;
    if (!str_starts_with($stored, 'enc:v1:')) return null;
    $payload = base64_decode(substr($stored, 7), true);
    if ($payload === false || strlen($payload) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) return null;
    $nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    try {
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $passwordKey);
        return $plain === false ? null : $plain;
    } catch (Throwable $e) {
        return null;
    }
}

function passwordMatches(string $stored, string $candidate): bool {
    if (str_starts_with($stored, 'enc:v1:')) {
        $plain = decryptPassword($stored);
        return $plain !== null && hash_equals($plain, $candidate);
    }
    return hash_equals($stored, $candidate);
}
