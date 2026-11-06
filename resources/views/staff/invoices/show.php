<?php use App\Core\View; $draft = $invoice['status'] === 'draft'; $admin = ($user['role'] ?? '') === 'admin'; $id = (int)$invoice['id']; ?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/rechnungen">Rechnungen</a> / <?= e($invoice['number'] ?? 'Entwurf #' . $id) ?></p>
    <h1><?= e($invoice['title'] ?: ($invoice['number'] ?? 'Entwurf')) ?></h1>
    <p class="meta"><?= View::badge($invoice['status']) ?> <a href="/kunden/<?= (int)$invoice['customer_id'] ?>"><?= e($invoice['customer_name']) ?></a>
      <?php if (!$draft): ?><span>vom <?= date_de($invoice['issue_date']) ?></span><span>fällig <?= date_de($invoice['due_date']) ?></span><?php endif ?></p>
  </div>
  <div class="actions">
    <a class="pill pill-ghost pill-sm" href="/rechnungen/<?= $id ?>/druck" target="_blank" rel="noopener" data-native><?= $draft ? 'Vorschau' : 'PDF / Drucken' ?></a>
    <?php if ($draft && $admin): ?>
      <form action="/rechnungen/<?= $id ?>/ausstellen" method="post" data-confirm="Rechnung jetzt verbindlich ausstellen? Danach ist sie nicht mehr änderbar.">
        <?= csrf_field() ?><button class="pill pill-sm" type="submit"<?= $canIssue ? '' : ' disabled title="Erst nach Gewerbeanmeldung möglich"' ?>>Ausstellen</button>
      </form>
    <?php endif ?>
    <?php if ($invoice['status'] === 'issued' && $admin): ?>
      <form action="/rechnungen/<?= $id ?>/bezahlt" method="post"><?= csrf_field() ?><button class="pill pill-sm" type="submit">Als bezahlt markieren</button></form>
    <?php endif ?>
    <?php if (in_array($invoice['status'], ['issued', 'paid'], true) && $admin): ?>
      <form action="/rechnungen/<?= $id ?>/stornieren" method="post" data-confirm="Rechnung stornieren?"><?= csrf_field() ?><button class="pill pill-ghost pill-sm" type="submit">Stornieren</button></form>
    <?php endif ?>
  </div>
</header>

<?php if ($draft && !$canIssue): ?>
  <div class="notice">Entwurfsmodus: Ohne Gewerbeanmeldung kann diese Rechnung nur vorbereitet werden. Die Vorschau ist mit „ENTWURF" gekennzeichnet und keine gültige Rechnung.</div>
<?php endif ?>
<?php if ($draft && $customer && ($customer['street'] === '' || $customer['city'] === '')): ?>
  <div class="notice notice-warn">Beim Kunden fehlt die Anschrift – sie ist zum Ausstellen erforderlich.</div>
<?php endif ?>

<?php View::partial('shared/invoice-items', ['invoice' => $invoice, 'editable' => $draft]) ?>

<?php if ($draft): ?>
  <div class="grid-2 gap-lg align-start mt">
    <form class="panel glass stack" action="/rechnungen/<?= $id ?>/positionen" method="post">
      <?= csrf_field() ?>
      <h2>Position hinzufügen</h2>
      <label class="field<?= invalid('description') ?>"><span>Beschreibung</span>
        <input type="text" name="description" value="<?= e(old('description')) ?>" maxlength="300" required><?= error_for('description') ?></label>
      <div class="grid-3">
        <label class="field<?= invalid('quantity') ?>"><span>Menge</span><input type="text" name="quantity" value="<?= e(old('quantity', '1')) ?>" inputmode="decimal" required></label>
        <label class="field<?= invalid('unit') ?>"><span>Einheit</span><input type="text" name="unit" value="<?= e(old('unit', 'Stk.')) ?>" maxlength="20" required></label>
        <label class="field<?= invalid('price') ?>"><span>Preis (€)</span><input type="text" name="price" value="<?= e(old('price')) ?>" inputmode="decimal" placeholder="49,90" required></label>
      </div>
      <?= error_for('quantity') ?><?= error_for('price') ?>
      <div class="row-end"><button class="pill pill-sm" type="submit">Hinzufügen</button></div>
    </form>

    <form class="panel glass stack" action="/rechnungen/<?= $id ?>" method="post">
      <?= csrf_field() ?>
      <h2>Details</h2>
      <label class="field"><span>Titel</span><input type="text" name="title" value="<?= e(old('title', $invoice['title'])) ?>" maxlength="120"></label>
      <label class="field"><span>Leistungsdatum / -zeitraum</span><input type="text" name="service_date" value="<?= e(old('service_date', $invoice['service_date'])) ?>" maxlength="60" placeholder="z. B. 01.–14.11.2026"></label>
      <label class="field"><span>Hinweis auf der Rechnung</span><textarea name="notes" rows="3" maxlength="1000"><?= e(old('notes', $invoice['notes'])) ?></textarea></label>
      <div class="row-between">
        <button class="pill pill-sm" type="submit">Speichern</button>
      </div>
    </form>
  </div>
  <form class="row-end mt" action="/rechnungen/<?= $id ?>/loeschen" method="post" data-confirm="Entwurf wirklich löschen?">
    <?= csrf_field() ?><button class="link-btn danger" type="submit">Entwurf löschen</button>
  </form>
<?php endif ?>
