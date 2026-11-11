<ol class="thread">
  <?php foreach ($messages as $m): ?>
    <?php if ($m['author_type'] === 'system'): ?>
      <li class="msg-system"><span><?= e($m['body']) ?> · <?= date_de($m['created_at'], true) ?></span></li>
      <?php continue; endif ?>
    <?php $mine = $m['author_type'] === $viewer; $internal = !empty($m['internal']); ?>
    <li class="msg<?= $mine ? ' msg-mine' : '' ?><?= $m['author_type'] === 'staff' ? ' msg-staff' : '' ?><?= $internal ? ' msg-internal' : '' ?>">
      <header>
        <b><?= e($m['author_name'] ?? 'Unbekannt') ?></b>
        <?php if ($internal): ?><span class="badge badge-warn">Interne Notiz</span>
        <?php elseif ($m['author_type'] === 'staff'): ?><span class="badge badge-info">Staff</span><?php endif ?>
        <time><?= date_de($m['created_at'], true) ?></time>
      </header>
      <div class="msg-body"><?= nl2p($m['body']) ?></div>
    </li>
  <?php endforeach ?>
</ol>
