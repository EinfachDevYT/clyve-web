<?php
$mode ??= 'login';
$copy = [
    'login'    => ['Dein Projekt im Blick.', 'Alles rund um deinen Auftrag bei Clyve.dev – an einem Ort, jederzeit erreichbar.'],
    'register' => ['Starte dein Projekt mit uns.', 'Ein Konto, alle Anfragen, Tickets und Belege – kostenlos und in unter einer Minute.'],
    'verify'   => ['Fast geschafft.', 'Ein kurzer Klick auf den Link in deinem Postfach – dann ist dein Kundenkonto freigeschaltet.'],
][$mode] ?? ['Dein Projekt im Blick.', ''];
?>
<section class="staff-auth<?= $mode === 'register' ? ' staff-auth-wide' : '' ?>">
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
      <a href="/" tabindex="-1"><img src="/assets/brand/clyve-logo.svg" alt="" width="170" height="40"></a>
      <span class="auth-chip mono">Kundenbereich</span>
    </div>
    <div class="staff-auth-copy">
      <h2><?= e($copy[0]) ?></h2>
      <p><?= e($copy[1]) ?></p>
      <ul class="auth-points">
        <li><span>01</span>Status deiner Anfragen live verfolgen</li>
        <li><span>02</span>Support-Tickets direkt ans Team</li>
        <li><span>03</span>Belege jederzeit als PDF</li>
      </ul>
    </div>
    <div class="staff-auth-foot"><?php App\Core\View::partial('shared/signature') ?></div>
  </aside>
  <div class="staff-auth-main">
    <div class="staff-auth-form">
      <a href="/" class="auth-back">← Zurück zur Website</a>
      <img class="staff-auth-mark" src="/assets/brand/clyve-mark.svg" alt="Clyve.dev" width="52" height="52">
      <?= $inner ?>
    </div>
  </div>
</section>
