<header class="page-head">
  <div><p class="kicker mono">Staff</p><h1>Einstellungen</h1></div>
</header>
<nav class="settings-tabs" aria-label="Einstellungen">
  <?php foreach ($tabs as $key => [$url, $label]): ?>
    <a href="<?= e($url) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach ?>
</nav>
