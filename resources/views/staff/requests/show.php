<?php
use App\Core\View;
use App\Services\InvoiceService;
use App\Services\Notifier;
use App\Services\RequestService;
$closed = in_array($request['status'], ['done', 'declined', 'cancelled'], true);
$quick = [
    'Danke!'      => "Hey {$request['name']}, danke für deine Anfrage! Ich schaue sie mir genauer an und melde mich mit einem Angebot.",
    'Rückfrage'   => "Hey {$request['name']}, kurze Rückfrage, damit ich dir ein passendes Angebot machen kann:\n\n",
    'Discord'     => "Hey {$request['name']}, lass uns die Details am besten kurz auf Discord besprechen. Ich schreibe dich an!",
    'Fertig'      => "Hey {$request['name']}, dein Projekt ist fertig! Schau es dir in Ruhe an und gib mir Bescheid, falls noch etwas angepasst werden soll.",
    'Absage'      => "Hey {$request['name']}, danke für dein Interesse! Leider kann ich das Projekt gerade nicht übernehmen.",
];
?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/anfragen">Anfragen</a> / <?= e($request['public_id']) ?></p>
    <h1><?= e($request['title'] ?: Notifier::TYPES[$request['type']]) ?></h1>
    <p class="meta"><?= View::badge($request['status']) ?> <span><?= e(Notifier::TYPES[$request['type']]) ?></span> <span>eingegangen <?= date_de($request['created_at'], true) ?></span>
      <?php if ($request['customer_id']): ?><a href="/kunden/<?= (int)$request['customer_id'] ?>">Kundenkonto</a><?php else: ?><span class="badge badge-muted">Gast</span><?php endif ?></p>
    <?php if (in_array($request['status'], ['in_progress', 'delivered', 'done'], true)): ?>
      <div class="progress-bar" title="<?= (int)$request['progress'] ?> %"><span data-progress="<?= (int)$request['progress'] ?>"></span></div>
    <?php endif ?>
  </div>
</header>

