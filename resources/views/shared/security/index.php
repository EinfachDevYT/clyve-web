<?php
use App\Services\Account;
$u = $user;
ob_start();
?>
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
