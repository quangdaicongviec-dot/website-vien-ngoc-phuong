<?php
/**
 * NỘI DUNG DO TRANG QUẢN TRỊ QUẢN LÝ
 * ------------------------------------------------------------------
 * - Lần đầu bấm "Lưu" trong trang quản trị, nội dung được ghi vào data/noi-dung/<tên>.json.
 *   Từ đó website đọc file JSON này thay cho file mẫu data/*.php tương ứng.
 * - Chưa sửa mục nào trong trang quản trị → website vẫn đọc file mẫu data/*.php như trước.
 * - Mỗi lần lưu, bản cũ được cất trong data/noi-dung/lich-su/ (giữ 30 bản gần nhất / mục)
 *   để khôi phục khi lỡ tay. File JSON bị hỏng → tự dùng bản lưu gần nhất.
 * - Cài đặt (data/noi-dung/cai-dat.json) ghi đè từng mục của config.php.
 * - Văn bản trên các trang (data/noi-dung/van-ban.json) ghi đè chữ mặc định trong includes/van-ban.php.
 */

/** Mục nội dung → file mẫu trong data/ */
const CONTENT_SOURCES = [
    'san-pham'   => 'products.php',
    'danh-muc'   => 'categories.php',
    'banner'     => 'banners.php',
    'kiem-dinh'  => 'certificates.php',
    'bo-suu-tap' => 'collections.php',
    'khach-hang' => 'feedback.php',
    'bai-viet'   => 'articles.php',
    'chinh-sach' => 'policies.php',
    'cai-dat'    => '',
    'van-ban'    => '',
    'ngu-hanh'   => '',
];

function content_dir(): string
{
    return ROOT . '/data/noi-dung';
}

function content_file(string $name): string
{
    return content_dir() . '/' . preg_replace('/[^a-z0-9-]/', '', $name) . '.json';
}

/** Đã được sửa trong trang quản trị chưa (có file JSON) */
function content_customized(string $name): bool
{
    return is_file(content_file($name));
}

/** Đọc một mục nội dung (JSON của trang quản trị, hoặc file mẫu data/*.php) */
function content_load(string $name): array
{
    global $CONTENT_CACHE;
    if (isset($CONTENT_CACHE[$name])) {
        return $CONTENT_CACHE[$name];
    }
    $data = null;
    $file = content_file($name);
    if (is_file($file)) {
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {                         // file hỏng → bản lưu gần nhất
            foreach (content_history($name) as $old) {
                $data = json_decode((string) file_get_contents($old['file']), true);
                if (is_array($data)) {
                    break;
                }
            }
        }
    }
    if (!is_array($data)) {
        $src  = CONTENT_SOURCES[$name] ?? '';
        $php  = $src !== '' ? ROOT . '/data/' . $src : '';
        $data = $php !== '' && is_file($php) ? (require $php) : [];
        if ($name === 'san-pham' && is_array($data)) {
            $data = content_mark_sample_prices($data, $php);
        }
    }
    return $CONTENT_CACHE[$name] = is_array($data) ? $data : [];
}

/**
 * Lưu một mục nội dung (ghi an toàn: file tạm rồi đổi tên; cất bản cũ vào lịch sử).
 * Trả về true nếu lưu được.
 */
