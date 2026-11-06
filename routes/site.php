<?php
declare(strict_types=1);

use App\Controllers\Site\AccountController;
use App\Controllers\Site\AuthController;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\InvoiceController;
use App\Controllers\Site\TicketController;
use App\Core\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->post('/anfrage', [HomeController::class, 'submitRequest']);
$router->get('/impressum', [HomeController::class, 'imprint']);
$router->get('/datenschutz', [HomeController::class, 'privacy']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/registrieren', [AuthController::class, 'showRegister']);
$router->post('/registrieren', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/konto', [AccountController::class, 'dashboard']);
$router->get('/konto/anfragen', [AccountController::class, 'requests']);
$router->get('/konto/profil', [AccountController::class, 'profile']);
$router->post('/konto/profil', [AccountController::class, 'updateProfile']);
$router->post('/konto/passwort', [AccountController::class, 'updatePassword']);

$router->get('/konto/tickets', [TicketController::class, 'index']);
$router->get('/konto/tickets/neu', [TicketController::class, 'create']);
$router->post('/konto/tickets', [TicketController::class, 'store']);
$router->get('/konto/tickets/{id}', [TicketController::class, 'show']);
$router->post('/konto/tickets/{id}/antwort', [TicketController::class, 'reply']);
$router->post('/konto/tickets/{id}/schliessen', [TicketController::class, 'close']);

$router->get('/konto/rechnungen', [InvoiceController::class, 'index']);
$router->get('/konto/rechnungen/{id}', [InvoiceController::class, 'show']);
$router->get('/konto/rechnungen/{id}/druck', [InvoiceController::class, 'print']);

return $router;
