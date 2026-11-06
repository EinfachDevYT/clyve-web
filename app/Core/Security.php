<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    }

    public static function sendHeaders(): void
    {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
            . "font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; object-src 'none'");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function startSession(string $context): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = self::isHttps();
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.save_path', self::sessionPath($context));
        ini_set('session.gc_maxlifetime', (string)config('session_idle.' . $context, 3600));

        // Host-only cookie: the staff subdomain never shares a session with the main site.
        session_name(($https ? '__Host-' : '') . ($context === 'staff' ? 'ed_staff' : 'ed_sid'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => $context === 'staff' ? 'Strict' : 'Lax',
        ]);
        session_start();

        $idle = (int)config('session_idle.' . $context, 3600);
        $last = (int)($_SESSION['_last'] ?? 0);
        if ($last && time() - $last > $idle) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_last'] = time();
    }

    private static function sessionPath(string $context): string
    {
        $dir = STORAGE_PATH . '/sessions/' . $context;
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        return $dir;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function rotateCsrf(): void
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function checkCsrf(mixed $token): bool
    {
        $expected = $_SESSION['_csrf'] ?? '';
        return is_string($token) && is_string($expected) && $expected !== '' && hash_equals($expected, $token);
    }

    public static function checkOrigin(array $allowed): bool
    {
        $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin === '' && !empty($_SERVER['HTTP_REFERER'])) {
            $p = parse_url((string)$_SERVER['HTTP_REFERER']);
            if (!empty($p['scheme']) && !empty($p['host'])) {
                $origin = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
            }
        }
        return $origin !== '' && in_array($origin, $allowed, true);
    }

    public static function guardPost(): void
    {
        $origins = (array)config(APP_CONTEXT === 'staff' ? 'staff_origins' : 'site_origins', []);
        if (self::checkOrigin($origins) && self::checkCsrf($_POST['csrf'] ?? null)) {
            return;
        }
        if (wants_json()) {
            json_out(403, ['ok' => false, 'message' => 'Sitzung abgelaufen. Bitte lade die Seite neu.']);
        }
        abort(403);
    }

    public static function clientIp(): string
    {
        $header = (string)config('trusted_ip_header', '');
        if ($header !== '' && !empty($_SERVER[$header])) {
            $ip = trim(explode(',', (string)$_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function hmac(string $value): string
    {
        return hash_hmac('sha256', $value, (string)config('app_secret'));
    }

    public static function ipAllowed(array $allowlist): bool
    {
        return $allowlist === [] || in_array(self::clientIp(), $allowlist, true);
    }
}
