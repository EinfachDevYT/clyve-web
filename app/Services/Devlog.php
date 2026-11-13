<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/**
 * Devlog posts. Body format, one entry per line:
 *   + added   - removed   ~ improved   ! fixed   ## section heading
 * Other lines become paragraphs. **bold** and `code` work inline.
 */
final class Devlog
{
    public const TAGS = ['feature' => 'Neues Feature', 'improvement' => 'Verbesserung', 'fix' => 'Bugfix', 'news' => 'News'];
    public const MARKS = ['+' => 'add', '-' => 'remove', '~' => 'change', '!' => 'fix'];
    public const MARK_LABELS = ['add' => 'Neu', 'remove' => 'Entfernt', 'change' => 'Verbessert', 'fix' => 'Behoben'];
    private const DISCORD_LIMIT = 2000;

    public static function published(): array
    {
        return Db::all("SELECT * FROM devlog_posts WHERE status = 'published' ORDER BY pinned DESC, published_at DESC, id DESC");
    }

    public static function findPublished(string $slug): ?array
    {
        return Db::one("SELECT p.*, s.name AS author_name FROM devlog_posts p LEFT JOIN staff s ON s.id = p.author_id WHERE p.slug = ? AND p.status = 'published'", [$slug]);
    }

    public static function unseen(array $customer): int
    {
        return (int)Db::value(
            "SELECT COUNT(*) FROM devlog_posts WHERE status = 'published' AND published_at > COALESCE(?, '')",
            [$customer['devlog_seen_at'] ?? null]
        );
    }

    public static function url(array $post): string
    {
        return Account::baseUrl('customer') . '/devlog/' . $post['slug'];
    }

    public static function slug(string $title, int $exceptId = 0): string
    {
        $s = strtr(mb_strtolower($title), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $s = trim((string)preg_replace('/[^a-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s), '-');
        $s = substr($s, 0, 60) ?: 'beitrag';
        $base = rtrim($s, '-');
        for ($i = 2, $slug = $base; Db::value('SELECT 1 FROM devlog_posts WHERE slug = ? AND id <> ?', [$slug, $exceptId]); $i++) {
            $slug = $base . '-' . $i;
        }
        return $slug;
    }

    /** @return list<array{type: string, text?: string, items?: list<array{0: string, 1: string}>}> */
    public static function blocks(string $body): array
    {
        $blocks = [];
        $para = [];
        $flush = static function () use (&$blocks, &$para): void {
            if ($para) {
                $blocks[] = ['type' => 'p', 'text' => implode("\n", $para)];
                $para = [];
            }
        };
        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = rtrim($line);
            if ($line === '') {
                $flush();
                continue;
            }
            if (str_starts_with($line, '## ')) {
                $flush();
                $blocks[] = ['type' => 'h', 'text' => trim(substr($line, 3))];
                continue;
            }
            $mark = self::MARKS[$line[0]] ?? null;
            if ($mark && preg_match('/^.\s+\S/u', $line)) {
                $flush();
                $last = array_key_last($blocks);
                if ($last === null || $blocks[$last]['type'] !== 'list') {
                    $blocks[] = ['type' => 'list', 'items' => []];
                    $last = array_key_last($blocks);
                }
                $blocks[$last]['items'][] = [$mark, trim(substr($line, 1))];
                continue;
            }
            $para[] = $line;
        }
        $flush();
        return $blocks;
    }

    public static function inline(string $text): string
    {
        $html = e($text);
        $html = (string)preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        $html = (string)preg_replace('/\*\*([^*]+)\*\*/', '<b>$1</b>', $html);
        return nl2br($html, false);
    }

    public static function render(string $body): string
    {
        $out = '';
        foreach (self::blocks($body) as $b) {
            if ($b['type'] === 'h') {
                $out .= '<h2>' . self::inline($b['text']) . '</h2>';
            } elseif ($b['type'] === 'p') {
                $out .= '<p>' . self::inline($b['text']) . '</p>';
            } else {
                $out .= '<ul class="changes">';
                foreach ($b['items'] as [$mark, $text]) {
                    $out .= '<li class="change-' . $mark . '"><span class="change-mark mono">' . e(self::MARK_LABELS[$mark]) . '</span><span>' . self::inline($text) . '</span></li>';
                }
                $out .= '</ul>';
            }
        }
        return $out;
    }

    public static function counts(string $body): array
    {
        $n = array_fill_keys(array_keys(self::MARK_LABELS), 0);
        foreach (self::blocks($body) as $b) {
            foreach ($b['items'] ?? [] as [$mark]) {
                $n[$mark]++;
            }
        }
        return $n;
    }

    /** Ready-to-post Discord message in the style of a GitHub changelog. */
    public static function discordMessage(array $post): string
    {
        $plain = static fn(string $s): string => str_replace('```', "'''", $s);
        $head = (self::role() !== '' ? '<@&' . self::role() . ">\n" : '') . '## 🌸 Devlog · ' . $plain($post['title']) . "\n";
        if ($post['summary'] !== '') {
            $head .= $plain($post['summary']) . "\n";
        }
        $foot = "\n🔗 **Ganzer Beitrag:** <" . self::url($post) . '>';

        $sections = [];
        $current = ['title' => '', 'lines' => []];
        foreach (self::blocks($post['body']) as $b) {
            if ($b['type'] === 'h') {
                if ($current['lines']) {
                    $sections[] = $current;
                }
                $current = ['title' => $b['text'], 'lines' => []];
            } elseif ($b['type'] === 'list') {
                foreach ($b['items'] as [$mark, $text]) {
                    $current['lines'][] = array_search($mark, self::MARKS, true) . ' ' . $plain(str_replace(['**', '`'], '', $text));
                }
            }
        }
        if ($current['lines']) {
            $sections[] = $current;
        }

        $budget = self::DISCORD_LIMIT - mb_strlen($head) - mb_strlen($foot) - 40;
        $body = '';
        $skipped = 0;
        foreach ($sections as $s) {
            $chunk = ($s['title'] !== '' ? "\n**" . $plain($s['title']) . "**" : '') . "\n```diff\n";
            $lines = '';
            foreach ($s['lines'] as $line) {
                if (mb_strlen($body . $chunk . $lines . $line . "\n```") + 1 > $budget) {
                    $skipped++;
                    continue;
                }
                $lines .= $line . "\n";
            }
            if ($lines !== '') {
                $body .= $chunk . $lines . '```';
            }
        }
        if ($skipped) {
            $body .= "\n*… und {$skipped} weitere Änderung" . ($skipped === 1 ? '' : 'en') . ' im Beitrag.*';
        }
        return $head . $body . $foot;
    }

    public static function webhookConfigured(): bool
    {
        return self::webhook() !== '';
    }

    public static function postToDiscord(array $post): bool
    {
        $hook = self::webhook();
        return $hook !== '' && Notifier::send($hook, [
            'username'         => (string)config('business.name', 'Clyve.dev') . ' Devlog',
            'content'          => self::discordMessage($post),
            'allowed_mentions' => ['parse' => [], 'roles' => self::role() !== '' ? [self::role()] : []],
        ]);
    }

    public static function role(): string
    {
        $role = (string)config('devlog_role', '');
        return preg_match('/^\d{15,22}$/', $role) ? $role : '';
    }

    private static function webhook(): string
    {
        $hook = (string)config('devlog_webhook', '');
        return Notifier::validWebhook($hook) ? $hook : '';
    }
}
