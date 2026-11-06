<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class Validator
{
    private array $errors = [];

    public function __construct(private array $input)
    {
    }

    public static function clean(mixed $value, int $max, bool $multiline = false): ?string
    {
        $value ??= '';
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
            return null;
        }
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $pattern = $multiline
            ? '/[\x00-\x09\x0B-\x1F\x7F\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u'
            : '/[\x00-\x1F\x7F\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';
        $value = trim((string)preg_replace($pattern, '', $value));
        return mb_strlen($value) > $max ? null : $value;
    }

    public function text(string $key, int $min, int $max, string $label, bool $multiline = false): string
    {
        $v = self::clean($this->input[$key] ?? '', $max, $multiline);
        if ($v === null) {
            $this->errors[$key] = "{$label}: maximal {$max} Zeichen.";
            return '';
        }
        if (mb_strlen($v) < $min) {
            $this->errors[$key] = $min <= 1 ? "{$label} fehlt." : "{$label}: mindestens {$min} Zeichen.";
        }
        return $v;
    }

    public function email(string $key, bool $required = true): string
    {
        $v = self::clean($this->input[$key] ?? '', 160) ?? '';
        if ($v === '' && !$required) {
            return '';
        }
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$key] = 'Bitte gib eine gültige E-Mail-Adresse an.';
            return '';
        }
        return strtolower($v);
    }

    public function discord(string $key): string
    {
        $v = self::clean($this->input[$key] ?? '', 40) ?? '';
        if ($v !== '' && !preg_match('/^[a-z0-9_.]{2,32}$/i', $v)) {
            $this->errors[$key] = 'Ungültiger Discord-Name.';
            return '';
        }
        return $v;
    }

    public function oneOf(string $key, array $allowed, string $message): string
    {
        $v = $this->input[$key] ?? '';
        if (!is_string($v) || !in_array($v, $allowed, true)) {
            $this->errors[$key] = $message;
            return '';
        }
        return $v;
    }

    public function date(string $key, bool $futureOnly = false): string
    {
        $v = $this->input[$key] ?? '';
        if ($v === '' || $v === null) {
            return '';
        }
        $d = is_string($v) ? DateTimeImmutable::createFromFormat('!Y-m-d', $v) : false;
        if (!$d || $d->format('Y-m-d') !== $v || $d > new DateTimeImmutable('+5 years')
            || ($futureOnly && $d < new DateTimeImmutable('today'))) {
            $this->errors[$key] = 'Ungültiges Datum.';
            return '';
        }
        return $v;
    }

    public function accepted(string $key, string $message): bool
    {
        if (($this->input[$key] ?? '') !== '1') {
            $this->errors[$key] = $message;
            return false;
        }
        return true;
    }

    public function addError(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
