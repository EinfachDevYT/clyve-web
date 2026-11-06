<section class="auth">
  <div class="auth-card glass">
    <img class="auth-mark" src="/assets/brand/clyve-mark.svg" alt="" width="64" height="64">
    <h1>Konto erstellen</h1>
    <p class="muted">Behalte den Überblick über Anfragen, Support und Rechnungen.</p>

    <form action="/registrieren" method="post" class="stack">
      <?= csrf_field() ?>
      <label class="field<?= invalid('name') ?>">
        <span>Name</span>
        <input type="text" name="name" value="<?= e(old('name')) ?>" maxlength="80" autocomplete="name" required>
        <?= error_for('name') ?>
      </label>
      <label class="field<?= invalid('email') ?>">
        <span>E-Mail</span>
        <input type="email" name="email" value="<?= e(old('email')) ?>" maxlength="160" autocomplete="email" required>
        <?= error_for('email') ?>
      </label>
      <label class="field<?= invalid('password') ?>">
        <span>Passwort <small class="muted">(min. 10 Zeichen)</small></span>
        <input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" required>
        <?= error_for('password') ?>
      </label>
      <label class="field<?= invalid('password_confirm') ?>">
        <span>Passwort wiederholen</span>
        <input type="password" name="password_confirm" autocomplete="new-password" required>
        <?= error_for('password_confirm') ?>
      </label>
      <label class="consent<?= invalid('consent') ?>">
        <input type="checkbox" name="consent" value="1" required>
        <span>Ich habe die <a href="/datenschutz">Datenschutzerklärung</a> gelesen.</span>
      </label>
      <button class="pill pill-block" type="submit">Konto erstellen</button>
    </form>

    <p class="auth-alt">Schon registriert? <a href="/login">Anmelden</a></p>
  </div>
</section>
