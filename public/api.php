<?php
/**
 * 初高中在线考试系统 - API接口
 * db文件: exam.db 自动生成
 * 表：users, exam_data
 * 密码使用项目外 libsodium 密钥加密存储；密钥路径由 PASSWORD_KEY_FILE 覆盖，默认 /etc/studyscape/password.key。
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ====== 漏桶算法：安全场景检测引擎（类定义放最前面，确保可用） ======

/**
 * 标准漏桶（速率型检测）
 * 事件持续灌入，超过容量则溢出（触发告警）
 * 用于：API 速率滥用、登录爆破等短时间高频请求
 */
class LeakyBucket {
    public $capacity;      // 桶容量
    public $leakRate;    // 漏出速率（每秒漏几个）
    public $level = 0;   // 当前水位
    public $lastLeakAt;  // 上次漏出时间戳
    public $overflowed = false; // 是否已溢出

    public function __construct($capacity, $leakPerSeconds, $startTime) {
        $this->capacity = $capacity;
        $this->leakRate = $capacity / $leakPerSeconds; // 每秒漏出量
        $this->lastLeakAt = $startTime;
    }

    /** 添加一个事件，返回是否溢出 */
    public function add($timestamp) {
        if ($this->overflowed) return true;

        // 先漏出（按时间差计算）
        $elapsed = $timestamp - $this->lastLeakAt;
        if ($elapsed > 0) {
            $this->level = max(0, $this->level - $elapsed * $this->leakRate);
            $this->lastLeakAt = $timestamp;
        }

        // 加水
        $this->level++;

        if ($this->level >= $this->capacity) {
            $this->overflowed = true;
            return true;
        }
        return false;
    }
}

/**
 * 去重漏桶（扫描型检测）
 * 统计不同值的数量，达到阈值就溢出
 * 用于：路径扫描（不同404 URL数量）、端口扫描等
 */
class UniqBucket {
    public $threshold;     // 阈值
    public $values = []; // 去重集合
    public $overflowed = false;

    public function __construct($threshold) {
        $this->threshold = $threshold;
    }

    /** 添加一个值，返回是否溢出 */
    public function add($value) {
        if ($this->overflowed) return true;
        $this->values[$value] = true;
        if (count($this->values) >= $this->threshold) {
            $this->overflowed = true;
            return true;
        }
        return false;
    }

    public function count() {
        return count($this->values);
    }
}

/**
 * 计数器桶（总量型检测）
 * 固定时间窗口内计数，超过阈值溢出
 * 用于：24h 总请求量异常等
 */
class CounterBucket {
    public $threshold;
    public $count = 0;
    public $overflowed = false;

    public function __construct($threshold) {
        $this->threshold = $threshold;
    }

    public function add() {
        if ($this->overflowed) return true;
        $this->count++;
        if ($this->count >= $this->threshold) {
            $this->overflowed = true;
            return true;
        }
        return false;
    }
}

/**
 * 比例桶：统计分子/分母的比例，超过阈值且满足最小计数时溢出
 * 用于检测 404 比例异常高等场景
 */
class RatioBucket {
    public $ratioThreshold;
    public $minCount;
    public $numerator = 0;
    public $denominator = 0;
    public $overflowed = false;

    public function __construct($ratioThreshold, $minCount) {
        $this->ratioThreshold = $ratioThreshold;
        $this->minCount = $minCount;
    }

    /**
     * @param bool $isNumerator 本次请求是否计入分子
     */
    public function add($isNumerator) {
        if ($this->overflowed) return true;
        $this->denominator++;
        if ($isNumerator) $this->numerator++;
        if ($this->denominator >= $this->minCount && $this->denominator > 0) {
            $ratio = $this->numerator / $this->denominator;
            if ($ratio >= $this->ratioThreshold) {
                $this->overflowed = true;
                return true;
            }
        }
        return false;
    }
}

// ===== P0-A5: HTTP 安全头 =====
// 移除 PHP 版本信息泄露
header_remove('X-Powered-By');

// 基础安全头
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

// 内容安全策略（CSP）— 限制资源加载来源，防御 XSS 和数据注入
// 允许 inline 样式和 eval（Vue/Naive UI 需要），图片允许 data: base64（OCR 图片等）
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

// 权限策略 — 禁用不需要的浏览器 API，减少攻击面
header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), gyroscope=(), accelerometer=(), magnetometer=()");

// ===== P0-A6: HTTP 方法限制 =====
// 只允许 GET 和 POST，其他方法一律返回 405
$allowedMethods = ['GET', 'POST'];
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($requestMethod, $allowedMethods, true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => '不支持的请求方法：' . $requestMethod,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== P0-A3: Session 安全加固 =====
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secure,
]);
session_start();

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

    if (!file_exists($logPath)) {
        dbQuery($db, "UPDATE security_scan_log SET status='failed', finished_at=?, result_info=? WHERE id=?", [
            date('Y-m-d H:i:s'),
            '日志文件不存在: ' . $logPath,
            $scanId
        ]);
        return ['success' => false, 'message' => '日志文件不存在: ' . $logPath, 'scan_id' => $scanId, 'skipped' => false];
    }

    $dbPath = dirname(__DIR__) . '/exam.db';
    $scriptPath = dirname(__DIR__) . '/security/log_monitor.py';
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
    $envFile = dirname(__DIR__) . '/security.env';
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

    $dbPath = realpath(__DIR__ . '/../exam.db');
    if (!$dbPath) return;

    $scriptPath = realpath(__DIR__ . '/../security/geo_lookup.py');
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
    return dirname(__DIR__) . '/smtp.env';
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

// ===================== SQLite 初始化 =====================
$dbFile = __DIR__ . '/../exam.db';
try {
    $db = new SQLite3($dbFile);
    $db->enableExceptions(true);
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => '数据库连接失败: ' . $e->getMessage()]));
}
try {
    $passwordKey = loadPasswordKey();
} catch (Throwable $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => '密码密钥初始化失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
}

