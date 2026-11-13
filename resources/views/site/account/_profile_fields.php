<?php
use App\Services\CustomerProfile;

$c ??= [];
$val = static fn(string $k, string $d = ''): string => old($k, (string)($c[$k] ?? $d));
$business = isset($_SESSION['_old']) ? old('business') === '1' : ($c['company'] ?? '') !== '';
$country = $val('country', 'Deutschland');
$countries = CustomerProfile::COUNTRIES;
if (!in_array($country, $countries, true)) {
    array_unshift($countries, $country);
}
?>
<div class="form-grid g-person">
  <label class="field<?= invalid('salutation') ?>"><span>Anrede</span>
    <select name="salutation" required>
      <?php if ($val('salutation') === ''): ?><option value="" disabled selected>Bitte wählen</option><?php endif ?>
      <?php foreach (CustomerProfile::SALUTATIONS as $k => $label): ?>
        <option value="<?= e($k) ?>"<?= $val('salutation') === $k ? ' selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach ?>
    </select><?= error_for('salutation') ?></label>
  <label class="field<?= invalid('first_name') ?>"><span>Vorname</span>
    <input type="text" name="first_name" value="<?= e($val('first_name')) ?>" maxlength="60" autocomplete="given-name" placeholder="Vorname eingeben…" required><?= error_for('first_name') ?></label>
  <label class="field<?= invalid('last_name') ?>"><span>Nachname</span>
    <input type="text" name="last_name" value="<?= e($val('last_name')) ?>" maxlength="60" autocomplete="family-name" placeholder="Nachname eingeben…" required><?= error_for('last_name') ?></label>
</div>

<?= $emailField ?? '' ?>

<label class="field<?= invalid('phone') ?>"><span>Telefonnummer <small class="muted">(optional)</small></span>
  <input type="tel" name="phone" value="<?= e($val('phone')) ?>" maxlength="30" autocomplete="tel" placeholder="Telefonnummer eingeben…"><?= error_for('phone') ?>
  <small class="muted">z. B. +49 30 12345678 oder 030 12345678</small></label>

<label class="consent"><input type="checkbox" name="business" value="1"<?= $business ? ' checked' : '' ?>><span><b>Geschäftskunde?</b></span></label>
<div class="form-grid g-biz biz-fields">
  <label class="field<?= invalid('company') ?>"><span>Firmenname</span>
    <input type="text" name="company" value="<?= e($val('company')) ?>" maxlength="120" autocomplete="organization" placeholder="Firmenname eingeben…"><?= error_for('company') ?></label>
  <label class="field<?= invalid('vat_id') ?>"><span>USt-IdNr. <small class="muted">(optional)</small></span>
    <input type="text" name="vat_id" value="<?= e($val('vat_id')) ?>" maxlength="20" placeholder="z. B. DE123456789"><?= error_for('vat_id') ?></label>
</div>

<h2 class="form-rule">Adresse</h2>
<div class="form-grid g-street">
  <label class="field<?= invalid('street') ?>"><span>Straße</span>
    <input type="text" name="street" value="<?= e($val('street')) ?>" maxlength="100" autocomplete="address-line1" placeholder="Straße eingeben…" required><?= error_for('street') ?></label>
  <label class="field<?= invalid('house_number') ?>"><span>Hausnummer</span>
    <input type="text" name="house_number" value="<?= e($val('house_number')) ?>" maxlength="12" placeholder="Nr." required><?= error_for('house_number') ?></label>
  <label class="field<?= invalid('address_extra') ?>"><span>Zusatz <small class="muted">(optional)</small></span>
    <input type="text" name="address_extra" value="<?= e($val('address_extra')) ?>" maxlength="80" autocomplete="address-line2" placeholder="z. B. 2. OG, c/o"><?= error_for('address_extra') ?></label>
</div>
<div class="form-grid g-city">
  <label class="field<?= invalid('zip') ?>"><span>PLZ</span>
    <input type="text" name="zip" value="<?= e($val('zip')) ?>" maxlength="10" autocomplete="postal-code" placeholder="PLZ eingeben…" required><?= error_for('zip') ?></label>
  <label class="field<?= invalid('city') ?>"><span>Stadt</span>
    <input type="text" name="city" value="<?= e($val('city')) ?>" maxlength="80" autocomplete="address-level2" placeholder="Stadt eingeben…" required><?= error_for('city') ?></label>
  <label class="field<?= invalid('country') ?>"><span>Land</span>
    <select name="country" autocomplete="country-name" required>
      <?php foreach ($countries as $name): ?><option<?= $country === $name ? ' selected' : '' ?>><?= e($name) ?></option><?php endforeach ?>
    </select><?= error_for('country') ?></label>
</div>
