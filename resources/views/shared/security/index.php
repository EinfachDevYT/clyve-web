<?php
use App\Services\Account;
$u = $user;
ob_start();
?>
<?php if ($revealed): ?>
  <section class="panel glass license-once stack">
    <div><h2>Deine Wiederherstellungscodes</h2>
      <p class="muted small">Nur jetzt sichtbar – beim Neuladen verschwinden sie wieder. Durchgestrichene Codes wurden bereits verwendet.</p></div>
    <ol class="recovery-codes mono"><?php foreach ($revealed as $rc): ?><li<?= $rc['used'] ? ' class="is-used"' : '' ?>><?= e($rc['code']) ?></li><?php endforeach ?></ol>
    <div><button type="button" class="pill pill-sm" data-copy="<?= e(implode("\n", array_column(array_filter($revealed, static fn($rc) => !$rc['used']), 'code'))) ?>">Unbenutzte kopieren</button></div>
  </section>
<?php endif ?>

<?php if ($recoveryCodes): ?>
  <section class="panel glass license-once stack">
    <div><h2>Deine Wiederherstellungscodes</h2>
      <p class="muted small">Damit kommst du in dein Konto, falls du dein Handy verlierst. Jeder Code funktioniert einmal. Speichere sie jetzt sicher ab (z. B. im Passwort-Manager) – sie werden nicht noch einmal angezeigt.</p></div>
    <ol class="recovery-codes mono"><?php foreach ($recoveryCodes as $c): ?><li><?= e($c) ?></li><?php endforeach ?></ol>
    <div><button type="button" class="pill pill-sm" data-copy="<?= e(implode("\n", $recoveryCodes)) ?>">Alle kopieren</button></div>
  </section>
<?php endif ?>

