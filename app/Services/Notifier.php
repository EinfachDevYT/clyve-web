<?php
declare(strict_types=1);

namespace App\Services;

final class Notifier
{
    public const TYPES = [
        'fivem' => 'FiveM Script',
        'web'   => 'Website',
        'bot'   => 'Discord Bot',
        'other' => 'Sonstiges',
    ];

    public static function newRequest(array $r): bool
    {
        $sent = self::discord(
            'Neue Anfrage: ' . ($r['title'] !== '' ? $r['title'] : self::TYPES[$r['type']]) . ' — ' . $r['name'],
            $r['message'],
            [
                'Art'     => self::TYPES[$r['type']],
                'Budget'  => $r['budget'] ?: '–',
                'Termin'  => $r['deadline'] ?: '–',
                'Discord' => $r['discord'] ?: '–',
                'E-Mail'  => $r['email'] ?: '–',
            ],
            'Anfrage ' . $r['public_id']
        );
        $mailed = self::mail('Neue Anfrage: ' . self::TYPES[$r['type']], self::plain($r), $r['email'] ?: null);
        return $sent || $mailed;
    }

    public static function newTicket(array $ticket, array $customer, string $body): void
    {
        self::discord(
            'Ticket #' . $ticket['id'] . ': ' . $ticket['subject'],
            $body,
            ['Kunde' => $customer['name'], 'E-Mail' => $customer['email']],
            'Support'
        );
    }

    private static function escape(string $s): string
    {
        $s = (string)preg_replace('/([\\\\`*_~|>#\-\[\]()])/', '\\\\$1', $s);
        return str_replace('@', "@\u{200B}", $s);
    }

    private static function discord(string $title, string $description, array $fields, string $footer): bool
    {
        $hook = (string)config('discord_webhook', '');
        if ($hook === '' || !preg_match('#^https://(?:ptb\.|canary\.)?discord(?:app)?\.com/api/webhooks/\d+/[\w-]+$#', $hook)) {
            return false;
        }

        $embedFields = [];
        foreach ($fields as $name => $value) {
            $embedFields[] = ['name' => $name, 'value' => mb_substr(self::escape((string)$value), 0, 1000), 'inline' => true];
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
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            error_log('[notifier] discord webhook failed: HTTP ' . $code);
            return false;
        }
        return true;
    }

    private static function mail(string $subject, string $body, ?string $replyTo): bool
    {
        $to = (string)config('mail_to', '');
        $from = (string)config('mail_from', '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $headers = ['From: Clyve.dev <' . $from . '>', 'Content-Type: text/plain; charset=UTF-8'];
        if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }

    private static function plain(array $r): string
    {
        return "Anfrage {$r['public_id']}\n"
            . 'Art: ' . self::TYPES[$r['type']] . "\n"
            . "Name: {$r['name']}\nTitel: {$r['title']}\nDiscord: {$r['discord']}\nE-Mail: {$r['email']}\n"
            . "Budget: {$r['budget']}\nTermin: {$r['deadline']}\n\n{$r['message']}\n";
    }
}
