<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="author" content="EinfachDev · einfachdev.de">
  <title><?= e($pageTitle) ?> · Clyve.dev</title>
  <link rel="icon" href="/assets/brand/clyve-icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="/assets/js/main.js" defer></script>
</head>
<body class="is-staff">
  <canvas id="petals" aria-hidden="true"></canvas>
  <main id="app" data-swap="main">
    <?php if ($flashes): ?>
      <div class="toasts" data-toasts>
        <?php foreach ($flashes as $f): ?><div class="toast toast-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div><?php endforeach ?>
      </div>
    <?php endif ?>
    <?= $content ?>
  </main>
</body>
</html>