// 创建表
$createSql = "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    is_initial_root INTEGER NOT NULL DEFAULT 0,
    is_admin INTEGER DEFAULT 0,
    is_approved INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    nickname TEXT DEFAULT NULL,
    avatar TEXT DEFAULT NULL,
    gender TEXT DEFAULT 'secret',
    grade TEXT DEFAULT NULL,
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

-- ===== P1-B: 方案B 组卷考试 / 自由刷题（4 张新表，不动现有表） =====

-- 试卷表（组卷考试用）
CREATE TABLE IF NOT EXISTS papers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    duration_minutes INTEGER NOT NULL DEFAULT 60,
    is_published INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_papers_published ON papers(is_published);

-- 试卷-题目关联表
CREATE TABLE IF NOT EXISTS paper_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paper_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    points REAL NOT NULL DEFAULT 1.0
);
CREATE INDEX IF NOT EXISTS idx_pq_paper ON paper_questions(paper_id);
CREATE INDEX IF NOT EXISTS idx_pq_question ON paper_questions(question_id);

-- 考试/刷题尝试记录（B1 + B2 共用）
CREATE TABLE IF NOT EXISTS exam_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_type TEXT NOT NULL,           -- 'exam' 组卷 / 'practice' 自由刷题
    paper_id INTEGER,                     -- B1时为试卷ID；B2时为NULL
    user_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'in_progress', -- 'in_progress' / 'submitted'
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME,
    duration_minutes INTEGER NOT NULL DEFAULT 0, -- B1来自试卷；B2用户输入，0=不限时
    self_score_total REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_attempts_user ON exam_attempts(user_id);
CREATE INDEX IF NOT EXISTS idx_attempts_paper ON exam_attempts(paper_id);
CREATE INDEX IF NOT EXISTS idx_attempts_type ON exam_attempts(attempt_type);

-- 作答明细表（B1 + B2 共用，每题一条）
CREATE TABLE IF NOT EXISTS exam_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    student_answer TEXT,                 -- 多空填空存 JSON 数组；单选/多选/判断存字符串
    self_correct INTEGER,               -- 1=对,0=错,NULL=未评
    self_score REAL,
    auto_correct INTEGER,               -- 预留：自动判分（本期恒 NULL）
    auto_score REAL,                    -- 预留：自动判分（本期恒 NULL）
    judged_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_answers_attempt ON exam_answers(attempt_id);
CREATE INDEX IF NOT EXISTS idx_answers_question ON exam_answers(question_id);

-- ===== P2-C: 资料下载 =====
CREATE TABLE IF NOT EXISTS materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type TEXT,
    subject TEXT,
    description TEXT,
    uploaded_by INTEGER,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    downloads INTEGER DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_materials_subject ON materials(subject);
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
        $db->exec("ALTER TABLE questions ADD COLUMN updated_at DATETIME");
    }
} catch (Exception $e) {
    // 静默：表不存在等已被 CREATE 兜底
}

// 注册审批已取消：兼容历史账号，统一视为已注册账号。
try {
    $db->exec("UPDATE users SET is_approved=1 WHERE is_approved=0");
} catch (Exception $e) {
    // 静默：用户表已由 CREATE TABLE 兜底
}

// 学段与题库分类迁移：只在缺列时添加，历史记录统一回填为 junior。
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
        created_by INTEGER NOT NULL,
        retry_count INTEGER NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        finished_at DATETIME
    )");
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

function ocrRefreshBatch(array $batch): array {
    global $db;
    $dir = __DIR__ . '/../data/ocr_batches/';
    $base = basename((string)$batch['stored_name']);
    $resultPath = $dir . $base . '.json';
    $logPath = $dir . $base . '.log';
    if (($batch['status'] === 'queued' || $batch['status'] === 'running') && is_file($resultPath)) {
        $json = @file_get_contents($resultPath);
        $data = json_decode((string)$json, true);
        if (is_array($data) && isset($data['questions']) && is_array($data['questions'])) {
            dbQuery($db, "UPDATE ocr_import_batches SET status='review', result_json=?, error_message=NULL, updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=? AND status<>'committed'", [$json, (int)$batch['id']]);
            $batch['status'] = 'review'; $batch['result_json'] = $json; $batch['error_message'] = null;
        }
    } elseif (($batch['status'] === 'queued' || $batch['status'] === 'running') && is_file($logPath) && filesize($logPath) > 0) {
        $error = trim((string)@file_get_contents($logPath));
        if ($error !== '') {
            dbQuery($db, "UPDATE ocr_import_batches SET status='failed', error_message=?, updated_at=CURRENT_TIMESTAMP, finished_at=CURRENT_TIMESTAMP WHERE id=? AND status<>'committed'", [mb_substr($error, 0, 1000), (int)$batch['id']]);
            $batch['status'] = 'failed'; $batch['error_message'] = mb_substr($error, 0, 1000);
        }
    }
    return $batch;
}

function startOcrWorker(array $batch): void {
    $root = realpath(__DIR__ . '/..');
    $dir = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ocr_batches';
    $input = $dir . DIRECTORY_SEPARATOR . basename((string)$batch['stored_name']);
    $output = $input . '.json';
    $log = $input . '.log';
    $python = getenv('PYTHON_BIN') ?: (PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');
    $worker = $root . DIRECTORY_SEPARATOR . 'ocr' . DIRECTORY_SEPARATOR . 'worker.py';
    if (!is_file($worker)) return;
    $cmd = escapeshellarg($python) . ' ' . escapeshellarg($worker) . ' --input ' . escapeshellarg($input) . ' --output ' . escapeshellarg($output) . ' --subject ' . escapeshellarg((string)$batch['subject']) . ' --education-level ' . escapeshellarg((string)$batch['education_level']);
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'start /B "StudyScapeOCR" ' . $cmd . ' > ' . escapeshellarg($log) . ' 2>&1';
        @pclose(@popen($cmd, 'r'));
    } else {
        @exec($cmd . ' > ' . escapeshellarg($log) . ' 2>&1 &');
    }
}

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

