<?php

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
