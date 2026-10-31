<?php
require __DIR__ . '/includes/init.php';

$page_title = 'Giỏ hàng';
$page_desc  = 'Giỏ hàng và thông tin đặt hàng tại ' . cfg('site_name') . '.';
$head_extra = '<meta name="robots" content="noindex">';
$body_class = 'page-cart';
$mini_footer = true;   // trang thanh toán: chân trang gọn để nút Đặt hàng gần hơn

// Dữ liệu sản phẩm cho giỏ hàng (giá luôn lấy theo dữ liệu mới nhất)
$catalog = catalog_data();
$has_bank = (bool) (cfg('bank')['account'] ?? '');
$has_cod  = cod_enabled();
$one_pay  = !$has_bank || !$has_cod;      // chỉ có 1 cách thanh toán → chọn sẵn

include ROOT . '/includes/header.php';
?>

<div class="container checkout-head">
  <?= render_breadcrumbs([['label' => 'Giỏ hàng']]) ?>
  <h1 class="checkout-head__title">Giỏ hàng &amp; đặt hàng</h1>
</div>

<div class="container cart-layout" data-cart-page>
  <div class="cart-main">
    <noscript>
      <div class="notice">Vui lòng bật JavaScript để dùng giỏ hàng, hoặc đặt hàng nhanh qua hotline <a href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a>.</div>
    </noscript>
    <div class="cart-loading" data-cart-loading>Đang tải giỏ hàng…</div>
    <ul class="cart-list" data-cart-list aria-label="Sản phẩm trong giỏ"></ul>
    <div class="empty-state" data-cart-empty hidden>
      <?= icon('bag') ?>
      <h2>Giỏ hàng của bạn đang trống</h2>
      <p>Hãy khám phá những món trang sức ngọc phỉ thúy được tuyển chọn dành riêng cho bạn.</p>
      <div class="empty-state__actions">
        <a class="btn btn--primary" href="<?= e(url('san-pham')) ?>">Tiếp tục mua sắm</a>
      </div>
    </div>
    <a class="link-more link-more--back" href="<?= e(url('san-pham')) ?>" data-cart-continue hidden><?= icon('left') ?>Tiếp tục mua sắm</a>

    <!-- Thông tin nhận hàng: chỉ các mục bắt buộc hiện sẵn, mục tùy chọn thu gọn để trang ngắn -->
    <form id="checkout-form" class="checkout-form" action="<?= e(base_path()) ?>/dat-hang.php" method="post" novalidate data-validate data-checkout-form data-cart-checkout hidden aria-labelledby="checkout-title">
      <?= csrf_field() ?>
      <input type="hidden" name="items" value="" data-cart-input>
      <h2 id="checkout-title" class="summary__title">Thông tin nhận hàng</h2>

      <div class="field-row">
        <div class="field">
          <label for="ho-ten">Họ và tên <span class="req" aria-hidden="true">*</span></label>
          <input id="ho-ten" name="ho-ten" type="text" required maxlength="80" autocomplete="name" aria-describedby="ho-ten-error">
          <p class="field-error" id="ho-ten-error" hidden></p>
        </div>
        <div class="field">
          <label for="dien-thoai">Số điện thoại <span class="req" aria-hidden="true">*</span></label>
          <input id="dien-thoai" name="dien-thoai" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" data-type="phone" aria-describedby="dien-thoai-error">
          <p class="field-error" id="dien-thoai-error" hidden></p>
        </div>
      </div>
      <div class="field">
        <label for="dia-chi">Địa chỉ nhận hàng <span class="req" aria-hidden="true">*</span></label>
        <textarea id="dia-chi" name="dia-chi" rows="2" required maxlength="300" autocomplete="street-address" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành" aria-describedby="dia-chi-error"></textarea>
        <p class="field-error" id="dia-chi-error" hidden></p>
      </div>

      <fieldset class="field pay-methods" data-pay-methods>
        <legend>Hình thức thanh toán <span class="req" aria-hidden="true">*</span></legend>
        <p class="field-error" id="pay-error" hidden></p>
        <?php if ($has_cod): ?>
        <label class="radio-card">
          <input type="radio" name="thanh-toan" value="cod"<?= $one_pay ? ' checked' : '' ?> aria-describedby="pay-error">
          <span><strong>Thanh toán khi nhận hàng (COD)</strong><small>Kiểm tra hàng rồi mới thanh toán · món độc bản được giữ riêng trong lúc chờ xác nhận</small></span>
        </label>
        <?php endif; ?>
        <?php if ($has_bank): ?>
        <label class="radio-card">
          <input type="radio" name="thanh-toan" value="chuyen-khoan"<?= $one_pay ? ' checked' : '' ?> aria-describedby="pay-error">
          <span><strong>Chuyển khoản ngân hàng</strong><small>Quét mã QR sau khi đặt · món độc bản được giữ riêng <?= (int) cfg('order_pending_minutes', 60) ?> phút chờ chuyển khoản</small></span>
        </label>
        <?php endif; ?>
      </fieldset>


      <details class="checkout-more" data-checkout-more>
        <summary><?= icon('mail') ?><span class="checkout-more__label">Email &amp; ghi chú cho cửa hàng<small>không bắt buộc</small></span><?= icon('down') ?></summary>
        <div class="checkout-more__body">
          <div class="field">
            <label for="email">Email <span class="optional">(để nhận phiếu xác nhận và link xem lại đơn)</span></label>
            <input id="email" name="email" type="email" maxlength="120" autocomplete="email" aria-describedby="email-error">
            <p class="field-error" id="email-error" hidden></p>
          </div>
          <div class="field">
            <label for="ghi-chu">Ghi chú <span class="optional">(ni tay, màu yêu thích, thời gian nhận…)</span></label>
            <textarea id="ghi-chu" name="ghi-chu" rows="2" maxlength="1000"></textarea>
          </div>
        </div>
      </details>

      <?php if ($gifts = cfg('gift_options', [])): ?>
      <details class="checkout-more gift-options" data-checkout-more>
        <summary><?= icon('gift') ?><span class="checkout-more__label">Dịch vụ quà tặng<small data-gift-summary>thiệp, hộp quà, giao hỏa tốc</small></span><?= icon('down') ?></summary>
        <div class="checkout-more__body">
          <?php foreach ($gifts as $key => $g): ?>
            <label class="check-row">
              <input type="checkbox" name="qua-tang[]" value="<?= e($key) ?>" data-gift-price="<?= (int) $g['price'] ?>"<?= $key === 'thiep' ? ' data-gift-note' : '' ?>>
              <span><?= e($g['label']) ?></span>
              <small><?= $g['price'] > 0 ? '+' . e(format_price($g['price'])) : 'Miễn phí' ?></small>
            </label>
            <?php if ($key === 'thiep'): ?>
              <div class="field gift-note" data-gift-note-field hidden>
                <label for="loi-nhan">Lời nhắn trên thiệp</label>
                <textarea id="loi-nhan" name="loi-nhan" rows="2" maxlength="300" placeholder="Ví dụ: Chúc mẹ luôn bình an và mạnh khỏe!"></textarea>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </details>
      <?php endif; ?>

    </form>
  </div>

  <!-- Tóm tắt + nút Đặt hàng: máy tính luôn hiện bên phải khi cuộn -->
  <aside class="checkout" data-cart-checkout hidden aria-label="Tóm tắt đơn hàng">
    <div class="summary">
      <h2 class="summary__title">Tóm tắt đơn hàng</h2>
      <dl>
        <div><dt>Tạm tính</dt><dd data-sum-subtotal>0₫</dd></div>
        <div><dt>Phí giao hàng</dt><dd data-sum-shipping>Miễn phí</dd></div>
        <div data-sum-gift-row hidden><dt>Dịch vụ quà tặng</dt><dd data-sum-gift>0₫</dd></div>
        <?php if (vat_rate()): ?><div><dt>Thuế VAT (<?= vat_rate() ?>%)</dt><dd data-sum-vat>0₫</dd></div><?php endif; ?>
        <div class="summary__total"><dt>Tổng cộng</dt><dd data-sum-total>0₫</dd></div>
      </dl>
      <p class="summary__pay"><span>Thanh toán</span><strong data-sum-pay="long">Chưa chọn</strong></p>
      <p class="summary__note" data-sum-contact hidden>Đơn có sản phẩm “Liên hệ” – chúng tôi sẽ báo giá khi gọi xác nhận.</p>
      <p class="form-error" data-form-error role="alert" hidden></p>
      <button type="submit" form="checkout-form" class="btn btn--primary btn--lg btn--block" data-submit data-checkout-submit>
        <span class="btn__label">Đặt hàng</span><span class="spinner" aria-hidden="true"></span>
      </button>
      <p class="checkout-form__terms"><?= str_replace('chính sách mua hàng', '<a href="' . e(url('chinh-sach')) . '">chính sách mua hàng</a>', e(txt('cart.terms'))) ?></p>
      <ul class="checkout-trust">
        <?php foreach (txt_lines('cart.trust') as $i => $line): ?><li><?= icon(['gem', 'truck', 'shield', 'gift', 'check'][$i] ?? 'check') ?><?= e($line) ?></li><?php endforeach; ?>
        <li><?= icon('phone') ?><span>Cần hỗ trợ? Gọi <a class="nowrap" href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a><?php if (cfg('zalo')): ?> hoặc <a href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener">nhắn Zalo</a><?php endif; ?></span></li>
      </ul>
    </div>
  </aside>
</div>

<!-- Điện thoại: thanh Tổng tiền + Đặt hàng luôn ở đáy màn hình -->
<div class="checkout-bar" data-checkout-bar hidden>
  <div class="checkout-bar__sum"><small data-sum-pay="short">Chưa chọn thanh toán</small><strong data-sum-total>0₫</strong></div>
  <button type="submit" form="checkout-form" class="btn btn--primary" data-submit><span class="btn__label">Đặt hàng</span><span class="spinner" aria-hidden="true"></span></button>
</div>

<script type="application/json" id="catalog-data"><?= json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<script type="application/json" id="shipping-data"><?= json_encode(['fee' => (int) cfg('shipping_fee', 0), 'freeFrom' => (int) cfg('free_ship_from', 0), 'vat' => vat_rate()]) ?></script>

<?php include ROOT . '/includes/footer.php'; ?>
