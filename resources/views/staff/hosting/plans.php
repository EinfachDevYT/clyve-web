<?php use App\Services\Hosting; $pl = $edit; ?>
<header class="page-head">
  <div><p class="kicker mono"><a href="/hosting">Hosting</a> / Tarife</p><h1>Tarife</h1></div>
</header>

<div class="grid-side">
  <div class="table-wrap glass">
    <table class="table">
      <thead><tr><th>Tarif</th><th>Preis</th><th>Laufzeit</th><th>In Nutzung</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($plans as $p): ?>
          <tr>
            <td data-label="Tarif"><b><?= e($p['name']) ?></b><?php if ($p['description'] !== ''): ?><br><small class="muted"><?= e($p['description']) ?></small><?php endif ?></td>
            <td data-label="Preis"><?= (int)$p['price_cents'] > 0 ? money((int)$p['price_cents']) : 'Kostenlos' ?></td>
            <td data-label="Laufzeit"><?= e(Hosting::termLabel((int)$p['term_months'])) ?></td>
            <td data-label="In Nutzung"><?= (int)$p['in_use'] ?></td>
            <td data-label="Status">
              <form action="/hosting/tarife/<?= (int)$p['id'] ?>/toggle" method="post"><?= csrf_field() ?>
                <button class="badge <?= $p['active'] ? 'badge-ok' : 'badge-muted' ?> badge-btn" type="submit" title="Umschalten"><?= $p['active'] ? 'Aktiv' : 'Aus' ?></button>
              </form>
            </td>
            <td class="num"><a href="/hosting/tarife?bearbeiten=<?= (int)$p['id'] ?>">Bearbeiten</a></td>
          </tr>
        <?php endforeach ?>
        <?php if (!$plans): ?><tr><td colspan="6" class="empty">Noch keine Tarife. Leg rechts deinen ersten an.</td></tr><?php endif ?>
      </tbody>
    </table>
  </div>

  <form class="panel glass stack" action="/hosting/tarife" method="post">
    <?= csrf_field() ?>
    <?php if ($pl): ?><input type="hidden" name="id" value="<?= (int)$pl['id'] ?>"><?php endif ?>
    <h2><?= $pl ? 'Tarif bearbeiten' : 'Neuer Tarif' ?></h2>
    <label class="field<?= invalid('name') ?>"><span>Name</span><input type="text" name="name" maxlength="80" required value="<?= e(old('name', $pl['name'] ?? '')) ?>" placeholder="z. B. Bot-Hosting Basic"><?= error_for('name') ?></label>
    <label class="field<?= invalid('description') ?>"><span>Beschreibung</span><input type="text" name="description" maxlength="300" value="<?= e(old('description', $pl['description'] ?? '')) ?>" placeholder="z. B. 1 Bot, 512 MB RAM"><?= error_for('description') ?></label>
    <div class="grid-2">
      <label class="field<?= invalid('price') ?>"><span>Preis (€)</span><input type="text" name="price" inputmode="decimal" value="<?= e(old('price', $pl ? number_format((int)$pl['price_cents'] / 100, 2, ',', '') : '')) ?>" placeholder="0 = kostenlos"><?= error_for('price') ?></label>
      <label class="field<?= invalid('term_months') ?>"><span>Laufzeit</span>
        <select name="term_months">
          <?php foreach (Hosting::TERMS as $m => $label): ?><option value="<?= $m ?>"<?= (int)old('term_months', (string)($pl['term_months'] ?? 1)) === $m ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
        </select><?= error_for('term_months') ?></label>
    </div>
    <p class="muted small">Preisänderungen gelten nur für neue Dienste.</p>
    <div class="row-between">
      <?php if ($pl): ?><a href="/hosting/tarife">Abbrechen</a><?php else: ?><span></span><?php endif ?>
      <button class="pill pill-sm" type="submit">Speichern</button>
    </div>
  </form>
</div>
