<?php ob_start() ?>
<div class="grid-2 gap-lg align-start">
  <form class="panel glass stack" action="/einstellungen" method="post">
    <?= csrf_field() ?>
    <h2>Profil</h2>
    <label class="field<?= invalid('name') ?>"><span>Name</span>
      <input type="text" name="name" maxlength="80" required value="<?= e(old('name', $user['name'])) ?>"><?= error_for('name') ?></label>
    <label class="field"><span>E-Mail <small class="muted">(ändern unter <a href="/einstellungen/sicherheit">Anmeldung &amp; Sicherheit</a>)</small></span>
      <input type="email" value="<?= e($user['email']) ?>" disabled></label>
    <div><button class="pill pill-sm" type="submit">Speichern</button></div>
  </form>
  <section class="panel glass stack">
    <h2>Konto</h2>
    <dl class="kv">
      <dt>Rolle</dt><dd><?= $user['role'] === 'admin' ? 'Admin' : 'Mitarbeiter' ?></dd>
      <dt>Dabei seit</dt><dd><?= date_de($user['created_at']) ?></dd>
      <dt>Letzte Anmeldung</dt><dd><?= $lastLogin ? date_de($lastLogin, true) : '–' ?></dd>
      <dt>Zwei-Faktor</dt><dd><?= $user['has_2fa'] ? '<span class="badge badge-ok">Aktiv</span>' : '<a href="/einstellungen/sicherheit/2fa">Jetzt einrichten →</a>' ?></dd>
    </dl>
  </section>
</div>
<?php App\Core\View::partial('shared/security/_page', ['user' => $user, 'active' => 'profil', 'inner' => ob_get_clean()]) ?>
