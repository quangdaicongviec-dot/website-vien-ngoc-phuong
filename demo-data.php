<?php
/**
 * Dữ liệu cho bản chạy thử tĩnh (Netlify) – chỉ hoạt động khi xuất bản với VNP_STATIC=1.
 * Website thật trả về 404.
 */
require __DIR__ . '/includes/init.php';
if (!STATIC_DEMO) {
    http_response_code(404);
    exit;
}
header('Content-Type: application/json; charset=UTF-8');

$products = [];
foreach (all_products() as $i => $p) {
    $products[$p['slug']] = [
        'name'     => $p['name'],
        'price'    => (int) $p['price'],
        'url'      => product_url($p),
        'image'    => product_image($p),
        'color'    => $p['color'],
        'size'     => $p['size'] ?? '',
        'category' => $p['category'],
        'catName'  => category_name($p['category']),
        'menh'     => array_values($p['menh'] ?? []),
        'dip'      => array_values($p['dip'] ?? []),
        'featured' => !empty($p['featured']),
        'unique'   => is_unique($p),
        'buy'      => can_buy($p),
        'deposit'  => deposit_amount($p),
        'order'    => $i,
    ];
}

$certs = [];
foreach (all_certificates() as $code => $c) {
    $prod = !empty($c['product']) ? find_product($c['product']) : null;
    $certs[] = [
        'code'    => $code,
        'key'     => normalize_cert_code($code),
        'lab'     => $c['lab'],
        'date'    => format_date($c['date']),
        'result'  => $c['result'],
        'details' => $c['details'] ?? [],
        'image'   => img_url($c['image']),
        'mau'     => !empty($c['mau']),
        'product' => $prod ? ['name' => $prod['name'], 'url' => product_url($prod), 'image' => product_image($prod),
                              'price' => is_sold($prod) ? 'Đã có chủ' : format_price($prod['price'])] : null,
    ];
}

echo json_encode([
    'site'       => cfg('site_name'),
    'hotline'    => cfg('hotline'),
    'zalo'       => cfg('zalo') ? zalo_href() : '',
    'products'   => $products,
    'categories' => array_map(fn($c) => ['name' => $c['name'], 'desc' => $c['desc']], $CATEGORIES),
    'certs'      => $certs,
    'menhInfo'   => menh_info(),
    'menhTables' => menh_tables(),
    'ranges'     => array_map(fn($r) => ['label' => $r['label'], 'min' => $r['min'], 'max' => min($r['max'], 9007199254740991)], price_ranges()),
    'recipients' => gift_recipients(),
    'gifts'      => cfg('gift_options', []),
    'hold'       => ['hours' => (int) cfg('hold_hours', 24), 'pending' => (int) cfg('hold_pending_minutes', 60), 'percent' => (int) cfg('hold_deposit', 10)],
    'bank'       => cfg('bank', []),
    'shipping'   => ['fee' => (int) cfg('shipping_fee', 0), 'freeFrom' => (int) cfg('free_ship_from', 0)],
    'urls'       => ['shop' => url('san-pham'), 'gift' => url('qua-tang'), 'cert' => url('tra-cuu-kiem-dinh'), 'contact' => url('lien-he'),
                     'order' => base_path() . '/dat-hang/', 'hold' => base_path() . '/giu-hang/'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
