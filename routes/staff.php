<?php
declare(strict_types=1);

use App\Controllers\Staff\AuthController;
use App\Controllers\Staff\CustomerController;
use App\Controllers\Staff\DashboardController;
use App\Controllers\Staff\HostingController;
use App\Controllers\Staff\ProductController;
use App\Controllers\Staff\ProfileController;
use App\Controllers\Staff\StaffController;
use App\Controllers\Staff\InvoiceController;
use App\Controllers\Staff\RequestController;
use App\Controllers\Staff\SettingsController;
use App\Controllers\Staff\TicketController;
use App\Core\Router;

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/login/2fa', [AuthController::class, 'showTwoFactor']);
$router->post('/login/2fa', [AuthController::class, 'verifyTwoFactor']);
$router->get('/passwort-vergessen', [AuthController::class, 'showForgot']);
$router->post('/passwort-vergessen', [AuthController::class, 'sendForgot']);
$router->get('/passwort-zuruecksetzen/{token}', [AuthController::class, 'showReset']);
$router->post('/passwort-zuruecksetzen/{token}', [AuthController::class, 'reset']);
$router->get('/email-bestaetigen/{token}', [AuthController::class, 'confirmEmail']);

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

$router->get('/hosting', [HostingController::class, 'index']);
$router->get('/hosting/neu', [HostingController::class, 'create']);
$router->post('/hosting', [HostingController::class, 'store']);
$router->get('/hosting/tarife', [HostingController::class, 'plans']);
$router->post('/hosting/tarife', [HostingController::class, 'storePlan']);
$router->post('/hosting/tarife/{id}/toggle', [HostingController::class, 'togglePlan']);
$router->get('/hosting/{id}', [HostingController::class, 'show']);
$router->post('/hosting/{id}', [HostingController::class, 'update']);
$router->post('/hosting/{id}/verlaengern', [HostingController::class, 'renew']);
$router->post('/hosting/{id}/sperren', [HostingController::class, 'suspend']);
$router->post('/hosting/{id}/entsperren', [HostingController::class, 'unsuspend']);
$router->post('/hosting/{id}/schluessel', [HostingController::class, 'rotateKey']);
$router->post('/hosting/{id}/kuendigen', [HostingController::class, 'cancel']);

$router->get('/verwaltung', [SettingsController::class, 'show']);
$router->post('/verwaltung', [SettingsController::class, 'update']);
$router->get('/verwaltung/betreiber', [SettingsController::class, 'business']);
$router->post('/verwaltung/betreiber', [SettingsController::class, 'updateBusiness']);
$router->get('/verwaltung/email', [SettingsController::class, 'email']);
$router->post('/verwaltung/email', [SettingsController::class, 'updateEmail']);
$router->post('/verwaltung/test-mail', [SettingsController::class, 'testMail']);
$router->get('/verwaltung/integrationen', [SettingsController::class, 'integrations']);
$router->post('/verwaltung/integrationen', [SettingsController::class, 'updateIntegrations']);
$router->post('/verwaltung/discord-test', [SettingsController::class, 'testDiscord']);
$router->get('/verwaltung/hosting', [SettingsController::class, 'hosting']);
$router->post('/verwaltung/hosting', [SettingsController::class, 'updateHosting']);
$router->get('/verwaltung/sicherheit', [SettingsController::class, 'securityPolicy']);
$router->post('/verwaltung/sicherheit', [SettingsController::class, 'updateSecurityPolicy']);

$router->get('/mitarbeiter', [StaffController::class, 'index']);
$router->get('/mitarbeiter/neu', [StaffController::class, 'create']);
$router->post('/mitarbeiter', [StaffController::class, 'store']);
$router->get('/mitarbeiter/{id}', [StaffController::class, 'show']);
$router->post('/mitarbeiter/{id}', [StaffController::class, 'update']);
$router->post('/mitarbeiter/{id}/status', [StaffController::class, 'toggleActive']);
$router->post('/mitarbeiter/{id}/2fa-reset', [StaffController::class, 'resetTwoFactor']);
$router->post('/mitarbeiter/{id}/link', [StaffController::class, 'sendLink']);
$router->post('/kunden/{id}/2fa-reset', [CustomerController::class, 'resetTwoFactor']);
$router->post('/kunden/{id}/passwort-link', [CustomerController::class, 'sendResetLink']);

$router->get('/einstellungen', [ProfileController::class, 'profile']);
$router->post('/einstellungen', [ProfileController::class, 'updateProfile']);
$router->get('/einstellungen/sicherheit', [ProfileController::class, 'security']);
$router->post('/einstellungen/sicherheit/passwort', [ProfileController::class, 'updatePassword']);
$router->post('/einstellungen/sicherheit/email', [ProfileController::class, 'updateEmail']);
$router->post('/einstellungen/sicherheit/email/abbrechen', [ProfileController::class, 'cancelEmail']);
$router->get('/einstellungen/sicherheit/2fa', [ProfileController::class, 'setupTwoFactor']);
$router->post('/einstellungen/sicherheit/2fa', [ProfileController::class, 'enableTwoFactor']);
$router->post('/einstellungen/sicherheit/2fa/deaktivieren', [ProfileController::class, 'disableTwoFactor']);
$router->post('/einstellungen/sicherheit/2fa/codes', [ProfileController::class, 'renewCodes']);
$router->post('/einstellungen/sicherheit/abmelden', [ProfileController::class, 'revokeSessions']);

return $router;
