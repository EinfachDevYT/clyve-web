<?php ob_start() ?>
<section class="panel glass twofa-setup">
  <div class="twofa-qr"><?= $qr ?></div>
  <div class="stack">
    <h2>Zwei-Faktor-Anmeldung einrichten</h2>
    <ol class="steps-list">
      <li>Öffne eine Authenticator-App (z. B. Google Authenticator, Microsoft Authenticator, Aegis, 1Password).</li>
      <li>Scanne den QR-Code. Geht das nicht, gib den Schlüssel von Hand ein:<br><span class="mono secret-key"><?= e($secret) ?></span>
        <button type="button" class="chip-btn" data-copy="<?= e(str_replace(' ', '', $secret)) ?>">Kopieren</button></li>
      <li>Gib den 6-stelligen Code ein, den die App anzeigt.</li>
    </ol>
    <form class="stack" action="<?= e($base) ?>/sicherheit/2fa" method="post">
      <?= csrf_field() ?>
      <label class="field<?= invalid('code') ?>"><span>Code aus der App</span>
        <input type="text" name="code" class="otp-input mono" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="123456" required autofocus><?= error_for('code') ?></label>
      <div class="row-between"><a href="<?= e($base) ?>/sicherheit">Abbrechen</a><button class="pill" type="submit">Aktivieren</button></div>
    </form>
  </div>
</section>
<?php App\Core\View::partial('shared/security/_page', ['user' => $user, 'active' => 'sicherheit', 'inner' => ob_get_clean()]) ?>