/**
 * 安全的 HTML 内容过滤：保留图片、表格等安全标签，移除 XSS 风险
 * 用于题干、解析、选项等可能包含 HTML/图片的字段
 */
function sanitizeHtmlContent($v) {
    $v = trim((string)$v);
    if ($v === '') return '';

    // 先移除所有 script/style/iframe 等危险标签及其内容
    $v = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $v);
    $v = preg_replace('/<\s*style[^>]*>.*?<\s*\/\s*style\s*>/is', '', $v);
    $v = preg_replace('/<\s*iframe[^>]*>.*?<\s*\/\s*iframe\s*>/is', '', $v);

    // 允许的安全标签
    $allowedTags = '<img><div><span><p><br><hr><table><thead><tbody><tr><td><th><ul><ol><li><strong><em><b><i><u><sub><sup><pre><code><blockquote>';
    $v = strip_tags($v, $allowedTags);

    // 移除所有 on* 事件属性（onclick, onerror, onload 等）
    $v = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $v);

    // 移除 javascript: 和 data:text/html 协议的 src/href
    $v = preg_replace('/\s(src|href)\s*=\s*"\s*javascript:[^"]*"/i', '', $v);
    $v = preg_replace("/\s(src|href)\s*=\s*'\s*javascript:[^']*'/i", '', $v);
    $v = preg_replace('/\s(src|href)\s*=\s*"\s*data:text\/html[^"]*"/i', '', $v);
    $v = preg_replace("/\s(src|href)\s*=\s*'\s*data:text\/html[^']*'/i", '', $v);

    // 确保 img 的 src 只允许 data:image 和 http(s) 协议
    if (preg_match_all('/<img[^>]*src\s*=\s*"([^"]*)"[^>]*>/i', $v, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $src = $m[1];
            if (!preg_match('/^(data:image\/|https?:\/\/|\/)/i', $src)) {
                $v = str_replace($m[0], '', $v);
            }
        }
    }

    return $v;
}

/**
 * 校验用户名：2-20 字符，仅允许中英文、数字、下划线、emoji
 */
function validateUsername(string $username): string {
    $username = trim($username);
    if ($username === '') jsonOut(false, '用户名不能为空');
    $len = mb_strlen($username, 'UTF-8');
    if ($len < 2) jsonOut(false, '用户名至少 2 个字符');
    if ($len > 20) jsonOut(false, '用户名不能超过 20 个字符');
    // 仅允许：中文、英文大小写、数字、下划线、emoji（U+1F300-U+1FAFF 等常见表情区）
    if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{1F000}-\x{1F02F}\x{1F0A0}-\x{1F0FF}]+$/u', $username)) {
        jsonOut(false, '用户名只能包含中文、英文、数字、下划线和表情符号');
    }
    return $username;
}

function validateEducationLevel($value, string $default = 'junior'): string {
    $value = strtolower(trim((string)$value));
    if ($value === '') return $default;
    if (!in_array($value, ['junior', 'senior'], true)) {
        jsonOut(false, '学段无效，只能选择初中或高中');
    }
    return $value;
}

function validateQuestionCategory($value, string $default = 'single'): string {
    $value = strtolower(trim((string)$value));
    if ($value === '') return $default;
    if (!in_array($value, ['single', 'multiple', 'judge', 'fill', 'multi_fill', 'short'], true)) {
        jsonOut(false, '分类无效');
    }
    return $value;
}

/**
 * 统一科目名称，防止同义科目因名称不同而分裂
 * 例：道法 → 道德与法治，政治 → 政治（不合并，保留独立科目）
 */
function normalizeSubject(string $subject): string {
    $subject = trim($subject);
    if ($subject === '') return $subject;
    // 别名 → 标准名
    $aliases = [
        '道法' => '道德与法治',
        '思想政治' => '道德与法治',
        '思想品德' => '道德与法治',
        '政史' => '道德与法治',  // 粗略处理，实际应该拆分
        '信息' => '信息技术',
        '计算机' => '信息技术',
        '英语（本）' => '英语',
        '语文（本）' => '语文',
        '数学（本）' => '数学',
    ];
    if (isset($aliases[$subject])) return $aliases[$subject];
    return $subject;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function profileNickname(string $value): ?string {
    $value = trim(preg_replace('/[\\x00-\\x1F\\x7F]/u', '', $value) ?? '');
    if ($value === '') return null;
    $len = mb_strlen($value, 'UTF-8');
    if ($len > 20) {
        jsonOut(false, '昵称不能超过 20 个字符');
    }
    // 仅允许：中文、英文大小写、数字、下划线、空格、emoji
    if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_\s\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{1F000}-\x{1F02F}\x{1F0A0}-\x{1F0FF}]+$/u', $value)) {
        jsonOut(false, '昵称只能包含中文、英文、数字、下划线、空格和表情符号');
    }
    return $value;
}

function profileAvatar(string $value): ?string {
    $value = trim($value);
    if ($value === '') return null;
    if (strlen($value) > 512 || preg_match('/[\\x00-\\x20<>"\\x7F]/', $value)) {
        jsonOut(false, '头像链接格式不正确或长度过长');
    }
    $parts = parse_url($value);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = (string)($parts['host'] ?? '');
    $path = strtolower((string)($parts['path'] ?? ''));
    if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
        jsonOut(false, '头像必须是有效的 http 或 https 图片链接');
    }
    if (!preg_match('/\\.(?:jpe?g|png|gif|webp|avif)$/i', $path)) {
        jsonOut(false, '头像链接必须指向 jpg、png、gif、webp 或 avif 图片');
    }
    return $value;
}

