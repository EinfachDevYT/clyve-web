<?php
$size = static fn(int $b): string => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format(max(1, $b / 1024), 0, ',', '.') . ' KB';
$ago = static function (?float $h): string {
    if ($h === null) {
        return 'noch nie';
    }
    if ($h < 1) {
        return 'vor ' . max(1, (int)round($h * 60)) . ' Min.';
    }
    return $h < 48 ? 'vor ' . (int)round($h) . ' Std.' : 'vor ' . (int)floor($h / 24) . ' Tagen';
};
$stale = $age === null || $age > 26;
?>
<?php App\Core\View::partial('staff/settings/_tabs', ['tabs' => $tabs, 'active' => 'backups']) ?>

<?php if ($stale): ?>
  <div class="notice notice-warn"><?= $age === null ? 'Es gibt noch kein Backup.' : 'Das letzte Backup ist älter als einen Tag.' ?>
    Läuft der Cronjob <code>bin/cron.php</code>? (siehe deploy/DEPLOY.md) – oder sichere jetzt von Hand.</div>
<?php endif ?>

<div class="grid-side">
  <section class="panel glass stack">
    <div class="row-between"><h2>Datenbank-Backups</h2><?= $stale ? '<span class="badge badge-warn">veraltet</span>' : '<span class="badge badge-ok">aktuell</span>' ?></div>
    <?php if (!$backups): ?>
      <p class="empty">Noch keine Backups.</p>
    <?php else: ?>
      <form class="stack" action="/verwaltung/backups/download" method="post" data-native>
        <?= csrf_field() ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Datei</th><th>Erstellt</th><th class="num">Größe</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($backups as $b): ?>
                <tr>
                  <td data-label="Datei" class="mono small"><?= e($b['name']) ?></td>
                  <td data-label="Erstellt"><?= e(date('d.m.Y, H:i', $b['time'])) ?></td>
                  <td data-label="Größe" class="num"><?= $size($b['size']) ?></td>
                  <td class="num"><button class="chip-btn" type="submit" name="file" value="<?= e($b['name']) ?>">Herunterladen</button></td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
        <label class="field<?= invalid('password') ?>"><span>Dein Passwort <small class="muted">(zum Herunterladen – Backups enthalten alle Kundendaten)</small></span>
          <input type="password" name="password" autocomplete="current-password" required><?= error_for('password') ?></label>
      </form>
    <?php endif ?>
  </section>

  <aside class="stack">
    <section class="panel glass stack">
      <h2>Status</h2>
      <dl class="kv">
        <dt>Letztes Backup</dt><dd><?= e($ago($age)) ?></dd>
        <dt>Automatisch</dt><dd>täglich ab <?= sprintf('%02d', $hour) ?>:00 Uhr</dd>
        <dt>Aufbewahrung</dt><dd><?= (int)$keepDays ?> Tage (mind. 3 Stück)</dd>
        <dt>Anzahl</dt><dd><?= count($backups) ?></dd>
      </dl>
      <form action="/verwaltung/backups" method="post"><?= csrf_field() ?><button class="pill pill-sm pill-block" type="submit">Jetzt sichern</button></form>
    </section>
    <section class="panel glass stack small">
      <h2>Gut zu wissen</h2>
      <p class="muted">Jedes Backup wird vor dem Speichern auf Fehler geprüft und komprimiert. Gespeichert unter <code class="break"><?= e($dir) ?></code> – nicht öffentlich erreichbar.</p>
      <p class="muted">Kopiere Backups regelmäßig auf einen anderen Rechner. Bewahre auch <code>config/config.php</code> sicher auf: Ohne den <code>app_secret</code> lassen sich gespeicherte Zugangsdaten und 2FA-Schlüssel nicht entschlüsseln.</p>
      <p class="muted">Wiederherstellen: siehe „Backup“ in deploy/DEPLOY.md.</p>
    </section>
  </aside>
</div>
