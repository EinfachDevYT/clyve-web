<?php
declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\View;
use App\Services\Account;
use App\Services\Mailer;

/** Login with optional second factor, password reset and e-mail confirmation – for both portals. */
trait AuthFlow
{
    abstract protected function home(): string;

    private function layout(): string
    {
        return APP_CONTEXT === 'staff' ? 'auth' : 'layout';
    }

    /** Called after e-mail and password were verified. */
    protected function completeLogin(array $user): never
    {
        if (Account::twoFactorOn($user)) {
            Auth::beginTwoFactor((int)$user['id']);
            redirect('/login/2fa');
        }
        Auth::login((int)$user['id']);
        Account::log(Account::type(), (int)$user['id'], 'login');
        redirect(Auth::intended($this->home()));
    }

    public function showTwoFactor(): void
    {
        if (Auth::pendingTwoFactor() === null) {
            redirect('/login');
        }
        View::render('shared/auth/two-factor', [], 'Bestätigung', $this->layout());
    }

    public function verifyTwoFactor(): void
    {
        $id = Auth::pendingTwoFactor();
        if ($id === null) {
            flash('info', 'Bitte melde dich erneut an.');
            redirect('/login');
        }
        if (!RateLimiter::ip('twofa', [['window' => 600, 'max' => 15]])) {
            fail_back([], '/login/2fa', 'Zu viele Versuche. Bitte warte ein paar Minuten.');
        }
        $type = Account::type();
        $account = Account::find($type, $id);
        $result = $account ? Account::verifySecondFactor($type, $account, (string)($_POST['code'] ?? '')) : null;
        if ($result === null) {
            Auth::failTwoFactor();
            Account::log($type, $id, 'twofa_failed');
            if (Auth::pendingTwoFactor() === null) {
                flash('error', 'Zu viele falsche Codes. Bitte melde dich erneut an.');
                redirect('/login');
            }
            fail_back(['code' => 'Der Code stimmt nicht.'], '/login/2fa', 'Der Code stimmt nicht.');
        }
        Auth::login($id);
        Account::log($type, $id, $result === 'recovery' ? 'recovery_used' : 'login');
        if ($result === 'recovery') {
            $left = Account::recoveryLeft($type, $id);
            flash($left <= 3 ? 'error' : 'info', 'Wiederherstellungscode verwendet – noch ' . $left . ' übrig.' . ($left <= 3 ? ' Erzeuge bald neue in deinen Einstellungen.' : ''));
            Account::notify($account, 'Anmeldung mit Wiederherstellungscode', ['in deinem Konto wurde gerade ein Wiederherstellungscode für die Anmeldung verwendet.']);
        }
        redirect(Auth::intended($this->home()));
    }

    public function showForgot(): void
    {
        if (Auth::user()) {
            redirect($this->home());
        }
        View::render('shared/auth/forgot', ['mail' => Mailer::enabled()], 'Passwort vergessen', $this->layout());
    }

