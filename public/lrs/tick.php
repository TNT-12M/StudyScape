<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('LRS', 1);
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/roles.php';
require __DIR__ . '/lib/game.php';
require __DIR__ . '/lib/ai.php';

$dataDir = lrs_config('data_dir');
$fp = fopen($dataDir . '/tick.lock', 'c');
if (!$fp) exit(1);
if (!flock($fp, LOCK_EX | LOCK_NB)) exit(0);

try {
    // 重置卡住的任务
    db()->prepare('UPDATE jobs SET status = "pending" WHERE status = "running" AND started_at < ?')->execute([now_ms() - 90000]);

    $st = db()->query("SELECT id FROM rooms WHERE status = 'playing' ORDER BY id");
    foreach ($st->fetchAll() as $r) {
        try {
            tx(function () use ($r) { game_tick((int) $r['id']); });
        } catch (Throwable $e) {
            error_log('[lrs-tick] room ' . $r['id'] . ': ' . $e->getMessage());
        }
    }

    // 清理 24 小时前的房间
    $old = now_ms() - 24 * 3600 * 1000;
    db()->prepare('DELETE FROM rooms WHERE status != "playing" AND updated_at < ?')->execute([$old]);
    db()->prepare('DELETE FROM results WHERE created_at < ?')->execute([now_ms() - 7 * 24 * 3600 * 1000]);
} catch (Throwable $e) {
    error_log('[lrs-tick] ' . $e->getMessage());
}

flock($fp, LOCK_UN);
fclose($fp);
