<?php use App\Core\View; ?>
<header class="page-head">
  <div><p class="kicker mono"><a href="/konto/tickets">Support</a> / Neu</p><h1>Neues Ticket</h1></div>
</header>

<form class="panel glass stack" action="/konto/tickets" method="post">
  <?= csrf_field() ?>
  <div class="grid-2">
    <label class="field<?= invalid('subject') ?>"><span>Betreff</span>
      <input type="text" name="subject" value="<?= e(old('subject')) ?>" maxlength="120" required><?= error_for('subject') ?></label>
    <label class="field<?= invalid('category') ?>"><span>Kategorie</span>
      <select name="category">
        <?php foreach ($categories as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= old('category') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach ?>
      </select></label>
  </div>
  <label class="field<?= invalid('body') ?>"><span>Nachricht</span>
    <textarea name="body" rows="8" maxlength="5000" required placeholder="Beschreib dein Anliegen so genau wie möglich."><?= e(old('body')) ?></textarea><?= error_for('body') ?></label>
  <div class="row-end"><button class="pill" type="submit">Ticket senden</button></div>
</form>
