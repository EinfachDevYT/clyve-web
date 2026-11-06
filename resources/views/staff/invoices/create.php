<header class="page-head"><div><p class="kicker mono"><a href="/rechnungen">Rechnungen</a> / Neu</p><h1>Neue Rechnung</h1></div></header>

<?php if (!$customers): ?>
  <div class="panel glass"><p class="empty">Es gibt noch keine Kunden. Kunden registrieren sich selbst im Kundenbereich.</p></div>
<?php else: ?>
  <form class="panel glass stack narrow" action="/rechnungen" method="post">
    <?= csrf_field() ?>
    <label class="field<?= invalid('customer_id') ?>"><span>Kunde</span>
      <select name="customer_id" required>
        <option value="">Bitte wählen…</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['id'] ?>"<?= (int)old('customer_id', (string)$selected) === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?> (<?= e($c['email']) ?>)</option>
        <?php endforeach ?>
      </select><?= error_for('customer_id') ?></label>
    <label class="field<?= invalid('title') ?>"><span>Titel / Projekt</span>
      <input type="text" name="title" value="<?= e(old('title')) ?>" maxlength="120" placeholder="z. B. Garagen-System"></label>
    <div class="row-end"><button class="pill" type="submit">Entwurf anlegen</button></div>
  </form>
<?php endif ?>
