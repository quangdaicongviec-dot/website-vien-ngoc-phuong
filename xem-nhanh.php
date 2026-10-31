<?php
/** Nội dung cửa sổ "Xem nhanh" sản phẩm */
require __DIR__ . '/includes/init.php';
header('X-Robots-Tag: noindex');

$p = find_product((string) ($_GET['sp'] ?? ''));
if (!$p) {
    http_response_code(404);
    echo '<p class="qv__error">Không tìm thấy sản phẩm.</p>';
    exit;
}
$off  = discount_percent($p);
$sold = is_sold($p);
$buy  = can_buy($p);
?>
<div class="qv" data-view-item="<?= e(json_encode(['id' => $p['slug'], 'name' => $p['name'], 'price' => (int) $p['price']], JSON_UNESCAPED_UNICODE)) ?>">
  <div class="qv__media" data-gallery>
    <div class="gallery__main"><div class="gallery__stage"><img src="<?= e(product_image($p)) ?>" alt="<?= e($p['name']) ?>" width="800" height="800" data-gallery-main></div></div>
    <?php if (count($p['images']) > 1): ?>
      <ul class="gallery__thumbs">
        <?php foreach ($p['images'] as $i => $img): ?>
          <li><button type="button" class="gallery__thumb" data-gallery-thumb="<?= e(img_url($img)) ?>" aria-label="Xem ảnh <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>><img src="<?= e(img_url($img)) ?>" alt="" width="120" height="120"></button></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="qv__info">
    <p class="product-info__cat"><?= e(category_name($p['category'])) ?></p>
    <h2 class="qv__title" id="qv-title"><?= e($p['name']) ?></h2>
    <?php if (is_unique($p) && !$sold): ?><p class="unique-note"><?= icon('gem') ?><span><strong>Độc bản</strong> – chỉ có một chiếc duy nhất.</span></p><?php endif; ?>
    <p class="price price--lg">
      <?php if ($sold): ?><span class="price__sold">Đã có chủ</span>
      <?php else: ?><span class="price__now"><?= e(format_price($p['price'])) ?></span><?php if ($off): ?><s class="price__old"><?= e(format_price($p['old_price'])) ?></s><?php endif; ?><?php endif; ?>
    </p>
    <a class="link-more qv__more" href="<?= e(product_url($p)) ?>">Xem trang chi tiết đầy đủ<?= icon('arrow') ?></a>
    <p class="product-info__short"><?= e($p['short']) ?></p>
    <dl class="spec-list spec-list--compact">
      <div><dt>Màu sắc</dt><dd><?= e($p['color']) ?></dd></div>
      <?php if (!empty($p['size'])): ?><div><dt>Kích thước</dt><dd><?= e($p['size']) ?></dd></div><?php endif; ?>
      <div><dt>Chất liệu</dt><dd><?= e($p['material']) ?></dd></div>
    </dl>
    <div class="qv__actions">
      <?php if ($buy): ?>
        <button type="button" class="btn btn--outline" data-add-to-cart="<?= e($p['slug']) ?>" data-name="<?= e($p['name']) ?>"<?= is_unique($p) ? ' data-unique' : '' ?>><?= icon('bag') ?>Thêm vào giỏ</button>
        <button type="button" class="btn btn--primary" data-add-to-cart="<?= e($p['slug']) ?>" data-name="<?= e($p['name']) ?>"<?= is_unique($p) ? ' data-unique' : '' ?> data-go-cart>Mua ngay</button>
      <?php else: ?>
        <a class="btn btn--primary qv__detail" href="<?= e(product_url($p)) ?>"><?= $sold ? 'Xem mẫu tương tự' : 'Xem chi tiết sản phẩm' ?></a>
      <?php endif; ?>
      <button type="button" class="card-tool card-tool--lg" data-wish="<?= e($p['slug']) ?>" aria-pressed="false" data-name="<?= e($p['name']) ?>"><?= icon('heart') ?><span class="sr-only">Yêu thích</span></button>
    </div>
  </div>
</div>
