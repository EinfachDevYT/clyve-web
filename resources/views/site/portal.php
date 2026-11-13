<?php
use App\Core\Db;
use App\Core\View;
$devlogNew = $user && Db::value("SELECT 1 FROM devlog_posts WHERE status = 'published' LIMIT 1") ? \App\Services\Devlog::unseen($user) : null;
$hasServices = $user && \App\Services\Settings::on('hosting_customer_view') && Db::value('SELECT 1 FROM services WHERE customer_id = ? LIMIT 1', [$user['id']]);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="author" content="EinfachDev · einfachdev.de">
  <title><?= e($pageTitle ? $pageTitle . ' · Kundenbereich' : 'Kundenbereich · Clyve.dev') ?></title>
  <?php if (!empty($meta['url'])): ?>
  <meta property="og:site_name" content="Clyve.dev">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($meta['description']) ?>">
  <meta property="og:url" content="<?= e($meta['url']) ?>">
  <?php endif ?>
  <meta name="theme-color" content="#A855F7">
  <link rel="icon" href="/assets/brand/clyve-icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(asset('/assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
  <script src="<?= e(asset('/assets/js/main.js')) ?>" defer></script>
</head>
<body class="is-staff is-portal">
  <div class="progress" id="progress" aria-hidden="true"></div>
  <div class="staff">
    <aside class="staff-side" data-swap="nav">
      <a class="brand" href="/konto" aria-label="Übersicht"><img src="/assets/brand/clyve-mark.svg" alt="" class="brand-mark" width="30" height="30"><img src="/assets/brand/clyve-logo.svg" alt="Clyve.dev" class="brand-logo" width="112" height="26"><small class="mono">konto</small></a>
      <nav class="staff-nav">
        <a href="/konto"<?= View::active('/konto', true) ?>>Übersicht</a>
        <a href="/konto/anfragen"<?= View::active('/konto/anfragen') ?>>Anfragen</a>
        <a href="/konto/tickets"<?= View::active('/konto/tickets') ?>>Support</a>
        <?php if ($hasServices): ?><a href="/konto/dienste"<?= View::active('/konto/dienste') ?>>Dienste</a><?php endif ?>
        <a href="/konto/rechnungen"<?= View::active('/konto/rechnungen') ?>>Rechnungen</a>
        <?php if ($devlogNew !== null): ?><a href="/konto/devlog"<?= View::active('/konto/devlog') ?: View::active('/devlog') ?>>Devlog<?php if ($devlogNew): ?> <span class="nav-count"><?= $devlogNew ?></span><?php endif ?></a><?php endif ?>
        <span class="nav-label mono">Persönlich</span>
        <a href="/konto/einstellungen"<?= View::active('/konto/einstellungen') ?>>Einstellungen</a>
        <span class="nav-label mono">Clyve.dev</span>
        <a href="/">Zur Website</a>
        <a href="/#anfrage">Neues Projekt anfragen</a>
      </nav>
      <?php View::partial('shared/signature', ['compact' => true]) ?>
      <div class="staff-me">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
        <a class="staff-me-link" href="/konto/einstellungen" title="Einstellungen"><b><?= e($user['name']) ?></b><small><?= e($user['email']) ?></small></a>
        <form action="/logout" method="post" data-native><?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Abmelden" title="Abmelden">⏻</button></form>
      </div>
    </aside>
    <main id="app" class="staff-main" data-swap="main">
      <?php if (($announcement = \App\Services\Settings::get('announcement')) !== ''): ?>
        <div class="announcement" role="note"><?= e($announcement) ?></div>
      <?php endif ?>
      <?php if ($flashes): ?>
        <div class="toasts" data-toasts>
          <?php foreach ($flashes as $f): ?><div class="toast toast-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div><?php endforeach ?>
        </div>
      <?php endif ?>
      <?= $content ?>
    </main>
  </div>
</body>
</html>
