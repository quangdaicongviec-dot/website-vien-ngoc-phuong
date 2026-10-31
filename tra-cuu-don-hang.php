<?php
/**
 * Tra cứu đơn hàng / giữ hàng cho khách: nhập mã (NP… / GIU…) + số điện thoại
 * → mở trang đơn hàng, trang giữ hàng hoặc phiếu xác nhận (đơn cũ đã dọn vẫn tra được qua phiếu).
 * Chống dò mã: mỗi địa chỉ IP tối đa 10 lần tra sai mỗi giờ.
 * Danh sách "Đơn hàng trên thiết bị này" do assets/js/main.js dựng từ bộ nhớ trình duyệt.
 */
require __DIR__ . '/includes/init.php';

$page_title = 'Tra cứu đơn hàng';
$page_desc  = 'Xem lại trạng thái đơn hàng, lượt giữ hàng và tải phiếu xác nhận của ' . cfg('site_name') . ' bằng mã đơn và số điện thoại.';
$body_class = 'page-lookup';
$nav_active = 'lookup';

$codeIn  = '';
$phoneIn = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !STATIC_DEMO) {
    $codeIn  = clean_text('ma', 40);
    $phoneIn = clean_text('dien-thoai', 20);
    if (lookup_fail_count() >= 10) {
        $error = 'Bạn đã tra cứu sai quá nhiều lần. Vui lòng thử lại sau 1 giờ, hoặc liên hệ cửa hàng để được hỗ trợ.';
    } elseif (!extract_payment_codes($codeIn)) {
        $error = 'Mã chưa đúng. Mã đơn hàng có dạng NP261020-AB12, mã giữ hàng có dạng GIU261020-AB12.';
    } elseif (!valid_phone($phoneIn)) {
        $error = 'Số điện thoại chưa đúng (ví dụ: 0912 345 678).';
    } elseif ($to = customer_lookup($codeIn, $phoneIn)) {
        redirect($to);
    } else {
        lookup_fail_count(true);
        $error = 'Không tìm thấy đơn hàng khớp với mã và số điện thoại này. Vui lòng kiểm tra lại (số điện thoại phải đúng số đã dùng khi đặt hàng).';
    }
}

include ROOT . '/includes/header.php';
?>

<section class="page-hero page-hero--center">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Tra cứu đơn hàng']]) ?>
    <span class="page-hero__icon"><?= icon('receipt') ?></span>
    <h1 class="page-hero__title"><?= e(txt('lookup.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('lookup.lead')) ?></p>
  </div>
</section>

<section class="section section--tight">
  <div class="container container--narrow">
    <form class="card lookup-form" method="post" action="<?= e(url('tra-cuu-don-hang')) ?>" novalidate data-validate data-order-lookup>
      <?php if ($error): ?><p class="notice notice--error lookup-form__error" role="alert"><?= e($error) ?></p><?php endif; ?>
      <p class="notice notice--error lookup-form__error" role="alert" data-lookup-error hidden></p>
      <div class="lookup-form__grid">
        <div class="field">
          <label for="lk-ma">Mã đơn hàng / mã giữ hàng <span class="req" aria-hidden="true">*</span></label>
          <input id="lk-ma" name="ma" type="text" required maxlength="40" value="<?= e($codeIn) ?>" placeholder="Ví dụ: NP261020-AB12" autocomplete="off" autocapitalize="characters" spellcheck="false" data-label="mã đơn hàng" aria-describedby="lk-ma-error">
          <p class="field-error" id="lk-ma-error" hidden></p>
        </div>
        <div class="field">
          <label for="lk-sdt">Số điện thoại đặt hàng <span class="req" aria-hidden="true">*</span></label>
          <input id="lk-sdt" name="dien-thoai" type="tel" required maxlength="20" value="<?= e($phoneIn) ?>" autocomplete="tel" data-type="phone" aria-describedby="lk-sdt-error">
          <p class="field-error" id="lk-sdt-error" hidden></p>
        </div>
      </div>
      <button type="submit" class="btn btn--primary" data-submit><?= icon('search') ?><span class="btn__label">Tra cứu</span><span class="spinner" aria-hidden="true"></span></button>
      <p class="lookup-form__hint">Mã đơn có trên trang đặt hàng, trong email xác nhận, và chính là <strong>nội dung chuyển khoản</strong> trong lịch sử giao dịch ngân hàng của bạn.</p>
    </form>

    <div class="my-orders" data-my-orders hidden>
      <h2 class="my-orders__title">Đơn hàng trên thiết bị này</h2>
      <ul class="my-orders__list"></ul>
    </div>

    <p class="lookup-help">Không nhớ mã đơn? Gọi <a href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a><?php if (cfg('zalo')): ?> hoặc <a href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener">nhắn Zalo</a><?php endif; ?> – cửa hàng sẽ gửi lại link đơn hàng cho bạn.</p>
  </div>
</section>

<?php include ROOT . '/includes/footer.php'; ?>
