<?php ob_start() ?>
<nav class="auth-tabs" aria-label="Kundenkonto">
  <a href="/login">Anmelden</a>
  <a href="/registrieren" aria-current="page">Registrieren</a>
</nav>

<h1>Konto erstellen</h1>
<p class="muted"><?= \App\Services\Account::verificationRequired() ? 'Kostenlos – nach der Registrierung bestätigst du kurz deine E-Mail-Adresse.' : 'Kostenlos und in wenigen Minuten.' ?></p>

<form action="/registrieren" method="post" class="stack" data-native>
  <?= csrf_field() ?>
  <?php ob_start() ?>
  <label class="field<?= invalid('email') ?>"><span>E-Mail</span>
    <input type="email" name="email" value="<?= e(old('email')) ?>" maxlength="160" autocomplete="email" placeholder="E-Mail eingeben…" required><?= error_for('email') ?></label>
  <?php App\Core\View::partial('site/account/_profile_fields', ['emailField' => ob_get_clean()]) ?>

  <h2 class="form-rule">Passwort</h2>
  <div class="form-grid g-2">
    <label class="field<?= invalid('password') ?>"><span>Passwort</span>
      <span class="pw-wrap"><input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" placeholder="Passwort eingeben…" required><button type="button" class="pw-toggle" data-pw-toggle aria-label="Passwort anzeigen"></button></span><?= error_for('password') ?></label>
    <label class="field<?= invalid('password_confirm') ?>"><span>Passwort bestätigen</span>
      <span class="pw-wrap"><input type="password" name="password_confirm" autocomplete="new-password" placeholder="Passwort wiederholen…" required><button type="button" class="pw-toggle" data-pw-toggle aria-label="Passwort anzeigen"></button></span><?= error_for('password_confirm') ?></label>
  </div>
  <p class="muted small form-hint">Das Passwort muss mindestens 10 Zeichen lang sein.</p>

  <label class="consent<?= invalid('consent') ?>">
    <input type="checkbox" name="consent" value="1" required>
    <span>Ich habe die <a href="/datenschutz">Datenschutzerklärung</a> gelesen.</span>
  </label>
  <button class="pill pill-block" type="submit">Konto erstellen</button>
</form>
<p class="auth-switch muted small">Schon ein Konto? <a href="/login">Jetzt anmelden</a></p>
<?php App\Core\View::partial('site/auth/_frame', ['inner' => ob_get_clean(), 'mode' => 'register']) ?>
