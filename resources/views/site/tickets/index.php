<?php use App\Core\View; ob_start() ?>
<header class="page-head">
  <div><p class="kicker mono">Kundenbereich</p><h1>Support</h1></div>
  <a class="pill pill-sm" href="/konto/tickets/neu">Neues Ticket</a>
</header>

<?php if (!$tickets): ?>
  <div class="panel glass empty-state">
    <img src="/assets/img/blossom.svg" alt="" width="48" height="48">
    <p>Alles ruhig hier. Brauchst du Hilfe?</p>
    <a class="pill pill-sm" href="/konto/tickets/neu">Ticket erstellen</a>
  </div>
<?php else: ?>
  <div class="table-wrap glass">
    <table class="table table-link">
      <thead><tr><th>#</th><th>Betreff</th><th>Aktualisiert</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td data-label="#" class="mono"><?= (int)$t['id'] ?></td>
            <td data-label="Betreff"><a href="/konto/tickets/<?= (int)$t['id'] ?>"><?= e($t['subject']) ?></a></td>
            <td data-label="Aktualisiert"><?= date_de($t['updated_at'], true) ?></td>
            <td data-label="Status"><?= View::badge($t['status']) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
<?php endif ?>
<?php View::partial('site/account/_shell', ['user' => $user, 'inner' => ob_get_clean()]) ?>
