<header class="page-head">
  <div><p class="kicker mono"><a href="/mitarbeiter">Mitarbeiter</a> / Neu</p><h1>Mitarbeiter einladen</h1></div>
</header>
<form class="panel glass stack narrow" action="/mitarbeiter" method="post">
  <?= csrf_field() ?>
  <label class="field<?= invalid('name') ?>"><span>Name</span><input type="text" name="name" maxlength="80" required value="<?= e(old('name')) ?>"><?= error_for('name') ?></label>
  <label class="field<?= invalid('email') ?>"><span>E-Mail</span><input type="email" name="email" maxlength="160" required value="<?= e(old('email')) ?>"><?= error_for('email') ?></label>
  <fieldset class="field<?= invalid('role') ?>"><legend>Rolle</legend>
    <div class="radio-cards">
      <label class="radio-card"><input type="radio" name="role" value="staff"<?= old('role', 'staff') === 'staff' ? ' checked' : '' ?>><span><b>Mitarbeiter</b><small>Anfragen, Tickets, Kunden, Belege, Hosting und Scripts.</small></span></label>
      <label class="radio-card"><input type="radio" name="role" value="admin"<?= old('role') === 'admin' ? ' checked' : '' ?>><span><b>Admin</b><small>Zusätzlich Verwaltung, Mitarbeiter und Löschen.</small></span></label>
    </div><?= error_for('role') ?>
  </fieldset>
  <p class="muted small"><?= $mail ? 'Die Person bekommt per E-Mail einen Link (7 Tage gültig), um ihr Passwort festzulegen. Der Link wird dir außerdem einmal angezeigt.' : 'E-Mail-Versand ist aus: Du bekommst einen Einladungslink (7 Tage gültig), den du sicher weitergibst.' ?></p>
  <div class="row-end"><button class="pill" type="submit">Einladen</button></div>
</form>
