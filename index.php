<?php
require __DIR__ . '/includes/init.php';

$nav_active = 'home';
$body_class = 'page-home';

$banners  = all_banners();
$feedback = all_feedback();
$featured = array_slice(array_values(array_filter(all_products(), fn($p) => !empty($p['featured']) && !is_sold($p))), 0, 8);
$newest   = array_slice(array_values(array_filter(all_products(), fn($p) => !empty($p['new']) && !is_sold($p))), 0, 8);
$articles = array_slice(all_articles(), 0, 3);

// Khung banner lấy theo tỉ lệ của banner ảnh đầu tiên → ảnh luôn hiển thị trọn vẹn
$hero_style = '';
if ($hf = hero_frame()) {
    $hero_style = '--hero-ratio: ' . $hf['w'] . ' / ' . $hf['h'] . ';';
    if ($hf['mobile']) {
        $hero_style .= ' --hero-ratio-m: ' . $hf['mobile'][0] . ' / ' . $hf['mobile'][1] . ';';
    }
}
// Điện thoại dùng khung dọc (banner đầu có ảnh dọc) → banner ảnh nào CHƯA có ảnh dọc sẽ tạm ẩn trên điện thoại
// (ảnh ngang đặt trong khung dọc sẽ thừa 2 dải trống lớn); slide chữ tự xếp dọc cho vừa khung.
$tall_m = !empty($hf['mobile']);

function banner_link(string $link): string
{
    if ($link === '' || $link[0] === '#') {
        return url() . $link;
    }
    if (preg_match('#^https?://#', $link)) {
        return $link;
    }
    // Tách phần ?tham-so=... (ví dụ 'san-pham?sap-xep=moi')
    [$path, $qs] = array_pad(explode('?', trim($link, '/'), 2), 2, '');
    parse_str($qs, $query);
    if (preg_match('#^danh-muc/([a-z0-9-]+)$#', $path, $m)) {
        return category_url($m[1], $query);
    }
    if (preg_match('#^san-pham/([a-z0-9-]+)$#', $path, $m) && ($p = find_product($m[1]))) {
        return product_url($p);
    }
    return url($path, $query);
}

include ROOT . '/includes/header.php';
?>

