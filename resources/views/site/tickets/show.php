<?php use App\Core\View; ?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/konto/tickets">Support</a> / #<?= (int)$ticket['id'] ?></p>
    <h1><?= e($ticket['subject']) ?></h1>
    <p class="meta"><?= View::badge($ticket['status']) ?> <span><?= e($categories[$ticket['category']] ?? '') ?></span> <span>erstellt <?= date_de($ticket['created_at'], true) ?></span></p>
  </div>
  <?php if ($ticket['status'] !== 'closed'): ?>
    <form action="/konto/tickets/<?= (int)$ticket['id'] ?>/schliessen" method="post">
      <?= csrf_field() ?>
      <button class="pill pill-ghost pill-sm" type="submit">Ticket schließen</button>
    </form>
  <?php endif ?>
</header>

<?php View::partial('shared/thread', ['messages' => $messages, 'viewer' => 'customer']) ?>

<form class="panel glass stack reply" action="/konto/tickets/<?= (int)$ticket['id'] ?>/antwort" method="post">
  <?= csrf_field() ?>
  <label class="field<?= invalid('body') ?>"><span><?= $ticket['status'] === 'closed' ? 'Ticket wieder öffnen mit einer Antwort' : 'Antworten' ?></span>
    <textarea name="body" rows="4" maxlength="5000" required><?= e(old('body')) ?></textarea><?= error_for('body') ?></label>
  <div class="row-end"><button class="pill" type="submit">Senden</button></div>
</form>
