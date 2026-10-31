<?php
/**
 * Nguồn dữ liệu sản phẩm cho Google Merchant Center: /google-shopping.xml
 * Khai báo link này trong Merchant Center > Sản phẩm > Nguồn cấp dữ liệu (Scheduled fetch).
 */
require __DIR__ . '/includes/init.php';
header('Content-Type: application/xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>', "\n";
echo '<title>', e(cfg('site_name')), '</title><link>', e(site_url() . '/'), '</link><description>', e(cfg('description')), "</description>\n";
foreach (all_products() as $p) {
    if ($p['price'] <= 0 || is_sold($p)) {
        continue; // Google yêu cầu có giá; món đã bán không đưa lên
    }
    $avail = ($p['stock'] ?? '') === 'het-hang' ? 'out_of_stock' : (($p['stock'] ?? '') === 'dat-truoc' ? 'preorder' : 'in_stock');
    $desc  = str_limit(trim($p['short'] . ' ' . preg_replace('/\s+/u', ' ', $p['description'])), 4900);
    echo "<item>\n";
    echo '  <g:id>', e($p['slug']), "</g:id>\n";
    echo '  <g:title>', e(mb_substr($p['name'], 0, 150)), "</g:title>\n";
    echo '  <g:description>', e($desc), "</g:description>\n";
    echo '  <g:link>', e(absolute_url(product_url($p))), "</g:link>\n";
    echo '  <g:image_link>', e(absolute_url(product_image($p))), "</g:image_link>\n";
    foreach (array_slice($p['images'], 1, 5) as $img) {
        echo '  <g:additional_image_link>', e(absolute_url(img_url($img))), "</g:additional_image_link>\n";
    }
    echo '  <g:availability>', $avail, "</g:availability>\n";
    if (!empty($p['old_price']) && $p['old_price'] > $p['price']) {
        echo '  <g:price>', (int) $p['old_price'], " VND</g:price>\n";
        echo '  <g:sale_price>', (int) $p['price'], " VND</g:sale_price>\n";
    } else {
        echo '  <g:price>', (int) $p['price'], " VND</g:price>\n";
    }
    echo '  <g:brand>', e(cfg('site_name')), "</g:brand>\n";
    echo "  <g:condition>new</g:condition>\n";
    echo "  <g:identifier_exists>no</g:identifier_exists>\n";
    echo "  <g:google_product_category>188</g:google_product_category>\n"; // Trang sức
    echo '  <g:product_type>', e('Trang sức ngọc > ' . category_name($p['category'])), "</g:product_type>\n";
    echo '  <g:color>', e($p['color']), "</g:color>\n";
    echo '  <g:material>', e($p['material']), "</g:material>\n";
    echo "</item>\n";
}
echo "</channel></rss>\n";
