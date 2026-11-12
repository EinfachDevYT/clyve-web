<?php ob_start() ?>
<h1><?= $invite ? 'Willkommen, ' . e(explode(' ', (string)$account['name'])[0]) : 'Neues Passwort' ?></h1>
<p class="muted"><?= $invite ? 'Leg ein Passwort für dein Konto ' . e($account['email']) . ' fest.' : 'Für ' . e($account['email']) . '. Danach wirst du auf allen Geräten abgemeldet.' ?></p>
<form action="/passwort-zuruecksetzen/<?= e($token) ?>" method="post" class="stack" data-native>
  <?= csrf_field() ?>
  <input type="text" name="username" value="<?= e($account['email']) ?>" autocomplete="username" hidden>
  <label class="field<?= invalid('password') ?>"><span>Neues Passwort <small class="muted">(mind. 10 Zeichen)</small></span>
    <input type="password" name="password" minlength="10" autocomplete="new-password" required autofocus><?= error_for('password') ?></label>
  <label class="field<?= invalid('password_confirm') ?>"><span>Wiederholen</span>
    <input type="password" name="password_confirm" autocomplete="new-password" required><?= error_for('password_confirm') ?></label>
  <button class="pill pill-block" type="submit"><?= $invite ? 'Konto einrichten' : 'Passwort speichern' ?></button>
</form>
<?php App\Core\View::partial('shared/auth/_wrap', ['inner' => ob_get_clean()]) ?>
