<?php
if (!defined('ROOT')) {
    require __DIR__ . '/includes/init.php';
}
http_response_code(404);
$page_title = 'Không tìm thấy trang';
$page_desc  = 'Trang bạn tìm không tồn tại hoặc đã được chuyển.';
$head_extra = '<meta name="robots" content="noindex">';
$nav_active = '';
$body_class = 'page-404';
$suggest    = array_slice(array_values(array_filter(all_products(), fn($p) => !empty($p['featured']))), 0, 4);

include ROOT . '/includes/header.php';
?>
<section class="section">
  <div class="container empty-state empty-state--page">
    <p class="big-code" aria-hidden="true">404</p>
    <h1>Không tìm thấy trang</h1>
    <?= txt_html('404.text') ?>
    <form class="search-form search-form--block" role="search" action="<?= e(url('san-pham')) ?>" method="get">
      <label class="sr-only" for="nf-q">Tìm kiếm sản phẩm</label>
      <input id="nf-q" name="q" type="search" placeholder="Tìm sản phẩm…">
      <button type="submit" class="search-form__btn"><?= icon('search') ?><span class="sr-only">Tìm kiếm</span></button>
    </form>
    <div class="empty-state__actions">
      <a class="btn btn--primary" href="<?= e(url()) ?>">Về trang chủ</a>
      <a class="btn btn--outline" href="<?= e(url('san-pham')) ?>">Xem sản phẩm</a>
    </div>
  </div>
  <?php if ($suggest): ?>
  <div class="container">
    <h2 class="section-title section-title--sm">Sản phẩm nổi bật</h2>
    <div class="product-grid">
      <?php foreach ($suggest as $p) echo render_product_card($p); ?>
    </div>
  </div>
  <?php endif; ?>
</section>
<?php include ROOT . '/includes/footer.php'; ?>
