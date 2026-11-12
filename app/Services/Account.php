<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Db;
use App\Core\Security;

/** Account security shared by customers and staff: 2FA, recovery codes, tokens, sessions, activity. */
final class Account
{
    public const EVENTS = [
        'login'            => 'Anmeldung',
        'login_failed'     => 'Fehlgeschlagene Anmeldung',
        'twofa_failed'     => 'Falscher Bestätigungscode',
        'recovery_used'    => 'Anmeldung mit Wiederherstellungscode',
        'password_changed' => 'Passwort geändert',
        'password_reset'   => 'Passwort zurückgesetzt',
        'email_changed'    => 'E-Mail-Adresse geändert',
        'twofa_enabled'    => 'Zwei-Faktor-Anmeldung aktiviert',
        'twofa_disabled'   => 'Zwei-Faktor-Anmeldung deaktiviert',
        'twofa_reset'      => 'Zwei-Faktor-Anmeldung vom Admin zurückgesetzt',
        'codes_renewed'    => 'Neue Wiederherstellungscodes erzeugt',
        'sessions_revoked' => 'Auf anderen Geräten abgemeldet',
    ];

    private const TTL = ['reset' => 3600, 'invite' => 604800, 'email' => 86400];

    public static function type(): string
    {
        return APP_CONTEXT === 'staff' ? 'staff' : 'customer';
    }

    private static function table(string $type): string
    {
        return $type === 'staff' ? 'staff' : 'customers';
    }

    public static function find(string $type, int $id): ?array
    {
        return Db::one('SELECT * FROM ' . self::table($type) . ' WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $type, string $email): ?array
    {
        $active = $type === 'staff' ? ' AND active = 1' : '';
        return Db::one('SELECT * FROM ' . self::table($type) . ' WHERE email = ?' . $active, [$email]);
    }

    public static function emailTaken(string $type, string $email, int $exceptId = 0): bool
    {
        return (bool)Db::value('SELECT 1 FROM ' . self::table($type) . ' WHERE email = ? AND id <> ?', [$email, $exceptId]);
    }

    /* ---------- passwords & sessions ---------- */

    public static function setPassword(string $type, int $id, string $password): void
    {
        Db::exec(
            'UPDATE ' . self::table($type) . " SET password_hash = ?, password_changed_at = datetime('now'), session_version = session_version + 1 WHERE id = ?",
            [\App\Core\Auth::hash($password), $id]
        );
    }

    /** Invalidates every session of the account (the caller re-logs the current one if wanted). */
    public static function revokeSessions(string $type, int $id): void
    {
        Db::exec('UPDATE ' . self::table($type) . ' SET session_version = session_version + 1 WHERE id = ?', [$id]);
    }

    /* ---------- two-factor ---------- */

    public static function twoFactorOn(array $account): bool
    {
        return !empty($account['totp_enabled_at']) && !empty($account['totp_secret']);
    }

    /** @return string[] plain recovery codes, shown once */
    public static function enableTwoFactor(string $type, int $id, string $secret, int $step): array
    {
        Db::exec(
            'UPDATE ' . self::table($type) . " SET totp_secret = ?, totp_enabled_at = datetime('now'), totp_last_step = ? WHERE id = ?",
            [Crypto::encrypt($secret, 'totp'), $step, $id]
        );
        self::log($type, $id, 'twofa_enabled');
        return self::newRecoveryCodes($type, $id);
    }

    public static function disableTwoFactor(string $type, int $id, string $event = 'twofa_disabled'): void
    {
        Db::exec('UPDATE ' . self::table($type) . ' SET totp_secret = NULL, totp_enabled_at = NULL, totp_last_step = 0 WHERE id = ?', [$id]);
        Db::exec('DELETE FROM recovery_codes WHERE account_type = ? AND account_id = ?', [$type, $id]);
        self::log($type, $id, $event);
    }

    /** Checks an authenticator code or a recovery code. Returns 'totp', 'recovery' or null. */
    public static function verifySecondFactor(string $type, array $account, string $input): ?string
    {
        $secret = Crypto::decrypt((string)$account['totp_secret'], 'totp');
        if ($secret === null) {
            return null;
        }
        $digits = preg_replace('/\s+/', '', $input) ?? '';
        if (preg_match('/^\d{6}$/', $digits)) {
            $step = Totp::verify($secret, $digits, (int)$account['totp_last_step']);
            if ($step === null) {
                return null;
            }
            $updated = Db::exec('UPDATE ' . self::table($type) . ' SET totp_last_step = ? WHERE id = ? AND totp_last_step < ?', [$step, $account['id'], $step]);
            return $updated->rowCount() === 1 ? 'totp' : null;
        }
        $code = strtolower((string)preg_replace('/[^a-z0-9]/i', '', $input));
        if (strlen($code) !== 10) {
            return null;
        }
        $used = Db::exec(
            "UPDATE recovery_codes SET used_at = datetime('now') WHERE account_type = ? AND account_id = ? AND code_hash = ? AND used_at IS NULL",
            [$type, $account['id'], hash('sha256', 'clyve-recovery|' . $code)]
        );
        return $used->rowCount() === 1 ? 'recovery' : null;
    }

    /** @return string[] */
    public static function newRecoveryCodes(string $type, int $id): array
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $codes = [];
        Db::transaction(static function () use ($type, $id, $alphabet, &$codes): void {
            Db::exec('DELETE FROM recovery_codes WHERE account_type = ? AND account_id = ?', [$type, $id]);
            for ($i = 0; $i < 10; $i++) {
                $raw = '';
                for ($j = 0; $j < 10; $j++) {
                    $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }
                $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
                Db::exec('INSERT INTO recovery_codes (account_type, account_id, code_hash) VALUES (?, ?, ?)', [$type, $id, hash('sha256', 'clyve-recovery|' . $raw)]);
            }
        });
        return $codes;
    }

    public static function recoveryLeft(string $type, int $id): int
    {
        return (int)Db::value('SELECT COUNT(*) FROM recovery_codes WHERE account_type = ? AND account_id = ? AND used_at IS NULL', [$type, $id]);
    }

    /* ---------- one-time tokens (reset, invite, e-mail change) ---------- */

    public static function createToken(string $purpose, string $type, int $id, string $data = '', ?int $ttl = null): string
    {
        $token = bin2hex(random_bytes(20));
        Db::exec('DELETE FROM auth_tokens WHERE account_type = ? AND account_id = ? AND purpose = ? AND used_at IS NULL', [$type, $id, $purpose]);
        Db::exec(
            "INSERT INTO auth_tokens (purpose, account_type, account_id, token_hash, data, expires_at) VALUES (?, ?, ?, ?, ?, datetime('now', ?))",
            [$purpose, $type, $id, hash('sha256', $token), $data, '+' . ($ttl ?? self::TTL[$purpose]) . ' seconds']
        );
        if (random_int(1, 20) === 1) {
            Db::exec("DELETE FROM auth_tokens WHERE expires_at < datetime('now', '-7 days')");
        }
        return $token;
    }

    /** @param string[] $purposes */
    public static function findToken(array $purposes, string $type, string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{40}$/', $token)) {
            return null;
        }
        $in = implode(',', array_fill(0, count($purposes), '?'));
        return Db::one(
            "SELECT * FROM auth_tokens WHERE token_hash = ? AND account_type = ? AND purpose IN ({$in}) AND used_at IS NULL AND expires_at > datetime('now')",
            [hash('sha256', $token), $type, ...$purposes]
        );
    }

