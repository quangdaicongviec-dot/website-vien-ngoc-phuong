<?php
/** Phần đầu trang dùng chung. Biến $page_title, $page_desc… được đặt ở từng trang trước khi include. */
$full_title = $page_title !== '' ? $page_title . ' | ' . cfg('site_name') : cfg('site_name') . ' – ' . cfg('tagline');
$og_image   = absolute_url(img_url($page_image));
$canonical  = $canonical ?: absolute_url(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));

$menu = [
    ['key' => 'home',  'label' => 'Trang chủ', 'url' => url()],
    ['key' => 'shop',  'label' => 'Sản phẩm',  'url' => url('san-pham'), 'children' => array_merge(
        [['label' => 'Tất cả sản phẩm', 'url' => url('san-pham')]],
        array_map(fn($slug, $c) => ['label' => $c['name'], 'url' => category_url($slug)], array_keys($CATEGORIES), $CATEGORIES)
    )],
    ['key' => 'menh',  'label' => 'Ngọc theo mệnh', 'url' => url('', []) . '#tra-cuu-menh', 'children' => array_merge(
        array_map(fn($k, $m) => ['label' => 'Mệnh ' . $m['name'], 'url' => menh_url($k), 'dot' => $m['color']], array_keys(menh_info()), menh_info()),
        [['label' => 'Tra cứu mệnh theo năm sinh', 'url' => url() . '#tra-cuu-menh']]
    )],
    ['key' => 'gift',  'label' => 'Quà tặng', 'url' => url('qua-tang'), 'children' => array_merge(
        [['label' => 'Chọn quà giúp tôi', 'url' => url('qua-tang') . '#chon-qua']],
        array_map(fn($slug, $c) => ['label' => $c['name'], 'url' => collection_url($slug)], array_keys(all_collections()), all_collections())
    )],
    ['key' => 'blog',    'label' => 'Cẩm nang',   'url' => url('cam-nang')],
    ['key' => 'about',   'label' => 'Giới thiệu', 'url' => url('gioi-thieu')],
    ['key' => 'policy',  'label' => 'Chính sách', 'url' => url('chinh-sach')],
    ['key' => 'lookup',  'label' => 'Tra cứu đơn', 'mlabel' => 'Tra cứu đơn hàng', 'url' => url('tra-cuu-don-hang')],
    ['key' => 'contact', 'label' => 'Liên hệ',    'url' => url('lien-he')],
];
$search_q = isset($_GET['q']) ? (string) $_GET['q'] : '';
// Tỉ lệ banner (cao/rộng) → đầu trang tự cao lên để đầu trang + banner vừa khít màn hình
$hero_hw  = ($hf = hero_frame()) ? round($hf['h'] / $hf['w'], 6) : 0;
if ($hero_hw) {
    $body_class = trim($body_class . ' has-hero-ratio');
}
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($full_title) ?></title>
<meta name="description" content="<?= e(str_limit($page_desc, 160)) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="#0b5a32">

