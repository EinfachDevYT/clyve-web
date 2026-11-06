<?php use App\Core\View; ?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/tickets">Tickets</a> / #<?= (int)$ticket['id'] ?></p>
    <h1><?= e($ticket['subject']) ?></h1>
    <p class="meta"><?= View::badge($ticket['status']) ?> <span><?= e($categories[$ticket['category']] ?? '') ?></span> <a href="/kunden/<?= (int)$ticket['customer_id'] ?>"><?= e($ticket['customer_name']) ?></a></p>
  </div>
  <form action="/tickets/<?= (int)$ticket['id'] ?>/status" method="post" class="inline-select">
    <?= csrf_field() ?>
    <select name="status" aria-label="Status">
      <?php foreach (['open', 'answered', 'closed'] as $s): ?><option value="<?= $s ?>"<?= $s === $ticket['status'] ? ' selected' : '' ?>><?= strip_tags(View::badge($s)) ?></option><?php endforeach ?>
    </select>
    <button class="pill pill-sm" type="submit">Setzen</button>
  </form>
</header>

<?php View::partial('shared/thread', ['messages' => $messages, 'viewer' => 'staff']) ?>

<form class="panel glass stack reply" action="/tickets/<?= (int)$ticket['id'] ?>/antwort" method="post">
  <?= csrf_field() ?>
  <label class="field<?= invalid('body') ?>"><span>Antwort</span>
    <textarea name="body" rows="5" maxlength="5000" required><?= e(old('body')) ?></textarea><?= error_for('body') ?></label>
  <div class="row-between">
    <label class="consent"><input type="checkbox" name="close" value="1"><span>Danach schließen</span></label>
    <button class="pill" type="submit">Antwort senden</button>
  </div>
</form>