<div class="request-layout">
  <div class="stack-lg">
    <section class="panel glass">
      <h2>Projektbeschreibung</h2>
      <div class="prose"><?= nl2p($request['message']) ?></div>
    </section>

    <?php if (!$closed): ?>
      <details class="panel glass"<?= $pending || $accepted ? '' : ' open' ?>>
        <summary class="panel-summary"><b><?= $pending ? 'Neues Angebot (ersetzt das offene)' : ($accepted ? 'Neues Angebot' : 'Angebot erstellen') ?></b></summary>
        <form class="stack mt" action="/anfragen/<?= (int)$request['id'] ?>/angebot" method="post">
          <?= csrf_field() ?>
          <div class="grid-2">
            <label class="field<?= invalid('amount') ?>"><span>Preis (€)</span>
              <input type="text" name="amount" value="<?= e(old('amount')) ?>" inputmode="decimal" placeholder="z. B. 49,90"><?= error_for('amount') ?></label>
            <label class="field<?= invalid('delivery') ?>"><span>Lieferzeit</span>
              <input type="text" name="delivery" value="<?= e(old('delivery')) ?>" maxlength="80" placeholder="z. B. 5–7 Tage"></label>
          </div>
          <label class="consent"><input type="checkbox" name="free" value="1"<?= old('free') === '1' ? ' checked' : '' ?>><span><b>Kostenlos</b> (Hobby / Freundschaftsdienst – kein Beleg nötig)</span></label>
          <label class="field<?= invalid('description') ?>"><span>Leistungsbeschreibung</span>
            <textarea name="description" rows="5" maxlength="3000" required placeholder="Was ist enthalten? Was nicht? Wie viele Änderungsrunden?"><?= e(old('description')) ?></textarea><?= error_for('description') ?></label>
          <div class="row-end"><button class="pill" type="submit">Angebot senden</button></div>
        </form>
      </details>
    <?php endif ?>

    <section>
      <h2 class="sr-only">Verlauf</h2>
      <?php if ($messages): View::partial('shared/thread', ['messages' => $messages, 'viewer' => 'staff']); else: ?>
        <div class="panel glass"><p class="empty">Noch keine Nachrichten. Schreib die erste Antwort unten.</p></div>
      <?php endif ?>
    </section>

    <form class="panel glass stack reply" action="/anfragen/<?= (int)$request['id'] ?>/antwort" method="post">
      <?= csrf_field() ?>
      <div class="row-between">
        <b>Antworten</b>
        <div class="quick-replies">
          <?php foreach ($quick as $label => $text): ?>
            <button type="button" class="chip-btn" data-insert="<?= e($text) ?>" data-target="#replyBody"><?= e($label) ?></button>
          <?php endforeach ?>
        </div>
      </div>
      <label class="field<?= invalid('body') ?>"><span class="sr-only">Nachricht</span>
        <textarea id="replyBody" name="body" rows="5" maxlength="5000" required><?= e(old('body')) ?></textarea><?= error_for('body') ?></label>
      <div class="row-between">
        <label class="consent"><input type="checkbox" name="internal" value="1"><span>Nur interne Notiz (Kunde sieht sie nicht)</span></label>
        <button class="pill" type="submit">Senden</button>
      </div>
      <p class="muted small"><?= $mailReady ? '✉️ Der Kunde bekommt eine E-Mail mit Link zur Anfrage.' : 'ℹ️ Keine E-Mail möglich (keine Adresse oder SMTP nicht eingerichtet). Der Kunde sieht die Antwort über seinen Link/sein Konto.' ?></p>
    </form>
  </div>

  <aside>
    <section class="panel glass">
      <h2>Kontakt</h2>
      <dl class="kv mt-sm">
        <dt>Name</dt><dd><?= e($request['name']) ?></dd>
        <dt>E-Mail</dt><dd><?php if ($request['contact_email']): ?><a href="mailto:<?= e($request['contact_email']) ?>?subject=<?= rawurlencode('Deine Anfrage ' . $request['public_id']) ?>"><?= e($request['contact_email']) ?></a><?php else: ?>–<?php endif ?></dd>
        <dt>Discord</dt><dd><?php if ($request['discord']): ?><button type="button" class="link-btn mono" data-copy="<?= e($request['discord']) ?>" title="Kopieren"><?= e($request['discord']) ?> ⧉</button><?php else: ?>–<?php endif ?></dd>
      </dl>
    </section>

    <section class="panel glass">
      <h2>Angaben</h2>
      <dl class="kv mt-sm">
        <?php foreach ($details as $label => $value): ?><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd><?php endforeach ?>
        <dt>Budget</dt><dd><?= e($request['budget'] ?: '–') ?></dd>
        <dt>Termin</dt><dd><?= date_de($request['deadline'] ?: null) ?></dd>
      </dl>
    </section>

    <section class="panel glass">
      <h2>Status</h2>
      <form class="stack mt-sm" action="/anfragen/<?= (int)$request['id'] ?>/status" method="post">
        <?= csrf_field() ?>
        <select name="status" aria-label="Status">
          <?php foreach (RequestService::STATUSES as $key => $label): ?><option value="<?= e($key) ?>"<?= $key === $request['status'] ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
        </select>
        <label class="field"><span>Fortschritt: <b data-range-out><?= (int)$request['progress'] ?></b> %</span>
          <input type="range" name="progress" min="0" max="100" step="5" value="<?= (int)$request['progress'] ?>" data-range></label>
        <button class="pill pill-sm" type="submit">Speichern</button>
      </form>
    </section>

    <section class="panel glass">
      <h2>Angebote</h2>
      <?php if (!$offers): ?><p class="empty">Noch kein Angebot.</p><?php else: ?>
        <ul class="list">
          <?php foreach ($offers as $o): ?>
            <li><span><b><?= e(RequestService::priceLabel((int)$o['amount_cents'])) ?></b><small><?= date_de($o['created_at'], true) ?><?= $o['delivery'] ? ' · ' . e($o['delivery']) : '' ?></small></span><?= View::badge($o['status']) ?></li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
    </section>

    <section class="panel glass">
      <h2>Belege</h2>
      <?php foreach ($invoices as $i): ?>
        <p class="row-between mt-sm"><a class="mono" href="/rechnungen/<?= (int)$i['id'] ?>"><?= e($i['number'] ?? 'Entwurf #' . $i['id']) ?></a><?= View::badge($i['status']) ?></p>
      <?php endforeach ?>
      <?php $hasDoc = (bool)array_filter($invoices, static fn($i) => $i['status'] !== 'cancelled'); ?>
      <?php if ($accepted && (int)$accepted['amount_cents'] > 0 && !$hasDoc): ?>
        <form class="mt-sm" action="/anfragen/<?= (int)$request['id'] ?>/rechnung" method="post">
          <?= csrf_field() ?><button class="pill pill-sm pill-ghost" type="submit"><?= e(InvoiceService::docLabel()) ?> aus Angebot erstellen</button>
        </form>
        <?php if (!$request['customer_id']): ?><p class="muted small mt-sm">Dafür braucht der Kunde ein Konto mit derselben E-Mail.</p><?php endif ?>
      <?php elseif (!$invoices): ?>
        <p class="empty">Sobald ein bezahltes Angebot angenommen ist, kannst du hier den Beleg erstellen.</p>
      <?php endif ?>
    </section>

    <?php if (!$request['customer_id']): ?>
      <section class="panel glass">
        <h2>Privater Link</h2>
        <?php if ($link): ?>
          <button type="button" class="link-btn small" data-copy="<?= e($link) ?>">Link kopieren ⧉</button>
          <p class="muted small mt-sm">Damit sieht der Kunde seine Anfrage ohne Konto. Schick ihn z. B. per Discord.</p>
        <?php endif ?>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
          <form class="mt-sm" action="/anfragen/<?= (int)$request['id'] ?>/link" method="post" data-confirm="Neuen Link erzeugen? Der alte funktioniert dann nicht mehr.">
            <?= csrf_field() ?><button class="link-btn small danger" type="submit">Neuen Link erzeugen</button>
          </form>
        <?php endif ?>
      </section>
    <?php endif ?>
  </aside>
</div>