function content_save(string $name, array $data): bool
{
    global $CONTENT_CACHE;
    $dir = content_dir();
    if (!is_dir($dir . '/lich-su')) {
        @mkdir($dir . '/lich-su', 0755, true);
    }
    content_protect_dir($dir);
    $file = content_file($name);
    $hist = $dir . '/lich-su/' . basename($file, '.json') . '-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4) . '.json';
    if (!is_file($file) && (CONTENT_SOURCES[$name] ?? '') !== '') {
        // Lần lưu đầu tiên: cất bản gốc (file mẫu data/*.php) để có thể quay lại
        @file_put_contents($hist, json_encode(content_load($name), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
    if (is_file($file)) {
        @copy($file, $hist);
        $old = content_history($name);
        foreach (array_slice($old, 30) as $o) {
            @unlink($o['file']);
        }
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    $CONTENT_CACHE[$name] = $data;
    return true;
}

/** Các bản đã lưu trước đây của một mục (mới nhất trước) */
function content_history(string $name): array
{
    $base  = basename(content_file($name), '.json');
    $files = glob(content_dir() . '/lich-su/' . $base . '-*.json') ?: [];
    $out   = [];
    foreach ($files as $f) {
        if (preg_match('/-(\d{8})-(\d{6})-[0-9a-f]{4}\.json$/', $f, $m)) {
            $out[] = ['file' => $f, 'id' => basename($f, '.json'), 'time' => strtotime($m[1] . ' ' . $m[2]) ?: filemtime($f)];
        }
    }
    usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
    return $out;
}

/** Chặn truy cập trực tiếp thư mục dữ liệu (phòng khi hosting bỏ qua .htaccess ở thư mục gốc) */
function content_protect_dir(string $dir): void
{
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    }
    if (!is_file($dir . '/index.php')) {
        @file_put_contents($dir . '/index.php', "<?php http_response_code(403);\n");
    }
}

/**
 * Sản phẩm mẫu: đánh dấu món còn dùng "GIÁ MÔ PHỎNG" (ghi chú trong data/products.php)
 * để trang quản trị nhắc thay bằng giá thật.
 */
function content_mark_sample_prices(array $products, string $phpFile): array
{
    $src = is_file($phpFile) ? (string) file_get_contents($phpFile) : '';
    if ($src === '' || strpos($src, 'GIÁ MÔ PHỎNG') === false) {
        return $products;
    }
    $parts = preg_split("/'slug'\s*=>\s*'/", $src);
    $flag  = [];
    foreach (array_slice($parts, 1) as $part) {
        $slug = strtok($part, "'");
        $body = substr($part, 0, (int) strpos($part . "'slug'", "'slug'"));
        if (preg_match("/'price'[^\n]*GIÁ MÔ PHỎNG/u", $body)) {
            $flag[$slug] = true;
        }
    }
    foreach ($products as $i => $p) {
        if (isset($flag[$p['slug'] ?? ''])) {
            $products[$i]['gia_mo_phong'] = true;
        }
    }
    return $products;
}

/* ------------------------------------------------------------------
 * Cài đặt (ghi đè config.php) & văn bản trên các trang
 * ------------------------------------------------------------------ */

/** Các mục cài đặt đã sửa trong trang quản trị */
function content_settings(): array
{
    return content_load('cai-dat');
}

/** Danh sách văn bản có thể sửa (nhóm, nhãn, chữ mặc định) */
function text_registry(): array
{
    static $reg = null;
    if ($reg === null) {
        $reg = require ROOT . '/includes/van-ban.php';
    }
    return $reg;
}

/**
 * Văn bản hiển thị trên trang: chữ đã sửa trong trang quản trị, hoặc chữ mặc định.
 * {SITE_NAME} được thay bằng tên cửa hàng. Trả về chữ thường (nhớ dùng e() khi in ra).
 */
function txt(string $key): string
{
    static $over = null;
    if ($over === null) {
        $over = content_load('van-ban');
    }
    $val = $over[$key] ?? (text_registry()[$key]['default'] ?? '');
    return strtr((string) $val, ['{SITE_NAME}' => (string) cfg('site_name')]);
}

/** Văn bản dạng danh sách: mỗi dòng 1 mục */
function txt_lines(string $key): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', txt($key)))));
}

/** Văn bản nhiều đoạn → HTML (đoạn cách nhau 1 dòng trống) */
function txt_html(string $key): string
{
    $t = trim(txt($key));
    return $t === '' ? '' : paragraphs($t);
}
