<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Controllers\Concerns\AuthFlow;

final class AuthController
{
    use AuthFlow;

    protected function home(): string
    {
        return '/konto';
    }

    protected function settingsUrl(): string
    {
        return '/konto/einstellungen/sicherheit';
    }

    public function showLogin(): void
    {
        $this->guestOnly();
        View::render('site/auth/login', [], 'Anmelden');
    }

    public function login(): void
    {
        $this->guestOnly();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if (!RateLimiter::ip('login') || !RateLimiter::hit('login', 'mail:' . $email)) {
            fail_back([], '/login', 'Zu viele Versuche. Bitte warte ein paar Minuten.');
        }
        $user = strlen($password) <= 200 ? Auth::verify($email, $password) : null;
        if (!$user) {
            fail_back(['email' => ' '], '/login', 'E-Mail oder Passwort ist falsch.');
        }
        $this->completeLogin($user);
    }

    public function showRegister(): void
    {
        $this->guestOnly();
        $this->registrationOpen();
        View::render('site/auth/register', [], 'Konto erstellen');
    }

    public function register(): void
    {
        $this->guestOnly();
        $this->registrationOpen();
        if (!RateLimiter::ip('register')) {
            fail_back([], '/registrieren', 'Zu viele Registrierungen. Bitte versuch es später erneut.');
        }

        $v = new Validator($_POST);
        $name = $v->text('name', 2, 80, 'Name');
        $email = $v->email('email');
        $password = (string)($_POST['password'] ?? '');
        if ($problem = Auth::passwordProblem($password, $email)) {
            $v->addError('password', $problem);
        } elseif (!hash_equals($password, (string)($_POST['password_confirm'] ?? ''))) {
            $v->addError('password_confirm', 'Die Passwörter stimmen nicht überein.');
        }
        $v->accepted('consent', 'Bitte akzeptiere die Datenschutzerklärung.');

        if (!$v->fails() && Db::value('SELECT 1 FROM customers WHERE email = ?', [$email])) {
            $v->addError('email', 'Zu dieser E-Mail existiert bereits ein Konto.');
        }
        if ($v->fails()) {
            fail_back($v->errors(), '/registrieren');
        }

        $id = Db::insert('INSERT INTO customers (email, name, password_hash) VALUES (?, ?, ?)', [$email, $name, Auth::hash($password)]);
        Db::exec('UPDATE requests SET customer_id = ? WHERE customer_id IS NULL AND email = ?', [$id, $email]);
        Auth::login($id);
        \App\Services\Account::log('customer', $id, 'login');
        flash('success', 'Dein Konto ist bereit. 🌸');
        redirect('/konto');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('info', 'Du wurdest abgemeldet.');
        redirect('/');
    }

    private function registrationOpen(): void
    {
        if (!\App\Services\Settings::on('registration_open')) {
            flash('info', 'Neue Konten können gerade nicht erstellt werden.');
            redirect('/login');
        }
    }

    private function guestOnly(): void
    {
        if (Auth::user()) {
            redirect('/konto');
        }
    }
}
