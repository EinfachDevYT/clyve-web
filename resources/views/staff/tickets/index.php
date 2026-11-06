<?php use App\Core\View; ?>
<header class="page-head"><div><p class="kicker mono">Team</p><h1>Tickets</h1></div></header>
<?php View::partial('staff/_filters', ['base' => '/tickets', 'current' => $status, 'options' => ['open' => 'Offen', 'answered' => 'Beantwortet', 'closed' => 'Geschlossen']]) ?>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>#</th><th>Betreff</th><th>Kunde</th><th>Aktualisiert</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td data-label="#" class="mono"><?= (int)$t['id'] ?></td>
          <td data-label="Betreff"><a href="/tickets/<?= (int)$t['id'] ?>"><?= e($t['subject']) ?></a></td>
          <td data-label="Kunde"><?= e($t['customer_name']) ?></td>
          <td data-label="Aktualisiert"><?= date_de($t['updated_at'], true) ?></td>
          <td data-label="Status"><?= View::badge($t['status']) ?></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$tickets): ?><tr><td colspan="5" class="empty">Keine Tickets.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
