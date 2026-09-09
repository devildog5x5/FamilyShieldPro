<?php
declare(strict_types=1);

final class Env
{
    private static string $path = '';

    /** @var array<string, string> */
    private static array $fileValues = [];

    public static function load(string $path): void
    {
        self::$fileValues = [];
        $chosen = '';
        foreach (self::candidates($path) as $candidate) {
            if (is_file($candidate)) {
                $chosen = $candidate;
                break;
            }
        }
        self::$path = $chosen !== '' ? $chosen : $path;
        if ($chosen === '') {
            return;
        }
        $raw = file_get_contents($chosen);
        if (!is_string($raw) || $raw === '') {
            return;
        }
        $raw = self::toUtf8($raw);
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        foreach (explode("\n", $raw) as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '#')) {
                continue;
            }
            if (str_starts_with(strtolower($trim), 'export ')) {
                $trim = trim(substr($trim, 7));
            }
            if (!str_contains($trim, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $trim, 2);
            $k = trim($k);
            $v = self::stripQuotes(trim($v));
            if ($k === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) {
                continue;
            }
            self::$fileValues[$k] = $v;
            if ($v === '') {
                continue;
            }
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        if (isset(self::$fileValues[$key]) && self::$fileValues[$key] !== '') {
            return self::$fileValues[$key];
        }
        $v = getenv($key);
        if ($v === false || $v === '') {
            $v = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
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

    /**
     * Whether the loaded .env exists, and which keys have a non-empty value in the file.
     * Values are never returned.
     *
     * @return array{found:bool,name:string,keys:array<string,bool>}
     */
    public static function fileStatus(): array
    {
        $keys = [];
        foreach (self::$fileValues as $k => $v) {
            $keys[$k] = $v !== '';
        }
        $path = self::$path;
        return [
            'found' => $path !== '' && is_file($path),
            'name' => $path !== '' ? basename($path) : '.env',
            'keys' => $keys,
        ];
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
                if (str_starts_with(strtolower($k), 'export ')) {
                    $k = trim(substr($k, 7));
                }
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
            self::$fileValues[$k] = $v;
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
        $body = implode("\n", $out);
        if ($body !== '' && !str_ends_with($body, "\n")) {
            $body .= "\n";
        }
        if (file_put_contents($path, $body) === false) {
            throw new RuntimeException('Could not write .env');
        }
    }

    private static function stripQuotes(string $v): string
    {
        if (
            (strlen($v) >= 2 && str_starts_with($v, '"') && str_ends_with($v, '"'))
            || (strlen($v) >= 2 && str_starts_with($v, "'") && str_ends_with($v, "'"))
        ) {
            return substr($v, 1, -1);
        }
        return $v;
    }

    private static function toUtf8(string $raw): string
    {
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            return substr($raw, 3);
        }
        if (str_starts_with($raw, "\xFF\xFE") || str_starts_with($raw, "\xFE\xFF")) {
            if (function_exists('mb_convert_encoding')) {
                $converted = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
                return is_string($converted) ? $converted : $raw;
            }
        }
        return $raw;
    }

    /** @return list<string> */
    private static function candidates(string $path): array
    {
        $out = [$path];
        if (!str_ends_with(strtolower($path), '.txt')) {
            $out[] = $path . '.txt';
        }
        return $out;
    }
}
