<?php
/** /robots.txt */
require __DIR__ . '/includes/init.php';
header('Content-Type: text/plain; charset=UTF-8');
$b = base_path();
echo "User-agent: *\n";
foreach (['/gio-hang', '/dat-hang.php', '/giu-hang.php', '/yeu-thich', '/xem-nhanh.php', '/the-san-pham.php', '/dang-ky.php', '/webhook-casso.php', '/webhook-sepay.php', '/phieu.php', '/quan-tri/'] as $path) {
    echo "Disallow: $b$path\n";
}
echo "\nSitemap: " . site_url() . "/sitemap.xml\n";
