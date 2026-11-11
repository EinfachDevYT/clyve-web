<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Minimal SMTP client (STARTTLS on 587, implicit TLS on 465). Falls back to nothing
 * when SMTP is not configured: callers get false and the message stays in the portal.
 */
final class Mailer
{
    public static function enabled(): bool
    {
        return (string)config('smtp.host', '') !== '' && filter_var((string)config('smtp.from', ''), FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function send(string $to, string $subject, string $text, ?string $replyTo = null): bool
    {
        if (!self::enabled() || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        try {
            self::deliver($to, $subject, $text, $replyTo);
            return true;
        } catch (\Throwable $e) {
            error_log('[mailer] ' . $e->getMessage());
            return false;
        }
    }

    /** Renders a plain-text mail from lines and an optional call to action. */
    public static function compose(string $greetingName, array $lines, ?string $actionLabel = null, ?string $actionUrl = null): string
    {
        $out = 'Hallo ' . $greetingName . ",\n\n" . implode("\n\n", $lines);
        if ($actionUrl) {
            $out .= "\n\n" . ($actionLabel ? $actionLabel . ":\n" : '') . $actionUrl;
        }
        return $out . "\n\nViele Grüße\n" . config('business.name', 'Clyve.dev') . "\n";
    }

    private static function header(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }

    private static function encode(string $value): string
    {
        return preg_match('/[^\x20-\x7E]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
    }

    private static function deliver(string $to, string $subject, string $text, ?string $replyTo): void
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
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'Auto-Submitted: auto-generated',
            ];
            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $headers[] = 'Reply-To: <' . $replyTo . '>';
            }
            $body = rtrim(chunk_split(base64_encode(str_replace(["\r\n", "\r"], "\n", $text)), 76, "\r\n"));
            $cmd(implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.", [250]);
            fwrite($fp, "QUIT\r\n");
        } finally {
            fclose($fp);
        }
    }
}
