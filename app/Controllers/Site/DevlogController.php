<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;
use App\Services\Devlog;

final class DevlogController
{
    public function index(): void
    {
        $user = Auth::require();
        $posts = Devlog::published();
        $seen = (string)($user['devlog_seen_at'] ?? '');
        Db::exec("UPDATE customers SET devlog_seen_at = datetime('now') WHERE id = ?", [$user['id']]);
        View::render('site/devlog/index', ['posts' => $posts, 'seen' => $seen], 'Devlog', 'portal');
    }

    public function show(array $p): void
    {
        $slug = (string)($p['slug'] ?? '');
        $post = preg_match('/^[a-z0-9-]{1,70}$/', $slug) ? Devlog::findPublished($slug) : null;
        if (!$post) {
            abort(404);
        }
        $user = Auth::user();
        $inAccount = $user && !\App\Services\Account::needsVerification($user);
        View::render('site/devlog/show', [
            'post' => $post,
            'inAccount' => $inAccount,
            'meta' => [
                'description' => $post['summary'] ?: mb_substr(trim((string)preg_replace('/\s+/', ' ', strip_tags(Devlog::render($post['body'])))), 0, 200),
                'type' => 'article',
                'url' => Devlog::url($post),
                'noindex' => true,
                'theme' => '#A855F7',
            ],
        ], $post['title'], $inAccount ? 'portal' : 'layout');
    }

    public function legacy(): void
    {
        redirect('/konto/devlog');
    }
}
