<?php
require __DIR__ . '/includes/init.php';

$page_title = 'Giới thiệu';
$page_desc  = txt('about.page_desc');
$nav_active = 'about';
$body_class = 'page-about';

include ROOT . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Giới thiệu']]) ?>
    <h1 class="page-hero__title"><?= e(txt('about.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('about.lead')) ?></p>
  </div>
</section>

<section class="section">
  <div class="container story">
    <div class="story__media">
      <img src="<?= e(img_url(txt('about.image'))) ?>" alt="Bộ sưu tập trang sức ngọc phỉ thúy" width="900" height="700">
    </div>
    <div class="story__content prose">
      <p class="eyebrow"><?= e(txt('about.story_eyebrow')) ?></p>
      <h2 class="section-title"><?= e(txt('about.story_title')) ?></h2>
      <?= txt_html('about.story_text') ?>
    </div>
  </div>
</section>

<section class="section section--muted" aria-labelledby="values-title">
  <div class="container">
    <header class="section-head">
      <p class="eyebrow"><?= e(txt('about.values_eyebrow')) ?></p>
      <h2 id="values-title" class="section-title"><?= e(txt('about.values_title')) ?></h2>
    </header>
    <ul class="value-grid">
      <?php foreach (['gem', 'award', 'leaf', 'shield'] as $i => $ic): ?>
        <li class="value-card"><?= icon($ic) ?><h3><?= e(txt('about.v' . ($i + 1) . '_title')) ?></h3><p><?= e(txt('about.v' . ($i + 1) . '_text')) ?></p></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section" aria-labelledby="process-title">
  <div class="container">
    <header class="section-head">
      <p class="eyebrow"><?= e(txt('about.process_eyebrow')) ?></p>
      <h2 id="process-title" class="section-title"><?= e(txt('about.process_title')) ?></h2>
    </header>
    <ol class="process">
      <?php for ($i = 1; $i <= 4; $i++): ?>
        <li><span class="process__num">0<?= $i ?></span><h3><?= e(txt('about.p' . $i . '_title')) ?></h3><p><?= e(txt('about.p' . $i . '_text')) ?></p></li>
      <?php endfor; ?>
    </ol>
  </div>
</section>

<section class="section section--cream">
  <div class="container cta-band">
    <div>
      <h2 class="section-title"><?= e(txt('about.cta_title')) ?></h2>
      <p><?= e(txt('about.cta_text')) ?></p>
    </div>
    <div class="cta-band__actions">
      <?php if (cfg('zalo')): ?><a class="btn btn--primary" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Chat Zalo</a><?php endif; ?>
      <a class="btn btn--outline" href="<?= e(url('san-pham')) ?>">Xem sản phẩm</a>
    </div>
  </div>
</section>

<?php include ROOT . '/includes/footer.php'; ?>
