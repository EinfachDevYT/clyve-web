<?php App\Core\View::partial('staff/settings/_tabs', ['tabs' => $tabs, 'active' => 'integrationen']) ?>

<form class="stack-lg" action="/verwaltung/integrationen" method="post">
  <?= csrf_field() ?>
  <div class="grid-2 gap-lg align-start">
    <section class="panel glass stack">
      <div class="row-between"><h2>Discord</h2><?= $hasWebhook ? '<span class="badge badge-ok">verbunden</span>' : '<span class="badge badge-muted">aus</span>' ?></div>
      <p class="muted small">Neue Anfragen, Antworten von Kunden, angenommene Angebote und Tickets landen als Nachricht in deinem Discord-Kanal. Kanal-Einstellungen → Integrationen → Webhooks → Neuer Webhook → URL kopieren.</p>
      <label class="field<?= invalid('discord_webhook') ?>"><span>Webhook-URL</span>
        <input type="url" name="discord_webhook" autocomplete="off" placeholder="<?= $hasWebhook ? '•••••••• gespeichert – leer lassen = unverändert' : 'https://discord.com/api/webhooks/…' ?>"><?= error_for('discord_webhook') ?></label>
      <?php if ($hasWebhook): ?><label class="consent"><input type="checkbox" name="discord_webhook_clear" value="1"><span>Webhook entfernen</span></label><?php endif ?>
      <label class="consent"><input type="checkbox" name="error_alerts" value="1"<?= \App\Services\Settings::on('error_alerts') ? ' checked' : '' ?>><span>Fehler-Meldungen senden – bei Serverfehlern oder fehlgeschlagenen Backups (gleicher Fehler max. 1× pro Stunde)</span></label>
    </section>

    <section class="panel glass stack">
      <div class="row-between"><h2>Devlog-Kanal</h2><?= $hasDevlogHook ? '<span class="badge badge-ok">verbunden</span>' : '<span class="badge badge-muted">aus</span>' ?></div>
      <p class="muted small">Webhook des öffentlichen Kanals, in dem Devlog-Beiträge erscheinen sollen (nicht der interne Benachrichtigungs-Kanal). Ohne Webhook kannst du die fertige Nachricht im Devlog kopieren und selbst posten.</p>
      <label class="field<?= invalid('devlog_webhook') ?>"><span>Webhook-URL</span>
        <input type="url" name="devlog_webhook" autocomplete="off" placeholder="<?= $hasDevlogHook ? '•••••••• gespeichert – leer lassen = unverändert' : 'https://discord.com/api/webhooks/…' ?>"><?= error_for('devlog_webhook') ?></label>
      <?php if ($hasDevlogHook): ?><label class="consent"><input type="checkbox" name="devlog_webhook_clear" value="1"><span>Webhook entfernen</span></label><?php endif ?>
      <label class="field<?= invalid('devlog_role') ?>"><span>Rolle erwähnen <small class="muted">(optional, Rollen-ID)</small></span>
        <input type="text" name="devlog_role" inputmode="numeric" maxlength="22" class="mono" value="<?= e(old('devlog_role', (string)config('devlog_role', ''))) ?>" placeholder="z. B. 112233445566778899"><?= error_for('devlog_role') ?></label>
    </section>

    <section class="panel glass stack">
      <div class="row-between"><h2>Tebex</h2><?= !empty($tebex['webstore_token']) ? '<span class="badge badge-ok">verbunden</span>' : '<span class="badge badge-muted">aus</span>' ?></div>
      <p class="muted small">Für den Script-Shop: Tebex-Panel → Integrations → Headless API → öffentlichen Webstore-Identifier kopieren.</p>
      <label class="field<?= invalid('webstore_token') ?>"><span>Webstore-Identifier</span>
        <input type="text" name="webstore_token" maxlength="80" class="mono" value="<?= e(old('webstore_token', (string)($tebex['webstore_token'] ?? ''))) ?>"><?= error_for('webstore_token') ?></label>
      <label class="field<?= invalid('store_url') ?>"><span>Shop-Adresse</span>
        <input type="url" name="store_url" maxlength="120" value="<?= e(old('store_url', (string)($tebex['store_url'] ?? ''))) ?>" placeholder="https://deinshop.tebex.io"><?= error_for('store_url') ?></label>
      <label class="field<?= invalid('image_hosts') ?>"><span>Erlaubte Bild-Hosts <small class="muted">(durch Komma getrennt)</small></span>
        <input type="text" name="image_hosts" maxlength="300" class="mono" value="<?= e(old('image_hosts', implode(', ', $imageHosts))) ?>"><?= error_for('image_hosts') ?></label>
    </section>
  </div>
  <div class="row-between">
    <span></span>
    <button class="pill" type="submit">Speichern</button>
  </div>
</form>

<?php if ($hasWebhook): ?>
  <form class="mt" action="/verwaltung/discord-test" method="post"><?= csrf_field() ?><button class="pill pill-sm pill-ghost" type="submit">Test-Nachricht an Discord senden</button></form>
<?php endif ?>
