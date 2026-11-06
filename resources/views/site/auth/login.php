<section class="cust-auth">
  <div class="cust-auth-panel">
    <?php App\Core\View::partial('site/auth/_aside', ['mode' => 'login']) ?>

    <div class="cust-auth-main">
      <nav class="auth-tabs" aria-label="Kundenkonto">
        <a href="/login" aria-current="page">Anmelden</a>
        <a href="/registrieren">Registrieren</a>
      </nav>

      <h1>Willkommen zurück</h1>
      <p class="muted">Melde dich mit deinem Kundenkonto an.</p>

      <form action="/login" method="post" class="stack">
        <?= csrf_field() ?>
        <label class="field<?= invalid('email') ?>"><span>E-Mail</span>
          <input type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" placeholder="du@beispiel.de" required autofocus></label>
        <label class="field"><span>Passwort</span>
          <input type="password" name="password" autocomplete="current-password" placeholder="••••••••••" required></label>
        <button class="pill pill-block" type="submit">Anmelden</button>
      </form>

      <p class="team-secure">
        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 11V8a6 6 0 0 1 12 0v3M5 11h14v10H5z"/></svg>
        Verschlüsselte Verbindung · Kein Tracking
      </p>
    </div>
  </div>
</section>
