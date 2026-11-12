<?php $f = static fn(string $k, string $default = '') => e(old($k, (string)($smtp[$k] ?? $default))); ?>
<?php App\Core\View::partial('staff/settings/_tabs', ['tabs' => $tabs, 'active' => 'email']) ?>

<div class="notice<?= $enabled ? '' : ' notice-warn' ?>">
  <?= $enabled
    ? 'E-Mail-Versand ist <b>aktiv</b>. Kunden werden bei Antworten, Angeboten und Statuswechseln benachrichtigt.'
    : 'E-Mail-Versand ist <b>aus</b>. Kunden sehen Antworten nur über ihren Link bzw. ihr Konto.' ?>
</div>

<div class="grid-side">
  <form class="panel glass stack" action="/verwaltung/email" method="post">
    <?= csrf_field() ?>
    <div><h2>Mailserver (SMTP)</h2><p class="muted small">Für Mailcow: ein Postfach wie <span class="mono">no-reply@clyve.dev</span> anlegen und hier eintragen. Server leer lassen = Versand aus.</p></div>
    <div class="grid-smtp">
      <label class="field<?= invalid('host') ?>"><span>Server</span><input type="text" name="host" maxlength="120" value="<?= $f('host') ?>" placeholder="mail.clyve.dev"><?= error_for('host') ?></label>
      <label class="field<?= invalid('port') ?>"><span>Port</span><input type="number" name="port" min="1" max="65535" value="<?= $f('port', '587') ?>"><?= error_for('port') ?></label>
      <label class="field<?= invalid('encryption') ?>"><span>Verschlüsselung</span>
        <select name="encryption">
          <?php foreach (['tls' => 'STARTTLS (Port 587)', 'ssl' => 'SSL/TLS (Port 465)', 'none' => 'Keine (nur localhost)'] as $k => $l): ?>
            <option value="<?= $k ?>"<?= old('encryption', (string)($smtp['encryption'] ?? 'tls')) === $k ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach ?>
        </select><?= error_for('encryption') ?></label>
    </div>
    <div class="grid-2">
      <label class="field<?= invalid('username') ?>"><span>Benutzername</span><input type="text" name="username" maxlength="160" autocomplete="off" value="<?= $f('username') ?>"><?= error_for('username') ?></label>
      <label class="field<?= invalid('password') ?>"><span>Passwort</span>
        <input type="password" name="password" maxlength="200" autocomplete="new-password" placeholder="<?= $hasPassword ? '•••••••• gespeichert – leer lassen = unverändert' : '' ?>"><?= error_for('password') ?></label>
    </div>
    <?php if ($hasPassword): ?><label class="consent"><input type="checkbox" name="password_clear" value="1"><span>Gespeichertes Passwort entfernen</span></label><?php endif ?>
    <div class="grid-2">
      <label class="field<?= invalid('from') ?>"><span>Absenderadresse</span><input type="email" name="from" maxlength="160" value="<?= $f('from') ?>" placeholder="no-reply@clyve.dev"><?= error_for('from') ?></label>
      <label class="field<?= invalid('from_name') ?>"><span>Absendername</span><input type="text" name="from_name" maxlength="80" value="<?= $f('from_name', (string)config('business.name', 'Clyve.dev')) ?>"><?= error_for('from_name') ?></label>
    </div>
    <label class="field<?= invalid('mail_to') ?>"><span>Benachrichtigungen an mich <small class="muted">(optional, zusätzlich zu Discord)</small></span>
      <input type="email" name="mail_to" maxlength="160" value="<?= e(old('mail_to', $mailTo)) ?>" placeholder="du@beispiel.de"><?= error_for('mail_to') ?></label>
    <div class="row-end"><button class="pill" type="submit">Speichern</button></div>
  </form>

  <aside class="stack-lg">
    <section class="panel glass stack">
      <h2>Testen</h2>
      <p class="muted small">Schickt eine Test-E-Mail an deine Staff-Adresse (<?= e($user['email']) ?>).</p>
      <form action="/verwaltung/test-mail" method="post"><?= csrf_field() ?><button class="pill pill-sm pill-ghost" type="submit"<?= $enabled ? '' : ' disabled' ?>>Test-E-Mail senden</button></form>
    </section>
    <section class="panel glass">
      <h2>Wann wird gemailt?</h2>
      <ul class="ticks mt-sm">
        <li>Eingangsbestätigung mit privatem Link</li>
        <li>Deine Antworten (nicht interne Notizen)</li>
        <li>Neues Angebot</li>
        <li>In Arbeit, Geliefert, Abgeschlossen, Abgelehnt</li>
      </ul>
    </section>
  </aside>
</div>
