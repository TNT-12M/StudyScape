<?php
// ===== 伪 Cron：Nginx 日志安全扫描（每 30 分钟一次） =====
// 任意请求进来时检查上次扫描时间，超时则异步拉起 Python 脚本
// 扫描间隔 30 分钟，避免频繁消耗服务器资源
// 返回：['success' => bool, 'message' => string, 'scan_id' => int|null, 'skipped' => bool]
function triggerSecurityScan(bool $force = false): array {
    global $db;
    if (!$db) return ['success' => false, 'message' => '数据库未连接', 'scan_id' => null, 'skipped' => false];

    $scanInterval = 1800; // 30 分钟
    if ($force) $scanInterval = 0;

    // 检查最近的扫描状态
    $lastScan = dbFetchOne($db, "SELECT id, started_at, status FROM security_scan_log WHERE scan_type='nginx_log' ORDER BY id DESC LIMIT 1");
    if (!$force && $lastScan) {
        $lastTime = strtotime($lastScan['started_at'] ?? '');
        if ($lastTime && (time() - $lastTime) < $scanInterval) {
            return ['success' => true, 'message' => '距上次扫描不足30分钟，跳过', 'scan_id' => $lastScan['id'], 'skipped' => true];
        }
        // 如果上一次还在 running，且不超过 10 分钟，不重复触发
        if ($lastScan['status'] === 'running' && $lastTime && (time() - $lastTime) < 600) {
            return ['success' => true, 'message' => '扫描正在进行中', 'scan_id' => $lastScan['id'], 'skipped' => true];
        }
    }

    // 如果上次是 running 且卡住了，先标记为失败
    if ($lastScan && $lastScan['status'] === 'running') {
        $lastTime = strtotime($lastScan['started_at'] ?? '');
        if (!$lastTime || (time() - $lastTime) >= 600) {
            dbQuery($db, "UPDATE security_scan_log SET status='failed', finished_at=?, result_info=? WHERE id=?", [
                date('Y-m-d H:i:s'),
                '扫描超时（超过10分钟未完成），已自动重置',
                $lastScan['id']
            ]);
        }
    }

    // 插入一条扫描记录，标记为 running（防止并发重复触发）
    dbQuery($db, "INSERT INTO security_scan_log(scan_type, started_at, status) VALUES('nginx_log', ?, 'running')", [
        date('Y-m-d H:i:s')
    ]);
    $scanId = $db->lastInsertRowID();

    // 日志文件路径（宝塔默认路径，可通过 .env 配置）
    $logPath = '/www/wwwlogs/120.79.161.207.log';
    $envFile = dirname(dirname(__DIR__)) . '/security.env';
    if (file_exists($envFile)) {
        $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($envLines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (str_starts_with($line, 'NGINX_LOG_PATH=')) {
                $logPath = trim(substr($line, 15));
            }
        }
    }

    if (!file_exists($logPath)) {
        dbQuery($db, "UPDATE security_scan_log SET status='failed', finished_at=?, result_info=? WHERE id=?", [
            date('Y-m-d H:i:s'),
            '日志文件不存在: ' . $logPath,
            $scanId
        ]);
        return ['success' => false, 'message' => '日志文件不存在: ' . $logPath, 'scan_id' => $scanId, 'skipped' => false];
    }

    $dbPath = dirname(dirname(__DIR__)) . '/exam.db';
    $scriptPath = dirname(dirname(__DIR__)) . '/security/log_monitor.py';
    if (!file_exists($scriptPath)) {
        dbQuery($db, "UPDATE security_scan_log SET status='failed', finished_at=?, result_info=? WHERE id=?", [
            date('Y-m-d H:i:s'),
            '扫描脚本不存在: ' . $scriptPath,
            $scanId
        ]);
        return ['success' => false, 'message' => '扫描脚本不存在: ' . $scriptPath, 'scan_id' => $scanId, 'skipped' => false];
    }

    // 异步拉起 Python 脚本（不阻塞当前请求）
    $pythonBin = 'python3';
    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

    if ($isWin) {
        $pythonBin = 'python';
        $cmd = sprintf(
            'start /B "" %s %s %s %s',
            $pythonBin,
            escapeshellarg($scriptPath),
            escapeshellarg($logPath),
            escapeshellarg($dbPath)
        );
    } else {
        $cmd = sprintf(
            'nohup %s %s %s %s > /dev/null 2>&1 & echo $!',
            escapeshellcmd($pythonBin),
            escapeshellarg($scriptPath),
            escapeshellarg($logPath),
            escapeshellarg($dbPath)
        );
    }

    // 尝试多种方式启动（popen 可能被禁用，依次降级）
    $launched = false;
    $errors = [];
    $launchMethod = '';

    // 方式 1：popen + pclose（最标准的异步启动）
    if (function_exists('popen') && function_exists('pclose')) {
        try {
            $handle = @popen($cmd, 'r');
            if ($handle !== false) {
                pclose($handle);
                $launched = true;
                $launchMethod = 'popen';
            }
        } catch (Throwable $e) {
            $errors[] = 'popen: ' . $e->getMessage();
        }
    } else {
        $errors[] = 'popen 函数被禁用';
    }

    // 方式 2：shell_exec（宝塔常见可用，nohup & 后台执行）
    if (!$launched && function_exists('shell_exec')) {
        try {
            $output = @shell_exec($cmd);
            if ($output !== null) {
                $launched = true;
                $launchMethod = 'shell_exec';
            }
        } catch (Throwable $e) {
            $errors[] = 'shell_exec: ' . $e->getMessage();
        }
    } else if (!$launched) {
        $errors[] = 'shell_exec 函数被禁用';
    }

    // 方式 3：exec
    if (!$launched && function_exists('exec')) {
        try {
            @exec($cmd, $output, $retCode);
            $launched = true;
            $launchMethod = 'exec';
        } catch (Throwable $e) {
            $errors[] = 'exec: ' . $e->getMessage();
        }
    }

    // 方式 4：proc_open（最可靠的后台启动方式）
    if (!$launched && function_exists('proc_open')) {
        try {
            $descriptorspec = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w'],  // stderr
            ];
            $proc = @proc_open($cmd, $descriptorspec, $pipes);
            if (is_resource($proc)) {
                // 关闭所有管道，让进程独立运行
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($proc);
                $launched = true;
                $launchMethod = 'proc_open';
            }
        } catch (Throwable $e) {
            $errors[] = 'proc_open: ' . $e->getMessage();
        }
    }

    if (!$launched) {
        error_log('security scan trigger failed: ' . implode('; ', $errors));
        // Python 启动失败，降级用纯 PHP 版扫描（同步执行，保证可用）
        $phpResult = phpSecurityScan($logPath, $scanId);
        if ($phpResult['success']) {
            return ['success' => true, 'message' => '扫描完成（PHP模式，Python启动失败已自动降级）', 'scan_id' => $scanId, 'skipped' => false];
        }
        dbQuery($db, "UPDATE security_scan_log SET status='failed', finished_at=?, result_info=? WHERE id=?", [
            date('Y-m-d H:i:s'),
            '启动失败: ' . implode('; ', $errors) . ' | PHP降级也失败: ' . $phpResult['message'],
            $scanId
        ]);
        return ['success' => false, 'message' => '启动失败: ' . implode('; ', $errors), 'scan_id' => $scanId, 'skipped' => false];
    }

    return ['success' => true, 'message' => "扫描已启动（$launchMethod）", 'scan_id' => $scanId, 'skipped' => false];
}

