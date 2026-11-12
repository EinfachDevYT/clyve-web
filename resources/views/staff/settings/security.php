<?php
$toggle = static function (string $key, string $label, string $hint) use ($settings): string {
    return '<label class="toggle"><input type="checkbox" name="' . e($key) . '" value="1"' . ($settings[$key] === '1' ? ' checked' : '') . '>'
        . '<span class="toggle-ui" aria-hidden="true"></span><span><b>' . e($label) . '</b><small>' . e($hint) . '</small></span></label>';
};
$without = array_filter($staff, static fn($s) => !$s['totp_enabled_at']);
?>
<?php App\Core\View::partial('staff/settings/_tabs', ['tabs' => $tabs, 'active' => 'sicherheit']) ?>

<div class="grid-side">
  <form class="panel glass stack" action="/verwaltung/sicherheit" method="post">
    <?= csrf_field() ?>
    <h2>Richtlinien</h2>
    <?= $toggle('staff_require_2fa', 'Zwei-Faktor-Anmeldung für Staff verpflichtend', 'Mitarbeiter ohne 2FA werden nach der Anmeldung direkt zur Einrichtung geschickt und können sonst nichts öffnen.') ?>
    <?= $toggle('security_mails', 'Sicherheits-E-Mails', 'Kunden und Mitarbeiter bekommen eine E-Mail, wenn Passwort, E-Mail-Adresse oder Zwei-Faktor-Anmeldung geändert werden.') ?>
    <div class="row-end"><button class="pill" type="submit">Speichern</button></div>
  </form>
  <aside class="panel glass stack">
    <h2>Status</h2>
    <dl class="kv">
      <dt>Aktive Mitarbeiter</dt><dd><?= count($staff) ?></dd>
      <dt>Ohne 2FA</dt><dd><?= $without ? implode(', ', array_map(static fn($s) => e($s['name']), $without)) : '<span class="badge badge-ok">keiner</span>' ?></dd>
    </dl>
    <p class="muted small"><a href="/mitarbeiter">Mitarbeiter verwalten →</a></p>
  </aside>
</div>
