/**
 * 初高中在线考试系统 - API接口
 * db文件: exam.db 自动生成
 * 表：users, exam_data
 * 密码使用项目外 libsodium 密钥加密存储；密钥路径由 PASSWORD_KEY_FILE 覆盖，默认 /etc/studyscape/password.key。
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ====== PSR-4 Autoloader for Gregwar/Captcha ======
// MIT License (Gregwar/Captcha), 手动 vendor 引入避免 composer 依赖
spl_autoload_register(function ($class) {
    $prefix = 'Gregwar\\Captcha\\';
    $baseDir = __DIR__ . '/vendor/Gregwar/Captcha/src/Gregwar/Captcha/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

