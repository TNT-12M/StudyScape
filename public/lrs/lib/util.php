<?php
if (!defined('LRS')) exit;

function lrs_config(?string $key = null)
{
    static $cfg = null;
    if ($cfg === null) $cfg = require __DIR__ . '/../config.php';
    if ($key === null) return $cfg;
    $cur = $cfg;
    foreach (explode('.', $key) as $k) {
        if (!is_array($cur) || !array_key_exists($k, $cur)) return null;
        $cur = $cur[$k];
    }
    return $cur;
}

function now_ms(): int
{
    return (int) floor(microtime(true) * 1000);
}

function jout(array $data, int $code = 200): void
{
    if (defined('LRS_CLI')) {
        if (($data['ok'] ?? true) === false) throw new RuntimeException((string) ($data['error'] ?? 'error'));
        return;
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $msg, int $code = 400): void
{
    if (defined('LRS_CLI')) throw new RuntimeException($msg);
    jout(['ok' => false, 'error' => $msg], $code);
}

function body_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

function clean_text(string $s, int $max = 300): string
{
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
    $s = trim($s);
    if (mb_strlen($s, 'UTF-8') > $max) $s = mb_substr($s, 0, $max, 'UTF-8');
    return $s;
}

function clean_name(string $s): string
{
    $s = clean_text($s, 12);
    $s = preg_replace('/[<>\'"`\\\\]/u', '', $s) ?? '';
    $s = trim($s);
    if ($s === '') $s = '无名氏';
    return $s;
}

function tx(callable $fn)
{
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $r = $fn($pdo);
        $pdo->exec('COMMIT');
        return $r;
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

function room_code(): string
{
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < 10; $i++) {
        $c = '';
        for ($j = 0; $j < 4; $j++) $c .= $abc[random_int(0, strlen($abc) - 1)];
        $st = db()->prepare('SELECT 1 FROM rooms WHERE code = ?');
        $st->execute([$c]);
        if (!$st->fetchColumn()) return $c;
    }
    return 'R' . random_int(1000, 9999);
}

function new_token(): string
{
    return bin2hex(random_bytes(16));
}

function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function jsonv($v): string
{
    return json_encode($v, JSON_UNESCAPED_UNICODE);
}

function jarr(?string $s, array $default = []): array
{
    if (!$s) return $default;
    $j = json_decode($s, true);
    return is_array($j) ? $j : $default;
}
