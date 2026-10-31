<?php
require __DIR__ . '/includes/init.php';

$policies   = all_policies();
$page_title = 'Chính sách mua hàng';
$page_desc  = 'Chính sách bảo hành, đổi trả, vận chuyển, thanh toán và bảo mật thông tin tại ' . cfg('site_name') . '.';
$nav_active = 'policy';
$body_class = 'page-policy';

include ROOT . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Chính sách']]) ?>
    <h1 class="page-hero__title"><?= e(txt('policy.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('policy.lead')) ?></p>
  </div>
</section>

<div class="container policy-layout">
  <nav class="policy-toc" aria-label="Mục lục chính sách">
    <ul>
      <?php foreach ($policies as $key => $pol): ?>
        <li><a href="#<?= e($key) ?>"><?= e($pol['title']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <div class="policy-content">
    <?php foreach ($policies as $key => $pol): ?>
      <section class="policy prose" id="<?= e($key) ?>" aria-labelledby="<?= e($key) ?>-title">
        <h2 id="<?= e($key) ?>-title"><?= e($pol['title']) ?></h2>
        <?= fill_placeholders($pol['content']) ?>
      </section>
    <?php endforeach; ?>
  </div>
</div>

<?php include ROOT . '/includes/footer.php'; ?>
