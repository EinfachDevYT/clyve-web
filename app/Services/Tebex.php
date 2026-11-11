<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use RuntimeException;

/** Reads packages from the Tebex Headless API and mirrors them into the products table. */
final class Tebex
{
    public const FRAMEWORKS = ['ESX', 'QBCore', 'Qbox', 'Standalone'];
    public const DEFAULT_IMAGE_HOSTS = ['dunb17ur4ymx4.cloudfront.net'];

    public static function configured(): bool
    {
        return preg_match('/^[a-z0-9-]{4,80}$/i', (string)config('tebex.webstore_token', '')) === 1;
    }

    public static function imageHosts(): array
    {
        return array_values(array_filter(
            (array)config('tebex.image_hosts', self::DEFAULT_IMAGE_HOSTS),
            static fn($h) => is_string($h) && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $h)
        ));
    }

    public static function safeImage(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || !in_array(strtolower($parts['host'] ?? ''), array_map('strtolower', self::imageHosts()), true)) {
            return '';
        }
        return $url;
    }

    public static function safeStoreUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $store = strtolower((string)parse_url((string)config('tebex.store_url', ''), PHP_URL_HOST));
        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || ($host !== $store && !str_ends_with($host, '.tebex.io') && $host !== 'tebex.io')) {
            return '';
        }
        return $url;
    }

    private static function plain(string $html): string
    {
        $text = preg_replace('#<(script|style|iframe|noscript)\b[^>]*>.*?</\1\s*>#is', '', $html) ?? '';
        $text = preg_replace('#<(br|/p|/div|/li|/h[1-6])\b[^>]*>#i', "\n", $text) ?? '';
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? '';
        $text = preg_replace("/\n\s*\n\s*\n+/", "\n\n", $text) ?? '';
        return trim($text);
    }

    private static function price(array $pkg): string
    {
        $amount = $pkg['total_price'] ?? $pkg['base_price'] ?? null;
        if (!is_numeric($amount)) {
            return '';
        }
        $currency = strtoupper((string)($pkg['currency'] ?? 'EUR'));
        $formatted = number_format((float)$amount, 2, ',', '.');
        return $currency === 'EUR' ? $formatted . ' €' : $formatted . ' ' . $currency;
    }

    /** @return array{created:int, updated:int, hidden:int} */
    public static function sync(): array
    {
        if (!self::configured()) {
            throw new RuntimeException('Tebex webstore token missing');
        }
        $url = 'https://headless.tebex.io/api/accounts/' . rawurlencode((string)config('tebex.webstore_token')) . '/categories?includePackages=1';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code !== 200 || !is_string($body)) {
            throw new RuntimeException('Tebex API HTTP ' . $code);
        }
        $json = json_decode($body, true);
        if (!is_array($json['data'] ?? null)) {
            throw new RuntimeException('Unexpected Tebex response');
        }
        return self::import($json);
    }

    /** @return array{created:int, updated:int, hidden:int} */
    public static function import(array $json): array
    {
        $store = rtrim((string)config('tebex.store_url', ''), '/');
        $seen = [];
        $created = $updated = 0;

        Db::transaction(static function () use ($json, $store, &$seen, &$created, &$updated): void {
            foreach ($json['data'] as $category) {
                foreach ((array)($category['packages'] ?? []) as $pkg) {
                    $id = (int)($pkg['id'] ?? 0);
                    if ($id <= 0 || !is_string($pkg['name'] ?? null)) {
                        continue;
                    }
                    $seen[] = $id;
                    $description = self::plain((string)($pkg['description'] ?? ''));
                    $row = [
                        'title'       => mb_substr(trim($pkg['name']), 0, 120),
                        'category'    => mb_substr((string)($category['name'] ?? ''), 0, 60),
                        'summary'     => mb_substr(preg_replace('/\s+/', ' ', $description) ?? '', 0, 180),
                        'description' => mb_substr($description, 0, 3000),
                        'price_label' => self::price($pkg),
                        'image_url'   => self::safeImage((string)($pkg['image'] ?? '')),
                        'tebex_url'   => $store !== '' ? self::safeStoreUrl($store . '/package/' . $id) : '',
                    ];
                    if (Db::value('SELECT 1 FROM products WHERE tebex_id = ?', [$id])) {
                        Db::exec(
                            "UPDATE products SET title = ?, category = ?, summary = ?, description = ?, price_label = ?, image_url = ?, tebex_url = ?, updated_at = datetime('now') WHERE tebex_id = ?",
                            [...array_values($row), $id]
                        );
                        $updated++;
                    } else {
                        Db::exec(
                            'INSERT INTO products (tebex_id, title, category, summary, description, price_label, image_url, tebex_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                            [$id, ...array_values($row)]
                        );
                        $created++;
                    }
                }
            }
        });

        $hidden = 0;
        if ($seen) {
            $placeholders = implode(',', array_fill(0, count($seen), '?'));
            $hidden = Db::exec("UPDATE products SET active = 0 WHERE tebex_id IS NOT NULL AND active = 1 AND tebex_id NOT IN ({$placeholders})", $seen)->rowCount();
        }
        return ['created' => $created, 'updated' => $updated, 'hidden' => $hidden];
    }
}
