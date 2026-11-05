<?php
if (!defined('LRS')) exit;

const AVATARS = ['🦊', '🐼', '🐨', '🐯', '🦁', '🐸', '🐵', '🐧', '🦉', '🐢', '🦄', '🐙', '🐳', '🦋', '🐰', '🐻', '🐶', '🐱', '🦝', '🐹'];
const BOT_NAMES = ['阿柴', '三月', '橘子', '老白', '小满', '青柠', '木木', '阿岚', '十一', '豆子', '南山', '可乐', '半仙', '阿九', '雾都', '铁蛋', '小鹿', '河马', '栗子', '晚风'];

const PERSONAS = [
    ['id' => 'veteran', 'name' => '老江湖', 'style' => '沉稳老练，喜欢先盘逻辑再下结论，常说我盘一下、先听后面的'],
    ['id' => 'rush', 'name' => '冲锋', 'style' => '语气强硬，敢点名，喜欢直接归票'],
    ['id' => 'calm', 'name' => '理性派', 'style' => '冷静克制，讲证据和概率，不爱情绪化'],
    ['id' => 'sassy', 'name' => '阴阳师', 'style' => '阴阳怪气，喜欢反问和挤兑别人'],
    ['id' => 'rookie', 'name' => '萌新', 'style' => '有点紧张，说话直白，会承认自己不太会玩'],
    ['id' => 'logic', 'name' => '逻辑怪', 'style' => '爱分析发言顺序和投票，条理清晰'],
    ['id' => 'quiet', 'name' => '闷葫芦', 'style' => '话少，句子短，偶尔冒出一句关键判断'],
    ['id' => 'actor', 'name' => '戏精', 'style' => '情绪丰富，喜欢夸张表达，带节奏'],
];

/* ---------- 基础读取 ---------- */

function room_by_code(string $code): ?array
{
    $st = db()->prepare('SELECT * FROM rooms WHERE code = ?');
    $st->execute([strtoupper($code)]);
    $r = $st->fetch();
    return $r ?: null;
}

function room_by_id(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM rooms WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
}

function room_players(int $roomId): array
{
    $st = db()->prepare('SELECT * FROM players WHERE room_id = ? ORDER BY seat ASC');
    $st->execute([$roomId]);
    return $st->fetchAll();
}

function player_by_token(array $room, string $token): ?array
{
    $st = db()->prepare('SELECT * FROM players WHERE room_id = ? AND token = ?');
    $st->execute([(int) $room['id'], $token]);
    $p = $st->fetch();
    return $p ?: null;
}

function alive_players(array $players): array
{
    return array_values(array_filter($players, fn($p) => (int) $p['alive'] === 1));
}

function by_seat(array $players, int $seat): ?array
{
    foreach ($players as $p) if ((int) $p['seat'] === $seat) return $p;
    return null;
}

function player_by_role(array $players, string $role): array
{
    return array_values(array_filter($players, fn($p) => $p['role'] === $role && (int) $p['alive'] === 1));
}

function pstate(array $p): array
{
    return jarr($p['pstate'] ?? null, []);
}

function save_pstate(int $playerId, array $st): void
{
    db()->prepare('UPDATE players SET pstate = ? WHERE id = ?')->execute([jsonv($st), $playerId]);
}

function set_phase(int $roomId, string $phase, int $seconds = 0, ?int $turnSeat = null, int $turnIndex = 0): void
{
    $end = $seconds > 0 ? now_ms() + $seconds * 1000 : 0;
    db()->prepare('UPDATE rooms SET phase = ?, phase_end = ?, turn_seat = ?, turn_index = ?, version = version + 1, updated_at = ? WHERE id = ?')
        ->execute([$phase, $end, $turnSeat, $turnIndex, now_ms(), $roomId]);
}

function msg(int $roomId, ?int $seat, string $kind, string $text, ?int $day = null, ?string $phase = null): int
{
    $room = room_by_id($roomId);
    $st = db()->prepare('INSERT INTO messages (room_id, day, phase, seat, kind, text, created_at) VALUES (?,?,?,?,?,?,?)');
    $st->execute([$roomId, $day ?? (int) ($room['day'] ?? 0), $phase ?? ($room['phase'] ?? ''), $seat, $kind, $text, now_ms()]);
    return (int) db()->lastInsertId();
}

function sysmsg(int $roomId, string $text): int
{
    return msg($roomId, null, 'system', $text);
}

function last_msg_day(int $roomId): int
{
    $r = room_by_id($roomId);
    return (int) ($r['day'] ?? 0);
}

/* ---------- 房间管理 ---------- */

function sanitize_config(array $in): array
{
    $seats = (int) ($in['seats'] ?? 9);
    $seats = max(5, min(16, $seats));
    $roles = $in['roles'] ?? preset_for($seats);
    if (!is_array($roles)) $roles = preset_for($seats);
    $roles = normalize_roles($roles, $seats);
    $ai = (($in['ai'] ?? 'llm') === 'rule') ? 'rule' : 'llm';
    return ['seats' => $seats, 'roles' => $roles, 'ai' => $ai];
}

function api_create_room(string $name, array $cfg, string $ip): array
{
    $name = clean_name($name);
    $cfg = sanitize_config($cfg);
    return tx(function () use ($name, $cfg, $ip) {
        $code = room_code();
        $now = now_ms();
        db()->prepare('INSERT INTO rooms (code,status,phase,day,phase_end,config,host_seat,ai_mode,created_at,updated_at,version) VALUES (?,?,?,?,?,?,?,?,?,?,1)')
            ->execute([$code, 'lobby', 'lobby', 0, 0, jsonv($cfg), 0, $cfg['ai'], $now, $now]);
        $roomId = (int) db()->lastInsertId();
        $token = new_token();
        $persona = null;
        db()->prepare('INSERT INTO players (room_id,seat,name,token,is_bot,is_host,role,alive,persona,avatar,last_seen,joined_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$roomId, 0, $name, $token, 0, 1, null, 1, $persona, AVATARS[0], $now, $now]);
        sysmsg($roomId, "{$name} 创建了房间，等待玩家加入");
        $room = room_by_id($roomId);
        return ['code' => $code, 'token' => $token, 'seat' => 0, 'room' => public_room($room)];
    });
}

function public_room(array $room): array
{
    return [
        'code' => $room['code'], 'status' => $room['status'], 'phase' => $room['phase'],
        'day' => (int) $room['day'], 'seats' => (int) (jarr($room['config'])['seats'] ?? 9),
        'players' => count_players((int) $room['id']),
        'created_at' => (int) $room['created_at'],
    ];
}

function count_players(int $roomId): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM players WHERE room_id = ?');
    $st->execute([$roomId]);
    return (int) $st->fetchColumn();
}

function api_join_room(string $code, string $name, string $token = ''): array
{
    $name = clean_name($name);
    return tx(function () use ($code, $name, $token) {
        $room = room_by_code($code);
        if (!$room) fail('房间不存在', 404);
        $roomId = (int) $room['id'];

        if ($token !== '') {
            $p = player_by_token($room, $token);
            if ($p) {
                db()->prepare('UPDATE players SET last_seen = ? WHERE id = ?')->execute([now_ms(), (int) $p['id']]);
                return ['code' => $room['code'], 'token' => $token, 'seat' => (int) $p['seat']];
            }
        }
        if ($room['status'] !== 'lobby') fail('游戏已经开始，无法加入', 403);
        $cfg = jarr($room['config']);
        $seats = (int) ($cfg['seats'] ?? 9);
        $players = room_players($roomId);
        if (count($players) >= $seats) fail('房间已满', 403);

        $used = array_map(fn($p) => (int) $p['seat'], $players);
        $seat = 0;
        while (in_array($seat, $used, true)) $seat++;
        if ($seat >= $seats) fail('房间已满', 403);

        $tok = new_token();
        $now = now_ms();
        db()->prepare('INSERT INTO players (room_id,seat,name,token,is_bot,is_host,role,alive,persona,avatar,last_seen,joined_at) VALUES (?,?,?,?,0,0,NULL,1,NULL,?,?,?)')
            ->execute([$roomId, $seat, $name, $tok, AVATARS[$seat % count(AVATARS)], $now, $now]);
        sysmsg($roomId, "{$name} 加入了房间（{$seat}号座位）");
        db()->prepare('UPDATE rooms SET version = version + 1, updated_at = ? WHERE id = ?')->execute([$now, $roomId]);
        return ['code' => $room['code'], 'token' => $tok, 'seat' => $seat];
    });
}

