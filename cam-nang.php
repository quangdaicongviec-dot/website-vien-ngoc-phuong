<?php
require __DIR__ . '/includes/init.php';

$articles   = all_articles();
$page_title = 'Cẩm nang ngọc phỉ thúy';
$page_desc  = 'Kiến thức chọn mua, đeo và bảo quản trang sức ngọc phỉ thúy: phân biệt ngọc loại A, B, C, đo ni tay, chọn màu ngọc hợp mệnh.';
$nav_active = 'blog';
$body_class = 'page-blog';

include ROOT . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Cẩm nang']]) ?>
    <h1 class="page-hero__title"><?= e(txt('blog.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('blog.lead')) ?></p>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <?php if ($articles): ?>
      <div class="post-grid">
        <?php foreach ($articles as $a) include ROOT . '/includes/article-card.php'; ?>
      </div>
    <?php else: ?>
      <div class="empty-state"><?= icon('leaf') ?><h2>Chưa có bài viết</h2><p>Các bài viết cẩm nang sẽ sớm được cập nhật.</p></div>
    <?php endif; ?>
  </div>
</section>

<?php include ROOT . '/includes/footer.php'; ?>
