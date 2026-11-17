<?php
/**
 * 大文件上传专用入口
 *
 * 目的：把「题库导入 / OCR 识别 / 资料上传」这类大体积请求与普通接口隔离。
 *   - 普通接口 /api.php      → Nginx 限制 2MB
 *   - 大文件 /upload.php    → Nginx 限制 52MB（文件 50MB + multipart 开销）
 *
 * 只有白名单内的 action 允许从本入口进入，其余请求直接拒绝，
 * 避免把 50MB 的接收上限暴露给登录、改资料等普通接口。
 */

$allowedActions = [
    'ocr_batch_create',        // OCR 审核识别：上传试卷 PDF / 图片
    'ocr_batch_import_json',   // OCR：直接导入 JSON
    'ocr_batch_commit',        // OCR 审核：入库（含题目图片 base64）
    'import_questions_json',   // 题库：导入题库 JSON
    'material_upload',         // 资料：上传资料文件
    'material_extract',        // 资料：上传待解析文件
];

$requestedAction = $_GET['action'] ?? $_POST['action'] ?? '';
// 请求体超过 PHP post_max_size 时，$_POST 会被整体清空，这里给出准确提示
if ($requestedAction === '' && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    http_response_code(413);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '文件超过 PHP 允许的上传上限，请联系管理员检查 upload_max_filesize / post_max_size',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($requestedAction, $allowedActions, true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => '该接口不支持大文件上传',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 大文件解析/转码需要更多内存与执行时间
@ini_set('memory_limit', '256M');
@set_time_limit(300);

require __DIR__ . '/api.php';
