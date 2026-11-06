<?php use App\Core\View; ?>
<header class="page-head">
  <div><p class="kicker mono">Staff</p><h1>Rechnungen</h1></div>
  <a class="pill pill-sm" href="/rechnungen/neu">Neue Rechnung</a>
</header>

<?php if (!$canIssue): ?>
  <div class="notice">Kein Gewerbe hinterlegt: Rechnungen können vorbereitet, aber noch <b>nicht ausgestellt</b> werden. Nach der Gewerbeanmeldung <code>business.registered</code> in der Config auf <code>true</code> setzen.</div>
<?php endif ?>

<?php View::partial('staff/_filters', ['base' => '/rechnungen', 'current' => $status, 'options' => ['' => 'Alle', 'draft' => 'Entwürfe', 'issued' => 'Offen', 'paid' => 'Bezahlt', 'cancelled' => 'Storniert']]) ?>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>Nummer</th><th>Kunde</th><th>Titel</th><th>Datum</th><th class="num">Betrag</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($invoices as $i): ?>
        <tr>
          <td data-label="Nummer" class="mono"><a href="/rechnungen/<?= (int)$i['id'] ?>"><?= e($i['number'] ?? 'Entwurf #' . $i['id']) ?></a></td>
          <td data-label="Kunde"><?= e($i['customer_name']) ?></td>
          <td data-label="Titel"><?= e($i['title'] ?: '–') ?></td>
          <td data-label="Datum"><?= date_de($i['issue_date'] ?? $i['created_at']) ?></td>
          <td data-label="Betrag" class="num"><?= $i['status'] === 'draft' ? '–' : money((int)$i['total_cents']) ?></td>
          <td data-label="Status"><?= View::badge($i['status']) ?></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$invoices): ?><tr><td colspan="6" class="empty">Keine Rechnungen.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
