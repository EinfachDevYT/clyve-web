<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
use App\Services\Devlog;

final class DevlogController
{
    public function index(): void
    {
        Auth::require();
        View::render('staff/devlog/index', [
            'posts' => Db::all("SELECT p.*, s.name AS author_name FROM devlog_posts p LEFT JOIN staff s ON s.id = p.author_id
                                ORDER BY p.status = 'published', p.pinned DESC, COALESCE(p.published_at, p.created_at) DESC, p.id DESC"),
        ], 'Devlog');
    }

    public function create(): void
    {
        Auth::require();
        View::render('staff/devlog/form', ['post' => null], 'Neuer Devlog-Beitrag');
    }

    public function edit(array $p): void
    {
        Auth::require();
        View::render('staff/devlog/form', ['post' => $this->find($p)], 'Devlog bearbeiten');
    }

    public function store(): void
    {
        $staff = Auth::require();
        $d = $this->validate('/devlog/neu', 0);
        $publish = ($_POST['intent'] ?? '') === 'publish';
        $id = Db::insert(
            "INSERT INTO devlog_posts (slug, title, tag, summary, body, pinned, author_id, status, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? THEN datetime('now') END)",
            [$d['slug'], $d['title'], $d['tag'], $d['summary'], $d['body'], $d['pinned'], $staff['id'], $publish ? 'published' : 'draft', $publish ? 1 : 0]
        );
        audit('staff:' . $staff['id'], $publish ? 'devlog_published' : 'devlog_created', '#' . $id);
        flash('success', $publish ? 'Beitrag veröffentlicht.' : 'Entwurf gespeichert.');
        redirect('/devlog/' . $id);
    }

    public function update(array $p): void
    {
        $staff = Auth::require();
        $post = $this->find($p);
        $d = $this->validate('/devlog/' . $post['id'], (int)$post['id']);
        $publish = ($_POST['intent'] ?? '') === 'publish' && $post['status'] === 'draft';
        Db::exec(
            "UPDATE devlog_posts SET slug = ?, title = ?, tag = ?, summary = ?, body = ?, pinned = ?, updated_at = datetime('now'),
             status = CASE WHEN ? THEN 'published' ELSE status END,
             published_at = CASE WHEN ? THEN datetime('now') ELSE published_at END WHERE id = ?",
            [$post['status'] === 'published' ? $post['slug'] : $d['slug'], $d['title'], $d['tag'], $d['summary'], $d['body'], $d['pinned'], $publish ? 1 : 0, $publish ? 1 : 0, $post['id']]
        );
        audit('staff:' . $staff['id'], $publish ? 'devlog_published' : 'devlog_updated', '#' . $post['id']);
        flash('success', $publish ? 'Beitrag veröffentlicht.' : 'Gespeichert.');
        redirect('/devlog/' . $post['id']);
    }

    public function unpublish(array $p): void
    {
        $staff = Auth::require();
        $post = $this->find($p);
        Db::exec("UPDATE devlog_posts SET status = 'draft', updated_at = datetime('now') WHERE id = ?", [$post['id']]);
        audit('staff:' . $staff['id'], 'devlog_unpublished', '#' . $post['id']);
        flash('info', 'Beitrag zurückgezogen – er ist wieder ein Entwurf und der Link funktioniert nicht mehr.');
        redirect('/devlog/' . $post['id']);
    }

    public function discord(array $p): void
    {
        $staff = Auth::require();
        $post = $this->find($p);
        if ($post['status'] !== 'published') {
            fail_back([], '/devlog/' . $post['id'], 'Veröffentliche den Beitrag zuerst – sonst führt der Link ins Leere.');
        }
        if (!Devlog::webhookConfigured()) {
            fail_back([], '/devlog/' . $post['id'], 'Kein Devlog-Webhook eingerichtet (Verwaltung → Integrationen).');
        }
        if (!Devlog::postToDiscord($post)) {
            fail_back([], '/devlog/' . $post['id'], 'Discord hat die Nachricht nicht angenommen. Ist der Webhook richtig?');
        }
        Db::exec("UPDATE devlog_posts SET discord_posted_at = datetime('now') WHERE id = ?", [$post['id']]);
        audit('staff:' . $staff['id'], 'devlog_discord', '#' . $post['id']);
        flash('success', 'In Discord gepostet.');
        redirect('/devlog/' . $post['id']);
    }

    public function destroy(array $p): void
    {
        $staff = Auth::requireAdmin();
        $post = $this->find($p);
        Db::exec('DELETE FROM devlog_posts WHERE id = ?', [$post['id']]);
        audit('staff:' . $staff['id'], 'devlog_deleted', '#' . $post['id'] . ' ' . $post['title']);
        flash('info', 'Beitrag gelöscht.');
        redirect('/devlog');
    }

    private function validate(string $back, int $id): array
    {
        $v = new Validator($_POST);
        $d = [
            'title'   => $v->text('title', 3, 120, 'Titel'),
            'tag'     => $v->oneOf('tag', array_keys(Devlog::TAGS), 'Bitte wähle eine Art.'),
            'summary' => $v->text('summary', 0, 280, 'Kurzfassung'),
            'body'    => $v->text('body', 3, 12000, 'Inhalt', true),
            'pinned'  => ($_POST['pinned'] ?? '') === '1' ? 1 : 0,
        ];
        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }
        $d['slug'] = Devlog::slug($d['title'], $id);
        return $d;
    }

    private function find(array $p): array
    {
        return Db::one('SELECT p.*, s.name AS author_name FROM devlog_posts p LEFT JOIN staff s ON s.id = p.author_id WHERE p.id = ?', [id_param($p)]) ?? abort(404);
    }
}
