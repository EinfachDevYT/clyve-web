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

    public static function attempt(string $email, string $password): ?array
    {
        $table = self::table();
        $active = $table === 'staff' ? ' AND active = 1' : '';
        $user = Db::one("SELECT * FROM {$table} WHERE email = ?{$active}", [$email]);

        $valid = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);
        if (!$user || !$valid) {
            return null;
        }
        if (password_needs_rehash($user['password_hash'], self::algo())) {
            Db::exec("UPDATE {$table} SET password_hash = ? WHERE id = ?", [self::hash($password), $user['id']]);
        }
        self::login((int)$user['id']);
        return $user;
    }

    public static function login(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['_uid'] = $id;
        $_SESSION['_fp'] = self::fingerprint();
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
        $active = $table === 'staff' ? ' AND active = 1' : '';
        $user = Db::one("SELECT * FROM {$table} WHERE id = ?{$active}", [$id]);
        if ($user) {
            unset($user['password_hash']);
        }
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
