<?php
use App\Core\View;
use App\Services\RequestService;
use App\Services\Notifier;
$closed = in_array($request['status'], ['done', 'declined', 'cancelled'], true);
?>
<header class="page-head">
  <div>
    <p class="kicker mono"><?= $inAccount ? '<a href="/konto/anfragen">Anfragen</a> / ' : 'Anfrage / ' ?><?= e($request['public_id']) ?></p>
    <h1><?= e($request['title'] ?: Notifier::TYPES[$request['type']]) ?></h1>
    <p class="meta"><?= View::badge($request['status']) ?> <span><?= e(Notifier::TYPES[$request['type']]) ?></span> <span>seit <?= date_de($request['created_at']) ?></span></p>
    <?php if (in_array($request['status'], ['in_progress', 'delivered', 'done'], true)): ?>
      <div class="progress-bar" role="progressbar" aria-valuenow="<?= (int)$request['progress'] ?>" aria-valuemin="0" aria-valuemax="100" title="<?= (int)$request['progress'] ?> %"><span data-progress="<?= (int)$request['progress'] ?>"></span></div>
    <?php endif ?>
  </div>
</header>

<div class="request-layout">
  <div class="stack-lg">
    <?php if ($offer): ?>
      <section class="offer-card">
        <p class="kicker mono">Dein Angebot</p>
        <div class="offer-price"><?= e(RequestService::priceLabel((int)$offer['amount_cents'])) ?></div>
        <?php if ($offer['delivery'] !== ''): ?><div class="offer-meta">Lieferung: <?= e($offer['delivery']) ?></div><?php endif ?>
        <div class="offer-text"><?= nl2p($offer['description']) ?></div>
        <div class="actions">
          <form action="<?= e($base) ?>/angebot" method="post" data-confirm="Angebot verbindlich annehmen?">
            <?= csrf_field() ?><input type="hidden" name="decision" value="accept">
            <button class="pill" type="submit">Angebot annehmen</button>
          </form>
          <form action="<?= e($base) ?>/angebot" method="post" data-confirm="Angebot ablehnen? Du kannst danach weiter schreiben.">
            <?= csrf_field() ?><input type="hidden" name="decision" value="decline">
            <button class="pill pill-ghost" type="submit">Ablehnen</button>
          </form>
        </div>
      </section>
    <?php endif ?>

    <section>
      <h2 class="sr-only">Verlauf</h2>
      <?php if (!$messages): ?>
        <div class="panel glass"><p class="empty">Noch keine Nachrichten. Ich melde mich in der Regel innerhalb von <?= e(\App\Services\Settings::get('response_time')) ?>.</p></div>
      <?php else: ?>
        <?php View::partial('shared/thread', ['messages' => $messages, 'viewer' => 'customer']) ?>
      <?php endif ?>
    </section>

    <?php if ($request['status'] !== 'cancelled'): ?>
      <form class="panel glass stack reply" action="<?= e($base) ?>/antwort" method="post">
        <?= csrf_field() ?>
        <label class="field<?= invalid('body') ?>"><span>Nachricht schreiben</span>
          <textarea name="body" rows="4" maxlength="5000" required placeholder="Fragen, Änderungswünsche, Links …"><?= e(old('body')) ?></textarea><?= error_for('body') ?></label>
        <div class="row-end"><button class="pill" type="submit">Senden</button></div>
      </form>
    <?php endif ?>
  </div>

  <aside>
    <section class="panel glass">
      <h2>Deine Angaben</h2>
      <dl class="kv mt-sm">
        <?php foreach ($details as $label => $value): ?><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd><?php endforeach ?>
        <?php if ($request['budget']): ?><dt>Budget</dt><dd><?= e($request['budget']) ?></dd><?php endif ?>
        <?php if ($request['deadline']): ?><dt>Termin</dt><dd><?= date_de($request['deadline']) ?></dd><?php endif ?>
        <dt>Kontakt</dt><dd><?= e($request['contact_email'] ?: '–') ?><?= $request['discord'] ? '<br>' . e($request['discord']) : '' ?></dd>
      </dl>
      <details class="mt-sm"><summary class="small muted">Ursprüngliche Beschreibung</summary><div class="prose small"><?= nl2p($request['message']) ?></div></details>
    </section>

    <?php if ($accepted): ?>
      <section class="panel glass">
        <h2>Vereinbart</h2>
        <dl class="kv mt-sm">
          <dt>Preis</dt><dd><b><?= e(RequestService::priceLabel((int)$accepted['amount_cents'])) ?></b></dd>
          <?php if ($accepted['delivery'] !== ''): ?><dt>Lieferung</dt><dd><?= e($accepted['delivery']) ?></dd><?php endif ?>
        </dl>
      </section>
    <?php endif ?>

    <?php if ($invoices): ?>
      <section class="panel glass">
        <h2>Belege</h2>
        <ul class="list">
          <?php foreach ($invoices as $i): ?>
            <li><a href="/konto/rechnungen/<?= (int)$i['id'] ?>"><b class="mono"><?= e($i['number']) ?></b><small><?= money((int)$i['total_cents']) ?></small></a><?= View::badge($i['status']) ?></li>
          <?php endforeach ?>
        </ul>
      </section>
    <?php elseif (!$request['customer_id'] && in_array($request['status'], ['accepted', 'in_progress', 'delivered', 'done'], true)): ?>
      <section class="panel glass">
        <h2>Belege &amp; Überblick</h2>
        <p class="muted small">Mit einem kostenlosen Kundenkonto (gleiche E-Mail) siehst du alle Anfragen und Belege an einem Ort.</p>
        <?php if (\App\Services\Settings::on('registration_open')): ?><a class="pill pill-sm" href="/registrieren">Konto erstellen</a><?php endif ?>
      </section>
    <?php endif ?>

    <?php if (!$inAccount): ?>
      <p class="muted small">🔒 Das ist dein privater Link zu dieser Anfrage. Gib ihn nicht weiter.</p>
    <?php endif ?>
  </aside>
</div>
