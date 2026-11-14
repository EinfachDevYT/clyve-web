<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/** Switches and texts the admin can change in the staff area. */
final class Settings
{
    public const DEFAULTS = [
        'requests_open'           => '1',
        'requests_closed_message' => 'Gerade nehme ich keine neuen Aufträge an. Schau gern bald wieder vorbei oder schreib mir auf Discord.',
        'type_fivem'              => '1',
        'type_web'                => '1',
        'type_bot'                => '1',
        'type_other'              => '1',
        'registration_open'       => '1',
        'registration_verify'     => '1',
        'tickets_enabled'         => '1',
        'shop_enabled'            => '1',
        'availability_label'      => 'Offen für neue Aufträge',
        'response_time'           => '< 24h',
        'announcement'            => '',
        'hosting_auto_suspend'    => '1',
        'hosting_grace_days'      => '3',
        'hosting_reminder_days'   => '14,3',
        'hosting_api_enabled'     => '1',
        'hosting_check_interval'  => '300',
        'hosting_notify_discord'  => '1',
        'hosting_suspend_message' => 'Dieser Bot ist gerade pausiert. Bitte wende dich an den Support.',
        'hosting_customer_view'   => '1',
        'staff_require_2fa'       => '0',
        'security_mails'          => '1',
        'error_alerts'            => '1',
    ];

    public const TOGGLES = [
        'requests_open', 'type_fivem', 'type_web', 'type_bot', 'type_other',
        'registration_open', 'registration_verify', 'tickets_enabled', 'shop_enabled',
    ];

    public const HOSTING_TOGGLES = ['hosting_auto_suspend', 'hosting_api_enabled', 'hosting_notify_discord', 'hosting_customer_view'];

    /** Config keys the admin can override in the staff area. */
    private const OVERRIDABLE = ['business', 'smtp', 'tebex', 'discord_webhook', 'devlog_webhook', 'devlog_role', 'mail_to'];
    /** Stored encrypted, never shown again in the UI. */
    public const SECRET_KEYS = ['smtp.password', 'discord_webhook', 'devlog_webhook'];

    private static ?array $cache = null;
    private static ?array $overrides = null;
    private static bool $loading = false;

    public static function isOverridable(string $key): bool
    {
        foreach (self::OVERRIDABLE as $root) {
            if ($key === $root || str_starts_with($key, $root . '.')) {
                return true;
            }
        }
        return false;
    }

    /** @return array<string, mixed> e.g. ['business.name' => 'Clyve.dev', 'smtp.port' => 587] */
    public static function overrides(): array
    {
        if (self::$overrides !== null) {
            return self::$overrides;
        }
        if (self::$loading) {
            return [];
        }
        self::$loading = true;
        $out = [];
        try {
            foreach (Db::all("SELECT key, value FROM settings WHERE key LIKE 'cfg.%'") as $row) {
                $name = substr($row['key'], 4);
                $value = json_decode($row['value'], true);
                if (in_array($name, self::SECRET_KEYS, true)) {
                    $value = is_string($value) ? self::decrypt($value) : null;
                    if ($value === null) {
                        continue;
                    }
                }
                $out[$name] = $value;
            }
        } catch (\Throwable $e) {
            error_log('[settings] ' . $e->getMessage());
            $out = [];
        } finally {
            self::$loading = false;
        }
        return self::$overrides = $out;
    }

    /** Saves overrides. null removes the override (config.php applies again). */
    public static function setConfig(array $values): void
    {
        Db::transaction(static function () use ($values): void {
            foreach ($values as $key => $value) {
                if (!self::isOverridable($key)) {
                    continue;
                }
                if ($value === null) {
                    Db::exec('DELETE FROM settings WHERE key = ?', ['cfg.' . $key]);
                    continue;
                }
                $stored = in_array($key, self::SECRET_KEYS, true) ? self::encrypt((string)$value) : $value;
                Db::exec(
                    "INSERT INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))
                     ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at",
                    ['cfg.' . $key, json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
                );
            }
        });
        self::$overrides = null;
    }

    public static function hasSecret(string $key): bool
    {
        return (string)config($key, '') !== '';
    }

    private static function encrypt(string $plain): string
    {
        return \App\Core\Crypto::encrypt($plain, 'settings');
    }

    private static function decrypt(string $stored): ?string
    {
        return \App\Core\Crypto::decrypt($stored, 'settings');
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            $stored = [];
            foreach (Db::all('SELECT key, value FROM settings') as $row) {
                $stored[$row['key']] = $row['value'];
            }
            self::$cache = array_intersect_key($stored, self::DEFAULTS) + self::DEFAULTS;
        }
        return self::$cache;
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? '';
    }

    public static function on(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function set(array $values): void
    {
        Db::transaction(static function () use ($values): void {
            foreach ($values as $key => $value) {
                if (!array_key_exists($key, self::DEFAULTS)) {
                    continue;
                }
                Db::exec(
                    "INSERT INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))
                     ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at",
                    [$key, (string)$value]
                );
            }
        });
        self::$cache = null;
    }

    /** Request types that are currently accepted. */
    public static function openTypes(): array
    {
        if (!self::on('requests_open')) {
            return [];
        }
        return array_values(array_filter(array_keys(Notifier::TYPES), static fn(string $t) => self::on('type_' . $t)));
    }
}
