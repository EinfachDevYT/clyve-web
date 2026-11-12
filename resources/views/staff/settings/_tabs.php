<header class="page-head">
  <div><p class="kicker mono">Verwaltung</p><h1>Website &amp; System</h1></div>
</header>
<nav class="settings-tabs" aria-label="Verwaltung">
  <?php foreach ($tabs as $key => [$url, $label]): ?>
    <a href="<?= e($url) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach ?>
</nav>