/**
 * 安全场景配置
 * 每个场景定义检测类型、阈值、严重等级
 */
function getSecurityScenarios(): array {
    return [
        // 场景1：API 速率滥用（5分钟内超过 600 次 API 请求 = 每秒2次，远高于正常用户操作）
        [
            'id' => 'api_rate_abuse',
            'name' => 'API 速率滥用',
            'type' => 'leaky',
            'severity' => 'medium',
            'capacity' => 600,
            'window_seconds' => 300, // 5分钟
            'filter' => 'api', // 只统计 API 请求
            'description' => '短时间内大量 API 请求',
        ],
        // 场景2：路径扫描（10分钟内 60 个不同的 404 URL）
        [
            'id' => 'path_scanning',
            'name' => '路径扫描',
            'type' => 'uniq',
            'severity' => 'high',
            'threshold' => 60,
            'window_minutes' => 10,
            'filter' => '404_path', // 只统计 404 的路径
            'description' => '探测大量不存在的路径，疑似扫描器',
        ],
        // 场景3：异常高请求量（24小时内超过 15000 次总请求，约每分钟10次以上持续24h）
        [
            'id' => 'high_total_volume',
            'name' => '异常高请求量',
            'type' => 'counter',
            'severity' => 'medium',
            'threshold' => 15000,
            'filter' => 'all',
            'description' => '24 小时内请求量异常高',
        ],
        // 场景4：扫描特征密集（24h 内 100 次以上扫描特征路径访问）
        [
            'id' => 'scan_pattern_dense',
            'name' => '扫描特征密集',
            'type' => 'counter',
            'severity' => 'high',
            'threshold' => 100,
            'filter' => 'scan_pattern',
            'description' => '大量访问含扫描特征的路径',
        ],
        // 场景5：高 404 比例 + 绝对量（总请求中 404 占比 > 40% 且 404 次数 > 200）
        [
            'id' => 'high_404_ratio',
            'name' => '高 404 比例',
            'type' => 'ratio',
            'severity' => 'high',
            'numerator_filter' => '404',
            'denominator_filter' => 'all',
            'ratio_threshold' => 0.40,
            'min_count' => 200,
            'description' => '404 比例异常高，疑似扫描器',
        ],
    ];
}