<!-- ================= BANNER ================= -->
<section class="hero<?= $hero_style ? ' hero--ratio' : '' ?><?= $tall_m ? ' hero--tall-m' : '' ?>" aria-roledescription="carousel" aria-label="Bộ sưu tập nổi bật" data-slider<?= $hero_style ? ' style="' . e($hero_style) . '"' : '' ?>>
  <h1 class="sr-only"><?= e(cfg('site_name')) ?> – Trang sức ngọc phỉ thúy tự nhiên</h1>
  <div class="hero__track">
    <?php foreach ($banners as $i => $b):
      $is_image = ($b['type'] ?? '') === 'image';
      $classes  = 'hero__slide' . ($i === 0 ? ' is-active' : '')
                . ($is_image ? ' hero__slide--image' : (!empty($b['background']) ? ' hero__slide--photo' : ''))
                . ($tall_m && $is_image && empty($b['image_mobile']) ? ' hero__slide--no-m' : '');
      $style    = $is_image
          ? (!empty($b['fill']) ? '--fill: ' . $b['fill'] . '; ' : '') . '--focus: ' . ($b['focus'] ?? '50% 50%') . ';'
          : (!empty($b['background']) ? "background-image:url('" . img_url($b['background']) . "')" : '');
    ?>
      <div class="<?= $classes ?>" role="group" aria-roledescription="slide" aria-label="<?= $i + 1 ?> / <?= count($banners) ?>"
           <?= $i ? 'aria-hidden="true"' : '' ?><?= $style ? ' style="' . e($style) . '"' : '' ?>>
        <?php if ($is_image): [$w, $h] = image_size($b['image']); ?>
          <a class="hero__image-link" href="<?= e(banner_link($b['link'] ?? '')) ?>"<?= $i ? ' tabindex="-1"' : '' ?>>
            <picture>
              <?php if (!empty($b['image_mobile'])): ?><source media="(max-width: 767px) and (orientation: portrait)" srcset="<?= e(img_url($b['image_mobile'])) ?>"><?php endif; ?>
              <img src="<?= e(img_url($b['image'])) ?>" alt="<?= e($b['title'] ?? '') ?>" width="<?= $w ?>" height="<?= $h ?>" <?= $i ? 'decoding="async"' : 'fetchpriority="high"' ?>>
            </picture>
          </a>
        <?php else: ?>
        <div class="container hero__inner">
          <div class="hero__content">
            <p class="eyebrow eyebrow--light"><?= e($b['eyebrow']) ?></p>
            <h2 class="hero__title"><?= e(keep_words($b['title'])) ?></h2>
            <p class="hero__text"><?= e(keep_words($b['text'])) ?></p>
            <div class="hero__actions">
              <a class="btn btn--gold" href="<?= e(banner_link($b['link'])) ?>"<?= $i ? ' tabindex="-1"' : '' ?>><?= e($b['button']) ?><?= icon('arrow') ?></a>
            </div>
          </div>
          <?php if (!empty($b['image'])): ?>
            <div class="hero__media">
              <img src="<?= e(img_url($b['image'])) ?>" alt="" width="900" height="700" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (count($banners) > 1): ?>
    <button type="button" class="hero__nav hero__nav--prev" data-slider-prev><?= icon('left') ?><span class="sr-only">Slide trước</span></button>
    <button type="button" class="hero__nav hero__nav--next" data-slider-next><?= icon('right') ?><span class="sr-only">Slide sau</span></button>
    <div class="hero__controls">
      <div class="hero__dots">
        <?php foreach ($banners as $i => $b): ?>
          <button type="button" class="hero__dot<?= $tall_m && ($b['type'] ?? '') === 'image' && empty($b['image_mobile']) ? ' hero__slide--no-m' : '' ?>" data-slider-dot="<?= $i ?>" aria-label="Xem slide <?= $i + 1 ?>: <?= e($b['title']) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button>
        <?php endforeach; ?>
      </div>
      <button type="button" class="hero__pause" data-slider-toggle aria-pressed="false">
        <span class="when-playing"><?= icon('pause') ?><span class="sr-only">Tạm dừng tự chạy</span></span>
        <span class="when-paused"><?= icon('play') ?><span class="sr-only">Tiếp tục tự chạy</span></span>
      </button>
    </div>
  <?php endif; ?>
</section>

<!-- ================= DANH MỤC ================= -->
<section class="section" aria-labelledby="cat-title">
  <div class="container">
    <header class="section-head">
      <p class="eyebrow"><?= e(txt('home.cat_eyebrow')) ?></p>
      <h2 id="cat-title" class="section-title"><?= e(txt('home.cat_title')) ?></h2>
      <p class="section-sub"><?= e(txt('home.cat_sub')) ?></p>
    </header>
    <ul class="cat-grid">
      <?php foreach ($CATEGORIES as $slug => $c): ?>
        <li>
          <a class="cat-tile" href="<?= e(category_url($slug)) ?>">
            <span class="cat-tile__img"><img src="<?= e(img_url($c['image'])) ?>" alt="" width="800" height="800" loading="lazy"></span>
            <span class="cat-tile__name"><?= e($c['name']) ?></span>
            <span class="cat-tile__count"><?= count_in_category($slug) ?> sản phẩm</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- ================= SẢN PHẨM NỔI BẬT ================= -->
<?php if ($featured): ?>
<section class="section section--muted" aria-labelledby="featured-title">
  <div class="container">
    <header class="section-head section-head--row">
      <div>
        <p class="eyebrow"><?= e(txt('home.feat_eyebrow')) ?></p>
        <h2 id="featured-title" class="section-title"><?= e(txt('home.feat_title')) ?></h2>
      </div>
      <a class="link-more" href="<?= e(url('san-pham')) ?>">Xem tất cả<?= icon('arrow') ?></a>
    </header>
    <div class="product-grid">
      <?php foreach ($featured as $p) echo render_product_card($p); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ================= QUÀ TẶNG THEO DỊP ================= -->
