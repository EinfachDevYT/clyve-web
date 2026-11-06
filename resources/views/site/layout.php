<?php use App\Core\View; ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ? $pageTitle . ' · Clyve.dev' : 'Clyve.dev') ?></title>
  <meta name="description" content="FiveM Scripts, Websites & Discord Bots nach Maß – sauber, performant, aus dem Saarland.">
  <meta name="theme-color" content="#0D0B14">
  <meta name="author" content="EinfachDev · einfachdev.de">
  <link rel="author" href="/humans.txt">
  <link rel="icon" href="/assets/brand/clyve-icon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="/assets/js/main.js" defer></script>
</head>
<body>
  <canvas id="petals" aria-hidden="true"></canvas>
  <div class="progress" id="progress" aria-hidden="true"></div>

  <header class="nav" data-swap="nav">
    <a class="brand" href="/" aria-label="Clyve.dev Startseite">
      <img src="/assets/brand/clyve-logo.svg" alt="Clyve.dev" class="brand-logo" width="162" height="38">
    </a>
    <nav class="nav-links" id="navLinks">
      <a href="/#leistungen">Leistungen</a>
      <a href="/#ablauf">Ablauf</a>
      <a href="/#faq">FAQ</a>
      <?php if ($user): ?>
        <a href="/konto"<?= View::active('/konto') ?>>Mein Konto</a>
        <form action="/logout" method="post" class="inline-form">
          <?= csrf_field() ?>
          <button type="submit" class="link-btn">Abmelden</button>
        </form>
      <?php else: ?>
        <a href="/login"<?= View::active('/login') ?>>Kundenlogin</a>
      <?php endif ?>
      <a class="pill pill-sm" href="/#anfrage">Auftrag anfragen</a>
    </nav>
    <button class="nav-toggle" id="navToggle" aria-label="Menü" aria-expanded="false"><span></span><span></span></button>
  </header>

  <main id="app" data-swap="main">
    <?php if ($flashes): ?>
      <div class="toasts" data-toasts>
        <?php foreach ($flashes as $f): ?>
          <div class="toast toast-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
    <?= $content ?>
  </main>

  <footer class="footer">
    <svg class="branch branch-f" viewBox="0 0 1200 140" preserveAspectRatio="none" aria-hidden="true">
      <path d="M0 110 C 260 60, 420 120, 620 80 S 980 40, 1200 90" />
    </svg>
    <div class="footer-inner">
      <a class="brand" href="/" aria-label="Clyve.dev"><img src="/assets/brand/clyve-logo.svg" alt="Clyve.dev" class="brand-logo brand-logo-lg" width="170" height="40"></a>
      <p class="mono muted">Danke fürs Vorbeischauen — crafted in the void</p>
      <nav class="footer-links">
        <a href="https://github.com/EinfachDevYT" target="_blank" rel="noopener">GitHub</a>
        <a href="https://discord.com/users/267311011520970752" target="_blank" rel="noopener">Discord</a>
        <a href="/impressum">Impressum</a>
        <a href="/datenschutz">Datenschutz</a>
      </nav>
    </div>
    <div class="footer-bar">
      <span class="mono muted">© <?= date('Y') ?> Clyve.dev · Alle Rechte vorbehalten</span>
      <?php App\Core\View::partial('shared/signature') ?>
    </div>
  </footer>
</body>
</html>