<div class="grid-2 gap-lg align-start">
  <div class="stack-lg">
    <section class="panel glass stack" id="zwei-faktor">
      <div class="row-between">
        <h2>Zwei-Faktor-Anmeldung</h2>
        <?= $u['has_2fa'] ? '<span class="badge badge-ok">Aktiv</span>' : '<span class="badge badge-muted">Aus</span>' ?>
      </div>
      <?php if (!$u['has_2fa']): ?>
        <p class="muted small">Schützt dein Konto zusätzlich mit einem Code aus einer App wie Google Authenticator, Microsoft Authenticator, Aegis oder 1Password – selbst wenn jemand dein Passwort kennt.</p>
        <div><a class="pill pill-sm" href="<?= e($base) ?>/sicherheit/2fa">Einrichten</a></div>
      <?php else: ?>
        <p class="muted small">Aktiv seit <?= date_de($u['totp_enabled_at']) ?>. Noch <b><?= (int)$recoveryLeft ?></b> von 10 Wiederherstellungscodes übrig.</p>
        <?php if ($canReveal): ?>
          <details class="snippet"<?= invalid('password_reveal') || invalid('code_reveal') ? ' open' : '' ?>>
            <summary class="panel-summary"><b>Codes anzeigen</b></summary>
            <?php if ($hasCopy): ?>
              <form class="stack mt-sm" action="<?= e($base) ?>/sicherheit/2fa/codes/anzeigen" method="post">
                <?= csrf_field() ?>
                <p class="muted small">Nur für dein eigenes Admin-Konto. Zur Sicherheit brauchst du Passwort und einen aktuellen Code aus der App; die Anzeige wird protokolliert.</p>
                <label class="field<?= invalid('password_reveal') ?>"><span>Passwort</span><input type="password" name="password_reveal" autocomplete="current-password" required><?= error_for('password_reveal') ?></label>
                <label class="field<?= invalid('code_reveal') ?>"><span>Code aus der App</span><input type="text" name="code" class="mono" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required><?= error_for('code_reveal') ?></label>
                <div><button class="pill pill-sm pill-ghost" type="submit">Codes anzeigen</button></div>
              </form>
            <?php else: ?>
              <p class="muted small mt-sm">Für deine aktuellen Codes ist keine Kopie gespeichert (sie wurden vor dieser Funktion oder vor deiner Admin-Rolle erzeugt). Erzeuge einmal neue Codes – danach kannst du sie hier jederzeit wieder anzeigen.</p>
            <?php endif ?>
          </details>
        <?php endif ?>
        <details class="snippet"<?= invalid('password_codes') ? ' open' : '' ?>>
          <summary class="panel-summary"><b>Neue Wiederherstellungscodes</b></summary>
          <form class="stack mt-sm" action="<?= e($base) ?>/sicherheit/2fa/codes" method="post">
            <?= csrf_field() ?>
            <label class="field<?= invalid('password_codes') ?>"><span>Passwort</span><input type="password" name="password_codes" autocomplete="current-password" required><?= error_for('password_codes') ?></label>
            <div><button class="pill pill-sm pill-ghost" type="submit">Neue Codes erzeugen</button></div>
          </form>
        </details>
        <details class="snippet"<?= invalid('password_2fa') || invalid('code_off') ? ' open' : '' ?>>
          <summary class="panel-summary"><b>Deaktivieren</b></summary>
          <form class="stack mt-sm" action="<?= e($base) ?>/sicherheit/2fa/deaktivieren" method="post">
            <?= csrf_field() ?>
            <label class="field<?= invalid('password_2fa') ?>"><span>Passwort</span><input type="password" name="password_2fa" autocomplete="current-password" required><?= error_for('password_2fa') ?></label>
            <label class="field<?= invalid('code_off') ?>"><span>Code aus der App</span><input type="text" name="code" class="mono" inputmode="numeric" autocomplete="one-time-code" maxlength="11" required><?= error_for('code_off') ?></label>
            <div><button class="pill pill-sm pill-ghost danger" type="submit">Zwei-Faktor-Anmeldung ausschalten</button></div>
          </form>
        </details>
      <?php endif ?>
    </section>

    <form class="panel glass stack" action="<?= e($base) ?>/sicherheit/passwort" method="post">
      <?= csrf_field() ?>
      <h2>Passwort ändern</h2>
      <input type="text" name="username" value="<?= e($u['email']) ?>" autocomplete="username" hidden>
      <label class="field<?= invalid('password_current') ?>"><span>Aktuelles Passwort</span>
        <input type="password" name="password_current" autocomplete="current-password" required><?= error_for('password_current') ?></label>
      <label class="field<?= invalid('password') ?>"><span>Neues Passwort <small class="muted">(mind. 10 Zeichen)</small></span>
        <input type="password" name="password" minlength="10" autocomplete="new-password" required><?= error_for('password') ?></label>
      <label class="field<?= invalid('password_confirm') ?>"><span>Wiederholen</span>
        <input type="password" name="password_confirm" autocomplete="new-password" required><?= error_for('password_confirm') ?></label>
      <p class="muted small"><?= $u['password_changed_at'] ? 'Zuletzt geändert am ' . date_de($u['password_changed_at']) . '. ' : '' ?>Andere Geräte werden danach abgemeldet.</p>
      <div><button class="pill pill-sm" type="submit">Passwort ändern</button></div>
    </form>
  </div>
  <div class="stack-lg">
    <section class="panel glass stack">
      <h2>E-Mail-Adresse</h2>
      <p>Aktuell: <b><?= e($u['email']) ?></b></p>
      <?php if ($pendingEmail): ?>
        <div class="notice">Bestätigung ausstehend für <b><?= e($pendingEmail['data']) ?></b> – schau in dieses Postfach.
          <form class="inline-form" action="<?= e($base) ?>/sicherheit/email/abbrechen" method="post"><?= csrf_field() ?><button class="link-btn" type="submit">Abbrechen</button></form></div>
      <?php endif ?>
      <details class="snippet"<?= invalid('email') || invalid('password_email') ? ' open' : '' ?>>
        <summary class="panel-summary"><b>Ändern</b></summary>
        <form class="stack mt-sm" action="<?= e($base) ?>/sicherheit/email" method="post">
          <?= csrf_field() ?>
          <label class="field<?= invalid('email') ?>"><span>Neue E-Mail-Adresse</span><input type="email" name="email" maxlength="160" value="<?= e(old('email')) ?>" required><?= error_for('email') ?></label>
          <label class="field<?= invalid('password_email') ?>"><span>Passwort zur Bestätigung</span><input type="password" name="password_email" autocomplete="current-password" required><?= error_for('password_email') ?></label>
          <p class="muted small"><?= $mail ? 'Wir schicken einen Bestätigungslink an die neue Adresse. Bis dahin bleibt die alte aktiv.' : 'Die Adresse wird sofort geändert.' ?></p>
          <div><button class="pill pill-sm pill-ghost" type="submit">E-Mail ändern</button></div>
        </form>
      </details>
    </section>

    <section class="panel glass stack">
      <h2>Geräte</h2>
      <p class="muted small">Du hast dich auf einem fremden Gerät angemeldet oder etwas kommt dir komisch vor? Beende alle anderen Sitzungen – nur dieses Gerät bleibt angemeldet.</p>
      <form action="<?= e($base) ?>/sicherheit/abmelden" method="post" data-confirm="Alle anderen Geräte abmelden?">
        <?= csrf_field() ?><button class="pill pill-sm pill-ghost" type="submit">Auf allen anderen Geräten abmelden</button>
      </form>
    </section>
  </div>