function profileGender(string $value): string {
    $value = trim($value);
    if ($value === '') return 'secret';
    if (!in_array($value, ['secret', 'male', 'female'], true)) {
        jsonOut(false, '性别选项无效');
    }
    return $value;
}

function profileGrade(string $value): ?string {
    $value = trim($value);
    if ($value === '') return null;
    $grades = ['grade7', 'grade8', 'grade9', 'high1', 'high2', 'high3'];
    if (!in_array($value, $grades, true)) {
        jsonOut(false, '年级选项无效');
    }
    return $value;
}

function isLoggedIn(): bool {
    return currentUser() !== null;
}

function currentUser(): ?array {
    global $db;
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0 || !isset($db)) return null;
    $user = dbFetchOne($db, "SELECT id, username, email, nickname, avatar, gender, grade, role, is_initial_root, is_admin, is_active, is_approved FROM users WHERE id=?", [$uid]);
    if (!$user || !(int)$user['is_active']) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['is_admin'], $_SESSION['is_approved'], $_SESSION['role']);
        return null;
    }
    if ($user['username'] === 'lian') {
        $user['role'] = 'root';
        $user['is_initial_root'] = 1;
    }
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = in_array($user['role'], ['root', 'content_admin'], true);
    $_SESSION['is_approved'] = (bool)$user['is_approved'];
    $_SESSION['role'] = $user['role'];
    return $user;
}

function isAdmin(): bool {
    $user = currentUser();
    return $user !== null && in_array($user['role'], ['root', 'content_admin'], true);
}

function isRoot(): bool {
    $user = currentUser();
    return $user !== null && $user['role'] === 'root';
}

function isInitialRoot(): bool {
    $user = currentUser();
    return $user !== null && $user['username'] === 'lian';
}

function requireAdmin() {
    if (!isLoggedIn()) jsonOut(false, "请先登录");
    // 管理员操作必须校验会话指纹，防止 Session 劫持/伪造
    if (!verifyAdminFingerprint()) {
        // 指纹不匹配：清除管理员状态，强制重新登录
        unset($_SESSION['admin_fp'], $_SESSION['is_admin'], $_SESSION['role']);
        jsonOut(false, "管理员会话已失效，请重新登录");
    }
    if (!isAdmin()) jsonOut(false, "权限不足，仅管理员可操作");
    return (int)$_SESSION['user_id'];
}

function requireRoot(): int {
    if (!isLoggedIn()) jsonOut(false, "请先登录");
    if (!verifyAdminFingerprint()) {
        unset($_SESSION['admin_fp'], $_SESSION['is_admin'], $_SESSION['role']);
        jsonOut(false, "管理员会话已失效，请重新登录");
    }
    if (!isRoot()) jsonOut(false, "权限不足，仅 root 可操作");
    return (int)$_SESSION['user_id'];
}

function requireContentPermission(string $permission): int {
    if (!isLoggedIn()) jsonOut(false, "请先登录");
    if (!verifyAdminFingerprint()) {
        unset($_SESSION['admin_fp'], $_SESSION['is_admin'], $_SESSION['role']);
        jsonOut(false, "管理员会话已失效，请重新登录");
    }
    $user = currentUser();
    if (!$user || !in_array($user['role'], ['root', 'content_admin'], true)) {
        jsonOut(false, "权限不足，仅内容管理员或 root 可操作");
    }
    return (int)$user['id'];
}

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
    $logDir = __DIR__ . '/../data/logs';
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