function api_add_bot(string $code, string $token): array
{
    return tx(function () use ($code, $token) {
        $room = require_host($code, $token);
        if ($room['status'] !== 'lobby') fail('游戏已开始');
        $cfg = jarr($room['config']);
        $seats = (int) $cfg['seats'];
        $players = room_players((int) $room['id']);
        if (count($players) >= $seats) fail('座位已满');
        $used = array_map(fn($p) => (int) $p['seat'], $players);
        $seat = 0;
        while (in_array($seat, $used, true)) $seat++;
        $persona = PERSONAS[array_rand(PERSONAS)];
        $botName = BOT_NAMES[($seat + random_int(0, count(BOT_NAMES) - 1)) % count(BOT_NAMES)];
        foreach ($players as $p) if ($p['name'] === $botName) $botName .= 'Ⅱ';
        $tok = new_token();
        $now = now_ms();
        db()->prepare('INSERT INTO players (room_id,seat,name,token,is_bot,is_host,role,alive,persona,avatar,last_seen,joined_at) VALUES (?,?,?,?,1,0,NULL,1,?,?,?,?)')
            ->execute([(int) $room['id'], $seat, $botName, $tok, jsonv($persona), AVATARS[$seat % count(AVATARS)], $now, $now]);
        sysmsg((int) $room['id'], "机器人 {$botName} 入座 {$seat} 号");
        db()->prepare('UPDATE rooms SET version = version + 1, updated_at = ? WHERE id = ?')->execute([$now, (int) $room['id']]);
        return ['seat' => $seat];
    });
}

function api_remove_bot(string $code, string $token, int $seat): array
{
    return tx(function () use ($code, $token, $seat) {
        $room = require_host($code, $token);
        if ($room['status'] !== 'lobby') fail('游戏已开始');
        $st = db()->prepare('SELECT * FROM players WHERE room_id = ? AND seat = ? AND is_bot = 1');
        $st->execute([(int) $room['id'], $seat]);
        $p = $st->fetch();
        if (!$p) fail('该座位没有机器人');
        db()->prepare('DELETE FROM players WHERE id = ?')->execute([(int) $p['id']]);
        sysmsg((int) $room['id'], "机器人 {$p['name']} 离开了房间");
        db()->prepare('UPDATE rooms SET version = version + 1, updated_at = ? WHERE id = ?')->execute([now_ms(), (int) $room['id']]);
        return ['ok' => true];
    });
}

function api_kick(string $code, string $token, int $seat): array
{
    return tx(function () use ($code, $token, $seat) {
        $room = require_host($code, $token);
        if ($room['status'] !== 'lobby') fail('游戏已开始');
        if ($seat === (int) $room['host_seat']) fail('不能移除房主');
        $st = db()->prepare('SELECT * FROM players WHERE room_id = ? AND seat = ?');
        $st->execute([(int) $room['id'], $seat]);
        $p = $st->fetch();
        if (!$p) fail('座位为空');
        db()->prepare('DELETE FROM players WHERE id = ?')->execute([(int) $p['id']]);
        sysmsg((int) $room['id'], "{$p['name']} 被房主移出房间");
        db()->prepare('UPDATE rooms SET version = version + 1, updated_at = ? WHERE id = ?')->execute([now_ms(), (int) $room['id']]);
        return ['ok' => true];
    });
}

function api_update_config(string $code, string $token, array $cfg): array
{
    return tx(function () use ($code, $token, $cfg) {
        $room = require_host($code, $token);
        if ($room['status'] !== 'lobby') fail('游戏已开始，无法修改配置');
        $cfg = sanitize_config($cfg);
        $players = room_players((int) $room['id']);
        if (count($players) > $cfg['seats']) fail('当前人数超过所选座位数，请先移除玩家或机器人');
        $err = validate_roles($cfg['roles'], $cfg['seats']);
        if ($err) fail($err);
        db()->prepare('UPDATE rooms SET config = ?, ai_mode = ?, version = version + 1, updated_at = ? WHERE id = ?')
            ->execute([jsonv($cfg), $cfg['ai'], now_ms(), (int) $room['id']]);
        return ['config' => $cfg];
    });
}

function require_host(string $code, string $token): array
{
    $room = room_by_code($code);
    if (!$room) fail('房间不存在', 404);
    $p = player_by_token($room, $token);
    if (!$p) fail('身份失效，请重新进入房间', 401);
    if ((int) $p['is_host'] !== 1) fail('只有房主可以操作', 403);
    return $room;
}

/* ---------- 开始游戏 ---------- */

function api_start(string $code, string $token): array
{
    return tx(function () use ($code, $token) {
        $room = require_host($code, $token);
        if ($room['status'] === 'playing') fail('游戏已经开始');
        if ($room['status'] === 'over') fail('请先点击“再来一局”');
        $roomId = (int) $room['id'];
        $cfg = jarr($room['config']);
        $seats = (int) $cfg['seats'];
        $roles = $cfg['roles'];
        $err = validate_roles($roles, $seats);
        if ($err) fail($err);

        $players = room_players($roomId);
        if (count($players) > $seats) fail('玩家数量超过座位数');
        // 补足机器人
        $now = now_ms();
        while (count($players) < $seats) {
            $used = array_map(fn($p) => (int) $p['seat'], $players);
            $seat = 0;
            while (in_array($seat, $used, true)) $seat++;
            $persona = PERSONAS[array_rand(PERSONAS)];
            $botName = BOT_NAMES[($seat + random_int(0, count(BOT_NAMES) - 1)) % count(BOT_NAMES)];
            foreach ($players as $p) if ($p['name'] === $botName) $botName .= 'Ⅱ';
            $tok = new_token();
            db()->prepare('INSERT INTO players (room_id,seat,name,token,is_bot,is_host,role,alive,persona,avatar,last_seen,joined_at) VALUES (?,?,?,?,1,0,NULL,1,?,?,?,?)')
                ->execute([$roomId, $seat, $botName, $tok, jsonv($persona), AVATARS[$seat % count(AVATARS)], $now, $now]);
            $players[] = ['seat' => $seat, 'name' => $botName, 'is_bot' => 1];
        }
        $players = room_players($roomId);

        $deck = expand_roles($roles);
        db()->prepare('DELETE FROM actions WHERE room_id = ?')->execute([$roomId]);
        db()->prepare('DELETE FROM messages WHERE room_id = ?')->execute([$roomId]);
        db()->prepare('DELETE FROM jobs WHERE room_id = ?')->execute([$roomId]);
        foreach ($players as $i => $p) {
            $role = $deck[$i] ?? 'villager';
            $ps = [];
            if ($role === 'seer') $ps = ['checks' => new stdClass()];
            if ($role === 'witch') $ps = ['antidote' => true, 'poison' => true];
            if ($role === 'guard') $ps = ['last_guard' => 0];
            db()->prepare('UPDATE players SET role = ?, alive = 1, death_reason = NULL, death_day = NULL, pstate = ? WHERE id = ?')
                ->execute([$role, jsonv($ps), (int) $p['id']]);
        }
        db()->prepare('UPDATE rooms SET status = "playing", day = 0, winner = NULL, pending = NULL, last_result = NULL, speak_order = NULL, turn_seat = NULL, turn_index = 0, version = version + 1, updated_at = ? WHERE id = ?')
            ->execute([now_ms(), $roomId]);
        sysmsg($roomId, '游戏开始，正在分配身份…');
        $room = room_by_id($roomId);
        begin_night($room);
        return ['ok' => true];
    });
}

/* ---------- 夜晚 ---------- */

function begin_night(array $room): void
{
    $roomId = (int) $room['id'];
    $day = (int) $room['day'] + 1;
    db()->prepare('UPDATE rooms SET day = ?, last_result = NULL, pending = NULL, speak_order = NULL, turn_seat = NULL, turn_index = 0, version = version + 1, updated_at = ? WHERE id = ?')
        ->execute([$day, now_ms(), $roomId]);
    sysmsg($roomId, "第 {$day} 天 · 天黑请闭眼");
    next_night_phase($roomId, null);
}

function night_phase_actor(array $players, string $phase): array
{
    $role = ['night_guard' => 'guard', 'night_seer' => 'seer', 'night_witch' => 'witch'][$phase] ?? null;
    if ($role) return player_by_role($players, $role);
    if ($phase === 'night_wolf') return player_by_role($players, 'wolf');
    return [];
}

function phase_active(array $players, string $phase): bool
{
    $actors = night_phase_actor($players, $phase);
    if (!$actors) return false;
    if ($phase === 'night_witch') {
        $w = $actors[0];
        $st = pstate($w);
        if (empty($st['antidote']) && empty($st['poison'])) return false;
    }
    return true;
}

