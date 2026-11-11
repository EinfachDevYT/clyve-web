<?php use App\Core\View; use App\Services\Notifier; ob_start() ?>
<header class="page-head">
  <div>
    <p class="kicker mono">Kundenbereich</p>
    <h1>Hey <?= e(explode(' ', $user['name'])[0]) ?> 🌸</h1>
  </div>
  <?php if (\App\Services\Settings::on('tickets_enabled')): ?><a class="pill pill-sm" href="/konto/tickets/neu">Neues Ticket</a><?php endif ?>
</header>

<div class="stats">
  <a class="stat glass" href="/konto/tickets"><b><?= $openTickets ?></b><span>Offene Tickets</span></a>
  <a class="stat glass" href="/konto/rechnungen"><b><?= $openInvoices ?></b><span>Offene Rechnungen</span></a>
  <a class="stat glass" href="/konto/rechnungen"><b><?= money($openAmount) ?></b><span>Offener Betrag</span></a>
</div>

<div class="grid-2 gap-lg">
  <section class="panel glass">
    <header class="panel-head"><h2>Letzte Anfragen</h2><a href="/konto/anfragen">Alle</a></header>
    <?php if (!$requests): ?>
      <p class="empty">Noch keine Anfragen. <a href="/#anfrage">Projekt anfragen →</a></p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($requests as $r): ?>
          <li><a href="/konto/anfragen/<?= (int)$r['id'] ?>"><b><?php if ($r['unread_customer']): ?><span class="unread-dot"></span><?php endif ?><?= e($r['title'] ?: Notifier::TYPES[$r['type']]) ?></b><small><?= date_de($r['updated_at'], true) ?></small></a><?= View::badge($r['status']) ?></li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </section>

  <section class="panel glass">
    <header class="panel-head"><h2>Support</h2><a href="/konto/tickets">Alle</a></header>
    <?php if (!$tickets): ?>
      <p class="empty">Keine Tickets vorhanden.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($tickets as $t): ?>
          <li><a href="/konto/tickets/<?= (int)$t['id'] ?>"><b>#<?= (int)$t['id'] ?> <?= e($t['subject']) ?></b><small><?= date_de($t['updated_at'], true) ?></small></a><?= View::badge($t['status']) ?></li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </section>
</div>
<?php View::partial('site/account/_shell', ['user' => $user, 'inner' => ob_get_clean()]) ?>
