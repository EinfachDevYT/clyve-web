<?php use App\Core\View; use App\Services\Notifier; use App\Services\RequestService; ?>
<header class="page-head">
  <div><p class="kicker mono">Staff</p><h1>Anfragen</h1></div>
  <form class="search" action="/anfragen" method="get" role="search">
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="ID, Name, Titel, E-Mail, Discord…" aria-label="Suchen">
  </form>
</header>
<?php View::partial('staff/_filters', ['base' => '/anfragen', 'current' => $status, 'options' => ['open' => 'Aktiv', 'new' => 'Neu', 'offer_sent' => 'Angebot offen', 'in_progress' => 'In Arbeit', 'done' => 'Abgeschlossen', 'declined' => 'Abgelehnt', 'all' => 'Alle']]) ?>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>ID</th><th>Projekt</th><th>Kunde</th><th>Art</th><th>Budget</th><th>Aktualisiert</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($requests as $r): ?>
        <tr>
          <td data-label="ID" class="mono"><a href="/anfragen/<?= (int)$r['id'] ?>"><?php if ($r['unread_staff']): ?><span class="unread-dot" title="Ungelesen"></span><?php endif ?><?= e($r['public_id']) ?></a></td>
          <td data-label="Projekt"><a href="/anfragen/<?= (int)$r['id'] ?>"><?= e($r['title'] ?: '–') ?></a></td>
          <td data-label="Kunde"><?= e($r['name']) ?><?= $r['customer_id'] ? ' <span class="badge badge-info">Konto</span>' : '' ?></td>
          <td data-label="Art"><?= e(Notifier::TYPES[$r['type']] ?? '') ?></td>
          <td data-label="Budget"><?= e($r['budget'] ?: '–') ?></td>
          <td data-label="Aktualisiert"><?= date_de($r['updated_at'], true) ?></td>
          <td data-label="Status"><?= View::badge($r['status']) ?><?= $r['status'] === 'in_progress' ? ' <small class="muted">' . (int)$r['progress'] . ' %</small>' : '' ?></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$requests): ?><tr><td colspan="7" class="empty">Keine Anfragen gefunden.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
