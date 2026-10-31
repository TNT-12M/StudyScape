
function recordSiteVisit(): void {
    global $db;
    if (siteStatBlockedAction((string)($_POST['action'] ?? $_GET['action'] ?? ''))) return;
    try {
        $date = siteStatDate();
        $visitor = ensureVisitorCookie();
        $hash = siteVisitorHash($visitor, $date);
        if (!siteRateAllowed('visit:' . substr($hash, 0, 48), 2, 60)) return;
        $db->exec('BEGIN IMMEDIATE');
        $stmt = $db->prepare('INSERT OR IGNORE INTO site_visit_daily_visitors(stat_date, visitor_hash) VALUES(?, ?)');
        $stmt->bindValue(1, $date, SQLITE3_TEXT);
        $stmt->bindValue(2, $hash, SQLITE3_TEXT);
        $stmt->execute();
        if ($db->changes() > 0) {
            $db->exec("INSERT INTO site_visit_daily(stat_date, uv_count, updated_at) VALUES('" . SQLite3::escapeString($date) . "', 1, CURRENT_TIMESTAMP) ON CONFLICT(stat_date) DO UPDATE SET uv_count=uv_count+1, updated_at=CURRENT_TIMESTAMP");
        }
        $db->exec('COMMIT');
    } catch (Throwable $e) {
        try { $db->exec('ROLLBACK'); } catch (Throwable $ignored) {}
        error_log('site visit failed: ' . $e->getMessage());
    }
}

// 验证码图片
if ($action === 'record_visit') {
    recordSiteVisit();
    jsonOut(true, '', ['recorded' => true, 'stat_date' => siteStatDate()]);
}

if ($action === 'captcha') {
    try {
        $builder = createCaptcha();
        // 用较强的抗 OCR 配置: buildAgainstOCR 自带扭曲 + 干扰线 + 噪点
        $builder->buildAgainstOCR(150, 50);
        // 降低颜色对比度, 让 OCR 更难识别; 背景 220, 文字偏深灰
        $builder->setBackgroundColor(220, 220, 220);
        $builder->setTextColor(50, 50, 50);
        $builder->setLineColor(160, 160, 160);
        // ★ Gregwar output() 不发 Content-Type, 必须显式发; 默认 jpeg (见 CaptchaBuilder::$imageType)
        header('Content-Type: image/' . $builder->getImageType());
        $builder->output();
    } catch (\Throwable $e) {
        // Gregwar 依赖 GD 扩展 + TTF 字体, 失败时回退到旧版简单验证码
        header("Content-type: image/png");
        $code = $_SESSION['captcha_code'] ?? 'ERROR';
        $im = imagecreate(150, 50);
        $bg = imagecolorallocate($im, 220, 220, 220);
        $txtcolor = imagecolorallocate($im, 50, 50, 50);
        $linecolor = imagecolorallocate($im, 160, 160, 160);
        for ($i = 0; $i < 3; $i++) {
            imageline($im, 0, random_int(0, 50), 150, random_int(0, 50), $linecolor);
        }
        for ($i = 0; $i < 30; $i++) {
            imagesetpixel($im, random_int(0, 150), random_int(0, 50), $linecolor);
        }
        imagestring($im, 5, 40, 14, $code, $txtcolor);
        imagepng($im);
        imagedestroy($im);
    }
