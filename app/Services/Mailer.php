<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Minimal SMTP client (STARTTLS on 587, implicit TLS on 465) sending HTML mails in the
 * site's look with a plain-text alternative. Without SMTP config callers just get false.
 */
final class Mailer
{
    public static function enabled(): bool
    {
        return (string)config('smtp.host', '') !== '' && filter_var((string)config('smtp.from', ''), FILTER_VALIDATE_EMAIL) !== false;
    }

    /** @param string|array $body plain text, or a message built with compose() */
    public static function send(string $to, string $subject, string|array $body, ?string $replyTo = null): bool
    {
        if (!self::enabled() || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        try {
            $message = is_array($body) ? $body : self::fromText($body);
            self::deliver($to, $subject, $message['text'], self::html($subject, $message), $replyTo);
            return true;
        } catch (\Throwable $e) {
            error_log('[mailer] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Builds a message with greeting, paragraphs, an optional button and optional facts / quoted text.
     * The plain-text version is kept for clients that don't show HTML.
     */
    public static function compose(
        string $greetingName,
        array $lines,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
        array $facts = [],
        string $quote = '',
    ): array {
        $greeting = 'Hallo ' . trim($greetingName) . ',';
        $text = $greeting . "\n\n" . implode("\n\n", $lines);
        if ($facts) {
            $text .= "\n";
            foreach ($facts as $k => $v) {
                $text .= "\n" . $k . ': ' . $v;
            }
        }
        if ($quote !== '') {
            $text .= "\n\n> " . str_replace("\n", "\n> ", trim($quote));
        }
        if ($actionUrl) {
            $text .= "\n\n" . ($actionLabel ? $actionLabel . ":\n" : '') . $actionUrl;
        }
        $text .= "\n\nViele Grüße\n" . self::sender() . "\n";

        return [
            'greeting' => $greeting,
            'lines'    => array_values(array_filter($lines, static fn($l) => trim((string)$l) !== '')),
            'action'   => $actionUrl ? [$actionLabel ?: 'Öffnen', $actionUrl] : null,
            'facts'    => $facts,
            'quote'    => trim($quote),
            'text'     => $text,
        ];
    }

    private static function fromText(string $text): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        return ['greeting' => '', 'lines' => preg_split('/\n{2,}/', $text) ?: [], 'action' => null, 'facts' => [], 'quote' => '', 'text' => $text . "\n"];
    }

    private static function sender(): string
    {
        return (string)config('business.name', 'Clyve.dev');
    }

    /** Escapes text and turns URLs into links. */
    public static function richText(string $text): string
    {
        $html = e($text);
        $html = (string)preg_replace_callback('#https?://[^\s<>"\']+#', static function (array $m): string {
            $url = rtrim($m[0], '.,);:');
            return '<a href="' . $url . '" style="color:#C084FC;text-decoration:underline;word-break:break-all;">' . $url . '</a>' . substr($m[0], strlen($url));
        }, $html);
        return nl2br($html, false);
    }

    public static function html(string $subject, array $message): string
    {
        $base = rtrim((string)(((array)config('site_origins', []))[0] ?? ''), '/');
        return \App\Core\View::capture('mail/html', [
            'subject'   => $subject,
            'message'   => $message,
            'business'  => self::sender(),
            'siteUrl'   => $base,
            'preheader' => mb_substr(trim((string)preg_replace('/\s+/', ' ', (string)($message['lines'][0] ?? ''))), 0, 140),
        ]);
    }

    private static function header(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }

    private static function encode(string $value): string
    {
        return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
    }

    private static function deliver(string $to, string $subject, string $text, string $html, ?string $replyTo): void
    {
        $host = (string)config('smtp.host');
        $port = (int)config('smtp.port', 587);
        $secure = (string)config('smtp.encryption', $port === 465 ? 'ssl' : 'tls');
        if ($secure === 'none' && !in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('unencrypted SMTP is only allowed to localhost');
        }
        $from = (string)config('smtp.from');
        $fromName = (string)config('smtp.from_name', config('business.name', 'Clyve.dev'));

        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        if (!$fp) {
            throw new RuntimeException("connect {$remote}: {$errstr}");
        }
        stream_set_timeout($fp, 15);

        $read = static function () use ($fp): array {
            $data = '';
            while (($line = fgets($fp, 1024)) !== false) {
                $data .= $line;
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return [(int)substr($data, 0, 3), $data];
        };
        $cmd = static function (string $command, array $expect) use ($fp, $read): string {
            fwrite($fp, $command . "\r\n");
            [$code, $data] = $read();
            if (!in_array($code, $expect, true)) {
                $shown = str_starts_with($command, 'AUTH') || strlen($command) > 60 && !str_contains($command, ' ') ? '[credentials]' : $command;
                throw new RuntimeException("SMTP {$shown} -> {$data}");
            }
            return $data;
        };

        try {
            [$code, $greeting] = $read();
            if ($code !== 220) {
                throw new RuntimeException('SMTP greeting: ' . $greeting);
            }
            $ehlo = 'EHLO ' . (parse_url((string)(config('site_origins')[0] ?? ''), PHP_URL_HOST) ?: 'localhost');
            $caps = $cmd($ehlo, [250]);

            if ($secure === 'tls') {
                if (!str_contains($caps, 'STARTTLS')) {
                    throw new RuntimeException('server offers no STARTTLS');
                }
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('TLS handshake failed');
                }
                $cmd($ehlo, [250]);
            }

            $user = (string)config('smtp.username', '');
            if ($user !== '') {
                $cmd('AUTH LOGIN', [334]);
                $cmd(base64_encode($user), [334]);
                $cmd(base64_encode((string)config('smtp.password', '')), [235]);
            }

            $cmd('MAIL FROM:<' . $from . '>', [250]);
            $cmd('RCPT TO:<' . $to . '>', [250, 251]);
            $cmd('DATA', [354]);

            $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
            $headers = [
                'Date: ' . date('r'),
                'From: ' . self::encode(self::header($fromName)) . ' <' . $from . '>',
                'To: <' . $to . '>',
                'Subject: ' . self::encode(self::header($subject)),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . ($alt = 'alt-' . bin2hex(random_bytes(10))) . '"',
                'Auto-Submitted: auto-generated',
            ];
            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $headers[] = 'Reply-To: <' . $replyTo . '>';
            }
            $b64 = static fn(string $data): string => rtrim(chunk_split(base64_encode($data), 76, "\r\n"));
            $rel = 'rel-' . bin2hex(random_bytes(10));
            $logo = BASE_PATH . '/public/assets/brand/clyve-logo-mail.png';
            $parts = [
                "--{$alt}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64(str_replace(["\r\n", "\r"], "\n", $text)),
                "--{$alt}\r\nContent-Type: multipart/related; boundary=\"{$rel}\"\r\n\r\n"
                . "--{$rel}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . $b64($html) . "\r\n"
                . (is_file($logo) ? "--{$rel}\r\nContent-Type: image/png; name=\"clyve.png\"\r\nContent-Transfer-Encoding: base64\r\nContent-ID: <logo@clyve>\r\nContent-Disposition: inline; filename=\"clyve.png\"\r\n\r\n" . $b64((string)file_get_contents($logo)) . "\r\n" : '')
                . "--{$rel}--",
                "--{$alt}--",
            ];
            $cmd(implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts) . "\r\n.", [250]);
            fwrite($fp, "QUIT\r\n");
        } finally {
            fclose($fp);
        }
    }
}