</div>

<?php if (Account::type() === 'customer'): $closeBlockers = \App\Services\CustomerDeletion::blockers($u); ?>
<section class="panel glass stack mt">
  <details class="danger-zone"<?= invalid('password_close') || invalid('code_close') || invalid('understood') ? ' open' : '' ?>>
    <summary>Konto schließen</summary>
    <div class="stack">
      <?php if ($closeBlockers): ?>
        <p class="muted small">Du kannst dein Konto gerade nicht selbst schließen:</p>
        <ul class="small"><?php foreach ($closeBlockers as $b): ?><li><?= e($b) ?></li><?php endforeach ?></ul>
        <p class="muted small">Sobald das erledigt ist, geht es hier. Bei Fragen: <a href="/konto/tickets/neu">Support-Ticket</a>.</p>
      <?php else: ?>
        <p class="muted small">Dein Konto, deine Kontaktdaten, Tickets und Anfragen werden gelöscht und du wirst abgemeldet. Ausgestellte Rechnungen bzw. Quittungen müssen wir gesetzlich aufbewahren – sie bleiben mit den Angaben erhalten, die darauf stehen. Das lässt sich nicht rückgängig machen.</p>
        <form class="stack" action="<?= e($base) ?>/konto-schliessen" method="post" data-native data-confirm="Konto wirklich endgültig schließen?">
          <?= csrf_field() ?>
          <label class="field<?= invalid('password_close') ?>"><span>Passwort zur Bestätigung</span><input type="password" name="password_close" autocomplete="current-password" required><?= error_for('password_close') ?></label>
          <?php if ($u['has_2fa']): ?>
            <label class="field<?= invalid('code_close') ?>"><span>Code aus der Authenticator-App</span><input type="text" name="code_close" inputmode="numeric" autocomplete="one-time-code" maxlength="20" required><?= error_for('code_close') ?></label>
          <?php endif ?>
          <label class="consent<?= invalid('understood') ?>"><input type="checkbox" name="understood" value="1" required><span>Ich verstehe, dass mein Konto endgültig gelöscht wird.</span></label>
          <div><button class="pill pill-sm danger" type="submit">Konto endgültig schließen</button></div>
        </form>
      <?php endif ?>
    </div>
  </details>
</section>
<?php endif ?>

<section class="panel glass stack mt">
  <h2>Letzte Aktivität</h2>
  <?php if ($events): ?>
    <ul class="timeline">
      <?php foreach ($events as $ev): $bad = in_array($ev['event'], ['login_failed', 'twofa_failed'], true); ?>
        <li<?= $bad ? ' class="is-bad"' : '' ?>><span class="muted small"><?= date_de($ev['created_at'], true) ?></span>
          <span><?= e(Account::EVENTS[$ev['event']] ?? $ev['event']) ?> <small class="muted"><?= e(trim($ev['agent'] . ($ev['ip_hint'] !== '' ? ' · ' . $ev['ip_hint'] : ''), ' ·')) ?></small></span></li>
      <?php endforeach ?>
    </ul>
  <?php else: ?><p class="empty">Noch keine Einträge.</p><?php endif ?>
</section>
<?php App\Core\View::partial('shared/security/_page', ['user' => $user, 'active' => 'sicherheit', 'inner' => ob_get_clean()]) ?>
