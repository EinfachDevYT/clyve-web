<?php $extra = !empty($editable) ? '<td></td>' : ''; ?>
<div class="table-wrap glass">
  <table class="table items">
    <thead><tr><th>Pos.</th><th>Beschreibung</th><th class="num">Menge</th><th class="num">Einzelpreis</th><th class="num">Gesamt</th><?php if (!empty($editable)): ?><th></th><?php endif ?></tr></thead>
    <tbody>
      <?php foreach ($invoice['items'] as $n => $item): ?>
        <tr>
          <td data-label="Pos."><?= $n + 1 ?></td>
          <td data-label="Beschreibung"><?= e($item['description']) ?></td>
          <td data-label="Menge" class="num"><?= e(rtrim(rtrim(number_format((float)$item['quantity'], 2, ',', ''), '0'), ',')) ?> <?= e($item['unit']) ?></td>
          <td data-label="Einzelpreis" class="num"><?= money((int)$item['unit_price_cents']) ?></td>
          <td data-label="Gesamt" class="num"><?= money(\App\Services\InvoiceService::lineTotal($item)) ?></td>
          <?php if (!empty($editable)): ?>
            <td class="num">
              <form action="/rechnungen/<?= (int)$invoice['id'] ?>/positionen/<?= (int)$item['id'] ?>/loeschen" method="post">
                <?= csrf_field() ?><button class="icon-btn" type="submit" aria-label="Position löschen">✕</button>
              </form>
            </td>
          <?php endif ?>
        </tr>
      <?php endforeach ?>
      <?php if (!$invoice['items']): ?>
        <tr><td colspan="6" class="empty">Noch keine Positionen.</td></tr>
      <?php endif ?>
    </tbody>
    <tfoot>
      <?php if (!$invoice['small_business']): ?>
        <tr><td colspan="4">Netto</td><td class="num"><?= money($invoice['net_cents']) ?></td><?= $extra ?></tr>
        <tr><td colspan="4">USt. <?= (int)$invoice['vat_rate'] ?> %</td><td class="num"><?= money($invoice['vat_cents']) ?></td><?= $extra ?></tr>
      <?php endif ?>
      <tr class="total"><td colspan="4">Gesamt</td><td class="num"><?= money($invoice['gross_cents']) ?></td><?= $extra ?></tr>
    </tfoot>
  </table>
</div>
<?php if ($invoice['mode'] === 'hobby'): ?>
  <p class="muted small">Quittung einer Privatperson, keine Umsatzsteuer.</p>
<?php elseif ($invoice['mode'] === 'kleinunternehmer'): ?>
  <p class="muted small">Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.</p>
<?php endif ?>
