<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use DateTimeImmutable;

/**
 * Hosted services (e.g. Discord bots): term, automatic suspension after expiry,
 * manual locks and license keys the bot checks against /api/v1/license.
 */
final class Hosting
{
    public const STATES = [
        'active'    => ['Aktiv', 'ok'],
        'grace'     => ['Kulanzzeit', 'warn'],
        'overdue'   => ['Überfällig', 'warn'],
        'expired'   => ['Abgelaufen', 'bad'],
        'suspended' => ['Gesperrt', 'bad'],
        'cancelled' => ['Gekündigt', 'muted'],
    ];

    public const TERMS = [0 => 'Unbefristet', 1 => '1 Monat', 3 => '3 Monate', 6 => '6 Monate', 12 => '12 Monate', 24 => '24 Monate'];

    private const KEY_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public static function today(): DateTimeImmutable
    {
        return new DateTimeImmutable('today');
    }

    public static function graceDays(): int
    {
        return max(0, min(60, (int)Settings::get('hosting_grace_days')));
    }

    public static function find(int $id, ?int $customerId = null): ?array
    {
        $sql = 'SELECT s.*, c.name AS customer_name, c.email AS customer_email, p.name AS plan_name
                FROM services s JOIN customers c ON c.id = s.customer_id LEFT JOIN service_plans p ON p.id = s.plan_id WHERE s.id = ?';
        $params = [$id];
        if ($customerId !== null) {
            $sql .= ' AND s.customer_id = ?';
            $params[] = $customerId;
        }
        return Db::one($sql, $params);
    }

    /** Days until expiry (negative = overdue), null = no end date. */
    public static function daysLeft(array $s): ?int
    {
        if (!$s['expires_on']) {
            return null;
        }
        $diff = self::today()->diff(new DateTimeImmutable($s['expires_on']));
        return $diff->invert ? -$diff->days : $diff->days;
    }

    public static function graceUntil(array $s): ?string
    {
        return $s['expires_on'] ? (new DateTimeImmutable($s['expires_on']))->modify('+' . self::graceDays() . ' days')->format('Y-m-d') : null;
    }

    /** What the service effectively is right now, independent of whether the cron already ran. */
    public static function state(array $s): string
    {
        if ($s['status'] !== 'active') {
            return $s['status'];
        }
        $left = self::daysLeft($s);
        if ($left === null || $left >= 0) {
            return 'active';
        }
        if (-$left <= self::graceDays()) {
            return 'grace';
        }
        return Settings::on('hosting_auto_suspend') ? 'expired' : 'overdue';
    }

    public static function runs(array $s): bool
    {
        return in_array(self::state($s), ['active', 'grace', 'overdue'], true);
    }

    public static function badge(array $s): string
    {
        [$label, $tone] = self::STATES[self::state($s)];
        return '<span class="badge badge-' . $tone . '">' . e($label) . '</span>';
    }

    /** Share of the current term already used, for the progress bar. */
    public static function termProgress(array $s): ?int
    {
        if (!$s['expires_on'] || (int)$s['term_months'] <= 0) {
            return null;
        }
        $end = new DateTimeImmutable($s['expires_on']);
        $start = $end->modify('-' . (int)$s['term_months'] . ' months');
        $total = max(1, $start->diff($end)->days);
        $done = self::today() < $start ? 0 : $start->diff(min(self::today(), $end))->days;
        return (int)round($done / $total * 100);
    }

    public static function priceLabel(array $s): string
    {
        if ((int)$s['price_cents'] <= 0) {
            return 'Kostenlos';
        }
        $months = (int)$s['term_months'];
        return money((int)$s['price_cents']) . ($months > 0 ? ' / ' . ($months === 1 ? 'Monat' : $months . ' Monate') : ' einmalig');
    }

    public static function termLabel(int $months): string
    {
        return self::TERMS[$months] ?? $months . ' Monate';
    }

    /* ---------- license keys ---------- */

    public static function newKey(): string
    {
        $groups = [];
        for ($g = 0; $g < 4; $g++) {
            $part = '';
            for ($i = 0; $i < 5; $i++) {
                $part .= self::KEY_ALPHABET[random_int(0, 31)];
            }
            $groups[] = $part;
        }
        return 'CLY-' . implode('-', $groups);
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', 'clyve-license|' . strtoupper(trim($key)));
    }

    public static function hint(string $key): string
    {
        return substr($key, 0, 9) . '…' . substr($key, -5);
    }

    /** Creates a new key, stores only its hash and returns the plain key once. */
    public static function rotateKey(int $serviceId, string $actor): string
    {
        $key = self::newKey();
        Db::exec("UPDATE services SET license_hash = ?, license_hint = ?, updated_at = datetime('now') WHERE id = ?", [self::hashKey($key), self::hint($key), $serviceId]);
        self::log($serviceId, 'key', 'Neuer Lizenzschlüssel erzeugt, der alte ist ungültig.', $actor);
        return $key;
    }

    public static function findByKey(string $key): ?array
    {
        if (!preg_match('/^CLY(-[23456789A-HJ-NP-Z]{5}){4}$/', strtoupper(trim($key)))) {
            return null;
        }
        return Db::one('SELECT * FROM services WHERE license_hash = ?', [self::hashKey($key)]);
    }

