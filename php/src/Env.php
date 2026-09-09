<?php
declare(strict_types=1);

final class Env
{
    private static string $path = '';

    public static function load(string $path): void
    {
        self::$path = $path;
        if (!is_file($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '#') || !str_contains($trim, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $trim, 2);
            $k = trim($k);
            $v = trim($v);
            if (
                (strlen($v) >= 2 && str_starts_with($v, '"') && str_ends_with($v, '"'))
                || (strlen($v) >= 2 && str_starts_with($v, "'") && str_ends_with($v, "'"))
            ) {
                $v = substr($v, 1, -1);
            }
            if ($k === '') {
                continue;
            }
            $current = getenv($k);
            if ($current === false || trim((string) $current) === '') {
                putenv("{$k}={$v}");
                $_ENV[$k] = $v;
            }
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_ENV[$key] ?? $default;
        }
        return is_string($v) ? $v : $default;
    }

    public static function truthy(string $key): bool
    {
        return in_array(strtolower(self::get($key)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function filePath(): string
    {
        return self::$path;
    }

    /** Write keys into .env and the running process. Never log values. */
    public static function putKeys(array $pairs): void
    {
        $path = self::$path;
        if ($path === '' || !is_file($path)) {
            throw new RuntimeException('.env is not available to update');
        }
        if (!is_writable($path)) {
            throw new RuntimeException('.env is not writable');
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException('Could not read .env');
        }
        $pending = [];
        foreach ($pairs as $k => $v) {
            $k = trim((string) $k);
            if ($k === '' || !preg_match('/^[A-Z][A-Z0-9_]*$/', $k)) {
                continue;
            }
            $pending[$k] = trim((string) $v);
        }
        if (!$pending) {
            return;
        }
        $found = [];
        $out = [];
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim !== '' && !str_starts_with($trim, '#') && str_contains($trim, '=')) {
                $k = trim(explode('=', $trim, 2)[0]);
                if (isset($pending[$k])) {
                    $out[] = $k . '=' . $pending[$k];
                    $found[$k] = true;
                    continue;
                }
            }
            $out[] = $line;
        }
        foreach ($pending as $k => $v) {
            if (empty($found[$k])) {
                $out[] = $k . '=' . $v;
            }
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
        }
        $body = implode("\n", $out);
        if ($body !== '' && !str_ends_with($body, "\n")) {
            $body .= "\n";
        }
        if (file_put_contents($path, $body) === false) {
            throw new RuntimeException('Could not write .env');
        }
    }
}
