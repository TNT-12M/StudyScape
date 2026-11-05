<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('LRS', 1);
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/roles.php';
require __DIR__ . '/lib/game.php';
require __DIR__ . '/lib/ai.php';

$lockFile = lrs_config('ai.lock');
$fp = fopen($lockFile, 'c');
if (!$fp) exit(1);
if (!flock($fp, LOCK_EX | LOCK_NB)) exit(0);

$deadline = time() + 55;
$processed = 0;

while (time() < $deadline && $processed < 12) {
    $job = null;
    try {
        $job = tx(function () {
            $st = db()->query("SELECT j.* FROM jobs j JOIN rooms r ON r.id = j.room_id WHERE j.status = 'pending' AND r.status = 'playing' ORDER BY j.id LIMIT 1");
            $j = $st->fetch();
            if (!$j) return null;
            db()->prepare('UPDATE jobs SET status = "running", started_at = ? WHERE id = ?')->execute([now_ms(), (int) $j['id']]);
            return $j;
        });
    } catch (Throwable $e) {
        error_log('[lrs-ai] claim: ' . $e->getMessage());
        usleep(300000);
        continue;
    }
    if (!$job) break;
    $processed++;

    $roomId = (int) $job['room_id'];
    $seat = (int) $job['seat'];
    try {
        $room = room_by_id($roomId);
        if (!$room || $room['status'] !== 'playing') {
            mark_job_done((int) $job['id']);
            continue;
        }
        $phase = $room['phase'];
        $kind = $phase === 'lastword' ? 'lastword' : 'speech';
        if (!in_array($phase, ['discuss', 'lastword'], true) || (int) ($room['turn_seat'] ?? -1) !== $seat) {
            mark_job_done((int) $job['id']);
            continue;
        }
        if (speech_exists($roomId, (int) $room['day'], $seat, $kind)) {
            mark_job_done((int) $job['id']);
            continue;
        }
        $text = ai_speech($roomId, $seat);
        tx(function () use ($roomId, $seat, $kind, $text, $job) {
            $r = room_by_id($roomId);
            if (!$r || $r['status'] !== 'playing') return;
            $ph = $r['phase'];
            $k = $ph === 'lastword' ? 'lastword' : 'speech';
            if (!in_array($ph, ['discuss', 'lastword'], true) || (int) ($r['turn_seat'] ?? -1) !== $seat) return;
            if (!speech_exists($roomId, (int) $r['day'], $seat, $k)) {
                msg($roomId, $seat, $k, $text, (int) $r['day'], $ph);
                db()->prepare('UPDATE rooms SET version = version + 1, updated_at = ? WHERE id = ?')->execute([now_ms(), $roomId]);
            }
            db()->prepare('UPDATE jobs SET status = "done", done_at = ? WHERE id = ?')->execute([now_ms(), (int) $job['id']]);
        });
    } catch (Throwable $e) {
        error_log('[lrs-ai] job ' . $job['id'] . ': ' . $e->getMessage());
        mark_job_done((int) $job['id']);
    }
}

function mark_job_done(int $id): void
{
    try {
        db()->prepare('UPDATE jobs SET status = "done", done_at = ? WHERE id = ?')->execute([now_ms(), $id]);
    } catch (Throwable $e) {
    }
}

flock($fp, LOCK_UN);
fclose($fp);
