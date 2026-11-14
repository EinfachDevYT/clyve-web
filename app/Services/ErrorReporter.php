<?php
declare(strict_types=1);

namespace App\Services;

use Throwable;

/** Sends server errors to the team's Discord channel – each distinct error at most once per hour. */
final class ErrorReporter
{
    private const THROTTLE = 3600;

    public static function exception(Throwable $e): void
    {
        self::report($e::class . ': ' . $e->getMessage(), $e->getFile(), $e->getLine());
    }

    public static function report(string $message, string $file, int $line): void
    {
        try {
            if (\App\Core\Db::value("SELECT value FROM settings WHERE key = 'error_alerts'") === '0') {
                return;
            }
        } catch (Throwable) {
            // database itself may be the problem – still try to report
        }
        try {
            $file = str_replace(BASE_PATH . '/', '', $file);
            $key = sha1(preg_replace('/\d+/', '#', $message) . $file . $line);
            $dir = STORAGE_PATH . '/errors';
            if (!is_dir($dir)) {
                @mkdir($dir, 0700, true);
            }
            $stamp = $dir . '/' . $key;
            $state = is_file($stamp) ? (json_decode((string)file_get_contents($stamp), true) ?: []) : [];
            if (isset($state['sent']) && time() - (int)$state['sent'] < self::THROTTLE) {
                $state['skipped'] = (int)($state['skipped'] ?? 0) + 1;
                @file_put_contents($stamp, json_encode($state), LOCK_EX);
                return;
            }
            $skipped = (int)($state['skipped'] ?? 0);
            @file_put_contents($stamp, json_encode(['sent' => time(), 'skipped' => 0]), LOCK_EX);

            $fields = ['Bereich' => defined('APP_CONTEXT') ? APP_CONTEXT : '?', 'Ort' => $file . ':' . $line];
            if (PHP_SAPI !== 'cli') {
                $path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
                $fields['Anfrage'] = ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' ' . preg_replace('#/[A-Za-z0-9_-]{20,}#', '/…', $path);
            }
            if ($skipped > 0) {
                $fields['Seit letzter Meldung'] = $skipped . '× erneut aufgetreten';
            }
            Notifier::event('⚠️ Fehler auf der Website', mb_substr($message, 0, 900), $fields, 'Fehler-Meldung · max. 1× pro Stunde je Fehler');
        } catch (Throwable $inner) {
            error_log('[errors] reporting failed: ' . $inner->getMessage());
        }
    }

    /** Fatal errors never reach the exception handler. */
    public static function register(): void
    {
        register_shutdown_function(static function (): void {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR], true)) {
                self::report('Fatal: ' . $err['message'], (string)$err['file'], (int)$err['line']);
            }
        });
    }
}
