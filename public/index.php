<?php
declare(strict_types=1);

$__path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
define('APP_CONTEXT', str_starts_with($__path, '/api/') ? 'api' : 'site');
require dirname(__DIR__) . '/app/bootstrap.php';

(require dirname(__DIR__) . '/routes/' . (APP_CONTEXT === 'api' ? 'api' : 'site') . '.php')->dispatch();
