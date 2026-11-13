<?php
declare(strict_types=1);

use App\Controllers\Site\AccountController;
use App\Controllers\Site\AuthController;
use App\Controllers\Site\DevlogController;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\InvoiceController;
use App\Controllers\Site\RequestController;
use App\Controllers\Site\ServiceController;
use App\Controllers\Site\SettingsController;
use App\Controllers\Site\ShopController;
use App\Controllers\Site\TicketController;
use App\Core\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->post('/anfrage', [RequestController::class, 'submit']);
$router->get('/anfrage/{token}', [RequestController::class, 'showByToken']);
$router->post('/anfrage/{token}/antwort', [RequestController::class, 'replyByToken']);
$router->post('/anfrage/{token}/angebot', [RequestController::class, 'offerByToken']);
$router->get('/scripts', [ShopController::class, 'index']);
$router->get('/impressum', [HomeController::class, 'imprint']);
$router->get('/datenschutz', [HomeController::class, 'privacy']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/registrieren', [AuthController::class, 'showRegister']);
$router->post('/registrieren', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/login/2fa', [AuthController::class, 'showTwoFactor']);
$router->post('/login/2fa', [AuthController::class, 'verifyTwoFactor']);
$router->get('/passwort-vergessen', [AuthController::class, 'showForgot']);
$router->post('/passwort-vergessen', [AuthController::class, 'sendForgot']);
$router->get('/passwort-zuruecksetzen/{token}', [AuthController::class, 'showReset']);
$router->post('/passwort-zuruecksetzen/{token}', [AuthController::class, 'reset']);
$router->get('/bestaetigen', [AuthController::class, 'showVerify']);
$router->post('/bestaetigen', [AuthController::class, 'resendVerify']);
$router->get('/bestaetigen/{token}', [AuthController::class, 'verify']);
$router->get('/email-bestaetigen/{token}', [AuthController::class, 'confirmEmail']);

$router->get('/konto', [AccountController::class, 'dashboard']);
$router->get('/konto/anfragen', [AccountController::class, 'requests']);
$router->get('/konto/anfragen/{id}', [RequestController::class, 'showForCustomer']);
$router->post('/konto/anfragen/{id}/antwort', [RequestController::class, 'replyForCustomer']);
$router->post('/konto/anfragen/{id}/angebot', [RequestController::class, 'offerForCustomer']);
$router->get('/konto/profil', [SettingsController::class, 'legacyProfile']);
$router->get('/konto/einstellungen', [SettingsController::class, 'profile']);
$router->post('/konto/einstellungen', [SettingsController::class, 'updateProfile']);
$router->get('/konto/einstellungen/sicherheit', [SettingsController::class, 'security']);
$router->post('/konto/einstellungen/sicherheit/passwort', [SettingsController::class, 'updatePassword']);
$router->post('/konto/einstellungen/sicherheit/email', [SettingsController::class, 'updateEmail']);
$router->post('/konto/einstellungen/sicherheit/email/abbrechen', [SettingsController::class, 'cancelEmail']);
$router->get('/konto/einstellungen/sicherheit/2fa', [SettingsController::class, 'setupTwoFactor']);
$router->post('/konto/einstellungen/sicherheit/2fa', [SettingsController::class, 'enableTwoFactor']);
$router->post('/konto/einstellungen/sicherheit/2fa/deaktivieren', [SettingsController::class, 'disableTwoFactor']);
$router->post('/konto/einstellungen/sicherheit/2fa/codes', [SettingsController::class, 'renewCodes']);
$router->post('/konto/einstellungen/sicherheit/abmelden', [SettingsController::class, 'revokeSessions']);

$router->get('/konto/tickets', [TicketController::class, 'index']);
$router->get('/konto/tickets/neu', [TicketController::class, 'create']);
$router->post('/konto/tickets', [TicketController::class, 'store']);
$router->get('/konto/tickets/{id}', [TicketController::class, 'show']);
$router->post('/konto/tickets/{id}/antwort', [TicketController::class, 'reply']);
$router->post('/konto/tickets/{id}/schliessen', [TicketController::class, 'close']);

$router->get('/konto/dienste', [ServiceController::class, 'index']);
$router->get('/konto/dienste/{id}', [ServiceController::class, 'show']);

$router->get('/konto/devlog', [DevlogController::class, 'index']);
$router->get('/devlog', [DevlogController::class, 'legacy']);
$router->get('/devlog/{slug}', [DevlogController::class, 'show']);

$router->get('/konto/rechnungen', [InvoiceController::class, 'index']);
$router->get('/konto/rechnungen/{id}', [InvoiceController::class, 'show']);
$router->get('/konto/rechnungen/{id}/druck', [InvoiceController::class, 'print']);

return $router;
