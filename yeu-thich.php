<?php
/** Sản phẩm yêu thích & đã xem gần đây — lưu trên trình duyệt của khách (không cần đăng nhập) */
require __DIR__ . '/includes/init.php';

$page_title = 'Sản phẩm yêu thích';
$page_desc  = 'Danh sách sản phẩm yêu thích và đã xem gần đây của bạn tại ' . cfg('site_name') . '.';
$head_extra = '<meta name="robots" content="noindex">';
$body_class = 'page-wishlist';

include ROOT . '/includes/header.php';
?>

<section class="page-hero page-hero--sm">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Yêu thích']]) ?>
    <h1 class="page-hero__title"><?= e(txt('wish.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('wish.lead')) ?></p>
  </div>
</section>

<section class="section section--tight" data-wish-page>
  <div class="container">
    <div class="product-grid" data-wish-list aria-live="polite"></div>
    <div class="empty-state" data-wish-empty hidden>
      <?= icon('heart') ?>
      <h2>Chưa có sản phẩm yêu thích</h2>
      <p>Bấm biểu tượng trái tim trên sản phẩm để lưu lại những món bạn ưng ý.</p>
      <div class="empty-state__actions"><a class="btn btn--primary" href="<?= e(url('san-pham')) ?>">Khám phá sản phẩm</a></div>
    </div>
  </div>
</section>

<section class="section section--tight section--muted" data-recent="" aria-labelledby="recent-title" hidden>
  <div class="container">
    <h2 id="recent-title" class="section-title section-title--sm">Sản phẩm bạn đã xem gần đây</h2>
    <div class="product-grid" data-recent-list></div>
  </div>
</section>

<?php include ROOT . '/includes/footer.php'; ?>