/**
 * 纯 PHP 版安全扫描（不依赖 Python，作为降级方案）
 * 解析 Nginx 访问日志，使用漏桶算法场景检测
 * 不查询地理位置（避免外网请求拖慢速度）
 *
 * 速度优化：
 *   - 手动解析时间字符串（比 DateTime::createFromFormat 快 3-5 倍）
 *   - 扫描特征合并为单个正则（一次匹配替代 30 次 str_contains）
 *   - 先做轻量过滤再做重操作
 */
function phpSecurityScan(string $logPath, int $scanId): array {
    global $db;
    if (!$db) return ['success' => false, 'message' => '数据库未连接'];
    if (!file_exists($logPath)) return ['success' => false, 'message' => '日志文件不存在: ' . $logPath];

    $startTime = microtime(true);
    $windowHours = 24;
    $cutoff = time() - $windowHours * 3600;

    // 加载白名单
    $whitelist = [];
    $envFile = dirname(dirname(__DIR__)) . '/security.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'SECURITY_WHITELIST_IPS=')) {
                $ips = explode(',', substr($line, 23));
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if ($ip) $whitelist[$ip] = true;
                }
            }
        }
    }

    // 已登录用户 IP 白名单：最近 24 小时内有登录记录的正常用户 IP 不参与检测
    // （正常用户操作量高很正常，不应该被当成攻击）
    $activeUserIps = dbFetchAll($db,
        "SELECT DISTINCT last_login_ip FROM users WHERE last_login_ip != '' AND is_active=1 AND last_login_time >= ?",
        [date('Y-m-d H:i:s', $cutoff)]
    );
    foreach ($activeUserIps as $row) {
        $ip = trim($row['last_login_ip'] ?? '');
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            $whitelist[$ip] = true;
        }
    }

    // 扫描特征合并为单个正则（只保留明显的漏洞探测特征，避免误匹配正常业务路径）
    $scanRegex = '/(\.env|\.git|\.svn|phpmyadmin|pma[_\-]|myadmin|wp-admin|wp-login|wordpress|wp-content|xmlrpc\.php|\/etc\/passwd|\/proc\/self|%2e%2e\/|union\s+select|<script.*>|alert\(|eval\(|base64_decode|cmd=.*\b|\bshell\b|fckeditor|kindeditor|ueditor|phpinfo\(\)|actuator|jenkins)/i';

    // 场景配置
    $scenarios = getSecurityScenarios();

    // IP 维度的桶集合：ip -> [scenario_id => bucket_instance]
    $ipBuckets = [];
    // IP 基础统计
    $ipStats = [];

    $lineCount = 0;
    $matched = 0;
    $handle = @fopen($logPath, 'r');
    if (!$handle) return ['success' => false, 'message' => '无法打开日志文件'];

    // 月份映射（快速解析时间用）
    $monthMap = [
        'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4,
        'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8,
        'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12,
    ];

    // Nginx 日志格式：IP - - [时间] "方法 路径 协议" 状态码 长度 "referer" "user-agent"
    // 优化：用更简单的字符串操作 + sscanf 替代完整正则
    while (($line = fgets($handle)) !== false) {
        $lineCount++;

        // 快速提取 IP（第一个空格之前）
        $spPos = strpos($line, ' ');
        if ($spPos === false) continue;
        $ip = substr($line, 0, $spPos);

        // 快速跳过白名单和内网 IP（轻量判断，尽早跳过）
        if (isset($whitelist[$ip])) continue;
        $firstOctet = substr($ip, 0, strcspn($ip, '.'));
        if ($firstOctet === '127' || $firstOctet === '10' || str_starts_with($ip, '192.168.')) continue;

        // 提取时间（方括号内）
        $lbPos = strpos($line, '[', $spPos);
        $rbPos = strpos($line, ']', $lbPos);
        if ($lbPos === false || $rbPos === false) continue;
        $timeStr = substr($line, $lbPos + 1, $rbPos - $lbPos - 1);

        // 快速解析时间（比 DateTime::createFromFormat 快 3-5 倍）
        // 格式：05/Oct/2026:19:51:12 +0800
        $parsedCount = sscanf($timeStr, '%d/%3s/%d:%d:%d:%d %d', $day, $monthStr, $year, $hour, $min, $sec, $tzOffset);
        if ($parsedCount !== 7) continue;
        if (!isset($monthMap[$monthStr])) continue;
        $month = $monthMap[$monthStr];
        $timestamp = gmmktime($hour, $min, $sec, $month, $day, $year);
        // 时区修正（+0800 转成秒减去，转成 UTC）
        $tzSign = ($tzOffset >= 0) ? 1 : -1;
        $tzHours = intval(abs($tzOffset) / 100);
        $tzMins = abs($tzOffset) % 100;
        $timestamp -= $tzSign * ($tzHours * 3600 + $tzMins * 60);

        if ($timestamp < $cutoff) continue;

        // 提取请求方法和路径（第一个引号后）
        $q1Pos = strpos($line, '"', $rbPos);
        $q2Pos = strpos($line, '"', $q1Pos + 1);
        if ($q1Pos === false || $q2Pos === false) continue;
        $request = substr($line, $q1Pos + 1, $q2Pos - $q1Pos - 1);

        // 提取方法和路径
        $sp2Pos = strpos($request, ' ');
        if ($sp2Pos === false) continue;
        $method = substr($request, 0, $sp2Pos);
        $path = substr($request, $sp2Pos + 1);
        // 去掉查询参数后面的协议部分
        $sp3Pos = strrpos($path, ' ');
        if ($sp3Pos !== false) {
            $path = substr($path, 0, $sp3Pos);
        }

        // 提取状态码（第二个引号后）
        $statusStart = $q2Pos + 2; // 跳过 " 和空格
        $sp4Pos = strpos($line, ' ', $statusStart);
        if ($sp4Pos === false) continue;
        $status = substr($line, $statusStart, $sp4Pos - $statusStart);

        $matched++;

        // 初始化 IP 统计
        if (!isset($ipStats[$ip])) {
            $ipStats[$ip] = [
                'api_count' => 0,
                'scan_count' => 0,
                'total_count' => 0,
                'status_404' => 0,
                'last_seen' => 0,
            ];
            // 初始化桶
            $ipBuckets[$ip] = [];
            foreach ($scenarios as $sc) {
                if ($sc['type'] === 'leaky') {
                    $ipBuckets[$ip][$sc['id']] = new LeakyBucket($sc['capacity'], $sc['window_seconds'], $timestamp);
                } elseif ($sc['type'] === 'uniq') {
                    $ipBuckets[$ip][$sc['id']] = new UniqBucket($sc['threshold']);
                } elseif ($sc['type'] === 'counter') {
                    $ipBuckets[$ip][$sc['id']] = new CounterBucket($sc['threshold']);
                } elseif ($sc['type'] === 'ratio') {
                    $ipBuckets[$ip][$sc['id']] = new RatioBucket($sc['ratio_threshold'], $sc['min_count']);
                }
            }
        }

        $stat = &$ipStats[$ip];
        $stat['total_count']++;
        $stat['last_seen'] = max($stat['last_seen'], $timestamp);

        $isApi = (str_contains($path, '/api.php') || str_starts_with($path, '/api/'));
        $is404 = ($status === '404');

        if ($isApi) $stat['api_count']++;
        if ($is404) $stat['status_404']++;

        // 扫描特征检测（单次正则匹配）
        $hasScanPattern = false;
        if (preg_match($scanRegex, $path)) {
            $hasScanPattern = true;
            $stat['scan_count']++;
        }

        // 喂给各个场景桶
        $buckets = &$ipBuckets[$ip];
        foreach ($scenarios as $sc) {
            $bucket = $buckets[$sc['id']];
            if ($bucket->overflowed) continue; // 已经溢出的跳过

            if ($sc['type'] === 'ratio') {
                // 比例桶：每次请求分母+1，满足分子条件时分子+1
                $isNum = false;
                switch ($sc['numerator_filter'] ?? '') {
                    case '404': $isNum = $is404; break;
                    case 'api': $isNum = $isApi; break;
                    case 'scan_pattern': $isNum = $hasScanPattern; break;
                }
                $bucket->add($isNum);
                continue;
            }

            $hit = false;
            switch ($sc['filter']) {
                case 'api':
                    $hit = $isApi;
                    break;
                case '404_path':
                    $hit = $is404;
                    break;
                case 'scan_pattern':
                    $hit = $hasScanPattern;
                    break;
                case 'all':
                    $hit = true;
                    break;
            }

            if (!$hit) continue;

            if ($sc['type'] === 'leaky') {
                $bucket->add($timestamp);
            } elseif ($sc['type'] === 'uniq') {
                // 路径去重用，path 作为唯一值
                // 去掉查询参数，减少重复计数
                $qPos = strpos($path, '?');
                $cleanPath = ($qPos !== false) ? substr($path, 0, $qPos) : $path;
                $bucket->add($cleanPath);
            } elseif ($sc['type'] === 'counter') {
                $bucket->add();
            }
        }
    }
    fclose($handle);

    // 根据桶溢出情况，汇总异常 IP
    $suspicious = [];
    $triggeredScenarios = []; // ip -> [scenario_id, ...]

    foreach ($ipStats as $ip => $stat) {
        $triggered = [];
        $maxSeverity = 'low'; // low < medium < high < critical

        foreach ($scenarios as $sc) {
            $bucket = $ipBuckets[$ip][$sc['id']];
            if ($bucket->overflowed) {
                $triggered[] = $sc['id'];
                // 计算最高严重等级
                $sevOrder = ['low' => 0, 'medium' => 1, 'high' => 2, 'critical' => 3];
                if ($sevOrder[$sc['severity']] > $sevOrder[$maxSeverity]) {
                    $maxSeverity = $sc['severity'];
                }
            }
        }

        if (!empty($triggered)) {
            // 至少触发 1 个场景才是异常
            $risk = $maxSeverity;
            // 触发 2 个及以上 high 场景，升级为 critical 级
            $highCount = 0;
            foreach ($triggered as $sid) {
                foreach ($scenarios as $sc) {
                    if ($sc['id'] === $sid && $sc['severity'] === 'high') $highCount++;
                }
            }
            if ($highCount >= 2) $risk = 'high'; // 数据库目前只有 high/medium，统一用 high
            if ($risk === 'low') $risk = 'medium'; // 至少 medium 才进异常列表

            $suspicious[] = [
                'ip' => $ip,
                'api_count' => $stat['api_count'],
                'scan_count' => $stat['scan_count'],
                'total_count' => $stat['total_count'],
                'status_404' => $stat['status_404'],
                'risk_level' => $risk,
                'last_seen' => date('Y-m-d H:i:s', $stat['last_seen']),
            ];
            $triggeredScenarios[$ip] = $triggered;
        }
    }

    // 按总请求数降序
    usort($suspicious, fn($a, $b) => $b['total_count'] - $a['total_count']);

    // 写入数据库
    $now = date('Y-m-d H:i:s');
    $newCount = 0;
    $newHighCount = 0;
    $newIpsInfo = []; // 新发现的 IP 信息（用于汇总通知）

    foreach ($suspicious as $item) {
        $ip = $item['ip'];
        $scenarioJson = json_encode($triggeredScenarios[$ip] ?? [], JSON_UNESCAPED_UNICODE);
        $scenarioNames = [];
        foreach ($triggeredScenarios[$ip] ?? [] as $sid) {
            foreach ($scenarios as $sc) {
                if ($sc['id'] === $sid) { $scenarioNames[] = $sc['name']; break; }
            }
        }

        $row = dbFetchOne($db, "SELECT id, risk_level, notified, location FROM security_ips WHERE ip=?", [$ip]);

        if ($row) {
            dbQuery($db, "UPDATE security_ips SET api_count=?, scan_count=?, total_count=?, status_404=?, risk_level=?, last_seen=?, updated_at=?, scenarios=? WHERE ip=?", [
                $item['api_count'], $item['scan_count'], $item['total_count'],
                $item['status_404'], $item['risk_level'], $item['last_seen'],
                $now, $scenarioJson, $ip
            ]);
            // 只有 high 级别才发通知，medium 只记录不打扰管理员
            $wasHigh = ($row['risk_level'] === 'high');
            $isHighNow = ($item['risk_level'] === 'high');
            if ($isHighNow && (!$wasHigh || !$row['notified'])) {
                dbQuery($db, "UPDATE security_ips SET notified=1 WHERE ip=?", [$ip]);
                $newCount++;
                $newHighCount++;
                $newIpsInfo[] = [
                    'ip' => $ip,
                    'risk_level' => $item['risk_level'],
                    'api_count' => $item['api_count'],
                    'scenarios' => $scenarioNames,
                ];
            }
        } else {
            $isHigh = ($item['risk_level'] === 'high');
            dbQuery($db, "INSERT INTO security_ips(ip, api_count, scan_count, total_count, status_404, risk_level, location, last_seen, first_detected, updated_at, notified, scenarios) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)", [
                $ip, $item['api_count'], $item['scan_count'], $item['total_count'],
                $item['status_404'], $item['risk_level'], '', $item['last_seen'], $now, $now,
                $isHigh ? 1 : 0, // 只有 high 才标记已通知
                $scenarioJson
            ]);
            // 只有 high 级别才发通知
            if ($isHigh) {
                $newCount++;
                $newHighCount++;
                $newIpsInfo[] = [
                    'ip' => $ip,
                    'risk_level' => $item['risk_level'],
                    'api_count' => $item['api_count'],
                    'scenarios' => $scenarioNames,
                ];
            }
        }
    }

    // 批量发送汇总通知（每次扫描最多 1 条）
    if ($newCount > 0) {
        _insertSecuritySummaryNotification($db, $newCount, $newHighCount, $newIpsInfo);
    }

    // 清理过期 IP（48 小时没出现的移除）
    if ($suspicious) {
        $placeholders = implode(',', array_fill(0, count($suspicious), '?'));
        $ipList = array_column($suspicious, 'ip');
        dbQuery($db, "DELETE FROM security_ips WHERE ip NOT IN ($placeholders) AND last_seen < ?", array_merge($ipList, [date('Y-m-d H:i:s', time() - 48 * 3600)]));
    }

    $duration = round(microtime(true) - $startTime, 2);
    $scenarioSummary = [];
    foreach ($scenarios as $sc) {
        $cnt = 0;
        foreach ($triggeredScenarios as $trig) {
            if (in_array($sc['id'], $trig)) $cnt++;
        }
        if ($cnt > 0) $scenarioSummary[] = $sc['name'] . "×$cnt";
    }

    // 更新扫描状态
    dbQuery($db, "UPDATE security_scan_log SET status='finished', finished_at=?, total_ip_count=?, abnormal_ip_count=?, result_info=? WHERE id=?", [
        $now,
        count($ipStats),
        count($suspicious),
        "PHP漏桶扫描：处理 $lineCount 行（匹配 $matched 条），" . count($suspicious) . " 个异常 IP，$duration 秒。场景：" . implode('、', $scenarioSummary ?: ['无']),
        $scanId
    ]);

    return ['success' => true, 'message' => "扫描完成，发现 " . count($suspicious) . " 个异常 IP（{$duration}s）", 'total' => count($suspicious), 'duration' => $duration];
}

