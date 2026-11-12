<?php ob_start() ?>
  <form class="panel glass stack narrow" action="/konto/einstellungen" method="post">
    <?= csrf_field() ?>
    <h2>Kontaktdaten &amp; Anschrift</h2>
    <p class="muted small">Die Anschrift wird für Rechnungen benötigt.</p>
    <label class="field<?= invalid('name') ?>"><span>Name</span>
      <input type="text" name="name" value="<?= e(old('name', $user['name'])) ?>" maxlength="80" required><?= error_for('name') ?></label>
    <label class="field"><span>E-Mail <small class="muted">(ändern unter <a href="/konto/einstellungen/sicherheit">Anmeldung &amp; Sicherheit</a>)</small></span>
      <input type="email" value="<?= e($user['email']) ?>" disabled></label>
    <label class="field<?= invalid('discord') ?>"><span>Discord</span>
      <input type="text" name="discord" value="<?= e(old('discord', $user['discord'])) ?>" maxlength="32"><?= error_for('discord') ?></label>
    <label class="field<?= invalid('company') ?>"><span>Firma <small class="muted">(optional)</small></span>
      <input type="text" name="company" value="<?= e(old('company', $user['company'])) ?>" maxlength="120"></label>
    <label class="field<?= invalid('street') ?>"><span>Straße &amp; Nr.</span>
      <input type="text" name="street" value="<?= e(old('street', $user['street'])) ?>" maxlength="120" autocomplete="street-address"></label>
    <div class="grid-zip">
      <label class="field<?= invalid('zip') ?>"><span>PLZ</span>
        <input type="text" name="zip" value="<?= e(old('zip', $user['zip'])) ?>" maxlength="10" autocomplete="postal-code"><?= error_for('zip') ?></label>
      <label class="field<?= invalid('city') ?>"><span>Ort</span>
        <input type="text" name="city" value="<?= e(old('city', $user['city'])) ?>" maxlength="80" autocomplete="address-level2"></label>
    </div>
    <label class="field<?= invalid('country') ?>"><span>Land</span>
      <input type="text" name="country" value="<?= e(old('country', $user['country'])) ?>" maxlength="60"></label>
    <button class="pill" type="submit">Speichern</button>
  </form>
<?php App\Core\View::partial('shared/security/_page', ['user' => $user, 'active' => 'profil', 'inner' => ob_get_clean()]) ?>