function loginThrottleIpFile(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $safeIp = preg_replace('/[^a-fA-F0-9.:_-]/', '_', $ip);
    $logDir = __DIR__ . '/../data/logs';
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

// ==================== CSRF验证（P0-A1：白名单收窄） ====================
// 登录前拿不到 token 的接口（login/register/captcha/check_session）+ 读接口（题库浏览/考试列表/刷题列表/资料列表）+ 首装专用 create_admin（内部再加首装保护）
$csrfBypass = [
    'login',
    'register',
    'captcha',
    'check_session',
    'send_email_code',
    'reset_password',
    'question_stats',
    'get_subjects',
    'list_questions',
    'get_question',
    // B：考试/刷题读接口（P1 新增时自动免 CSRF，提前占位避免破坏顺序）
    'paper_list',
    'paper_get',
    'attempt_list_mine',
    'attempt_result',
    'practice_subjects',
    'practice_result',
    'feedback_list_mine',
    'notification_list',
    'notification_unread_count',
    // C：资料读接口
    'material_list',
    'material_category_list',
    'material_request_submit',
    'material_request_list_mine',
    'material_download',      // GET 直链，无法带 CSRF，另有一次性 token 鉴权
    // 首页公开概览（未登录即可调用）
    'public_overview',
    'record_visit',
    'admin_access_stats',
    // 安全监控读接口
    'security_scan_status',
    'security_ip_list',
    'security_scan_check',
    // 已移除的历史危险 action 直接返回“功能已移除”，不要求旧客户端提供 CSRF。
    'admin_danger_challenge',
    'clear_users',
    'reset_db',
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
            // ==================== 发送邮箱验证码 ====================
            case 'send_email_code':
                $email = sanitizeInput($_POST['email'] ?? '');
                $purpose = sanitizeInput($_POST['purpose'] ?? 'register'); // register | reset
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));

                if (!in_array($purpose, ['register', 'reset'], true)) {
                    jsonOut(false, '非法用途');
                }
                if (empty($email) || empty($captcha)) {
                    jsonOut(false, '请填写邮箱和图片验证码');
                }
                if (!validateEmail($email)) {
                    jsonOut(false, '邮箱格式不正确');
                }
                if (!isset($_SESSION['captcha_code']) || $_SESSION['captcha_code'] !== $captcha) {
                    unset($_SESSION['captcha_code']);
                    jsonOut(false, '图片验证码错误');
                }
                unset($_SESSION['captcha_code']);

                // 重置密码：检查邮箱是否存在
                if ($purpose === 'reset') {
                    $exist = dbFetchOne($db, "SELECT id FROM users WHERE email=?", [$email]);
                    if (!$exist) {
                        // 统一返回成功，避免枚举邮箱
                        jsonOut(true, '如果该邮箱已注册，验证码将在 60 秒内发送');
                    }
                    // 频率限制：每月不超过 5 次
                    if (!canResetPassword($email)) {
                        jsonOut(false, '该邮箱本月重置密码次数已达上限（5次），请下月再试');
                    }
                }

                // 注册：检查邮箱是否已被注册
                if ($purpose === 'register') {
                    $exist = dbFetchOne($db, "SELECT id FROM users WHERE email=?", [$email]);
                    if ($exist) {
                        jsonOut(false, '该邮箱已被注册');
                    }
                }

                // 冷却期检查（60 秒）
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
                $account = trim((string)($_POST['account'] ?? ''));
                $email = sanitizeInput($_POST['email'] ?? '');
                $emailCode = trim($_POST['email_code'] ?? '');
                $newPassword = $_POST['new_password'] ?? '';
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));

                if (empty($account) || empty($email) || empty($emailCode) || empty($newPassword) || empty($captcha)) {
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

                // 查找用户：支持用户名或邮箱
                $u = dbFetchOne($db, "SELECT * FROM users WHERE (username=? OR email=?) AND email=?", [$account, $account, $email]);
                if (!$u) {
                    // 统一提示，避免枚举
                    jsonOut(false, '账号或邮箱不匹配');
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

                dbQuery($db, 'UPDATE users SET nickname=?, avatar=?, gender=?, grade=? WHERE id=?', [$nickname, $avatar, $gender, $grade, $uid]);
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
                jsonOut(true, '', array_merge($metrics, ['timezone' => 'Asia/Shanghai', 'today' => ['date' => $today->format('Y-m-d'), 'uv' => $todayUv], 'summary' => ['uv_7d' => $uv7d, 'average_uv' => round($uv7d / 7, 2), 'peak_uv' => (int)$peak['uv'], 'peak_date' => $peak['date']], 'trend' => $trend, 'by_subject' => $by_subject, 'by_type' => $by_type]));
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
                dbQuery($db, 'INSERT INTO ocr_import_batches(file_name, stored_name, subject, education_level, created_by, status) VALUES(?,?,?,?,?,\'running\')', [(string)$file['name'], $stored, $subject, $level, $uid]);
                $batchId = (int)$db->lastInsertRowID();
                $batch = dbFetchOne($db, 'SELECT * FROM ocr_import_batches WHERE id=?', [$batchId]);
                startOcrWorker($batch ?: ['id' => $batchId, 'stored_name' => $stored, 'subject' => $subject, 'education_level' => $level]);
                jsonOut(true, '文件已上传，OCR 处理中', ['batch_id' => $batchId, 'status' => 'running']);
                break;

            case 'ocr_batch_list':
                requireContentPermission('question_import');
                $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
                $size = max(1, min(50, (int)($_POST['page_size'] ?? $_GET['page_size'] ?? 20)));
                $total = (int)(dbFetchOne($db, 'SELECT COUNT(*) AS c FROM ocr_import_batches')['c'] ?? 0);
                $items = dbFetchAll($db, 'SELECT id, file_name, stored_name, status, subject, education_level, error_message, retry_count, created_at, updated_at, finished_at, json_extract(result_json, "$.questions") AS questions_json FROM ocr_import_batches ORDER BY id DESC LIMIT ? OFFSET ?', [$size, ($page - 1) * $size]);
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
                $batch = dbFetchOne($db, 'SELECT id, file_name, stored_name, status, subject, education_level, result_json, error_message, retry_count, created_at, updated_at, finished_at FROM ocr_import_batches WHERE id=?', [$id]);
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
                    $kept[] = ['question_id' => (string)($q['source_qid'] ?? count($kept) + 1), 'question_type' => (string)($q['question_type'] ?? 'single'), 'question_content' => $content, 'question_options' => $options, 'answer' => (string)($q['correct_answer'] ?? ''), 'resolve' => (string)($q['explanation'] ?? ''), 'difficulty' => max(1, min(5, (int)($q['difficulty'] ?? 3))), 'points' => max(0, (float)($q['points'] ?? 1)), 'is_html' => !empty($q['is_html']) ? 1 : 0, 'subject' => $batch['subject'], 'education_level' => $batch['education_level']];
                }
                if (!$kept) jsonOut(false, '没有可入库的题目');
                $importData = ['paper_name' => $batch['file_name'], 'subject' => $batch['subject'], 'education_level' => $batch['education_level'], 'questions' => $kept];
                $normalized = [];
                importExtractQuestions($importData, $batch['subject'] ?: '综合', $normalized);
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
                importExtractQuestions($data, $detectedSubject, $questions);

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
                // 一次性
                unset($_SESSION['download_tokens'][$token]);
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
                header('Content-Length: ' . filesize($realAbs));
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                if (preg_match('/MSIE|Trident|Edge/i', $ua)) {
                    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
                } else {
                    header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
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
    // 模式0a：PaperCutter-VL 包装格式（{match_key, paper_name, subject, education_level, questions: [...]}）
    if (isset($data['questions']) && is_array($data['questions'])) {
        // 从包装层提取科目（覆盖"综合"默认值）
        if (!empty($data['subject']) && ($subject === '' || $subject === '综合')) {
            $subject = trim($data['subject']);
        }
        $data = $data['questions'];
    }
    // 模式0b：PaperCutter-VL 扁平格式（含 question_id/question_content/question_options/question_images 等）
    if (pcvl_isPaperCutterVL($data)) {
        $pcvlSubject = $subject !== '' ? $subject : pcvl_detectSubject($data);
        pcvlConvertPaperCutterVL($data, $pcvlSubject, $questions);
        return;
    }
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
    $answer = $q['correct_answer'] ?? ($q['answer'] ?? ($q['答案'] ?? ($q['参考答案'] ?? '')));
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
        'subject'        => normalizeSubject($q['subject'] ?? $subject),
        'question_type'  => $type,
        'category'       => $q['category'] ?? $type,
        'education_level'=> $q['education_level'] ?? 'junior',
        'content'        => $q['content'] ?? ($q['题干'] ?? ''),
        'options'        => $options,
        'correct_answer' => $answer,
        'explanation'    => $q['explanation'] ?? ($q['resolve'] ?? ($q['解析'] ?? null)),
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
        'category'       => $q['category'] ?? $type,
        'education_level'=> $q['education_level'] ?? 'junior',
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
            'category'       => 'fill',
            'education_level'=> $q['education_level'] ?? 'junior',
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
// ======== P1-B 模块：公共帮助函数 ====================
// =====================================================

/**
 * 学生端加载 questions：统一处理 options JSON、去掉 correct_answer / explanation
 * 输入 $links: [[question_id, sort_order, points], ...]
 */
function examB_loadQuestionsForStudent($db, array $links): array {
    $ids = array_filter(array_column($links, 'question_id'));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in)", $ids);
    $byId = [];
    foreach ($qrows as $q) {
        $q['options']    = $q['options'] ? json_decode($q['options'], true) : null;
        $q['difficulty'] = (int)$q['difficulty'];
        $q['points']     = (float)$q['points'];
        // 答题中：永不返回 correct_answer / explanation
        unset($q['correct_answer'], $q['explanation']);
        $byId[(int)$q['id']] = $q;
    }
    $result = [];
    foreach ($links as $link) {
        $qid = (int)($link['question_id'] ?? 0);
        if (!isset($byId[$qid])) continue;
        $item = $byId[$qid];
        if (isset($link['points'])) $item['paper_points'] = (float)$link['points'];
        $result[] = $item;
    }
    return $result;
}

/**
 * 学生端加载 questions：**带正确答案**（仅用于结果页）
 */
function examB_loadQuestionsWithAnswer($db, array $qids): array {
    $ids = array_values(array_filter(array_map('intval', $qids)));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in)", $ids);
    $byId = [];
    foreach ($qrows as $q) {
        $q['options']        = $q['options'] ? json_decode($q['options'], true) : null;
        $q['difficulty']     = (int)$q['difficulty'];
        $q['points']         = (float)$q['points'];
        $q['correct_answer'] = $q['correct_answer'] ?? '';
        $byId[(int)$q['id']] = $q;
    }
    $out = [];
    foreach ($ids as $id) { if (isset($byId[$id])) $out[] = $byId[$id]; }
    return $out;
}

