<?php
declare(strict_types=1);

$db = require __DIR__ . '/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path === '/favicon.ico') {
    $file = __DIR__ . '/static/img/logo.png';
    if (is_file($file)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($file);
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
        ];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
}

(new App($db))->run();
