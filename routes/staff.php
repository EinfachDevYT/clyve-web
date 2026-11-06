<?php
declare(strict_types=1);

use App\Controllers\Staff\AuthController;
use App\Controllers\Staff\CustomerController;
use App\Controllers\Staff\DashboardController;
use App\Controllers\Staff\InvoiceController;
use App\Controllers\Staff\RequestController;
use App\Controllers\Staff\TicketController;
use App\Core\Router;

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/anfragen', [RequestController::class, 'index']);
$router->get('/anfragen/{id}', [RequestController::class, 'show']);
$router->post('/anfragen/{id}/status', [RequestController::class, 'updateStatus']);

$router->get('/tickets', [TicketController::class, 'index']);
$router->get('/tickets/{id}', [TicketController::class, 'show']);
$router->post('/tickets/{id}/antwort', [TicketController::class, 'reply']);
$router->post('/tickets/{id}/status', [TicketController::class, 'updateStatus']);

$router->get('/kunden', [CustomerController::class, 'index']);
$router->get('/kunden/{id}', [CustomerController::class, 'show']);

$router->get('/rechnungen', [InvoiceController::class, 'index']);
$router->get('/rechnungen/neu', [InvoiceController::class, 'create']);
$router->post('/rechnungen', [InvoiceController::class, 'store']);
$router->get('/rechnungen/{id}', [InvoiceController::class, 'show']);
$router->post('/rechnungen/{id}', [InvoiceController::class, 'update']);
$router->post('/rechnungen/{id}/positionen', [InvoiceController::class, 'addItem']);
$router->post('/rechnungen/{id}/positionen/{item}/loeschen', [InvoiceController::class, 'removeItem']);
$router->post('/rechnungen/{id}/ausstellen', [InvoiceController::class, 'issue']);
$router->post('/rechnungen/{id}/bezahlt', [InvoiceController::class, 'markPaid']);
$router->post('/rechnungen/{id}/stornieren', [InvoiceController::class, 'cancel']);
$router->post('/rechnungen/{id}/loeschen', [InvoiceController::class, 'destroy']);
$router->get('/rechnungen/{id}/druck', [InvoiceController::class, 'print']);

return $router;