function examB_sanitizePaper($paper): ?array {
    if (!$paper) return null;
    $paper['duration_minutes'] = (int)$paper['duration_minutes'];
    $paper['is_published'] = (int)$paper['is_published'];
    $paper['created_by'] = $paper['created_by'] ? (int)$paper['created_by'] : null;
    return $paper;
}

function examB_getAttemptRow($db, int $aid): ?array {
    $r = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=?", [$aid]);
    if (!$r) return null;
    $r['duration_minutes'] = (int)$r['duration_minutes'];
    $r['paper_id'] = $r['paper_id'] ? (int)$r['paper_id'] : null;
    $r['self_score_total'] = $r['self_score_total'] !== null ? (float)$r['self_score_total'] : null;
    return $r;
}

/**
 * 返回本次作答允许出现的题目及其最高自评分值。
 * 这张白名单同时用于提交答案和自评，避免客户端伪造题号或分数。
 */
function examB_attemptQuestionPoints($db, array $attempt): array {
    if (!empty($attempt['paper_id'])) {
        $rows = dbFetchAll($db, "SELECT question_id, points FROM paper_questions WHERE paper_id=?", [(int)$attempt['paper_id']]);
        $points = [];
        foreach ($rows as $row) $points[(int)$row['question_id']] = max(0.0, (float)$row['points']);
        return $points;
    }
    $rows = dbFetchAll($db, "SELECT ea.question_id, COALESCE(q.points, 1.0) AS points FROM exam_answers ea LEFT JOIN questions q ON q.id=ea.question_id WHERE ea.attempt_id=?", [(int)$attempt['id']]);
    $points = [];
    foreach ($rows as $row) $points[(int)$row['question_id']] = max(0.0, (float)$row['points']);
    return $points;
}

/**
 * 构建提交后的结果页 payload（供 attempt_result / practice_result 调用）
 * $isExam: true=组卷（含试卷信息 + paper_points）；false=自由刷题
 */
