<?php

// ===================== API路由 =====================
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 访问统计只接受服务端生成的匿名 Cookie；统计失败不能阻断业务请求。
function siteStatDate(): string {
    $tz = new DateTimeZone('Asia/Shanghai');
    return (new DateTimeImmutable('now', $tz))->format('Y-m-d');
}

function siteVisitorHash(string $visitorId, string $date): string {
    $secret = getenv('VISITOR_HASH_SECRET') ?: hash('sha256', __FILE__ . '|' . __DIR__);
    return hash_hmac('sha256', $visitorId . '|' . $date, $secret);
}

function ensureVisitorCookie(): string {
    $name = 'site_visitor_id';
    $value = $_COOKIE[$name] ?? '';
    if (!is_string($value) || !preg_match('/^[a-f0-9]{32}$/', $value)) {
        try { $value = bin2hex(random_bytes(16)); } catch (Throwable $e) { $value = md5(uniqid('', true)); }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie($name, $value, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    return $value;
}

function siteStatBlockedAction(string $action): bool {
    if ($action !== 'record_visit') return true;
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, ['GET', 'POST'], true)) return true;
    $ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua === '' || preg_match('/bot|crawler|spider|scrapy|curl|wget|python-requests|go-http-client|headless|nikto|sqlmap|nmap|masscan|zgrab/i', $ua)) return true;
    return false;
}

function siteRateAllowed(string $key, int $limit, int $window): bool {
    global $db;
    try {
        $now = time();
        $row = dbFetchOne($db, 'SELECT window_start, request_count FROM site_visit_rate_limits WHERE bucket_key=?', [$key]);
        if (!$row || $now - (int)$row['window_start'] >= $window) {
            dbQuery($db, 'INSERT OR REPLACE INTO site_visit_rate_limits(bucket_key, window_start, request_count, updated_at) VALUES(?,?,1,CURRENT_TIMESTAMP)', [$key, $now]);
            return true;
        }
        if ((int)$row['request_count'] >= $limit) return false;
        dbQuery($db, 'UPDATE site_visit_rate_limits SET request_count=request_count+1, updated_at=CURRENT_TIMESTAMP WHERE bucket_key=?', [$key]);
        return true;
    } catch (Throwable $e) {
        error_log('site rate limit failed: ' . $e->getMessage());
        return true;
    }
}

function ocrBridgeCall(string $method, string $path, array $data = null): array {
    // 统一的 bridge HTTP 调用封装 (http://127.0.0.1:1201)
    // 返回 ['ok' => bool, 'data' => array, 'http_code' => int, 'curl_error' => string]
    $bridgeHost = 'http://127.0.0.1:1201';
    $url = $bridgeHost . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);  // 15s 超时
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

    if ($method === 'POST') {
        $json = json_encode($data ?: [], JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    }

    $resp = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $decoded = null;
    if ($resp !== false && $resp !== '') {
        $decoded = json_decode((string)$resp, true);
    }

    return [
        'ok' => $resp !== false && $httpCode >= 200 && $httpCode < 400,
        'data' => is_array($decoded) ? $decoded : [],
        'http_code' => $httpCode,
        'curl_error' => $curlErr ?: '',
    ];
}

