<?php
declare(strict_types=1);

$db = require __DIR__ . '/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$rel = ltrim($path, '/');
if ($rel !== '' && !str_contains($rel, '/') && !str_contains($rel, '\\') && !str_contains($rel, '..') && ($rel[0] ?? '') !== '.') {
    $rootFile = __DIR__ . '/' . $rel;
    $ext = strtolower(pathinfo($rootFile, PATHINFO_EXTENSION));
    $blocked = ['zip', 'ps1', 'sql', 'bak', 'log', 'sh', 'sqlite', 'db', 'md', 'yml', 'yaml', 'ini', 'dist', 'apk', 'exe', 'msi', 'dmg', '7z', 'phar', 'tgz', 'gz', 'git'];
    if (in_array($ext, $blocked, true) || str_contains($rel, '.env')) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not available';
        exit;
    }
    $rootTypes = [
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'txt' => 'text/plain; charset=utf-8',
        'webmanifest' => 'application/manifest+json; charset=utf-8',
    ];
    if (isset($rootTypes[$ext]) && is_file($rootFile)) {
        header('Content-Type: ' . $rootTypes[$ext]);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        readfile($rootFile);
        exit;
    }
}
if (str_starts_with($path, '/static/')) {
    $rel = str_replace(['..', '\\'], '', substr($path, 8));
    $file = __DIR__ . '/static/' . $rel;
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $blocked = ['zip', 'ps1', 'sql', 'bak', 'log', 'sh', 'sqlite', 'db', 'md', 'yml', 'yaml', 'ini', 'dist', 'apk', 'exe', 'msi', 'dmg', '7z', 'phar', 'tgz', 'gz', 'git'];
        if (in_array($ext, $blocked, true) || str_contains($rel, '.env')) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not available';
            exit;
        }
        $types = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
        ];
        if (!isset($types[$ext])) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not available';
            exit;
        }
        header('Content-Type: ' . $types[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
}

(new App($db))->run();