<?php if ($collections = all_collections()): ?>
<section class="section" aria-labelledby="gift-title">
  <div class="container">
    <header class="section-head section-head--row">
      <div>
        <p class="eyebrow"><?= e(txt('home.gift_eyebrow')) ?></p>
        <h2 id="gift-title" class="section-title"><?= e(txt('home.gift_title')) ?></h2>
      </div>
      <a class="link-more" href="<?= e(url('qua-tang')) ?>#chon-qua"><?= icon('gift') ?>Chọn quà giúp tôi<?= icon('arrow') ?></a>
    </header>
    <ul class="occasion-grid">
      <?php foreach ($collections as $slug => $c): ?>
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
<?php endif; ?>

<!-- ================= TRA CỨU MỆNH ================= -->
<section class="section menh-section" id="tra-cuu-menh" aria-labelledby="menh-title">
  <div class="container menh-layout">
    <div class="menh-intro">
      <p class="eyebrow eyebrow--light"><?= e(txt('home.menh_eyebrow')) ?></p>
      <h2 id="menh-title" class="section-title section-title--light"><?= e(txt('home.menh_title')) ?></h2>
      <?= txt_html('home.menh_intro') ?>
      <form class="menh-form" action="<?= e(url('san-pham')) ?>" method="get" data-menh-form novalidate>
        <label for="nam-sinh" class="menh-form__label">Năm sinh âm lịch</label>
        <div class="menh-form__row">
          <input id="nam-sinh" name="nam-sinh" type="number" inputmode="numeric" min="1900" max="2100" placeholder="Ví dụ: 1990" required aria-describedby="nam-sinh-error">
          <button type="submit" class="btn btn--gold">Tra cứu</button>
        </div>
        <p class="field-error field-error--light" id="nam-sinh-error" hidden></p>
      </form>
    </div>
    <div class="menh-result" data-menh-result aria-live="polite">
      <p class="menh-result__placeholder">Kết quả tra cứu sẽ hiển thị tại đây.</p>
    </div>
  </div>
  <div class="container">
    <ul class="element-row" aria-label="Xem sản phẩm theo mệnh">
      <?php foreach (menh_info() as $k => $m): ?>
        <li>
          <a class="element-chip" href="<?= e(menh_url($k)) ?>" style="--el: <?= e($m['color']) ?>">
            <span class="element-chip__dot" aria-hidden="true"></span>
            <span><strong>Mệnh <?= e($m['name']) ?></strong><small><?= e($m['jade']) ?></small></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <script type="application/json" id="menh-data"><?= json_encode(['tables' => menh_tables(), 'info' => menh_info(), 'shop' => url('san-pham')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
</section>

<!-- ================= SẢN PHẨM MỚI ================= -->
<?php if ($newest): ?>
<section class="section" aria-labelledby="new-title">
  <div class="container">
    <header class="section-head section-head--row">
      <div>
        <p class="eyebrow"><?= e(txt('home.new_eyebrow')) ?></p>
        <h2 id="new-title" class="section-title"><?= e(txt('home.new_title')) ?></h2>
      </div>
      <a class="link-more" href="<?= e(url('san-pham', ['sap-xep' => 'moi'])) ?>">Xem tất cả<?= icon('arrow') ?></a>
    </header>
    <div class="product-grid">
      <?php foreach ($newest as $p) echo render_product_card($p); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ================= CAM KẾT MINH BẠCH ================= -->
<section class="section section--tight trust" aria-labelledby="trust-title">
  <div class="container trust__inner">
    <div class="trust__intro" data-reveal>
      <p class="eyebrow"><?= e(txt('home.trust_eyebrow')) ?></p>
      <h2 id="trust-title" class="section-title"><?= e(txt('home.trust_title')) ?></h2>
      <form class="search-form search-form--wide" action="<?= e(url('tra-cuu-kiem-dinh')) ?>" method="get" role="search">
        <label class="sr-only" for="home-cert">Mã giấy kiểm định</label>
        <input id="home-cert" name="ma" type="search" placeholder="Mã kiểm định, VD: VNP-2610-0001" required>
        <button type="submit" class="search-form__btn"><?= icon('search') ?><span class="sr-only">Tra cứu</span></button>
      </form>
    </div>
    <ul class="trust__list">
      <li data-reveal><?= icon('cert') ?><strong><?= e(txt('home.trust1_title')) ?></strong><span><?= e(txt('home.trust1_text')) ?></span></li>
      <li data-reveal><?= icon('video') ?><strong><?= e(txt('home.trust2_title')) ?></strong><span><?= e(txt('home.trust2_text')) ?></span></li>
      <li data-reveal><?= icon('gem') ?><strong><?= e(txt('home.trust3_title')) ?></strong><span><?= e(txt('home.trust3_text')) ?></span></li>
      <li data-reveal><?= icon('calendar') ?><strong><a href="<?= e(url('dat-lich')) ?>"><?= e(txt('home.trust4_title')) ?></a></strong><span><?= e(txt('home.trust4_text')) ?></span></li>
    </ul>
  </div>
</section>

<!-- ================= CÂU CHUYỆN THƯƠNG HIỆU ================= -->
<section class="section section--cream" aria-labelledby="story-title">
  <div class="container story">
    <div class="story__media">
      <img src="<?= e(img_url(txt('home.story_image'))) ?>" alt="Vòng ngọc, mặt dây chuyền và chuỗi hạt ngọc phỉ thúy" width="900" height="700" loading="lazy">
    </div>
    <div class="story__content">
      <p class="eyebrow"><?= e(txt('home.story_eyebrow')) ?></p>
      <h2 id="story-title" class="section-title"><?= e(txt('home.story_title')) ?></h2>
      <?= txt_html('home.story_text') ?>
      <?php if ($points = txt_lines('home.story_points')): ?><ul class="check-list">
        <?php foreach ($points as $pt): ?><li><?= icon('check') ?><?= e($pt) ?></li><?php endforeach; ?>
      </ul><?php endif; ?>
      <a class="btn btn--primary" href="<?= e(url('gioi-thieu')) ?>"><?= e(txt('home.story_button')) ?><?= icon('arrow') ?></a>
    </div>
  </div>
</section>

<!-- ================= CẢM NHẬN KHÁCH HÀNG ================= -->
<?php if ($feedback): ?>
<section class="section" aria-labelledby="fb-title">
  <div class="container">
    <header class="section-head">
      <p class="eyebrow"><?= e(txt('home.fb_eyebrow')) ?></p>
      <h2 id="fb-title" class="section-title"><?= e(txt('home.fb_title')) ?></h2>
      <p class="section-sub"><?= e(txt('home.fb_sub')) ?></p>
    </header>
    <div class="feedback-grid">
      <?php foreach ($feedback as $f): ?>
        <figure class="feedback<?= !empty($f['image']) ? ' feedback--photo' : '' ?>" data-reveal>
          <?php if (!empty($f['image'])): ?>
            <button type="button" class="feedback__img" data-lightbox-src="<?= e(img_url($f['image'])) ?>" aria-label="Xem ảnh lớn của <?= e($f['name']) ?>">
              <img src="<?= e(img_url($f['image'])) ?>" alt="<?= e($f['name']) ?> đeo <?= e($f['product'] ?? 'trang sức ngọc') ?>" width="600" height="750" loading="lazy">
            </button>
          <?php endif; ?>
          <blockquote><p><?= e($f['content']) ?></p></blockquote>
          <figcaption><strong><?= e($f['name']) ?></strong><?php if (!empty($f['place'])): ?> · <?= e($f['place']) ?><?php endif; ?><?php if (!empty($f['product'])): ?><span><?= e($f['product']) ?></span><?php endif; ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ================= CẨM NANG ================= -->
<?php if ($articles): ?>
<section class="section section--muted" aria-labelledby="blog-title">
  <div class="container">
    <header class="section-head section-head--row">
      <div>
        <p class="eyebrow"><?= e(txt('home.blog_eyebrow')) ?></p>
        <h2 id="blog-title" class="section-title"><?= e(txt('home.blog_title')) ?></h2>
      </div>
      <a class="link-more" href="<?= e(url('cam-nang')) ?>">Xem tất cả<?= icon('arrow') ?></a>
    </header>
    <div class="post-grid">
      <?php foreach ($articles as $a) include ROOT . '/includes/article-card.php'; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include ROOT . '/includes/footer.php'; ?>
