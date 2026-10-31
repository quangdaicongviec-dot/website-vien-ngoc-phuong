<?php
/** Đăng ký nhận tin — lưu vào data/don-hang/dang-ky-nhan-tin-YYYY-MM.csv */
require __DIR__ . '/includes/init.php';

$back = url();
$ref  = (string) ($_SERVER['HTTP_REFERER'] ?? '');
if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
    $back = preg_replace('/#.*$/', '', $ref);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($back);
}

$email = clean_text('email', 120);
if ($guard = form_guard('newsletter', 10)) {
    flash($guard, 'error');
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('Email chưa đúng định dạng, vui lòng kiểm tra lại.', 'error');
} else {
    save_csv('dang-ky-nhan-tin', ['Thời gian', 'Email'], [date('d/m/Y H:i'), $email]);
    form_mark_sent('newsletter');
    flash('Cảm ơn bạn đã đăng ký! Chúng tôi sẽ gửi tin bộ sưu tập mới và ưu đãi tới ' . $email . '.');
}
redirect($back);
