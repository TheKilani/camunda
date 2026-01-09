<?php
// Router for PHP's built-in dev server.
// Serves existing static files directly; otherwise routes everything to index.php.

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if (PHP_SAPI === 'cli-server' && $path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
