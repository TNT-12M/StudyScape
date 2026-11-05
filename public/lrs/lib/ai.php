<?php
if (!defined('LRS')) exit;

function ai_enabled(): bool
{
    return (bool) lrs_config('ai.enabled');
}

function ai_fail_file(): string
{
    return lrs_config('data_dir') . '/ai_fail';
}

function ai_recently_failed(): bool
{
    $f = ai_fail_file();
    if (!is_file($f)) return false;
    return (time() - (int) @filemtime($f)) < 120;
}

function ai_mark_failed(): void
{
    @file_put_contents(ai_fail_file(), (string) time());
}

function ai_http(array $payload, int $timeout): ?array
{
    $endpoint = lrs_config('ai.endpoint');
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false || $code >= 400) {
        ai_mark_failed();
        return null;
    }
    $j = json_decode($body, true);
    return is_array($j) ? $j : null;
}

function ai_chat(array $messages, int $maxTokens = 110, float $temp = 0.85): ?string
{
    $payload = [
        'model' => lrs_config('ai.model'),
        'messages' => $messages,
        'max_tokens' => $maxTokens,
        'temperature' => $temp,
        'top_p' => 0.9,
        'stop' => ["\n\n"],
        'stream' => false,
    ];
    $j = ai_http($payload, (int) lrs_config('ai.timeout'));
    if (!$j) return null;
    $txt = $j['choices'][0]['message']['content'] ?? ($j['choices'][0]['text'] ?? null);
    if (!is_string($txt)) return null;
    return $txt;
}

/** 生成一条机器人发言 */
function ai_speech(int $roomId, int $seat): string
{
    $room = room_by_id($roomId);
    $players = room_players($roomId);
    $me = by_seat($players, $seat);
    if (!$room || !$me) return '（沉默）';
    $role = $me['role'] ?: 'villager';
    $day = (int) $room['day'];
    $alive = alive_players($players);
    $aliveTxt = [];
    foreach ($alive as $p) $aliveTxt[] = (int) $p['seat'] . '号';
    $res = jarr($room['last_result']);
    $deathTxt = '平安夜';
    if (!empty($res['deaths'])) {
        $ds = [];
        foreach ($res['deaths'] as $d) $ds[] = (int) $d['seat'] . '号';
        $deathTxt = implode('、', $ds) . ' 死亡';
    }

    $recent = [];
    $st = db()->prepare('SELECT seat, kind, text FROM messages WHERE room_id = ? AND kind IN ("speech","lastword") ORDER BY id DESC LIMIT 7');
    $st->execute([$roomId]);
    foreach (array_reverse($st->fetchAll()) as $m) {
        $recent[] = $m['seat'] . '号：' . mb_substr(str_replace("\n", ' ', $m['text']), 0, 55);
    }
    $recentTxt = $recent ? implode(' | ', $recent) : '（还没有人发言）';

    $private = '';
    $persona = jarr($me['persona'] ?? null, []);
    $style = $persona['style'] ?? '普通玩家';
    $pst = pstate($me);

    if ($role === 'wolf') {
        $mates = [];
        foreach ($players as $p) if ($p['role'] === 'wolf' && (int) $p['seat'] !== $seat) $mates[] = (int) $p['seat'] . '号';
        $private .= '你是狼人，狼队友是' . ($mates ? implode('、', $mates) : '无') . '，必须隐藏身份，可以怀疑别人、带节奏。';
    } elseif ($role === 'seer') {
        $checks = $pst['checks'] ?? [];
        if ($checks) {
            $cs = [];
            foreach ($checks as $s => $w) $cs[] = $s . '号是' . ($w ? '狼人' : '好人');
            $private .= '你是预言家，查验结果：' . implode('，', $cs) . '。必要时可以起跳预言家。';
        } else {
            $private .= '你是预言家，还没有查验结果。';
        }
    } elseif ($role === 'witch') {
        $private .= '你是女巫，' . (!empty($pst['antidote']) ? '解药还在，' : '解药已用，') . (!empty($pst['poison']) ? '毒药还在。' : '毒药已用。');
    } elseif ($role === 'guard') {
        $private .= '你是守卫，' . (!empty($pst['last_guard']) ? '昨晚守护了' . (int) $pst['last_guard'] . '号。' : '还没有守护记录。');
    } elseif ($role === 'hunter') {
        $private .= '你是猎人，死亡时可以开枪。';
    } else {
        $private .= '你是平民，没有特殊信息。';
    }

    $self = $seat . '号';
    $sys = "你在玩中文狼人杀，你是{$self}{$persona['name']}，性格：{$style}。以第一人称发言，2到3句话，60字以内，口语化。只输出发言内容，不要括号、不要换行、不要解释规则。";
    $user = "第{$day}天白天。存活：" . implode('、', $aliveTxt) . "。昨晚：{$deathTxt}。你的身份：{$private}最近发言：{$recentTxt}。请说出你的怀疑对象和投票意向。";

    $txt = null;
    if (ai_enabled() && !ai_recently_failed()) {
        $out = ai_chat([
            ['role' => 'system', 'content' => $sys],
            ['role' => 'user', 'content' => $user],
        ], (int) lrs_config('ai.max_tokens'), (float) lrs_config('ai.temperature'));
        if ($out !== null) $txt = clean_speech($out, $role);
    }
    if ($txt === null || $txt === '') $txt = rule_speech($roomId, $seat);
    return $txt;
}

