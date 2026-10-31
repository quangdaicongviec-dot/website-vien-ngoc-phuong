<?php
/** Trả về HTML thẻ sản phẩm theo danh sách mã (dùng cho Yêu thích, Đã xem gần đây) */
require __DIR__ . '/includes/init.php';
header('X-Robots-Tag: noindex');

$slugs = array_slice(array_unique(array_filter(explode(',', preg_replace('/[^a-z0-9,-]/', '', (string) ($_GET['ds'] ?? ''))))), 0, STATIC_DEMO ? 500 : 24);
foreach ($slugs as $slug) {
    if ($p = find_product($slug)) {
        echo render_product_card($p);
    }
}
