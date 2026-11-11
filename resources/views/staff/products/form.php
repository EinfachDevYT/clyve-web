<?php use App\Services\Tebex; $pr = $product; $fw = $pr ? explode(',', $pr['frameworks']) : []; $action = $pr ? '/scripts/' . (int)$pr['id'] : '/scripts'; ?>
<header class="page-head">
  <div><p class="kicker mono"><a href="/scripts">Scripts</a> / <?= $pr ? 'Bearbeiten' : 'Neu' ?></p><h1><?= e($pr['title'] ?? 'Neues Script') ?></h1></div>
</header>

<?php if ($pr && $pr['tebex_id']): ?>
  <div class="notice">Von Tebex synchronisiert (Paket #<?= (int)$pr['tebex_id'] ?>). Titel, Texte, Preis, Bild und Link kommen beim Sync von Tebex – hier stellst du Sichtbarkeit, Reihenfolge und Frameworks ein.</div>
<?php endif ?>

<form class="panel glass stack narrow" action="<?= e($action) ?>" method="post">
  <?= csrf_field() ?>
  <label class="field<?= invalid('title') ?>"><span>Titel</span><input type="text" name="title" maxlength="120" required value="<?= e(old('title', $pr['title'] ?? '')) ?>"><?= error_for('title') ?></label>
  <div class="grid-2">
    <label class="field<?= invalid('category') ?>"><span>Kategorie</span><input type="text" name="category" maxlength="60" value="<?= e(old('category', $pr['category'] ?? '')) ?>" placeholder="z. B. Jobs, UI, Systeme"></label>
    <label class="field<?= invalid('price_label') ?>"><span>Preis (Anzeige)</span><input type="text" name="price_label" maxlength="40" value="<?= e(old('price_label', $pr['price_label'] ?? '')) ?>" placeholder="z. B. 14,99 € oder Kostenlos"></label>
  </div>
  <label class="field<?= invalid('summary') ?>"><span>Kurzbeschreibung</span><input type="text" name="summary" maxlength="200" value="<?= e(old('summary', $pr['summary'] ?? '')) ?>"></label>
  <label class="field<?= invalid('description') ?>"><span>Beschreibung</span><textarea name="description" rows="6" maxlength="3000"><?= e(old('description', $pr['description'] ?? '')) ?></textarea></label>
  <label class="field<?= invalid('tebex_url') ?>"><span>Link zum Tebex-Paket</span><input type="url" name="tebex_url" value="<?= e(old('tebex_url', $pr['tebex_url'] ?? '')) ?>" placeholder="https://deinshop.tebex.io/package/123"><?= error_for('tebex_url') ?></label>
  <label class="field<?= invalid('image_url') ?>"><span>Bild-URL <small class="muted">(erlaubt: <?= e(implode(', ', Tebex::imageHosts()) ?: 'keine Hosts konfiguriert') ?>)</small></span><input type="url" name="image_url" value="<?= e(old('image_url', $pr['image_url'] ?? '')) ?>"><?= error_for('image_url') ?></label>
  <fieldset class="field"><legend>Frameworks</legend>
    <div class="chips">
      <?php foreach (Tebex::FRAMEWORKS as $f): ?>
        <label class="chip"><input type="checkbox" name="frameworks[]" value="<?= e($f) ?>"<?= in_array($f, $fw, true) ? ' checked' : '' ?>><span><?= e($f) ?></span></label>
      <?php endforeach ?>
    </div>
  </fieldset>
  <div class="grid-3 align-end">
    <label class="consent"><input type="checkbox" name="active" value="1"<?= ($pr['active'] ?? 1) ? ' checked' : '' ?>><span>Sichtbar</span></label>
    <label class="consent"><input type="checkbox" name="featured" value="1"<?= ($pr['featured'] ?? 0) ? ' checked' : '' ?>><span>Hervorheben</span></label>
    <label class="field"><span>Reihenfolge</span><input type="text" inputmode="numeric" name="sort" value="<?= (int)($pr['sort'] ?? 0) ?>"></label>
  </div>
  <div class="row-between">
    <button class="pill" type="submit">Speichern</button>
  </div>
</form>
<?php if ($pr && ($user['role'] ?? '') === 'admin'): ?>
  <form class="row-end mt narrow" action="/scripts/<?= (int)$pr['id'] ?>/loeschen" method="post" data-confirm="Script wirklich löschen?">
    <?= csrf_field() ?><button class="link-btn danger" type="submit">Script löschen</button>
  </form>
<?php endif ?>
