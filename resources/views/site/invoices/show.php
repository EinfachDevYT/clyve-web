<?php use App\Core\View; ?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/konto/rechnungen">Rechnungen</a> / <?= e($invoice['number']) ?></p>
    <h1><?= e($invoice['title'] ?: 'Rechnung ' . $invoice['number']) ?></h1>
    <p class="meta"><?= View::badge($invoice['status']) ?> <span>vom <?= date_de($invoice['issue_date']) ?></span> <span>fällig <?= date_de($invoice['due_date']) ?></span></p>
  </div>
  <a class="pill pill-sm" href="/konto/rechnungen/<?= (int)$invoice['id'] ?>/druck" target="_blank" rel="noopener" data-native>PDF / Drucken</a>
</header>

<?php View::partial('shared/invoice-items', ['invoice' => $invoice]) ?>

<?php if ($invoice['status'] === 'issued' && !empty($invoice['sender']['iban'])): ?>
  <section class="panel glass pay">
    <h2>Zahlung</h2>
    <dl class="kv">
      <dt>Empfänger</dt><dd><?= e($invoice['sender']['owner']) ?></dd>
      <dt>IBAN</dt><dd class="mono"><?= e($invoice['sender']['iban']) ?></dd>
      <?php if ($invoice['sender']['bic']): ?><dt>BIC</dt><dd class="mono"><?= e($invoice['sender']['bic']) ?></dd><?php endif ?>
      <dt>Verwendungszweck</dt><dd class="mono"><?= e($invoice['number']) ?></dd>
      <dt>Betrag</dt><dd><b><?= money($invoice['gross_cents']) ?></b></dd>
    </dl>
  </section>
<?php endif ?>