function next_night_phase(int $roomId, ?string $current): void
{
    $order = ['night_guard', 'night_wolf', 'night_seer', 'night_witch'];
    $players = room_players($roomId);
    $start = 0;
    if ($current !== null) {
        $idx = array_search($current, $order, true);
        $start = $idx === false ? 0 : $idx + 1;
    }
    for ($i = $start; $i < count($order); $i++) {
        if (phase_active($players, $order[$i])) {
            $sec = (int) lrs_config('timers.night');
            set_phase($roomId, $order[$i], $sec);
            // 机器人立刻行动
            bot_night_actions($roomId, $order[$i]);
            $room = room_by_id($roomId);
            if ($room['phase'] === $order[$i] && night_phase_done($roomId, $order[$i])) {
                next_night_phase($roomId, $order[$i]);
            }
            return;
        }
    }
    resolve_night($roomId);
}

function night_phase_done(int $roomId, string $phase): bool
{
    $room = room_by_id($roomId);
    if (!$room || $room['phase'] !== $phase) return false;
    $players = room_players($roomId);
    $actors = night_phase_actor($players, $phase);
    if (!$actors) return true;
    foreach ($actors as $a) {
        $st = db()->prepare('SELECT COUNT(*) FROM actions WHERE room_id = ? AND day = ? AND phase = ? AND actor_seat = ?');
        $st->execute([$roomId, (int) $room['day'], $phase, (int) $a['seat']]);
        if ((int) $st->fetchColumn() === 0) return false;
    }
    return true;
}

function has_action(int $roomId, int $day, string $phase, int $seat): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM actions WHERE room_id = ? AND day = ? AND phase = ? AND actor_seat = ?');
    $st->execute([$roomId, $day, $phase, $seat]);
    return (int) $st->fetchColumn() > 0;
}

