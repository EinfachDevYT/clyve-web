<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Controllers\Concerns\AuthFlow;
use App\Services\Account;
use App\Services\CustomerProfile;
use App\Services\Settings;

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
        View::render('site/auth/login', [], 'Anmelden', 'auth');
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
        if (Account::needsVerification($user)) {
            $this->awaitVerification((int)$user['id']);
            flash('info', 'Bitte bestätige zuerst deine E-Mail-Adresse.');
            redirect('/bestaetigen');
        }
        $this->completeLogin($user);
    }

    public function showRegister(): void
    {
        $this->guestOnly();
        $this->registrationOpen();
        View::render('site/auth/register', [], 'Konto erstellen', 'auth');
    }

    public function register(): void
    {
        $this->guestOnly();
        $this->registrationOpen();
        if (!RateLimiter::ip('register')) {
            fail_back([], '/registrieren', 'Zu viele Registrierungen. Bitte versuch es später erneut.');
        }

        $v = new Validator($_POST);
        $email = $v->email('email');
        $profile = CustomerProfile::validate($v, $_POST);
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

        $verify = Account::verificationRequired();
        $cols = array_keys($profile);
        $id = Db::insert(
            'INSERT INTO customers (email, password_hash, email_verified_at, ' . implode(', ', $cols) . ")
             VALUES (?, ?, CASE WHEN ? THEN NULL ELSE datetime('now') END" . str_repeat(', ?', count($cols)) . ')',
            [$email, Auth::hash($password), $verify ? 1 : 0, ...array_values($profile)]
        );
        if ($verify) {
            $this->awaitVerification($id);
            Account::sendVerification(Account::find('customer', $id));
            $_SESSION['_verify']['sent'] = time();
            redirect('/bestaetigen');
        }
        Db::exec('UPDATE requests SET customer_id = ? WHERE customer_id IS NULL AND email = ?', [$id, $email]);
        Auth::login($id);
        Account::log('customer', $id, 'login');
        flash('success', 'Dein Konto ist bereit. 🌸');
        redirect('/konto');
    }

    /* ---------- e-mail verification ---------- */

    private function awaitVerification(int $id): void
    {
        session_regenerate_id(true);
        $sent = (int)($_SESSION['_verify']['id'] ?? 0) === $id ? (int)($_SESSION['_verify']['sent'] ?? 0) : 0;
        $_SESSION['_verify'] = ['id' => $id, 'at' => time(), 'sent' => $sent];
    }

    /** Customer waiting for confirmation in this browser, or null. */
    private function pendingVerification(): ?array
    {
        $pending = $_SESSION['_verify'] ?? null;
        if (!is_array($pending) || time() - (int)$pending['at'] > 3600) {
            unset($_SESSION['_verify']);
            return null;
        }
        $customer = Account::find('customer', (int)$pending['id']);
        if (!$customer || !empty($customer['email_verified_at'])) {
            unset($_SESSION['_verify']);
            return null;
        }
        return $customer;
    }

    public function showVerify(): void
    {
        $this->guestOnly();
        $customer = $this->pendingVerification();
        if (!$customer) {
            redirect('/login');
        }
        View::render('site/auth/verify', ['email' => (string)$customer['email']], 'E-Mail bestätigen', 'auth');
    }

    public function resendVerify(): void
    {
        $this->guestOnly();
        $customer = $this->pendingVerification();
        if (!$customer) {
            redirect('/login');
        }
        $last = (int)($_SESSION['_verify']['sent'] ?? 0);
        if (time() - $last < 60) {
            fail_back([], '/bestaetigen', 'Bitte warte eine Minute, bevor du eine neue E-Mail anforderst.');
        }
        if (!RateLimiter::ip('verify', [['window' => 3600, 'max' => 10]]) || !RateLimiter::hit('verify', 'customer:' . $customer['id'], [['window' => 3600, 'max' => 4]])) {
            fail_back([], '/bestaetigen', 'Zu viele Anfragen. Bitte versuch es später erneut.');
        }
        $_SESSION['_verify']['sent'] = time();
        if (!Account::sendVerification($customer)) {
            fail_back([], '/bestaetigen', 'Die E-Mail konnte gerade nicht gesendet werden. Bitte versuch es später erneut.');
        }
        flash('success', 'Neue E-Mail ist unterwegs. Schau auch im Spam-Ordner nach.');
        redirect('/bestaetigen');
    }

    public function verify(array $p): void
    {
        $token = Account::findToken(['verify'], 'customer', (string)($p['token'] ?? ''));
        $customer = $token ? Account::find('customer', (int)$token['account_id']) : null;
        if (!$token || !$customer || strcasecmp((string)$token['data'], (string)$customer['email']) !== 0) {
            $me = Auth::user();
            if ($me && !empty($me['email_verified_at'])) {
                flash('info', 'Deine E-Mail-Adresse ist bereits bestätigt.');
            } else {
                flash('error', 'Der Bestätigungslink ist ungültig oder abgelaufen. Melde dich an, um einen neuen anzufordern.');
            }
            redirect(Auth::user() ? '/konto' : '/login');
        }
        Account::consumeToken((int)$token['id']);
        Account::markVerified((int)$customer['id']);
        $sameBrowser = (int)($_SESSION['_verify']['id'] ?? 0) === (int)$customer['id'];
        unset($_SESSION['_verify']);
        if ($sameBrowser && !Auth::user() && !Account::twoFactorOn($customer)) {
            Auth::login((int)$customer['id']);
            Account::log('customer', (int)$customer['id'], 'login');
            flash('success', 'E-Mail bestätigt – dein Konto ist bereit. 🌸');
            redirect('/konto');
        }
        flash('success', 'E-Mail bestätigt. Du kannst dich jetzt anmelden.');
        redirect(Auth::user() ? '/konto' : '/login');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('info', 'Du wurdest abgemeldet.');
        redirect('/');
    }

    private function registrationOpen(): void
    {
        if (!Settings::on('registration_open')) {
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
