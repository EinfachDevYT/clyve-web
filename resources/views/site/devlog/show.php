<?php use App\Services\Devlog; ?>
<?php if (!$inAccount): ?><div class="devlog-public"><?php endif ?>
<article class="devlog-post">
  <header class="page-head">
    <div>
      <p class="kicker mono"><?php if ($inAccount): ?><a href="/konto/devlog">Devlog</a><?php else: ?>Clyve.dev · Devlog<?php endif ?></p>
      <h1><?= e($post['title']) ?></h1>
      <div class="devlog-meta">
        <span class="devlog-tag tag-<?= e($post['tag']) ?>"><?= e(Devlog::TAGS[$post['tag']]) ?></span>
        <time class="muted small" datetime="<?= e($post['published_at']) ?>"><?= date_de($post['published_at']) ?></time>
        <?php if ($post['author_name']): ?><span class="muted small">von <?= e($post['author_name']) ?></span><?php endif ?>
      </div>
    </div>
  </header>
  <?php if ($post['summary']): ?><p class="devlog-lead"><?= e($post['summary']) ?></p><?php endif ?>
  <div class="devlog-body panel glass"><?= Devlog::render($post['body']) ?></div>
  <footer class="devlog-foot">
    <?php if ($inAccount): ?>
      <a href="/konto/devlog">← Alle Beiträge</a>
    <?php else: ?>
      <p class="muted">Alle Devlogs findest du in deinem Kundenkonto.</p>
      <div class="btn-row"><a class="pill pill-sm" href="/login">Zum Kundenbereich</a><a class="pill pill-sm pill-ghost" href="/">Zur Website</a></div>
    <?php endif ?>
  </footer>
</article>
<?php if (!$inAccount): ?></div><?php endif ?>
