<?php use App\Services\Account; ?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/mitarbeiter">Mitarbeiter</a> / #<?= (int)$m['id'] ?></p>
    <h1><?= e($m['name']) ?></h1>
    <p class="meta"><?= $m['active'] ? '<span class="badge badge-ok">Aktiv</span>' : '<span class="badge badge-muted">Deaktiviert</span>' ?>
      <?= $m['totp_enabled_at'] ? '<span class="badge badge-ok">2FA aktiv</span>' : '<span class="badge badge-warn">ohne 2FA</span>' ?> <span><?= e($m['email']) ?></span></p>
  </div>
</header>

<?php if ($link): ?>
  <section class="panel glass license-once stack">
    <div><h2><?= $link['purpose'] === 'invite' ? 'Einladungslink' : 'Link zum Zurücksetzen' ?></h2>
      <p class="muted small"><?= $link['sent'] ? 'Wurde per E-Mail verschickt. ' : '' ?>Nur jetzt sichtbar. Gib ihn nur direkt an die Person weiter – wer ihn hat, kann das Passwort festlegen.</p></div>
    <div class="license-key"><code class="mono small"><?= e($link['url']) ?></code><button type="button" class="pill pill-sm" data-copy="<?= e($link['url']) ?>">Kopieren</button></div>
  </section>
<?php endif ?>

<div class="request-layout">
  <div class="stack-lg">
    <form class="panel glass stack" action="/mitarbeiter/<?= (int)$m['id'] ?>" method="post">
      <?= csrf_field() ?>
      <h2>Stammdaten</h2>
      <label class="field<?= invalid('name') ?>"><span>Name</span><input type="text" name="name" maxlength="80" required value="<?= e(old('name', $m['name'])) ?>"><?= error_for('name') ?></label>
      <fieldset class="field<?= invalid('role') ?>"><legend>Rolle</legend>
        <div class="radio-cards">
          <?php foreach (['staff' => 'Mitarbeiter', 'admin' => 'Admin'] as $k => $l): ?>
            <label class="radio-card"><input type="radio" name="role" value="<?= $k ?>"<?= old('role', $m['role']) === $k ? ' checked' : '' ?>><span><b><?= $l ?></b></span></label>
          <?php endforeach ?>
        </div><?= error_for('role') ?>
      </fieldset>
      <div class="row-end"><button class="pill pill-sm" type="submit">Speichern</button></div>
    </form>

    <section class="panel glass">
      <h2>Aktivität</h2>
      <?php if ($events): ?>
        <ul class="timeline">
          <?php foreach ($events as $ev): ?>
            <li<?= in_array($ev['event'], ['login_failed', 'twofa_failed'], true) ? ' class="is-bad"' : '' ?>><span class="muted small"><?= date_de($ev['created_at'], true) ?></span>
              <span><?= e(Account::EVENTS[$ev['event']] ?? $ev['event']) ?> <small class="muted"><?= e(trim($ev['agent'] . ($ev['ip_hint'] !== '' ? ' · ' . $ev['ip_hint'] : ''), ' ·')) ?></small></span></li>
          <?php endforeach ?>
        </ul>
      <?php else: ?><p class="empty mt-sm">Noch keine Anmeldungen.</p><?php endif ?>
    </section>
  </div>

  <aside>
    <?php if (!$self && $m['active']): ?>
      <form class="panel glass stack" action="/mitarbeiter/<?= (int)$m['id'] ?>/link" method="post">
        <?= csrf_field() ?>
        <h2>Zugang</h2>
        <p class="muted small">Erstellt einen neuen Link zum Festlegen des Passworts<?= $mail ? ' und schickt ihn per E-Mail' : '' ?>.</p>
        <button class="pill pill-sm pill-ghost" type="submit">Passwort-Link erstellen</button>
      </form>
    <?php endif ?>
    <?php if (!$self && $m['totp_enabled_at']): ?>
      <form class="panel glass stack" action="/mitarbeiter/<?= (int)$m['id'] ?>/2fa-reset" method="post" data-confirm="Zwei-Faktor-Anmeldung zurücksetzen? Die Person muss sie neu einrichten.">
        <?= csrf_field() ?>
        <h2>Zwei-Faktor</h2>
        <p class="muted small">Nur, wenn die Person ihr Handy und ihre Codes verloren hat.</p>
        <button class="pill pill-sm pill-ghost" type="submit">2FA zurücksetzen</button>
      </form>
    <?php endif ?>
    <?php if (!$self): ?>
      <form class="panel glass stack" action="/mitarbeiter/<?= (int)$m['id'] ?>/status" method="post" data-confirm="<?= $m['active'] ? 'Zugang deaktivieren? Alle Sitzungen werden beendet.' : 'Zugang wieder aktivieren?' ?>">
        <?= csrf_field() ?>
        <h2><?= $m['active'] ? 'Deaktivieren' : 'Aktivieren' ?></h2>
        <button class="link-btn<?= $m['active'] ? ' danger' : '' ?>" type="submit"><?= $m['active'] ? 'Zugang deaktivieren' : 'Zugang aktivieren' ?></button>
      </form>
    <?php else: ?>
      <section class="panel glass stack"><h2>Dein Konto</h2><p class="muted small">Passwort, E-Mail und 2FA änderst du unter <a href="/einstellungen/sicherheit">Einstellungen</a>.</p></section>
    <?php endif ?>
  </aside>
</div>
