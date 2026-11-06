<section class="auth">
  <div class="auth-card glass">
    <img class="auth-mark" src="/assets/brand/clyve-mark.svg" alt="" width="64" height="64">
    <h1>Willkommen zurück</h1>
    <p class="muted">Melde dich an, um deine Projekte, Tickets und Rechnungen zu sehen.</p>

    <form action="/login" method="post" class="stack">
      <?= csrf_field() ?>
      <label class="field<?= invalid('email') ?>">
        <span>E-Mail</span>
        <input type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus>
      </label>
      <label class="field">
        <span>Passwort</span>
        <input type="password" name="password" autocomplete="current-password" required>
      </label>
      <button class="pill pill-block" type="submit">Anmelden</button>
    </form>

    <p class="auth-alt">Noch kein Konto? <a href="/registrieren">Jetzt registrieren</a></p>
  </div>
</section>
