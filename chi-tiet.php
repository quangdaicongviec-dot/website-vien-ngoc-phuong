<?php
require __DIR__ . '/includes/init.php';

$product = find_product((string) ($_GET['sp'] ?? ''));
if (!$product) {
    http_response_code(404);
    include ROOT . '/404.php';
    exit;
}

$p        = $product;
$off      = discount_percent($p);
$stock    = $p['stock'] ?? 'con-hang';
$cat      = find_category($p['category']);
$related  = related_products($p, 4);
$sold     = is_sold($p);
$held     = hold_for($p['slug']);
$buy      = can_buy($p);
$unique   = is_unique($p);
$video    = video_source($p['video'] ?? '');
$cert     = !empty($p['cert']) ? find_certificate($p['cert']) : null;
// Tab chọn cỡ theo loại: vòng (chu vi bàn tay → ni vòng), nhẫn (ngón tay → ni nhẫn), chuỗi hạt (cổ tay → độ dài chuỗi)
$size_kind = ['vong-ngoc' => 'vong', 'nhan-ngoc' => 'nhan', 'chuoi-hat' => 'chuoi'][$p['category']] ?? '';
$has_size  = $size_kind !== '';
$size_tab  = ['vong' => 'Chọn ni vòng', 'nhan' => 'Chọn ni nhẫn', 'chuoi' => 'Chọn cỡ chuỗi'][$size_kind] ?? '';
$ni        = (int) ($p['ni'] ?? 0);
$ring      = ring_range($p);

$page_title = $p['name'];
$page_desc  = $p['short'] ?: str_limit($p['description']);
$page_image = $p['images'][0] ?? ((string) cfg('og_image') ?: 'og-image.jpg');
$page_type  = 'product';
$nav_active = 'shop';
$body_class = 'page-product' . ($buy ? ' has-mobile-buy' : '');
$canonical  = absolute_url(product_url($p));

$availability = $sold ? 'SoldOut' : ($stock === 'het-hang' ? 'OutOfStock' : ($stock === 'dat-truoc' ? 'PreOrder' : ($held ? 'LimitedAvailability' : 'InStock')));
$ld = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $p['name'],
    'image'       => array_map(fn($i) => absolute_url(img_url($i)), $p['images']),
    'description' => str_limit($p['short'] . ' ' . $p['description'], 300),
    'sku'         => $p['slug'],
    'brand'       => ['@type' => 'Brand', 'name' => cfg('site_name')],
    'category'    => $cat['name'] ?? '',
    'color'       => $p['color'],
    'material'    => $p['material'],
];
if ($p['price'] > 0) {
    $ld['offers'] = [
        '@type'         => 'Offer',
        'url'           => $canonical,
        'priceCurrency' => 'VND',
        'price'         => $p['price'],
        'availability'  => 'https://schema.org/' . $availability,
        'itemCondition' => 'https://schema.org/NewCondition',
    ];
}
$head_extra = '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';
if ($p['price'] > 0) {
    $head_extra .= "\n" . '<meta property="product:price:amount" content="' . (int) $p['price'] . '"><meta property="product:price:currency" content="VND">';
}

$similar_msg = 'Tôi muốn đặt mẫu tương tự: ' . $p['name'];
include ROOT . '/includes/header.php';
?>

<div class="container">
  <?= render_breadcrumbs([
      ['label' => 'Sản phẩm', 'url' => url('san-pham')],
      ['label' => $cat['name'] ?? '', 'url' => category_url($p['category'])],
      ['label' => $p['name']],
  ]) ?>
</div>

