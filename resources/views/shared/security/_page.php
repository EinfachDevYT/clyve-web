<?php
$base = APP_CONTEXT === 'staff' ? '/einstellungen' : '/konto/einstellungen';
$tabs = ['profil' => [$base, 'Profil'], 'sicherheit' => [$base . '/sicherheit', 'Anmeldung & Sicherheit']];
ob_start();
?>
<header class="page-head">
  <div><p class="kicker mono"><?= APP_CONTEXT === 'staff' ? 'Persönlich' : 'Kundenbereich' ?></p><h1>Einstellungen</h1></div>
</header>
<nav class="settings-tabs" aria-label="Einstellungen">
  <?php foreach ($tabs as $key => [$url, $label]): ?>
    <a href="<?= e($url) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach ?>
</nav>
<?= $inner ?>
<?php
$page = ob_get_clean();
if (APP_CONTEXT === 'staff') {
    echo $page;
} else {
    App\Core\View::partial('site/account/_shell', ['user' => $user, 'inner' => $page]);
}
