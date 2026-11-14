<?php
declare(strict_types=1);

use App\Core\Security;
use App\Core\View;

if (!defined('APP_CONTEXT')) {
    http_response_code(500);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . '/storage');
define('VIEW_PATH', BASE_PATH . '/resources/views');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
header_remove('X-Powered-By');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require __DIR__ . '/helpers.php';

$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    error_log('[app] config/config.php missing');
    http_response_code(503);
    exit('Service unavailable');
}
$GLOBALS['__config'] = require $configFile;

if (strlen((string)config('app_secret')) < 32 || config('app_secret') === 'CHANGE_ME') {
    error_log('[app] app_secret not set');
    http_response_code(503);
    exit('Service unavailable');
}

if (!is_dir(STORAGE_PATH)) {
    mkdir(STORAGE_PATH, 0700, true);
}

App\Services\ErrorReporter::register();

if (PHP_SAPI === 'cli') {
    set_exception_handler(static function (Throwable $e): void {
        error_log('[cli] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        App\Services\ErrorReporter::exception($e);
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    });
} else {
    Security::sendHeaders();
    if (APP_CONTEXT !== 'api') {
        Security::startSession(APP_CONTEXT);
    }

    set_exception_handler(static function (Throwable $e): void {
        error_log('[app] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        App\Services\ErrorReporter::exception($e);
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (APP_CONTEXT === 'api' || wants_json()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Serverfehler.']);
            return;
        }
        View::render('errors/500', [], 'Fehler');
    });
}