    public static function consumeToken(int $tokenId): bool
    {
        return Db::exec("UPDATE auth_tokens SET used_at = datetime('now') WHERE id = ? AND used_at IS NULL", [$tokenId])->rowCount() === 1;
    }

    public static function pendingEmail(string $type, int $id): ?array
    {
        return Db::one(
            "SELECT * FROM auth_tokens WHERE account_type = ? AND account_id = ? AND purpose = 'email' AND used_at IS NULL AND expires_at > datetime('now') ORDER BY id DESC LIMIT 1",
            [$type, $id]
        );
    }

    public static function cancelEmail(string $type, int $id): void
    {
        Db::exec("DELETE FROM auth_tokens WHERE account_type = ? AND account_id = ? AND purpose = 'email' AND used_at IS NULL", [$type, $id]);
    }

    public static function changeEmail(string $type, int $id, string $email): void
    {
        Db::exec('UPDATE ' . self::table($type) . ' SET email = ? WHERE id = ?', [$email, $id]);
        self::cancelEmail($type, $id);
        self::log($type, $id, 'email_changed');
    }

    /* ---------- activity ---------- */

    public static function log(string $type, int $id, string $event): void
    {
        Db::exec(
            'INSERT INTO account_events (account_type, account_id, event, ip_hint, agent) VALUES (?, ?, ?, ?, ?)',
            [$type, $id, $event, self::ipHint(Security::clientIp()), self::agent((string)($_SERVER['HTTP_USER_AGENT'] ?? ''))]
        );
        if (random_int(1, 50) === 1) {
            Db::exec("DELETE FROM account_events WHERE created_at < datetime('now', '-180 days')");
        }
    }

    public static function events(string $type, int $id, int $limit = 12): array
    {
        return Db::all('SELECT * FROM account_events WHERE account_type = ? AND account_id = ? ORDER BY id DESC LIMIT ' . max(1, min(100, $limit)), [$type, $id]);
    }

    public static function lastLogin(string $type, int $id): ?string
    {
        $v = Db::value("SELECT created_at FROM account_events WHERE account_type = ? AND account_id = ? AND event IN ('login','recovery_used') ORDER BY id DESC LIMIT 1", [$type, $id]);
        return $v === false ? null : (string)$v;
    }

    /** Shortened IP (IPv4 /24, IPv6 /48) – enough to recognise a location, not a person. */
    public static function ipHint(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.x', $ip) ?? '';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            return $packed === false ? '' : implode(':', array_slice(str_split(bin2hex($packed), 4), 0, 3)) . '::x';
        }
        return '';
    }

    public static function agent(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg/')     => 'Edge',
            str_contains($ua, 'OPR/')     => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/')  => 'Chrome',
            str_contains($ua, 'Safari/')  => 'Safari',
            $ua === ''                    => '',
            default                       => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'Windows')  => 'Windows',
            str_contains($ua, 'Android')  => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Linux')    => 'Linux',
            default                       => '',
        };
        return trim($browser . ($os !== '' ? ' · ' . $os : ''), ' ·');
    }

    /* ---------- notifications ---------- */

    public static function notify(array $account, string $subject, array $lines): bool
    {
        if (!Settings::on('security_mails')) {
            return false;
        }
        $lines[] = 'Warst du das nicht? Dann setze bitte sofort dein Passwort zurück und melde dich bei uns.';
        return Mailer::send((string)$account['email'], $subject, Mailer::compose(explode(' ', (string)$account['name'])[0], $lines));
    }

    public static function baseUrl(string $type): string
    {
        $origins = (array)config($type === 'staff' ? 'staff_origins' : 'site_origins', []);
        return rtrim((string)($origins[0] ?? ''), '/');
    }
}
