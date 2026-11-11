<?php App\Core\View::partial('staff/settings/_tabs', ['tabs' => $tabs, 'active' => 'integrationen']) ?>

<form class="stack-lg" action="/einstellungen/integrationen" method="post">
  <?= csrf_field() ?>
  <div class="grid-2 gap-lg align-start">
    <section class="panel glass stack">
      <div class="row-between"><h2>Discord</h2><?= $hasWebhook ? '<span class="badge badge-ok">verbunden</span>' : '<span class="badge badge-muted">aus</span>' ?></div>
      <p class="muted small">Neue Anfragen, Antworten von Kunden, angenommene Angebote und Tickets landen als Nachricht in deinem Discord-Kanal. Kanal-Einstellungen → Integrationen → Webhooks → Neuer Webhook → URL kopieren.</p>
      <label class="field<?= invalid('discord_webhook') ?>"><span>Webhook-URL</span>
        <input type="url" name="discord_webhook" autocomplete="off" placeholder="<?= $hasWebhook ? '•••••••• gespeichert – leer lassen = unverändert' : 'https://discord.com/api/webhooks/…' ?>"><?= error_for('discord_webhook') ?></label>
      <?php if ($hasWebhook): ?><label class="consent"><input type="checkbox" name="discord_webhook_clear" value="1"><span>Webhook entfernen</span></label><?php endif ?>
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
  <form class="mt" action="/einstellungen/discord-test" method="post"><?= csrf_field() ?><button class="pill pill-sm pill-ghost" type="submit">Test-Nachricht an Discord senden</button></form>
<?php endif ?>
