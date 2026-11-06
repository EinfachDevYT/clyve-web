<section class="staff-auth">
  <aside class="staff-auth-brand" aria-hidden="true">
    <svg class="branch staff-branch-a" viewBox="0 0 520 420">
      <path d="M-20 40 C 120 70, 220 40, 300 130 S 400 250, 340 360" />
      <path d="M130 0 C 150 80, 110 150, 210 200" class="thin" />
      <path d="M300 130 C 360 110, 420 120, 480 80" class="thin" />
    </svg>
    <svg class="branch staff-branch-b" viewBox="0 0 520 420">
      <path d="M560 400 C 430 360, 360 300, 300 220 S 220 110, 270 10" />
      <path d="M300 220 C 240 240, 180 230, 110 280" class="thin" />
    </svg>

    <div class="staff-auth-top">
      <img src="/assets/brand/clyve-logo.svg" alt="" width="170" height="40">
      <span class="auth-chip mono">Staff Portal</span>
    </div>

    <div class="staff-auth-copy">
      <h2>Alles an einem Ort.</h2>
      <p>Anfragen, Support und Rechnungen von Clyve.dev – übersichtlich in einem Dashboard.</p>
      <ul class="auth-points">
        <li><span>01</span>Neue Anfragen sofort im Blick</li>
        <li><span>02</span>Tickets direkt beantworten</li>
        <li><span>03</span>Rechnungen erstellen &amp; verwalten</li>
      </ul>
    </div>

    <div class="staff-auth-foot">
      <?php App\Core\View::partial('shared/signature') ?>
    </div>
  </aside>

  <div class="staff-auth-main">
    <div class="staff-auth-form">
      <img class="staff-auth-mark" src="/assets/brand/clyve-mark.svg" alt="Clyve.dev" width="52" height="52">
      <h1>Anmelden</h1>
      <p class="muted">Nur für Mitarbeiter von Clyve.dev.</p>

      <form action="/login" method="post" class="stack" data-native>
        <?= csrf_field() ?>
        <label class="field<?= invalid('email') ?>"><span>E-Mail</span>
          <input type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="username" placeholder="name@clyve.dev" required autofocus></label>
        <label class="field"><span>Passwort</span>
          <input type="password" name="password" autocomplete="current-password" placeholder="••••••••••" required></label>
        <button class="pill pill-block" type="submit">Anmelden</button>
      </form>

      <p class="auth-secure">
        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 11V8a6 6 0 0 1 12 0v3M5 11h14v10H5z"/></svg>
        Verschlüsselte Verbindung · Anmeldungen werden protokolliert
      </p>
    </div>
  </div>
</section>