function clean_speech(string $s, string $role): string
{
    $s = strip_tags($s);
    $s = str_replace(["\r", "\n", "\t"], ' ', $s);
    $s = preg_replace('/[*#`>]+/u', '', $s) ?? $s;
    $s = preg_replace('/^[（(][^）)]{0,20}[）)]\s*/u', '', $s) ?? $s;
    $s = preg_replace('/^(发言|回答|输出)[:：]\s*/u', '', $s) ?? $s;
    $s = trim($s, " \t\n\r\0\x0B\"'“”‘’「」");
    $s = preg_replace('/\s{2,}/u', ' ', $s) ?? $s;
    if (mb_strlen($s) > 140) $s = mb_substr($s, 0, 140);
    if (mb_strlen($s) < 4) return '';
    $bad = ['作为AI', '作为 AI', '语言模型', '对不起', '无法', 'assistant', '系统提示'];
    foreach ($bad as $b) if (mb_stripos($s, $b) !== false) return '';
    if ($role !== 'wolf') {
        if (mb_strpos($s, '我是狼') !== false || mb_strpos($s, '我狼人') !== false) return '';
    }
    // 去掉重复的句号
    $s = preg_replace('/([。！？!?])\1+/u', '$1', $s) ?? $s;
    return $s;
}

function rule_speech(int $roomId, int $seat): string
{
    $room = room_by_id($roomId);
    $players = room_players($roomId);
    $me = by_seat($players, $seat);
    $role = $me['role'] ?? 'villager';
    $day = (int) $room['day'];
    $alive = alive_players($players);
    $susp = bot_suspects($roomId, $players, $seat);
    $res = jarr($room['last_result']);
    $deadLine = '';
    if (!empty($res['deaths'])) {
        $ds = [];
        foreach ($res['deaths'] as $d) $ds[] = (int) $d['seat'] . '号';
        $deadLine = '昨晚' . implode('、', $ds) . '倒了，';
    } elseif ($day > 1) {
        $deadLine = '昨晚是平安夜，';
    }

    $candidates = [];
    foreach ($susp as $s => $score) {
        if ($s === $seat) continue;
        $candidates[] = [(int) $s, (int) $score];
    }
    $x = $candidates ? $candidates[0][0] : 0;
    if (!$x) {
        foreach ($alive as $p) if ((int) $p['seat'] !== $seat) { $x = (int) $p['seat']; break; }
    }
    $y = 0;
    foreach ($alive as $p) {
        $s = (int) $p['seat'];
        if ($s !== $seat && $s !== $x) { $y = $s; break; }
    }
    $prefixes = ['我先盘一下，', '说实话，', '别急，我理一理，', '我简单说两句，', '按我的视角，', '那我直说了，'];
    $suffixes = ['先听后面的。', '过。', '就这样。', '大家自己判断。', '我票型很明确。', ''];
    $prefix = $prefixes[($seat + $day) % count($prefixes)];
    $suffix = $suffixes[($seat * 3 + $day) % count($suffixes)];

    // 预言家验到狼，偶尔起跳
    if ($role === 'seer' && $day >= 2) {
        $checks = pstate($me)['checks'] ?? [];
        foreach ($checks as $s => $isWolf) {
            if ($isWolf) {
                if (($seat + $day) % 100 < 65) {
                    return "我是预言家，昨晚查验 {$s} 号，是狼人。今天全票出 {$s} 号，守卫今晚守我。";
                }
                return "{$prefix}{$deadLine}我查过 {$s} 号，身份不干净，今天优先出 {$s} 号。{$suffix}";
            }
        }
        return "{$prefix}{$deadLine}我是预言家，昨晚验的是 {$x} 号，是好人。目前先出 {$x} 号的对立面。";
    }

    if ($role === 'wolf') {
        $mates = [];
        foreach ($players as $p) if ($p['role'] === 'wolf') $mates[] = (int) $p['seat'];
        $pool = [];
        foreach ($alive as $p) {
            $s = (int) $p['seat'];
            if ($s === $seat || in_array($s, $mates, true)) continue;
            $pool[] = $s;
        }
        if ($pool) $x = $pool[($seat + $day) % count($pool)];
        $tpl = [
            "{$prefix}{$deadLine}我是好人，{$x} 号刚才的发言太飘了，我怀疑他在藏身份，今天先出 {$x} 号。",
            "{$prefix}我盘了一下，{$x} 号一直在带节奏，视角很奇怪，我给他一票。",
            "{$prefix}{$deadLine}{$x} 号和 {$y} 号里必有一狼，我更倾向 {$x} 号，今天出他。",
        ];
        return $tpl[($seat + $day) % count($tpl)];
    }

    if ($role === 'witch') {
        $tpl = [
            "{$prefix}{$deadLine}我是女巫，昨晚救了一个人，{$x} 号的发言我很不舒服，今天出 {$x} 号。",
            "{$prefix}{$deadLine}药我留着，{$x} 号一直在划水，我觉得他有问题，票 {$x} 号。",
        ];
        return $tpl[($seat + $day) % count($tpl)];
    }

    if ($role === 'guard') {
        $tpl = [
            "{$prefix}{$deadLine}我是守卫，昨晚守了人。{$x} 号发言太急，不像好人视角，我票 {$x} 号。",
            "{$prefix}我这边信息不多，{$x} 号的逻辑站不住，今天先出 {$x} 号。",
        ];
        return $tpl[($seat + $day) % count($tpl)];
    }

    $tpl = [
        "{$prefix}{$deadLine}我是 {$seat} 号，好人。{$x} 号的发言前后矛盾，今天我先出 {$x} 号。",
        "{$prefix}我是 {$seat} 号，平民。{$x} 号一直在带节奏，我怀疑他是狼，票 {$x} 号。",
        "{$prefix}{$deadLine}我比较信 {$y} 号，{$x} 号我得再听听，但票先给 {$x} 号。",
        "{$prefix}我是 {$seat} 号，好人。场上我最怀疑 {$x} 号，今天出他，{$suffix}",
    ];
    return $tpl[($seat + $day) % count($tpl)];
}
