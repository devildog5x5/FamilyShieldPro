<?php
declare(strict_types=1);

final class Db
{
    public const DEMO_EMAIL = 'family@ourcircle.app';
    public const DEMO_NAME = 'Pat Foster';
    public const DEMO_PASSWORD = 'password123';
    public const VERSION = '1.3.0';

    public static function connect(string $path): PDO
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }

    public static function init(PDO $db): void
    {
        $db->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS circles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                plan TEXT NOT NULL DEFAULT 'yearly',
                created_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                circle_id INTEGER NOT NULL REFERENCES circles(id) ON DELETE CASCADE,
                email TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                phone TEXT,
                role TEXT NOT NULL DEFAULT 'member',
                status TEXT NOT NULL DEFAULT 'access',
                sms_opt_out INTEGER NOT NULL DEFAULT 0,
                totp_secret TEXT,
                totp_pending TEXT,
                totp_enabled INTEGER NOT NULL DEFAULT 0,
                recovery_codes TEXT,
                last_seen_at TEXT,
                created_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS invites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                circle_id INTEGER NOT NULL REFERENCES circles(id) ON DELETE CASCADE,
                email TEXT NOT NULL,
                name TEXT,
                phone TEXT,
                token TEXT NOT NULL UNIQUE,
                invited_by INTEGER,
                status TEXT NOT NULL DEFAULT 'sent',
                created_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS trusted (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                circle_id INTEGER NOT NULL REFERENCES circles(id) ON DELETE CASCADE,
                name TEXT NOT NULL,
                phone TEXT,
                website TEXT,
                kind TEXT NOT NULL DEFAULT 'other',
                notes TEXT,
                added_by INTEGER,
                created_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS checks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                circle_id INTEGER NOT NULL REFERENCES circles(id) ON DELETE CASCADE,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                text TEXT NOT NULL DEFAULT '',
                phone TEXT,
                url TEXT,
                screenshot_token TEXT,
                headline TEXT,
                level TEXT,
                analysis_json TEXT NOT NULL,
                created_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS check_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                check_id INTEGER NOT NULL REFERENCES checks(id) ON DELETE CASCADE,
                user_id INTEGER NOT NULL,
                kind TEXT NOT NULL,
                body TEXT NOT NULL,
                created_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                check_id INTEGER NOT NULL REFERENCES checks(id) ON DELETE CASCADE,
                circle_id INTEGER NOT NULL,
                payer_user_id INTEGER NOT NULL,
                triggered_by INTEGER NOT NULL,
                created_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS uploads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                circle_id INTEGER NOT NULL,
                check_id INTEGER,
                token TEXT NOT NULL UNIQUE,
                mime TEXT NOT NULL,
                original_name TEXT,
                abs_path TEXT NOT NULL,
                created_at TEXT NOT NULL
            );
        SQL);
        $n = (int) $db->query('SELECT COUNT(*) FROM circles')->fetchColumn();
        if ($n === 0) {
            self::seedDemo($db);
        }
    }

    public static function seedDemo(PDO $db): void
    {
        $now = Http::now();
        $db->prepare('INSERT INTO circles (name, plan, created_at) VALUES (?,?,?)')
            ->execute(['Foster family', 'yearly', $now]);
        $cid = (int) $db->lastInsertId();
        $db->prepare(
            'INSERT INTO users (circle_id, email, name, password_hash, phone, role, status, created_at)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            $cid,
            self::DEMO_EMAIL,
            self::DEMO_NAME,
            password_hash(self::DEMO_PASSWORD, PASSWORD_DEFAULT),
            '',
            'owner',
            'access',
            $now,
        ]);
        $uid = (int) $db->lastInsertId();
        $rows = [
            ['First National (example)', '8005550100', 'https://example-bank.invalid', 'bank', 'Use the number on the back of the debit card.'],
            ['Family clinic', '8005550142', '', 'doctor', 'Ask for the nurse line, not a callback from a text.'],
            ['Jordan (adult child)', '5550108888', '', 'family', 'Call before any unexpected payment request.'],
            ['City power company', '8005550199', '', 'utility', 'Printed on the monthly bill.'],
        ];
        $st = $db->prepare(
            'INSERT INTO trusted (circle_id, name, phone, website, kind, notes, added_by, created_at)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        foreach ($rows as $r) {
            $st->execute([$cid, $r[0], $r[1], $r[2], $r[3], $r[4], $uid, $now]);
        }
    }
}
