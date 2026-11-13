<?php ob_start() ?>
<nav class="auth-tabs" aria-label="Kundenkonto">
  <a href="/login">Anmelden</a>
  <a href="/registrieren" aria-current="page">Registrieren</a>
</nav>

<h1>Konto erstellen</h1>
<p class="muted"><?= \App\Services\Account::verificationRequired() ? 'Kostenlos – nach der Registrierung bestätigst du kurz deine E-Mail-Adresse.' : 'Kostenlos und in unter einer Minute.' ?></p>

<form action="/registrieren" method="post" class="stack" data-native>
  <?= csrf_field() ?>
  <label class="field<?= invalid('name') ?>"><span>Name</span>
    <input type="text" name="name" value="<?= e(old('name')) ?>" maxlength="80" autocomplete="name" required><?= error_for('name') ?></label>
  <label class="field<?= invalid('email') ?>"><span>E-Mail</span>
    <input type="email" name="email" value="<?= e(old('email')) ?>" maxlength="160" autocomplete="email" placeholder="du@beispiel.de" required><?= error_for('email') ?></label>
  <label class="field<?= invalid('password') ?>"><span>Passwort</span>
    <input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" placeholder="min. 10 Zeichen" required><?= error_for('password') ?></label>
  <label class="field<?= invalid('password_confirm') ?>"><span>Passwort wiederholen</span>
    <input type="password" name="password_confirm" autocomplete="new-password" required><?= error_for('password_confirm') ?></label>
  <label class="consent<?= invalid('consent') ?>">
    <input type="checkbox" name="consent" value="1" required>
    <span>Ich habe die <a href="/datenschutz">Datenschutzerklärung</a> gelesen.</span>
  </label>
  <button class="pill pill-block" type="submit">Konto erstellen</button>
</form>
<?php App\Core\View::partial('site/auth/_frame', ['inner' => ob_get_clean(), 'mode' => 'register']) ?>
