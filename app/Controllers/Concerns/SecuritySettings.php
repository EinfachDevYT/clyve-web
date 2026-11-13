<?php
declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Core\Auth;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Services\Account;
use App\Services\Mailer;
use App\Services\QrCode;
use App\Services\Totp;

/** Personal security settings (password, e-mail, two-factor, devices) – for both portals. */
trait SecuritySettings
{
    abstract protected function base(): string;

    /** Only admins of the staff area may view their own recovery codes again. */
    private function mayRevealCodes(array $user): bool
    {
        return APP_CONTEXT === 'staff' && ($user['role'] ?? '') === 'admin';
    }

    private function pageLayout(): string
    {
        return APP_CONTEXT === 'site' ? 'portal' : 'layout';
    }

    private function securityUrl(): string
    {
        return $this->base() . '/sicherheit';
    }

    public function security(): void
    {
        $user = Auth::require();
        $type = Account::type();
        $codes = $_SESSION['_recovery_once'] ?? null;
        $revealed = $_SESSION['_recovery_reveal'] ?? null;
        unset($_SESSION['_recovery_once'], $_SESSION['_recovery_reveal']);
        $canReveal = $user['has_2fa'] && $this->mayRevealCodes($user);
        View::render('shared/security/index', [
            'base'          => $this->base(),
            'pendingEmail'  => Account::pendingEmail($type, (int)$user['id']),
            'mail'          => Mailer::enabled(),
            'events'        => Account::events($type, (int)$user['id'], 10),
            'recoveryLeft'  => $user['has_2fa'] ? Account::recoveryLeft($type, (int)$user['id']) : 0,
            'recoveryCodes' => is_array($codes) ? $codes : null,
            'canReveal'     => $canReveal,
            'hasCopy'       => $canReveal && Account::hasStoredRecoveryCodes($type, (int)$user['id']),
            'revealed'      => $canReveal && is_array($revealed) ? $revealed : null,
        ], 'Sicherheit', $this->pageLayout());
    }

    private function guardAttempts(): void
    {
        if (!RateLimiter::hit('reauth', Account::type() . ':' . Auth::id(), [['window' => 900, 'max' => 10]])) {
            fail_back([], $this->securityUrl(), 'Zu viele Versuche. Bitte warte ein paar Minuten.');
        }
    }

    private function requirePassword(string $field = 'password_current'): void
    {
        $this->guardAttempts();
        if (!Auth::checkPassword(Auth::id(), (string)($_POST[$field] ?? ''))) {
            fail_back([$field => 'Das Passwort ist falsch.'], $this->securityUrl());
        }
    }

    public function updatePassword(): void
    {
        $user = Auth::require();
        $this->requirePassword();
        $new = (string)($_POST['password'] ?? '');
        if ($problem = Auth::passwordProblem($new, $user['email'])) {
            fail_back(['password' => $problem], $this->securityUrl());
        }
        if (!hash_equals($new, (string)($_POST['password_confirm'] ?? ''))) {
            fail_back(['password_confirm' => 'Die Passwörter stimmen nicht überein.'], $this->securityUrl());
        }
        $type = Account::type();
        Account::setPassword($type, (int)$user['id'], $new);
        Auth::login((int)$user['id']);
        Account::log($type, (int)$user['id'], 'password_changed');
        Account::notify($user, 'Dein Passwort wurde geändert', ['das Passwort für dein Konto wurde gerade geändert. Andere Geräte wurden abgemeldet.']);
        flash('success', 'Passwort geändert. Andere Geräte wurden abgemeldet.');
        redirect($this->securityUrl());
    }

    public function updateEmail(): void
    {
        $user = Auth::require();
        $type = Account::type();
        $v = new Validator($_POST);
        $email = $v->email('email');
        if (!$v->fails() && $email === strtolower((string)$user['email'])) {
            $v->addError('email', 'Das ist bereits deine E-Mail-Adresse.');
        }
        if (!$v->fails() && Account::emailTaken($type, $email, (int)$user['id'])) {
            $v->addError('email', 'Diese E-Mail-Adresse wird bereits verwendet.');
        }
        if ($v->fails()) {
            fail_back($v->errors(), $this->securityUrl());
        }
        $this->requirePassword('password_email');

        if (!Mailer::enabled()) {
            Account::changeEmail($type, (int)$user['id'], $email);
            flash('success', 'E-Mail-Adresse geändert.');
            redirect($this->securityUrl());
        }
        $token = Account::createToken('email', $type, (int)$user['id'], $email);
        $sent = Mailer::send($email, 'Neue E-Mail-Adresse bestätigen', Mailer::compose(explode(' ', (string)$user['name'])[0], [
            'bitte bestätige, dass du diese Adresse für dein Konto verwenden möchtest. Der Link ist 24 Stunden gültig.',
            'Bis zur Bestätigung bleibt deine bisherige Adresse aktiv.',
        ], 'Adresse bestätigen', Account::baseUrl($type) . '/email-bestaetigen/' . $token));
        if (!$sent) {
            Account::cancelEmail($type, (int)$user['id']);
            fail_back([], $this->securityUrl(), 'Die Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte versuch es später erneut.');
        }
        flash('success', 'Fast geschafft: Bestätige die neue Adresse über den Link, den wir an ' . $email . ' geschickt haben.');
        redirect($this->securityUrl());
    }

