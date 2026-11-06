<nav class="filters">
  <?php foreach ($options as $value => $label): ?>
    <a href="<?= e($base . ($value === '' ? '' : '?status=' . $value)) ?>"<?= $current === $value ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach ?>
</nav>
