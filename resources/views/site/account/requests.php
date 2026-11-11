<?php use App\Core\View; use App\Services\Notifier; ob_start() ?>
<header class="page-head">
  <div><p class="kicker mono">Kundenbereich</p><h1>Meine Anfragen</h1></div>
  <a class="pill pill-sm" href="/#anfrage">Neue Anfrage</a>
</header>

<?php if (!$requests): ?>
  <div class="panel glass"><p class="empty">Du hast noch keine Anfragen gestellt. <a href="/#anfrage">Jetzt anfragen →</a></p></div>
<?php else: ?>
  <div class="table-wrap glass">
    <table class="table">
      <thead><tr><th>ID</th><th>Projekt</th><th>Art</th><th>Aktualisiert</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td data-label="ID" class="mono"><a href="/konto/anfragen/<?= (int)$r['id'] ?>"><?php if ($r['unread_customer']): ?><span class="unread-dot" title="Neue Nachricht"></span><?php endif ?><?= e($r['public_id']) ?></a></td>
            <td data-label="Projekt"><a href="/konto/anfragen/<?= (int)$r['id'] ?>"><?= e($r['title'] ?: '–') ?></a></td>
            <td data-label="Art"><?= e(Notifier::TYPES[$r['type']] ?? $r['type']) ?></td>
            <td data-label="Aktualisiert"><?= date_de($r['updated_at'], true) ?></td>
            <td data-label="Status"><?= View::badge($r['status']) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
<?php endif ?>
<?php View::partial('site/account/_shell', ['user' => $user, 'inner' => ob_get_clean()]) ?>
