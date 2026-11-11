<?php
declare(strict_types=1);

use App\Controllers\Api\LicenseController;
use App\Core\Router;

$router = new Router();
$router->get('/api/v1/license', [LicenseController::class, 'check']);

return $router;
