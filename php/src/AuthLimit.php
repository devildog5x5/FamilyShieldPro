<?php
declare(strict_types=1);

final class AuthLimit
{
    public const WINDOW = 900;
    public const MAX_ID = 5;
    public const MAX_TOTP = 8;
    public const MAX_OPERATOR_IP = 8;
    public const MAX_IP = 25;
    public const MAX_FORGOT = 5;

    public static function blocked(PDO $db, string $gate, string $identity): ?string
    {
        $wait = self::waitSeconds($db, $gate, $identity);
        if ($wait <= 0) {
            return null;
        }
        $mins = max(1, (int) ceil($wait / 60));
        if ($gate === 'forgot' || $gate === 'admin-forgot') {
            return "Please wait about {$mins} minutes before requesting another reset.";
        }
        if ($gate === 'operator' || $gate === 'operator-step') {
            return "Too many operator sign-in tries. Wait about {$mins} minutes, then try again.";
        }
        if ($gate === 'recovery') {
            return "Too many reset tries. Wait about {$mins} minutes, then try again.";
        }
        return "Too many sign-in tries. Wait about {$mins} minutes, then try again. You can also use Forgot password.";
    }

    public static function fail(PDO $db, string $gate, string $identity): void
    {
        self::ensure($db);
        $db->prepare('INSERT INTO auth_events (gate, ip_hash, id_hash, created_at) VALUES (?,?,?,?)')
            ->execute([$gate, self::ipHash(), self::idHash($identity), time()]);
    }

    public static function clear(PDO $db, string $gate, string $identity): void
    {
        self::ensure($db);
        $db->prepare('DELETE FROM auth_events WHERE gate=? AND id_hash=?')
            ->execute([$gate, self::idHash($identity)]);
    }

    /** @return array{fails:int, locked:int} */
    public static function snapshot(PDO $db): array
    {
        self::ensure($db);
        $since = time() - self::WINDOW;
        $st = $db->prepare('SELECT COUNT(*) FROM auth_events WHERE created_at>=?');
        $st->execute([$since]);
        $fails = (int) $st->fetchColumn();
        $st = $db->prepare(
            'SELECT COUNT(*) FROM (
                SELECT id_hash FROM auth_events WHERE created_at>=? GROUP BY gate, id_hash HAVING COUNT(*)>=?
             ) AS locked'
        );
        $st->execute([$since, self::MAX_ID]);
        return ['fails' => $fails, 'locked' => (int) $st->fetchColumn()];
    }

    public static function ensure(PDO $db): void
    {
        $db->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS auth_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                gate TEXT NOT NULL,
                ip_hash TEXT NOT NULL,
                id_hash TEXT NOT NULL,
                created_at INTEGER NOT NULL
            );
            CREATE INDEX IF NOT EXISTS auth_events_ip ON auth_events (ip_hash, created_at);
            CREATE INDEX IF NOT EXISTS auth_events_id ON auth_events (gate, id_hash, created_at);
        SQL);
        $db->prepare('DELETE FROM auth_events WHERE created_at<?')->execute([time() - 86400]);
    }

    private static function waitSeconds(PDO $db, string $gate, string $identity): int
    {
        self::ensure($db);
        $since = time() - self::WINDOW;
        $ip = self::ipHash();
        $id = self::idHash($identity);

        $ipWait = self::oldestWait(
            $db,
            'SELECT created_at FROM auth_events WHERE ip_hash=? AND created_at>=? ORDER BY created_at DESC LIMIT ?',
            [$ip, $since, self::MAX_IP],
            self::MAX_IP
        );
        if ($ipWait > 0) {
            return $ipWait;
        }

        if ($gate === 'operator' || $gate === 'operator-step') {
            $opWait = self::oldestWait(
                $db,
                'SELECT created_at FROM auth_events WHERE ip_hash=? AND gate IN (\'operator\',\'operator-step\') AND created_at>=? ORDER BY created_at DESC LIMIT ?',
                [$ip, $since, self::MAX_OPERATOR_IP],
                self::MAX_OPERATOR_IP
            );
            if ($opWait > 0) {
                return $opWait;
            }
        }

        $max = self::maxForGate($gate);
        return self::oldestWait(
            $db,
            'SELECT created_at FROM auth_events WHERE gate=? AND id_hash=? AND created_at>=? ORDER BY created_at DESC LIMIT ?',
            [$gate, $id, $since, $max],
            $max
        );
    }

    /** @param list<mixed> $args */
    private static function oldestWait(PDO $db, string $sql, array $args, int $max): int
    {
        $st = $db->prepare($sql);
        foreach ($args as $i => $v) {
            $st->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->execute();
        $times = $st->fetchAll(PDO::FETCH_COLUMN);
        if (count($times) < $max) {
            return 0;
        }
        $oldest = (int) min(array_map('intval', $times));
        return max(0, $oldest + self::WINDOW - time());
    }

    private static function maxForGate(string $gate): int
    {
        if ($gate === 'totp' || $gate === 'totp-setup') {
            return self::MAX_TOTP;
        }
        if ($gate === 'forgot' || $gate === 'admin-forgot') {
            return self::MAX_FORGOT;
        }
        return self::MAX_ID;
    }

    private static function ipHash(): string
    {
        return self::h(Http::clientIp());
    }

    private static function idHash(string $identity): string
    {
        return self::h(strtolower(trim($identity)));
    }

    private static function h(string $s): string
    {
        return hash_hmac('sha256', $s, Env::get('APP_SECRET', 'fsp-dev-change-me'));
    }
}
