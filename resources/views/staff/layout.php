<?php use App\Core\View; ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="author" content="EinfachDev · einfachdev.de">
  <title><?= e($pageTitle ? $pageTitle . ' · Team' : 'Team · Clyve.dev') ?></title>
  <link rel="icon" href="/assets/img/blossom.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="/assets/js/main.js" defer></script>
</head>
<body class="is-staff">
  <div class="progress" id="progress" aria-hidden="true"></div>
  <?php if (!$user): ?>
    <main id="app" data-swap="main"><?= $content ?></main>
  <?php else: ?>
  <div class="staff">
    <aside class="staff-side" data-swap="nav">
      <a class="brand" href="/"><img src="/assets/img/blossom.svg" alt="" width="24" height="24"><span>Clyve<b>.dev</b> <small class="mono">team</small></span></a>
      <nav class="staff-nav">
        <a href="/"<?= View::active('/') ?>>Dashboard</a>
        <a href="/anfragen"<?= View::active('/anfragen') ?>>Anfragen</a>
        <a href="/tickets"<?= View::active('/tickets') ?>>Tickets</a>
        <a href="/kunden"<?= View::active('/kunden') ?>>Kunden</a>
        <a href="/rechnungen"<?= View::active('/rechnungen') ?>>Rechnungen</a>
      </nav>
      <?php View::partial('shared/signature', ['compact' => true]) ?>
      <div class="staff-me">
        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
        <div><b><?= e($user['name']) ?></b><small><?= $user['role'] === 'admin' ? 'Admin' : 'Mitarbeiter' ?></small></div>
        <form action="/logout" method="post"><?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Abmelden" title="Abmelden">⏻</button></form>
      </div>
    </aside>
    <main id="app" class="staff-main" data-swap="main">
      <?php if ($flashes): ?>
        <div class="toasts" data-toasts>
          <?php foreach ($flashes as $f): ?><div class="toast toast-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div><?php endforeach ?>
        </div>
      <?php endif ?>
      <?= $content ?>
    </main>
  </div>
  <?php endif ?>
</body>
</html>
