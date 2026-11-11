<?php
$toggle = static function (string $key, string $label, string $hint) use ($settings): string {
    return '<label class="toggle"><input type="checkbox" name="' . e($key) . '" value="1"' . ($settings[$key] === '1' ? ' checked' : '') . '>'
        . '<span class="toggle-ui" aria-hidden="true"></span><span><b>' . e($label) . '</b><small>' . e($hint) . '</small></span></label>';
};
?>
<?php App\Core\View::partial('staff/settings/_tabs', ['tabs' => $tabs, 'active' => 'allgemein']) ?>

<form class="stack-lg" action="/einstellungen" method="post">
  <?= csrf_field() ?>
  <div class="grid-2 gap-lg align-start">
    <section class="panel glass stack">
      <h2>Aufträge</h2>
      <?= $toggle('requests_open', 'Anfragen annehmen', 'Aus = das Formular wird durch deinen Hinweis ersetzt.') ?>
      <div class="toggle-group">
        <?= $toggle('type_fivem', 'FiveM Scripts', 'Kategorie im Formular') ?>
        <?= $toggle('type_web', 'Websites', 'Kategorie im Formular') ?>
        <?= $toggle('type_bot', 'Discord Bots', 'Kategorie im Formular') ?>
        <?= $toggle('type_other', 'Sonstiges', 'Kategorie im Formular') ?>
      </div>
      <label class="field<?= invalid('requests_closed_message') ?>"><span>Hinweis, wenn Anfragen geschlossen sind</span>
        <textarea name="requests_closed_message" rows="3" maxlength="300"><?= e(old('requests_closed_message', $settings['requests_closed_message'])) ?></textarea><?= error_for('requests_closed_message') ?></label>
    </section>

    <section class="panel glass stack">
      <h2>Bereiche</h2>
      <?= $toggle('registration_open', 'Registrierung', 'Neue Kundenkonten erlauben') ?>
      <?= $toggle('tickets_enabled', 'Support-Tickets', 'Kunden können neue Tickets erstellen') ?>
      <?= $toggle('shop_enabled', 'Script-Shop', 'Seite /scripts mit deinen Tebex-Produkten') ?>

      <h2 class="mt">Texte auf der Startseite</h2>
      <label class="field<?= invalid('availability_label') ?>"><span>Verfügbarkeit (oben im Hero)</span>
        <input type="text" name="availability_label" maxlength="60" value="<?= e(old('availability_label', $settings['availability_label'])) ?>"><?= error_for('availability_label') ?></label>
      <label class="field<?= invalid('response_time') ?>"><span>Antwortzeit</span>
        <input type="text" name="response_time" maxlength="20" value="<?= e(old('response_time', $settings['response_time'])) ?>"><?= error_for('response_time') ?></label>
      <label class="field<?= invalid('announcement') ?>"><span>Ankündigung (Banner auf allen Seiten, leer = aus)</span>
        <input type="text" name="announcement" maxlength="200" value="<?= e(old('announcement', $settings['announcement'])) ?>" placeholder="z. B. Urlaub bis 20.11. – Antworten dauern etwas länger"><?= error_for('announcement') ?></label>
    </section>
  </div>
  <div class="row-end"><button class="pill" type="submit">Speichern</button></div>
</form>
