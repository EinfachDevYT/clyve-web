<?php ob_start() ?>
<h1>Bestätigung</h1>
<p class="muted">Gib den 6-stelligen Code aus deiner Authenticator-App ein.</p>
<form action="/login/2fa" method="post" class="stack" data-native>
  <?= csrf_field() ?>
  <label class="field<?= invalid('code') ?>"><span>Code</span>
    <input type="text" name="code" class="otp-input mono" inputmode="numeric" autocomplete="one-time-code" maxlength="11" placeholder="123 456" required autofocus><?= error_for('code') ?></label>
  <button class="pill pill-block" type="submit">Bestätigen</button>
</form>
<details class="auth-help">
  <summary>Kein Zugriff auf die App?</summary>
  <p class="muted small">Gib stattdessen einen deiner Wiederherstellungscodes ein (z. B. <span class="mono">abcde-12345</span>) – jeder funktioniert nur einmal.
    Hast du keine mehr, <?= APP_CONTEXT === 'staff' ? 'wende dich an einen Admin.' : 'melde dich bei unserem Support.' ?></p>
</details>
<p class="auth-alt"><a href="/login">Abbrechen</a></p>
<?php App\Core\View::partial('shared/auth/_wrap', ['inner' => ob_get_clean()]) ?>
