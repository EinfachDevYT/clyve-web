<section class="auth">
  <div class="auth-card glass">
    <img class="auth-lockup" src="/assets/brand/clyve-by-einfachdev-stacked.svg" alt="Clyve.dev – engineered by EinfachDev" width="220" height="170">
    <h1>Team-Login</h1>
    <p class="muted">Interner Bereich. Zugriffe werden protokolliert.</p>
    <form action="/login" method="post" class="stack" data-native>
      <?= csrf_field() ?>
      <label class="field<?= invalid('email') ?>"><span>E-Mail</span>
        <input type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="username" required autofocus></label>
      <label class="field"><span>Passwort</span>
        <input type="password" name="password" autocomplete="current-password" required></label>
      <button class="pill pill-block" type="submit">Anmelden</button>
    </form>
  </div>
</section>
