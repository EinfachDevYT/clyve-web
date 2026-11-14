<?php use App\Core\View; use App\Services\Notifier; ?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/kunden">Kunden</a> / <?= sprintf('K-%05d', (int)$customer['id']) ?></p>
    <h1><?= e($customer['name']) ?></h1>
  </div>
  <?php if (!$customer['deleted_at']): ?>
  <div class="actions">
    <a class="pill pill-sm pill-ghost" href="/hosting/neu?kunde=<?= (int)$customer['id'] ?>">Dienst anlegen</a>
    <a class="pill pill-sm" href="/rechnungen/neu?customer=<?= (int)$customer['id'] ?>">Rechnung erstellen</a>
  </div>
  <?php endif ?>
</header>
<?php if ($customer['deleted_at']): ?>
  <div class="notice">Dieses Konto wurde am <?= date_de($customer['deleted_at']) ?> geschlossen. Persönliche Daten sind gelöscht – erhalten bleiben nur Belege und Dienste, die aufbewahrt werden müssen.</div>
<?php endif ?>

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
      <dt>Name</dt><dd><?= e(\App\Services\CustomerProfile::addressedName($customer)) ?></dd>
      <dt>Telefon</dt><dd><?= $customer['phone'] ? '<a href="tel:' . e(preg_replace('/[^\d+]/', '', $customer['phone'])) . '">' . e($customer['phone']) . '</a>' : '–' ?></dd>
      <dt>E-Mail</dt><dd class="kv-mail"><a href="mailto:<?= e($customer['email']) ?>"><?= str_replace('@', '@<wbr>', e($customer['email'])) ?></a></dd>
      <dt>Discord</dt><dd<?= $customer['discord'] ? ' class="mono"' : '' ?>><?= e($customer['discord'] ?: '–') ?></dd>
      <dt>Firma</dt><dd><?= e($customer['company'] ?: '–') ?><?= $customer['vat_id'] ? '<br><small class="mono">USt-IdNr. ' . e($customer['vat_id']) . '</small>' : '' ?></dd>
      <dt>Anschrift</dt><dd><?php if (\App\Services\CustomerProfile::complete($customer)): ?><?= e(\App\Services\CustomerProfile::streetLine($customer)) ?><br><?php if ($customer['address_extra']): ?><?= e($customer['address_extra']) ?><br><?php endif ?><?= e($customer['zip']) ?> <?= e($customer['city']) ?><br><?= e($customer['country']) ?><?php else: ?><span class="badge badge-warn">fehlt</span><?php endif ?></dd>
      <dt>E-Mail bestätigt</dt><dd><?= $customer['email_verified_at'] ? '<span class="badge badge-ok">Ja</span>' : '<span class="badge badge-warn">Ausstehend</span>' ?></dd>
      <dt>Kunde seit</dt><dd><?= date_de($customer['created_at']) ?></dd>
      <dt>Letzte Anmeldung</dt><dd><?= $lastLogin ? date_de($lastLogin, true) : '–' ?></dd>
      <dt>Zwei-Faktor</dt><dd><?= $customer['totp_enabled_at'] ? '<span class="badge badge-ok">Aktiv</span>' : '<span class="badge badge-muted">Aus</span>' ?></dd>
    </dl>
    <?php if ($resetLink): ?>
      <div class="notice mt stack"><b>Link zum Zurücksetzen</b><?= $resetLink['sent'] ? ' (per E-Mail verschickt)' : '' ?>
        <code class="mono small break"><?= e($resetLink['url']) ?></code>
        <div><button type="button" class="chip-btn" data-copy="<?= e($resetLink['url']) ?>">Kopieren</button></div></div>
    <?php endif ?>
    <?php if (($user['role'] ?? '') === 'admin' && !$customer['deleted_at']): ?>
      <div class="stack mt">
        <?php if (!$customer['email_verified_at']): ?>
          <form action="/kunden/<?= (int)$customer['id'] ?>/bestaetigen" method="post" data-confirm="E-Mail-Adresse manuell als bestätigt markieren? Nur, wenn du sicher bist, dass sie dem Kunden gehört."><?= csrf_field() ?><button class="pill pill-sm pill-ghost pill-block" type="submit">E-Mail manuell bestätigen</button></form>
        <?php endif ?>
        <form action="/kunden/<?= (int)$customer['id'] ?>/passwort-link" method="post" data-confirm="Link zum Zurücksetzen des Passworts erstellen?"><?= csrf_field() ?><button class="pill pill-sm pill-ghost pill-block" type="submit">Passwort-Link erstellen</button></form>
        <?php if ($customer['totp_enabled_at']): ?>
          <form action="/kunden/<?= (int)$customer['id'] ?>/2fa-reset" method="post" data-confirm="Zwei-Faktor-Anmeldung des Kunden zurücksetzen? Nur nach sicherer Identitätsprüfung!"><?= csrf_field() ?><button class="pill pill-sm pill-ghost pill-block" type="submit">2FA zurücksetzen</button></form>
        <?php endif ?>
      </div>
      <details class="danger-zone mt"<?= errors() ? ' open' : '' ?>>
        <summary>Kunde löschen</summary>
        <div class="stack">
          <?php if ($deleteBlockers): ?>
            <?php foreach ($deleteBlockers as $b): ?><p class="notice notice-warn small"><?= e($b) ?></p><?php endforeach ?>
          <?php else: ?>
            <p class="muted small"><?= $mustKeep
              ? 'Der Kunde hat Belege oder Dienste. Das Konto wird deshalb <b>anonymisiert</b>: Login, Kontaktdaten, Profil, Tickets und Anfragen ohne Beleg werden gelöscht, ausgestellte Belege bleiben wie gesetzlich vorgeschrieben erhalten.'
              : 'Das Konto wird mit Tickets und Anfragen <b>vollständig gelöscht</b>.' ?></p>
            <?php if ($unpaid): ?><p class="notice notice-warn small"><?= $unpaid ?> offene Rechnung(en) – sie bleiben erhalten, aber der Kunde kann sie danach nicht mehr im Konto sehen.</p><?php endif ?>
            <form class="stack" action="/kunden/<?= (int)$customer['id'] ?>/loeschen" method="post"><?= csrf_field() ?>
              <label class="field<?= invalid('confirm') ?>"><span>Zum Bestätigen <b>LÖSCHEN</b> eintippen</span><input type="text" name="confirm" autocomplete="off" required><?= error_for('confirm') ?></label>
              <label class="consent"><input type="checkbox" name="notify" value="1" checked><span>Kunden per E-Mail informieren</span></label>
              <button class="pill pill-sm pill-block danger" type="submit">Endgültig löschen</button>
            </form>
          <?php endif ?>
        </div>
      </details>
    <?php endif ?>
  </aside>
</div>