function examB_buildResult($db, int $aid, int $uid, bool $isExam): array {
    $attempt = examB_getAttemptRow($db, $aid);
    $paper = null;
    $questionsOrdered = [];
    if ($attempt['paper_id']) {
        $paper = examB_sanitizePaper(dbFetchOne($db, "SELECT * FROM papers WHERE id=?", [(int)$attempt['paper_id']]));
        $links = dbFetchAll($db,
            "SELECT question_id, sort_order, points FROM paper_questions WHERE paper_id=? ORDER BY sort_order ASC, id ASC",
            [(int)$attempt['paper_id']]
        );
        $ids = array_column($links, 'question_id');
        $questionsById = [];
        foreach (examB_loadQuestionsWithAnswer($db, $ids) as $q) { $questionsById[(int)$q['id']] = $q; }
        foreach ($links as $link) {
            $qid = (int)$link['question_id'];
            if (!isset($questionsById[$qid])) continue;
            $item = $questionsById[$qid];
            $item['paper_points'] = (float)$link['points'];
            $item['sort_order'] = (int)$link['sort_order'];
            $questionsOrdered[] = $item;
        }
    } else {
        // practice：按 exam_answers 写入顺序
        $ans = dbFetchAll($db, "SELECT id, question_id FROM exam_answers WHERE attempt_id=? ORDER BY id ASC", [$aid]);
        $ids = array_column($ans, 'question_id');
        $questionsById = [];
        foreach (examB_loadQuestionsWithAnswer($db, $ids) as $q) { $questionsById[(int)$q['id']] = $q; }
        foreach ($ans as $row) {
            $qid = (int)$row['question_id'];
            if (isset($questionsById[$qid])) $questionsOrdered[] = $questionsById[$qid];
        }
    }

    // 读取作答 + 自评
    $ansRows = dbFetchAll($db, "SELECT question_id, student_answer, self_correct, self_score, judged_at FROM exam_answers WHERE attempt_id=?", [$aid]);
    $ansMap = [];
    foreach ($ansRows as $a) {
        $qid = (int)$a['question_id'];
        $a['self_correct'] = $a['self_correct'] !== null ? (int)$a['self_correct'] : null;
        $a['self_score']   = $a['self_score']   !== null ? (float)$a['self_score'] : null;
        $ansMap[$qid] = $a;
    }

    $maxPossible = 0.0;
    $answeredCount = 0;
    $graded = 0; // 已自评题数（self_correct 非空）
    $correctCount = 0;
    foreach ($questionsOrdered as &$q) {
        $qid = (int)$q['id'];
        $a = $ansMap[$qid] ?? null;
        $sa = $a['student_answer'] ?? null;
        // 多空 / 多选：如果是 JSON 数组字符串，返回数组（便于前端渲染）
        if (is_string($sa) && in_array($q['question_type'], ['multi_fill', 'fill', 'multi'], true)) {
            $dec = json_decode($sa, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) $sa = $dec;
        }
        $q['student_answer'] = $sa;
        $q['self_correct']   = $a['self_correct'] ?? null;
        $q['self_score']     = $a['self_score'] ?? null;
        $q['judged_at']      = $a['judged_at'] ?? null;
        $q['max_points']     = (float)($q['paper_points'] ?? $q['points'] ?? 1.0);
        $maxPossible += $q['max_points'];
        if ($sa !== null && $sa !== '' && $sa !== []) $answeredCount++;
        if ($q['self_correct'] !== null) {
            $graded++;
            if ($q['self_correct'] === 1) $correctCount++;
        }
    }

    return [
        'attempt'           => $attempt,
        'paper'             => $paper,
        'questions'         => $questionsOrdered,
        'summary'           => [
            'total_count'       => count($questionsOrdered),
            'answered_count'    => $answeredCount,
            'graded_count'      => $graded,
            'correct_count'     => $correctCount,
            'max_possible'      => $maxPossible,
            'self_score_total'  => $attempt['self_score_total'],
        ],
    ];
}

// =====================================================
// ============== PaperCutter-VL 格式识别与转换 ==============
// =====================================================

/**
 * 判断是否为 PaperCutter-VL 输出格式
 * 特征：顶层数组每条均为对象，含 question_content / question_options / question_type 等字段
 */
function pcvl_isPaperCutterVL(array $data): bool {
    if (!is_array($data) || isset($data[0]) && !is_array($data[0])) return false;
    if (!isset($data[0]) || !is_array($data[0])) return false;
    $keys = array_keys($data[0]);
    $required = ['question_content', 'question_type'];
    $hit = 0;
    foreach ($required as $r) {
        if (in_array($r, $keys, true)) $hit++;
    }
    // 至少命中 2 个 PaperCutter 特有字段即判定为真
    if ($hit < 2) return false;
    // 且必须具备 PaperCutter 独特字段之一
    $paperCutterOnly = ['question_id', 'question_images', 'analysis_images', 'source_province', 'source_year'];
    foreach ($paperCutterOnly as $k) {
        if (in_array($k, $keys, true)) return true;
    }
    return false;
}

/**
 * 从整份 PaperCutter-VL 数据推断科目
 */
function pcvl_detectSubject(array $data): string {
    // 先看是否有 subject 字段非空
    foreach ($data as $q) {
        if (!empty($q['subject']) && is_string($q['subject'])) {
            $s = trim($q['subject']);
            if ($s !== '') return $s;
        }
    }
    // 从 source 里猜（例如 "2025年安徽省初中学业水平考试 道德与法治（开卷）"）
    $subjectMap = [
        '道德与法治' => '道德与法治', '政治' => '政治',
        '历史' => '历史', '地理' => '地理',
        '语文' => '语文', '数学' => '数学', '英语' => '英语',
        '物理' => '物理', '化学' => '化学', '生物' => '生物',
        '信息技术' => '信息技术',
    ];
    foreach ($data as $q) {
        $src = $q['source'] ?? '';
        $cnt = $q['question_content'] ?? '';
        $blob = $src . $cnt;
        foreach ($subjectMap as $k => $v) {
            if ($k !== '政治' && mb_strpos($blob, $k) !== false) return $v;
        }
    }
    return '综合';
}

/**
 * PaperCutter-VL 格式 → 系统扁平数组
 * 主要处理：题型识别、选项（去除 A./B. 前缀）、题干 HTML 富化（base64 图片嵌入、表格内嵌）
 */