function add_action(int $roomId, string $phase, int $seat, ?int $target, string $kind, array $extra = []): void
{
    $room = room_by_id($roomId);
    db()->prepare('INSERT OR IGNORE INTO actions (room_id, day, phase, actor_seat, target_seat, kind, extra, created_at) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$roomId, (int) $room['day'], $phase, $seat, $target, $kind, jsonv($extra), now_ms()]);
}

function wolf_target(int $roomId): ?int
{
    $room = room_by_id($roomId);
    $st = db()->prepare('SELECT target_seat, COUNT(*) c FROM actions WHERE room_id = ? AND day = ? AND phase = "night_wolf" AND kind = "wolf_kill" AND target_seat >= 0 GROUP BY target_seat ORDER BY c DESC');
    $st->execute([$roomId, (int) $room['day']]);
    $rows = $st->fetchAll();
    if (!$rows) return null;
    $max = (int) $rows[0]['c'];
    $top = array_values(array_filter($rows, fn($r) => (int) $r['c'] === $max));
    return (int) $top[array_rand($top)]['target_seat'];
}

function resolve_night(int $roomId): void
{
    $room = room_by_id($roomId);
    $players = room_players($roomId);
    $day = (int) $room['day'];

    $guardTarget = null;
    $st = db()->prepare('SELECT target_seat FROM actions WHERE room_id = ? AND day = ? AND phase = "night_guard" AND kind = "guard_protect"');
    $st->execute([$roomId, $day]);
    $g = $st->fetchColumn();
    if ($g !== false) $guardTarget = (int) $g;

    $victim = wolf_target($roomId);

    $saveTarget = null;
    $st = db()->prepare('SELECT target_seat FROM actions WHERE room_id = ? AND day = ? AND phase = "night_witch" AND kind = "witch_save"');
    $st->execute([$roomId, $day]);
    $s = $st->fetchColumn();
    if ($s !== false) $saveTarget = (int) $s;

    $poisonTarget = null;
    $st = db()->prepare('SELECT target_seat FROM actions WHERE room_id = ? AND day = ? AND phase = "night_witch" AND kind = "witch_poison"');
    $st->execute([$roomId, $day]);
    $po = $st->fetchColumn();
    if ($po !== false) $poisonTarget = (int) $po;

    $deaths = [];
    $guarded = false;
    $saved = false;
    if ($victim !== null) {
        $byGuard = ($guardTarget !== null && $guardTarget === $victim);
        $bySave = ($saveTarget !== null && $saveTarget === $victim);
        if ($byGuard && $bySave) {
            $deaths[] = ['seat' => $victim, 'reason' => 'wolf'];
        } elseif ($byGuard) {
            $guarded = true;
        } elseif ($bySave) {
            $saved = true;
        } else {
            $deaths[] = ['seat' => $victim, 'reason' => 'wolf'];
        }
    }
    if ($poisonTarget !== null) {
        $exists = false;
        foreach ($deaths as $d) if ($d['seat'] === $poisonTarget) $exists = true;
        if (!$exists) $deaths[] = ['seat' => $poisonTarget, 'reason' => 'poison'];
    }

    $now = now_ms();
    $deathSeats = [];
    foreach ($deaths as $d) {
        db()->prepare('UPDATE players SET alive = 0, death_reason = ?, death_day = ? WHERE room_id = ? AND seat = ?')
            ->execute([$d['reason'], $day, $roomId, $d['seat']]);
        $deathSeats[] = $d['seat'];
    }

    $text = '天亮了。';
    if (!$deaths) {
        $text .= '昨晚是平安夜。';
    } else {
        $names = [];
        foreach ($deaths as $d) {
            $p = by_seat($players, $d['seat']);
            $names[] = ($p ? $p['name'] . '（' . $d['seat'] . '号）' : $d['seat'] . '号');
        }
        $text .= '昨晚 ' . implode('、', $names) . ' 死亡。';
    }
    sysmsg($roomId, $text);
    db()->prepare('UPDATE rooms SET last_result = ?, version = version + 1, updated_at = ? WHERE id = ?')
        ->execute([jsonv(['deaths' => $deaths, 'peace' => !$deaths, 'guarded' => $guarded, 'saved' => $saved, 'day' => $day]), $now, $roomId]);

    // 猎人被狼刀 -> 可开枪
    $hunter = null;
    foreach ($deaths as $d) {
        $p = by_seat($players, $d['seat']);
        if ($p && $p['role'] === 'hunter' && $d['reason'] === 'wolf') $hunter = (int) $p['seat'];
    }
    if ($hunter !== null) {
        start_hunter($roomId, $hunter, 'discuss');
        return;
    }
    if (check_win($roomId)) return;
    begin_discuss($roomId);
}

function start_hunter(int $roomId, int $seat, string $after): void
{
    $p = by_seat(room_players($roomId), $seat);
    db()->prepare('UPDATE rooms SET pending = ?, version = version + 1, updated_at = ? WHERE id = ?')
        ->execute([jsonv(['after' => $after]), now_ms(), $roomId]);
    set_phase($roomId, 'hunter', (int) lrs_config('timers.hunter'), $seat);
    sysmsg($roomId, ($p['name'] ?? $seat) . "（{$seat}号）是猎人，可以开枪带走一名玩家");
    bot_hunter_action($roomId);
}

function finish_hunter(int $roomId, int $seat, ?int $target): void
{
    $room = room_by_id($roomId);
    $pending = jarr($room['pending']);
    $players = room_players($roomId);
    $shooter = by_seat($players, $seat);
    $shotHunter = null;
    if ($target !== null && $target >= 0) {
        $t = by_seat($players, $target);
        db()->prepare('UPDATE players SET alive = 0, death_reason = "hunter", death_day = ? WHERE room_id = ? AND seat = ?')
            ->execute([(int) $room['day'], $roomId, $target]);
        sysmsg($roomId, ($shooter['name'] ?? $seat) . " 开枪带走了 " . ($t['name'] ?? $target) . "（{$target}号）");
        if ($t && $t['role'] === 'hunter') $shotHunter = $target;
        $res = jarr($room['last_result']);
        $res['hunter_shot'] = ['seat' => $seat, 'target' => $target];
        db()->prepare('UPDATE rooms SET last_result = ?, version = version + 1, updated_at = ? WHERE id = ?')->execute([jsonv($res), now_ms(), $roomId]);
    } else {
        sysmsg($roomId, ($shooter['name'] ?? $seat) . ' 没有开枪');
    }
    if ($shotHunter !== null) {
        start_hunter($roomId, $shotHunter, $pending['after'] ?? 'discuss');
        return;
    }
    if (check_win($roomId)) return;
    if (($pending['after'] ?? 'discuss') === 'exile') {
        begin_night(room_by_id($roomId));
    } else {
        begin_discuss($roomId);
    }
}

/* ---------- 白天 ---------- */

function begin_discuss(int $roomId): void
{
    $room = room_by_id($roomId);
    if (!$room || $room['status'] !== 'playing') return;
    $players = alive_players(room_players($roomId));
    if (!$players) return;
    $n = count($players);
    $startIdx = random_int(0, $n - 1);
    $order = [];
    for ($i = 0; $i < $n; $i++) $order[] = (int) $players[($startIdx + $i) % $n]['seat'];
    $cur = $order[0];
    $p = by_seat($players, $cur);
    $sec = (int) $p['is_bot'] ? (int) lrs_config('timers.discuss_bot') : (int) lrs_config('timers.discuss');
    db()->prepare('UPDATE rooms SET speak_order = ?, turn_index = 0, version = version + 1, updated_at = ? WHERE id = ?')
        ->execute([jsonv($order), now_ms(), $roomId]);
    set_phase($roomId, 'discuss', $sec, $cur, 0);
    sysmsg($roomId, "进入发言环节，从 {$cur} 号开始");
    ensure_speech_job($roomId);
}

function current_speaker(array $room): ?int
{
    if ($room['turn_seat'] === null) return null;
    return (int) $room['turn_seat'];
}

function speech_exists(int $roomId, int $day, int $seat, string $kind = 'speech'): bool
{
    $st = db()->prepare('SELECT COUNT(*) FROM messages WHERE room_id = ? AND day = ? AND seat = ? AND kind = ?');
    $st->execute([$roomId, $day, $seat, $kind]);
    return (int) $st->fetchColumn() > 0;
}

function advance_discuss(int $roomId): void
{
    $room = room_by_id($roomId);
    if (!$room || $room['phase'] !== 'discuss') return;
    $order = jarr($room['speak_order']);
    $players = room_players($roomId);
    $aliveSeats = array_map(fn($p) => (int) $p['seat'], alive_players($players));
    $idx = (int) $room['turn_index'];
    for ($i = $idx + 1; $i < count($order); $i++) {
        if (in_array((int) $order[$i], $aliveSeats, true)) {
            $seat = (int) $order[$i];
            $p = by_seat($players, $seat);
            $sec = (int) $p['is_bot'] ? (int) lrs_config('timers.discuss_bot') : (int) lrs_config('timers.discuss');
            db()->prepare('UPDATE rooms SET turn_index = ?, version = version + 1, updated_at = ? WHERE id = ?')->execute([$i, now_ms(), $roomId]);
            set_phase($roomId, 'discuss', $sec, $seat, $i);
            ensure_speech_job($roomId);
            return;
        }
    }
    begin_vote($roomId);
}

function begin_vote(int $roomId): void
{
    $room = room_by_id($roomId);
    if (!$room || $room['status'] !== 'playing') return;
    set_phase($roomId, 'vote', (int) lrs_config('timers.vote'));
    sysmsg($roomId, '发言结束，请投票放逐一名玩家');
    bot_vote_actions($roomId);
}

function resolve_vote(int $roomId): void
{
    $room = room_by_id($roomId);
    $day = (int) $room['day'];
    $players = room_players($roomId);
    $st = db()->prepare('SELECT actor_seat, target_seat FROM actions WHERE room_id = ? AND day = ? AND phase = "vote" AND kind = "vote"');
    $st->execute([$roomId, $day]);
    $votes = $st->fetchAll();
    $counts = [];
    foreach ($votes as $v) {
        $t = (int) $v['target_seat'];
        if ($t >= 0) $counts[$t] = ($counts[$t] ?? 0) + 1;
    }
    arsort($counts);
    $detail = [];
    foreach ($votes as $v) {
        $p = by_seat($players, (int) $v['actor_seat']);
        $detail[] = ['seat' => (int) $v['actor_seat'], 'name' => $p['name'] ?? '', 'target' => (int) $v['target_seat']];
    }
    $lines = [];
    foreach ($counts as $seat => $c) {
        $p = by_seat($players, (int) $seat);
        $lines[] = ($p['name'] ?? $seat) . "（{$seat}号）{$c}票";
    }
    sysmsg($roomId, '投票结果：' . ($lines ? implode('，', $lines) : '全部弃票'));

    $exiled = null;
    if ($counts) {
        $first = array_key_first($counts);
        $max = $counts[$first];
        $top = array_keys(array_filter($counts, fn($c) => $c === $max));
        if (count($top) === 1) $exiled = (int) $top[0];
    }
    $res = jarr($room['last_result']);
    $res['vote'] = ['detail' => $detail, 'counts' => $counts, 'exiled' => $exiled];
    db()->prepare('UPDATE rooms SET last_result = ?, version = version + 1, updated_at = ? WHERE id = ?')->execute([jsonv($res), now_ms(), $roomId]);

    if ($exiled === null) {
        sysmsg($roomId, '出现平票，本轮无人被放逐');
        if (check_win($roomId)) return;
        begin_night(room_by_id($roomId));
        return;
    }
    $p = by_seat($players, $exiled);
    db()->prepare('UPDATE players SET alive = 0, death_reason = "vote", death_day = ? WHERE room_id = ? AND seat = ?')
        ->execute([$day, $roomId, $exiled]);
    sysmsg($roomId, ($p['name'] ?? $exiled) . "（{$exiled}号）被放逐，请发表遗言");
    $sec = (int) lrs_config('timers.lastword');
    set_phase($roomId, 'lastword', $sec, $exiled);
    ensure_speech_job($roomId);
}

function after_lastword(int $roomId): void
{
    $room = room_by_id($roomId);
    $seat = (int) ($room['turn_seat'] ?? 0);
    $players = room_players($roomId);
    $p = by_seat($players, $seat);
    if ($p && $p['role'] === 'hunter') {
        start_hunter($roomId, $seat, 'exile');
        return;
    }
    if (check_win($roomId)) return;
    begin_night(room_by_id($roomId));
}

/* ---------- 胜负 ---------- */

function check_win(int $roomId): bool
{
    $room = room_by_id($roomId);
    if (!$room || $room['status'] !== 'playing') return true;
    $players = room_players($roomId);
    $wolves = 0;
    $good = 0;
    foreach ($players as $p) {
        if ((int) $p['alive'] !== 1) continue;
        if ($p['role'] === 'wolf') $wolves++;
        else $good++;
    }
    $winner = null;
    if ($wolves === 0) $winner = 'good';
    elseif ($wolves >= $good) $winner = 'wolf';
    if ($winner === null) return false;

    db()->prepare('UPDATE rooms SET status = "over", phase = "over", phase_end = 0, winner = ?, turn_seat = NULL, version = version + 1, updated_at = ? WHERE id = ?')
        ->execute([$winner, now_ms(), $roomId]);
    sysmsg($roomId, $winner === 'good' ? '🎉 好人阵营获胜！' : '🐺 狼人阵营获胜！');
    $list = [];
    foreach ($players as $p) {
        $list[] = ['seat' => (int) $p['seat'], 'name' => $p['name'], 'role' => $p['role'], 'alive' => (int) $p['alive']];
    }
    db()->prepare('INSERT INTO results (room_id, code, winner, days, players_json, created_at) VALUES (?,?,?,?,?,?)')
        ->execute([$roomId, $room['code'], $winner, (int) $room['day'], jsonv($list), now_ms()]);
    return true;
}

/* ---------- 玩家操作 ---------- */

function api_action(string $code, string $token, string $type, ?int $target, string $text): array
{
    return tx(function () use ($code, $token, $type, $target, $text) {
        $room = room_by_code($code);
        if (!$room) fail('房间不存在', 404);
        $me = player_by_token($room, $token);
        if (!$me) fail('身份失效，请重新进入房间', 401);
        if ($room['status'] !== 'playing') fail('游戏未开始');
        $roomId = (int) $room['id'];
        $day = (int) $room['day'];
        $phase = $room['phase'];
        $players = room_players($roomId);
        $seat = (int) $me['seat'];

        switch ($type) {
            case 'guard':
                if ($phase !== 'night_guard' || $me['role'] !== 'guard' || (int) $me['alive'] !== 1) fail('当前不能使用守护');
                $st = pstate($me);
                if ($target === null || $target < 0) fail('请选择守护目标');
                if ((int) ($st['last_guard'] ?? 0) === $target) fail('不能连续两晚守护同一人');
                $tp = by_seat($players, $target);
                if (!$tp || (int) $tp['alive'] !== 1) fail('目标不可选');
                add_action($roomId, $phase, $seat, $target, 'guard_protect');
                $st['last_guard'] = $target;
                save_pstate((int) $me['id'], $st);
                break;
            case 'wolf_kill':
                if ($phase !== 'night_wolf' || $me['role'] !== 'wolf' || (int) $me['alive'] !== 1) fail('当前不能袭击');
                if ($target === null || $target < 0) fail('请选择袭击目标');
                $tp = by_seat($players, $target);
                if (!$tp || (int) $tp['alive'] !== 1) fail('目标不可选');
                add_action($roomId, $phase, $seat, $target, 'wolf_kill');
                break;
            case 'seer_check':
                if ($phase !== 'night_seer' || $me['role'] !== 'seer' || (int) $me['alive'] !== 1) fail('当前不能查验');
                if ($target === null || $target < 0 || $target === $seat) fail('请选择查验目标');
                $tp = by_seat($players, $target);
                if (!$tp || (int) $tp['alive'] !== 1) fail('目标不可选');
                $isWolf = $tp['role'] === 'wolf';
                add_action($roomId, $phase, $seat, $target, 'seer_check', ['is_wolf' => $isWolf]);
                $st = pstate($me);
                $st['checks'] = $st['checks'] ?? [];
                $st['checks'][(string) $target] = $isWolf;
                save_pstate((int) $me['id'], $st);
                break;
            case 'witch_save':
                if ($phase !== 'night_witch' || $me['role'] !== 'witch' || (int) $me['alive'] !== 1) fail('当前不能用药');
                $st = pstate($me);
                if (empty($st['antidote'])) fail('解药已经用完了');
                $victim = wolf_target($roomId);
                if ($victim === null || $victim !== $target) fail('只能救今晚被袭击的玩家');
                add_action($roomId, $phase, $seat, $target, 'witch_save');
                $st['antidote'] = false;
                save_pstate((int) $me['id'], $st);
                break;
            case 'witch_poison':
                if ($phase !== 'night_witch' || $me['role'] !== 'witch' || (int) $me['alive'] !== 1) fail('当前不能用药');
                $st = pstate($me);
                if (empty($st['poison'])) fail('毒药已经用完了');
                if ($target === null || $target < 0 || $target === $seat) fail('请选择毒杀目标');
                $tp = by_seat($players, $target);
                if (!$tp || (int) $tp['alive'] !== 1) fail('目标不可选');
                add_action($roomId, $phase, $seat, $target, 'witch_poison');
                $st['poison'] = false;
                save_pstate((int) $me['id'], $st);
                break;
            case 'witch_none':
                if ($phase !== 'night_witch' || $me['role'] !== 'witch' || (int) $me['alive'] !== 1) fail('当前不能操作');
                add_action($roomId, $phase, $seat, 0, 'witch_none');
                break;
            case 'hunter_shoot':
                if ($phase !== 'hunter' || (int) ($room['turn_seat'] ?? 0) !== $seat) fail('当前不能开枪');
                if ($target !== null && $target >= 0) {
                    $tp = by_seat($players, $target);
                    if (!$tp || (int) $tp['alive'] !== 1 || $target === $seat) fail('目标不可选');
                }
                finish_hunter($roomId, $seat, ($target !== null && $target >= 0) ? $target : null);
                return ['ok' => true];
            case 'speak':
                if (!in_array($phase, ['discuss', 'lastword'], true)) fail('当前不能发言');
                if ((int) ($room['turn_seat'] ?? 0) !== $seat) fail('还没轮到你发言');
                $txt = clean_text($text, 300);
                if (mb_strlen($txt) < 2) fail('发言内容太短');
                $kind = $phase === 'lastword' ? 'lastword' : 'speech';
                if (!speech_exists($roomId, $day, $seat, $kind)) {
                    msg($roomId, $seat, $kind, $txt, $day, $phase);
                    db()->prepare('UPDATE jobs SET status = "done", done_at = ? WHERE room_id = ? AND day = ? AND seat = ? AND status IN ("pending","running")')
                        ->execute([now_ms(), $roomId, $day, $seat]);
                }
                if ($phase === 'discuss') advance_discuss($roomId);
                else after_lastword($roomId);
                return ['ok' => true];
            case 'vote':
                if ($phase !== 'vote' || (int) $me['alive'] !== 1) fail('当前不能投票');
                if ($target !== null && $target >= 0) {
                    $tp = by_seat($players, $target);
                    if (!$tp || (int) $tp['alive'] !== 1) fail('目标不可选');
                    if ($target === $seat) fail('不能投自己');
                } else {
                    $target = -1;
                }
                add_action($roomId, $phase, $seat, $target, 'vote');
                $alive = alive_players($players);
                $st = db()->prepare('SELECT COUNT(*) FROM actions WHERE room_id = ? AND day = ? AND phase = "vote"');
                $st->execute([$roomId, $day]);
                if ((int) $st->fetchColumn() >= count($alive)) resolve_vote($roomId);
                return ['ok' => true];
            default:
                fail('未知操作');
        }
        db()->prepare('UPDATE rooms SET version = version + 1, updated_at = ? WHERE id = ?')->execute([now_ms(), $roomId]);
        if (strpos($phase, 'night_') === 0 && night_phase_done($roomId, $phase)) {
            next_night_phase($roomId, $phase);
        }
        return ['ok' => true];
    });
}

/* ---------- 机器人行为 ---------- */

function bot_night_actions(int $roomId, string $phase): void
{
    $room = room_by_id($roomId);
    $players = room_players($roomId);
    $day = (int) $room['day'];
    $alive = alive_players($players);

    if ($phase === 'night_guard') {
        $guards = player_by_role($players, 'guard');
        if (!$guards) return;
        $g = $guards[0];
        if ((int) $g['is_bot'] !== 1 || has_action($roomId, $day, $phase, (int) $g['seat'])) return;
        $st = pstate($g);
        $last = (int) ($st['last_guard'] ?? 0);
        $claimed = claimed_seer_seat($roomId, $players);
        $choices = array_values(array_filter($alive, fn($p) => (int) $p['seat'] !== $last));
        if (!$choices) return;
        $target = null;
        if ($claimed && $claimed !== $last && by_seat($alive, $claimed)) {
            $target = $claimed;
        } else {
            $target = (int) $choices[array_rand($choices)]['seat'];
        }
        add_action($roomId, $phase, (int) $g['seat'], $target, 'guard_protect');
        $st['last_guard'] = $target;
        save_pstate((int) $g['id'], $st);
        return;
    }

    if ($phase === 'night_wolf') {
        $wolves = player_by_role($players, 'wolf');
        $botWolves = array_filter($wolves, fn($p) => (int) $p['is_bot'] === 1 && !has_action($roomId, $day, $phase, (int) $p['seat']));
        if (!$botWolves) return;
        // 群狼统一目标：若已有狼票则跟票
        $stx = db()->prepare('SELECT target_seat FROM actions WHERE room_id = ? AND day = ? AND phase = "night_wolf" AND kind = "wolf_kill" LIMIT 1');
        $stx->execute([$roomId, $day]);
        $existing = $stx->fetchColumn();
        $target = $existing !== false ? (int) $existing : wolf_pick_target($roomId, $players, $wolves);
        foreach ($botWolves as $w) add_action($roomId, $phase, (int) $w['seat'], $target, 'wolf_kill');
        return;
    }

    if ($phase === 'night_seer') {
        $seers = player_by_role($players, 'seer');
        if (!$seers) return;
        $s = $seers[0];
        if ((int) $s['is_bot'] !== 1 || has_action($roomId, $day, $phase, (int) $s['seat'])) return;
        $st = pstate($s);
        $checked = array_keys($st['checks'] ?? []);
        $cands = [];
        foreach ($alive as $p) {
            if ((int) $p['seat'] === (int) $s['seat']) continue;
            if (in_array((string) $p['seat'], $checked, true)) continue;
            $cands[] = $p;
        }
        if (!$cands) foreach ($alive as $p) if ((int) $p['seat'] !== (int) $s['seat']) $cands[] = $p;
        if (!$cands) return;
        $t = $cands[array_rand($cands)];
        $isWolf = $t['role'] === 'wolf';
        add_action($roomId, $phase, (int) $s['seat'], (int) $t['seat'], 'seer_check', ['is_wolf' => $isWolf]);
        $st['checks'][(string) $t['seat']] = $isWolf;
        save_pstate((int) $s['id'], $st);
        return;
    }

    if ($phase === 'night_witch') {
        $witches = player_by_role($players, 'witch');
        if (!$witches) return;
        $w = $witches[0];
        if ((int) $w['is_bot'] !== 1 || has_action($roomId, $day, $phase, (int) $w['seat'])) return;
        $st = pstate($w);
        $victim = wolf_target($roomId);
        $save = !empty($st['antidote']);
        $poison = !empty($st['poison']);
        if ($victim !== null && $save && (random_int(1, 100) <= ($day === 1 ? 85 : 55))) {
            add_action($roomId, $phase, (int) $w['seat'], $victim, 'witch_save');
            $st['antidote'] = false;
            save_pstate((int) $w['id'], $st);
            return;
        }
        if ($poison && $day >= 2 && random_int(1, 100) <= 60) {
            $susp = bot_suspects($roomId, $players, (int) $w['seat']);
            foreach ($susp as $t => $score) {
                if ($score < 3) break;
                if (($victim !== null && $t === $victim)) continue;
                add_action($roomId, $phase, (int) $w['seat'], $t, 'witch_poison');
                $st['poison'] = false;
                save_pstate((int) $w['id'], $st);
                return;
            }
        }
        add_action($roomId, $phase, (int) $w['seat'], 0, 'witch_none');
        return;
    }
}

function claimed_seer_seat(int $roomId, array $players): ?int
{
    $st = db()->prepare('SELECT seat, text FROM messages WHERE room_id = ? AND seat IS NOT NULL AND kind IN ("speech","lastword") ORDER BY id DESC LIMIT 40');
    $st->execute([$roomId]);
    foreach ($st->fetchAll() as $m) {
        $t = $m['text'];
        if (mb_strpos($t, '我是预言家') !== false || mb_strpos($t, '我跳预言家') !== false || mb_strpos($t, '我预言家') !== false) {
            $p = by_seat($players, (int) $m['seat']);
            if ($p && (int) $p['alive'] === 1) return (int) $m['seat'];
        }
    }
    return null;
}

function claimed_role_seat(int $roomId, array $players, string $word): ?int
{
    $st = db()->prepare('SELECT seat, text FROM messages WHERE room_id = ? AND seat IS NOT NULL AND kind IN ("speech","lastword") ORDER BY id DESC LIMIT 40');
    $st->execute([$roomId]);
    foreach ($st->fetchAll() as $m) {
        if (mb_strpos($m['text'], $word) === false) continue;
        $p = by_seat($players, (int) $m['seat']);
        if ($p && (int) $p['alive'] === 1) return (int) $m['seat'];
    }
    return null;
}

function wolf_pick_target(int $roomId, array $players, array $wolves): int
{
    $alive = alive_players($players);
    $wolfSeats = array_map(fn($p) => (int) $p['seat'], $wolves);
    $claimedSeer = claimed_seer_seat($roomId, $players);
    $claimedWitch = claimed_role_seat($roomId, $players, '我是女巫');
    $claimedGuard = claimed_role_seat($roomId, $players, '我是守卫');
    $best = [];
    $bestScore = -999;
    foreach ($alive as $p) {
        $seat = (int) $p['seat'];
        if (in_array($seat, $wolfSeats, true)) continue;
        $score = random_int(0, 3);
        if ($claimedSeer === $seat) $score += 12;
        if ($claimedWitch === $seat) $score += 8;
        if ($claimedGuard === $seat) $score += 6;
        if ((int) $p['is_bot'] === 0) $score += 2;
        $score += (int) (crc32($roomId . ':' . $seat . ':' . $p['role']) % 3);
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = [$seat];
        } elseif ($score === $bestScore) {
            $best[] = $seat;
        }
    }
    if (!$best) {
        foreach ($alive as $p) $best[] = (int) $p['seat'];
    }
    return (int) $best[array_rand($best)];
}

