
// ==================== CSRF验证（P0-A1：白名单收窄） ====================
// 登录前拿不到 token 的接口（login/register/captcha/check_session）+ 读接口（题库浏览/考试列表/刷题列表/资料列表）+ 首装专用 create_admin（内部再加首装保护）
$csrfBypass = [
    'login',
    'register',
    'captcha',
    'check_session',
    'send_email_code',
    'lookup_reset_email',
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
