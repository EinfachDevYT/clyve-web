<?php use App\Core\View; use App\Services\Notifier; ?>
<header class="page-head"><div><p class="kicker mono">Staff</p><h1>Anfragen</h1></div></header>
<?php View::partial('staff/_filters', ['base' => '/anfragen', 'current' => $status, 'options' => ['' => 'Alle', 'new' => 'Neu', 'contacted' => 'Kontaktiert', 'accepted' => 'Angenommen', 'declined' => 'Abgelehnt', 'done' => 'Erledigt']]) ?>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>ID</th><th>Projekt</th><th>Name</th><th>Art</th><th>Budget</th><th>Eingang</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($requests as $r): ?>
        <tr>
          <td data-label="ID" class="mono"><a href="/anfragen/<?= (int)$r['id'] ?>"><?= e($r['public_id']) ?></a></td>
          <td data-label="Projekt"><a href="/anfragen/<?= (int)$r['id'] ?>"><?= e($r['title'] ?: '–') ?></a></td>
          <td data-label="Name"><?= e($r['name']) ?></td>
          <td data-label="Art"><?= e(Notifier::TYPES[$r['type']] ?? '') ?></td>
          <td data-label="Budget"><?= e($r['budget'] ?: '–') ?></td>
          <td data-label="Eingang"><?= date_de($r['created_at'], true) ?></td>
          <td data-label="Status"><?= View::badge($r['status']) ?></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$requests): ?><tr><td colspan="7" class="empty">Keine Anfragen.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
