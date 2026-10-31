<?php
/** Đặt lịch xem ngọc tận tay tại cửa hàng — lưu data/don-hang/dat-lich-YYYY-MM.csv + báo email/Telegram */
require __DIR__ . '/includes/init.php';

$slots   = cfg('appointment_slots', []);
$maxDays = max(1, (int) cfg('appointment_days', 30));
$today   = date('Y-m-d');
$lastDay = date('Y-m-d', strtotime("+$maxDays days"));
$errors  = [];
$old     = ['ho-ten' => '', 'dien-thoai' => '', 'ngay' => '', 'gio' => '', 'san-pham' => '', 'ghi-chu' => ''];

$pre = find_product((string) ($_GET['sp'] ?? ''));
if ($pre) {
    $old['san-pham'] = $pre['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = clean_text($k, $k === 'ghi-chu' ? 1000 : 160);
    }
    if ($guard = form_guard('appointment', 20)) {
        $errors['form'] = $guard;
    }
    if (mb_strlen($old['ho-ten']) < 2)       $errors['ho-ten']     = 'Vui lòng nhập họ và tên.';
    if (!valid_phone($old['dien-thoai']))     $errors['dien-thoai'] = 'Số điện thoại chưa đúng (ví dụ: 0912 345 678).';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['ngay']) || $old['ngay'] < $today || $old['ngay'] > $lastDay) {
        $errors['ngay'] = 'Vui lòng chọn ngày trong vòng ' . $maxDays . ' ngày tới.';
    }
    if (!in_array($old['gio'], $slots, true)) $errors['gio'] = 'Vui lòng chọn khung giờ.';

    if (!$errors) {
        $when = $old['gio'] . ' ' . date('d/m/Y', strtotime($old['ngay']));
        save_csv('dat-lich', ['Thời gian gửi', 'Ngày hẹn', 'Giờ', 'Họ tên', 'Điện thoại', 'Sản phẩm quan tâm', 'Ghi chú'],
            [date('d/m/Y H:i'), date('d/m/Y', strtotime($old['ngay'])), $old['gio'], $old['ho-ten'], $old['dien-thoai'], $old['san-pham'], $old['ghi-chu']]);
        $msg = "📅 ĐẶT LỊCH XEM NGỌC\nLúc: $when\nKhách: {$old['ho-ten']} – {$old['dien-thoai']}"
            . ($old['san-pham'] ? "\nQuan tâm: {$old['san-pham']}" : '') . ($old['ghi-chu'] ? "\nGhi chú: {$old['ghi-chu']}" : '');
        notify_telegram($msg);
        send_mail(cfg('order_email'), 'Đặt lịch xem ngọc – ' . $old['ho-ten'] . ' – ' . $when, nl2br(e($msg)));
        form_mark_sent('appointment');
        $_SESSION['last_appointment'] = ['when' => $when, 'name' => $old['ho-ten'], 'product' => $old['san-pham']];
        redirect(url('dat-lich', ['da-dat' => 1]));
    }
}

$done       = !empty($_GET['da-dat']) ? ($_SESSION['last_appointment'] ?? null) : null;
$page_title = 'Đặt lịch xem ngọc';
$page_desc  = 'Đặt lịch đến ' . cfg('site_name') . ' xem tận tay và được tư vấn chọn ngọc phỉ thúy, đo ni tay miễn phí.';
$body_class = 'page-appointment';
if ($done) {
    $head_extra = '<meta name="robots" content="noindex">';
}

function fa(string $name, array $errors): string
{
    return isset($errors[$name]) ? ' aria-invalid="true"' : '';
}