function bot_suspects(int $roomId, array $players, int $meSeat): array
{
    $room = room_by_id($roomId);
    $day = (int) $room['day'];
    $me = by_seat($players, $meSeat);
    $alive = alive_players($players);
    $myRole = $me['role'] ?? 'villager';
    $teammates = [];
    if ($myRole === 'wolf') {
        foreach ($players as $p) if ($p['role'] === 'wolf') $teammates[] = (int) $p['seat'];
    }
    $checks = pstate($me)['checks'] ?? [];
    $scores = [];
    foreach ($alive as $p) {
        $seat = (int) $p['seat'];
        if ($seat === $meSeat) continue;
        $score = (int) (crc32($roomId . ':' . $day . ':' . $seat . ':' . $meSeat) % 4);
        // 昨天投过我
        $st = db()->prepare('SELECT COUNT(*) FROM actions WHERE room_id = ? AND day = ? AND phase = "vote" AND actor_seat = ? AND target_seat = ?');
        $st->execute([$roomId, $day - 1, $seat, $meSeat]);
        if ((int) $st->fetchColumn() > 0) $score += 4;
        if (isset($checks[(string) $seat]) && $checks[(string) $seat] === true) $score += 100;
        if (in_array($seat, $teammates, true)) $score -= 50;
        if (claimed_seer_seat($roomId, $players) === $seat && $myRole === 'wolf') $score += 8;
        $scores[$seat] = $score;
    }
    arsort($scores);
    return $scores;
}

