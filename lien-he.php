<?php
require __DIR__ . '/includes/init.php';

$errors = [];
$old    = ['ho-ten' => '', 'dien-thoai' => '', 'email' => '', 'noi-dung' => ''];
// Đi từ sản phẩm đã có chủ → điền sẵn yêu cầu tìm mẫu tương tự
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && ($ref = find_product((string) ($_GET['sp'] ?? '')))) {
    $old['noi-dung'] = 'Tôi muốn tìm mẫu tương tự "' . $ref['name'] . '" (' . $ref['color'] . (!empty($ref['size']) ? ', ' . $ref['size'] : '') . '). Ngân sách khoảng: ';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = clean_text($k, $k === 'noi-dung' ? 2000 : 120);
    }
    if ($guard = form_guard('contact', 20)) {
        $errors['form'] = $guard;
    }
    if (mb_strlen($old['ho-ten']) < 2)   $errors['ho-ten']     = 'Vui lòng nhập họ và tên.';
    if (!valid_phone($old['dien-thoai'])) $errors['dien-thoai'] = 'Số điện thoại chưa đúng (ví dụ: 0912 345 678).';
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email chưa đúng định dạng.';
    if (mb_strlen($old['noi-dung']) < 5) $errors['noi-dung']   = 'Vui lòng nhập nội dung cần tư vấn.';

    if (!$errors) {
        save_csv('lien-he', ['Thời gian', 'Họ tên', 'Điện thoại', 'Email', 'Nội dung'],
            [date('d/m/Y H:i'), $old['ho-ten'], $old['dien-thoai'], $old['email'], $old['noi-dung']]);
        send_mail(cfg('order_email'), 'Liên hệ mới từ website – ' . $old['ho-ten'],
            '<h2>Liên hệ mới</h2><p><strong>Họ tên:</strong> ' . e($old['ho-ten']) . '<br><strong>Điện thoại:</strong> ' . e($old['dien-thoai'])
            . '<br><strong>Email:</strong> ' . e($old['email']) . '</p><p>' . nl2br(e($old['noi-dung'])) . '</p>', $old['email']);
        notify_telegram("✉️ LIÊN HỆ MỚI\n{$old['ho-ten']} – {$old['dien-thoai']}" . ($old['email'] ? " – {$old['email']}" : '') . "\n{$old['noi-dung']}");
        form_mark_sent('contact');
        flash('Cảm ơn bạn! Chúng tôi đã nhận được tin nhắn và sẽ liên hệ lại sớm.');
        redirect(url('lien-he') . '#lien-he-form');
    }
}

$page_title = 'Liên hệ';
$page_desc  = 'Liên hệ ' . cfg('site_name') . ' để được tư vấn chọn ngọc phỉ thúy, đo ni tay và đặt hàng. Hotline ' . cfg('hotline') . '.';
$nav_active = 'contact';
$body_class = 'page-contact';
$bank       = cfg('bank', []);

function field_attrs(string $name, array $errors): string
{
    return isset($errors[$name]) ? ' aria-invalid="true"' : '';
}