<section class="container product-detail" data-view-item="<?= e(json_encode(['id' => $p['slug'], 'name' => $p['name'], 'price' => (int) $p['price']], JSON_UNESCAPED_UNICODE)) ?>">
  <!-- ===== Ảnh & video ===== -->
  <div class="gallery" data-gallery>
    <div class="gallery__main">
      <div class="gallery__stage" data-zoom>
        <img src="<?= e(product_image($p)) ?>" alt="<?= e($p['name']) ?>" width="800" height="800" data-gallery-main fetchpriority="high">
        <button type="button" class="gallery__expand" data-lightbox-open><?= icon('zoom') ?><span class="sr-only">Phóng to ảnh</span></button>
      </div>
      <div class="gallery__video" data-gallery-video hidden></div>
      <span class="badges">
        <?php if ($sold): ?><span class="badge badge--sold">Đã có chủ</span><?php endif; ?>
        <?php if (!$sold && $off): ?><span class="badge badge--sale">-<?= $off ?>%</span><?php endif; ?>
        <?php if (!$sold && !empty($p['new'])): ?><span class="badge badge--new">Mới</span><?php endif; ?>
        <?php if (!$sold && $unique): ?><span class="badge badge--unique">Độc bản</span><?php endif; ?>
      </span>
    </div>
    <?php if (count($p['images']) > 1 || $video): ?>
      <ul class="gallery__thumbs" aria-label="Ảnh và video sản phẩm">
        <?php foreach ($p['images'] as $i => $img): ?>
          <li>
            <button type="button" class="gallery__thumb" data-gallery-thumb="<?= e(img_url($img)) ?>" aria-label="Xem ảnh <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
              <img src="<?= e(img_url($img)) ?>" alt="" width="160" height="160" loading="lazy">
            </button>
          </li>
        <?php endforeach; ?>
        <?php if ($video): ?>
          <li>
            <button type="button" class="gallery__thumb gallery__thumb--video" data-gallery-video-thumb data-type="<?= e($video['type']) ?>" data-src="<?= e($video['src']) ?>" aria-label="Xem video thật của sản phẩm">
              <img src="<?= e(product_image($p, count($p['images']) > 1 ? 1 : 0)) ?>" alt="" width="160" height="160" loading="lazy">
              <span class="gallery__play"><?= icon('play') ?></span>
              <span class="gallery__thumb-label">Video</span>
            </button>
          </li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>
    <p class="gallery__hint"><?= icon('zoom') ?><span class="hint-mouse"><?= e(keep_words('Rê chuột lên ảnh để xem chi tiết vân ngọc, bấm vào ảnh để soi kỹ' . ($video ? ' · bấm “Video” để xem ngọc dưới ánh sáng thật' : ''))) ?></span><span class="hint-touch"><?= e(keep_words('Chạm vào ảnh để soi vân ngọc · vuốt ngang để xem ảnh khác' . ($video ? ' · bấm “Video” để xem ngọc dưới ánh sáng thật' : ''))) ?></span></p>
  </div>

  <!-- ===== Thông tin ===== -->
  <div class="product-info">
    <a class="product-info__cat" href="<?= e(category_url($p['category'])) ?>"><?= e($cat['name'] ?? '') ?></a>
    <h1 class="product-info__title"><?= e($p['name']) ?></h1>
    <?php if ($unique && !$sold): ?>
      <p class="unique-note"><?= icon('gem') ?><span><strong>Độc bản</strong> – mỗi viên ngọc tự nhiên chỉ có một chiếc duy nhất với màu sắc và vân riêng.</span></p>
    <?php endif; ?>

    <div class="price price--lg">
      <?php if ($sold): ?>
        <span class="price__sold">Đã có chủ</span>
      <?php else: ?>
        <span class="price__now"><?= e(format_price($p['price'])) ?></span>
        <?php if ($off): ?>
          <s class="price__old"><span class="sr-only">Giá gốc </span><?= e(format_price($p['old_price'])) ?></s>
          <span class="badge badge--sale">Tiết kiệm <?= e(format_price($p['old_price'] - $p['price'])) ?></span>
        <?php endif; ?>
        <?php if (vat_rate() && $p['price'] > 0): ?><small class="price__vat">Chưa gồm VAT <?= vat_rate() ?>%</small><?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($p['short']): ?><p class="product-info__short"><?= e($p['short']) ?></p><?php endif; ?>

    <dl class="spec-list">
      <div><dt>Chất liệu</dt><dd><?= e($p['material']) ?></dd></div>
      <div><dt>Màu sắc</dt><dd><?= e($p['color']) ?></dd></div>
      <?php if (!empty($p['size'])): ?><div><dt>Kích thước</dt><dd><?= e($p['size']) ?></dd></div><?php endif; ?>
      <?php if (!empty($p['menh'])): ?>
        <div><dt>Hợp mệnh</dt><dd class="menh-tags">
          <?php foreach ($p['menh'] as $m): $mi = menh_info()[$m] ?? null; if (!$mi) continue; ?>
            <a class="chip chip--sm" href="<?= e(menh_url($m)) ?>"><span class="dot" style="--dot: <?= e($mi['color']) ?>"></span><?= e($mi['name']) ?></a>
          <?php endforeach; ?>
        </dd></div>
      <?php endif; ?>
      <?php if ($cert): ?>
        <div><dt>Giấy kiểm định</dt><dd><a class="cert-link" href="<?= e(certificate_url($cert['code'])) ?>"><?= icon('cert') ?><?= e($cert['code']) ?> – Tra cứu</a></dd></div>
      <?php endif; ?>
      <div><dt>Tình trạng</dt><dd><span class="stock stock--<?= e($held && !$sold ? 'dat-truoc' : $stock) ?>"><?= e($held && !$sold ? (is_order_taken($held) ? 'Đã có khách đặt' : 'Đang được giữ') : stock_label($stock)) ?></span></dd></div>
    </dl>

    <?php if ($sold): ?>
      <div class="notice notice--sold">
        <p><strong>Món này đã có chủ.</strong> Vì mỗi viên ngọc là độc bản, chúng tôi sẽ tìm giúp bạn mẫu tương tự về màu sắc, kích thước và ngân sách.</p>
        <div class="notice__actions">
          <?php if (cfg('zalo')): ?><a class="btn btn--primary" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Đặt mẫu tương tự qua Zalo</a><?php endif; ?>
          <a class="btn btn--outline" href="<?= e(url('lien-he', ['sp' => $p['slug']])) ?>#lien-he-form">Gửi yêu cầu tìm mẫu</a>
        </div>
      </div>
    <?php elseif ($held): ?>
      <div class="notice notice--held">
        <?php if (is_order_taken($held)): ?>
          <p><?= icon('lock') ?><strong>Món này đã có khách đặt mua.</strong> Vì mỗi viên ngọc là độc bản, hãy liên hệ để chúng tôi tìm giúp bạn mẫu tương tự, hoặc xem các mẫu bên dưới.</p>
        <?php elseif (($held['type'] ?? 'hold') === 'order'): ?>
          <p><?= icon('lock') ?><strong>Một khách hàng vừa đặt mua món này</strong> và <?= ($held['pay'] ?? 'chuyen-khoan') === 'cod' ? 'đang chờ cửa hàng xác nhận đơn' : 'đang chờ chuyển khoản (đến ' . e(date('H:i', (int) $held['until'])) . ')' ?>. Nếu đơn không hoàn tất, món ngọc sẽ được mở bán lại – hãy liên hệ để được báo ngay.</p>
        <?php elseif (($held['status'] ?? '') === 'pending'): ?>
          <p><?= icon('lock') ?><strong>Một khách hàng đang đặt cọc giữ món này</strong> (chờ xác nhận đến <?= e(date('H:i', (int) $held['until'])) ?>). Nếu không hoàn tất đặt cọc, món ngọc sẽ được mở bán lại – hãy liên hệ để được báo ngay.</p>
        <?php else: ?>
          <p><?= icon('lock') ?><strong>Đã có khách đặt cọc giữ món này</strong> đến <?= e(date('H:i', (int) $held['until']) . ' ngày ' . date('d/m', (int) $held['until'])) ?>. Hãy liên hệ để được báo ngay khi món ngọc mở bán lại, hoặc xem các mẫu tương tự bên dưới.</p>
        <?php endif; ?>
        <div class="notice__actions">
          <?php if (cfg('zalo')): ?><a class="btn btn--primary" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Báo tôi khi mở bán</a><?php endif; ?>
          <a class="btn btn--outline" href="#related-title">Xem mẫu tương tự</a>
        </div>
      </div>
    <?php elseif ($stock === 'het-hang'): ?>
      <p class="notice">Sản phẩm tạm hết hàng. Vui lòng liên hệ để được báo khi có hàng hoặc tư vấn mẫu tương tự.</p>
    <?php else: ?>
      <form class="buy-box" data-buy-form="<?= e($p['slug']) ?>" data-name="<?= e($p['name']) ?>"<?= $unique ? ' data-unique' : '' ?>>
        <?php if ($unique): ?>
          <input type="hidden" name="qty" value="1">
        <?php else: ?>
          <div class="qty">
            <label class="sr-only" for="qty">Số lượng</label>
            <button type="button" class="qty__btn" data-qty-step="-1"><?= icon('minus') ?><span class="sr-only">Giảm số lượng</span></button>
            <input id="qty" type="number" name="qty" value="1" min="1" max="20" inputmode="numeric">
            <button type="button" class="qty__btn" data-qty-step="1"><?= icon('plus') ?><span class="sr-only">Tăng số lượng</span></button>
          </div>
        <?php endif; ?>
        <button type="submit" class="btn btn--outline btn--lg" data-action="add"><?= icon('bag') ?>Thêm vào giỏ</button>
        <button type="submit" class="btn btn--primary btn--lg" data-action="buy">Mua ngay</button>
      </form>
      <?php if (can_hold($p)): ?>
        <div class="hold-box">
          <button type="button" class="btn btn--ghost" data-dialog-open="hold-dialog"><?= icon('lock') ?>Giữ hàng <?= (int) cfg('hold_hours', 24) ?> giờ</button>
          <p>Đặt cọc <strong><?= e(format_price(deposit_amount($p))) ?></strong> (<?= (int) cfg('hold_deposit', 10) ?>%) để giữ riêng món này <?= (int) cfg('hold_hours', 24) ?> giờ trong lúc bạn cân nhắc.</p>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="action-row">
      <button type="button" class="link-action" data-wish="<?= e($p['slug']) ?>" aria-pressed="false" data-name="<?= e($p['name']) ?>"><?= icon('heart') ?><span data-wish-label>Thêm vào yêu thích</span></button>
      <a class="link-action" href="<?= e(url('dat-lich', ['sp' => $p['slug']])) ?>"><?= icon('calendar') ?>Đặt lịch xem tận tay</a>
    </div>

    <div class="contact-row">
      <?php if (cfg('hotline')): ?>
        <a class="contact-pill" href="<?= e(tel_href(cfg('hotline'))) ?>"><?= icon('phone') ?><span><small>Gọi tư vấn</small><strong><?= e(cfg('hotline')) ?></strong></span></a>
      <?php endif; ?>
      <?php if (cfg('zalo')): ?>
        <a class="contact-pill contact-pill--zalo" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?><span><small>Xem video thật</small><strong>Chat Zalo</strong></span></a>
      <?php endif; ?>
    </div>

    <ul class="perks">
      <?php foreach (['gem', 'truck', 'return', 'gift'] as $i => $ic): if (($t = txt('product.trust' . ($i + 1))) === '') continue; ?>
        <li><?= icon($ic) ?><?= e($t) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- ===== Tab thông tin ===== -->
