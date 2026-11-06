<?php
declare(strict_types=1);

use App\Core\Db;
use App\Core\Security;
use App\Core\View;

function config(string $key, mixed $default = null): mixed
{
    $v = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) {
            return $default;
        }
        $v = $v[$k];
    }
    return $v;
}

function e(mixed $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(Security::csrfToken()) . '">';
}

function redirect(string $to, int $code = 303): never
{
    if (!str_starts_with($to, '/') || str_starts_with($to, '//') || str_contains($to, '\\')) {
        $to = '/';
    }
    header('Location: ' . $to, true, $code);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $path = (string)parse_url($ref, PHP_URL_PATH);
    $host = (string)parse_url($ref, PHP_URL_HOST);
    redirect($host === ($_SERVER['HTTP_HOST'] ?? '') && $path !== '' ? $path : $fallback);
}

function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function take_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($f) ? $f : [];
}

function old(string $key, string $default = ''): string
{
    $v = $_SESSION['_old'][$key] ?? $default;
    return is_string($v) ? $v : $default;
}

function errors(): array
{
    return is_array($_SESSION['_errors'] ?? null) ? $_SESSION['_errors'] : [];
}

function error_for(string $key): string
{
    $err = errors()[$key] ?? null;
    return $err ? '<small class="field-error">' . e($err) . '</small>' : '';
}

function invalid(string $key): string
{
    return isset(errors()[$key]) ? ' invalid' : '';
}

function fail_back(array $errors, string $fallback, ?string $message = null): never
{
    $input = $_POST;
    unset($input['csrf'], $input['password'], $input['password_confirm'], $input['password_current']);
    $_SESSION['_old'] = $input;
    $_SESSION['_errors'] = $errors;
    flash('error', $message ?? 'Bitte überprüfe deine Eingaben.');
    redirect($fallback);
}

function wants_json(): bool
{
    return str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

function json_out(int $status, array $data): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function money(int $cents): string
{
    return number_format($cents / 100, 2, ',', '.') . ' €';
}

function parse_money(string $s): ?int
{
    $s = trim(str_replace(['€', ' ', "\u{00A0}"], '', $s));
    if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $s)) {
        $s = str_replace(['.', ','], ['', '.'], $s);
    } elseif (preg_match('/^\d+,\d{1,2}$/', $s)) {
        $s = str_replace(',', '.', $s);
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $s)) {
        return null;
    }
    $c = (int)round((float)$s * 100);
    return $c <= 100_000_000 ? $c : null;
}

function date_de(?string $value, bool $withTime = false): string
{
    if (!$value) {
        return '–';
    }
    try {
        $utc = strlen($value) > 10;
        $d = new DateTimeImmutable($value, new DateTimeZone($utc ? 'UTC' : 'Europe/Berlin'));
        return $d->setTimezone(new DateTimeZone('Europe/Berlin'))->format($withTime ? 'd.m.Y, H:i' : 'd.m.Y');
    } catch (Throwable) {
        return '–';
    }
}

function id_param(array $p, string $key = 'id'): int
{
    $v = $p[$key] ?? '';
    return (is_string($v) && ctype_digit($v) && strlen($v) < 10) ? (int)$v : 0;
}

function audit(string $actor, string $action, string $subject = ''): void
{
    Db::exec('INSERT INTO audit_log (actor, action, subject) VALUES (?, ?, ?)', [$actor, $action, $subject]);
}

function abort(int $code): never
{
    http_response_code($code);
    $titles = [403 => 'Kein Zugriff', 404 => 'Nicht gefunden'];
    View::render('errors/' . ($code === 403 ? '403' : '404'), [], $titles[$code] ?? 'Fehler');
    exit;
}

function nl2p(string $text): string
{
    return nl2br(e($text), false);
}
