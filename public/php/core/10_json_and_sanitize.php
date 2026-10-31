<?php

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