<section class="container product-tabs" data-tabs>
  <div class="tabs__list" role="tablist" aria-label="Thông tin sản phẩm">
    <button type="button" role="tab" id="tab-1" aria-controls="panel-1" aria-selected="true">Mô tả</button>
    <?php if ($has_size): ?><button type="button" role="tab" id="tab-2" aria-controls="panel-2" aria-selected="false" tabindex="-1"><?= e($size_tab) ?></button><?php endif; ?>
    <?php if ($cert): ?><button type="button" role="tab" id="tab-4" aria-controls="panel-4" aria-selected="false" tabindex="-1">Giấy kiểm định</button><?php endif; ?>
    <button type="button" role="tab" id="tab-3" aria-controls="panel-3" aria-selected="false" tabindex="-1">Giao hàng &amp; đổi trả</button>
  </div>
  <div class="tabs__panel prose" role="tabpanel" id="panel-1" aria-labelledby="tab-1" tabindex="0">
    <?= paragraphs($p['description']) ?>
    <?php if (txt('product.note') !== ''): ?><p><em><?= e(txt('product.note')) ?></em></p><?php endif; ?>
  </div>
  <?php if ($has_size): ?>
  <div class="tabs__panel" role="tabpanel" id="panel-2" aria-labelledby="tab-2" tabindex="0" hidden>
    <div class="size-tool">
      <?php if ($size_kind === 'vong'): ?>
      <div class="prose">
        <ol>
          <li>Khép ngón cái chạm vào gốc ngón út, giữ bàn tay như khi đeo vòng.</li>
          <li>Dùng thước dây (hoặc sợi chỉ) đo vòng quanh phần rộng nhất của bàn tay.</li>
          <li>Nhập số đo vào ô bên cạnh để biết ni vòng phù hợp.</li>
        </ol>
        <?php if ($ni): ?>
          <p>Chiếc vòng này có <strong>ni <?= $ni ?> mm</strong>, vừa với bàn tay có chu vi khoảng <strong><?= e(number_format(($ni - 2) * M_PI / 10, 1, ',', '')) ?> – <?= e(number_format($ni * M_PI / 10, 1, ',', '')) ?> cm</strong>.</p>
        <?php endif; ?>
      </div>
      <form class="size-calc card" action="<?= e(category_url('vong-ngoc')) ?>" method="get" data-size-calc<?= $ni ? ' data-ni="' . $ni . '"' : '' ?>>
        <label for="chu-vi"><?= icon('ruler') ?>Chu vi bàn tay của bạn (cm)</label>
        <div class="size-calc__row">
          <input id="chu-vi" name="chu-vi" type="text" inputmode="decimal" autocomplete="off" maxlength="5" placeholder="Ví dụ: 17,5" required>
          <button type="submit" class="btn btn--primary">Tính ni</button>
        </div>
        <div class="size-calc__result" data-size-result aria-live="polite"></div>
      </form>
      <?php elseif ($size_kind === 'nhan'): ?>
      <div class="prose">
        <ol>
          <li>Cắt một dải giấy nhỏ (hoặc dùng sợi chỉ) quấn quanh gốc ngón tay muốn đeo nhẫn – vừa khít, không thắt chặt.</li>
          <li>Đánh dấu chỗ hai đầu gặp nhau, trải thẳng và đo độ dài bằng thước: đó là <strong>chu vi ngón tay</strong> (mm).</li>
          <li>Hoặc đo <strong>đường kính lòng trong</strong> của chiếc nhẫn bạn đang đeo vừa (mm).</li>
          <li>Nhập số đo vào ô bên cạnh để biết ni nhẫn.</li>
        </ol>
        <p>Mẹo: nên đo vào cuối ngày khi ngón tay hơi nở; khớp ngón to thì chọn ni vừa đủ đeo qua khớp. Phân vân giữa 2 ni, hãy chọn ni lớn hơn.</p>
        <?php if ($ring): ?>
          <p>Mẫu nhẫn này có <strong><?= $ring[0] === $ring[1] ? 'ni ' . $ring[0] : 'ni ' . $ring[0] . ' – ' . $ring[1] ?></strong>. Khi đặt hàng, ghi ni của bạn vào ô <em>Ghi chú</em> để cửa hàng làm đúng ni.</p>
        <?php endif; ?>
      </div>
      <form class="size-calc card" action="<?= e(category_url('nhan-ngoc')) ?>" method="get" data-ring-calc<?= $ring ? ' data-range="' . $ring[0] . '-' . $ring[1] . '"' : '' ?>>
        <label for="ngon-tay"><?= icon('ruler') ?>Chu vi ngón tay hoặc đường kính lòng nhẫn</label>
        <div class="size-calc__row">
          <input id="ngon-tay" name="ngon-tay" type="text" inputmode="decimal" autocomplete="off" maxlength="5" placeholder="VD: 54 mm hoặc 5,4 cm" required>
          <button type="submit" class="btn btn--primary">Tính ni</button>
        </div>
        <div class="size-calc__result" data-size-result aria-live="polite"></div>
      </form>
      <?php else: ?>
      <div class="prose">
        <ol>
          <li>Dùng thước dây (hoặc sợi chỉ) đo vòng quanh cổ tay, ngay trên xương cổ tay.</li>
          <li>Đeo vừa: chọn chuỗi dài hơn cổ tay khoảng 1 cm. Thích đeo thoải mái: dài hơn khoảng 2 cm.</li>
          <li>Hạt càng to, chuỗi cần dài hơn một chút để đeo không bị chật.</li>
        </ol>
        <p>Khi đặt hàng, ghi số đo cổ tay vào ô <em>Ghi chú</em> để cửa hàng tư vấn cỡ chuỗi phù hợp.</p>
      </div>
      <form class="size-calc card" data-wrist-calc>
        <label for="co-tay"><?= icon('ruler') ?>Chu vi cổ tay của bạn (cm)</label>
        <div class="size-calc__row">
          <input id="co-tay" name="co-tay" type="text" inputmode="decimal" autocomplete="off" maxlength="5" placeholder="Ví dụ: 15" required>
          <button type="submit" class="btn btn--primary">Tính cỡ</button>
        </div>
        <div class="size-calc__result" data-size-result aria-live="polite"></div>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($cert): ?>
  <div class="tabs__panel" role="tabpanel" id="panel-4" aria-labelledby="tab-4" tabindex="0" hidden>
    <div class="cert-mini">
      <a href="<?= e(certificate_url($cert['code'])) ?>" class="cert-mini__img"><img src="<?= e(img_url($cert['image'])) ?>" alt="Giấy kiểm định <?= e($cert['code']) ?>" width="500" height="350" loading="lazy"></a>
      <div class="prose">
        <p><strong>Mã giấy kiểm định: <?= e($cert['code']) ?></strong><br><?= e($cert['result']) ?></p>
        <p>Bạn có thể tự tra cứu bất cứ lúc nào bằng mã in trên giấy kiểm định đi kèm sản phẩm.</p>
        <p><a class="btn btn--outline btn--sm" href="<?= e(certificate_url($cert['code'])) ?>"><?= icon('cert') ?>Xem chi tiết giấy kiểm định</a></p>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <div class="tabs__panel prose" role="tabpanel" id="panel-3" aria-labelledby="tab-3" tabindex="0" hidden>
    <ul>
      <?php foreach (txt_lines('product.shipping') as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
    </ul>
    <p><a href="<?= e(url('chinh-sach')) ?>">Xem đầy đủ chính sách</a></p>
  </div>
</section>

<?php if ($related): ?>
<section class="section section--tight" aria-labelledby="related-title">
  <div class="container">
    <header class="section-head section-head--row">
      <h2 id="related-title" class="section-title"><?= $sold || $held ? 'Mẫu tương tự đang có sẵn' : 'Có thể bạn cũng thích' ?></h2>
      <a class="link-more" href="<?= e(category_url($p['category'])) ?>">Xem thêm<?= icon('arrow') ?></a>
    </header>
    <div class="product-grid">
      <?php foreach ($related as $rp) echo render_product_card($rp); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--tight section--muted" data-recent="<?= e($p['slug']) ?>" aria-labelledby="recent-title" hidden>
  <div class="container">
    <h2 id="recent-title" class="section-title section-title--sm">Sản phẩm bạn đã xem</h2>
    <div class="product-grid" data-recent-list></div>
  </div>
</section>

<!-- Giữ hàng -->
<?php if (can_hold($p)): ?>
<dialog class="modal" id="hold-dialog" aria-labelledby="hold-title">
  <form class="modal__body" method="post" action="<?= e(base_path()) ?>/giu-hang.php" novalidate data-validate>
    <button type="button" class="icon-btn modal__close" data-dialog-close><?= icon('close') ?><span class="sr-only">Đóng</span></button>
    <p class="eyebrow">Giữ hàng <?= (int) cfg('hold_hours', 24) ?> giờ</p>
    <h2 id="hold-title" class="modal__title" tabindex="-1" autofocus><?= e($p['name']) ?></h2>
    <ol class="modal__steps">
      <li>Gửi thông tin bên dưới – món ngọc được <strong>giữ tạm <?= (int) cfg('hold_pending_minutes', 60) ?> phút</strong>.</li>
      <li>Quét mã QR chuyển khoản đặt cọc <strong><?= e(format_price(deposit_amount($p))) ?></strong>.</li>
      <li>Ngay khi nhận được cọc, chúng tôi xác nhận và <strong>giữ riêng cho bạn <?= (int) cfg('hold_hours', 24) ?> giờ</strong>.</li>
    </ol>
    <?= csrf_field() ?>
    <input type="hidden" name="sp" value="<?= e($p['slug']) ?>">
    <div class="field">
      <label for="h-ten">Họ và tên <span class="req" aria-hidden="true">*</span></label>
      <input id="h-ten" name="ho-ten" type="text" required maxlength="80" autocomplete="name" aria-describedby="h-ten-error">
      <p class="field-error" id="h-ten-error" hidden></p>
    </div>
    <div class="field">
      <label for="h-sdt">Số điện thoại <span class="req" aria-hidden="true">*</span></label>
      <input id="h-sdt" name="dien-thoai" type="tel" required maxlength="20" autocomplete="tel" data-type="phone" aria-describedby="h-sdt-error">
      <p class="field-error" id="h-sdt-error" hidden></p>
    </div>
    <div class="field">
      <label for="h-email">Email <span class="optional">(không bắt buộc)</span></label>
      <input id="h-email" name="email" type="email" maxlength="120" autocomplete="email" aria-describedby="h-email-error h-email-hint">
      <p class="field-hint" id="h-email-hint">Để nhận phiếu xác nhận đặt cọc qua email.</p>
      <p class="field-error" id="h-email-error" hidden></p>
    </div>
    <div class="field hold-agree">
      <label class="check-row">
        <input type="checkbox" name="dong-y" value="1" required data-msg="Vui lòng đánh dấu đồng ý Chính sách đặt cọc – giữ hàng." aria-describedby="h-agree-error">
        <span>Tôi đã đọc và đồng ý <a href="<?= e(url('chinh-sach')) ?>#dat-coc" target="_blank" rel="noopener">Chính sách đặt cọc – giữ hàng</a> (tiền cọc được trừ vào giá khi mua).</span>
      </label>
      <p class="field-error" id="h-agree-error" hidden></p>
    </div>
    <div class="modal__actions">
      <button type="submit" class="btn btn--primary" data-submit><span class="btn__label">Giữ hàng cho tôi</span><span class="spinner" aria-hidden="true"></span></button>
      <button type="button" class="btn btn--outline" data-dialog-close>Để sau</button>
    </div>
  </form>
</dialog>
<?php endif; ?>


<?php if ($buy): ?>
<div class="mobile-buy" data-mobile-buy>
  <div class="mobile-buy__price"><small><?= e(str_limit($p['name'], 32)) ?></small><strong><?= e(format_price($p['price'])) ?></strong></div>
  <button type="button" class="btn btn--primary" data-add-to-cart="<?= e($p['slug']) ?>" data-name="<?= e($p['name']) ?>"<?= $unique ? ' data-unique' : '' ?> data-go-cart>Mua ngay</button>
</div>
<?php endif; ?>

<?php include ROOT . '/includes/footer.php'; ?>
