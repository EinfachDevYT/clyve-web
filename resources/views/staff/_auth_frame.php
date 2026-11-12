<section class="staff-auth">
  <aside class="staff-auth-brand" aria-hidden="true">
    <svg class="branch staff-branch-a" viewBox="0 0 520 420">
      <path d="M-20 40 C 120 70, 220 40, 300 130 S 400 250, 340 360" />
      <path d="M130 0 C 150 80, 110 150, 210 200" class="thin" />
    </svg>
    <div class="staff-auth-top">
      <img src="/assets/brand/clyve-logo.svg" alt="" width="170" height="40">
      <span class="auth-chip mono">Staff Portal</span>
    </div>
    <div class="staff-auth-copy">
      <h2>Sicher angemeldet.</h2>
      <p>Zwei-Faktor-Anmeldung und protokollierte Zugriffe schützen Kundendaten und Rechnungen.</p>
    </div>
    <div class="staff-auth-foot"><?php App\Core\View::partial('shared/signature') ?></div>
  </aside>
  <div class="staff-auth-main">
    <div class="staff-auth-form">
      <img class="staff-auth-mark" src="/assets/brand/clyve-mark.svg" alt="Clyve.dev" width="52" height="52">
      <?= $inner ?>
    </div>
  </div>
</section>