function ocrRefreshBatch(array $batch): array {
    global $db;
    $curStatus = (string)($batch['status'] ?? '');
    $jobId = trim((string)($batch['bridge_job_id'] ?? ''));

    // ★ 僵尸自愈: queued 但没有 bridge_job_id (startOcrWorker 崩溃跳过了)
    if ($jobId === '' && ($curStatus === 'queued' || $curStatus === 'running')) {
        $age = time() - strtotime($batch['created_at'] ?? 'now');
        if ($age > 3600) {
            // 超过 1 小时还没入队 → startOcrWorker 当时炸了, 标记失败让用户重试
            dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message='OCR 入队超时 (可能当时 bridge_server 不在线), 请点删除后重新上传', updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=?", [(int)$batch['id']]);
            $batch['status'] = 'failed';
            $batch['error_message'] = 'OCR 入队超时, 请重新上传';
            return $batch;
        }
        // 还没超时 → 自动 retry startOcrWorker
        if ($age > 30) {
            startOcrWorker($batch);
            // retry 后重查 DB 状态
            $batch = dbFetchOne($db, 'SELECT * FROM ocr_import_batches WHERE id=?', [(int)$batch['id']]) ?: $batch;
            $jobId = trim((string)($batch['bridge_job_id'] ?? ''));
            $curStatus = (string)($batch['status'] ?? '');
        }
    }

    if ($curStatus === 'review' || $curStatus === 'committed' || $curStatus === 'failed') {
        return $batch;  // 终态不刷新
    }

    if ($jobId === '') return $batch;

    $resp = ocrBridgeCall('GET', '/status/' . urlencode($jobId));

    // ① bridge 连不上（curl 超时/拒绝）
    if ($resp['curl_error'] !== '') {
        // 只是暂时连不上，不要标记失败；等下次刷新再试
        return $batch;
    }
    // ② bridge 返回 404 任务不存在 → 大概率 bridge 重启过丢了队列
    if ($resp['http_code'] === 404) {
        dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message='bridge 任务已过期丢失 (可能 bridge_server 重启过), 请点重试重新提交', updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=? AND status NOT IN ('review','committed','failed')", [(int)$batch['id']]);
        $batch['status'] = 'failed';
        $batch['error_message'] = 'bridge 任务已过期丢失 (可能 bridge_server 重启过), 请点重试重新提交';
        return $batch;
    }
    // ③ bridge 其他错误
    if (!$resp['ok']) {
        return $batch;  // 临时错误，等下次
    }

    $bdata = $resp['data'];
    $bStatus = (string)($bdata['status'] ?? '');

    // ★ 透出阶段 + 进度提示 + 队列位置 (供前端细粒度进度显示)
    $batch['stage'] = (string)($bdata['stage'] ?? '');
    $batch['progress'] = (string)($bdata['progress'] ?? '');
    $batch['queue_position'] = (int)($bdata['queue_position'] ?? 0);

    // ★ 关键: 同步 bridge status → DB status, 否则前端永远显示 "排队中"
    // bridge 的 processing 对应前端 status-processing 分支 (细粒度阶段显示)
    if ($bStatus === 'processing') {
        if ($curStatus === 'queued') {
            dbQuery($db, "UPDATE ocr_import_batches SET status='processing', updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='queued'", [(int)$batch['id']]);
        }
        $batch['status'] = 'processing';
    } elseif ($bStatus === 'queued') {
        // bridge 仍在排队, 但 DB 里如果已经是 processing 就别回退 (race condition)
        if ($curStatus !== 'processing') {
            $batch['status'] = 'queued';
        }
    }

    if ($bStatus === 'done' && isset($bdata['result']) && is_array($bdata['result'])) {
        // ✅ 成功! 把 JSON 塞进 DB
        $json = json_encode($bdata['result'], JSON_UNESCAPED_UNICODE);
        dbQuery($db, "UPDATE ocr_import_batches SET status='review', result_json=?, error_message=NULL, updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=? AND status NOT IN ('review','committed','failed')", [$json, (int)$batch['id']]);
        $batch['status'] = 'review';
        $batch['result_json'] = $json;
        $batch['error_message'] = null;
    } elseif ($bStatus === 'failed') {
        // ❌ Worker 处理失败 (pipeline 报错等)
        $err = trim((string)($bdata['error'] ?? '未知错误'));
        dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message=?, updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=? AND status NOT IN ('review','committed','failed')", [mb_substr($err, 0, 1000), (int)$batch['id']]);
        $batch['status'] = 'failed';
        $batch['error_message'] = $err;
    }

    return $batch;
}

function startOcrWorker(array $batch): void {
    global $db;
    $root = realpath(__DIR__ . '/../..');
    $dir = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ocr_batches';
    $input = $dir . DIRECTORY_SEPARATOR . basename((string)$batch['stored_name']);

    if (!is_file($input)) {
        dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message='上传文件丢失: " . SQLite3::escapeString(basename((string)$batch['stored_name'])) . "', updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=?", [(int)$batch['id']]);
        return;
    }

    // ① 读文件 → base64
    $raw = @file_get_contents($input);
    if ($raw === false || $raw === '') {
        dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message='文件读取失败', updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=?", [(int)$batch['id']]);
        return;
    }
    $b64 = base64_encode($raw);

    // ② 调 bridge 1201/upload
    $payload = [
        'filename' => (string)$batch['file_name'],
        'file_b64' => $b64,
        'subject' => (string)($batch['subject'] ?? ''),
        'education_level' => (string)($batch['education_level'] ?? ''),
        'paper_name' => pathinfo((string)$batch['file_name'], PATHINFO_FILENAME),
    ];
    $resp = ocrBridgeCall('POST', '/upload', $payload);

    // ③ bridge 不可达 (curl 错误)
    if ($resp['curl_error'] !== '') {
        $hint = strpos($resp['curl_error'], 'Connection refused') !== false
            ? 'bridge_server (120.79.161.207:1201) 未启动, 公网服务器执行: sudo systemctl start studyscape-bridge'
            : 'bridge 连接失败: ' . $resp['curl_error'];
        dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message=?, updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=?", [$hint, (int)$batch['id']]);
        return;
    }

    // ④ bridge 返回业务错误 (4xx/5xx) — 带详细 error + detail + hint
    if (!$resp['ok']) {
        $bdata = $resp['data'];
        $mainErr = trim((string)($bdata['error'] ?? ('HTTP ' . $resp['http_code'])));
        $detail = trim((string)($bdata['detail'] ?? ''));
        $hint = trim((string)($bdata['hint'] ?? ''));
        $fullMsg = $mainErr;
        if ($detail !== '') $fullMsg .= "\n详情: " . $detail;
        if ($hint !== '') $fullMsg .= "\n建议: " . $hint;
        dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message=?, updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=?", [mb_substr($fullMsg, 0, 1500), (int)$batch['id']]);
        return;
    }

    // ⑤ 成功! 拿 bridge_job_id 存 DB, status=queued
    $jobId = trim((string)($resp['data']['job_id'] ?? ''));
    if ($jobId === '') {
        dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message='bridge 返回无 job_id', updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=?", [(int)$batch['id']]);
        return;
    }
    dbQuery($db, "UPDATE ocr_import_batches SET status='queued', bridge_job_id=?, error_message=NULL, updated_at=CURRENT_TIMESTAMP WHERE id=?", [$jobId, (int)$batch['id']]);
}
