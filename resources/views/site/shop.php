<?php use App\Services\Tebex; ?>
<section class="section shop">
  <header class="section-head">
    <p class="kicker mono">Script-Shop</p>
    <h1 class="shop-title">Fertige FiveM Scripts</h1>
    <p class="sub">Sofort einsatzbereit, sauber konfigurierbar. Kauf und Auslieferung laufen sicher über Tebex.</p>
  </header>

  <nav class="filters filters-center" aria-label="Framework">
    <a href="/scripts"<?= $framework === '' ? ' aria-current="page"' : '' ?>>Alle</a>
    <?php foreach (Tebex::FRAMEWORKS as $f): ?>
      <a href="/scripts?framework=<?= rawurlencode($f) ?>"<?= $framework === $f ? ' aria-current="page"' : '' ?>><?= e($f) ?></a>
    <?php endforeach ?>
  </nav>

  <?php if (!$products): ?>
    <div class="closed-panel glass">
      <img src="/assets/brand/clyve-mark.svg" alt="" width="56" height="56">
      <h3><?= $framework ? 'Für ' . e($framework) . ' gibt es gerade nichts' : 'Bald gibt es hier Scripts' ?></h3>
      <p>Du suchst etwas Bestimmtes? Ich baue es dir auch individuell.</p>
    </div>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $pr): ?>
        <article class="product glass<?= $pr['featured'] ? ' is-featured' : '' ?>">
          <div class="product-media">
            <?php if ($pr['image_url'] !== '' && Tebex::safeImage($pr['image_url']) !== ''): ?>
              <img src="<?= e($pr['image_url']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
            <?php else: ?>
              <img class="product-placeholder" src="/assets/brand/clyve-mark.svg" alt="">
            <?php endif ?>
            <?php if ($pr['featured']): ?><span class="badge badge-info product-flag">Empfohlen</span><?php endif ?>
          </div>
          <div class="product-body">
            <?php if ($pr['category']): ?><p class="kicker mono"><?= e($pr['category']) ?></p><?php endif ?>
            <h2><?= e($pr['title']) ?></h2>
            <?php if ($pr['summary']): ?><p class="muted"><?= e($pr['summary']) ?></p><?php endif ?>
            <?php if ($pr['frameworks']): ?>
              <p class="product-tags"><?php foreach (explode(',', $pr['frameworks']) as $f): ?><span class="badge badge-muted"><?= e($f) ?></span><?php endforeach ?></p>
            <?php endif ?>
            <?php if ($pr['description'] !== '' && $pr['description'] !== $pr['summary']): ?>
              <details class="product-more"><summary>Mehr erfahren</summary><div class="prose small"><?= nl2p($pr['description']) ?></div></details>
            <?php endif ?>
          </div>
          <footer class="product-foot">
            <b class="product-price"><?= e($pr['price_label'] ?: '') ?></b>
            <?php if ($pr['tebex_url'] !== '' && Tebex::safeStoreUrl($pr['tebex_url']) !== ''): ?>
              <a class="pill pill-sm" href="<?= e($pr['tebex_url']) ?>" target="_blank" rel="noopener noreferrer">Auf Tebex kaufen ↗</a>
            <?php endif ?>
          </footer>
        </article>
      <?php endforeach ?>
    </div>
  <?php endif ?>

  <?php if ($fivemOpen): ?>
    <div class="shop-cta glass">
      <div><h2>Nicht das Richtige dabei?</h2><p class="muted">Ich entwickle dir ein Script genau nach deinen Wünschen.</p></div>
      <a class="pill" href="/?typ=fivem#anfrage">Individuelles Script anfragen</a>
    </div>
  <?php endif ?>
</section>
