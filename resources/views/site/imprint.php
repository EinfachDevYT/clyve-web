<section class="legal">
  <p class="kicker mono">Rechtliches</p>
  <h1>Impressum</h1>
  <p class="todo">Vor dem Livegang mit echten Angaben füllen (§ 5 DDG).</p>

  <h2>Angaben gemäß § 5 DDG</h2>
  <p><?= e(config('business.owner')) ?><br><?= e(config('business.street')) ?><br><?= e(config('business.zip')) ?> <?= e(config('business.city')) ?><br>Deutschland</p>

  <h2>Kontakt</h2>
  <p>E-Mail: <?= e(config('business.email')) ?></p>

  <?php if (config('business.vat_id')): ?>
    <h2>Umsatzsteuer-ID</h2>
    <p><?= e(config('business.vat_id')) ?></p>
  <?php endif ?>

  <h2>Verantwortlich für den Inhalt</h2>
  <p><?= e(config('business.owner')) ?>, Anschrift wie oben</p>
</section>