    public function cancelEmail(): void
    {
        $user = Auth::require();
        Account::cancelEmail(Account::type(), (int)$user['id']);
        flash('info', 'E-Mail-Wechsel abgebrochen.');
        redirect($this->securityUrl());
    }

    public function setupTwoFactor(): void
    {
        $user = Auth::require();
        if ($user['has_2fa']) {
            redirect($this->securityUrl());
        }
        $pending = $_SESSION['_totp_setup'] ?? null;
        if (!is_array($pending) || time() - (int)$pending['at'] > 900) {
            $pending = ['secret' => Totp::newSecret(), 'at' => time()];
            $_SESSION['_totp_setup'] = $pending;
        }
        $uri = Totp::uri($pending['secret'], (string)$user['email']);
        View::render('shared/security/two-factor', [
            'base'   => $this->base(),
            'qr'     => QrCode::svg($uri, 5),
            'secret' => Totp::pretty($pending['secret']),
        ], 'Zwei-Faktor-Anmeldung', $this->pageLayout());
    }

    public function enableTwoFactor(): void
    {
        $user = Auth::require();
        $pending = $_SESSION['_totp_setup'] ?? null;
        if ($user['has_2fa'] || !is_array($pending)) {
            redirect($this->securityUrl());
        }
        $this->guardAttempts();
        $step = Totp::verify((string)$pending['secret'], (string)($_POST['code'] ?? ''));
        if ($step === null) {
            fail_back(['code' => 'Der Code stimmt nicht. Prüfe die Uhrzeit deines Handys und versuch es erneut.'], $this->securityUrl() . '/2fa');
        }
        unset($_SESSION['_totp_setup']);
        $_SESSION['_recovery_once'] = Account::enableTwoFactor(Account::type(), (int)$user['id'], (string)$pending['secret'], $step, $this->mayRevealCodes($user));
        flash('success', 'Zwei-Faktor-Anmeldung ist aktiv. Speichere jetzt deine Wiederherstellungscodes.');
        redirect($this->securityUrl());
    }

    public function disableTwoFactor(): void
    {
        $user = Auth::require();
        if (!$user['has_2fa']) {
            redirect($this->securityUrl());
        }
        $this->requirePassword('password_2fa');
        $type = Account::type();
        $account = Account::find($type, (int)$user['id']);
        if (!Account::verifySecondFactor($type, $account, (string)($_POST['code'] ?? ''))) {
            fail_back(['code_off' => 'Der Code stimmt nicht.'], $this->securityUrl());
        }
        Account::disableTwoFactor($type, (int)$user['id']);
        Account::notify($user, 'Zwei-Faktor-Anmeldung deaktiviert', ['für dein Konto wurde die Zwei-Faktor-Anmeldung gerade ausgeschaltet.']);
        flash('info', 'Zwei-Faktor-Anmeldung deaktiviert.');
        redirect($this->securityUrl());
    }

    public function renewCodes(): void
    {
        $user = Auth::require();
        if (!$user['has_2fa']) {
            redirect($this->securityUrl());
        }
        $this->requirePassword('password_codes');
        $type = Account::type();
        $_SESSION['_recovery_once'] = Account::newRecoveryCodes($type, (int)$user['id'], $this->mayRevealCodes($user));
        Account::log($type, (int)$user['id'], 'codes_renewed');
        flash('success', 'Neue Wiederherstellungscodes erzeugt. Die alten gelten nicht mehr.');
        redirect($this->securityUrl());
    }

    public function revealCodes(): void
    {
        $user = Auth::require();
        if (!$user['has_2fa'] || !$this->mayRevealCodes($user)) {
            abort(404);
        }
        $this->requirePassword('password_reveal');
        $type = Account::type();
        $code = (string)preg_replace('/\s+/', '', (string)($_POST['code'] ?? ''));
        $account = Account::find($type, (int)$user['id']);
        if (!preg_match('/^\d{6}$/', $code) || Account::verifySecondFactor($type, $account, $code) !== 'totp') {
            fail_back(['code_reveal' => 'Der Code aus der App stimmt nicht.'], $this->securityUrl());
        }
        $codes = Account::storedRecoveryCodes($type, (int)$user['id']);
        if ($codes === null) {
            fail_back([], $this->securityUrl(), 'Für deine aktuellen Codes ist keine Kopie gespeichert. Erzeuge einmal neue Codes, dann kannst du sie künftig wieder anzeigen.');
        }
        $_SESSION['_recovery_reveal'] = $codes;
        Account::log($type, (int)$user['id'], 'codes_viewed');
        Account::notify($user, 'Wiederherstellungscodes angezeigt', ['in deinem Staff-Konto wurden gerade die Wiederherstellungscodes angezeigt.']);
        audit('staff:' . $user['id'], 'recovery_codes_viewed');
        redirect($this->securityUrl());
    }

    public function revokeSessions(): void
    {
        $user = Auth::require();
        $type = Account::type();
        Account::revokeSessions($type, (int)$user['id']);
        Auth::login((int)$user['id']);
        Account::log($type, (int)$user['id'], 'sessions_revoked');
        flash('success', 'Alle anderen Geräte wurden abgemeldet.');
        redirect($this->securityUrl());
    }
}
