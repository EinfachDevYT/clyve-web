<?php
$isDraft = $invoice['status'] === 'draft';
$s = $invoice['sender'] ?? [
    'name' => config('business.name'), 'owner' => config('business.owner'), 'street' => config('business.street'),
    'zip' => config('business.zip'), 'city' => config('business.city'), 'email' => config('business.email'),
    'tax_number' => config('business.tax_number'), 'vat_id' => config('business.vat_id'),
    'iban' => config('business.iban'), 'bic' => config('business.bic'), 'bank' => config('business.bank'),
];
$r = $invoice['recipient'] ?? ['name' => $invoice['customer_name'], 'company' => '', 'street' => '', 'zip' => '', 'city' => '', 'country' => ''];
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <meta name="author" content="EinfachDev · einfachdev.de">
  <title><?= e($invoice['number'] ?? 'Entwurf') ?> · Rechnung</title>
  <link rel="stylesheet" href="/assets/css/invoice.css">
  <script src="/assets/js/print.js" defer></script>
</head>
<body>
  <div class="toolbar">
    <button type="button" data-print>Drucken / Als PDF speichern</button>
  </div>

  <article class="paper<?= $isDraft ? ' is-draft' : '' ?><?= $invoice['status'] === 'cancelled' ? ' is-cancelled' : '' ?>">
    <?php if ($isDraft): ?><div class="watermark">ENTWURF</div><?php endif ?>
    <?php if ($invoice['status'] === 'cancelled'): ?><div class="watermark">STORNIERT</div><?php endif ?>

    <header class="head">
      <div class="brand">
        <img src="/assets/img/blossom.svg" alt="" width="34" height="34">
        <div><b><?= e($s['name']) ?></b><small><?= e($s['owner']) ?></small></div>
      </div>
      <div class="sender-line"><?= e($s['owner']) ?> · <?= e($s['street']) ?> · <?= e($s['zip']) ?> <?= e($s['city']) ?></div>
    </header>

    <section class="addr">
      <div class="recipient">
        <?php if ($r['company']): ?><?= e($r['company']) ?><br><?php endif ?>
        <?= e($r['name']) ?><br>
        <?= e($r['street']) ?><br>
        <?= e($r['zip']) ?> <?= e($r['city']) ?>
        <?php if ($r['country'] && $r['country'] !== 'Deutschland'): ?><br><?= e($r['country']) ?><?php endif ?>
      </div>
      <dl class="facts">
        <dt>Rechnungsnr.</dt><dd><?= e($invoice['number'] ?? '—') ?></dd>
        <dt>Rechnungsdatum</dt><dd><?= date_de($invoice['issue_date']) ?></dd>
        <dt>Leistungsdatum</dt><dd><?= e($invoice['service_date'] ?: '—') ?></dd>
        <dt>Kundennr.</dt><dd><?= sprintf('K-%05d', (int)$invoice['customer_id']) ?></dd>
      </dl>
    </section>

    <h1>Rechnung<?= $invoice['title'] ? ' – ' . e($invoice['title']) : '' ?></h1>

    <table class="items">
      <thead><tr><th>Pos.</th><th>Beschreibung</th><th class="num">Menge</th><th class="num">Einzelpreis</th><th class="num">Gesamt</th></tr></thead>
      <tbody>
        <?php foreach ($invoice['items'] as $n => $item): ?>
          <tr>
            <td><?= $n + 1 ?></td>
            <td><?= e($item['description']) ?></td>
            <td class="num"><?= e(rtrim(rtrim(number_format((float)$item['quantity'], 2, ',', ''), '0'), ',')) ?> <?= e($item['unit']) ?></td>
            <td class="num"><?= money((int)$item['unit_price_cents']) ?></td>
            <td class="num"><?= money(\App\Services\InvoiceService::lineTotal($item)) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
      <tfoot>
        <?php if (!$invoice['small_business']): ?>
          <tr><td colspan="4">Nettobetrag</td><td class="num"><?= money($invoice['net_cents']) ?></td></tr>
          <tr><td colspan="4">zzgl. <?= (int)$invoice['vat_rate'] ?> % USt.</td><td class="num"><?= money($invoice['vat_cents']) ?></td></tr>
        <?php endif ?>
        <tr class="total"><td colspan="4">Rechnungsbetrag</td><td class="num"><?= money($invoice['gross_cents']) ?></td></tr>
      </tfoot>
    </table>

    <?php if ($invoice['small_business']): ?>
      <p class="note">Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.</p>
    <?php endif ?>
    <?php if ($invoice['notes']): ?><p class="note"><?= nl2p($invoice['notes']) ?></p><?php endif ?>
    <?php if (!$isDraft && $invoice['status'] !== 'cancelled'): ?>
      <p class="note">Bitte überweise den Betrag bis zum <b><?= date_de($invoice['due_date']) ?></b> unter Angabe der Rechnungsnummer.</p>
    <?php endif ?>

    <footer class="foot">
      <div><b><?= e($s['name']) ?></b><br><?= e($s['owner']) ?><br><?= e($s['street']) ?><br><?= e($s['zip']) ?> <?= e($s['city']) ?></div>
      <div><?= e($s['email']) ?><?php if ($s['tax_number']): ?><br>St.-Nr.: <?= e($s['tax_number']) ?><?php endif ?><?php if ($s['vat_id']): ?><br>USt-IdNr.: <?= e($s['vat_id']) ?><?php endif ?></div>
      <div><?php if ($s['iban']): ?><?= e($s['bank']) ?><br>IBAN: <?= e($s['iban']) ?><?php if ($s['bic']): ?><br>BIC: <?= e($s['bic']) ?><?php endif ?><?php endif ?></div>
    </footer>
  </article>
</body>
</html>