    public function sendForgot(): void
    {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if (!RateLimiter::ip('reset', [['window' => 3600, 'max' => 6]]) || !RateLimiter::hit('reset', 'mail:' . $email, [['window' => 3600, 'max' => 3]])) {
            fail_back([], '/passwort-vergessen', 'Zu viele Anfragen. Bitte versuch es später erneut.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            fail_back(['email' => 'Bitte gib eine gültige E-Mail-Adresse an.'], '/passwort-vergessen');
        }
        $type = Account::type();
        $account = Account::findByEmail($type, $email);
        if ($account && Mailer::enabled()) {
            $token = Account::createToken('reset', $type, (int)$account['id']);
            Mailer::send($account['email'], 'Passwort zurücksetzen', Mailer::compose(explode(' ', (string)$account['name'])[0], [
                'du hast angefordert, dein Passwort zurückzusetzen. Der Link ist 60 Minuten gültig und funktioniert nur einmal.',
                'Wenn du das nicht warst, kannst du diese E-Mail ignorieren – dein Passwort bleibt unverändert.',
            ], 'Neues Passwort festlegen', Account::baseUrl($type) . '/passwort-zuruecksetzen/' . $token));
        }
        flash('success', 'Falls ein Konto mit dieser E-Mail existiert, ist ein Link unterwegs. Schau auch im Spam-Ordner nach.');
        redirect('/login');
    }

    public function showReset(array $p): void
    {
        $token = Account::findToken(['reset', 'invite'], Account::type(), (string)($p['token'] ?? ''));
        if (!$token) {
            flash('error', 'Der Link ist ungültig oder abgelaufen. Fordere bitte einen neuen an.');
            redirect('/passwort-vergessen');
        }
        $account = Account::find(Account::type(), (int)$token['account_id']);
        View::render('shared/auth/reset', ['invite' => $token['purpose'] === 'invite', 'token' => (string)$p['token'], 'account' => $account], $token['purpose'] === 'invite' ? 'Konto einrichten' : 'Neues Passwort', $this->layout());
    }

    public function reset(array $p): void
    {
        $type = Account::type();
        $raw = (string)($p['token'] ?? '');
        $token = Account::findToken(['reset', 'invite'], $type, $raw);
        $account = $token ? Account::find($type, (int)$token['account_id']) : null;
        if (!$token || !$account) {
            flash('error', 'Der Link ist ungültig oder abgelaufen.');
            redirect('/passwort-vergessen');
        }
        $password = (string)($_POST['password'] ?? '');
        if ($problem = Auth::passwordProblem($password, $account['email'])) {
            fail_back(['password' => $problem], '/passwort-zuruecksetzen/' . $raw);
        }
        if (!hash_equals($password, (string)($_POST['password_confirm'] ?? ''))) {
            fail_back(['password_confirm' => 'Die Passwörter stimmen nicht überein.'], '/passwort-zuruecksetzen/' . $raw);
        }
        if (!Account::consumeToken((int)$token['id'])) {
            redirect('/login');
        }
        Account::setPassword($type, (int)$account['id'], $password);
        Account::log($type, (int)$account['id'], 'password_reset');
        if ($token['purpose'] === 'reset') {
            Account::notify($account, 'Dein Passwort wurde geändert', ['das Passwort für dein Konto wurde gerade über „Passwort vergessen“ neu festgelegt. Alle Geräte wurden abgemeldet.']);
        }
        Auth::logout();
        flash('success', $token['purpose'] === 'invite' ? 'Dein Konto ist eingerichtet. Melde dich jetzt an.' : 'Passwort geändert. Melde dich mit dem neuen Passwort an.');
        redirect('/login');
    }

    public function confirmEmail(array $p): void
    {
        $type = Account::type();
        $token = Account::findToken(['email'], $type, (string)($p['token'] ?? ''));
        $account = $token ? Account::find($type, (int)$token['account_id']) : null;
        $target = Auth::user() ? $this->settingsUrl() : '/login';
        if (!$token || !$account) {
            flash('error', 'Der Bestätigungslink ist ungültig oder abgelaufen.');
            redirect($target);
        }
        $email = (string)$token['data'];
        if (Account::emailTaken($type, $email, (int)$account['id'])) {
            Account::cancelEmail($type, (int)$account['id']);
            flash('error', 'Diese E-Mail-Adresse wird inzwischen von einem anderen Konto verwendet.');
            redirect($target);
        }
        Account::consumeToken((int)$token['id']);
        Account::changeEmail($type, (int)$account['id'], $email);
        Account::notify($account, 'Deine E-Mail-Adresse wurde geändert', ['die E-Mail-Adresse deines Kontos wurde auf ' . $email . ' geändert. Diese Adresse bekommt keine Nachrichten mehr.']);
        flash('success', 'E-Mail-Adresse bestätigt und geändert.');
        redirect($target);
    }

    abstract protected function settingsUrl(): string;
}
