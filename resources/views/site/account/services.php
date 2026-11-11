<?php use App\Core\View; use App\Services\Hosting; ob_start() ?>
<header class="page-head">
  <div><p class="kicker mono">Kundenbereich</p><h1>Meine Dienste</h1></div>
</header>

<?php if (!$services): ?>
  <div class="panel glass"><p class="empty">Du hast noch keine gehosteten Dienste.</p></div>
<?php else: ?>
  <div class="table-wrap glass">
    <table class="table">
      <thead><tr><th>Dienst</th><th>Status</th><th>Läuft bis</th><th class="num">Preis</th></tr></thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td data-label="Dienst"><a href="/konto/dienste/<?= (int)$s['id'] ?>"><?= e($s['name']) ?></a></td>
            <td data-label="Status"><?= Hosting::badge($s) ?></td>
            <td data-label="Läuft bis"><?= $s['expires_on'] ? date_de($s['expires_on']) : 'Unbefristet' ?></td>
            <td data-label="Preis" class="num"><?= e(Hosting::priceLabel($s)) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
<?php endif ?>
<?php View::partial('site/account/_shell', ['user' => $user, 'inner' => ob_get_clean()]) ?>
