<?php
/**
 * Khởi tạo: nạp cấu hình, dữ liệu và hàm dùng chung. Mọi trang đều gọi file này đầu tiên.
 */
define('ROOT', dirname(__DIR__));

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('vnp_session');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

require ROOT . '/includes/content.php';

// Cấu hình: config.php, ghi đè bởi các mục đã sửa trong trang quản trị (data/noi-dung/cai-dat.json)
$CONFIG     = array_replace(require ROOT . '/config.php', content_settings());

// Bản chạy thử tĩnh (Netlify): chỉ bật khi chạy công cụ xuất bản với biến môi trường VNP_STATIC=1.
// Website thật trên hosting KHÔNG bị ảnh hưởng.
define('STATIC_DEMO', getenv('VNP_STATIC') === '1');
if (STATIC_DEMO) {
    $CONFIG['site_url'] = rtrim((string) getenv('VNP_STATIC_URL'), '/');
    $CONFIG['bank']     = array_merge($CONFIG['bank'] ?? [], ['account' => '0000000000', 'holder' => 'TAI KHOAN THU NGHIEM', 'branch' => '']);
    foreach (['telegram_bot_token', 'telegram_chat_id', 'casso_webhook_key', 'ga4_id', 'fb_pixel_id', 'tiktok_pixel_id'] as $k) {
        $CONFIG[$k] = '';
    }
}
// Dữ liệu: bản đã sửa trong trang quản trị (data/noi-dung/*.json) hoặc file mẫu data/*.php
$CATEGORIES = content_load('danh-muc');
$PRODUCTS   = content_load('san-pham');

require ROOT . '/includes/functions.php';
require ROOT . '/includes/bi-mat.php';   // kho thông tin bí mật (mã hóa)
require ROOT . '/includes/smtp.php';     // gửi email qua SMTP
require ROOT . '/includes/r2.php';       // lưu ảnh / video / sao lưu lên Cloudflare R2

header('Content-Type: text/html; charset=UTF-8');

// Giá trị mặc định cho từng trang (mỗi trang có thể ghi đè trước khi include header.php)
$page_title = '';
$page_desc  = cfg('description');
$page_image = (string) cfg('og_image') ?: 'og-image.jpg';
$page_type  = 'website';
$nav_active = '';
$body_class = '';
$canonical  = '';
$head_extra = '';