/**
 * 插入安全预警站内通知（PHP版用，复用通知逻辑）
 */
function _insertPhpSecurityNotification($db, $ipData, $location, $scenarioNames = []) {
    // 找 root 用户
    $root = dbFetchOne($db, "SELECT id FROM users WHERE is_initial_root=1 OR username='lian' LIMIT 1");
    if (!$root) return;

    $riskLabel = $ipData['risk_level'] === 'high' ? '高度可疑' : '异常';
    $locStr = $location ? "，地理位置：$location" : '';
    $scenarioStr = $scenarioNames ? '，触发场景：' . implode('、', $scenarioNames) : '';
    $body = "【安全预警】检测到异常 IP {$ipData['ip']}，24 小时内总请求 {$ipData['total_count']} 次（API {$ipData['api_count']} 次），{$riskLabel}（扫描特征 {$ipData['scan_count']} 次，404 {$ipData['status_404']} 次）{$scenarioStr}{$locStr}。最近活跃：{$ipData['last_seen']}";

    dbQuery($db, "INSERT INTO notifications(user_id, title, body, type, created_at) VALUES(?,?,?,?,?)", [
        $root['id'],
        "安全预警：异常 IP {$ipData['ip']}",
        $body,
        'security',
        date('Y-m-d H:i:s')
    ]);
}

