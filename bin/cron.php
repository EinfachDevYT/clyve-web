<?php
declare(strict_types=1);

// Every 5 minutes: */5 * * * * www-data php /var/www/clyve-web/bin/cron.php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
define('APP_CONTEXT', 'cron');
require dirname(__DIR__) . '/app/bootstrap.php';

if (App\Services\Backup::due()) {
    try {
        echo date('c') . ' backup: ' . App\Services\Backup::create() . "\n";
    } catch (Throwable $e) {
        error_log('[backup] ' . $e->getMessage());
        App\Services\ErrorReporter::report('Backup fehlgeschlagen: ' . $e->getMessage(), $e->getFile(), $e->getLine());
        fwrite(STDERR, "backup failed: {$e->getMessage()}\n");
    }
}

$r = App\Services\Hosting::tick(true);
if ($r === null) {
    fwrite(STDERR, "hosting: skipped (locked or failed, see error log)\n");
    exit(1);
}
if ($r['reminded'] || $r['suspended']) {
    echo date('c') . " hosting: {$r['reminded']} reminder(s), {$r['suspended']} suspended\n";
}
