<ol class="thread">
  <?php foreach ($messages as $m): $mine = $m['author_type'] === $viewer; ?>
    <li class="msg <?= $mine ? 'msg-mine' : '' ?> <?= $m['author_type'] === 'staff' ? 'msg-staff' : '' ?>">
      <header>
        <b><?= e($m['author_name'] ?? 'Unbekannt') ?></b>
        <?php if ($m['author_type'] === 'staff'): ?><span class="badge badge-info">Team</span><?php endif ?>
        <time><?= date_de($m['created_at'], true) ?></time>
      </header>
      <div class="msg-body"><?= nl2p($m['body']) ?></div>
    </li>
  <?php endforeach ?>
</ol>
