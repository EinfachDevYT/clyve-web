<section class="cust-auth">
  <div class="cust-auth-panel">
    <?php App\Core\View::partial('site/auth/_aside', ['mode' => 'login']) ?>
    <div class="cust-auth-main"><?= $inner ?></div>
  </div>
</section>
