<?php use App\Services\Devlog; ?>
<header class="page-head">
  <div><p class="kicker mono">Staff</p><h1>Devlog</h1></div>
  <a class="pill pill-sm" href="/devlog/neu">Neuer Beitrag</a>
</header>
<p class="muted small">Beiträge erscheinen für eingeloggte Kunden unter „Devlog“. Jeder veröffentlichte Beitrag hat einen Link, den du mit fertiger Nachricht in Discord teilen kannst.</p>

<div class="table-wrap glass">
  <table class="table table-link">
    <thead><tr><th>Beitrag</th><th>Art</th><th>Änderungen</th><th>Status</th><th>Datum</th></tr></thead>
    <tbody>
      <?php foreach ($posts as $p): $n = Devlog::counts($p['body']); ?>
        <tr>
          <td data-label="Beitrag"><a href="/devlog/<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a><?= $p['pinned'] ? ' <span class="badge badge-info">Angeheftet</span>' : '' ?></td>
          <td data-label="Art"><span class="devlog-tag tag-<?= e($p['tag']) ?>"><?= e(Devlog::TAGS[$p['tag']]) ?></span></td>
          <td data-label="Änderungen" class="mono small"><span class="c-add">+<?= $n['add'] ?></span> <span class="c-remove">−<?= $n['remove'] ?></span> <span class="c-change">~<?= $n['change'] ?></span> <span class="c-fix">!<?= $n['fix'] ?></span></td>
          <td data-label="Status"><?= $p['status'] === 'published' ? '<span class="badge badge-ok">Veröffentlicht</span>' : '<span class="badge badge-muted">Entwurf</span>' ?><?= $p['discord_posted_at'] ? ' <span class="badge badge-info">Discord</span>' : '' ?></td>
          <td data-label="Datum"><?= date_de($p['published_at'] ?? $p['created_at']) ?></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$posts): ?><tr><td colspan="5" class="empty">Noch keine Beiträge.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
