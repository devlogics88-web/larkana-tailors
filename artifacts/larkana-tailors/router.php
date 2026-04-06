<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/') {
    // Only serve files inside /assets/ with allowlisted extensions.
    // realpath() resolves symlinks and ".." segments, blocking traversal.
    $assetsDir = realpath(__DIR__ . '/assets');
    $filePath  = realpath(__DIR__ . $uri);

    $allowedExts = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico',
                    'svg', 'woff', 'woff2', 'ttf', 'webp'];
    $ext = strtolower(pathinfo((string)$filePath, PATHINFO_EXTENSION));

    if (
        $filePath !== false &&
        $assetsDir !== false &&
        str_starts_with($filePath, $assetsDir . DIRECTORY_SEPARATOR) &&
        in_array($ext, $allowedExts, true)
    ) {
        $mimes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'ico'   => 'image/x-icon',
            'svg'   => 'image/svg+xml',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'webp'  => 'image/webp',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        readfile($filePath);
        exit;
    }
}

// All other requests (PHP routes, unlisted paths) are handled by index.php.
require_once __DIR__ . '/index.php';
