<?php

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 生成图形验证码（5 位混合大写字母+数字，排除易混淆字符 0/O/1/I）
// 底层使用 Gregwar/Captcha（MIT），带扭曲/波浪形变/噪点/干扰线，抗 OCR
function createCaptcha(): \Gregwar\Captcha\CaptchaBuilder {
    // 指定字符集（排除 0/O/1/I 易混淆字符）+ 固定 5 位
    static $phraseBuilder = null;
    if ($phraseBuilder === null) {
        $phraseBuilder = new \Gregwar\Captcha\PhraseBuilder(5, 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
    }
    $builder = new \Gregwar\Captcha\CaptchaBuilder(null, $phraseBuilder);
    // 输出 session 存实际验证码短语 (校验用, 统一大写)
    $_SESSION['captcha_code'] = $builder->getPhrase();
    $_SESSION['captcha_expires'] = time() + 300; // 5 分钟过期
    return $builder;
}

// 将 GD 图像资源以 PNG 格式输出到内存字符串（不产生临时文件）
function capturePng($img, int $quality = 6): string {
    if (!is_resource($img) && !($img instanceof \GdImage)) return '';
    ob_start();
    try {
        imagepng($img, null, $quality);
    } catch (\Throwable $_) {
        ob_end_clean();
        return '';
    }
    $blob = ob_get_clean();
    return $blob === false ? '' : $blob;
}
