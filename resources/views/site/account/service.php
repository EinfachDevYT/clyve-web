<?php
use App\Core\View;
use App\Services\Hosting;
use App\Services\Settings;
$state = Hosting::state($s);
$left = Hosting::daysLeft($s);
$progress = Hosting::termProgress($s);
?>
<header class="page-head">
  <div>
    <p class="kicker mono"><a href="/konto/dienste">Dienste</a> / #<?= (int)$s['id'] ?></p>
    <h1><?= e($s['name']) ?></h1>
    <p class="meta"><?= Hosting::badge($s) ?><?php if ($s['plan_name']): ?> <span><?= e($s['plan_name']) ?></span><?php endif ?></p>
  </div>
  <?php if (Settings::on('tickets_enabled') && $s['status'] !== 'cancelled'): ?>
    <a class="pill pill-sm" href="/konto/tickets/neu">Verlängerung oder Hilfe anfragen</a>
  <?php endif ?>
</header>

<?php if ($s['status'] === 'suspended'): ?>
  <div class="notice notice-warn"><b>Dieser Dienst ist pausiert</b><?= $s['suspended_by'] === 'auto' ? ', weil die Laufzeit abgelaufen ist. Nach einer Verlängerung läuft er automatisch wieder.' : ($s['suspended_reason'] !== '' ? ': ' . e($s['suspended_reason']) : '.') ?></div>
<?php elseif ($state === 'grace' || $state === 'overdue'): ?>
  <div class="notice notice-warn">Die Laufzeit ist am <?= date_de($s['expires_on']) ?> abgelaufen.<?= $state === 'grace' ? ' Der Dienst läuft noch bis ' . date_de(Hosting::graceUntil($s)) . ' weiter – bitte verlängere rechtzeitig.' : '' ?></div>
<?php elseif ($left !== null && $left <= 14 && $s['status'] === 'active'): ?>
  <div class="notice">Läuft in <?= $left ?> <?= $left === 1 ? 'Tag' : 'Tagen' ?> ab (<?= date_de($s['expires_on']) ?>).</div>
<?php endif ?>

<div class="grid-2 gap-lg align-start">
  <section class="panel glass stack">
    <h2>Laufzeit</h2>
    <dl class="kv">
      <dt>Start</dt><dd><?= date_de($s['starts_on']) ?></dd>
      <dt>Läuft bis</dt><dd><?= $s['expires_on'] ? date_de($s['expires_on']) : 'Unbefristet' ?></dd>
      <?php if ($left !== null && $s['status'] === 'active' && $left >= 0): ?><dt>Restlaufzeit</dt><dd><?= $left ?> <?= $left === 1 ? 'Tag' : 'Tage' ?></dd><?php endif ?>
      <dt>Preis</dt><dd><?= e(Hosting::priceLabel($s)) ?></dd>
    </dl>
    <?php if ($progress !== null && $s['status'] === 'active'): ?>
      <div class="progress-bar" title="<?= $progress ?> % der Laufzeit"><span data-progress="<?= $progress ?>"></span></div>
    <?php endif ?>
  </section>

  <section class="panel glass stack">
    <h2>Lizenz</h2>
    <dl class="kv">
      <dt>Schlüssel</dt><dd class="mono"><?= e($s['license_hint'] ?: '–') ?></dd>
      <dt>Zuletzt online</dt><dd><?= $s['last_check_at'] ? date_de($s['last_check_at'], true) : 'noch nicht' ?></dd>
    </dl>
    <p class="muted small">Den vollständigen Schlüssel hast du bei der Einrichtung erhalten. Brauchst du einen neuen, melde dich über den Support.</p>
  </section>
</div>

<?php if ($events): ?>
  <section class="panel glass mt">
    <h2>Verlauf</h2>
    <ul class="timeline mt-sm">
      <?php foreach ($events as $ev): ?>
        <li><span class="muted small"><?= date_de($ev['created_at'], true) ?></span><span><?= e($ev['detail']) ?></span></li>
      <?php endforeach ?>
    </ul>
  </section>
<?php endif ?>
