<?php use App\Core\View; use App\Services\Notifier; ?>
<header class="page-head">
  <div><p class="kicker mono">Staff</p><h1>Dashboard</h1></div>
  <a class="pill pill-sm" href="/rechnungen/neu">Neue Rechnung</a>
</header>

<div class="stats stats-4">
  <a class="stat glass" href="/anfragen?status=new"><b><?= $stats['requests'] ?></b><span>Neue Anfragen</span></a>
  <a class="stat glass" href="/tickets?status=open"><b><?= $stats['tickets'] ?></b><span>Offene Tickets</span></a>
  <a class="stat glass" href="/kunden"><b><?= $stats['customers'] ?></b><span>Kunden</span></a>
  <a class="stat glass" href="/rechnungen?status=issued"><b><?= money($stats['unpaid']) ?></b><span>Unbezahlt</span></a>
</div>

<div class="grid-2 gap-lg">
  <section class="panel glass">
    <header class="panel-head"><h2>Anfragen</h2><a href="/anfragen">Alle</a></header>
    <?php if (!$requests): ?><p class="empty">Keine offenen Anfragen.</p><?php else: ?>
      <ul class="list">
        <?php foreach ($requests as $r): ?>
          <li><a href="/anfragen/<?= (int)$r['id'] ?>"><b><?= e($r['title'] ?: Notifier::TYPES[$r['type']]) ?></b><small><?= e($r['name']) ?> · <?= date_de($r['created_at'], true) ?></small></a><?= View::badge($r['status']) ?></li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </section>
  <section class="panel glass">
    <header class="panel-head"><h2>Wartende Tickets</h2><a href="/tickets">Alle</a></header>
    <?php if (!$tickets): ?><p class="empty">Alles beantwortet. 🌸</p><?php else: ?>
      <ul class="list">
        <?php foreach ($tickets as $t): ?>
          <li><a href="/tickets/<?= (int)$t['id'] ?>"><b>#<?= (int)$t['id'] ?> <?= e($t['subject']) ?></b><small><?= e($t['customer_name']) ?> · <?= date_de($t['updated_at'], true) ?></small></a><?= View::badge($t['status']) ?></li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </section>
</div>
