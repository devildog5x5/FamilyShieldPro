<?php
declare(strict_types=1);

final class Db
{
    public const DEMO_EMAIL = 'family@ourcircle.app';
    public const DEMO_NAME = 'Pat Foster';
    public const DEMO_PASSWORD = 'password123';
    public const VERSION = '1.3.6';
    public const RESET_NOTICE = 'If that email is on file, a one-hour reset link is on the way. When mail is not connected, the link is saved as password-reset.txt next to the database (blocked from the web).';

    private static ?string $path = null;

    public static function connect(string $path): PDO
    {
        self::$path = $path;
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
            CREATE TABLE IF NOT EXISTS operators (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kind TEXT NOT NULL,
                subject_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                used_at TEXT,
                created_at TEXT NOT NULL
            );
        SQL);
        $n = (int) $db->query('SELECT COUNT(*) FROM circles')->fetchColumn();
        if ($n === 0) {
            self::seedDemo($db);
        }
        self::ensureOperator($db);
    }

    public static function operatorEmail(): string
    {
        $email = strtolower(trim(Env::get('OPERATOR_EMAIL')));
        if ($email === '') {
            $email = strtolower(trim(Env::get('SUPPORT_EMAIL', 'CustomerService@FamilyShieldPro.com')));
        }
        return $email;
    }

    public static function operatorRow(PDO $db): ?array
    {
        $row = $db->query('SELECT * FROM operators ORDER BY id ASC LIMIT 1')->fetch();
        return $row ?: null;
    }

    public static function ensureOperator(PDO $db): void
    {
        $email = self::operatorEmail();
        $plain = Env::get('OPERATOR_PASSWORD');
        if ($email === '' || !str_contains($email, '@') || $plain === '') {
            return;
        }
        $row = self::operatorRow($db);
        if ($row) {
            if (strtolower((string) $row['email']) !== $email) {
                $db->prepare('UPDATE operators SET email=? WHERE id=?')->execute([$email, $row['id']]);
            }
            return;
        }
        $db->prepare('INSERT INTO operators (email, password_hash, created_at) VALUES (?,?,?)')
            ->execute([$email, password_hash($plain, PASSWORD_DEFAULT), Http::now()]);
    }

    public static function factoryReset(PDO $db): void
    {
        $savedOp = self::operatorRow($db);
        $tables = [
            'check_notes',
            'alerts',
            'checks',
            'uploads',
            'trusted',
            'invites',
            'users',
            'circles',
            'password_resets',
            'operators',
        ];
        $db->exec('PRAGMA foreign_keys = OFF');
        foreach ($tables as $table) {
            $db->exec('DROP TABLE IF EXISTS "' . $table . '"');
        }
        $db->exec('PRAGMA foreign_keys = ON');
        self::wipeUploadFiles();
        self::clearResetFile();
        self::init($db);
        if (!self::operatorRow($db) && $savedOp) {
            $db->prepare('INSERT INTO operators (email, password_hash, created_at) VALUES (?,?,?)')
                ->execute([
                    (string) $savedOp['email'],
                    (string) $savedOp['password_hash'],
                    Http::now(),
                ]);
        }
    }

    public static function wipeUploadFiles(): void
    {
        $dir = (self::$path ? dirname(self::$path) : (dirname(__DIR__) . '/data'))
            . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }

    public static function resetFilePath(): string
    {
        $dir = self::$path ? dirname(self::$path) : (dirname(__DIR__) . '/data');
        return $dir . DIRECTORY_SEPARATOR . 'password-reset.txt';
    }

    public static function writeResetFile(string $url, string $kind = 'circle'): void
    {
        $path = self::resetFilePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $who = $kind === 'operator' ? 'operator console' : 'circle member';
        $body = "Family Shield Pro password reset ({$who})\nGenerated: " . Http::now() . "\n\n"
            . "Open this link in your browser (expires in 1 hour):\n\n"
            . $url . "\n\n"
            . "If you did not request this, delete this file.\n";
        file_put_contents($path, $body);
    }

    public static function clearResetFile(): void
    {
        $path = self::resetFilePath();
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function issueResetToken(PDO $db, string $kind, int $subjectId): string
    {
        $kind = $kind === 'operator' ? 'operator' : 'user';
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $now = Http::now();
        $expires = gmdate('Y-m-d\TH:i:s\Z', time() + 3600);
        $db->prepare('UPDATE password_resets SET used_at=? WHERE kind=? AND subject_id=? AND used_at IS NULL')
            ->execute([$now, $kind, $subjectId]);
        $db->prepare(
            'INSERT INTO password_resets (kind, subject_id, token_hash, expires_at, created_at) VALUES (?,?,?,?,?)'
        )->execute([$kind, $subjectId, hash('sha256', $token), $expires, $now]);
        return $token;
    }

    public static function peekResetToken(PDO $db, string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $st = $db->prepare(
            'SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL'
        );
        $st->execute([hash('sha256', $token)]);
        $row = $st->fetch();
        if (!$row || (($row['expires_at'] ?? '') < Http::now())) {
            return null;
        }
        return $row;
    }

    public static function consumeResetToken(PDO $db, string $token, string $passwordHash): ?array
    {
        $row = self::peekResetToken($db, $token);
        if (!$row) {
            return null;
        }
        $kind = (string) $row['kind'];
        $id = (int) $row['subject_id'];
        if ($kind === 'operator') {
            $db->prepare('UPDATE operators SET password_hash=? WHERE id=?')->execute([$passwordHash, $id]);
        } else {
            $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$passwordHash, $id]);
        }
        $db->prepare('UPDATE password_resets SET used_at=? WHERE id=?')->execute([Http::now(), $row['id']]);
        self::clearResetFile();
        return $row;
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
