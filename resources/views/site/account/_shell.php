<?php
use App\Core\Db;
use App\Core\View;
$hasServices = \App\Services\Settings::on('hosting_customer_view') && Db::value('SELECT 1 FROM services WHERE customer_id = ? LIMIT 1', [$user['id']]);
?>
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
      <?php if ($hasServices): ?><a href="/konto/dienste"<?= View::active('/konto/dienste') ?>>Dienste</a><?php endif ?>
      <a href="/konto/rechnungen"<?= View::active('/konto/rechnungen') ?>>Rechnungen</a>
      <a href="/konto/einstellungen"<?= View::active('/konto/einstellungen') ?>>Einstellungen</a>
    </nav>
  </aside>
  <section class="shell-main">
    <?= $inner ?>
  </section>
</div>
