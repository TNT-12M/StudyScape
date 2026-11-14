<?php
/**
 * 初高中在线考试系统 - API接口
 * db文件: exam.db 自动生成
 * 表：users, exam_data
 * 密码使用项目外 libsodium 密钥加密存储；密钥路径由 PASSWORD_KEY_FILE 覆盖，默认 /etc/studyscape/password.key。
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ====== PSR-4 Autoloader for Gregwar/Captcha ======
// MIT License (Gregwar/Captcha), 手动 vendor 引入避免 composer 依赖
spl_autoload_register(function ($class) {
    $prefix = 'Gregwar\\Captcha\\';
    $baseDir = __DIR__ . '/../../vendor/Gregwar/Captcha/src/Gregwar/Captcha/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

// ===================== 配置加载器 =====================
// 统一读取 server.env / smtp.env / AccessKey.env 格式 (KEY=VALUE)
// 与 Python 端读取格式兼容
if (!function_exists('studyscape_root')) {
    function studyscape_root(): string {
        // public/php/core/ → 项目根 (3 级)
        return dirname(dirname(dirname(__DIR__)));
    }
}

if (!function_exists('loadEnvFile')) {
    /**
     * 加载 .env 格式文件 (KEY=VALUE, 支持 # 注释, 支持 "val with space" 引号)
     * 优先级: 环境变量 > .env 文件 > 默认值
     */
    function loadEnvFile(string $path): array {
        $vars = [];
        if (!is_file($path)) return $vars;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return $vars;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $eq = strpos($line, '=');
            if ($eq === false) continue;
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            // 去掉首尾引号
            if ((str_starts_with($val, '"') && str_ends_with($val, '"'))
             || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if ($key !== '') $vars[$key] = $val;
        }
        return $vars;
    }
}

// 加载 server.env (分布式架构配置) — 项目根目录
$GLOBALS['_studyscape_env'] = loadEnvFile(studyscape_root() . '/server.env');

if (!function_exists('env')) {
    /**
     * 统一取值: 先查 getenv(), 再查 server.env, 再查默认值
     */
    function env(string $key, mixed $default = null): mixed {
        // 1. 进程环境变量 (最优先, 部署时可 docker -e / systemd Environment= 覆盖)
        $v = getenv($key);
        if ($v !== false && $v !== '') return $v;
        // 2. server.env 文件
        if (isset($GLOBALS['_studyscape_env'][$key]) && $GLOBALS['_studyscape_env'][$key] !== '') {
            return $GLOBALS['_studyscape_env'][$key];
        }
        return $default;
    }
}

if (!function_exists('envInt')) {
    function envInt(string $key, int $default = 0): int {
        $v = env($key, $default);
        return (int)$v;
    }
}

if (!function_exists('envBool')) {
    function envBool(string $key, bool $default = false): bool {
        $v = strtolower((string)env($key, $default ? 'true' : 'false'));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }
}
