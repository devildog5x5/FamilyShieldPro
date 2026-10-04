<?php
declare(strict_types=1);

final class Http
{
    private static ?string $cspNonce = null;

    public static function cspNonce(): string
    {
        if (self::$cspNonce === null) {
            self::$cspNonce = bin2hex(random_bytes(16));
        }
        return self::$cspNonce;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function bodyJson(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');
        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }
        return $path;
    }

    public static function e(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function safeNext(?string $raw): string
    {
        if ($raw === null) {
            return '/home';
        }
        $next = trim($raw);
        if ($next === '' || strlen($next) > 200) {
            return '/home';
        }
        if (!str_starts_with($next, '/') || str_starts_with($next, '//') || str_contains($next, '\\')) {
            return '/home';
        }
        if (str_contains($next, '://') || str_contains($next, '..')) {
            return '/home';
        }
        return $next;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::e(self::csrfToken()) . '" />';
    }

    public static function csrfCheck(): void
    {
        $sent = (string) ($_POST['_csrf'] ?? '');
        $need = self::csrfToken();
        if ($sent === '' || !hash_equals($need, $sent)) {
            $_SESSION['flash'] = 'That form expired. Please try again.';
            $_SESSION['flash_type'] = 'error';
        $path = self::path();
        $stay = in_array($path, ['/login', '/signup', '/forgot', '/reset', '/admin/login', '/admin/forgot', '/theme'], true)
            || str_starts_with($path, '/join/');
        if ($path === '/theme') {
            $path = '/';
        }
        if ($path === '/forgot/code') {
            $stay = true;
            $path = '/forgot';
        }
        if ($path === '/admin/factory-reset') {
            $stay = true;
            $path = '/admin';
        }
        if ($path === '/admin/sql' || str_starts_with($path, '/admin/data')) {
            $stay = true;
            $path = '/admin/data';
        }
        if ($path === '/admin/stripe-setup' || $path === '/admin/stripe-check') {
            $stay = true;
            $path = '/admin';
        }
        if ($path === '/trial/continue') {
            $stay = true;
            $path = '/home';
        }
        self::redirect($stay ? $path : '/home');
        }
    }

    public static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        $nonce = self::cspNonce();
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self' 'nonce-{$nonce}'; connect-src 'self'; frame-ancestors 'none'; upgrade-insecure-requests");
        $base = strtolower(Env::get('BASE_URL'));
        if (str_starts_with($base, 'https://')) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        header_remove('X-Powered-By');
    }

    public static function flash(?string $msg = null, string $type = 'ok'): ?array
    {
        if ($msg !== null) {
            $_SESSION['flash'] = $msg;
            $_SESSION['flash_type'] = $type;
            return null;
        }
        if (empty($_SESSION['flash'])) {
            return null;
        }
        $out = ['text' => (string) $_SESSION['flash'], 'type' => (string) ($_SESSION['flash_type'] ?? 'ok')];
        unset($_SESSION['flash'], $_SESSION['flash_type']);
        return $out;
    }

    public static function now(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }

    public static function clientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function baseUrl(): string
    {
        $b = rtrim(Env::get('BASE_URL'), '/');
        if ($b !== '') {
            return $b;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https://' : 'http://') . $host;
    }

    /** 301 www, /index.php, and trailing slashes to one path. Local hosts are left alone except the path rules. */
    public static function canonicalRedirect(): void
    {
        $method = self::method();
        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = parse_url($uri, PHP_URL_QUERY);
        $qs = is_string($query) && $query !== '' ? '?' . $query : '';
        if ($path === '' || str_contains($path, '\\') || str_contains($path, "\n") || str_contains($path, "\r") || str_contains($path, '//')) {
            return;
        }
        $clean = $path;
        if ($clean === '/index.php' || $clean === '/index.php/') {
            $clean = '/';
        } elseif (strlen($clean) > 1 && str_ends_with($clean, '/')) {
            $clean = rtrim($clean, '/');
        }
        if ($host === 'www.familyshieldpro.com') {
            self::redirect('https://familyshieldpro.com' . $clean . $qs, 301);
        }
        if ($clean !== $path) {
            self::redirect($clean . $qs, 301);
        }
    }
}
