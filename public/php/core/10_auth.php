
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
