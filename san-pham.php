<?php
require __DIR__ . '/includes/init.php';

$per_page = STATIC_DEMO ? 1000 : 12;

// ---- Đọc bộ lọc từ đường dẫn ----
$state = [
    'danh-muc' => preg_replace('/[^a-z0-9-]/', '', (string) ($_GET['danh-muc'] ?? '')),
    'menh'     => preg_replace('/[^a-z]/', '', (string) ($_GET['menh'] ?? '')),
    'gia'      => preg_replace('/[^a-z0-9-]/', '', (string) ($_GET['gia'] ?? '')),
    'q'        => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80),
    'sap-xep'  => preg_replace('/[^a-z-]/', '', (string) ($_GET['sap-xep'] ?? '')),
    'chu-vi'   => '',
    'ngon-tay' => '',
];
// Tìm vòng vừa tay: chu vi bàn tay (cm) → ni vòng (mm)
$cm_raw = mb_substr(trim((string) ($_GET['chu-vi'] ?? '')), 0, 10);
$cm     = (float) str_replace(',', '.', $cm_raw);
if ($cm >= 120 && $cm <= 260) {
    $cm /= 10;                                     // khách nhập bằng mm → đổi ra cm
}
$ni = 0;
if ($cm >= 12 && $cm <= 26) {
    $ni = ni_from_cm($cm);
    $state['chu-vi'] = rtrim(rtrim(number_format($cm, 1, '.', ''), '0'), '.');
}
// Tìm nhẫn vừa tay: chu vi ngón tay (mm) hoặc đường kính lòng nhẫn cũ (mm) → ni nhẫn
$mm_raw  = mb_substr(trim((string) ($_GET['ngon-tay'] ?? '')), 0, 10);
$mm      = (float) str_replace(',', '.', $mm_raw);
$ring    = $mm > 0 ? ring_ni_from_mm($mm) : 0;
$ring_mm = $ring ? str_replace('.', ',', (string) round(ring_circumference($mm), 1)) : '';   // chu vi ngón tay đã quy đổi (mm)
if ($ring) {
    $state['ngon-tay'] = rtrim(rtrim(number_format($mm, 1, '.', ''), '0'), '.');
}
$cm_error   = $cm_raw !== '' && !$ni;
$ring_error = $mm_raw !== '' && !$ring;
$category = $state['danh-muc'] ? find_category($state['danh-muc']) : null;
if ($state['danh-muc'] && !$category) {
    $state['danh-muc'] = '';
}
if (!isset(menh_info()[$state['menh']])) {
    $state['menh'] = '';
}
if (!isset(price_ranges()[$state['gia']])) {
    $state['gia'] = '';
}
if (!isset(sort_options()[$state['sap-xep']])) {
    $state['sap-xep'] = '';
}

// Tra cứu theo năm sinh (khi trình duyệt tắt JavaScript)
$lookup = null;
if (isset($_GET['nam-sinh'])) {
    $lookup = lunar_year_menh((int) $_GET['nam-sinh']);
    if ($lookup) {
        $state['menh'] = $lookup['menh'];
        $lookup['year'] = (int) $_GET['nam-sinh'];
    }
}

/** Tạo đường dẫn giữ nguyên các bộ lọc khác */
function shop_link(array $state, array $override = []): string
{
    $s   = array_merge($state, $override);
    $cat = $s['danh-muc'];
    unset($s['danh-muc']);
    $s = array_filter($s, fn($v) => $v !== '' && $v !== null && $v !== 1);
    return $cat ? category_url($cat, $s) : url('san-pham', $s);
}

$items = filter_products([
    'category' => $state['danh-muc'],
    'menh'     => $state['menh'],
    'price'    => $state['gia'],
    'q'        => $state['q'],
    'sort'     => $state['sap-xep'],
    'ni'       => $ni,
    'ni_nhan'  => $ring,
]);
$total = count($items);
$pages = max(1, (int) ceil($total / $per_page));
$page  = min($pages, max(1, (int) ($_GET['trang'] ?? 1)));
$items = array_slice($items, ($page - 1) * $per_page, $per_page);