/**
 * 批量汇总安全通知（每次扫描最多发 1 条）
 * 避免发现 N 个 IP 就发 N 条通知把通知栏撑爆
 */
function _insertSecuritySummaryNotification($db, $totalNew, $highCount, $ipsInfo) {
    $root = dbFetchOne($db, "SELECT id FROM users WHERE is_initial_root=1 OR username='lian' LIMIT 1");
    if (!$root) return;

    $now = date('Y-m-d H:i:s');
    $title = "【安全预警】发现 {$totalNew} 个异常 IP（高度可疑 {$highCount} 个）";

    // 正文：前 5 个 IP 明细，多的省略
    $bodyLines = [];
    $bodyLines[] = "本次扫描新发现 {$totalNew} 个异常 IP，其中高度可疑 {$highCount} 个。";
    $bodyLines[] = '';

    $showCount = min(5, count($ipsInfo));
    for ($i = 0; $i < $showCount; $i++) {
        $info = $ipsInfo[$i];
        $riskLabel = $info['risk_level'] === 'high' ? '🔴 高度可疑' : '🟡 异常';
        $scenarioStr = $info['scenarios'] ? '（' . implode('、', $info['scenarios']) . '）' : '';
        $bodyLines[] = ($i + 1) . ". {$info['ip']} {$riskLabel} - API 请求 {$info['api_count']} 次{$scenarioStr}";
    }

    if (count($ipsInfo) > 5) {
        $bodyLines[] = '... 及另外 ' . (count($ipsInfo) - 5) . ' 个 IP';
    }

    $bodyLines[] = '';
    $bodyLines[] = '请前往管理员后台 → 概览 → 安全监控 查看详情。';

    $body = implode("\n", $bodyLines);

    dbQuery($db, "INSERT INTO notifications(user_id, title, body, type, created_at) VALUES(?,?,?,?,?)", [
        $root['id'],
        $title,
        $body,
        'security',
        $now
    ]);
}

