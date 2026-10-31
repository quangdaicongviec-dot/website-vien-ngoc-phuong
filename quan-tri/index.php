<?php
/**
 * TRANG QUẢN TRỊ – địa chỉ: https://tenmiencuaban.vn/quan-tri/
 * Mỗi trang con nằm trong quan-tri/trang/<tên>.php (mở qua ?p=<tên>).
 */
define('ADMIN', true);
// Website tính đường dẫn gốc theo file đang chạy → báo như trang gốc để link ảnh, CSS, trang… luôn đúng
$_SERVER['SCRIPT_NAME'] = preg_replace('#/quan-tri/[^/]*$#', '/index.php', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
require dirname(__DIR__) . '/includes/init.php';
require __DIR__ . '/lib.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, private');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

if (STATIC_DEMO) {                          // bản chạy thử tĩnh không có trang quản trị
    http_response_code(404);
    exit;
}

$page = preg_replace('/[^a-z-]/', '', (string) ($_GET['p'] ?? ''));

// Lần đầu: chưa có tài khoản nào → tạo tài khoản quản lý
if (!adm_users()) {
    require __DIR__ . '/trang/cai-dat-dau.php';
    exit;
}

if ($page === 'dang-nhap') {
    require __DIR__ . '/trang/dang-nhap.php';
    exit;
}

$me = adm_user();
if (!$me) {
    $back = $page !== '' && $page !== 'dang-xuat' ? $_SERVER['REQUEST_URI'] ?? '' : '';
    if ($page === 'tai-anh') {
        adm_json(['error' => 'Phiên đăng nhập đã hết hạn – vui lòng tải lại trang và đăng nhập.'], 401);
    }
    redirect(adm_url('dang-nhap', $back ? ['ve' => $back] : []));
}

if ($page === 'dang-xuat') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && adm_csrf_ok()) {
        adm_log('Đăng xuất');
        adm_logout();
        flash('Bạn đã đăng xuất.');
    }
    redirect(adm_url('dang-nhap'));
}

$page = $page ?: 'tong-quan';
$file = __DIR__ . '/trang/' . $page . '.php';
if (!is_file($file)) {
    http_response_code(404);
    adm_head('Không tìm thấy trang', '');
    echo adm_notice('Trang này không tồn tại. <a href="' . e(adm_url()) . '">Về trang tổng quan</a>.', 'warn');
    adm_foot();
    exit;
}
if (!adm_can($page, $me)) {
    http_response_code(403);
    adm_head('Không có quyền', '');
    echo adm_notice('Tài khoản của bạn không có quyền mở mục này. Hãy nhờ người quản lý cấp quyền.', 'warn');
    adm_foot();
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !adm_csrf_ok()) {
    if ($page === 'tai-anh') {
        adm_json(['error' => 'Phiên làm việc đã hết hạn – vui lòng tải lại trang.'], 400);
    }
    flash('Phiên làm việc đã hết hạn (trang mở quá lâu). Vui lòng thử lại – dữ liệu vừa nhập chưa được lưu.', 'error');
    redirect($_SERVER['REQUEST_URI'] ?? adm_url());
}

require $file;
