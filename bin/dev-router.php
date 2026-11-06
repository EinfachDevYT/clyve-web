<?php
// Router for PHP's built-in server: php -S 127.0.0.1:8080 -t public bin/dev-router.php
$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($root . $path) && !str_ends_with($path, '.php')) {
    return false;
}
require $root . '/index.php';
