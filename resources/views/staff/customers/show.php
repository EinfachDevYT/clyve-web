<?php use App\Core\View; use App\Services\Notifier; ?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/kunden">Kunden</a> / <?= sprintf('K-%05d', (int)$customer['id']) ?></p>
    <h1><?= e($customer['name']) ?></h1>
  </div>
  <div class="actions">
    <a class="pill pill-sm pill-ghost" href="/hosting/neu?kunde=<?= (int)$customer['id'] ?>">Dienst anlegen</a>
    <a class="pill pill-sm" href="/rechnungen/neu?customer=<?= (int)$customer['id'] ?>">Rechnung erstellen</a>
  </div>
</header>

<div class="grid-side">
  <div class="stack-lg">
    <?php if ($services): ?>
    <section class="panel glass">
      <header class="panel-head"><h2>Dienste</h2></header>
      <ul class="list"><?php foreach ($services as $sv): ?>
        <li><a href="/hosting/<?= (int)$sv['id'] ?>"><b><?= e($sv['name']) ?></b><small><?= $sv['expires_on'] ? 'bis ' . date_de($sv['expires_on']) : 'unbefristet' ?></small></a><?= \App\Services\Hosting::badge($sv) ?></li>
      <?php endforeach ?></ul>
    </section>
    <?php endif ?>
    <section class="panel glass">
      <header class="panel-head"><h2>Tickets</h2></header>
      <?php if (!$tickets): ?><p class="empty">Keine Tickets.</p><?php else: ?>
        <ul class="list"><?php foreach ($tickets as $t): ?>
          <li><a href="/tickets/<?= (int)$t['id'] ?>"><b>#<?= (int)$t['id'] ?> <?= e($t['subject']) ?></b><small><?= date_de($t['updated_at'], true) ?></small></a><?= View::badge($t['status']) ?></li>
        <?php endforeach ?></ul>
      <?php endif ?>
    </section>
    <section class="panel glass">
      <header class="panel-head"><h2>Rechnungen</h2></header>
      <?php if (!$invoices): ?><p class="empty">Keine Rechnungen.</p><?php else: ?>
        <ul class="list"><?php foreach ($invoices as $i): ?>
          <li><a href="/rechnungen/<?= (int)$i['id'] ?>"><b><?= e($i['number'] ?? 'Entwurf #' . $i['id']) ?></b><small><?= e($i['title']) ?></small></a><span class="row-gap"><?= money((int)$i['total_cents']) ?> <?= View::badge($i['status']) ?></span></li>
        <?php endforeach ?></ul>
      <?php endif ?>
    </section>
    <section class="panel glass">
      <header class="panel-head"><h2>Anfragen</h2></header>
      <?php if (!$requests): ?><p class="empty">Keine Anfragen.</p><?php else: ?>
        <ul class="list"><?php foreach ($requests as $r): ?>
          <li><a href="/anfragen/<?= (int)$r['id'] ?>"><b><?= e($r['title'] ?: Notifier::TYPES[$r['type']]) ?></b><small><?= date_de($r['created_at']) ?></small></a><?= View::badge($r['status']) ?></li>
        <?php endforeach ?></ul>
      <?php endif ?>
    </section>
  </div>
  <aside class="panel glass">
    <dl class="kv">
      <dt>E-Mail</dt><dd><a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a></dd>
      <dt>Discord</dt><dd class="mono"><?= e($customer['discord'] ?: '–') ?></dd>
      <dt>Firma</dt><dd><?= e($customer['company'] ?: '–') ?></dd>
      <dt>Anschrift</dt><dd><?php if ($customer['street']): ?><?= e($customer['street']) ?><br><?= e($customer['zip']) ?> <?= e($customer['city']) ?><br><?= e($customer['country']) ?><?php else: ?><span class="badge badge-warn">fehlt</span><?php endif ?></dd>
      <dt>Kunde seit</dt><dd><?= date_de($customer['created_at']) ?></dd>
    </dl>
  </aside>
</div>
