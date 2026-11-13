<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use RuntimeException;

final class RequestService
{
    public const STATUSES = [
        'new'         => 'Neu',
        'in_review'   => 'In Prüfung',
        'offer_sent'  => 'Angebot gesendet',
        'accepted'    => 'Angenommen',
        'in_progress' => 'In Arbeit',
        'delivered'   => 'Geliefert',
        'done'        => 'Abgeschlossen',
        'declined'    => 'Abgelehnt',
        'cancelled'   => 'Storniert',
    ];

    public const OPEN_STATUSES = ['new', 'in_review', 'offer_sent', 'accepted', 'in_progress', 'delivered'];

    /** Type specific questions of the request form. Values are whitelisted on the server. */
    public const DETAILS = [
        'fivem' => [
            'framework' => ['label' => 'Framework', 'kind' => 'select', 'options' => ['ESX', 'QBCore', 'Qbox', 'Standalone', 'Weiß ich nicht']],
            'kind'      => ['label' => 'Art', 'kind' => 'select', 'options' => ['Neues Script', 'NUI / Interface', 'MLO / Map', 'Anpassung eines bestehenden Scripts', 'Fehlerbehebung', 'Sonstiges']],
            'delivery'  => ['label' => 'Auslieferung', 'kind' => 'select', 'options' => ['Open Source', 'Escrow (Tebex)', 'Egal']],
            'extras'    => ['label' => 'Extras', 'kind' => 'multi', 'options' => ['ox_lib', 'ox_inventory', 'ox_target', 'Datenbank (oxmysql)', 'Discord-Logs', 'Konfigurierbar']],
        ],
        'web' => [
            'kind'     => ['label' => 'Art', 'kind' => 'select', 'options' => ['Landingpage', 'Mehrseitige Website', 'Dashboard / Portal', 'Shop', 'Redesign', 'Sonstiges']],
            'pages'    => ['label' => 'Umfang', 'kind' => 'select', 'options' => ['1 Seite', '2–5 Seiten', '6–10 Seiten', 'Mehr als 10']],
            'features' => ['label' => 'Funktionen', 'kind' => 'multi', 'options' => ['Login / Konten', 'Kontaktformular', 'Admin-Bereich', 'Zahlungen', 'Mehrsprachig', 'Discord-Login']],
            'hosting'  => ['label' => 'Hosting & Domain', 'kind' => 'select', 'options' => ['Habe ich schon', 'Bitte mit einrichten', 'Weiß ich nicht']],
        ],
        'bot' => [
            'features' => ['label' => 'Funktionen', 'kind' => 'multi', 'options' => ['Tickets', 'Bewerbungen', 'Verify', 'Willkommen', 'Rollen', 'Moderation', 'Logs', 'Giveaways', 'FiveM-Status']],
            'size'     => ['label' => 'Servergröße', 'kind' => 'select', 'options' => ['Unter 100', '100–1.000', '1.000–10.000', 'Über 10.000']],
            'hosting'  => ['label' => 'Hosting', 'kind' => 'select', 'options' => ['Hoste ich selbst', 'Bitte 24/7 hosten', 'Weiß ich nicht']],
        ],
        'other' => [],
    ];

    public static function siteUrl(string $path = ''): string
    {
        return rtrim((string)(config('site_origins')[0] ?? ''), '/') . $path;
    }

    public static function trackingUrl(string $token): string
    {
        return self::siteUrl('/anfrage/' . $token);
    }

    /** The private link token is derived from a stored random salt, so it can be re-sent without storing it. */
    public static function tokenFor(array $request): ?string
    {
        if (empty($request['access_token_salt'])) {
            return null;
        }
        return substr(hash_hmac('sha256', 'request|' . $request['id'] . '|' . $request['access_token_salt'], (string)config('app_secret')), 0, 40);
    }

