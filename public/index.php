<?php
declare(strict_types=1);

define('APP_CONTEXT', 'site');
require dirname(__DIR__) . '/app/bootstrap.php';

(require dirname(__DIR__) . '/routes/site.php')->dispatch();
