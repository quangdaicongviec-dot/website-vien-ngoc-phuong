<?php
/** Quà tặng: công cụ "Chọn quà giúp tôi" + bộ sưu tập theo dịp (data/collections.php) */
require __DIR__ . '/includes/init.php';

$nav_active = 'gift';
$body_class = 'page-gift';

/* ---------- Trang một bộ sưu tập ---------- */
$bst = preg_replace('/[^a-z0-9-]/', '', (string) ($_GET['bst'] ?? ''));
if ($bst !== '') {
    $col = find_collection($bst);
    if (!$col) {
        http_response_code(404);
        include ROOT . '/404.php';
        exit;
    }
    $items      = collection_products($bst);
    $page_title = $col['name'];
    $page_desc  = $col['desc'];
    $page_image = $col['image'];
    $canonical  = absolute_url(collection_url($bst));
    include ROOT . '/includes/header.php';
    ?>
    <section class="page-hero page-hero--collection">
      <div class="container page-hero__split">
        <div>
          <?= render_breadcrumbs([['label' => 'Quà tặng', 'url' => url('qua-tang')], ['label' => $col['name']]]) ?>
          <p class="eyebrow">Quà tặng theo dịp</p>
          <h1 class="page-hero__title"><?= e($col['name']) ?></h1>
          <p class="page-hero__desc"><?= e($col['desc']) ?></p>
          <a class="link-more" href="<?= e(url('qua-tang')) ?>#chon-qua"><?= icon('gift') ?>Chưa chắc chọn gì? Để chúng tôi gợi ý</a>
        </div>
        <img class="page-hero__img" src="<?= e(img_url($col['image'])) ?>" alt="" width="800" height="800">
      </div>
    </section>
    <section class="section section--tight">
      <div class="container">
        <?php if ($items): ?>
          <div class="product-grid">
            <?php foreach ($items as $p) echo render_product_card($p, 'h2'); ?>
          </div>
        <?php else: ?>
          <div class="empty-state"><?= icon('gift') ?><h2>Bộ sưu tập đang được cập nhật</h2><p>Liên hệ để được tư vấn mẫu phù hợp với dịp này.</p></div>
        <?php endif; ?>
        <h2 class="section-title section-title--sm gift-more__title">Các dịp khác</h2>
        <ul class="occasion-grid occasion-grid--sm">
          <?php foreach (all_collections() as $slug => $c): if ($slug === $bst) continue; ?>
            <li><a class="occasion" href="<?= e(collection_url($slug)) ?>"><img src="<?= e(img_url($c['image'])) ?>" alt="" width="800" height="800" loading="lazy"><span class="occasion__text"><strong><?= e($c['name']) ?></strong></span></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
    <?php
    include ROOT . '/includes/footer.php';
    exit;
}

/* ---------- Trang quà tặng + gợi ý ---------- */
$who     = preg_replace('/[^a-z-]/', '', (string) ($_GET['cho'] ?? ''));
$year    = (int) ($_GET['nam-sinh'] ?? 0);
$budget  = preg_replace('/[^a-z0-9-]/', '', (string) ($_GET['ngan-sach'] ?? ''));
$asked   = isset(gift_recipients()[$who]);
$results = $asked ? gift_finder($who, $year >= 1900 && $year <= 2100 ? $year : 0, $budget) : [];
$lunar   = $asked && $year >= 1900 && $year <= 2100 ? lunar_year_menh($year) : null;
$hidden  = $asked && $budget !== '' ? gift_unpriced_count($who, $lunar ? $year : 0) : 0;

$page_title = 'Quà tặng ngọc phỉ thúy';
$page_desc  = 'Gợi ý quà tặng ngọc phỉ thúy theo người nhận, năm sinh và ngân sách: quà tặng mẹ, mừng thọ, trao dâu, đầy tháng cho bé, tân gia – khai trương.';
if ($asked) {
    $head_extra = '<meta name="robots" content="noindex, follow">';
}