    /** Issues a new salt (invalidating old links) and returns the new token. */
    public static function rotateToken(int $requestId): string
    {
        $salt = bin2hex(random_bytes(16));
        $token = substr(hash_hmac('sha256', 'request|' . $requestId . '|' . $salt, (string)config('app_secret')), 0, 40);
        Db::exec('UPDATE requests SET access_token_salt = ?, access_token_hash = ? WHERE id = ?', [$salt, hash('sha256', $token), $requestId]);
        return $token;
    }

    /** @return array{0: array, 1: string} request row and the plain tracking token */
    public static function create(array $data, ?array $customer): array
    {
        $publicId = strtoupper(bin2hex(random_bytes(4)));

        $id = Db::insert(
            'INSERT INTO requests (public_id, customer_id, type, name, title, discord, email, budget, deadline, message, details_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $publicId, $customer['id'] ?? null, $data['type'], $data['name'], $data['title'], $data['discord'], $data['email'],
                $data['budget'], $data['deadline'], $data['message'], json_encode($data['details'], JSON_UNESCAPED_UNICODE),
            ]
        );
        $token = self::rotateToken($id);
        $request = self::find($id);

        Notifier::newRequest($request + ['details' => $data['details']]);
        if ($request['email'] !== '') {
            Mailer::send(
                $request['email'],
                'Deine Anfrage ' . $publicId . ' ist angekommen',
                Mailer::compose($request['name'], [
                    'danke für deine Anfrage „' . ($request['title'] ?: Notifier::TYPES[$request['type']]) . '“. Ich schaue sie mir an und melde mich in der Regel innerhalb von ' . Settings::get('response_time') . '.',
                    'Über diesen privaten Link siehst du jederzeit den Stand, kannst mir antworten und Angebote annehmen. Gib ihn nicht weiter.',
                ], 'Deine Anfrage', self::trackingUrl($token))
            );
        }
        return [$request, $token];
    }

    public static function find(int $id): ?array
    {
        $r = Db::one('SELECT r.*, c.name AS customer_name, c.email AS customer_email FROM requests r LEFT JOIN customers c ON c.id = r.customer_id WHERE r.id = ?', [$id]);
        return $r ? self::hydrate($r) : null;
    }

    public static function findByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{40}$/', $token)) {
            return null;
        }
        $id = Db::value('SELECT id FROM requests WHERE access_token_hash = ?', [hash('sha256', $token)]);
        return $id ? self::find((int)$id) : null;
    }

    public static function findForCustomer(int $id, int $customerId): ?array
    {
        $r = self::find($id);
        return $r && (int)$r['customer_id'] === $customerId ? $r : null;
    }

    private static function hydrate(array $r): array
    {
        $r['details'] = json_decode($r['details_json'] ?: '{}', true) ?: [];
        $r['contact_email'] = $r['email'] !== '' ? $r['email'] : (string)($r['customer_email'] ?? '');
        return $r;
    }

    /** Turns the stored detail values into label => readable text pairs. */
    public static function detailLines(array $request): array
    {
        $out = [];
        foreach (self::DETAILS[$request['type']] ?? [] as $key => $field) {
            $value = $request['details'][$key] ?? null;
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $out[$field['label']] = is_array($value) ? implode(', ', $value) : (string)$value;
        }
        return $out;
    }

    public static function messages(int $requestId, bool $withInternal): array
    {
        return Db::all(
            "SELECT m.*, CASE m.author_type WHEN 'staff' THEN s.name WHEN 'customer' THEN r.name ELSE 'System' END AS author_name
             FROM request_messages m
             JOIN requests r ON r.id = m.request_id
             LEFT JOIN staff s ON m.author_type = 'staff' AND s.id = m.author_id
             WHERE m.request_id = ?" . ($withInternal ? '' : ' AND m.internal = 0') . ' ORDER BY m.id',
            [$requestId]
        );
    }

    public static function offers(int $requestId): array
    {
        return Db::all('SELECT * FROM request_offers WHERE request_id = ? ORDER BY id DESC', [$requestId]);
    }

    public static function pendingOffer(int $requestId): ?array
    {
        return Db::one("SELECT * FROM request_offers WHERE request_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1", [$requestId]);
    }

    public static function acceptedOffer(int $requestId): ?array
    {
        return Db::one("SELECT * FROM request_offers WHERE request_id = ? AND status = 'accepted' ORDER BY id DESC LIMIT 1", [$requestId]);
    }

    private static function touch(int $requestId, ?string $unreadFor): void
    {
        $set = "updated_at = datetime('now')";
        if ($unreadFor === 'staff') {
            $set .= ', unread_staff = 1';
        } elseif ($unreadFor === 'customer') {
            $set .= ', unread_customer = 1';
        }
        Db::exec("UPDATE requests SET {$set} WHERE id = ?", [$requestId]);
    }

    public static function markRead(int $requestId, string $side): void
    {
        $col = $side === 'staff' ? 'unread_staff' : 'unread_customer';
        Db::exec("UPDATE requests SET {$col} = 0 WHERE id = ?", [$requestId]);
    }

    private static function system(int $requestId, string $text): void
    {
        Db::exec("INSERT INTO request_messages (request_id, author_type, body) VALUES (?, 'system', ?)", [$requestId, $text]);
    }

    public static function customerMessage(array $request, string $body): void
    {
        Db::exec("INSERT INTO request_messages (request_id, author_type, author_id, body) VALUES (?, 'customer', ?, ?)", [$request['id'], $request['customer_id'], $body]);
        self::touch((int)$request['id'], 'staff');
        if ($request['status'] === 'new') {
            Db::exec("UPDATE requests SET status = 'in_review' WHERE id = ? AND status = 'new'", [$request['id']]);
        }
        Notifier::event('Antwort auf Anfrage ' . $request['public_id'], $body, ['Von' => $request['name']], 'Anfragen');
    }

    public static function staffMessage(array $request, int $staffId, string $body, bool $internal): bool
    {
        Db::exec("INSERT INTO request_messages (request_id, author_type, author_id, body, internal) VALUES (?, 'staff', ?, ?, ?)", [$request['id'], $staffId, $body, $internal ? 1 : 0]);
        if ($internal) {
            return false;
        }
        self::touch((int)$request['id'], 'customer');
        if ($request['status'] === 'new') {
            Db::exec("UPDATE requests SET status = 'in_review' WHERE id = ?", [$request['id']]);
        }
        return self::notifyCustomer($request, 'Neue Nachricht zu deiner Anfrage ' . $request['public_id'], [
            'du hast eine neue Nachricht zu deiner Anfrage „' . ($request['title'] ?: Notifier::TYPES[$request['type']]) . '“:',
        ], quote: $body, action: 'Antworten');
    }

    public static function sendOffer(array $request, int $staffId, int $amountCents, string $delivery, string $description): bool
    {
        if (in_array($request['status'], ['done', 'declined', 'cancelled'], true)) {
            throw new RuntimeException('Request is closed');
        }
        Db::transaction(static function () use ($request, $staffId, $amountCents, $delivery, $description): void {
            Db::exec("UPDATE request_offers SET status = 'withdrawn', responded_at = datetime('now') WHERE request_id = ? AND status = 'pending'", [$request['id']]);
            Db::exec(
                'INSERT INTO request_offers (request_id, amount_cents, delivery, description, created_by) VALUES (?, ?, ?, ?, ?)',
                [$request['id'], $amountCents, $delivery, $description, $staffId]
            );
            Db::exec("UPDATE requests SET status = 'offer_sent' WHERE id = ?", [$request['id']]);
            self::system((int)$request['id'], 'Angebot gesendet: ' . self::priceLabel($amountCents) . ($delivery !== '' ? ' · Lieferung: ' . $delivery : ''));
        });
        self::touch((int)$request['id'], 'customer');
        return self::notifyCustomer($request, 'Dein Angebot zu ' . $request['public_id'], [
            'ich habe dir ein Angebot zu deiner Anfrage „' . ($request['title'] ?: Notifier::TYPES[$request['type']]) . '“ erstellt.',
            'Du kannst es über den Link annehmen oder ablehnen.',
        ], ['Preis' => self::priceLabel($amountCents)] + ($delivery !== '' ? ['Lieferung' => $delivery] : []), $description, 'Angebot ansehen');
    }

    public static function respondToOffer(array $request, bool $accept): void
    {
        $offer = self::pendingOffer((int)$request['id']) ?? throw new RuntimeException('No pending offer');
        Db::transaction(static function () use ($request, $offer, $accept): void {
            Db::exec("UPDATE request_offers SET status = ?, responded_at = datetime('now') WHERE id = ? AND status = 'pending'", [$accept ? 'accepted' : 'declined', $offer['id']]);
            Db::exec('UPDATE requests SET status = ? WHERE id = ?', [$accept ? 'accepted' : 'in_review', $request['id']]);
            self::system((int)$request['id'], $accept ? 'Angebot angenommen.' : 'Angebot abgelehnt.');
        });
        self::touch((int)$request['id'], 'staff');
        Notifier::event(
            ($accept ? 'Angebot angenommen: ' : 'Angebot abgelehnt: ') . $request['public_id'],
            (string)($request['title'] ?: Notifier::TYPES[$request['type']]),
            ['Kunde' => $request['name'], 'Betrag' => self::priceLabel((int)$offer['amount_cents'])],
            'Anfragen'
        );
    }

    public static function setStatus(array $request, string $status, ?int $progress): bool
    {
        if (!isset(self::STATUSES[$status])) {
            throw new RuntimeException('Unknown status');
        }
        $progress = $status === 'done' ? 100 : ($progress === null ? (int)$request['progress'] : max(0, min(100, $progress)));
        $changed = $status !== $request['status'];
        if (!$changed && $progress === (int)$request['progress']) {
            return false;
        }
        Db::exec('UPDATE requests SET status = ?, progress = ? WHERE id = ?', [$status, $progress, $request['id']]);
        $text = 'Status: ' . self::STATUSES[$status] . ($status === 'in_progress' ? ' (' . $progress . ' %)' : '');
        self::system((int)$request['id'], $text);
        self::touch((int)$request['id'], 'customer');

        if (!$changed || !in_array($status, ['in_progress', 'delivered', 'done', 'declined', 'cancelled'], true)) {
            return false;
        }
        $lines = [
            'in_progress' => 'ich habe mit deinem Projekt begonnen. Den Fortschritt siehst du jederzeit über den Link.',
            'delivered'   => 'dein Projekt ist fertig und wurde geliefert. Schau es dir an und sag mir Bescheid, falls etwas nicht passt.',
            'done'        => 'dein Auftrag ist abgeschlossen. Danke für dein Vertrauen!',
            'declined'    => 'leider kann ich deine Anfrage diesmal nicht übernehmen. Danke für dein Interesse.',
            'cancelled'   => 'deine Anfrage wurde storniert.',
        ];
        return self::notifyCustomer($request, self::STATUSES[$status] . ': ' . $request['public_id'], [$lines[$status]]);
    }

    public static function priceLabel(int $cents): string
    {
        return $cents === 0 ? 'Kostenlos' : money($cents);
    }

    /** Sends a mail with a link to the request: the account page for customers, a fresh private link for guests. */
    private static function notifyCustomer(array $request, string $subject, array $lines, array $facts = [], string $quote = '', string $action = 'Zur Anfrage'): bool
    {
        $to = $request['contact_email'];
        if ($to === '' || !Mailer::enabled()) {
            return false;
        }
        $url = $request['customer_id']
            ? self::siteUrl('/konto/anfragen/' . $request['id'])
            : self::trackingUrl(self::tokenFor($request) ?? self::rotateToken((int)$request['id']));
        return Mailer::send($to, $subject, Mailer::compose($request['name'], $lines, $action, $url, $facts, $quote));
    }
}
