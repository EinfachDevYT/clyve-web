<?php use App\Core\View; ?>
<header class="page-head">
  <div><p class="kicker mono">Kundenbereich</p><h1>Rechnungen</h1></div>
</header>

<?php if (!$invoices): ?>
  <div class="panel glass"><p class="empty">Noch keine Rechnungen vorhanden.</p></div>
<?php else: ?>
  <div class="table-wrap glass">
    <table class="table">
      <thead><tr><th>Nummer</th><th>Projekt</th><th>Datum</th><th>Fällig</th><th class="num">Betrag</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($invoices as $i): ?>
          <tr>
            <td data-label="Nummer" class="mono"><a href="/konto/rechnungen/<?= (int)$i['id'] ?>"><?= e($i['number']) ?></a></td>
            <td data-label="Projekt"><?= e($i['title'] ?: '–') ?></td>
            <td data-label="Datum"><?= date_de($i['issue_date']) ?></td>
            <td data-label="Fällig"><?= date_de($i['due_date']) ?></td>
            <td data-label="Betrag" class="num"><?= money((int)$i['total_cents']) ?></td>
            <td data-label="Status"><?= View::badge($i['status']) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
<?php endif ?>
