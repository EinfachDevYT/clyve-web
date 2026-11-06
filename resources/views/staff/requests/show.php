<?php use App\Core\View; use App\Services\Notifier; ?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/anfragen">Anfragen</a> / <?= e($request['public_id']) ?></p>
    <h1><?= e($request['title'] ?: Notifier::TYPES[$request['type']]) ?></h1>
    <p class="meta"><?= View::badge($request['status']) ?> <span><?= date_de($request['created_at'], true) ?></span></p>
  </div>
  <form action="/anfragen/<?= (int)$request['id'] ?>/status" method="post" class="inline-select">
    <?= csrf_field() ?>
    <select name="status" aria-label="Status">
      <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>"<?= $s === $request['status'] ? ' selected' : '' ?>><?= strip_tags(View::badge($s)) ?></option><?php endforeach ?>
    </select>
    <button class="pill pill-sm" type="submit">Setzen</button>
  </form>
</header>

<div class="grid-side">
  <section class="panel glass">
    <h2>Beschreibung</h2>
    <div class="prose"><?= nl2p($request['message']) ?></div>
  </section>
  <aside class="panel glass">
    <dl class="kv">
      <dt>Name</dt><dd><?= e($request['name']) ?></dd>
      <dt>Art</dt><dd><?= e(Notifier::TYPES[$request['type']] ?? '') ?></dd>
      <dt>Budget</dt><dd><?= e($request['budget'] ?: '–') ?></dd>
      <dt>Termin</dt><dd><?= date_de($request['deadline'] ?: null) ?></dd>
      <dt>Discord</dt><dd class="mono"><?= e($request['discord'] ?: '–') ?></dd>
      <dt>E-Mail</dt><dd><?php if ($request['email']): ?><a href="mailto:<?= e($request['email']) ?>"><?= e($request['email']) ?></a><?php else: ?>–<?php endif ?></dd>
      <dt>Konto</dt><dd><?php if ($request['customer_id']): ?><a href="/kunden/<?= (int)$request['customer_id'] ?>"><?= e($request['customer_name']) ?></a><?php else: ?>Gast<?php endif ?></dd>
    </dl>
  </aside>
</div>
