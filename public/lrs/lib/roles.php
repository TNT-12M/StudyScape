<?php
if (!defined('LRS')) exit;

const ROLE_DEFS = [
    'wolf' => [
        'name' => '狼人', 'team' => 'wolf', 'icon' => '🐺',
        'desc' => '每晚与队友共同袭击一名玩家。白天隐藏身份，混淆视听。',
    ],
    'seer' => [
        'name' => '预言家', 'team' => 'good', 'icon' => '🔮',
        'desc' => '每晚查验一名玩家的身份，得知其是否为狼人。',
    ],
    'witch' => [
        'name' => '女巫', 'team' => 'good', 'icon' => '🧪',
        'desc' => '拥有一瓶解药和一瓶毒药，每晚最多使用一瓶。解药可救回当晚被狼人袭击的玩家。',
    ],
    'hunter' => [
        'name' => '猎人', 'team' => 'good', 'icon' => '🎯',
        'desc' => '被狼人杀害或被投票放逐时，可以开枪带走一名玩家。被女巫毒杀时无法开枪。',
    ],
    'guard' => [
        'name' => '守卫', 'team' => 'good', 'icon' => '🛡️',
        'desc' => '每晚守护一名玩家，使其免受狼人袭击，不能连续两晚守护同一人。',
    ],
    'villager' => [
        'name' => '平民', 'team' => 'good', 'icon' => '👤',
        'desc' => '没有特殊技能，依靠发言与投票帮助好人阵营找出狼人。',
    ],
];

const ROLE_ORDER = ['wolf', 'seer', 'witch', 'hunter', 'guard', 'villager'];

function role_def(string $role): array
{
    return ROLE_DEFS[$role] ?? ROLE_DEFS['villager'];
}

function role_name(string $role): string
{
    return role_def($role)['name'];
}

/** 常用板子预设：人数 => 角色数量 */
function role_presets(): array
{
    return [
        6 => ['wolf' => 2, 'seer' => 1, 'witch' => 1, 'hunter' => 0, 'guard' => 0, 'villager' => 2],
        7 => ['wolf' => 2, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 0, 'villager' => 2],
        8 => ['wolf' => 2, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 0, 'villager' => 3],
        9 => ['wolf' => 3, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 0, 'villager' => 3],
        10 => ['wolf' => 3, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 1, 'villager' => 3],
        11 => ['wolf' => 3, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 1, 'villager' => 4],
        12 => ['wolf' => 4, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 1, 'villager' => 4],
        13 => ['wolf' => 4, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 1, 'villager' => 5],
        14 => ['wolf' => 4, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 1, 'villager' => 6],
        15 => ['wolf' => 5, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 1, 'villager' => 6],
        16 => ['wolf' => 5, 'seer' => 1, 'witch' => 1, 'hunter' => 1, 'guard' => 1, 'villager' => 7],
    ];
}

function preset_for(int $n): array
{
    $p = role_presets();
    if (isset($p[$n])) return $p[$n];
    // 兜底：按比例生成
    $wolves = max(1, (int) floor($n / 3));
    $roles = ['wolf' => $wolves, 'seer' => 1, 'witch' => 1, 'hunter' => 0, 'guard' => 0, 'villager' => 0];
    if ($n >= 9) $roles['hunter'] = 1;
    if ($n >= 10) $roles['guard'] = 1;
    $roles['villager'] = $n - array_sum($roles);
    return $roles;
}

function normalize_roles(array $in, int $seats): array
{
    $out = [];
    foreach (ROLE_ORDER as $r) $out[$r] = max(0, min(8, (int) ($in[$r] ?? 0)));
    if ($out['seer'] > 1) $out['seer'] = 1;
    if ($out['witch'] > 1) $out['witch'] = 1;
    if ($out['hunter'] > 1) $out['hunter'] = 1;
    if ($out['guard'] > 1) $out['guard'] = 1;
    // 总数校准到座位数：多退少补到平民
    $sum = array_sum($out);
    if ($sum !== $seats) {
        $out['villager'] = max(0, $out['villager'] + ($seats - $sum));
        $sum = array_sum($out);
        while ($sum > $seats) {
            if ($out['villager'] > 0) $out['villager']--;
            elseif ($out['wolf'] > 1) $out['wolf']--;
            else break;
            $sum = array_sum($out);
        }
    }
    return $out;
}

function validate_roles(array $roles, int $seats): ?string
{
    $sum = array_sum($roles);
    if ($sum !== $seats) return "角色数量（{$sum}）与座位数（{$seats}）不一致";
    if (($roles['wolf'] ?? 0) < 1) return '至少需要 1 名狼人';
    $others = $seats - ($roles['wolf'] ?? 0);
    if ($others < 2) return '好人阵营至少需要 2 人';
    if (($roles['wolf'] ?? 0) >= $others) return '狼人数量不能多于或等于好人数量';
    return null;
}

/** 扩展角色列表 */
function expand_roles(array $roles): array
{
    $deck = [];
    foreach (ROLE_ORDER as $r) {
        for ($i = 0; $i < (int) ($roles[$r] ?? 0); $i++) $deck[] = $r;
    }
    shuffle($deck);
    return $deck;
}
