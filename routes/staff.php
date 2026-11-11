<?php
declare(strict_types=1);

use App\Controllers\Staff\AuthController;
use App\Controllers\Staff\CustomerController;
use App\Controllers\Staff\DashboardController;
use App\Controllers\Staff\ProductController;
use App\Controllers\Staff\InvoiceController;
use App\Controllers\Staff\RequestController;
use App\Controllers\Staff\SettingsController;
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
$router->post('/anfragen/{id}/antwort', [RequestController::class, 'reply']);
$router->post('/anfragen/{id}/angebot', [RequestController::class, 'offer']);
$router->post('/anfragen/{id}/link', [RequestController::class, 'rotateLink']);
$router->post('/anfragen/{id}/rechnung', [RequestController::class, 'createInvoice']);

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

$router->get('/scripts', [ProductController::class, 'index']);
$router->get('/scripts/neu', [ProductController::class, 'create']);
$router->post('/scripts', [ProductController::class, 'store']);
$router->post('/scripts/sync', [ProductController::class, 'sync']);
$router->get('/scripts/{id}', [ProductController::class, 'edit']);
$router->post('/scripts/{id}', [ProductController::class, 'update']);
$router->post('/scripts/{id}/toggle', [ProductController::class, 'toggle']);
$router->post('/scripts/{id}/loeschen', [ProductController::class, 'destroy']);

$router->get('/einstellungen', [SettingsController::class, 'show']);
$router->post('/einstellungen', [SettingsController::class, 'update']);
$router->get('/einstellungen/betreiber', [SettingsController::class, 'business']);
$router->post('/einstellungen/betreiber', [SettingsController::class, 'updateBusiness']);
$router->get('/einstellungen/email', [SettingsController::class, 'email']);
$router->post('/einstellungen/email', [SettingsController::class, 'updateEmail']);
$router->post('/einstellungen/test-mail', [SettingsController::class, 'testMail']);
$router->get('/einstellungen/integrationen', [SettingsController::class, 'integrations']);
$router->post('/einstellungen/integrationen', [SettingsController::class, 'updateIntegrations']);
$router->post('/einstellungen/discord-test', [SettingsController::class, 'testDiscord']);

return $router;