    /* ---------- lifecycle ---------- */

    public static function create(array $data, string $actor): array
    {
        $id = Db::insert(
            'INSERT INTO services (customer_id, plan_id, request_id, name, price_cents, term_months, starts_on, expires_on, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$data['customer_id'], $data['plan_id'], $data['request_id'], $data['name'], $data['price_cents'], $data['term_months'],
             $data['starts_on'], $data['expires_on'], $data['notes']]
        );
        self::log($id, 'created', 'Dienst angelegt' . ($data['expires_on'] ? ', läuft bis ' . date_de($data['expires_on']) : ', unbefristet') . '.', $actor);
        return ['id' => $id, 'key' => self::rotateKey($id, $actor)];
    }

    /** Extends from the current end date, or from today if it already ran out. */
    public static function renew(array $s, int $months, string $actor): string
    {
        $base = $s['expires_on'] && new DateTimeImmutable($s['expires_on']) >= self::today()
            ? new DateTimeImmutable($s['expires_on'])
            : self::today();
        $until = self::addMonths($base, $months)->format('Y-m-d');
        $unlock = $s['status'] === 'suspended' && $s['suspended_by'] === 'auto';
        Db::exec(
            "UPDATE services SET expires_on = ?, reminded = '', updated_at = datetime('now')" . ($unlock ? ", status = 'active', suspended_by = '', suspended_reason = '', suspended_at = NULL" : '') . ' WHERE id = ?',
            [$until, $s['id']]
        );
        self::log((int)$s['id'], 'renewed', 'Um ' . self::termLabel($months) . ' verlängert bis ' . date_de($until) . ($unlock ? ', Sperre aufgehoben' : '') . '.', $actor);
        return $until;
    }

    public static function suspend(array $s, string $reason, string $by, string $actor): void
    {
        Db::exec(
            "UPDATE services SET status = 'suspended', suspended_by = ?, suspended_reason = ?, suspended_at = datetime('now'), updated_at = datetime('now') WHERE id = ?",
            [$by, $reason, $s['id']]
        );
        self::log((int)$s['id'], 'suspended', 'Gesperrt' . ($reason !== '' ? ': ' . $reason : '.'), $actor);
    }

    public static function unsuspend(array $s, string $actor): void
    {
        Db::exec("UPDATE services SET status = 'active', suspended_by = '', suspended_reason = '', suspended_at = NULL, updated_at = datetime('now') WHERE id = ?", [$s['id']]);
        self::log((int)$s['id'], 'unsuspended', 'Sperre aufgehoben.', $actor);
    }

    public static function cancel(array $s, string $actor): void
    {
        Db::exec("UPDATE services SET status = 'cancelled', updated_at = datetime('now') WHERE id = ?", [$s['id']]);
        self::log((int)$s['id'], 'cancelled', 'Dienst gekündigt, der Lizenzschlüssel ist ungültig.', $actor);
    }

    public static function reactivate(array $s, string $actor): void
    {
        Db::exec("UPDATE services SET status = 'active', suspended_by = '', suspended_reason = '', suspended_at = NULL, updated_at = datetime('now') WHERE id = ?", [$s['id']]);
        self::log((int)$s['id'], 'reactivated', 'Dienst reaktiviert.', $actor);
    }

    public static function log(int $serviceId, string $action, string $detail, string $actor, bool $visible = true): void
    {
        Db::exec('INSERT INTO service_events (service_id, action, detail, actor, visible) VALUES (?, ?, ?, ?, ?)', [$serviceId, $action, $detail, $actor, $visible ? 1 : 0]);
    }

    public static function events(int $serviceId, bool $all): array
    {
        return Db::all('SELECT * FROM service_events WHERE service_id = ?' . ($all ? '' : ' AND visible = 1') . ' ORDER BY id DESC LIMIT 50', [$serviceId]);
    }

    public static function addMonths(DateTimeImmutable $d, int $months): DateTimeImmutable
    {
        $target = $d->modify('first day of +' . $months . ' months');
        return $target->setDate((int)$target->format('Y'), (int)$target->format('m'), min((int)$d->format('d'), (int)$target->format('t')));
    }

    /* ---------- automation ---------- */

    /** @return int[] reminder thresholds in days, largest first */
    public static function reminderDays(): array
    {
        $days = array_filter(array_map('intval', explode(',', Settings::get('hosting_reminder_days'))), static fn(int $d) => $d > 0 && $d <= 60);
        $days = array_values(array_unique($days));
        rsort($days);
        return $days;
    }

    /** Runs at most every few minutes, from cron or lazily from requests. */
    public static function tick(bool $force = false): ?array
    {
        $lock = STORAGE_PATH . '/hosting-tick.lock';
        if (!$force && is_file($lock) && filemtime($lock) > time() - 600) {
            return null;
        }
        $fh = fopen($lock, 'c');
        if ($fh === false || !flock($fh, LOCK_EX | LOCK_NB)) {
            return null;
        }
        try {
            touch($lock);
            return self::process();
        } catch (\Throwable $e) {
            error_log('[hosting] ' . $e->getMessage());
            return null;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    private static function process(): array
    {
        $result = ['reminded' => 0, 'suspended' => 0];
        $thresholds = self::reminderDays();
        $rows = Db::all(
            "SELECT s.*, c.name AS customer_name, c.email AS customer_email FROM services s JOIN customers c ON c.id = s.customer_id
             WHERE s.status = 'active' AND s.expires_on IS NOT NULL"
        );
        foreach ($rows as $s) {
            $left = self::daysLeft($s);
            if (self::state($s) === 'expired') {
                self::suspend($s, 'Laufzeit abgelaufen', 'auto', 'system');
                self::mail($s, 'ist pausiert', [
                    'die Laufzeit von „' . $s['name'] . '“ ist am ' . date_de($s['expires_on']) . ' abgelaufen, deshalb ist der Dienst jetzt pausiert.',
                    'Sobald du verlängerst, läuft er automatisch wieder an.',
                ]);
                if (Settings::on('hosting_notify_discord')) {
                    Notifier::event('Dienst automatisch gesperrt', $s['name'], ['Kunde' => $s['customer_name'], 'Abgelaufen' => date_de($s['expires_on'])], 'Hosting');
                }
                $result['suspended']++;
                continue;
            }
            if ($left === null || $left < 0) {
                continue;
            }
            $due = null;
            foreach ($thresholds as $d) {
                if ($left <= $d) {
                    $due = $d;
                }
            }
            $marker = $s['expires_on'] . ':' . $due;
            if ($due === null || $s['reminded'] === $marker) {
                continue;
            }
            Db::exec('UPDATE services SET reminded = ? WHERE id = ?', [$marker, $s['id']]);
            $sent = self::mail($s, $left === 0 ? 'läuft heute ab' : 'läuft in ' . $left . ($left === 1 ? ' Tag' : ' Tagen') . ' ab', [
                'kurze Erinnerung: „' . $s['name'] . '“ läuft am ' . date_de($s['expires_on']) . ' ab.',
                self::graceDays() > 0
                    ? 'Danach läuft er noch ' . self::graceDays() . ' Tage weiter, bevor er pausiert wird. Melde dich gern, wenn du verlängern möchtest.'
                    : 'Danach wird er pausiert. Melde dich gern, wenn du verlängern möchtest.',
            ]);
            self::log((int)$s['id'], 'reminder', 'Erinnerung: läuft in ' . $left . ' Tagen ab' . ($sent ? ' (E-Mail gesendet).' : ' (kein E-Mail-Versand eingerichtet).'), 'system', false);
            $result['reminded']++;
        }
        return $result;
    }

    private static function mail(array $s, string $subjectTail, array $lines): bool
    {
        $base = rtrim((string)(((array)config('site_origins', []))[0] ?? ''), '/');
        return Mailer::send(
            (string)$s['customer_email'],
            $s['name'] . ' ' . $subjectTail,
            Mailer::compose(explode(' ', (string)$s['customer_name'])[0], $lines, Settings::on('hosting_customer_view') ? 'Zum Dienst' : null, Settings::on('hosting_customer_view') && $base !== '' ? $base . '/konto/dienste/' . (int)$s['id'] : null)
        );
    }

    /* ---------- API ---------- */

    /** Response for a bot's license check. */
    public static function check(array $s, string $version): array
    {
        Db::exec(
            "UPDATE services SET last_check_at = datetime('now'), last_version = CASE WHEN ? = '' THEN last_version ELSE ? END
             WHERE id = ? AND (last_check_at IS NULL OR last_check_at < datetime('now', '-60 seconds') OR (? <> '' AND last_version <> ?))",
            [$version, $version, $s['id'], $version, $version]
        );
        return self::payload($s);
    }

    public static function payload(array $s): array
    {
        $state = self::state($s);
        $valid = in_array($state, ['active', 'grace', 'overdue'], true);
        $message = match ($state) {
            'grace', 'overdue' => 'Die Laufzeit ist abgelaufen. Bitte verlängern.',
            'expired'   => 'Die Laufzeit ist abgelaufen.',
            'suspended' => $s['suspended_by'] === 'staff' && $s['suspended_reason'] !== '' ? $s['suspended_reason'] : Settings::get('hosting_suspend_message'),
            'cancelled' => 'Dieser Dienst wurde gekündigt.',
            default     => '',
        };
        return [
            'valid'          => $valid,
            'status'         => $state,
            'service'        => $s['name'],
            'expires_at'     => $s['expires_on'],
            'grace_until'    => $state === 'grace' ? self::graceUntil($s) : null,
            'days_left'      => self::daysLeft($s),
            'message'        => $message,
            'check_interval' => self::checkInterval(),
        ];
    }

    public static function checkInterval(): int
    {
        return max(60, min(86400, (int)Settings::get('hosting_check_interval')));
    }

    public static function apiUrl(): string
    {
        $base = rtrim((string)(((array)config('site_origins', []))[0] ?? 'https://clyve.dev'), '/');
        return $base . '/api/v1/license';
    }
}