<meta property="og:locale" content="vi_VN">
<meta property="og:site_name" content="<?= e(cfg('site_name')) ?>">
<meta property="og:type" content="<?= e($page_type) ?>">
<meta property="og:title" content="<?= e($page_title ?: $full_title) ?>">
<meta property="og:description" content="<?= e(str_limit($page_desc, 200)) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($og_image) ?>">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="<?= e(base_path()) ?>/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="<?= e(img_url('favicon-32.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(img_url('apple-touch-icon.png')) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/brand-lotus.css')) ?>"><?php /* Hoạ tiết hoa sen – xóa dòng này để gỡ */ ?>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'JewelryStore',
    'name'     => cfg('site_name'),
    'url'      => site_url() . '/',
    'logo'     => absolute_url(img_url(site_logo())),
    'image'    => absolute_url(img_url((string) cfg('og_image') ?: 'og-image.jpg')),
    'telephone'=> cfg('hotline'),
    'email'    => cfg('email'),
    'address'  => cfg('address'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php /* ---- Đo lường & quảng cáo (chỉ chạy khi đã điền mã trong config.php) ---- */ ?>
<?php if ($ga = cfg('ga4_id')): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',<?= json_encode($ga) ?>);</script>
<?php endif; ?>
<?php if ($fb = cfg('fb_pixel_id')): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',<?= json_encode((string) $fb) ?>);fbq('track','PageView');</script>
<?php endif; ?>
<?php if ($tt = cfg('tiktok_pixel_id')): ?>
<script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=d.createElement("script");o.type="text/javascript",o.async=!0,o.src=r+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};ttq.load(<?= json_encode((string) $tt) ?>);ttq.page();}(window,document,'ttq');</script>
<?php endif; ?>
<?php if ($hero_hw): ?><style>:root { --hero-hw: <?= $hero_hw ?>; }</style><?php endif; ?>
<?= $head_extra ?>
<?php if (STATIC_DEMO): ?>
<meta name="robots" content="noindex, nofollow">
<script type="application/json" id="demo-cfg"><?= json_encode([
    'site'     => cfg('site_name'),
    'hotline'  => cfg('hotline'),
    'tel'      => tel_href(cfg('hotline')),
    'zalo'     => cfg('zalo') ? zalo_href() : '',
    'bank'     => cfg('bank', []),
    'hold'     => ['hours' => (int) cfg('hold_hours', 24), 'pending' => (int) cfg('hold_pending_minutes', 60), 'percent' => (int) cfg('hold_deposit', 10), 'orderPending' => (int) cfg('order_pending_minutes', 60)],
    'ranges'   => array_map(fn($r) => ['label' => $r['label'], 'min' => $r['min'], 'max' => min($r['max'], 9007199254740991)], price_ranges()),
    'menhInfo' => menh_info(),
    'urls'     => ['shop' => url('san-pham'), 'cart' => url('gio-hang'), 'gift' => url('qua-tang'), 'contact' => url('lien-he'),
                   'order' => base_path() . '/dat-hang/', 'hold' => base_path() . '/giu-hang/', 'receipt' => base_path() . '/phieu/', 'lookup' => url('tra-cuu-don-hang'), 'data' => base_path() . '/demo-data.json'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<style>
  .demo-bar { position: relative; z-index: 70; background: #fff4d6; color: #5c4300; font-size: 12.5px; line-height: 1.45; text-align: center; padding: 7px 44px 7px 16px; border-bottom: 1px solid #f0dca0; }
  .demo-bar strong { color: #8a5a00; letter-spacing: .04em; }
  .demo-bar button { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 30px; height: 30px; border: 0; background: none; font-size: 18px; color: #8a5a00; cursor: pointer; }
  .demo-bar__short { display: none; }
  @media (max-width: 600px) { .demo-bar { font-size: 12px; text-align: left; padding: 6px 40px 6px 12px; } .demo-bar__long { display: none; } .demo-bar__short { display: inline; } }
  .demo-sim { margin-top: 16px; padding: 14px 16px; border: 1px dashed #e0c36f; border-radius: 6px; background: #fffbef; font-size: 13.5px; }
  .demo-sim p { margin: 0 0 10px; }
</style>
<?php endif; ?>
</head>
<body class="<?= e($body_class) ?>" data-base="<?= e(base_path()) ?>" data-cart-url="<?= e(url('gio-hang')) ?>">
<a class="skip-link" href="#main">Bỏ qua, đến nội dung chính</a>
<?php if (STATIC_DEMO): ?>
<div class="demo-bar" data-demo-bar role="note"><strong>BẢN CHẠY THỬ</strong> · <span class="demo-bar__long">Đơn hàng, giữ hàng, lịch hẹn gửi trên trang này không được xử lý thật – vui lòng không chuyển khoản. Sản phẩm, giá và thông tin liên hệ chỉ là dữ liệu mẫu.</span><span class="demo-bar__short">Dữ liệu mẫu – đơn hàng không được xử lý thật, vui lòng không chuyển khoản.</span><button type="button" aria-label="Ẩn thông báo" data-demo-bar-close>×</button></div>
<?php endif; ?>

<header class="site-header">
  <div class="container header-bar">
    <a class="brand" href="<?= e(url()) ?>">
      <picture>
        <?php if (!cfg('logo_image')): ?><source srcset="<?= e(img_url('logo.webp')) ?>" type="image/webp"><?php endif; ?>
        <img src="<?= e(img_url(site_logo())) ?>" alt=""<?= e(cfg('site_name') . ' – ' . cfg('tagline')) ?>" width="365" height="320">
      </picture>
    </a>

    <nav class="main-nav" aria-label="Menu chính">
      <ul class="menu">
        <?php foreach ($menu as $n => $item): $has = !empty($item['children']); ?>
          <li class="menu__item<?= $has ? ' has-sub' : '' ?><?= $nav_active === $item['key'] ? ' is-active' : '' ?>">
            <a class="menu__link" href="<?= e($item['url']) ?>"<?= $nav_active === $item['key'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
            <?php if ($has): ?>
              <button type="button" class="sub-toggle" aria-expanded="false" aria-controls="sub-<?= $n ?>">
                <?= icon('down') ?><span class="sr-only">Mở danh sách <?= e($item['label']) ?></span>
              </button>
              <ul class="submenu" id="sub-<?= $n ?>">
                <?php foreach ($item['children'] as $c): ?>
                  <li><a href="<?= e($c['url']) ?>"><?php if (!empty($c['dot'])): ?><span class="dot" style="--dot: <?= e($c['dot']) ?>"></span><?php endif; ?><?= e($c['label']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="header-actions">
      <button type="button" class="icon-btn search-toggle" aria-controls="header-search" aria-expanded="false" data-search-toggle>
        <?= icon('search') ?><span class="sr-only">Tìm kiếm sản phẩm</span>
      </button>
      <a class="icon-btn wish-link" href="<?= e(url('yeu-thich')) ?>">
        <?= icon('heart') ?>
        <span class="cart-count" data-wish-count hidden>0</span>
        <span class="sr-only">Sản phẩm yêu thích</span>
      </a>
      <a class="icon-btn cart-link" href="<?= e(url('gio-hang')) ?>">
        <?= icon('bag') ?>
        <span class="cart-count" data-cart-count hidden>0</span>
        <span class="sr-only">Giỏ hàng</span>
      </a>
      <button type="button" class="icon-btn menu-toggle" aria-controls="mobile-menu" aria-expanded="false" data-drawer-open>
        <?= icon('menu') ?><span class="sr-only">Mở menu</span>
      </button>
    </div>
  </div>

  <!-- Ô tìm kiếm (mở khi bấm biểu tượng kính lúp) -->
  <div class="search-panel" id="header-search" hidden>
    <div class="container search-panel__inner">
      <form class="search-form search-form--wide" role="search" action="<?= e(url('san-pham')) ?>" method="get">
        <label class="sr-only" for="header-q">Tìm kiếm sản phẩm</label>
        <input id="header-q" name="q" type="search" value="<?= e($search_q) ?>" placeholder="Tìm vòng, mặt dây chuyền…" autocomplete="off">
        <button type="submit" class="search-form__btn"><?= icon('search') ?><span class="sr-only">Tìm kiếm</span></button>
      </form>
      <p class="search-panel__hint">Gợi ý:
        <?php foreach ($CATEGORIES as $slug => $c): ?>
          <a href="<?= e(category_url($slug)) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </p>
      <button type="button" class="icon-btn search-panel__close" data-search-close><?= icon('close') ?><span class="sr-only">Đóng tìm kiếm</span></button>
    </div>
  </div>
</header>

<!-- Menu trên điện thoại -->
<div class="drawer" id="mobile-menu" hidden>
  <div class="drawer__overlay" data-drawer-close></div>
  <div class="drawer__panel" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
    <div class="drawer__head">
      <img src="<?= e(img_url('logo-emblem.png')) ?>" alt="" width="40" height="36">
      <p id="drawer-title" class="drawer__title"><?= e(cfg('site_name')) ?></p>
      <button type="button" class="icon-btn" data-drawer-close><?= icon('close') ?><span class="sr-only">Đóng menu</span></button>
    </div>
    <form class="search-form" role="search" action="<?= e(url('san-pham')) ?>" method="get">
      <label class="sr-only" for="drawer-q">Tìm kiếm sản phẩm</label>
      <input id="drawer-q" name="q" type="search" placeholder="Tìm sản phẩm…">
      <button type="submit" class="search-form__btn"><?= icon('search') ?><span class="sr-only">Tìm kiếm</span></button>
    </form>
    <ul class="drawer-menu">
      <?php foreach ($menu as $item): ?>
        <li>
          <?php if (!empty($item['children'])): ?>
            <details<?= $nav_active === $item['key'] ? ' open' : '' ?>>
              <summary><?= e($item['label']) ?><?= icon('down') ?></summary>
              <ul>
                <?php foreach ($item['children'] as $c): ?>
                  <li><a href="<?= e($c['url']) ?>"><?php if (!empty($c['dot'])): ?><span class="dot" style="--dot: <?= e($c['dot']) ?>"></span><?php endif; ?><?= e($c['label']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </details>
          <?php else: ?>
            <a href="<?= e($item['url']) ?>"<?= $nav_active === $item['key'] ? ' aria-current="page"' : '' ?>><?= e($item['mlabel'] ?? $item['label']) ?></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="drawer__contact">
      <?php if (cfg('hotline')): ?><a class="btn btn--primary btn--block" href="<?= e(tel_href(cfg('hotline'))) ?>"><?= icon('phone') ?>Gọi <?= e(cfg('hotline')) ?></a><?php endif; ?>
      <?php if (cfg('zalo')): ?><a class="btn btn--outline btn--block" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Chat Zalo</a><?php endif; ?>
    </div>
  </div>
</div>

<main id="main" tabindex="-1">