include ROOT . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Đặt lịch xem ngọc']]) ?>
    <h1 class="page-hero__title"><?= e(txt('appt.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('appt.lead')) ?></p>
  </div>
</section>

<section class="section section--tight">
  <div class="container contact-layout">
    <div>
      <?php if ($done): ?>
        <div class="notice notice--success" role="status">
          <p><strong>Đã đặt lịch thành công!</strong> Hẹn gặp <?= e($done['name']) ?> lúc <strong><?= e($done['when']) ?></strong>. Chúng tôi sẽ gọi xác nhận trước buổi hẹn.</p>
        </div>
      <?php endif; ?>
      <ul class="benefit-list">
        <?php foreach (['gem', 'ruler', 'cert', 'clock'] as $i => $ic): ?>
          <li><?= icon($ic) ?><div><strong><?= e(txt('appt.b' . ($i + 1) . '_title')) ?></strong><span><?= e(txt('appt.b' . ($i + 1) . '_text')) ?></span></div></li>
        <?php endforeach; ?>
      </ul>
      <?php if (cfg('address')): ?>
        <div class="summary">
          <h2 class="summary__title">Địa chỉ cửa hàng</h2>
          <p class="addr"><?= icon('pin') ?><?= e(cfg('address')) ?></p>
          <?php if (cfg('working_hours')): ?><p class="addr"><?= icon('clock') ?><?= e(cfg('working_hours')) ?></p><?php endif; ?>
          <a class="link-more" href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(cfg('address'))) ?>" target="_blank" rel="noopener">Chỉ đường trên Google Maps<?= icon('arrow') ?></a>
        </div>
      <?php endif; ?>
    </div>

    <form class="card" method="post" action="<?= e(url('dat-lich')) ?>" novalidate data-validate>
      <h2 class="summary__title">Chọn thời gian hẹn</h2>
      <?= csrf_field() ?>
      <?php if (!empty($errors['form'])): ?><p class="form-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
      <div class="field-row">
        <div class="field">
          <label for="a-ten">Họ và tên <span class="req" aria-hidden="true">*</span></label>
          <input id="a-ten" name="ho-ten" type="text" required maxlength="80" autocomplete="name" value="<?= e($old['ho-ten']) ?>" aria-describedby="a-ten-error"<?= fa('ho-ten', $errors) ?>>
          <p class="field-error" id="a-ten-error"<?= isset($errors['ho-ten']) ? '' : ' hidden' ?>><?= e($errors['ho-ten'] ?? '') ?></p>
        </div>
        <div class="field">
          <label for="a-sdt">Số điện thoại <span class="req" aria-hidden="true">*</span></label>
          <input id="a-sdt" name="dien-thoai" type="tel" required maxlength="20" autocomplete="tel" data-type="phone" value="<?= e($old['dien-thoai']) ?>" aria-describedby="a-sdt-error"<?= fa('dien-thoai', $errors) ?>>
          <p class="field-error" id="a-sdt-error"<?= isset($errors['dien-thoai']) ? '' : ' hidden' ?>><?= e($errors['dien-thoai'] ?? '') ?></p>
        </div>
      </div>
      <div class="field">
        <label for="a-ngay">Ngày hẹn <span class="req" aria-hidden="true">*</span></label>
        <input id="a-ngay" name="ngay" type="date" required min="<?= e($today) ?>" max="<?= e($lastDay) ?>" value="<?= e($old['ngay']) ?>" aria-describedby="a-ngay-error"<?= fa('ngay', $errors) ?>>
        <p class="field-error" id="a-ngay-error"<?= isset($errors['ngay']) ? '' : ' hidden' ?>><?= e($errors['ngay'] ?? '') ?></p>
      </div>
      <fieldset class="field">
        <legend>Khung giờ <span class="req" aria-hidden="true">*</span></legend>
        <div class="slot-grid">
          <?php foreach ($slots as $i => $slot): ?>
            <label class="slot"><input type="radio" name="gio" value="<?= e($slot) ?>"<?= $old['gio'] === $slot ? ' checked' : '' ?><?= $i === 0 ? ' required' : '' ?>><span><?= e($slot) ?></span></label>
          <?php endforeach; ?>
        </div>
        <?php if (isset($errors['gio'])): ?><p class="field-error"><?= e($errors['gio']) ?></p><?php endif; ?>
      </fieldset>
      <div class="field">
        <label for="a-sp">Sản phẩm bạn quan tâm <span class="optional">(không bắt buộc)</span></label>
        <input id="a-sp" name="san-pham" type="text" maxlength="160" value="<?= e($old['san-pham']) ?>" placeholder="Ví dụ: vòng ngọc trắng băng ni 55">
      </div>
      <div class="field">
        <label for="a-note">Ghi chú <span class="optional">(ngân sách, số người đi cùng…)</span></label>
        <textarea id="a-note" name="ghi-chu" rows="3" maxlength="1000"><?= e($old['ghi-chu']) ?></textarea>
      </div>
      <button type="submit" class="btn btn--primary btn--lg btn--block" data-submit><span class="btn__label">Đặt lịch hẹn</span><span class="spinner" aria-hidden="true"></span></button>
    </form>
  </div>
</section>

<?php include ROOT . '/includes/footer.php'; ?>
