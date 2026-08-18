<?php

/**
 * 邮箱验证与密码重置模块
 * 使用 luckycola 第三方邮件 API 发送邮件
 * 验证码本地生成，本地存储验证
 */

// ==================== 邮件API配置 ====================
if (!defined('MAIL_API_URL')) {
    define('MAIL_API_URL', 'https://luckycola.com.cn/tools/customMail');
}
if (!defined('COLA_KEY')) {
    define('COLA_KEY', 'Y0AZMPJuwbbqQc1786950761792HgRlFwHrdu');
}
if (!defined('SMTP_EMAIL')) {
    define('SMTP_EMAIL', '3905262296@qq.com');
}
if (!defined('SMTP_CODE')) {
    define('SMTP_CODE', 'tmlxmuukqdoxccaj');
}
if (!defined('SMTP_TYPE')) {
    define('SMTP_TYPE', 'qq');
}
if (!defined('MAIL_FROM_TITLE')) {
    define('MAIL_FROM_TITLE', '在线考试系统');
}

/**
 * 确保 password_resets 表存在
 */
function ensureResetTable(PDO $pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(10) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(email)
        )");
    } catch (PDOException $e) {}
}

/**
 * 记录邮件API调试日志
 */
function mailDebugLog(string $message): void {
    $logDir = __DIR__ . '/data/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logEntry = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
    @file_put_contents($logDir . '/mail_debug.log', $logEntry, FILE_APPEND);
}

/**
 * 调用 luckycola API 发送邮件（含重试机制）
 * @param string $toMail 收件邮箱
 * @param string $subject 邮件主题
 * @param string $content 邮件内容（支持HTML）
 * @param int $maxRetries 最大重试次数
 * @return array ['success' => bool, 'message' => string]
 */
function sendMailByApi(string $toMail, string $subject, string $content, int $maxRetries = 2): array {
    $lastResult = null;

    for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
        if ($attempt > 0) {
            $delay = $attempt * 2;
            mailDebugLog("第 {$attempt} 次重试，等待 {$delay} 秒...");
            sleep($delay);
        }

        $postData = [
            'ColaKey' => COLA_KEY,
            'tomail' => $toMail,
            'fromTitle' => MAIL_FROM_TITLE,
            'subject' => $subject,
            'content' => $content,
            'isTextContent' => false,
            'smtpCode' => SMTP_CODE,
            'smtpEmail' => SMTP_EMAIL,
            'smtpCodeType' => SMTP_TYPE
        ];

        mailDebugLog("发送邮件(第".($attempt+1)."次): to=$toMail, subject=$subject");

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => MAIL_API_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $apiResult = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        mailDebugLog("邮件API响应: httpCode=$httpCode, raw=$apiResult");

        // 网络层错误：重试
        if ($curlError) {
            $lastResult = ['success' => false, 'message' => '邮件服务连接失败: ' . $curlError];
            continue;
        }
        if ($httpCode != 200) {
            $lastResult = ['success' => false, 'message' => '邮件服务异常 (HTTP ' . $httpCode . ')'];
            continue;
        }
        if (!$apiResult) {
            $lastResult = ['success' => false, 'message' => '邮件服务无响应'];
            continue;
        }

        $apiResponse = json_decode($apiResult, true);
        if ($apiResponse === null) {
            $lastResult = ['success' => false, 'message' => '邮件服务返回数据异常'];
            continue;
        }

        // luckycola 成功：code=0
        if (isset($apiResponse['code']) && $apiResponse['code'] == 0) {
            return ['success' => true, 'message' => '验证码已发送到您的邮箱'];
        }

        // code=-4: SMTP发送失败（QQ可能临时锁定），不重试
        if (isset($apiResponse['code']) && $apiResponse['code'] == -4) {
            return ['success' => false, 'message' => '邮件服务暂时不可用，请稍后再试'];
        }

        // code=-13: 授权码无效
        if (isset($apiResponse['code']) && $apiResponse['code'] == -13) {
            return ['success' => false, 'message' => '邮件配置错误，请联系管理员'];
        }

        // 其他错误：不重试
        $apiMsg = $apiResponse['msg'] ?? ($apiResponse['error'] ?? '未知错误');
        return ['success' => false, 'message' => '邮件发送失败: ' . $apiMsg];
    }

    return $lastResult ?? ['success' => false, 'message' => '邮件服务暂时不可用，请稍后再试'];
}

/**
 * 生成并保存重置验证码，同时通过邮件发送
 * @param PDO $pdo 数据库连接
 * @param string $email 用户邮箱
 * @param string $username 用户名
 * @return array ['success' => bool, 'message' => string]
 */
function sendResetCode(PDO $pdo, string $email, string $username = ''): array {
    ensureResetTable($pdo);

    // 生成6位验证码
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 600);

    // 先存验证码到数据库
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $code, $expires]);

    // 构造邮件内容
    $userName = $username ?: '用户';
    $subject = '密码重置验证码';
    $content = "<div style='max-width:500px;margin:0 auto;font-family:Arial,sans-serif;'>
        <h2 style='color:#4f46e5;text-align:center;'>在线考试系统</h2>
        <p>尊敬的 {$userName}：</p>
        <p>您正在请求重置密码，验证码为：</p>
        <p style='font-size:32px;font-weight:bold;color:#4f46e5;text-align:center;letter-spacing:8px;'>{$code}</p>
        <p style='color:#666;font-size:13px;text-align:center;'>此验证码10分钟内有效，请尽快使用。</p>
        <p style='color:#999;font-size:12px;text-align:center;'>请勿将验证码泄露给他人</p>
        <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'/>
        <p style='color:#999;font-size:12px;text-align:center;'>此邮件由系统自动发送，请勿回复</p>
    </div>";

    // 调用API发送邮件
    $result = sendMailByApi($email, $subject, $content);

    if (!$result['success']) {
        // 发送失败，清除已存的验证码
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
        return $result;
    }

    return ['success' => true, 'message' => '验证码已发送到您的邮箱，请查收'];
}

/**
 * 验证验证码并重置密码（本地验证）
 * @param PDO $pdo 数据库连接
 * @param string $email 用户邮箱
 * @param string $code 验证码
 * @param string $newPassword 新密码
 * @return array ['success' => bool, 'message' => string]
 */
function verifyAndResetPassword(PDO $pdo, string $email, string $code, string $newPassword): array {
    ensureResetTable($pdo);

    // 本地验证验证码
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
    $stmt->execute([$email, $code]);
    $record = $stmt->fetch();

    if (!$record) {
        // 检查是否存在但已过期
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND expires_at > NOW()");
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => '验证码已过期，请重新获取'];
        }
        return ['success' => false, 'message' => '验证码错误'];
    }

    // 验证成功，更新密码
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);

    // 清除已使用的验证码
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);

    return ['success' => true, 'message' => '密码重置成功，请重新登录'];
}
