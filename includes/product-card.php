<?php
/** Thẻ sản phẩm — biến có sẵn: $p (sản phẩm), $headingTag */
$off    = discount_percent($p);
$url    = product_url($p);
$stock  = $p['stock'] ?? 'con-hang';
$sold   = is_sold($p);
$held   = hold_for($p['slug']);
$buy    = can_buy($p);
?>
<article class="product-card<?= $sold ? ' is-sold' : '' ?>" data-reveal<?php if (STATIC_DEMO):
  static $order = null; $order = $order ?? array_flip(array_column(all_products(), 'slug'));
?> data-slug="<?= e($p['slug']) ?>" data-cat="<?= e($p['category']) ?>" data-menh="<?= e(implode(' ', $p['menh'] ?? [])) ?>" data-price="<?= (int) $p['price'] ?>" data-ni="<?= (int) ($p['ni'] ?? 0) ?>" data-ring="<?= ($rr = ring_range($p)) ? $rr[0] . '-' . $rr[1] : '' ?>" data-new="<?= !empty($p['new']) ? 1 : 0 ?>" data-sold="<?= $sold ? 1 : 0 ?>" data-order="<?= (int) $order[$p['slug']] ?>" data-search="<?= e(vn_fold($p['name'] . ' ' . $p['color'] . ' ' . category_name($p['category']) . ' ' . $p['short'])) ?>"<?php endif; ?>>
  <div class="product-card__media">
    <a class="product-card__img" href="<?= e($url) ?>" tabindex="-1" aria-hidden="true">
      <img src="<?= e(product_image($p)) ?>" alt="" width="800" height="800" loading="lazy" decoding="async">
      <?php if (!empty($p['images'][1])): ?>
        <img class="product-card__alt" src="<?= e(product_image($p, 1)) ?>" alt="" width="800" height="800" loading="lazy" decoding="async">
      <?php endif; ?>
    </a>
    <?php if (!empty($p['images'][1])): ?><span class="card-dots" aria-hidden="true"><i class="is-on"></i><i></i></span><?php endif; ?>
    <span class="badges">
      <?php if ($sold): ?><span class="badge badge--sold">Đã có chủ</span>
      <?php elseif ($held): ?><span class="badge badge--held"><?= is_order_taken($held) ? 'Đã có khách đặt' : 'Đang được giữ' ?></span>
      <?php else: ?>
        <?php if ($off): ?><span class="badge badge--sale">-<?= $off ?>%</span><?php endif; ?>
        <?php if (!empty($p['new'])): ?><span class="badge badge--new">Mới</span><?php endif; ?>
        <?php if (is_unique($p)): ?><span class="badge badge--unique">Độc bản</span><?php endif; ?>
        <?php if ($stock === 'het-hang' || $stock === 'dat-truoc'): ?><span class="badge badge--muted"><?= e(stock_label($stock)) ?></span><?php endif; ?>
      <?php endif; ?>
    </span>
    <div class="card-tools">
      <button type="button" class="card-tool" data-wish="<?= e($p['slug']) ?>" aria-pressed="false" data-name="<?= e($p['name']) ?>" title="Yêu thích">
        <?= icon('heart') ?><span class="sr-only">Yêu thích: <?= e($p['name']) ?></span>
      </button>
      <button type="button" class="card-tool" data-quick-view="<?= e($p['slug']) ?>" title="Xem nhanh">
        <?= icon('eye') ?><span class="sr-only">Xem nhanh: <?= e($p['name']) ?></span>
      </button>
    </div>
  </div>
  <div class="product-card__body">
    <p class="product-card__cat"><?= e(category_name($p['category'])) ?></p>
    <<?= $headingTag ?> class="product-card__name"><a href="<?= e($url) ?>"><?= e($p['name']) ?></a></<?= $headingTag ?>>
    <p class="price">
      <?php if ($sold): ?>
        <span class="price__sold">Đã có chủ</span>
      <?php else: ?>
        <span class="price__now"><?= e(format_price($p['price'])) ?></span>
        <?php if ($off): ?><s class="price__old"><span class="sr-only">Giá gốc </span><?= e(format_price($p['old_price'])) ?></s><?php endif; ?>
      <?php endif; ?>
    </p>
  </div>
  <!-- Nút chính luôn là "Xem chi tiết" (khách biết chỗ xem đầy đủ); nút túi nhỏ bên cạnh để thêm nhanh vào giỏ -->
  <div class="product-card__actions">
    <a class="btn btn--outline btn--sm product-card__cta" href="<?= e($url) ?>"><?= $sold ? 'Tìm mẫu tương tự' : 'Xem chi tiết' ?><span class="sr-only">: <?= e($p['name']) ?></span></a>
    <?php if ($buy): ?>
      <button type="button" class="btn btn--primary btn--sm product-card__cart" data-add-to-cart="<?= e($p['slug']) ?>" data-name="<?= e($p['name']) ?>"<?= is_unique($p) ? ' data-unique' : '' ?> title="Thêm vào giỏ">
        <?= icon('bag') ?><span class="product-card__cart-txt">Thêm vào giỏ</span><span class="sr-only">: <?= e($p['name']) ?></span>
      </button>
    <?php endif; ?>
  </div>
</article>
