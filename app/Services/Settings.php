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
        'tickets_enabled'         => '1',
        'shop_enabled'            => '1',
        'availability_label'      => 'Offen für neue Aufträge',
        'response_time'           => '< 24h',
        'announcement'            => '',
    ];

    public const TOGGLES = [
        'requests_open', 'type_fivem', 'type_web', 'type_bot', 'type_other',
        'registration_open', 'tickets_enabled', 'shop_enabled',
    ];

    private static ?array $cache = null;

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
