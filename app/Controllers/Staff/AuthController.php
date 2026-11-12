<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\View;
use App\Controllers\Concerns\AuthFlow;

final class AuthController
{
    use AuthFlow;

    protected function home(): string
    {
        return '/';
    }

    protected function settingsUrl(): string
    {
        return '/einstellungen/sicherheit';
    }

    public function showLogin(): void
    {
        if (Auth::user()) {
            redirect('/');
        }
        View::render('staff/login', [], 'Staff-Login', 'auth');
    }

    public function login(): void
    {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if (!RateLimiter::ip('login') || !RateLimiter::hit('login', 'staff:' . $email)) {
            fail_back([], '/login', 'Zu viele Versuche. Bitte warte ein paar Minuten.');
        }
        $user = strlen($password) <= 200 ? Auth::verify($email, $password) : null;
        if (!$user) {
            audit('staff:?', 'login_failed', $email);
            fail_back(['email' => ' '], '/login', 'Anmeldung fehlgeschlagen.');
        }
        audit('staff:' . $user['id'], 'login');
        $this->completeLogin($user);
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
