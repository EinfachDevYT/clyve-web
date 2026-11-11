<?php use App\Services\Hosting; $sel = old('customer_id', $preselect ? (string)$preselect : ''); ?>
<header class="page-head">
  <div><p class="kicker mono"><a href="/hosting">Hosting</a> / Neu</p><h1>Dienst anlegen</h1></div>
</header>

<?php if (!$customers): ?>
  <div class="notice notice-warn">Es gibt noch keine Kundenkonten. Ein Dienst gehört immer zu einem Konto – bitte den Kunden zuerst registrieren lassen.</div>
<?php else: ?>
<form class="panel glass stack narrow" action="/hosting" method="post">
  <?= csrf_field() ?>
  <label class="field<?= invalid('customer_id') ?>"><span>Kunde</span>
    <select name="customer_id" required>
      <option value="">– bitte wählen –</option>
      <?php foreach ($customers as $c): ?>
        <option value="<?= (int)$c['id'] ?>"<?= $sel === (string)$c['id'] ? ' selected' : '' ?>><?= e($c['name'] . ' · ' . $c['email']) ?></option>
      <?php endforeach ?>
    </select><?= error_for('customer_id') ?></label>
  <label class="field<?= invalid('name') ?>"><span>Name des Dienstes</span>
    <input type="text" name="name" maxlength="120" required value="<?= e(old('name')) ?>" placeholder="z. B. Ticket-Bot für Nova RP"><?= error_for('name') ?></label>

  <label class="field<?= invalid('plan_id') ?>"><span>Tarif</span>
    <select name="plan_id">
      <option value="">Individuell (Preis und Laufzeit unten)</option>
      <?php foreach ($plans as $pl): ?>
        <option value="<?= (int)$pl['id'] ?>"<?= old('plan_id') === (string)$pl['id'] ? ' selected' : '' ?>><?= e($pl['name'] . ' – ' . Hosting::priceLabel($pl)) ?></option>
      <?php endforeach ?>
    </select><?= error_for('plan_id') ?>
    <small class="muted"><?= $plans ? 'Mit Tarif kannst du Preis und Laufzeit leer lassen.' : 'Noch keine Tarife angelegt.' ?></small></label>

  <div class="grid-3">
    <label class="field<?= invalid('price') ?>"><span>Preis (€)</span>
      <input type="text" name="price" inputmode="decimal" value="<?= e(old('price')) ?>" placeholder="0 = kostenlos"><?= error_for('price') ?></label>
    <label class="field<?= invalid('term_months') ?>"><span>Laufzeit</span>
      <select name="term_months">
        <option value="">aus Tarif</option>
        <?php foreach (Hosting::TERMS as $m => $label): ?>
          <option value="<?= $m ?>"<?= old('term_months') === (string)$m ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach ?>
      </select><?= error_for('term_months') ?></label>
    <label class="field<?= invalid('starts_on') ?>"><span>Start</span>
      <input type="date" name="starts_on" value="<?= e(old('starts_on', Hosting::today()->format('Y-m-d'))) ?>"><?= error_for('starts_on') ?></label>
  </div>

  <?php if ($requests): ?>
    <label class="field<?= invalid('request_id') ?>"><span>Zugehörige Anfrage <small class="muted">(optional)</small></span>
      <select name="request_id">
        <option value="">–</option>
        <?php foreach ($requests as $r): ?>
          <option value="<?= (int)$r['id'] ?>"<?= old('request_id') === (string)$r['id'] ? ' selected' : '' ?>><?= e($r['public_id'] . ' · ' . ($r['title'] ?: 'Discord Bot')) ?></option>
        <?php endforeach ?>
      </select><?= error_for('request_id') ?></label>
  <?php endif ?>
  <label class="field<?= invalid('notes') ?>"><span>Interne Notiz</span>
    <textarea name="notes" rows="3" maxlength="2000" placeholder="z. B. Server, Container-Name, Besonderheiten"><?= e(old('notes')) ?></textarea></label>
  <p class="muted small">Beim Anlegen wird ein Lizenzschlüssel erzeugt und einmalig angezeigt.</p>
  <div class="row-end"><button class="pill" type="submit">Dienst anlegen</button></div>
</form>
<?php endif ?>
