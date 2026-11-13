<?php
use App\Services\Devlog;
$p = $post;
$action = $p ? '/devlog/' . (int)$p['id'] : '/devlog';
$published = $p && $p['status'] === 'published';
?>
<header class="page-head">
  <div><p class="kicker mono"><a href="/devlog">Devlog</a> / <?= $p ? ($published ? 'Veröffentlicht' : 'Entwurf') : 'Neu' ?></p><h1><?= e($p['title'] ?? 'Neuer Beitrag') ?></h1></div>
  <?php if ($published): ?><a class="pill pill-sm pill-ghost" href="<?= e(Devlog::url($p)) ?>" target="_blank" rel="noopener">Ansehen ↗</a><?php endif ?>
</header>

<div class="grid-side">
  <form class="panel glass stack" action="<?= e($action) ?>" method="post">
    <?= csrf_field() ?>
    <label class="field<?= invalid('title') ?>"><span>Titel</span>
      <input type="text" name="title" maxlength="120" required value="<?= e(old('title', $p['title'] ?? '')) ?>" placeholder="z. B. Neuer Kundenbereich"><?= error_for('title') ?></label>
    <div class="grid-2">
      <label class="field<?= invalid('tag') ?>"><span>Art</span>
        <select name="tag"><?php foreach (Devlog::TAGS as $k => $label): ?><option value="<?= e($k) ?>"<?= old('tag', $p['tag'] ?? 'feature') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select><?= error_for('tag') ?></label>
      <label class="consent align-self-end"><input type="checkbox" name="pinned" value="1"<?= ($p['pinned'] ?? 0) ? ' checked' : '' ?>><span>Oben anheften</span></label>
    </div>
    <label class="field<?= invalid('summary') ?>"><span>Kurzfassung <small class="muted">(erscheint in der Übersicht und in Discord)</small></span>
      <input type="text" name="summary" maxlength="280" value="<?= e(old('summary', $p['summary'] ?? '')) ?>"><?= error_for('summary') ?></label>
    <label class="field<?= invalid('body') ?>"><span>Inhalt</span>
      <textarea name="body" rows="16" maxlength="12000" class="mono devlog-editor" required placeholder="Ein paar einleitende Sätze …&#10;&#10;## Kundenbereich&#10;+ Neue Seitenleiste&#10;~ Login schneller&#10;! Fehler beim Speichern behoben&#10;- Alte Profilseite entfernt"><?= e(old('body', $p['body'] ?? '')) ?></textarea><?= error_for('body') ?></label>
    <div class="devlog-help small">
      <span><b class="c-add mono">+</b> Neu</span><span><b class="c-remove mono">-</b> Entfernt</span><span><b class="c-change mono">~</b> Verbessert</span><span><b class="c-fix mono">!</b> Behoben</span><span><b class="mono">##</b> Abschnitt</span><span><b class="mono">**fett**</b></span><span><b class="mono">`code`</b></span>
    </div>
    <div class="btn-row">
      <?php if ($published): ?>
        <button class="pill" type="submit">Speichern</button>
      <?php else: ?>
        <button class="pill" type="submit" name="intent" value="publish">Veröffentlichen</button>
        <button class="pill pill-ghost" type="submit" name="intent" value="draft">Als Entwurf speichern</button>
      <?php endif ?>
    </div>
  </form>

  <aside class="stack">
    <?php if ($p): $msg = Devlog::discordMessage($p); ?>
      <section class="panel glass stack">
        <div class="row-between"><h2>Teilen</h2><?= $p['discord_posted_at'] ? '<span class="badge badge-info">gepostet ' . date_de($p['discord_posted_at']) . '</span>' : '' ?></div>
        <?php if ($published): ?>
          <label class="field"><span>Link</span><input type="text" class="mono small" readonly value="<?= e(Devlog::url($p)) ?>"></label>
          <button type="button" class="chip-btn" data-copy="<?= e(Devlog::url($p)) ?>">Link kopieren</button>
        <?php else: ?>
          <p class="muted small">Der Link funktioniert, sobald der Beitrag veröffentlicht ist.</p>
        <?php endif ?>
        <div class="field"><span>Discord-Nachricht <small class="muted">(Stand: zuletzt gespeichert)</small></span>
          <pre class="discord-preview mono"><?= e($msg) ?></pre>
          <small class="muted"><?= mb_strlen($msg) ?> / 2000 Zeichen</small></div>
        <button type="button" class="pill pill-sm pill-ghost" data-copy="<?= e($msg) ?>">Nachricht kopieren</button>
        <?php if ($published && Devlog::webhookConfigured()): ?>
          <form action="/devlog/<?= (int)$p['id'] ?>/discord" method="post" data-confirm="<?= $p['discord_posted_at'] ? 'Wurde schon gepostet. Trotzdem nochmal in Discord posten?' : 'Jetzt im Devlog-Kanal posten?' ?>"><?= csrf_field() ?>
            <button class="pill pill-sm pill-block" type="submit">In Discord posten</button></form>
        <?php elseif ($published && ($user['role'] ?? '') === 'admin'): ?>
          <p class="muted small">Direkt posten: Devlog-Webhook unter <a href="/verwaltung/integrationen">Verwaltung → Integrationen</a> eintragen.</p>
        <?php endif ?>
      </section>
      <section class="panel glass stack">
        <?php if ($published): ?>
          <form action="/devlog/<?= (int)$p['id'] ?>/zurueckziehen" method="post" data-confirm="Beitrag zurückziehen? Kunden sehen ihn dann nicht mehr und der Link funktioniert nicht mehr."><?= csrf_field() ?><button class="pill pill-sm pill-ghost pill-block" type="submit">Zurückziehen</button></form>
        <?php endif ?>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
          <form action="/devlog/<?= (int)$p['id'] ?>/loeschen" method="post" data-confirm="Beitrag endgültig löschen?"><?= csrf_field() ?><button class="link-btn danger" type="submit">Beitrag löschen</button></form>
        <?php endif ?>
      </section>
    <?php else: ?>
      <section class="panel glass stack small">
        <h2>So funktioniert's</h2>
        <p class="muted">Jede Zeile mit <b class="c-add mono">+</b>, <b class="c-remove mono">-</b>, <b class="c-change mono">~</b> oder <b class="c-fix mono">!</b> wird ein Eintrag im Changelog – wie bei GitHub. Nach dem Speichern bekommst du hier den Link und die fertige Discord-Nachricht.</p>
      </section>
    <?php endif ?>
  </aside>
</div>
