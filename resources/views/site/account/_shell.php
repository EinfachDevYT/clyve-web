<?php use App\Core\View; ?>
<div class="shell">
  <aside class="shell-nav glass">
    <div class="shell-user">
      <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
      <div><b><?= e($user['name']) ?></b><small><?= e($user['email']) ?></small></div>
    </div>
    <nav>
      <a href="/konto"<?= View::active('/konto', true) ?>>Übersicht</a>
      <a href="/konto/anfragen"<?= View::active('/konto/anfragen') ?>>Anfragen</a>
      <a href="/konto/tickets"<?= View::active('/konto/tickets') ?>>Support</a>
      <a href="/konto/rechnungen"<?= View::active('/konto/rechnungen') ?>>Rechnungen</a>
      <a href="/konto/profil"<?= View::active('/konto/profil') ?>>Profil</a>
    </nav>
  </aside>
  <section class="shell-main">
    <?= $inner ?>
  </section>
</div>
