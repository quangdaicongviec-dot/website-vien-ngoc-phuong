<?php
/** Sơ đồ trang cho Google: /sitemap.xml */
require __DIR__ . '/includes/init.php';
header('Content-Type: application/xml; charset=UTF-8');

$urls = [];
$add  = function (string $path, string $prio = '0.6', ?string $mod = null) use (&$urls) {
    $urls[] = ['loc' => absolute_url($path), 'prio' => $prio, 'mod' => $mod];
};
$add(url(), '1.0');
foreach (['san-pham', 'qua-tang', 'cam-nang', 'tra-cuu-kiem-dinh', 'tra-cuu-don-hang', 'dat-lich', 'gioi-thieu', 'chinh-sach', 'lien-he'] as $page) {
    $add(url($page), '0.7');
}
foreach (array_keys($CATEGORIES) as $slug) {
    $add(category_url($slug), '0.8');
}
foreach (array_keys(all_collections()) as $slug) {
    $add(collection_url($slug), '0.7');
}
$productsFile = date('Y-m-d', filemtime(content_customized('san-pham') ? content_file('san-pham') : ROOT . '/data/products.php'));
foreach (all_products() as $p) {
    $add(product_url($p), is_sold($p) ? '0.4' : '0.9', $productsFile);
}
foreach (all_articles() as $a) {
    $add(article_url($a), '0.6', $a['date']);
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as $u) {
    echo '  <url><loc>', e($u['loc']), '</loc>', $u['mod'] ? '<lastmod>' . e($u['mod']) . '</lastmod>' : '', '<priority>', $u['prio'], "</priority></url>\n";
}
echo '</urlset>', "\n";
