<header class="page-head">
  <div><p class="kicker mono">Verwaltung</p><h1>Mitarbeiter</h1></div>
  <a class="pill pill-sm" href="/mitarbeiter/neu">Mitarbeiter einladen</a>
</header>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>Name</th><th>E-Mail</th><th>Rolle</th><th>2FA</th><th>Letzte Anmeldung</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($members as $m): ?>
        <tr>
          <td data-label="Name"><a href="/mitarbeiter/<?= (int)$m['id'] ?>"><?= e($m['name']) ?></a><?= (int)$m['id'] === (int)$user['id'] ? ' <small class="muted">(du)</small>' : '' ?></td>
          <td data-label="E-Mail"><?= e($m['email']) ?></td>
          <td data-label="Rolle"><?= $m['role'] === 'admin' ? '<span class="badge badge-info">Admin</span>' : 'Mitarbeiter' ?></td>
          <td data-label="2FA"><?= $m['totp_enabled_at'] ? '<span class="badge badge-ok">Aktiv</span>' : '<span class="badge badge-warn">Aus</span>' ?></td>
          <td data-label="Letzte Anmeldung"><?= $m['last_login'] ? date_de($m['last_login'], true) : '<span class="muted">noch nie</span>' ?></td>
          <td data-label="Status"><?= $m['active'] ? '<span class="badge badge-ok">Aktiv</span>' : '<span class="badge badge-muted">Deaktiviert</span>' ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