function pcvlConvertPaperCutterVL(array $data, string $subject, array &$questions): void {
    $typeMap = [
        '单选题' => 'single', '单选题（四选一）' => 'single',
        '多选题' => 'multiple', '多选题（不定项）' => 'multiple',
        '判断题' => 'judge', '对错题' => 'judge',
        '填空题' => 'fill', '多空填空题' => 'multi_fill',
        '简答题' => 'short', '问答题' => 'short', '论述题' => 'short',
        '选择题' => 'single',
    ];

    foreach ($data as $q) {
        if (!is_array($q)) continue;
        $rawType = $q['question_type'] ?? '';
        $typeKey = $typeMap[$rawType] ?? 'single';

        // 选项处理
        $rawOpts = $q['question_options'] ?? [];
        $options = [];
        if (is_array($rawOpts) && !empty($rawOpts)) {
            foreach ($rawOpts as $opt) {
                if (!is_string($opt)) continue;
                // 去掉 "A. " / "A、" / "A)" / "A)" 这类前缀，保留内容
                $clean = preg_replace('/^\s*[A-Z][.、)）:：\s]+/', '', $opt);
                // 如果只剩了一个字母（比如 "A"），就把原串留下
                if ($clean === '' && preg_match('/^\s*([A-Z])\s*$', $opt, $m)) {
                    $options[] = $opt;
                } else {
                    $options[] = $clean !== '' ? $clean : $opt;
                }
            }
        }

        // 根据选项数推断类型（若只有 "选择题" 这种含糊标签）
        if ($rawType === '选择题') {
            $cnt = count($options);
            if ($cnt === 2) $typeKey = 'judge';
            elseif ($cnt >= 4) $typeKey = 'single';
        }

        // 答案处理（优先使用 answer 字段，兼容 correct_answer 字段）
        $answerText = $q['answer'] ?? ($q['correct_answer'] ?? '');
        $explanation = $q['resolve'] ?? ($q['explanation'] ?? '');

        // 题干富化：把 question_images、analysis_images 里的 base64 补到 HTML 中
        $content = $q['question_content'] ?? '';
        $tables = $q['question_tables'] ?? [];
        $qImages = $q['question_images'] ?? [];
        $aImages = $q['analysis_images'] ?? [];
        $isHtml = false;

        // 若 content 本身已含 HTML（PaperCutter-VL 常常把 base64 <img> 直接塞在 content 里），直接保留
        if (preg_match('/<[a-zA-Z][^>]*>/', $content)) {
            $isHtml = true;
        }
        // 若选项中含有 HTML 标签，也标记为富文本
        if (!empty($options)) {
            foreach ($options as $opt) {
                if (is_string($opt) && preg_match('/<[a-zA-Z][^>]*>/', $opt)) {
                    $isHtml = true;
                    break;
                }
            }
        }
        // 把附加表格追加到 content
        if (is_array($tables) && !empty($tables)) {
            foreach ($tables as $t) {
                if (is_string($t) && trim($t) !== '') {
                    $content .= "\n\n" . $t;
                    $isHtml = true;
                }
            }
        }
        // 图片去重：记录已出现过的 base64 摘要（前 64 字符），避免同一张图重复插入
        $seenImgHash = [];

        // 把题目图片追加到题干 content；解析图片追加到 explanation
        $appendImgs = function (array $imgs, string &$target, bool &$htmlFlag) use (&$seenImgHash) {
            foreach ($imgs as $b64) {
                if (!is_string($b64) || trim($b64) === '') continue;
                // 归一化：去掉 data: 前缀，只取 base64 部分做去重
                $pure = preg_replace('/^data:image\/[a-z]+;base64,/i', '', $b64);
                $pure = ltrim($pure);
                // 用前 80 字符做指纹去重（足够区分不同图，又不会太慢）
                $fingerprint = substr($pure, 0, 80);
                if ($fingerprint === '' || isset($seenImgHash[$fingerprint])) continue;
                $seenImgHash[$fingerprint] = true;

                // 检查 target 中是否已内嵌了这张图（PaperCutter-VL 有时 content 里已有 <img src="data:..."> 又在 images 数组里重复列）
                if (strpos($target, $fingerprint) !== false) continue;

                $src = 'data:image/jpeg;base64,' . $pure;
                $target .= "\n\n<div style=\"text-align:center\"><img src=\"" . htmlspecialchars($src, ENT_QUOTES) . "\" alt=\"图片\" style=\"max-width:100%;height:auto;\"></div>";
                $htmlFlag = true;
            }
        };
        // 题目图片 → 题干
        $appendImgs($qImages, $content, $isHtml);
        // 解析图片 → 解析（不是题干！）
        $appendImgs($aImages, $explanation, $isHtml);

        // 推断难度
        $diff = intval($q['difficulty'] ?? 0);
        if ($diff <= 0) $diff = importEstimateDifficulty($content);

        // 题干太长时用 content-box 展示全文（不再只截断 200 字）
        $points = 2.0; // 默认分
        $sourceLabel = $q['source'] ?? '';
        if ($sourceLabel !== '') {
            $explanation = trim($explanation . "\n（来源：" . $sourceLabel . "）");
        }

        $questions[] = [
            'subject'        => normalizeSubject($subject),
            'question_type'  => $typeKey,
            'category'       => $typeKey,
            'education_level'=> '',   // 由外层按当前选择的学段覆盖
            'content'        => $content,
            'options'        => !empty($options) ? $options : null,
            'correct_answer' => $answerText,
            'explanation'    => $explanation !== '' ? $explanation : null,
            'difficulty'     => $diff,
            'points'         => $points,
            'is_html'        => $isHtml ? 1 : 0,
            '_pcvl_qid'      => $q['question_id'] ?? 0,  // 保留原始题号用于答案匹配
        ];
    }
}
