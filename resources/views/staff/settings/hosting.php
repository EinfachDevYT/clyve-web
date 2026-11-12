<?php
use App\Services\Hosting;
$toggle = static function (string $key, string $label, string $hint) use ($settings): string {
    return '<label class="toggle"><input type="checkbox" name="' . e($key) . '" value="1"' . ($settings[$key] === '1' ? ' checked' : '') . '>'
        . '<span class="toggle-ui" aria-hidden="true"></span><span><b>' . e($label) . '</b><small>' . e($hint) . '</small></span></label>';
};
$val = static fn(string $k) => e(old($k, $settings[$k]));
?>
<?php App\Core\View::partial('staff/settings/_tabs', ['tabs' => $tabs, 'active' => 'hosting']) ?>

<form class="stack-lg" action="/verwaltung/hosting" method="post">
  <?= csrf_field() ?>
  <div class="grid-2 gap-lg align-start">
    <section class="panel glass stack">
      <div><h2>Laufzeit &amp; Sperre</h2><p class="muted small">Gilt für alle gehosteten Dienste. Änderungen wirken sofort, auch auf laufende Bots.</p></div>
      <?= $toggle('hosting_auto_suspend', 'Automatisch sperren', 'Nach Ablauf + Kulanzzeit wird der Dienst gesperrt. Aus = er läuft weiter und wird nur als überfällig markiert.') ?>
      <div class="grid-2">
        <label class="field<?= invalid('hosting_grace_days') ?>"><span>Kulanzzeit (Tage)</span>
          <input type="number" name="hosting_grace_days" min="0" max="60" value="<?= $val('hosting_grace_days') ?>"><?= error_for('hosting_grace_days') ?></label>
        <label class="field<?= invalid('hosting_reminder_days') ?>"><span>Erinnerung vorher (Tage)</span>
          <input type="text" name="hosting_reminder_days" maxlength="30" class="mono" value="<?= $val('hosting_reminder_days') ?>" placeholder="14,3"><?= error_for('hosting_reminder_days') ?></label>
      </div>
      <label class="field<?= invalid('hosting_suspend_message') ?>"><span>Hinweis bei Sperre <small class="muted">(bekommt der Bot, wenn kein eigener Grund angegeben ist)</small></span>
        <input type="text" name="hosting_suspend_message" maxlength="200" value="<?= $val('hosting_suspend_message') ?>"><?= error_for('hosting_suspend_message') ?></label>
      <?= $toggle('hosting_notify_discord', 'Discord-Benachrichtigung', 'Meldung an deinen Webhook, wenn ein Dienst automatisch gesperrt wird.') ?>
      <?= $toggle('hosting_customer_view', 'Kundenansicht', 'Kunden sehen ihre Dienste, Laufzeit und Status unter „Dienste“ im Kundenbereich.') ?>
    </section>

    <section class="panel glass stack">
      <div><h2>Lizenz-API</h2><p class="muted small">Deine Bots fragen regelmäßig, ob sie laufen dürfen. Den Code dafür findest du bei jedem Dienst.</p></div>
      <?= $toggle('hosting_api_enabled', 'Lizenzprüfung aktiv', 'Aus = die API antwortet „nicht verfügbar“; Bots laufen dann im Offline-Modus weiter.') ?>
      <label class="field<?= invalid('hosting_check_interval') ?>"><span>Prüfintervall (Sekunden)</span>
        <input type="number" name="hosting_check_interval" min="60" max="86400" value="<?= $val('hosting_check_interval') ?>"><?= error_for('hosting_check_interval') ?></label>
      <dl class="kv">
        <dt>Endpunkt</dt><dd class="mono"><?= e(Hosting::apiUrl()) ?></dd>
        <dt>Header</dt><dd class="mono">Authorization: Bearer CLY-…</dd>
        <dt>Automatik</dt><dd>Cronjob <span class="mono">bin/cron.php</span> alle 5 Minuten (siehe DEPLOY.md). Ohne Cronjob läuft die Prüfung beim Öffnen des Staff-Bereichs und bei Lizenzabfragen mit.</dd>
      </dl>
      <p class="muted small"><a href="/hosting/tarife">Tarife verwalten →</a></p>
    </section>
  </div>
  <div class="row-end"><button class="pill" type="submit">Speichern</button></div>
</form>