// ---- Tiêu đề trang ----
$menh = $state['menh'] ? menh_info()[$state['menh']] : null;
if ($category) {
    $heading = $category['name'];
    $intro   = $category['desc'];
} elseif ($state['q'] !== '') {
    $heading = 'Kết quả tìm kiếm';
    $intro   = 'Từ khóa: “' . $state['q'] . '”';
} elseif ($ni) {
    $heading = 'Vòng ngọc vừa tay bạn';
    $intro   = 'Chu vi bàn tay ' . str_replace('.', ',', $state['chu-vi']) . ' cm → ni phù hợp ' . $ni . ' – ' . ($ni + 2) . ' mm.';
} elseif ($ring) {
    $heading = 'Nhẫn ngọc vừa tay bạn';
    $intro   = 'Chu vi ngón tay khoảng ' . $ring_mm . ' mm → ni nhẫn ' . $ring . '.';
} elseif ($menh) {
    $heading = 'Ngọc hợp mệnh ' . $menh['name'];
    $intro   = 'Màu tương hợp: ' . $menh['good'] . '. Gợi ý: ' . $menh['jade'] . '.';
} else {
    $heading = 'Tất cả sản phẩm';
    $intro   = 'Trang sức ngọc phỉ thúy tự nhiên loại A – vòng ngọc, mặt dây chuyền, nhẫn, bông tai, chuỗi hạt và vật phẩm phong thủy.';
}

$page_title = $heading . ($page > 1 ? ' – Trang ' . $page : '');
$page_desc  = $intro;
$nav_active = $menh && !$category ? 'menh' : 'shop';
$body_class = 'page-shop';
$canonical  = absolute_url(shop_link(['danh-muc' => $state['danh-muc'], 'menh' => $state['menh'], 'gia' => '', 'q' => '', 'sap-xep' => ''], $page > 1 ? ['trang' => $page] : []));
if ($state['q'] !== '' || $lookup || $ni || $ring) {
    $head_extra = '<meta name="robots" content="noindex, follow">';
}

$crumbs = [['label' => 'Sản phẩm', 'url' => url('san-pham')]];
if ($category) {
    $crumbs[] = ['label' => $category['name']];
} elseif ($heading !== 'Tất cả sản phẩm') {
    $crumbs[] = ['label' => $heading];
} else {
    $crumbs = [['label' => 'Sản phẩm']];
}

$has_filters = $state['danh-muc'] || $state['menh'] || $state['gia'] || $state['q'] !== '' || $ni || $ring;
$show_size   = $state['danh-muc'] === 'vong-ngoc' || $ni;
$show_ring   = $state['danh-muc'] === 'nhan-ngoc' || $ring;

include ROOT . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <?= render_breadcrumbs($crumbs) ?>
    <h1 class="page-hero__title"><?= e($heading) ?></h1>
    <p class="page-hero__desc"><?= e($intro) ?></p>
  </div>
</section>

