<?php
declare(strict_types=1);

use App\Core\Security;

define('APP_CONTEXT', 'staff');
require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');

if (!Security::ipAllowed((array)config('staff_ip_allowlist', []))) {
    http_response_code(404);
    exit;
}

(require dirname(__DIR__) . '/routes/staff.php')->dispatch();
