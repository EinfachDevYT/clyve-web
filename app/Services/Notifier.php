<?php
declare(strict_types=1);

namespace App\Services;

/** Notifications to the owner: Discord webhook and, if configured, an e-mail to mail_to. */
final class Notifier
{
    public const TYPES = [
        'fivem' => 'FiveM Script',
        'web'   => 'Website',
        'bot'   => 'Discord Bot',
        'other' => 'Sonstiges',
    ];

    public static function newRequest(array $r): void
    {
        $fields = [
            'Art'     => self::TYPES[$r['type']],
            'Budget'  => $r['budget'] ?: '–',
            'Termin'  => $r['deadline'] ?: '–',
            'Discord' => $r['discord'] ?: '–',
            'E-Mail'  => $r['email'] ?: '–',
        ] + RequestService::detailLines($r);

        $title = 'Neue Anfrage: ' . ($r['title'] !== '' ? $r['title'] : self::TYPES[$r['type']]) . ' — ' . $r['name'];
        self::event($title, $r['message'], $fields, 'Anfrage ' . $r['public_id']);

        $owner = (string)config('mail_to', '');
        if ($owner !== '') {
            $lines = [];
            foreach ($fields as $k => $v) {
                $lines[] = $k . ': ' . $v;
            }
            Mailer::send($owner, $title, implode("\n", $lines) . "\n\n" . $r['message'] . "\n\n" . RequestService::siteUrl(), $r['email'] ?: null);
        }
    }

    public static function newTicket(array $ticket, array $customer, string $body): void
    {
        self::event('Ticket #' . $ticket['id'] . ': ' . $ticket['subject'], $body, ['Kunde' => $customer['name'], 'E-Mail' => $customer['email']], 'Support');
    }

    private static function escape(string $s): string
    {
        $s = (string)preg_replace('/([\\\\`*_~|>#\-\[\]()])/', '\\\\$1', $s);
        return str_replace('@', "@\u{200B}", $s);
    }

    public static function event(string $title, string $description, array $fields, string $footer): bool
    {
        $hook = (string)config('discord_webhook', '');
        if ($hook === '' || !preg_match('#^https://(?:ptb\.|canary\.)?discord(?:app)?\.com/api/webhooks/\d+/[\w-]+$#', $hook)) {
            return false;
        }

        $embedFields = [];
        foreach (array_slice($fields, 0, 20, true) as $name => $value) {
            $embedFields[] = ['name' => mb_substr((string)$name, 0, 250), 'value' => mb_substr(self::escape((string)$value), 0, 1000) ?: '–', 'inline' => true];
        }

        $payload = json_encode([
            'username'         => 'Clyve.dev',
            'allowed_mentions' => ['parse' => []],
            'embeds'           => [[
                'title'       => mb_substr(self::escape($title), 0, 250),
                'description' => mb_substr(self::escape($description), 0, 4000),
                'color'       => 0xA855F7,
                'fields'      => $embedFields,
                'footer'      => ['text' => $footer],
                'timestamp'   => gmdate('c'),
            ]],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($hook);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code < 200 || $code >= 300) {
            error_log('[notifier] discord webhook failed: HTTP ' . $code);
            return false;
        }
        return true;
    }
}