<div class="container shop-layout">
  <aside class="shop-sidebar" id="shop-filters" aria-label="Bộ lọc sản phẩm">
    <div class="shop-sidebar__head">
      <h2 class="filter-title">Bộ lọc</h2>
      <button type="button" class="icon-btn shop-sidebar__close" data-filter-close><?= icon('close') ?><span class="sr-only">Đóng bộ lọc</span></button>
    </div>

    <form class="search-form search-form--block" role="search" action="<?= e(url('san-pham')) ?>" method="get">
      <label class="sr-only" for="shop-q">Tìm trong cửa hàng</label>
      <input id="shop-q" name="q" type="search" value="<?= e($state['q']) ?>" placeholder="Tìm sản phẩm…">
      <button type="submit" class="search-form__btn"><?= icon('search') ?><span class="sr-only">Tìm kiếm</span></button>
    </form>

    <div class="filter-group">
      <h3 class="filter-group__title">Danh mục</h3>
      <ul class="filter-list">
        <li><a href="<?= e(shop_link($state, ['danh-muc' => '', 'trang' => 1])) ?>"<?= !$state['danh-muc'] ? ' aria-current="true"' : '' ?>>Tất cả<span><?= count(all_products()) ?></span></a></li>
        <?php foreach ($CATEGORIES as $slug => $c): ?>
          <li><a href="<?= e(shop_link($state, ['danh-muc' => $slug])) ?>"<?= $state['danh-muc'] === $slug ? ' aria-current="true"' : '' ?>><?= e($c['name']) ?><span><?= count_in_category($slug) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="filter-group">
      <h3 class="filter-group__title">Hợp mệnh</h3>
      <ul class="filter-list filter-list--chips">
        <?php foreach (menh_info() as $k => $m): $on = $state['menh'] === $k; ?>
          <li><a class="chip" style="--el: <?= e($m['color']) ?>" href="<?= e(shop_link($state, ['menh' => $on ? '' : $k])) ?>"<?= $on ? ' aria-current="true"' : '' ?>><span class="dot" style="--dot: <?= e($m['color']) ?>"></span><?= e($m['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="filter-group">
      <h3 class="filter-group__title">Khoảng giá</h3>
      <ul class="filter-list">
        <?php foreach (price_ranges() as $k => $r): $on = $state['gia'] === $k; ?>
          <li><a href="<?= e(shop_link($state, ['gia' => $on ? '' : $k])) ?>"<?= $on ? ' aria-current="true"' : '' ?>><?= e($r['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php if ($has_filters): ?>
      <a class="btn btn--outline btn--block btn--sm" href="<?= e(url('san-pham')) ?>">Xóa tất cả bộ lọc</a>
    <?php endif; ?>
  </aside>
  <div class="shop-backdrop" data-filter-close hidden></div>

  <div class="shop-main">
    <?php if ($lookup): $mi = menh_info()[$lookup['menh']]; ?>
      <div class="notice notice--menh" style="--el: <?= e($mi['color']) ?>">
        <p><strong>Năm <?= e($lookup['year']) ?> – <?= e($lookup['canchi']) ?></strong>: mệnh <strong><?= e($mi['name']) ?></strong> (<?= e($lookup['napam']) ?>).
        Màu tương hợp: <?= e($mi['good']) ?>. Nên tránh: <?= e($mi['avoid']) ?>.</p>
      </div>
    <?php endif; ?>

    <?php if ($show_size): ?>
      <form class="size-finder" action="<?= e(category_url('vong-ngoc')) ?>" method="get">
        <span class="size-finder__icon"><?= icon('ruler') ?></span>
        <div class="size-finder__text">
          <label for="sf-cm"><strong>Tìm vòng vừa tay</strong></label>
          <span>Khép ngón cái vào gốc ngón út, đo vòng quanh phần rộng nhất của bàn tay.</span>
        </div>
        <div class="size-finder__row">
          <input id="sf-cm" name="chu-vi" type="text" inputmode="decimal" autocomplete="off" maxlength="6" placeholder="Chu vi (cm)" value="<?= e($state['chu-vi'] !== '' ? str_replace('.', ',', $state['chu-vi']) : $cm_raw) ?>" required<?= $cm_error ? ' aria-invalid="true" aria-describedby="sf-cm-error"' : '' ?>>
          <button type="submit" class="btn btn--primary btn--sm">Tìm vòng</button>
        </div>
        <?php if ($cm_error): ?><p class="field-error size-finder__error" id="sf-cm-error" role="alert">Chu vi bàn tay thường từ 12 đến 26 cm – ví dụ: 17,5.</p><?php endif; ?>
      </form>
    <?php endif; ?>

    <?php if ($show_ring): ?>
      <form class="size-finder" action="<?= e(category_url('nhan-ngoc')) ?>" method="get">
        <span class="size-finder__icon"><?= icon('ruler') ?></span>
        <div class="size-finder__text">
          <label for="sf-mm"><strong>Tìm nhẫn vừa tay</strong></label>
          <span data-ring-info><?php if ($ring): ?>Chu vi ngón tay khoảng <?= e($ring_mm) ?> mm → <strong class="nowrap">ni nhẫn <?= $ring ?></strong>.<?php else: ?>Quấn dải giấy quanh gốc ngón tay rồi đo độ dài (mm), hoặc đo đường kính lòng chiếc nhẫn đang đeo vừa.<?php endif; ?></span>
        </div>
        <div class="size-finder__row">
          <input id="sf-mm" name="ngon-tay" type="text" inputmode="decimal" autocomplete="off" maxlength="6" placeholder="VD: 54 mm" value="<?= e($state['ngon-tay'] !== '' ? str_replace('.', ',', $state['ngon-tay']) : $mm_raw) ?>" required<?= $ring_error ? ' aria-invalid="true" aria-describedby="sf-mm-error"' : '' ?>>
          <button type="submit" class="btn btn--primary btn--sm">Tìm nhẫn</button>
        </div>
        <?php if ($ring_error): ?><p class="field-error size-finder__error" id="sf-mm-error" role="alert">Chưa tính được ni từ số đo này. Nhập chu vi ngón tay (VD: 54 mm hoặc 5,4 cm) hoặc đường kính lòng nhẫn (VD: 17 mm).</p><?php endif; ?>
      </form>
    <?php endif; ?>

    <div class="shop-toolbar">
      <p class="shop-toolbar__count" role="status"><?= $total ? 'Hiển thị <strong>' . $total . '</strong> sản phẩm' : 'Không có sản phẩm phù hợp' ?></p>
      <button type="button" class="btn btn--outline btn--sm shop-toolbar__filter" aria-controls="shop-filters" aria-expanded="false" data-filter-open><?= icon('filter') ?>Bộ lọc</button>
      <form class="sort-form" method="get" action="<?= e(strtok($_SERVER['REQUEST_URI'] ?? '', '?')) ?>" data-autosubmit>
        <?php foreach (['danh-muc' => pretty() ? '' : $state['danh-muc'], 'menh' => $state['menh'], 'gia' => $state['gia'], 'q' => $state['q'], 'chu-vi' => $state['chu-vi'], 'ngon-tay' => $state['ngon-tay']] as $k => $v): if ($v !== ''): ?>
          <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
        <?php endif; endforeach; ?>
        <label for="sort">Sắp xếp</label>
        <select id="sort" name="sap-xep">
          <?php foreach (sort_options() as $k => $label): ?>
            <option value="<?= e($k) ?>"<?= $state['sap-xep'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn btn--sm btn--primary">Áp dụng</button></noscript>
      </form>
    </div>

    <?php if ($has_filters): ?>
      <ul class="active-filters" aria-label="Bộ lọc đang áp dụng">
        <?php if ($category): ?><li><a href="<?= e(shop_link($state, ['danh-muc' => ''])) ?>"><?= e($category['name']) ?><?= icon('close') ?><span class="sr-only">Bỏ lọc</span></a></li><?php endif; ?>
        <?php if ($menh): ?><li><a href="<?= e(shop_link($state, ['menh' => ''])) ?>">Mệnh <?= e($menh['name']) ?><?= icon('close') ?><span class="sr-only">Bỏ lọc</span></a></li><?php endif; ?>
        <?php if ($state['gia']): ?><li><a href="<?= e(shop_link($state, ['gia' => ''])) ?>"><?= e(price_ranges()[$state['gia']]['label']) ?><?= icon('close') ?><span class="sr-only">Bỏ lọc</span></a></li><?php endif; ?>
        <?php if ($state['q'] !== ''): ?><li><a href="<?= e(shop_link($state, ['q' => ''])) ?>">“<?= e($state['q']) ?>”<?= icon('close') ?><span class="sr-only">Bỏ lọc</span></a></li><?php endif; ?>
        <?php if ($ni): ?><li><a href="<?= e(shop_link($state, ['chu-vi' => ''])) ?>">Ni <?= $ni ?>–<?= $ni + 2 ?> mm<?= icon('close') ?><span class="sr-only">Bỏ lọc</span></a></li><?php endif; ?>
        <?php if ($ring): ?><li><a href="<?= e(shop_link($state, ['ngon-tay' => ''])) ?>">Ni nhẫn <?= $ring ?><?= icon('close') ?><span class="sr-only">Bỏ lọc</span></a></li><?php endif; ?>
      </ul>
    <?php endif; ?>

    <?php if ($items): ?>
      <div class="product-grid product-grid--3">
        <?php foreach ($items as $p) echo render_product_card($p, 'h2'); ?>
      </div>
      <?= render_pagination($page, $pages, fn($n) => shop_link($state, ['trang' => $n])) ?>
    <?php else: ?>
      <div class="empty-state">
        <?= icon('gem') ?>
        <h2>Chưa tìm thấy sản phẩm phù hợp</h2>
        <p>Hãy thử bỏ bớt bộ lọc, tìm với từ khóa khác, hoặc liên hệ để được tư vấn mẫu theo yêu cầu.</p>
        <div class="empty-state__actions">
          <a class="btn btn--primary" href="<?= e(url('san-pham')) ?>">Xem tất cả sản phẩm</a>
          <?php if (cfg('zalo')): ?><a class="btn btn--outline" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener">Tư vấn qua Zalo</a><?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include ROOT . '/includes/footer.php'; ?>
