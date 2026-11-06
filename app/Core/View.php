<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use RuntimeException;

final class View
{
    public static function render(string $template, array $data = [], string $title = '', ?string $layout = 'layout'): void
    {
        $data['user'] ??= Auth::user();
        $content = self::capture($template, $data);

        if ($layout === null) {
            echo $content;
        } else {
            echo self::capture(APP_CONTEXT . '/' . $layout, $data + [
                'content'   => $content,
                'pageTitle' => $title,
                'flashes'   => take_flashes(),
            ]);
        }
        unset($_SESSION['_old'], $_SESSION['_errors']);
    }

    public static function capture(string $template, array $data = []): string
    {
        $__file = self::path($template);
        extract($data, EXTR_SKIP);
        ob_start();
        require $__file;
        return (string)ob_get_clean();
    }

    public static function partial(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }

    private static function path(string $template): string
    {
        if (!preg_match('#^[a-z0-9_\-]+(/[a-z0-9_\-]+)*$#', $template)) {
            throw new InvalidArgumentException('Invalid template name');
        }
        $file = VIEW_PATH . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Template not found: ' . $template);
        }
        return $file;
    }

    public static function active(string $prefix, bool $exact = false): string
    {
        $path = rtrim((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/') ?: '/';
        $on = $exact || $prefix === '/' ? $path === $prefix : ($path === $prefix || str_starts_with($path, $prefix . '/'));
        return $on ? ' aria-current="page"' : '';
    }

    public static function badge(string $status): string
    {
        static $map = [
            'open'      => ['Offen', 'warn'],
            'answered'  => ['Beantwortet', 'ok'],
            'closed'    => ['Geschlossen', 'muted'],
            'draft'     => ['Entwurf', 'muted'],
            'issued'    => ['Offen', 'warn'],
            'paid'      => ['Bezahlt', 'ok'],
            'cancelled' => ['Storniert', 'bad'],
            'new'       => ['Neu', 'warn'],
            'contacted' => ['Kontaktiert', 'info'],
            'accepted'  => ['Angenommen', 'ok'],
            'declined'  => ['Abgelehnt', 'bad'],
            'done'      => ['Erledigt', 'muted'],
        ];
        [$label, $tone] = $map[$status] ?? [$status, 'muted'];
        return '<span class="badge badge-' . $tone . '">' . e($label) . '</span>';
    }
}
