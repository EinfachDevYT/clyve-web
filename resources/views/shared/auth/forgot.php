<?php ob_start() ?>
<h1>Passwort vergessen</h1>
<?php if ($mail): ?>
  <p class="muted">Gib die E-Mail-Adresse deines Kontos ein. Wir schicken dir einen Link, mit dem du ein neues Passwort festlegst.</p>
  <form action="/passwort-vergessen" method="post" class="stack" data-native>
    <?= csrf_field() ?>
    <label class="field<?= invalid('email') ?>"><span>E-Mail</span>
      <input type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus><?= error_for('email') ?></label>
    <button class="pill pill-block" type="submit">Link senden</button>
  </form>
<?php else: ?>
  <p class="muted"><?= APP_CONTEXT === 'staff'
    ? 'Bitte einen Admin, dir unter Mitarbeiter einen neuen Link zu erstellen.'
    : 'Schreib uns kurz über Discord oder per E-Mail an ' . e((string)config('business.email', '')) . ' – wir schicken dir einen Link zum Zurücksetzen.' ?></p>
<?php endif ?>
<p class="auth-alt"><a href="/login">Zurück zur Anmeldung</a></p>
<?php App\Core\View::partial('shared/auth/_wrap', ['inner' => ob_get_clean()]) ?>