function bot_vote_actions(int $roomId): void
{
    $room = room_by_id($roomId);
    $players = room_players($roomId);
    $alive = alive_players($players);
    foreach ($alive as $p) {
        if ((int) $p['is_bot'] !== 1) continue;
        $seat = (int) $p['seat'];
        $susp = bot_suspects($roomId, $players, $seat);
        $target = 0;
        foreach ($susp as $t => $score) {
            if ($score <= 0) break;
            $target = (int) $t;
            break;
        }
        if ($target === 0) {
            $others = array_values(array_filter($alive, fn($x) => (int) $x['seat'] !== $seat));
            if ($others) $target = (int) $others[array_rand($others)]['seat'];
        }
        add_action($roomId, 'vote', $seat, $target, 'vote');
    }
}

function bot_hunter_action(int $roomId): void
{
    $room = room_by_id($roomId);
    if (!$room || $room['phase'] !== 'hunter') return;
    $seat = (int) ($room['turn_seat'] ?? 0);
    $players = room_players($roomId);
    $me = by_seat($players, $seat);
    if (!$me || (int) $me['is_bot'] !== 1) return;
    if (has_action($roomId, (int) $room['day'], 'hunter', $seat)) return;
    $susp = bot_suspects($roomId, $players, $seat);
    $target = -1;
    foreach ($susp as $t => $score) {
        if ($score > 2) { $target = (int) $t; break; }
    }
    add_action($roomId, (int) $room['day'], 'hunter', $seat, $target, 'hunter_shoot');
    finish_hunter($roomId, $seat, $target >= 0 ? $target : null);
}

/* ---------- 状态聚合 ---------- */

function phase_text(array $room): string
{
    $map = [
        'lobby' => '等待开始',
        'night_guard' => '天黑请闭眼 · 守卫行动',
        'night_wolf' => '天黑请闭眼 · 狼人行动',
        'night_seer' => '天黑请闭眼 · 预言家验人',
        'night_witch' => '天黑请闭眼 · 女巫用药',
        'dawn' => '天亮了',
        'hunter' => '猎人开枪',
        'discuss' => '白天发言',
        'vote' => '投票放逐',
        'lastword' => '遗言',
        'over' => '游戏结束',
    ];
    return $map[$room['phase']] ?? '进行中';
}

