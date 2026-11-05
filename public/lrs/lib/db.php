<?php
if (!defined('LRS')) exit;

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $path = lrs_config('db');
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0770, true);

    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA busy_timeout=8000');
    $pdo->exec('PRAGMA synchronous=NORMAL');
    $pdo->exec('PRAGMA foreign_keys=ON');
    db_init($pdo);
    return $pdo;
}

function db_init(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        status TEXT NOT NULL DEFAULT 'lobby',
        phase TEXT NOT NULL DEFAULT 'lobby',
        day INTEGER NOT NULL DEFAULT 0,
        phase_end INTEGER NOT NULL DEFAULT 0,
        config TEXT NOT NULL DEFAULT '{}',
        winner TEXT,
        host_seat INTEGER NOT NULL DEFAULT 0,
        ai_mode TEXT NOT NULL DEFAULT 'llm',
        turn_seat INTEGER,
        turn_index INTEGER NOT NULL DEFAULT 0,
        speak_order TEXT,
        last_result TEXT,
        pending TEXT,
        created_at INTEGER NOT NULL,
        updated_at INTEGER NOT NULL,
        version INTEGER NOT NULL DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS players (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id INTEGER NOT NULL,
        seat INTEGER NOT NULL,
        name TEXT NOT NULL,
        token TEXT NOT NULL UNIQUE,
        is_bot INTEGER NOT NULL DEFAULT 0,
        is_host INTEGER NOT NULL DEFAULT 0,
        role TEXT,
        alive INTEGER NOT NULL DEFAULT 1,
        death_reason TEXT,
        death_day INTEGER,
        persona TEXT,
        avatar TEXT,
        pstate TEXT,
        last_seen INTEGER NOT NULL DEFAULT 0,
        joined_at INTEGER NOT NULL,
        UNIQUE(room_id, seat),
        FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS actions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id INTEGER NOT NULL,
        day INTEGER NOT NULL,
        phase TEXT NOT NULL,
        actor_seat INTEGER NOT NULL,
        target_seat INTEGER,
        kind TEXT NOT NULL,
        extra TEXT,
        created_at INTEGER NOT NULL,
        UNIQUE(room_id, day, phase, actor_seat, kind),
        FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id INTEGER NOT NULL,
        day INTEGER NOT NULL DEFAULT 0,
        phase TEXT,
        seat INTEGER,
        kind TEXT NOT NULL DEFAULT 'text',
        text TEXT NOT NULL,
        created_at INTEGER NOT NULL,
        FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id INTEGER NOT NULL,
        day INTEGER NOT NULL,
        phase TEXT NOT NULL,
        seat INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT 'pending',
        created_at INTEGER NOT NULL,
        started_at INTEGER,
        done_at INTEGER,
        UNIQUE(room_id, day, phase, seat),
        FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS results (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id INTEGER NOT NULL,
        code TEXT NOT NULL,
        winner TEXT NOT NULL,
        days INTEGER NOT NULL,
        players_json TEXT NOT NULL,
        created_at INTEGER NOT NULL
    )");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_msg_room ON messages(room_id, id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_pl_room ON players(room_id, seat)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_act_room ON actions(room_id, day, phase)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status, id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rooms_status ON rooms(status, updated_at)");
}
