<?php
declare(strict_types=1);

$db = require __DIR__ . '/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$rel = ltrim($path, '/');
if ($rel !== '' && !str_contains($rel, '/') && !str_contains($rel, '\\') && !str_contains($rel, '..') && ($rel[0] ?? '') !== '.') {
    $rootFile = __DIR__ . '/' . $rel;
    $ext = strtolower(pathinfo($rootFile, PATHINFO_EXTENSION));
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
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
}

(new App($db))->run();