function state_for(array $room, array $me): array
{
    $roomId = (int) $room['id'];
    $players = room_players($roomId);
    $over = $room['status'] === 'over';
    $meSeat = (int) $me['seat'];
    $myRole = $me['role'] ?? null;
    $myState = pstate($me);
    $now = now_ms();
    $alive = alive_players($players);
    $aliveSeats = array_map(fn($p) => (int) $p['seat'], $alive);
    $config = jarr($room['config']);
    $day = (int) $room['day'];
    $phase = $room['phase'];

    $list = [];
    foreach ($players as $p) {
        $online = (int) $p['is_bot'] === 1 || ((int) $p['last_seen'] > $now - 25000);
        $row = [
            'seat' => (int) $p['seat'],
            'name' => $p['name'],
            'avatar' => $p['avatar'] ?: AVATARS[(int) $p['seat'] % count(AVATARS)],
            'is_bot' => (int) $p['is_bot'],
            'is_host' => (int) $p['is_host'],
            'alive' => (int) $p['alive'],
            'death_reason' => $p['death_reason'],
            'death_day' => $p['death_day'] === null ? null : (int) $p['death_day'],
            'online' => $online,
        ];
        if ($over) $row['role'] = $p['role'];
        $list[] = $row;
    }

    $teammates = [];
    $known = [];
    if ($myRole === 'wolf') {
        foreach ($players as $p) if ($p['role'] === 'wolf' && (int) $p['seat'] !== $meSeat) $teammates[] = (int) $p['seat'];
    }

    // 聊天
    $st = db()->prepare('SELECT id, day, seat, kind, text, created_at FROM messages WHERE room_id = ? ORDER BY id DESC LIMIT 160');
    $st->execute([$roomId]);
    $chat = array_reverse($st->fetchAll());
    $seatNames = [];
    foreach ($players as $p) $seatNames[(int) $p['seat']] = $p['name'];
    foreach ($chat as &$c) {
        $c['seat'] = $c['seat'] === null ? null : (int) $c['seat'];
        $c['name'] = $c['seat'] === null ? null : ($seatNames[$c['seat']] ?? '');
        $c['id'] = (int) $c['id'];
        $c['day'] = (int) $c['day'];
    }
    unset($c);

    // 当前行动
    $action = null;
    $done = null;
    $isMyTurn = (int) ($room['turn_seat'] ?? -1) === $meSeat;
    $myAlive = (int) $me['alive'] === 1;

    if ($room['status'] === 'playing') {
        if ($phase === 'night_guard' && $myAlive && $myRole === 'guard' && !has_action($roomId, $day, $phase, $meSeat)) {
            $targets = [];
            foreach ($alive as $p) {
                if ((int) $p['seat'] === (int) ($myState['last_guard'] ?? 0)) continue;
                $targets[] = (int) $p['seat'];
            }
            $action = ['type' => 'guard', 'title' => '选择今晚守护的玩家', 'targets' => $targets, 'hint' => '不能连续两晚守护同一人'];
        }
        if ($phase === 'night_wolf' && $myAlive && $myRole === 'wolf' && !has_action($roomId, $day, $phase, $meSeat)) {
            $targets = $aliveSeats;
            $action = ['type' => 'wolf_kill', 'title' => '选择今晚袭击的目标', 'targets' => $targets, 'hint' => '与狼队友商量后出手'];
        }
        if ($phase === 'night_seer' && $myAlive && $myRole === 'seer' && !has_action($roomId, $day, $phase, $meSeat)) {
            $targets = array_values(array_filter($aliveSeats, fn($s) => $s !== $meSeat));
            $action = ['type' => 'seer_check', 'title' => '选择今晚查验的玩家', 'targets' => $targets, 'hint' => '查验结果只有你自己能看到'];
        }
        if ($phase === 'night_witch' && $myAlive && $myRole === 'witch' && !has_action($roomId, $day, $phase, $meSeat)) {
            $victim = wolf_target($roomId);
            $targets = array_values(array_filter($aliveSeats, fn($s) => $s !== $meSeat));
            $action = [
                'type' => 'witch', 'title' => '女巫用药',
                'victim' => $victim,
                'can_save' => !empty($myState['antidote']) && $victim !== null,
                'can_poison' => !empty($myState['poison']),
                'targets' => $targets,
                'hint' => '本晚最多使用一瓶药',
            ];
        }
        if ($phase === 'hunter' && $isMyTurn) {
            $targets = array_values(array_filter($aliveSeats, fn($s) => $s !== $meSeat));
            $action = ['type' => 'hunter_shoot', 'title' => '发动技能：开枪带走一名玩家', 'targets' => $targets, 'can_pass' => true, 'hint' => '也可以选择不开枪'];
        }
        if ($phase === 'discuss' && $myAlive && $isMyTurn) {
            $action = ['type' => 'speak', 'title' => '轮到你发言了', 'limit' => 300, 'hint' => '分析局势，表明怀疑对象'];
        }
        if ($phase === 'lastword' && $isMyTurn) {
            $action = ['type' => 'speak', 'title' => '发表遗言', 'limit' => 300, 'hint' => '留下你的信息'];
        }
        if ($phase === 'vote' && $myAlive && !has_action($roomId, $day, $phase, $meSeat)) {
            $action = ['type' => 'vote', 'title' => '投票放逐一名玩家', 'targets' => array_values(array_filter($aliveSeats, fn($s) => $s !== $meSeat)), 'can_pass' => true, 'hint' => '弃票也是一种选择'];
        }
    }

    // 已完成的行动提示
    if ($phase === 'night_guard' && $myRole === 'guard' && has_action($roomId, $day, $phase, $meSeat)) {
        $done = ['text' => '你已选择守护目标，等待天亮…'];
    }
    if ($phase === 'night_seer' && $myRole === 'seer') {
        $st = db()->prepare('SELECT target_seat, extra FROM actions WHERE room_id = ? AND day = ? AND phase = ? AND actor_seat = ?');
        $st->execute([$roomId, $day, $phase, $meSeat]);
        $a = $st->fetch();
        if ($a) {
            $ex = jarr($a['extra']);
            $t = by_seat($players, (int) $a['target_seat']);
            $done = ['text' => '查验结果：' . ($t['name'] ?? '') . '（' . $a['target_seat'] . '号）是' . (!empty($ex['is_wolf']) ? '【狼人】' : '【好人】')];
        }
    }
    if ($phase === 'night_wolf' && $myRole === 'wolf' && has_action($roomId, $day, $phase, $meSeat)) {
        $done = ['text' => '狼队已出手，等待天亮…'];
    }
    if ($phase === 'night_witch' && $myRole === 'witch' && has_action($roomId, $day, $phase, $meSeat)) {
        $done = ['text' => '你已用药，等待天亮…'];
    }

    // 狼队票型
    $wolfVotes = [];
    if ($myRole === 'wolf' && $phase === 'night_wolf') {
        $st = db()->prepare('SELECT actor_seat, target_seat FROM actions WHERE room_id = ? AND day = ? AND phase = "night_wolf"');
        $st->execute([$roomId, $day]);
        foreach ($st->fetchAll() as $a) $wolfVotes[] = ['seat' => (int) $a['actor_seat'], 'target' => (int) $a['target_seat']];
    }

    // 发言状态
    $speech = null;
    if (in_array($phase, ['discuss', 'lastword'], true) && $room['turn_seat'] !== null) {
        $seat = (int) $room['turn_seat'];
        $p = by_seat($players, $seat);
        $kind = $phase === 'lastword' ? 'lastword' : 'speech';
        $speech = [
            'seat' => $seat, 'name' => $p['name'] ?? '',
            'typing' => !speech_exists($roomId, $day, $seat, $kind),
            'is_me' => $seat === $meSeat,
        ];
    }

    // 投票信息
    $vote = null;
    if ($phase === 'vote') {
        $st = db()->prepare('SELECT actor_seat, target_seat FROM actions WHERE room_id = ? AND day = ? AND phase = "vote"');
        $st->execute([$roomId, $day]);
        $rows = $st->fetchAll();
        $mine = null;
        foreach ($rows as $v) if ((int) $v['actor_seat'] === $meSeat) $mine = (int) $v['target_seat'];
        $vote = ['voted' => count($rows), 'total' => count($alive), 'my_vote' => $mine];
    }
    $lastResult = jarr($room['last_result']);

    return [
        'code' => $room['code'],
        'status' => $room['status'],
        'phase' => $phase,
        'phase_text' => phase_text($room),
        'day' => $day,
        'phase_end' => (int) $room['phase_end'],
        'server_now' => $now,
        'winner' => $room['winner'],
        'config' => $config,
        'host_seat' => (int) $room['host_seat'],
        'my_seat' => $meSeat,
        'players' => $list,
        'me' => [
            'seat' => $meSeat,
            'name' => $me['name'],
            'role' => $myRole,
            'role_info' => $myRole ? role_def($myRole) : null,
            'team' => $myRole ? role_def($myRole)['team'] : null,
            'alive' => (int) $me['alive'],
            'is_host' => (int) $me['is_host'],
            'death_reason' => $me['death_reason'],
            'state' => $myState,
        ],
        'teammates' => $teammates,
        'chat' => $chat,
        'action' => $action,
        'action_done' => $done,
        'wolf_votes' => $wolfVotes,
        'speech' => $speech,
        'vote' => $vote,
        'last_result' => $lastResult,
        'speak_order' => jarr($room['speak_order']),
        'turn_seat' => $room['turn_seat'] === null ? null : (int) $room['turn_seat'],
        'turn_index' => (int) $room['turn_index'],
        'ai_mode' => $room['ai_mode'],
    ];
}

/* ---------- 结算 / 重开 ---------- */

