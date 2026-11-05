<?php
define('LRS', 1);
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/roles.php';
require __DIR__ . '/lib/ai.php';
require __DIR__ . '/lib/game.php';

header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('请使用 POST 请求', 405);
}
if (!preg_match('/^[a-z_]{2,24}$/', $action)) fail('非法请求', 400);

$in = body_json();
$code = strtoupper(trim((string) ($in['code'] ?? '')));
$token = trim((string) ($in['token'] ?? ''));

try {
    switch ($action) {
        case 'create':
            $r = api_create_room((string) ($in['name'] ?? ''), (array) ($in['config'] ?? []), $_SERVER['REMOTE_ADDR'] ?? '');
            jout(['ok' => true] + $r);

        case 'join':
            $r = api_join_room($code, (string) ($in['name'] ?? ''), $token);
            jout(['ok' => true] + $r);

        case 'state':
            if ($code === '' || $token === '') fail('参数不完整', 400);
            $room = room_by_code($code);
            if (!$room) fail('房间不存在或已关闭', 404);
            $me = player_by_token($room, $token);
            if (!$me) fail('身份已失效，请重新进入房间', 401);

            // ====== 心跳优化：last_seen 只有 >=15s 间隔才写 DB（原逻辑太频繁 ======
            $meLastSeen = (int) ($me['last_seen'] ?? 0);
            $nowMs = now_ms();
            if ($meLastSeen < $nowMs - 15000) {
                db()->prepare('UPDATE players SET last_seen = ? WHERE id = ?')->execute([$nowMs, (int) $me['id']]);
            }

            // ====== 条件请求（ETag）：客户端带 If-None-Match，如果版本没变直接 304 ======
            $roomVersion = (int) ($room['version'] ?? 1);
            $etag = '"lrs-' . (int) $room['id'] . '-' . $roomVersion . '"';
            if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
                header('ETag: ' . $etag);
                header('Cache-Control: no-cache');
                http_response_code(304);
                echo '';
                exit;
            }

            // 推进游戏 tick
            tick_room((int) $room['id']);
            // tick 可能改变 version（状态推进），重读一次
            $room = room_by_id((int) $room['id']);
            $roomVersion = (int) ($room['version'] ?? $roomVersion);
            $etag = '"lrs-' . (int) $room['id'] . '-' . $roomVersion . '"';

            $me = player_by_token($room, $token) ?: $me;
            header('ETag: ' . $etag);
            header('Cache-Control: no-cache');
            jout([
                'ok'       => true,
                'state'    => state_for($room, $me),
                'server_now' => now_ms(),
                'version'  => $roomVersion,
            ]);

        case 'add_bot':
            jout(['ok' => true] + api_add_bot($code, $token));

        case 'remove_bot':
            jout(['ok' => true] + api_remove_bot($code, $token, (int) ($in['seat'] ?? -1)));

        case 'kick':
            jout(['ok' => true] + api_kick($code, $token, (int) ($in['seat'] ?? -1)));

        case 'config':
            jout(['ok' => true] + api_update_config($code, $token, (array) ($in['config'] ?? [])));

        case 'start':
            jout(['ok' => true] + api_start($code, $token));

        case 'action':
            jout(['ok' => true] + api_action($code, $token, (string) ($in['type'] ?? ''), isset($in['target']) ? (int) $in['target'] : null, (string) ($in['text'] ?? '')));

        case 'restart':
            jout(['ok' => true] + api_restart($code, $token));

        case 'leave':
            jout(['ok' => true] + api_leave($code, $token));

        case 'rooms':
            jout(['ok' => true] + api_rooms());

        case 'history':
            jout(['ok' => true] + api_history());

        default:
            fail('未知操作', 404);
    }
} catch (Throwable $e) {
    error_log('[lrs] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    fail('服务器开小差了，请稍后再试', 500);
}
