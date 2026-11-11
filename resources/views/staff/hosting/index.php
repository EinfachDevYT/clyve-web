<?php use App\Core\View; use App\Controllers\Staff\HostingController; use App\Services\Hosting; ?>
<header class="page-head">
  <div><p class="kicker mono">Staff</p><h1>Hosting</h1></div>
  <div class="actions">
    <?php if ($user['role'] === 'admin'): ?><a class="pill pill-sm pill-ghost" href="/hosting/tarife">Tarife</a><?php endif ?>
    <a class="pill pill-sm" href="/hosting/neu">Dienst anlegen</a>
  </div>
</header>

<div class="stats">
  <a class="stat glass" href="/hosting?status=aktiv"><b><?= $counts['aktiv'] ?></b><span>Aktive Dienste</span></a>
  <a class="stat glass" href="/hosting?status=bald"><b><?= $counts['bald'] ?></b><span>Laufen in 14 Tagen ab</span></a>
  <a class="stat glass" href="/hosting?status=gesperrt"><b><?= $counts['gesperrt'] ?></b><span>Gesperrt</span></a>
</div>

<?php View::partial('staff/_filters', ['base' => '/hosting', 'current' => $filter, 'options' => HostingController::FILTERS]) ?>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>Dienst</th><th>Kunde</th><th>Status</th><th>Läuft bis</th><th>Letzte Prüfung</th><th class="num">Preis</th></tr></thead>
    <tbody>
      <?php foreach ($services as $s): $left = Hosting::daysLeft($s); ?>
        <tr>
          <td data-label="Dienst"><a href="/hosting/<?= (int)$s['id'] ?>"><?= e($s['name']) ?></a></td>
          <td data-label="Kunde"><a href="/kunden/<?= (int)$s['customer_id'] ?>"><?= e($s['customer_name']) ?></a></td>
          <td data-label="Status"><?= Hosting::badge($s) ?></td>
          <td data-label="Läuft bis"><?= $s['expires_on'] ? date_de($s['expires_on']) . ($s['status'] === 'active' && $left !== null ? ' <small class="muted">(' . ($left >= 0 ? 'noch ' . $left . ' T.' : 'seit ' . -$left . ' T.') . ')</small>' : '') : 'Unbefristet' ?></td>
          <td data-label="Letzte Prüfung"><?= $s['last_check_at'] ? date_de($s['last_check_at'], true) : '<span class="muted">noch nie</span>' ?></td>
          <td data-label="Preis" class="num"><?= e(Hosting::priceLabel($s)) ?></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$services): ?><tr><td colspan="6" class="empty">Keine Dienste in dieser Ansicht.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