function api_restart(string $code, string $token): array
{
    return tx(function () use ($code, $token) {
        $room = require_host($code, $token);
        $roomId = (int) $room['id'];
        db()->prepare('DELETE FROM actions WHERE room_id = ?')->execute([$roomId]);
        db()->prepare('DELETE FROM messages WHERE room_id = ?')->execute([$roomId]);
        db()->prepare('DELETE FROM jobs WHERE room_id = ?')->execute([$roomId]);
        db()->prepare('UPDATE players SET role = NULL, alive = 1, death_reason = NULL, death_day = NULL, pstate = NULL WHERE room_id = ?')->execute([$roomId]);
        db()->prepare('UPDATE rooms SET status = "lobby", phase = "lobby", day = 0, winner = NULL, pending = NULL, last_result = NULL, speak_order = NULL, turn_seat = NULL, turn_index = 0, phase_end = 0, version = version + 1, updated_at = ? WHERE id = ?')
            ->execute([now_ms(), $roomId]);
        sysmsg($roomId, '房主开启了一局新游戏，等待准备');
        return ['ok' => true];
    });
}

function api_leave(string $code, string $token): array
{
    return tx(function () use ($code, $token) {
        $room = room_by_code($code);
        if (!$room) fail('房间不存在', 404);
        $p = player_by_token($room, $token);
        if (!$p) return ['ok' => true];
        if ($room['status'] === 'lobby') {
            db()->prepare('DELETE FROM players WHERE id = ?')->execute([(int) $p['id']]);
            sysmsg((int) $room['id'], "{$p['name']} 离开了房间");
        } else {
            db()->prepare('UPDATE players SET is_bot = 1, last_seen = 0 WHERE id = ?')->execute([(int) $p['id']]);
            sysmsg((int) $room['id'], "{$p['name']} 离开了，已由机器人托管");
        }
        db()->prepare('UPDATE rooms SET version = version + 1, updated_at = ? WHERE id = ?')->execute([now_ms(), (int) $room['id']]);
        return ['ok' => true];
    });
}

function api_rooms(): array
{
    $st = db()->query("SELECT * FROM rooms WHERE status = 'lobby' AND updated_at > " . (now_ms() - 3600 * 1000) . " ORDER BY updated_at DESC LIMIT 20");
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $cfg = jarr($r['config']);
        $cnt = count_players((int) $r['id']);
        $out[] = [
            'code' => $r['code'],
            'seats' => (int) ($cfg['seats'] ?? 9),
            'players' => $cnt,
            'host' => player_host_name((int) $r['id']),
            'created_at' => (int) $r['created_at'],
        ];
    }
    return ['rooms' => $out];
}

function player_host_name(int $roomId): string
{
    $st = db()->prepare('SELECT name FROM players WHERE room_id = ? AND is_host = 1');
    $st->execute([$roomId]);
    return (string) ($st->fetchColumn() ?: '');
}

function api_history(): array
{
    $st = db()->query('SELECT code, winner, days, players_json, created_at FROM results ORDER BY id DESC LIMIT 12');
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $ps = jarr($r['players_json']);
        $wolfNames = [];
        foreach ($ps as $p) if (($p['role'] ?? '') === 'wolf') $wolfNames[] = ($p['seat'] ?? '?') . '号';
        $out[] = [
            'code' => $r['code'], 'winner' => $r['winner'], 'days' => (int) $r['days'],
            'wolves' => implode('、', $wolfNames), 'players' => count($ps),
            'created_at' => (int) $r['created_at'],
        ];
    }
    return ['history' => $out];
}

/* ---------- 定时推进 ---------- */

function game_tick(int $roomId): void
{
    $room = room_by_id($roomId);
    if (!$room || $room['status'] !== 'playing') return;
    $now = now_ms();
    $phase = $room['phase'];
    $day = (int) $room['day'];
    $players = room_players($roomId);

    if (strpos($phase, 'night_') === 0) {
        bot_night_actions($roomId, $phase);
        $room = room_by_id($roomId);
        if (!$room || $room['phase'] !== $phase) return;
        if (night_phase_done($roomId, $phase)) {
            next_night_phase($roomId, $phase);
            return;
        }
        if ($now >= (int) $room['phase_end']) {
            next_night_phase($roomId, $phase);
        }
        return;
    }

    if ($phase === 'hunter') {
        bot_hunter_action($roomId);
        $room = room_by_id($roomId);
        if (!$room || $room['phase'] !== 'hunter') return;
        if ($now >= (int) $room['phase_end']) {
            finish_hunter($roomId, (int) $room['turn_seat'], null);
        }
        return;
    }

    if ($phase === 'discuss') {
        $seat = (int) ($room['turn_seat'] ?? 0);
        $p = by_seat($players, $seat);
        if (!$p) { advance_discuss($roomId); return; }
        if (speech_exists($roomId, $day, $seat, 'speech')) { advance_discuss($roomId); return; }
        if ($now >= (int) $room['phase_end']) {
            $txt = (int) $p['is_bot'] === 1 ? rule_speech($roomId, $seat) : '（发言超时，过麦）';
            msg($roomId, $seat, 'speech', $txt, $day, 'discuss');
            db()->prepare('UPDATE jobs SET status = "done", done_at = ? WHERE room_id = ? AND day = ? AND seat = ? AND status IN ("pending","running")')
                ->execute([now_ms(), $roomId, $day, $seat]);
            advance_discuss($roomId);
            return;
        }
        if ((int) $p['is_bot'] === 1) ensure_speech_job($roomId);
        return;
    }

    if ($phase === 'vote') {
        bot_vote_actions($roomId);
        $st = db()->prepare('SELECT COUNT(*) FROM actions WHERE room_id = ? AND day = ? AND phase = "vote"');
        $st->execute([$roomId, $day]);
        $votes = (int) $st->fetchColumn();
        $aliveCount = count(alive_players($players));
        if ($votes >= $aliveCount || $now >= (int) $room['phase_end']) {
            resolve_vote($roomId);
        }
        return;
    }

    if ($phase === 'lastword') {
        $seat = (int) ($room['turn_seat'] ?? 0);
        if (speech_exists($roomId, $day, $seat, 'lastword')) { after_lastword($roomId); return; }
        if ($now >= (int) $room['phase_end']) {
            $p = by_seat($players, $seat);
            $txt = ($p && (int) $p['is_bot'] === 1) ? rule_speech($roomId, $seat) : '（没有留下遗言）';
            msg($roomId, $seat, 'lastword', $txt, $day, 'lastword');
            db()->prepare('UPDATE jobs SET status = "done", done_at = ? WHERE room_id = ? AND day = ? AND seat = ? AND status IN ("pending","running")')
                ->execute([now_ms(), $roomId, $day, $seat]);
            after_lastword($roomId);
            return;
        }
        $p = by_seat($players, $seat);
        if ($p && (int) $p['is_bot'] === 1) ensure_speech_job($roomId);
        return;
    }
}

function tick_room(int $roomId): void
{
    $lockFile = lrs_config('data_dir') . '/tick.lock';
    $fp = @fopen($lockFile, 'c');
    if (!$fp) return;
    if (!flock($fp, LOCK_EX | LOCK_NB)) { fclose($fp); return; }
    try {
        tx(function () use ($roomId) { game_tick($roomId); });
    } catch (Throwable $e) {
        error_log('tick error: ' . $e->getMessage());
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/* ---------- AI 发言任务 ---------- */

function ensure_speech_job(int $roomId): void
{
    $room = room_by_id($roomId);
    if (!$room || $room['status'] !== 'playing') return;
    $phase = $room['phase'];
    $seat = (int) ($room['turn_seat'] ?? 0);
    if (!$seat && $seat !== 0) return;
    $p = by_seat(room_players($roomId), $seat);
    if (!$p || (int) $p['is_bot'] !== 1) return;
    if ($room['ai_mode'] === 'rule') {
        // 规则模式直接发言
        if (!speech_exists($roomId, (int) $room['day'], $seat, $phase === 'lastword' ? 'lastword' : 'speech')) {
            $text = rule_speech($roomId, $seat);
            msg($roomId, $seat, $phase === 'lastword' ? 'lastword' : 'speech', $text, (int) $room['day'], $phase);
        }
        return;
    }
    $st = db()->prepare('INSERT OR IGNORE INTO jobs (room_id, day, phase, seat, status, created_at) VALUES (?,?,?,?,?,?)');
    $st->execute([$roomId, (int) $room['day'], $phase, $seat, 'pending', now_ms()]);
    spawn_ai_worker();
}

function spawn_ai_worker(): void
{
    $lock = lrs_config('ai.lock');
    $fp = @fopen($lock, 'c');
    if ($fp) {
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return;
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    $php = lrs_config('ai.php');
    $script = __DIR__ . '/../worker_ai.php';
    @exec('nohup ' . escapeshellarg($php) . ' ' . escapeshellarg($script) . ' >> /www/lrs-data/ai.log 2>&1 &');
}
