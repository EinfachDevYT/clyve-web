<header class="page-head">
  <div><p class="kicker mono">Staff</p><h1>Scripts</h1></div>
  <div class="actions">
    <?php if ($configured): ?>
      <form action="/scripts/sync" method="post"><?= csrf_field() ?><button class="pill pill-sm pill-ghost" type="submit">Von Tebex synchronisieren</button></form>
    <?php endif ?>
    <a class="pill pill-sm" href="/scripts/neu">Script hinzufügen</a>
  </div>
</header>

<?php if (!$configured): ?>
  <div class="notice">Tebex-Sync ist nicht eingerichtet. Trag in <code>config.php</code> unter <code>tebex</code> deinen Webstore-Identifier und die Shop-Adresse ein – oder pflege die Scripts hier von Hand.</div>
<?php endif ?>
<?php if (!\App\Services\Settings::on('shop_enabled')): ?>
  <div class="notice notice-warn">Der Script-Shop ist in den Einstellungen ausgeschaltet – die Seite /scripts ist gerade nicht erreichbar.</div>
<?php endif ?>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>Script</th><th>Kategorie</th><th>Preis</th><th>Quelle</th><th>Sichtbar</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($products as $pr): ?>
        <tr>
          <td data-label="Script"><a href="/scripts/<?= (int)$pr['id'] ?>"><?= e($pr['title']) ?></a><?= $pr['featured'] ? ' <span class="badge badge-info">Hervorgehoben</span>' : '' ?></td>
          <td data-label="Kategorie"><?= e($pr['category'] ?: '–') ?></td>
          <td data-label="Preis"><?= e($pr['price_label'] ?: '–') ?></td>
          <td data-label="Quelle"><?= $pr['tebex_id'] ? '<span class="badge badge-muted">Tebex</span>' : 'Manuell' ?></td>
          <td data-label="Sichtbar">
            <form action="/scripts/<?= (int)$pr['id'] ?>/toggle" method="post"><?= csrf_field() ?>
              <button class="badge <?= $pr['active'] ? 'badge-ok' : 'badge-muted' ?> badge-btn" type="submit" title="Umschalten"><?= $pr['active'] ? 'Aktiv' : 'Aus' ?></button>
            </form>
          </td>
          <td class="num"><a href="/scripts/<?= (int)$pr['id'] ?>">Bearbeiten</a></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$products): ?><tr><td colspan="6" class="empty">Noch keine Scripts.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
