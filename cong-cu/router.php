<?php
// Bộ định tuyến cho máy chủ thử nghiệm `php -S` (mô phỏng .htaccess) – dùng khi chạy thử trên máy và khi xuất bản Netlify.
$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(data|includes)(/|$)|^/config\.php$#', $path)) { http_response_code(403); echo 'Forbidden'; return true; }
if (preg_match('#\.(md|csv)$#', $path)) { http_response_code(403); echo 'Forbidden'; return true; }
$map = [
  '#^/san-pham/([a-z0-9-]+)/?$#' => ['chi-tiet.php', 'sp'],
  '#^/danh-muc/([a-z0-9-]+)/?$#' => ['san-pham.php', 'danh-muc'],
  '#^/cam-nang/([a-z0-9-]+)/?$#' => ['bai-viet.php', 'bv'],
  '#^/qua-tang/([a-z0-9-]+)/?$#' => ['qua-tang.php', 'bst'],
];
// Trang quản trị /quan-tri/ (giống .htaccess: chặn thư mục trang/ và lib.php)
if (preg_match('#^/quan-tri(/|$)#', $path)) {
  if ($path === '/quan-tri') { header('Location: /quan-tri/' . (($q = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)) ? '?' . $q : '')); return true; }
  if (preg_match('#^/quan-tri/(trang/|lib\.php|\.htaccess)#', $path)) { http_response_code(403); echo 'Forbidden'; return true; }
  if ($path === '/quan-tri/' || $path === '/quan-tri/index.php') { $_SERVER['SCRIPT_NAME'] = '/quan-tri/index.php'; require $root . '/quan-tri/index.php'; return true; }
}
$files = ['/sitemap.xml' => 'sitemap.php', '/robots.txt' => 'robots.php', '/google-shopping.xml' => 'google-shopping.php'];
if (isset($files[$path])) { $_SERVER['SCRIPT_NAME'] = '/' . $files[$path]; require $root . '/' . $files[$path]; return true; }
foreach ($map as $re => [$file, $param]) {
  if (preg_match($re, $path, $m)) {
    $_GET[$param] = $m[1];
    $_SERVER['SCRIPT_NAME'] = '/' . $file;
    require $root . '/' . $file; return true;
  }
}
if ($path !== '/' && is_file($root . $path)) return false;
if (preg_match('#^/([a-z0-9-]+)$#', $path, $m) && is_file($root . '/' . $m[1] . '.php')) {
  $_SERVER['SCRIPT_NAME'] = '/' . $m[1] . '.php';
  require $root . '/' . $m[1] . '.php'; return true;
}
if ($path === '/') { $_SERVER['SCRIPT_NAME'] = '/index.php'; require $root . '/index.php'; return true; }
$_SERVER['SCRIPT_NAME'] = '/404.php';
require $root . '/404.php'; return true;