include ROOT . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Liên hệ']]) ?>
    <h1 class="page-hero__title"><?= e(txt('contact.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('contact.lead')) ?></p>
  </div>
</section>

<section class="section section--tight">
  <div class="container contact-layout">
    <div class="contact-info">
      <ul class="info-cards">
        <?php if (cfg('hotline')): ?>
          <li class="info-card"><?= icon('phone') ?><div><h2>Hotline</h2><a href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a></div></li>
        <?php endif; ?>
        <?php if (cfg('zalo')): ?>
          <li class="info-card"><?= icon('chat') ?><div><h2>Zalo</h2><a href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener">Chat qua Zalo</a></div></li>
        <?php endif; ?>
        <?php if (cfg('email')): ?>
          <li class="info-card"><?= icon('mail') ?><div><h2>Email</h2><a href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></div></li>
        <?php endif; ?>
        <?php if (cfg('address')): ?>
          <li class="info-card"><?= icon('pin') ?><div><h2>Địa chỉ</h2><p><?= e(cfg('address')) ?></p></div></li>
        <?php endif; ?>
        <?php if (cfg('working_hours')): ?>
          <li class="info-card"><?= icon('clock') ?><div><h2>Giờ làm việc</h2><p><?= e(cfg('working_hours')) ?></p></div></li>
        <?php endif; ?>
      </ul>

      <?php if (!empty($bank['account'])): ?>
        <div class="summary bank-box bank-box--inline">
          <h2 class="summary__title">Tài khoản chuyển khoản</h2>
          <dl>
            <div><dt>Ngân hàng</dt><dd><?= e($bank['bank']) ?><?= !empty($bank['branch']) ? ' – ' . e($bank['branch']) : '' ?></dd></div>
            <div><dt>Số tài khoản</dt><dd><strong><?= e($bank['account']) ?></strong></dd></div>
            <?php if (!empty($bank['holder'])): ?><div><dt>Chủ tài khoản</dt><dd><?= e($bank['holder']) ?></dd></div><?php endif; ?>
          </dl>
        </div>
      <?php endif; ?>
    </div>

    <form class="contact-form card" id="lien-he-form" method="post" action="<?= e(url('lien-he')) ?>" novalidate data-validate>
      <h2 class="summary__title">Gửi tin nhắn cho chúng tôi</h2>
      <?= csrf_field() ?>
      <?php if (!empty($errors['form'])): ?><p class="form-error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
      <div class="field">
        <label for="c-ho-ten">Họ và tên <span class="req" aria-hidden="true">*</span></label>
        <input id="c-ho-ten" name="ho-ten" type="text" required maxlength="80" autocomplete="name" value="<?= e($old['ho-ten']) ?>" aria-describedby="c-ho-ten-error"<?= field_attrs('ho-ten', $errors) ?>>
        <p class="field-error" id="c-ho-ten-error"<?= isset($errors['ho-ten']) ? '' : ' hidden' ?>><?= e($errors['ho-ten'] ?? '') ?></p>
      </div>
      <div class="field-row">
        <div class="field">
          <label for="c-dien-thoai">Số điện thoại <span class="req" aria-hidden="true">*</span></label>
          <input id="c-dien-thoai" name="dien-thoai" type="tel" required maxlength="20" autocomplete="tel" data-type="phone" value="<?= e($old['dien-thoai']) ?>" aria-describedby="c-dien-thoai-error"<?= field_attrs('dien-thoai', $errors) ?>>
          <p class="field-error" id="c-dien-thoai-error"<?= isset($errors['dien-thoai']) ? '' : ' hidden' ?>><?= e($errors['dien-thoai'] ?? '') ?></p>
        </div>
        <div class="field">
          <label for="c-email">Email <span class="optional">(không bắt buộc)</span></label>
          <input id="c-email" name="email" type="email" maxlength="120" autocomplete="email" value="<?= e($old['email']) ?>" aria-describedby="c-email-error"<?= field_attrs('email', $errors) ?>>
          <p class="field-error" id="c-email-error"<?= isset($errors['email']) ? '' : ' hidden' ?>><?= e($errors['email'] ?? '') ?></p>
        </div>
      </div>
      <div class="field">
        <label for="c-noi-dung">Nội dung <span class="req" aria-hidden="true">*</span></label>
        <textarea id="c-noi-dung" name="noi-dung" rows="5" required maxlength="2000" placeholder="Bạn quan tâm sản phẩm nào, ni tay, ngân sách…" aria-describedby="c-noi-dung-error"<?= field_attrs('noi-dung', $errors) ?>><?= e($old['noi-dung']) ?></textarea>
        <p class="field-error" id="c-noi-dung-error"<?= isset($errors['noi-dung']) ? '' : ' hidden' ?>><?= e($errors['noi-dung'] ?? '') ?></p>
      </div>
      <button type="submit" class="btn btn--primary btn--lg btn--block" data-submit><span class="btn__label">Gửi tin nhắn</span><span class="spinner" aria-hidden="true"></span></button>
    </form>
  </div>
</section>

<?php if (cfg('map_embed')): ?>
<section class="map-embed" aria-label="Bản đồ cửa hàng">
  <iframe src="<?= e(cfg('map_embed')) ?>" title="Bản đồ đường đến <?= e(cfg('site_name')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</section>
<?php endif; ?>

<?php include ROOT . '/includes/footer.php'; ?>
