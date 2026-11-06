<?php use App\Core\View; ob_start() ?>
<header class="page-head">
  <div><p class="kicker mono">Kundenbereich</p><h1>Profil</h1></div>
</header>

<div class="grid-2 gap-lg align-start">
  <form class="panel glass stack" action="/konto/profil" method="post">
    <?= csrf_field() ?>
    <h2>Kontaktdaten &amp; Anschrift</h2>
    <p class="muted small">Die Anschrift wird für Rechnungen benötigt.</p>
    <label class="field<?= invalid('name') ?>"><span>Name</span>
      <input type="text" name="name" value="<?= e(old('name', $user['name'])) ?>" maxlength="80" required><?= error_for('name') ?></label>
    <label class="field"><span>E-Mail</span>
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

  <form class="panel glass stack" action="/konto/passwort" method="post">
    <?= csrf_field() ?>
    <h2>Passwort ändern</h2>
    <label class="field<?= invalid('password_current') ?>"><span>Aktuelles Passwort</span>
      <input type="password" name="password_current" autocomplete="current-password" required><?= error_for('password_current') ?></label>
    <label class="field<?= invalid('password') ?>"><span>Neues Passwort</span>
      <input type="password" name="password" minlength="10" autocomplete="new-password" required><?= error_for('password') ?></label>
    <label class="field<?= invalid('password_confirm') ?>"><span>Wiederholen</span>
      <input type="password" name="password_confirm" autocomplete="new-password" required><?= error_for('password_confirm') ?></label>
    <button class="pill pill-ghost" type="submit">Passwort ändern</button>
  </form>
</div>
<?php View::partial('site/account/_shell', ['user' => $user, 'inner' => ob_get_clean()]) ?>