include ROOT . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Quà tặng']]) ?>
    <h1 class="page-hero__title"><?= e(txt('gift.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('gift.lead')) ?></p>
  </div>
</section>

<section class="section section--tight" id="chon-qua" aria-labelledby="quiz-title">
  <div class="container">
    <form class="gift-quiz card" action="<?= e(url('qua-tang')) ?>#ket-qua" method="get">
      <header class="gift-quiz__head">
        <span class="gift-quiz__icon"><?= icon('gift') ?></span>
        <div>
          <h2 id="quiz-title" class="section-title section-title--sm">Chọn quà giúp tôi</h2>
          <p>Trả lời 3 câu hỏi nhanh, chúng tôi gợi ý những món phù hợp nhất.</p>
        </div>
      </header>

      <fieldset class="gift-quiz__step">
        <legend><span class="step-num">1</span>Bạn tặng ai?</legend>
        <div class="choice-grid">
          <?php foreach (gift_recipients() as $k => $r): ?>
            <label class="choice"><input type="radio" name="cho" value="<?= e($k) ?>"<?= $who === $k ? ' checked' : '' ?> required><span><?= e($r['label']) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <fieldset class="gift-quiz__step">
        <legend><span class="step-num">2</span>Năm sinh âm lịch của người nhận <span class="optional">(không bắt buộc – để gợi ý màu ngọc hợp mệnh)</span></legend>
        <label class="sr-only" for="g-nam">Năm sinh âm lịch</label>
        <input id="g-nam" class="gift-quiz__year" name="nam-sinh" type="number" inputmode="numeric" min="1900" max="2100" placeholder="Ví dụ: 1968" value="<?= $year ? (int) $year : '' ?>">
      </fieldset>

      <fieldset class="gift-quiz__step">
        <legend><span class="step-num">3</span>Ngân sách của bạn</legend>
        <div class="choice-grid choice-grid--budget">
          <label class="choice"><input type="radio" name="ngan-sach" value=""<?= $budget === '' ? ' checked' : '' ?>><span>Chưa xác định</span></label>
          <?php foreach (price_ranges() as $k => $r): ?>
            <label class="choice"><input type="radio" name="ngan-sach" value="<?= e($k) ?>"<?= $budget === $k ? ' checked' : '' ?>><span><?= e($r['label']) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <button type="submit" class="btn btn--primary btn--lg"><?= icon('sparkle') ?>Gợi ý quà cho tôi</button>
    </form>

    <?php if ($asked): ?>
      <div class="gift-results" id="ket-qua" tabindex="-1">
        <h2 class="section-title section-title--sm">Gợi ý quà tặng <?= e(mb_strtolower(gift_recipients()[$who]['label'])) ?></h2>
        <?php if ($lunar): $mi = menh_info()[$lunar['menh']]; ?>
          <p class="notice notice--menh" style="--el: <?= e($mi['color']) ?>">Người nhận sinh năm <?= (int) $year ?> (<?= e($lunar['canchi']) ?>) – mệnh <strong><?= e($mi['name']) ?></strong>. Màu ngọc hợp: <?= e($mi['jade']) ?>. Những món hợp mệnh được ưu tiên, món có màu kỵ mệnh (<?= e(mb_strtolower($mi['avoid'])) ?>) không được gợi ý.</p>
        <?php endif; ?>
        <?php if ($results): ?>
          <div class="product-grid">
            <?php foreach ($results as $p) echo render_product_card($p); ?>
          </div>
        <?php endif; ?>
        <?php if ($hidden): ?>
          <p class="notice gift-unpriced">Còn <strong><?= $hidden ?> mẫu</strong> phù hợp nhưng giá cần liên hệ (mỗi món ngọc độc bản được báo giá riêng).
            <a href="<?= e(url('qua-tang', ['cho' => $who, 'nam-sinh' => $year ?: ''])) ?>#ket-qua">Xem cả các mẫu này</a></p>
        <?php endif; ?>
        <?php if (!$results && !$hidden): ?>
          <div class="empty-state"><?= icon('gift') ?><h3>Chưa có mẫu phù hợp hoàn toàn</h3><p>Hãy thử chọn ngân sách khác, hoặc nhắn cho chúng tôi để được tư vấn riêng.</p>
            <?php if (cfg('zalo')): ?><div class="empty-state__actions"><a class="btn btn--primary" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener">Nhờ tư vấn qua Zalo</a></div><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--muted" aria-labelledby="occ-title">
  <div class="container">
    <header class="section-head">
      <p class="eyebrow">Theo dịp</p>
      <h2 id="occ-title" class="section-title">Bộ sưu tập quà tặng</h2>
    </header>
    <ul class="occasion-grid">
      <?php foreach (all_collections() as $slug => $c): ?>
        <li>
          <a class="occasion" href="<?= e(collection_url($slug)) ?>" data-reveal>
            <img src="<?= e(img_url($c['image'])) ?>" alt="" width="800" height="800" loading="lazy">
            <span class="occasion__text"><strong><?= e($c['name']) ?></strong><small><?= e($c['short']) ?></small></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php include ROOT . '/includes/footer.php'; ?>
