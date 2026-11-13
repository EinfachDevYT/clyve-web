<?php use App\Services\Devlog; ?>
<header class="page-head">
  <div><p class="kicker mono">Kundenbereich</p><h1>Devlog</h1></div>
</header>
<p class="muted">Was sich bei Clyve.dev tut – neue Funktionen, Verbesserungen und behobene Fehler.</p>

<?php if (!$posts): ?>
  <div class="panel glass empty-state">
    <img src="/assets/img/blossom.svg" alt="" width="48" height="48">
    <p>Noch keine Beiträge – bald gibt's hier Neuigkeiten.</p>
  </div>
<?php else: ?>
  <div class="devlog-list">
    <?php foreach ($posts as $p): $n = Devlog::counts($p['body']); $new = $seen === '' || $p['published_at'] > $seen; ?>
      <a class="devlog-card glass" href="/devlog/<?= e($p['slug']) ?>">
        <div class="devlog-meta">
          <span class="devlog-tag tag-<?= e($p['tag']) ?>"><?= e(Devlog::TAGS[$p['tag']]) ?></span>
          <?php if ($p['pinned']): ?><span class="badge badge-info">Angeheftet</span><?php endif ?>
          <?php if ($new): ?><span class="badge badge-ok">Neu</span><?php endif ?>
          <time class="muted small" datetime="<?= e($p['published_at']) ?>"><?= date_de($p['published_at']) ?></time>
        </div>
        <h2><?= e($p['title']) ?></h2>
        <?php if ($p['summary']): ?><p class="muted"><?= e($p['summary']) ?></p><?php endif ?>
        <div class="devlog-counts mono small">
          <?php foreach (Devlog::MARK_LABELS as $k => $label): if ($n[$k]): ?><span class="c-<?= $k ?>"><?= $n[$k] ?> <?= e($label) ?></span><?php endif; endforeach ?>
        </div>
      </a>
    <?php endforeach ?>
  </div>
<?php endif ?>
