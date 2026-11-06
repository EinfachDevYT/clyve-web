<header class="page-head">
  <div><p class="kicker mono">Team</p><h1>Kunden</h1></div>
  <form class="search" action="/kunden" method="get" role="search">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, E-Mail, Discord…" aria-label="Suchen">
  </form>
</header>

<div class="table-wrap glass">
  <table class="table">
    <thead><tr><th>Nr.</th><th>Name</th><th>E-Mail</th><th>Discord</th><th>Seit</th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td data-label="Nr." class="mono"><?= sprintf('K-%05d', (int)$c['id']) ?></td>
          <td data-label="Name"><a href="/kunden/<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a></td>
          <td data-label="E-Mail"><?= e($c['email']) ?></td>
          <td data-label="Discord" class="mono"><?= e($c['discord'] ?: '–') ?></td>
          <td data-label="Seit"><?= date_de($c['created_at']) ?></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$customers): ?><tr><td colspan="5" class="empty">Keine Kunden gefunden.</td></tr><?php endif ?>
    </tbody>
  </table>
</div>