/**
 * 异步触发地理位置补全（Python 脚本，后台运行）
 * 扫描完成后调用，补全异常 IP 的地理位置信息
 * 静默失败，不影响主流程
 */
function _triggerGeoLookup() {
    global $db;
    if (!$db) return;

    $dbPath = realpath(__DIR__ . '/../../../exam.db');
    if (!$dbPath) return;

    $scriptPath = realpath(__DIR__ . '/../../../security/geo_lookup.py');
    if (!$scriptPath) return;

    // 检查是否还有未补全的 IP，没有就不启动了
    $row = dbFetchOne($db, "SELECT COUNT(*) AS c FROM security_ips WHERE location = '' OR location IS NULL");
    if (!$row || $row['c'] == 0) return;

    // 检查上一次 geo 查找是否还在运行（通过扫描日志中的 geo_lookup 标记）
    // 简化处理：直接后台启动，Python 脚本自己会处理限速和错误
    $cmd = sprintf('python3 %s %s > /dev/null 2>&1 &', escapeshellarg($scriptPath), escapeshellarg($dbPath));

    // 尝试多种启动方式
    $launched = false;
    $errors = [];

    // 方式1: proc_open（最可靠）
    if (function_exists('proc_open')) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];
        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (is_resource($proc)) {
            proc_close($proc);
            $launched = true;
        } else {
            $errors[] = 'proc_open 失败';
        }
    } else {
        $errors[] = 'proc_open 被禁用';
    }

    // 方式2: shell_exec + nohup
    if (!$launched && function_exists('shell_exec')) {
        $cmd2 = sprintf('nohup python3 %s %s > /dev/null 2>&1 &', escapeshellarg($scriptPath), escapeshellarg($dbPath));
        $result = @shell_exec($cmd2);
        $launched = true; // shell_exec 无法可靠判断是否成功，假设成功
    }

    // 方式3: exec
    if (!$launched && function_exists('exec')) {
        $cmd3 = sprintf('python3 %s %s > /dev/null 2>&1 &', escapeshellarg($scriptPath), escapeshellarg($dbPath));
        @exec($cmd3);
        $launched = true;
    }

    // 方式4: popen
    if (!$launched && function_exists('popen')) {
        $cmd4 = sprintf('python3 %s %s &', escapeshellarg($scriptPath), escapeshellarg($dbPath));
        $handle = @popen($cmd4, 'r');
        if ($handle) {
            pclose($handle);
            $launched = true;
        }
    }

    // 静默失败，不记录也不通知（地理位置是锦上添花的功能）
    // error_log 一下方便排查
    if (!$launched) {
        error_log('geo lookup trigger failed: ' . implode(', ', $errors));
    }
}

// 非登录/注册等关键操作时，静默触发（忽略异常）
// （伪 Cron 触发在数据库初始化 + 迁移完成之后，见下方）

/**
 * 生成管理员会话指纹，绑定用户代理和IP前缀，防止 Session 劫持
 * 管理员登录时生成，每次管理员操作都校验
 */
