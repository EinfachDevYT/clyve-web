<?php
use App\Services\InvoiceService;
$f = static fn(string $k, string $default = '') => e(old($k, (string)($b[$k] ?? $default)));
$labels = ['name' => 'Name der Website', 'owner' => 'Vor- und Nachname', 'street' => 'Straße', 'zip' => 'PLZ', 'city' => 'Ort', 'email' => 'E-Mail'];
?>
<?php App\Core\View::partial('staff/settings/_tabs', ['tabs' => $tabs, 'active' => 'betreiber']) ?>

<?php if ($missing): ?>
  <div class="notice notice-warn">Impressum und Datenschutz sind noch unvollständig. Es fehlt: <b><?= e(implode(', ', array_map(static fn($m) => $labels[$m], $missing))) ?></b>.</div>
<?php else: ?>
  <div class="notice">Diese Angaben erscheinen im <a href="<?= e(rtrim((string)(config('site_origins')[0] ?? ''), '/')) ?>/impressum" target="_blank" rel="noopener">Impressum</a>, in der <a href="<?= e(rtrim((string)(config('site_origins')[0] ?? ''), '/')) ?>/datenschutz" target="_blank" rel="noopener">Datenschutzerklärung</a> und auf neuen Belegen.</div>
<?php endif ?>

<form class="stack-lg" action="/einstellungen/betreiber" method="post">
  <?= csrf_field() ?>
  <div class="grid-2 gap-lg align-start">
    <section class="panel glass stack">
      <div><h2>Betreiber</h2><p class="muted small">Pflichtangaben für das Impressum (§ 5 DDG). Als Privatperson: dein echter Name und eine ladungsfähige Anschrift.</p></div>
      <label class="field<?= invalid('name') ?>"><span>Name der Website / Marke</span><input type="text" name="name" maxlength="80" required value="<?= $f('name') ?>"><?= error_for('name') ?></label>
      <label class="field<?= invalid('owner') ?>"><span>Vor- und Nachname</span><input type="text" name="owner" maxlength="80" required autocomplete="name" value="<?= $f('owner') ?>"><?= error_for('owner') ?></label>
      <label class="field<?= invalid('street') ?>"><span>Straße und Hausnummer</span><input type="text" name="street" maxlength="120" required autocomplete="street-address" value="<?= $f('street') ?>"><?= error_for('street') ?></label>
      <div class="grid-zip">
        <label class="field<?= invalid('zip') ?>"><span>PLZ</span><input type="text" name="zip" maxlength="10" required autocomplete="postal-code" value="<?= $f('zip') ?>"><?= error_for('zip') ?></label>
        <label class="field<?= invalid('city') ?>"><span>Ort</span><input type="text" name="city" maxlength="80" required autocomplete="address-level2" value="<?= $f('city') ?>"><?= error_for('city') ?></label>
      </div>
      <label class="field<?= invalid('country') ?>"><span>Land</span><input type="text" name="country" maxlength="60" required value="<?= $f('country', 'Deutschland') ?>"><?= error_for('country') ?></label>
      <div class="grid-2">
        <label class="field<?= invalid('email') ?>"><span>E-Mail (öffentlich)</span><input type="email" name="email" maxlength="160" required value="<?= $f('email') ?>"><?= error_for('email') ?></label>
        <label class="field<?= invalid('phone') ?>"><span>Telefon <small class="muted">(optional)</small></span><input type="tel" name="phone" maxlength="30" value="<?= $f('phone') ?>"><?= error_for('phone') ?></label>
      </div>
      <label class="field<?= invalid('hosting') ?>"><span>Hosting-Anbieter <small class="muted">(für die Datenschutzerklärung)</small></span>
        <input type="text" name="hosting" maxlength="200" value="<?= $f('hosting') ?>" placeholder="z. B. netcup GmbH, Daimlerstraße 25, 76185 Karlsruhe"><?= error_for('hosting') ?></label>
    </section>

    <div class="stack-lg">
      <section class="panel glass stack">
        <div><h2>Belege</h2><p class="muted small">Bestimmt, ob du Quittungen (Privatperson) oder Rechnungen ausstellst. Bereits ausgestellte Belege bleiben unverändert.</p></div>
        <div class="radio-cards">
          <?php foreach (InvoiceService::MODES as $key => $label): ?>
            <label class="radio-card"><input type="radio" name="mode" value="<?= e($key) ?>"<?= old('mode', InvoiceService::mode()) === $key ? ' checked' : '' ?>><span><b><?= e($label) ?></b>
              <small><?= ['hobby' => 'Ohne Gewerbe. Angebote auch kostenlos, Belege als Quittung (Q-…).', 'kleinunternehmer' => 'Nach Gewerbeanmeldung, ohne Umsatzsteuer (§ 19 UStG).', 'regular' => 'Rechnungen mit Umsatzsteuer.'][$key] ?></small></span></label>
          <?php endforeach ?>
        </div>
        <?= error_for('mode') ?>
        <div class="grid-2">
          <label class="field<?= invalid('tax_number') ?>"><span>Steuernummer</span><input type="text" name="tax_number" maxlength="30" value="<?= $f('tax_number') ?>"><?= error_for('tax_number') ?></label>
          <label class="field<?= invalid('vat_id') ?>"><span>USt-IdNr.</span><input type="text" name="vat_id" maxlength="20" value="<?= $f('vat_id') ?>" placeholder="DE…"><?= error_for('vat_id') ?></label>
        </div>
        <div class="grid-3">
          <label class="field<?= invalid('invoice_prefix') ?>"><span>Präfix</span><input type="text" name="invoice_prefix" maxlength="6" value="<?= $f('invoice_prefix', 'CLY') ?>"><?= error_for('invoice_prefix') ?></label>
          <label class="field<?= invalid('payment_days') ?>"><span>Zahlungsziel (Tage)</span><input type="number" name="payment_days" min="0" max="90" value="<?= $f('payment_days', '14') ?>"><?= error_for('payment_days') ?></label>
          <label class="field<?= invalid('vat_rate') ?>"><span>USt.-Satz (%)</span><input type="number" name="vat_rate" min="0" max="30" value="<?= $f('vat_rate', '19') ?>"><?= error_for('vat_rate') ?></label>
        </div>
      </section>

      <section class="panel glass stack">
        <div><h2>Bankverbindung</h2><p class="muted small">Erscheint auf Belegen und in offenen Belegen im Kundenbereich.</p></div>
        <label class="field<?= invalid('iban') ?>"><span>IBAN</span><input type="text" name="iban" maxlength="42" value="<?= $f('iban') ?>" class="mono"><?= error_for('iban') ?></label>
        <div class="grid-2">
          <label class="field<?= invalid('bic') ?>"><span>BIC</span><input type="text" name="bic" maxlength="11" value="<?= $f('bic') ?>" class="mono"><?= error_for('bic') ?></label>
          <label class="field<?= invalid('bank') ?>"><span>Bank</span><input type="text" name="bank" maxlength="80" value="<?= $f('bank') ?>"><?= error_for('bank') ?></label>
        </div>
      </section>
    </div>
  </div>
  <div class="row-end"><button class="pill" type="submit">Speichern</button></div>
</form>
