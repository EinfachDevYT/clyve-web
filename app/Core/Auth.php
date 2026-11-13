<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    // Verified against when the e-mail is unknown, so response time doesn't reveal which accounts exist.
    private const DUMMY_HASH = '$2y$12$gWbXhksS23NrXc38zj7O6uFqUGDN7KNEubtdHBCYQ1YYZb.mmhEk2';

    private static ?array $user = null;
    private static bool $loaded = false;

    private static function table(): string
    {
        return APP_CONTEXT === 'staff' ? 'staff' : 'customers';
    }

    private static function algo(): string|int
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algo());
    }

    public static function passwordProblem(string $password, string $email = ''): ?string
    {
        $len = mb_strlen($password);
        if ($len < 10) {
            return 'Mindestens 10 Zeichen.';
        }
        if ($len > 200) {
            return 'Maximal 200 Zeichen.';
        }
        $local = strtolower((string)strtok($email, '@'));
        if (strlen($local) >= 4 && str_contains(strtolower($password), $local)) {
            return 'Das Passwort darf nicht deine E-Mail enthalten.';
        }
        if (count(array_unique(mb_str_split($password))) < 5) {
            return 'Das Passwort ist zu einfach.';
        }
        return null;
    }

    /** Checks e-mail and password without logging in. */
    public static function verify(string $email, string $password): ?array
    {
        $table = self::table();
        $active = $table === 'staff' ? ' AND active = 1' : ' AND deleted_at IS NULL';
        $user = Db::one("SELECT * FROM {$table} WHERE email = ?{$active}", [$email]);

        $valid = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);
        if (!$user) {
            return null;
        }
        if (!$valid) {
            \App\Services\Account::log(\App\Services\Account::type(), (int)$user['id'], 'login_failed');
            return null;
        }
        if (password_needs_rehash($user['password_hash'], self::algo())) {
            Db::exec("UPDATE {$table} SET password_hash = ? WHERE id = ?", [self::hash($password), $user['id']]);
        }
        return $user;
    }

    public static function checkPassword(int $id, string $password): bool
    {
        $hash = (string)Db::value('SELECT password_hash FROM ' . self::table() . ' WHERE id = ?', [$id]);
        return strlen($password) <= 200 && password_verify($password, $hash !== '' ? $hash : self::DUMMY_HASH);
    }

    /** Password was right, second factor still missing. */
    public static function beginTwoFactor(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['_2fa'] = ['id' => $id, 'at' => time(), 'tries' => 0, 'fp' => self::fingerprint()];
        Security::rotateCsrf();
    }

    public static function pendingTwoFactor(): ?int
    {
        $p = $_SESSION['_2fa'] ?? null;
        if (!is_array($p) || time() - (int)$p['at'] > 600 || (int)$p['tries'] >= 5 || !hash_equals((string)$p['fp'], self::fingerprint())) {
            unset($_SESSION['_2fa']);
            return null;
        }
        return (int)$p['id'];
    }

    public static function failTwoFactor(): void
    {
        if (isset($_SESSION['_2fa'])) {
            $_SESSION['_2fa']['tries']++;
        }
    }

    public static function login(int $id): void
    {
        session_regenerate_id(true);
        unset($_SESSION['_2fa']);
        $_SESSION['_uid'] = $id;
        $_SESSION['_fp'] = self::fingerprint();
        $_SESSION['_sv'] = (int)Db::value('SELECT session_version FROM ' . self::table() . ' WHERE id = ?', [$id]);
        Security::rotateCsrf();
        self::$loaded = false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
        self::$loaded = true;
    }

    private static function fingerprint(): string
    {
        return hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;

        $id = (int)($_SESSION['_uid'] ?? 0);
        if ($id <= 0 || !hash_equals((string)($_SESSION['_fp'] ?? ''), self::fingerprint())) {
            return self::$user = null;
        }
        $table = self::table();
        $active = $table === 'staff' ? ' AND active = 1' : ' AND deleted_at IS NULL';
        $user = Db::one("SELECT * FROM {$table} WHERE id = ?{$active}", [$id]);
        if (!$user || (int)$user['session_version'] !== (int)($_SESSION['_sv'] ?? 0)) {
            unset($_SESSION['_uid'], $_SESSION['_sv']);
            return self::$user = null;
        }
        $user['has_2fa'] = !empty($user['totp_enabled_at']);
        unset($user['password_hash'], $user['totp_secret']);
        return self::$user = $user;
    }

    public static function id(): int
    {
        return (int)(self::user()['id'] ?? 0);
    }

    public static function require(): array
    {
        $user = self::user();
        if ($user) {
            if (APP_CONTEXT === 'site' && \App\Services\Account::needsVerification($user)) {
                self::logout();
                $_SESSION['_verify'] = ['id' => (int)$user['id'], 'at' => time()];
                flash('info', 'Bitte bestätige zuerst deine E-Mail-Adresse.');
                redirect('/bestaetigen');
            }
            if (APP_CONTEXT === 'staff' && !$user['has_2fa'] && \App\Services\Settings::on('staff_require_2fa')) {
                $path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
                if (!str_starts_with($path, '/einstellungen/sicherheit')) {
                    flash('info', 'Bitte richte zuerst die Zwei-Faktor-Anmeldung ein – sie ist für den Staff-Bereich Pflicht.');
                    redirect('/einstellungen/sicherheit');
                }
            }
            return $user;
        }
        $_SESSION['_intended'] = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        flash('info', 'Bitte melde dich an.');
        redirect('/login');
    }

    public static function requireAdmin(): array
    {
        $user = self::require();
        if (($user['role'] ?? '') !== 'admin') {
            abort(403);
        }
        return $user;
    }

    public static function intended(string $fallback): string
    {
        $to = (string)($_SESSION['_intended'] ?? $fallback);
        unset($_SESSION['_intended']);
        return $to;
    }
}
