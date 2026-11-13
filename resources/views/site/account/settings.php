<?php ob_start() ?>
  <form class="panel glass stack" action="/konto/einstellungen" method="post">
    <?= csrf_field() ?>
    <h2>Persönliche Daten</h2>
    <p class="muted small">Name und Anschrift erscheinen auf deinen Rechnungen.</p>
    <?php ob_start() ?>
    <div class="form-grid g-2">
      <label class="field"><span>E-Mail <small class="muted">(ändern unter <a href="/konto/einstellungen/sicherheit">Anmeldung &amp; Sicherheit</a>)</small></span>
        <input type="email" value="<?= e($user['email']) ?>" disabled></label>
      <label class="field<?= invalid('discord') ?>"><span>Discord <small class="muted">(optional)</small></span>
        <input type="text" name="discord" value="<?= e(old('discord', $user['discord'])) ?>" maxlength="32"><?= error_for('discord') ?></label>
    </div>
    <?php App\Core\View::partial('site/account/_profile_fields', ['c' => $user, 'emailField' => ob_get_clean()]) ?>
    <div><button class="pill" type="submit">Speichern</button></div>
  </form>
<?php App\Core\View::partial('shared/security/_page', ['user' => $user, 'active' => 'profil', 'inner' => ob_get_clean()]) ?>
