<?php
/**
 * Hàm dùng chung cho toàn website. Thông thường bạn KHÔNG cần sửa file này.
 */

/* ------------------------------------------------------------------
 * Cơ bản
 * ------------------------------------------------------------------ */

/** Escape HTML */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Đọc cấu hình trong config.php */
function cfg(string $key, $default = '')
{
    global $CONFIG;
    return $CONFIG[$key] ?? $default;
}

/** Thư mục cài website (rỗng nếu đặt ở thư mục gốc tên miền) */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/.');
    }
    return $base;
}

/** Địa chỉ tuyệt đối của website (dùng cho thẻ chia sẻ Facebook/Zalo) */
function site_url(): string
{
    $configured = rtrim((string) cfg('site_url'), '/');
    if ($configured !== '') {
        return $configured;
    }
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $host   = preg_replace('/[^a-z0-9.\-:]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host . base_path();
}

function absolute_url(string $path): string
{
    if (preg_match('#^https?://#', $path)) {
        return $path;
    }
    $root = preg_replace('#' . preg_quote(base_path(), '#') . '$#', '', site_url());
    return rtrim($root, '/') . $path;
}

function pretty(): bool
{
    return (bool) cfg('pretty_urls', true);
}

/* ------------------------------------------------------------------
 * Đường dẫn
 * ------------------------------------------------------------------ */

function url(string $page = '', array $query = []): string
{
    if ($page === '' || $page === 'index') {
        $path = base_path() . '/';
    } else {
        $path = base_path() . '/' . $page . (pretty() ? '' : '.php');
    }
    $query = array_filter($query, fn($v) => $v !== '' && $v !== null);
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function product_url(array $p): string
{
    return pretty()
        ? base_path() . '/san-pham/' . rawurlencode($p['slug'])
        : base_path() . '/chi-tiet.php?sp=' . rawurlencode($p['slug']);
}

function category_url(string $slug, array $query = []): string
{
    if (!pretty()) {
        return url('san-pham', ['danh-muc' => $slug] + $query);
    }
    $query = array_filter($query, fn($v) => $v !== '' && $v !== null);
    return base_path() . '/danh-muc/' . rawurlencode($slug) . ($query ? '?' . http_build_query($query) : '');
}

function article_url(array $a): string
{
    return pretty()
        ? base_path() . '/cam-nang/' . rawurlencode($a['slug'])
        : base_path() . '/bai-viet.php?bv=' . rawurlencode($a['slug']);
}

function menh_url(string $menh): string
{
    return url('san-pham', ['menh' => $menh]);
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . $path;
    $v    = is_file($file) ? filemtime($file) : '1';
    return base_path() . '/assets/' . $path . '?v=' . $v;
}

function img_url(string $path): string
{
    if (preg_match('#^(https?:)?//#', $path)) {
        return $path;
    }
    $path = ltrim($path, '/');
    // Ảnh tải lên trang quản trị: lấy từ Cloudflare R2 nếu đã bật "Khách xem ảnh từ R2" và ảnh đã có trên R2
    if (strncmp($path, 'uploads/', 8) === 0 && ($r2 = r2_url('assets/img/' . $path)) !== '') {
        return $r2;
    }
    return base_path() . '/assets/img/' . $path;
}

/** Khung banner trang chủ: kích thước banner ảnh đầu tiên trong data/banners.php (null nếu không có) */
function hero_frame(): ?array
{
    static $frame = false;
    if ($frame !== false) {
        return $frame;
    }
    $frame = null;
    foreach (all_banners() as $b) {
        if (($b['type'] ?? '') === 'image') {
            [$w, $h] = image_size($b['image']);
            $frame = ['w' => $w, 'h' => $h, 'mobile' => !empty($b['image_mobile']) ? image_size($b['image_mobile']) : null];
            break;
        }
    }
    return $frame;
}

/** Kích thước [rộng, cao] của ảnh trong assets/img/ (để trình duyệt giữ chỗ, tránh giật trang) */
function image_size(string $path, array $default = [1920, 960]): array
{
    $file = ROOT . '/assets/img/' . ltrim($path, '/');
    $info = is_file($file) ? @getimagesize($file) : false;
    return $info ? [(int) $info[0], (int) $info[1]] : $default;
}

/**
 * Chữ hiển thị lớn (banner…): chống ngắt dòng xấu bằng khoảng trắng không ngắt
 * – "phỉ thúy", "vân ngọc" luôn liền nhau; dấu "–" không đứng đầu dòng; 2 chữ cuối không bị tách (tránh 1 chữ lẻ ở dòng cuối).
 */
function keep_words(string $s): string
{
    $nb = "\u{00A0}";
    $s  = preg_replace('/\s+([–—])\s+/u', $nb . '$1 ', trim($s));
    $s  = preg_replace('/(phỉ|vân)\s+(thúy|ngọc)/iu', '$1' . $nb . '$2', $s);
    return preg_replace('/\s+(\S+)$/u', $nb . '$1', $s);
}

function tel_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
}

function zalo_href(): string
{
    return 'https://zalo.me/' . preg_replace('/[^0-9]/', '', (string) cfg('zalo'));
}

function redirect(string $to, int $code = 303): void
{
    header('Location: ' . $to, true, $code);
    exit;
}

/* ------------------------------------------------------------------
 * Định dạng
 * ------------------------------------------------------------------ */

function format_price($amount): string
{
    $amount = (int) $amount;
    if ($amount <= 0) {
        return 'Liên hệ';
    }
    return number_format($amount, 0, ',', '.') . '₫';
}

function discount_percent(array $p): int
{
    if (empty($p['old_price']) || $p['old_price'] <= $p['price'] || $p['price'] <= 0) {
        return 0;
    }
    return (int) round(100 - $p['price'] / $p['old_price'] * 100);
}

function format_date(string $ymd): string
{
    $t = strtotime($ymd);
    return $t ? date('d/m/Y', $t) : $ymd;
}

/** Đoạn văn thuần → HTML (tách đoạn bằng dòng trống) */
function paragraphs(string $text): string
{
    $blocks = preg_split('/\R\s*\R/u', trim($text));
    $html   = '';
    foreach ($blocks as $b) {
        $html .= '<p>' . nl2br(e(trim($b)), false) . '</p>' . "\n";
    }
    return $html;
}

function str_limit(string $text, int $limit = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
}

/** Bỏ dấu tiếng Việt để tìm kiếm */
function vn_fold(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $map = [
        'a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ', 'i' => 'ìíịỉĩ',
        'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ', 'y' => 'ỳýỵỷỹ', 'd' => 'đ',
    ];
    foreach ($map as $plain => $chars) {
        $s = str_replace(mb_str_split($chars), $plain, $s);
    }
    return $s;
}

/* ------------------------------------------------------------------
 * Sản phẩm
 * ------------------------------------------------------------------ */

/** Sản phẩm đang hiển thị trên website (bỏ món đã "Ẩn khỏi website" trong trang quản trị) */
function all_products(): array
{
    global $PRODUCTS;
    return array_values(array_filter($PRODUCTS, fn($p) => empty($p['hidden'])));
}

function find_product(string $slug): ?array
{
    foreach (all_products() as $p) {
        if ($p['slug'] === $slug) {
            return $p;
        }
    }
    return null;
}

function find_category(string $slug): ?array
{
    global $CATEGORIES;
    return isset($CATEGORIES[$slug]) ? ['slug' => $slug] + $CATEGORIES[$slug] : null;
}

function category_name(string $slug): string
{
    global $CATEGORIES;
    return $CATEGORIES[$slug]['name'] ?? '';
}

function count_in_category(string $slug): int
{
    return count(array_filter(all_products(), fn($p) => $p['category'] === $slug));
}

function product_image(array $p, int $i = 0): string
{
    return img_url($p['images'][$i] ?? ($p['images'][0] ?? 'og-image.jpg'));
}

/** Khoảng giá dùng cho bộ lọc */
function price_ranges(): array
{
    $custom = content_load('ngu-hanh')['gia'] ?? null;          // sửa trong trang quản trị → Ngũ hành & quà tặng
    if (is_array($custom) && $custom) {
        return array_map(fn($r) => ['label' => (string) $r['label'], 'min' => (int) $r['min'], 'max' => (int) $r['max'] ?: PHP_INT_MAX], $custom);
    }
    return [
        'duoi-3-trieu'  => ['label' => 'Dưới 3 triệu',     'min' => 1,        'max' => 2999999],
        '3-10-trieu'    => ['label' => '3 – 10 triệu',     'min' => 3000000,  'max' => 9999999],
        '10-30-trieu'   => ['label' => '10 – 30 triệu',    'min' => 10000000, 'max' => 29999999],
        'tren-30-trieu' => ['label' => 'Trên 30 triệu',    'min' => 30000000, 'max' => PHP_INT_MAX],
    ];
}

function sort_options(): array
{
    return [
        ''         => 'Mặc định',
        'moi'      => 'Mới nhất',
        'gia-tang' => 'Giá: thấp đến cao',
        'gia-giam' => 'Giá: cao đến thấp',
        'ten'      => 'Tên: A – Z',
    ];
}

function filter_products(array $f): array
{
    $items = all_products();

    if (!empty($f['category'])) {
        $items = array_filter($items, fn($p) => $p['category'] === $f['category']);
    }
    if (!empty($f['menh'])) {
        $items = array_filter($items, fn($p) => in_array($f['menh'], $p['menh'] ?? [], true));
    }
    if (!empty($f['price']) && isset(price_ranges()[$f['price']])) {
        $r     = price_ranges()[$f['price']];
        $items = array_filter($items, fn($p) => $p['price'] >= $r['min'] && $p['price'] <= $r['max']);
    }
    if (!empty($f['ni'])) {
        // Vòng vừa tay: ni bằng hoặc lớn hơn tối đa 2 mm so với ni tính được
        $items = array_filter($items, fn($p) => !empty($p['ni']) && $p['ni'] >= $f['ni'] && $p['ni'] <= $f['ni'] + 2);
    }
    if (!empty($f['ni_nhan'])) {
        // Nhẫn vừa tay: ni của khách nằm trong khoảng ni mẫu nhẫn làm / điều chỉnh được
        $items = array_filter($items, fn($p) => ($r = ring_range($p)) && $f['ni_nhan'] >= $r[0] && $f['ni_nhan'] <= $r[1]);
    }
    if (!empty($f['q'])) {
        $words = array_filter(explode(' ', vn_fold($f['q'])));
        $items = array_filter($items, function ($p) use ($words) {
            $hay = vn_fold($p['name'] . ' ' . $p['color'] . ' ' . category_name($p['category']) . ' ' . $p['short']);
            foreach ($words as $w) {
                if (mb_strpos($hay, $w) === false) {
                    return false;
                }
            }
            return true;
        });
    }

    $items = array_values($items);
    $order = array_flip(array_column(all_products(), 'slug'));

    switch ($f['sort'] ?? '') {
        case 'moi':
            usort($items, fn($a, $b) => [!empty($b['new']), $order[$a['slug']]] <=> [!empty($a['new']), $order[$b['slug']]]);
            break;
        case 'gia-tang':
            usort($items, fn($a, $b) => ($a['price'] ?: PHP_INT_MAX) <=> ($b['price'] ?: PHP_INT_MAX));
            break;
        case 'gia-giam':
            usort($items, fn($a, $b) => $b['price'] <=> $a['price']);
            break;
        case 'ten':
            $coll = class_exists('Collator') ? new Collator('vi_VN') : null;
            usort($items, fn($a, $b) => $coll ? $coll->compare($a['name'], $b['name']) : strcmp(vn_fold($a['name']), vn_fold($b['name'])));
            break;
    }
    // Món đã có chủ luôn xếp cuối danh sách
    return array_merge(
        array_values(array_filter($items, fn($p) => !is_sold($p))),
        array_values(array_filter($items, 'is_sold'))
    );
}

function related_products(array $product, int $limit = 4): array
{
    // Gợi ý chỉ những món đang mua được (bỏ món đã có chủ / đang được khách khác giữ hoặc đặt)
    $pool = array_filter(all_products(), fn($p) => $p['slug'] !== $product['slug'] && !is_sold($p) && !hold_for($p['slug']));
    $same = array_filter($pool, fn($p) => $p['category'] === $product['category']);
    $more = array_filter($pool, fn($p) => $p['category'] !== $product['category']
        && array_intersect($p['menh'] ?? [], $product['menh'] ?? []));
    return array_slice(array_merge(array_values($same), array_values($more)), 0, $limit);
}

function stock_label(string $stock): string
{
    return ['con-hang' => 'Còn hàng', 'het-hang' => 'Hết hàng', 'dat-truoc' => 'Đặt trước', 'da-ban' => 'Đã có chủ'][$stock] ?? 'Còn hàng';
}

/* ------------------------------------------------------------------
 * Độc bản · đã có chủ · giữ hàng
 * ------------------------------------------------------------------ */

function is_sold(array $p): bool
{
    return ($p['stock'] ?? '') === 'da-ban';
}

function is_unique(array $p): bool
{
    return !empty($p['doc_ban']);
}

/*
 * Giữ hàng 2 bước:
 *  1. Khách gửi yêu cầu  → 'pending'  : giữ tạm 'hold_pending_minutes' phút để khách chuyển cọc.
 *  2. Shop xác nhận cọc  → 'confirmed': giữ chính thức 'hold_hours' giờ kể từ lúc xác nhận.
 * Dữ liệu lưu trong data/don-hang/giu-hang.json (mỗi sản phẩm một lượt giữ).
 */
function holds_file(): string
{
    return ROOT . '/data/don-hang/giu-hang.json';
}

/** Toàn bộ lượt giữ (kể cả đã hết hạn gần đây) */
function read_holds_raw(): array
{
    $file = holds_file();
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    return is_array($data) ? $data : [];
}

/** Các lượt giữ còn hiệu lực */
function all_holds(): array
{
    static $holds = null;
    if ($holds === null) {
        $holds = array_filter(read_holds_raw(), fn($h) => ($h['until'] ?? 0) > time());
    }
    return $holds;
}

function hold_for(string $slug): ?array
{
    return all_holds()[$slug] ?? null;
}

function hold_status(array $h): string
{
    if (($h['until'] ?? 0) <= time()) {
        return 'expired';
    }
    return $h['status'] ?? 'confirmed';
}

function find_hold_by_code(string $code): ?array
{
    foreach (read_holds_raw() as $h) {
        if (($h['code'] ?? '') === $code) {
            return $h;
        }
    }
    return null;
}

/** Đọc – sửa – ghi file giữ hàng có khóa file (tránh 2 người ghi cùng lúc) */
function write_holds(callable $fn)
{
    $dir = dirname(holds_file());
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $fh = @fopen(holds_file(), 'c+');
    if (!$fh) {
        return false;
    }
    flock($fh, LOCK_EX);
    $data = json_decode((string) stream_get_contents($fh), true);
    $data = is_array($data) ? $data : [];
    // bỏ các lượt đã hết hạn quá 7 ngày
    $data = array_filter($data, fn($h) => ($h['until'] ?? 0) > time() - 7 * 86400);
    $result = $fn($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

/** Tạo lượt giữ mới; trả về false nếu món đang có người khác giữ */
function save_hold(string $slug, array $hold): bool
{
    return (bool) write_holds(function (array &$data) use ($slug, $hold) {
        if (isset($data[$slug]) && ($data[$slug]['until'] ?? 0) > time()) {
            return false;
        }
        $data[$slug] = $hold;
        return true;
    });
}

/** Shop xác nhận đã nhận cọc → giữ chính thức. Trả về 'ok' | 'already' | 'notfound' | 'taken' | 'sold' */
function confirm_hold(string $code): string
{
    return (string) write_holds(function (array &$data) use ($code) {
        foreach ($data as $slug => $h) {
            if (($h['code'] ?? '') !== $code) {
                continue;
            }
            if (($h['status'] ?? '') === 'confirmed' && $h['until'] > time()) {
                return 'already';
            }
            $p = find_product($slug);
            if (!$p || is_sold($p)) {
                return 'sold';
            }
            $data[$slug]['status']       = 'confirmed';
            $data[$slug]['confirmed_at'] = time();
            $data[$slug]['until']        = time() + max(1, (int) cfg('hold_hours', 24)) * 3600;
            return 'ok';
        }
        return 'notfound';
    });
}

/**
 * Gia hạn lượt giữ đã nhận cọc thêm 'hold_extend_hours' giờ (mỗi lượt giữ chỉ 1 lần – theo chính sách đặt cọc).
 * Trả về thời điểm giữ mới, hoặc 0 nếu không gia hạn được. Phiếu đặt cọc được cập nhật theo.
 */
function extend_hold(string $code): int
{
    $until = (int) write_holds(function (array &$data) use ($code) {
        foreach ($data as $slug => $h) {
            if (($h['code'] ?? '') === $code && ($h['type'] ?? 'hold') === 'hold' && ($h['status'] ?? '') === 'confirmed'
                && ($h['until'] ?? 0) > time() && empty($h['extended_at'])) {
                $data[$slug]['until']       = $h['until'] + max(1, (int) cfg('hold_extend_hours', 24)) * 3600;
                $data[$slug]['extended_at'] = time();
                return $data[$slug]['until'];
            }
        }
        return 0;
    });
    $r = $until ? find_receipt($code) : null;
    if ($r) {
        $r['hold_until']  = $until;
        $r['extended_at'] = time();
        save_receipt($r);
    }
    return $until;
}

/** Hủy lượt giữ (mở bán lại ngay) */
function cancel_hold(string $code): bool
{
    return (bool) write_holds(function (array &$data) use ($code) {
        $found = false;
        foreach ($data as $slug => $h) {
            if (($h['code'] ?? '') === $code) {
                unset($data[$slug]);
                $found = true;
            }
        }
        return $found;
    });
}

/* ------------------------------------------------------------------
 * Đơn hàng: lưu trữ, giữ món độc bản trong đơn chuyển khoản, thanh toán
 * ------------------------------------------------------------------ */

/** Lưu / cập nhật đơn hàng (data/don-hang/don-hang.json — giữ 'order_keep_days' ngày; đơn đã có phiếu thì phiếu lưu vĩnh viễn) */
function save_order(array $order): void
{
    $keep = max(30, (int) cfg('order_keep_days', 730));
    update_json_store('don-hang.json', function (array &$all) use ($order, $keep) {
        $all = array_filter($all, fn($o) => ($o['created'] ?? 0) > time() - $keep * 86400);
        $all[$order['code']] = $order;
    });
}

function find_order(string $code): ?array
{
    return read_json_store('don-hang.json')[$code] ?? null;
}

function order_manage_url(string $code): string
{
    return absolute_url(base_path() . '/dat-hang.php?quan-ly=' . rawurlencode($code) . '&k=' . sign_code($code));
}

/**
 * Giữ các món độc bản của một đơn chuyển khoản (tất cả hoặc không món nào).
 * Trả về danh sách mã sản phẩm vừa bị người khác giữ (rỗng = thành công).
 */
function reserve_order_items(array $slugs, array $base): array
{
    return (array) write_holds(function (array &$data) use ($slugs, $base) {
        $taken = array_values(array_filter($slugs, fn($s) => isset($data[$s]) && ($data[$s]['until'] ?? 0) > time()));
        if ($taken) {
            return $taken;
        }
        foreach ($slugs as $s) {
            $p = find_product($s);
            $data[$s] = $base + ['slug' => $s, 'name_product' => $p['name'] ?? $s];
        }
        return [];
    });
}

/** Số tiền đã nhận cho đơn */
function order_paid_amount(string $code): int
{
    return (int) (payment_for($code)['amount'] ?? 0);
}

/**
 * Trạng thái đơn: 'cod' | 'pending' (chờ chuyển khoản) | 'partial' (chuyển thiếu) | 'paid'
 *                 | 'expired' (hết giờ giữ món, chưa có tiền) | 'cancelled'
 */
function order_state(array $o): string
{
    if (($o['status'] ?? '') === 'cancelled') {
        return 'cancelled';
    }
    if (($o['pay'] ?? '') !== 'chuyen-khoan') {
        return 'cod';
    }
    $paid  = order_paid_amount($o['code']);
    $total = (int) ($o['total'] ?? 0);
    if ($paid > 0 && ($total <= 0 || $paid >= $total)) {
        return 'paid';
    }
    if ($paid > 0) {
        return 'partial';
    }
    if (!empty($o['reserved'])) {
        $active = array_filter(all_holds(), fn($h) => ($h['code'] ?? '') === $o['code']);
        if (!$active) {
            return 'expired';
        }
    }
    return 'pending';
}

/**
 * Đơn đã được thanh toán đủ → giữ các món độc bản tới khi cửa hàng đánh dấu "Đã có chủ".
 * Trả về danh sách món KHÔNG giữ được (đã hết giờ và vừa có người khác giữ / đã bán).
 */
function finalize_order_reservations(array $o, string $status = 'paid'): array
{
    $slugs = $o['reserved'] ?? [];
    if (!$slugs) {
        return [];
    }
    $until = time() + max(1, (int) cfg('order_paid_hold_days', 7)) * 86400;
    return (array) write_holds(function (array &$data) use ($o, $slugs, $until, $status) {
        $lost = [];
        foreach ($slugs as $s) {
            $h = $data[$s] ?? null;
            if ($h && ($h['code'] ?? '') === $o['code']) {
                $data[$s]['status'] = $status;
                $data[$s]['until']  = $until;
            } elseif ($h && ($h['until'] ?? 0) > time()) {
                $lost[] = $s;                       // người khác đang giữ
            } else {
                $p = find_product($s);
                if (!$p || is_sold($p)) {
                    $lost[] = $s;
                    continue;
                }
                $data[$s] = ['type' => 'order', 'code' => $o['code'], 'slug' => $s, 'name_product' => $p['name'],
                             'name' => $o['name'], 'phone' => $o['phone'], 'amount' => $o['total'], 'deposit' => $o['total'],
                             'pay' => $o['pay'] ?? '', 'status' => $status, 'created' => time(), 'until' => $until];
            }
        }
        return $lost;
    });
}

/** Cửa hàng đã gọi khách xác nhận đơn COD → giữ món tới khi giao. Trả về danh sách món không giữ được */
function confirm_cod_order(string $code): array
{
    $o = find_order($code);
    if (!$o) {
        return [];
    }
    $lost = finalize_order_reservations($o, 'confirmed');
    $o['confirmed_at'] = time();
    if ($lost) {
        $o['lost'] = array_values(array_unique(array_merge($o['lost'] ?? [], $lost)));
    }
    save_order($o);
    issue_order_receipt($code);
    return $lost;
}

/** Món đang được giữ cho một đơn đã thanh toán / đã xác nhận (hiển thị "Đã có khách đặt") */
function is_order_taken(?array $h): bool
{
    return $h && ($h['type'] ?? 'hold') === 'order' && in_array($h['status'] ?? '', ['paid', 'confirmed'], true);
}

/** Ghi nhận tiền vào cho đơn (cộng dồn) */
function record_order_payment(string $code, int $amount, string $when, string $ref): void
{
    update_json_store('thanh-toan.json', function (array &$pays) use ($code, $amount, $when, $ref) {
        $prev = $pays[$code]['amount'] ?? 0;
        $pays[$code] = ['amount' => $prev + $amount, 'time' => $when, 'ref' => $ref];
    });
}

/** Sau khi có tiền vào: trả về [trạng thái, danh sách món không giữ được] */
function order_after_payment(string $code): array
{
    $o = find_order($code);
    if (!$o) {
        return ['notfound', []];
    }
    $state = order_state($o);
    $lost  = $state === 'paid' ? finalize_order_reservations($o) : [];
    if ($lost) {                       // ghi lại để trang của khách báo đúng tình huống
        $o['lost'] = array_values(array_unique(array_merge($o['lost'] ?? [], $lost)));
        save_order($o);
    }
    if ($state === 'paid') {
        issue_order_receipt($code);
    }
    return [$state, $lost];
}

/* ------------------------------------------------------------------
 * Thao tác của cửa hàng – dùng chung cho link quản lý (Telegram / email) và trang quản trị.
 * Mỗi hàm trả về [loại thông báo 'success' | 'error', nội dung].
 * ------------------------------------------------------------------ */

function order_state_labels(): array
{
    return ['cod' => 'Chờ xác nhận', 'pending' => 'Chờ thanh toán', 'partial' => 'Đã nhận một phần', 'paid' => 'Đã nhận thanh toán',
            'expired' => 'Hết thời gian giữ món', 'cancelled' => 'Đã hủy'];
}

/** Giai đoạn giao hàng do cửa hàng cập nhật */
function fulfillment_labels(): array
{
    return ['' => 'Chưa giao', 'dang-giao' => 'Đang giao hàng', 'hoan-tat' => 'Đã giao – hoàn tất'];
}

function hold_time(int $ts): string
{
    return date('H:i', $ts) . ' ngày ' . date('d/m/Y', $ts);
}

/** Tên các sản phẩm từ danh sách mã */
function product_names(array $slugs): array
{
    return array_map(fn($s) => find_product($s)['name'] ?? $s, $slugs);
}

/** Sửa một sản phẩm trong dữ liệu (kể cả món đang ẩn) và lưu lại */
function product_update(string $slug, callable $fn): bool
{
    global $PRODUCTS;
    $all = content_load('san-pham');
    $hit = false;
    foreach ($all as $i => $p) {
        if (($p['slug'] ?? '') === $slug) {
            $all[$i] = $fn($p);
            $hit = true;
        }
    }
    if ($hit && content_save('san-pham', $all)) {
        $PRODUCTS = $all;
        return true;
    }
    return false;
}

/** Cửa hàng xác nhận đã nhận đủ tiền một đơn chuyển khoản */
function shop_order_mark_paid(string $code): array
{
    $o = find_order($code);
    if (!$o || !in_array(order_state($o), ['pending', 'partial', 'expired'], true)) {
        return ['error', 'Đơn này không ở trạng thái chờ thanh toán.'];
    }
    $remain = max(0, (int) $o['total'] - order_paid_amount($code));
    record_order_payment($code, $remain ?: 1, date('Y-m-d H:i:s'), 'xac-nhan-thu-cong');
    [, $lost] = order_after_payment($code);
    $lostNames = product_names($lost);
    notify_telegram("✅ ĐƠN {$o['code']} – XÁC NHẬN ĐÃ NHẬN TIỀN (thủ công)\nKhách: {$o['name']} – {$o['phone']}"
        . ($lostNames ? "\n⚠️ Không giữ được: " . implode(', ', $lostNames) : '')
        . (!empty($o['reserved']) ? "\nSau khi giao hàng, bấm “Hoàn tất” trong trang quản trị để chuyển món độc bản sang “Đã có chủ”." : '')
        . (find_receipt($code) ? receipt_share_text($code, $o['phone'], 'Phiếu xác nhận thanh toán') : ''));
    return $lostNames
        ? ['error', 'Đã ghi nhận đơn ' . $code . ' đã thanh toán. Lưu ý: không giữ được ' . implode(', ', $lostNames) . ' (đã có khách khác giữ/mua) – vui lòng liên hệ khách.']
        : ['success', 'Đã ghi nhận đơn ' . $code . ' đã thanh toán. Phiếu xác nhận thanh toán đã được lập cho khách.'];
}

/** Cửa hàng đã gọi khách và xác nhận đơn COD */
function shop_order_confirm_cod(string $code): array
{
    $o = find_order($code);
    if (!$o || order_state($o) !== 'cod' || !empty($o['confirmed_at'])) {
        return ['error', 'Đơn này đã được xác nhận hoặc không phải đơn trả tiền khi nhận hàng.'];
    }
    $lostNames = product_names(confirm_cod_order($code));
    notify_telegram("☑️ ĐÃ XÁC NHẬN ĐƠN COD {$o['code']}\nKhách: {$o['name']} – {$o['phone']}"
        . ($lostNames ? "\n⚠️ Không giữ được: " . implode(', ', $lostNames) : '')
        . receipt_share_text($code, $o['phone'], 'Phiếu xác nhận đơn hàng'));
    return $lostNames
        ? ['error', 'Đã xác nhận đơn ' . $code . '. Lưu ý: không giữ được ' . implode(', ', $lostNames) . ' (đã hết giờ giữ và có khách khác đặt/mua) – vui lòng báo lại khách.']
        : ['success', 'Đã xác nhận đơn ' . $code . '.' . (!empty($o['reserved']) ? ' Món độc bản được giữ tới khi giao.' : '') . ' Phiếu xác nhận đơn hàng đã được lập cho khách.'];
}

/** Hủy đơn: mở bán lại món độc bản, phiếu (nếu có) ghi "Đã hủy" */
function shop_order_cancel(string $code): array
{
    $o = find_order($code);
    if (!$o || order_state($o) === 'cancelled') {
        return ['error', 'Đơn này đã được hủy trước đó.'];
    }
    cancel_hold($code);
    cancel_receipt($code);
    $o['status']       = 'cancelled';
    $o['cancelled_at'] = time();
    save_order($o);
    notify_telegram("❌ ĐÃ HỦY ĐƠN {$o['code']}" . (!empty($o['reserved']) ? ' – mở bán lại món độc bản.' : ''));
    return ['success', 'Đã hủy đơn ' . $code . '.' . (!empty($o['reserved']) ? ' Các món độc bản được mở bán lại.' : '')];
}

/**
 * Cập nhật giao hàng: 'dang-giao' | 'hoan-tat' | '' (chưa giao).
 * Hoàn tất + $markSold: món độc bản trong đơn chuyển sang "Đã có chủ" (không bán trùng được nữa).
 */
function shop_order_fulfill(string $code, string $stage, bool $markSold = true): array
{
    $o = find_order($code);
    if (!$o || order_state($o) === 'cancelled' || !isset(fulfillment_labels()[$stage])) {
        return ['error', 'Không cập nhật được đơn này.'];
    }
    $o['fulfillment'] = $stage;
    $o[$stage === 'hoan-tat' ? 'completed_at' : 'shipping_at'] = time();
    $sold = [];
    if ($stage === 'hoan-tat' && $markSold) {
        foreach ($o['items'] as $it) {
            $p = find_product($it['slug']) ?? null;
            if ($p && is_unique($p) && !is_sold($p)) {
                product_update($it['slug'], fn($x) => ['stock' => 'da-ban'] + $x);
                $sold[] = $p['name'];
            }
        }
        cancel_hold($code);                        // món đã bán → không cần giữ nữa
    }
    save_order($o);
    return ['success', 'Đơn ' . $code . ': ' . fulfillment_labels()[$stage] . '.' . ($sold ? ' Đã chuyển sang “Đã có chủ”: ' . implode(', ', $sold) . '.' : '')];
}

/** Cửa hàng xác nhận đã nhận cọc giữ hàng */
function shop_hold_confirm(string $code): array
{
    $h = find_hold_by_code($code);
    $r = confirm_hold($code);
    if ($r === 'ok' && $h) {
        issue_hold_receipt($code);
        notify_telegram("✅ ĐÃ NHẬN CỌC {$code}\n{$h['name_product']}\nKhách: {$h['name']} – {$h['phone']}\nGiữ đến: " . hold_time(time() + (int) cfg('hold_hours', 24) * 3600)
            . receipt_share_text($code, $h['phone'], 'Phiếu xác nhận cọc'));
    }
    $msg = [
        'ok'       => 'Đã xác nhận nhận cọc. Sản phẩm được giữ chính thức ' . (int) cfg('hold_hours', 24) . ' giờ, phiếu xác nhận đặt cọc đã được lập cho khách.',
        'already'  => 'Lượt giữ này đã được xác nhận trước đó.',
        'notfound' => 'Không tìm thấy lượt giữ (có thể đã bị hủy hoặc món đã được khách khác giữ).',
        'sold'     => 'Sản phẩm đã được đánh dấu "Đã có chủ".',
    ][$r] ?? 'Không thể xác nhận.';
    return [$r === 'ok' || $r === 'already' ? 'success' : 'error', $msg];
}

function shop_hold_extend(string $code): array
{
    $h     = find_hold_by_code($code);
    $until = extend_hold($code);
    if ($until && $h) {
        notify_telegram("⏩ ĐÃ GIA HẠN GIỮ HÀNG {$code}\n{$h['name_product']}\nKhách: {$h['name']} – {$h['phone']}\nGiữ đến: " . hold_time($until));
    }
    return $until
        ? ['success', 'Đã gia hạn giữ hàng đến ' . hold_time($until) . '. Phiếu đặt cọc của khách đã được cập nhật.']
        : ['error', 'Không gia hạn được (lượt giữ chưa nhận cọc, đã hết hạn, hoặc đã gia hạn 1 lần).'];
}

function shop_hold_cancel(string $code): array
{
    $h  = find_hold_by_code($code);
    $ok = cancel_hold($code);
    cancel_receipt($code);
    if ($ok && $h) {
        notify_telegram("❌ ĐÃ HỦY GIỮ HÀNG {$code}\n{$h['name_product']} – mở bán lại.");
    }
    return $ok ? ['success', 'Đã hủy giữ hàng. Sản phẩm được mở bán lại ngay.'] : ['error', 'Không tìm thấy lượt giữ.'];
}

/** Khách đã mua món đang giữ (trả phần còn lại): món chuyển "Đã có chủ", phiếu cọc ghi "Đã hoàn tất mua" */
function shop_hold_complete(string $code): array
{
    $h = find_hold_by_code($code);
    if (!$h || ($h['status'] ?? '') !== 'confirmed') {
        return ['error', 'Chỉ hoàn tất được lượt giữ đã nhận cọc.'];
    }
    product_update($h['slug'], fn($x) => ['stock' => 'da-ban'] + $x);
    cancel_hold($code);
    $r = find_receipt($code);
    if ($r) {
        $r['status']       = 'completed';
        $r['completed_at'] = time();
        save_receipt($r);
    }
    return ['success', 'Đã ghi nhận khách mua “' . $h['name_product'] . '”. Sản phẩm chuyển sang “Đã có chủ”.'];
}

/** Trạng thái phiếu do cửa hàng cập nhật */
function receipt_status_labels(): array
{
    return ['valid' => 'Còn hiệu lực', 'completed' => 'Đã hoàn tất mua', 'credit' => 'Đã chuyển thành phiếu mua hàng',
            'refunded' => 'Đã hoàn tiền', 'cancelled' => 'Đã hủy'];
}

function shop_receipt_status(string $code, string $status, string $note = ''): array
{
    $r = find_receipt($code);
    if (!$r || !isset(receipt_status_labels()[$status])) {
        return ['error', 'Không tìm thấy phiếu.'];
    }
    $r['status']            = $status;
    $r[$status . '_at']     = time();
    $r['status_note']       = $note;
    save_receipt($r);
    return ['success', 'Phiếu ' . $code . ': ' . receipt_status_labels()[$status] . '.'];
}

/* ------------------------------------------------------------------
 * Phiếu xác nhận cho khách: đặt cọc / thanh toán / đơn hàng (COD đã xác nhận)
 * Mỗi phiếu lưu 1 file data/don-hang/phieu/MÃ.json và KHÔNG tự xóa
 * (khách còn dùng làm bằng chứng khi bảo hành, đổi trả, hoàn cọc).
 * Phiếu lưu sẵn thông tin cửa hàng & điều khoản lúc lập → sửa cấu hình sau này không làm đổi phiếu cũ.
 * ------------------------------------------------------------------ */

function receipt_file(string $code): string
{
    return ROOT . '/data/don-hang/phieu/' . preg_replace('/[^A-Z0-9-]/', '', strtoupper($code)) . '.json';
}

function find_receipt(string $code): ?array
{
    $file = receipt_file($code);
    $r    = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    return is_array($r) ? $r : null;
}

function save_receipt(array $r): void
{
    $dir = dirname(receipt_file($r['code']));
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents(receipt_file($r['code']), json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

/* ------------------------------------------------------------------
 * Khách xem lại đơn / lượt giữ: link có mã ký riêng (không phụ thuộc phiên trình duyệt)
 * và trang "Tra cứu đơn hàng" bằng mã đơn + số điện thoại.
 * ------------------------------------------------------------------ */

function customer_sig(string $code): string
{
    return substr(hash_hmac('sha256', 'khach|' . $code, app_secret()), 0, 20);
}

function order_view_url(string $code): string
{
    return absolute_url(base_path() . '/dat-hang.php?ma=' . rawurlencode($code) . '&t=' . customer_sig($code));
}

function hold_view_url(string $code): string
{
    return absolute_url(base_path() . '/giu-hang.php?ma=' . rawurlencode($code) . '&t=' . customer_sig($code));
}

/** Chỉ giữ chữ số; +84 / 84xxxxxxxxx → 0xxxxxxxxx */
function phone_digits(string $phone): string
{
    $d = preg_replace('/\D/', '', $phone);
    return strlen($d) === 11 && strpos($d, '84') === 0 ? '0' . substr($d, 2) : $d;
}

/** Dấu mã hóa số điện thoại lưu trong phiếu – đủ để đối chiếu khi tra cứu mà không lưu số thật */
function phone_key(string $phone): string
{
    return substr(hash('sha256', 'vnp-phone|' . phone_digits($phone)), 0, 24);
}

/** Zalo của khách (mở thẳng cuộc trò chuyện theo số điện thoại) */
function zalo_chat_url(string $phone): string
{
    return 'https://zalo.me/' . phone_digits($phone);
}

/** Đoạn tin Telegram để cửa hàng gửi phiếu cho khách qua Zalo */
function receipt_share_text(string $code, string $phone, string $title = 'Phiếu xác nhận'): string
{
    return "\n\n$title – gửi khách qua Zalo:\n" . receipt_url($code) . "\nMở Zalo khách: " . zalo_chat_url($phone);
}

/**
 * Tra cứu cho khách: mã đơn (NP…) / mã giữ hàng (GIU…) + số điện thoại.
 * Trả về link trang đơn / trang giữ hàng / phiếu, hoặc '' nếu không khớp.
 * Đơn đã quá hạn lưu hoặc lượt giữ đã dọn → vẫn tra được qua phiếu (lưu vĩnh viễn).
 */
function customer_lookup(string $codeInput, string $phone): string
{
    $code   = extract_payment_codes($codeInput)[0] ?? '';
    $digits = phone_digits($phone);
    if ($code === '' || strlen($digits) < 9) {
        return '';
    }
    if (strpos($code, 'NP') === 0) {
        $o = find_order($code);
        if ($o) {
            return phone_digits($o['phone']) === $digits ? order_view_url($code) : '';
        }
    } else {
        $h = find_hold_by_code($code);
        if ($h && ($h['type'] ?? 'hold') === 'hold') {
            return phone_digits($h['phone']) === $digits ? hold_view_url($code) : '';
        }
    }
    $r = find_receipt($code);
    if ($r) {
        $ok = !empty($r['phone_key']) ? hash_equals($r['phone_key'], phone_key($digits)) : mask_phone($digits) === ($r['phone'] ?? '');
        return $ok ? receipt_url($code) : '';
    }
    return '';
}

/** Chống dò mã: đếm số lần tra cứu sai trong giờ hiện tại của địa chỉ IP này ($add = true: ghi thêm 1 lần sai) */
function lookup_fail_count(bool $add = false): int
{
    $ip   = substr(hash('sha256', client_ip() . '|' . app_secret()), 0, 16);
    $hour = date('YmdH');
    return (int) update_json_store('tra-cuu-gioi-han.json', function (array &$all) use ($ip, $hour, $add) {
        $all = array_filter($all, fn($x) => ($x['h'] ?? '') === $hour);
        $n   = (int) ($all[$ip]['n'] ?? 0);
        if ($add) {
            $all[$ip] = ['h' => $hour, 'n' => ++$n];
        }
        return $n;
    });
}

/** Mã ký riêng cho link phiếu (khác link quản lý của cửa hàng – khách không mở được trang quản lý) */
function receipt_sig(string $code): string
{
    return substr(hash_hmac('sha256', 'phieu|' . $code, app_secret()), 0, 20);
}

function receipt_url(string $code): string
{
    return absolute_url(base_path() . '/phieu.php?ma=' . rawurlencode($code) . '&k=' . receipt_sig($code));
}

function receipt_title(string $kind): string
{
    return ['coc' => 'Phiếu xác nhận đặt cọc', 'thanh-toan' => 'Phiếu xác nhận thanh toán', 'don-hang' => 'Phiếu xác nhận đơn hàng'][$kind] ?? 'Phiếu xác nhận';
}

/** 0912345678 → 0912•••678 (phiếu có thể được chia sẻ, không in đủ số điện thoại) */
function mask_phone(string $phone): string
{
    $d = preg_replace('/\D/', '', $phone);
    return strlen($d) >= 8 ? substr($d, 0, 4) . '•••' . substr($d, -3) : $phone;
}

/** Điền {SITE_NAME}, {HOTLINE}, {GIO_GIU}, {PHAN_TRAM_COC}… vào điều khoản (chữ thường, không HTML) */
function receipt_fill(string $text): string
{
    return strtr($text, [
        '{SITE_NAME}'       => cfg('site_name'),
        '{HOTLINE}'         => cfg('hotline'),
        '{EMAIL}'           => cfg('email'),
        '{WEBSITE}'         => preg_replace('#^https?://#', '', site_url()),
        '{LINK_CHINH_SACH}' => preg_replace('#^https?://#', '', absolute_url(url('chinh-sach'))),
    ] + policy_numbers());
}

/** Thông tin cửa hàng in trên phiếu */
function receipt_shop(): array
{
    $bank = cfg('bank', []);
    return [
        'brand'   => cfg('site_name'),
        'name'    => cfg('company_name'),
        'tax'     => cfg('tax_code'),
        'license' => cfg('license_info'),
        'address' => cfg('address'),
        'phone'   => cfg('hotline'),
        'email'   => cfg('email'),
        'web'     => preg_replace('#^https?://#', '', site_url()),
        'bank'    => implode(' · ', array_filter([$bank['bank'] ?? '', $bank['account'] ?? '', $bank['holder'] ?? ''])),
    ];
}

/** Dòng sản phẩm trên phiếu: kèm màu / kích thước / chất liệu và giấy kiểm định (mã + nơi kiểm định) */
function receipt_item(string $slug, string $name, int $price, int $qty): array
{
    $p    = find_product($slug);
    $cert = (string) ($p['cert'] ?? '');
    $c    = $cert !== '' ? find_certificate($cert) : null;
    return [
        'name'   => $name,
        'qty'    => $qty,
        'price'  => $price,
        'spec'   => implode(' · ', array_filter([$p['color'] ?? '', $p['size'] ?? '', $p['material'] ?? ''])),
        'cert'   => $cert,
        'lab'    => (string) ($c['lab'] ?? ''),
        'unique' => $p ? is_unique($p) : false,
    ];
}

/** Mã giao dịch ngân hàng để in trên phiếu (Casso lưu dạng "id|mã-ngân-hàng"; xác nhận tay thì không có) */
function bank_reference(string $ref): string
{
    if ($ref === '' || $ref === 'xac-nhan-thu-cong') {
        return '';
    }
    $parts = explode('|', $ref);
    return trim((string) end($parts)) ?: trim($parts[0]);
}

/** "45 Nguyễn Huệ, Quận 1, TP.HCM" → "•••, Quận 1, TP.HCM" (phiếu có thể được chia sẻ – không in số nhà) */
function mask_address(string $address): string
{
    $parts = array_map('trim', explode(',', $address));
    return count($parts) >= 3 ? '•••, ' . implode(', ', array_slice($parts, -2)) : '•••';
}

/**
 * Lập phiếu (hoặc cập nhật số tiền nếu đã có). Lần đầu lập thì gửi email cho khách (nếu khách để lại email).
 * Trả về phiếu đã lưu.
 */
function issue_receipt(array $r, string $email = ''): array
{
    $old = find_receipt($r['code']);
    $r['status'] = $old['status'] ?? 'valid';
    $r['issued'] = $old['issued'] ?? time();
    $r['shop']   = $old['shop'] ?? receipt_shop();
    $r['terms']  = $old['terms'] ?? array_map('receipt_fill', (array) (cfg('receipt_terms', [])[$r['kind']] ?? []));
    $r['footer'] = $old['footer'] ?? receipt_fill((string) cfg('receipt_note'));
    if ($old) {
        $r['updated'] = time();
    }
    save_receipt($r);
    if (!$old && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_receipt_email($r, $email);
    }
    return $r;
}

/** Phiếu xác nhận đặt cọc – lập khi cọc được xác nhận (tự động qua Casso hoặc cửa hàng bấm) */
function issue_hold_receipt(string $code, int $amount = 0, string $when = '', string $ref = '', bool $notify = true): ?array
{
    $h = find_hold_by_code($code);
    if (!$h || ($h['type'] ?? 'hold') !== 'hold' || ($h['status'] ?? '') !== 'confirmed') {
        return null;
    }
    $p     = find_product($h['slug']);
    $price = (int) ($p['price'] ?? 0);
    $paid  = $amount > 0 ? $amount : (int) $h['deposit'];
    return issue_receipt([
        'kind'       => 'coc',
        'code'       => $code,
        'name'       => $h['name'],
        'phone'      => mask_phone($h['phone']),
        'phone_key'  => phone_key($h['phone']),
        'items'      => [receipt_item($h['slug'], $h['name_product'], $price, 1)],
        'total'      => $price,
        'paid'       => $paid,
        'remain'     => max(0, $price - $paid),
        'method'     => 'Chuyển khoản ngân hàng',
        'paid_at'    => ($when !== '' ? strtotime($when) : 0) ?: time(),
        'ref'        => $ref,
        'hold_until' => (int) $h['until'],
        'agreed_at'  => (int) ($h['agreed_at'] ?? 0),
        'policy'     => (string) ($h['policy'] ?? ''),
    ], $notify ? (string) ($h['email'] ?? '') : '');
}

/** Phiếu xác nhận thanh toán (đơn chuyển khoản đã đủ tiền) / xác nhận đơn hàng (COD đã được cửa hàng xác nhận) */
function issue_order_receipt(string $code, bool $notify = true): ?array
{
    $o = find_order($code);
    if (!$o) {
        return null;
    }
    $cod = ($o['pay'] ?? '') !== 'chuyen-khoan';
    if ($cod ? empty($o['confirmed_at']) : order_state($o) !== 'paid') {
        return null;
    }
    $pay   = payment_for($code);
    $total = (int) $o['total'];
    $paid  = $cod || $total <= 0 ? 0 : (int) ($pay['amount'] ?? 0);
    $lost  = array_map(fn($s) => find_product($s)['name'] ?? $s, $o['lost'] ?? []);
    return issue_receipt([
        'kind'     => $cod ? 'don-hang' : 'thanh-toan',
        'code'     => $code,
        'name'     => $o['name'],
        'phone'    => mask_phone($o['phone']),
        'phone_key'=> phone_key($o['phone']),
        'address'  => mask_address((string) ($o['address'] ?? '')),
        'items'    => array_map(fn($i) => receipt_item($i['slug'], $i['name'], (int) $i['price'], (int) $i['qty']), $o['items']),
        'subtotal' => (int) $o['subtotal'],
        'shipping' => (int) $o['shipping'],
        'gifts'    => $o['gifts'] ?? [],
        'vat'      => (int) ($o['vat'] ?? 0),
        'vat_rate' => (int) ($o['vat_rate'] ?? 0),
        'total'    => $total,
        'paid'     => $paid,
        'remain'   => $cod ? $total : max(0, $total - $paid),
        'method'   => $cod ? 'Thanh toán khi nhận hàng (COD)' : 'Chuyển khoản ngân hàng',
        'paid_at'  => $cod ? 0 : (strtotime((string) ($pay['time'] ?? '')) ?: time()),
        'ref'      => $cod ? '' : bank_reference((string) ($pay['ref'] ?? '')),
        'note'     => $lost ? 'Món ' . implode(', ', $lost) . ' đã có khách khác đặt trước khi tiền về – cửa hàng sẽ liên hệ để đổi mẫu tương tự hoặc hoàn tiền.' : '',
    ], $notify ? (string) ($o['email'] ?? '') : '');
}

/**
 * Link phiếu mở khi phiếu CHƯA phát hành (khách bấm "Xem phiếu" ngay sau khi đặt):
 * trả về thông tin trạng thái để hiển thị, hoặc null nếu không có đơn / lượt giữ này.
 * Đơn đã thanh toán / đã xác nhận mà chưa có phiếu (đơn cũ) → lập phiếu ngay, trả về ['issued' => true].
 */
function receipt_pending_info(string $code): ?array
{
    if (strpos($code, 'NP') === 0) {
        $o = find_order($code);
        if (!$o) {
            return null;
        }
        $cod   = ($o['pay'] ?? '') !== 'chuyen-khoan';
        $state = order_state($o);
        if (($cod && !empty($o['confirmed_at']) && $state === 'cod') || $state === 'paid') {
            return issue_order_receipt($code, false) ? ['issued' => true] : null;
        }
        return [
            'type'   => 'order',
            'kind'   => $cod ? 'don-hang' : 'thanh-toan',
            'state'  => $state,
            'code'   => $code,
            'phone'  => $o['phone'],
            'lines'  => array_map(fn($i) => $i['name'] . ' × ' . (int) $i['qty'], $o['items']),
            'amount' => (int) $o['total'],
            'paid'   => order_paid_amount($code),
            'view'   => order_view_url($code),
        ];
    }
    $h = find_hold_by_code($code);
    if (!$h || ($h['type'] ?? 'hold') !== 'hold') {
        return null;
    }
    $state = hold_status($h);
    if (($h['status'] ?? '') === 'confirmed') {
        return issue_hold_receipt($code, 0, '', '', false) ? ['issued' => true] : null;
    }
    return [
        'type'   => 'hold',
        'kind'   => 'coc',
        'state'  => $state,
        'code'   => $code,
        'phone'  => $h['phone'],
        'lines'  => [$h['name_product']],
        'amount' => (int) $h['deposit'],
        'paid'   => 0,
        'view'   => hold_view_url($code),
    ];
}

/** Đơn / lượt giữ bị hủy → phiếu vẫn còn (là bằng chứng đã trả tiền) nhưng ghi rõ "Đã hủy" */
function cancel_receipt(string $code): void
{
    $r = find_receipt($code);
    if ($r && ($r['status'] ?? '') !== 'cancelled') {
        $r['status']       = 'cancelled';
        $r['cancelled_at'] = time();
        save_receipt($r);
    }
}

function send_receipt_email(array $r, string $to): void
{
    $title = receipt_title($r['kind']);
    $first = $r['items'][0]['name'] ?? '';
    $until = (int) ($r['hold_until'] ?? 0);
    $lead  = [
        'coc'        => 'đã nhận tiền đặt cọc <strong>' . e(format_price($r['paid'])) . '</strong> cho <strong>' . e($first) . '</strong>. Món ngọc được giữ riêng cho bạn đến <strong>' . date('H:i', $until) . ' ngày ' . date('d/m/Y', $until) . '</strong>.',
        'thanh-toan' => 'đã nhận thanh toán <strong>' . e(format_price($r['paid'])) . '</strong> cho đơn hàng <strong>' . e($r['code']) . '</strong>.',
        'don-hang'   => 'đã xác nhận đơn hàng <strong>' . e($r['code']) . '</strong>' . ($r['total'] > 0 ? '. Bạn thanh toán <strong>' . e(format_price($r['total'])) . '</strong> khi nhận hàng.' : '.'),
    ][$r['kind']] ?? '';
    send_mail($to, cfg('site_name') . ' – ' . $title . ' ' . $r['code'],
        '<h2>' . e($title) . '</h2><p>Xin chào ' . e($r['name']) . ',</p><p>' . e(cfg('site_name')) . ' ' . $lead . '</p>'
        . '<p>Bạn có thể xem, tải phiếu (PDF) hoặc lưu ảnh phiếu tại link dưới đây – link dùng được trên mọi điện thoại, máy tính:</p>'
        . '<p><a href="' . e(receipt_url($r['code'])) . '" style="display:inline-block;padding:10px 18px;background:#0b5a32;color:#fff;border-radius:6px;text-decoration:none">Xem &amp; tải phiếu xác nhận</a></p>'
        . '<p style="color:#666;font-size:13px">Vui lòng giữ email này. Phiếu có mã QR để kiểm tra trên website của cửa hàng.<br>Hotline: ' . e(cfg('hotline')) . '</p>',
        cfg('email'));
}

/* ------------------------------------------------------------------
 * Tự động xác nhận thanh toán (Casso webhook)
 * ------------------------------------------------------------------ */

/** Đã bật tự động xác nhận tiền vào (Casso hoặc SePay) */
function auto_payments_enabled(): bool
{
    return match ((string) cfg('payment_provider', 'casso')) {
        'sepay' => trim((string) cfg('sepay_api_key')) !== '',
        'none'  => false,
        default => trim((string) cfg('casso_webhook_key')) !== '',
    };
}

/** Đọc – sửa – ghi một file JSON trong data/don-hang/ có khóa file */
function update_json_store(string $name, callable $fn)
{
    $dir = ROOT . '/data/don-hang';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $fh = @fopen($dir . '/' . $name, 'c+');
    if (!$fh) {
        return false;
    }
    flock($fh, LOCK_EX);
    $data   = json_decode((string) stream_get_contents($fh), true);
    $data   = is_array($data) ? $data : [];
    $result = $fn($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

function read_json_store(string $name): array
{
    $file = ROOT . '/data/don-hang/' . $name;
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    return is_array($data) ? $data : [];
}

/**
 * Xử lý một giao dịch tiền vào (từ Casso hoặc SePay): tự xác nhận cọc GIU… / đơn NP… theo mã trong nội dung,
 * ghi nhật ký data/don-hang/giao-dich-YYYY-MM.csv và báo Telegram. Trả về mô tả việc đã làm.
 */
function bank_transfer_received(string $txId, int $amount, string $desc, string $when, string $from = '', string $ref = ''): string
{
    // Bỏ qua giao dịch đã xử lý (dịch vụ có thể gửi lại) và tiền ra
    $isNew = update_json_store('giao-dich-da-xu-ly.json', function (array &$seen) use ($txId) {
        if (isset($seen[$txId])) {
            return false;
        }
        $seen[$txId] = time();
        if (count($seen) > 1000) {
            $seen = array_slice($seen, -1000, null, true);
        }
        return true;
    });
    if (!$isNew || $amount <= 0) {
        return $amount <= 0 ? 'bỏ qua (tiền ra)' : 'bỏ qua (đã xử lý)';
    }

    $codes  = extract_payment_codes($desc);
    $action = 'không có mã';
    $msg    = '';
    foreach ($codes as $code) {
        if (strpos($code, 'GIU') === 0) {
            $h = find_hold_by_code($code);
            if (!$h) {
                $action = "$code: không tìm thấy lượt giữ";
                $msg    = "⚠️ TIỀN VÀO " . format_price($amount) . " – mã $code nhưng không tìm thấy lượt giữ (có thể đã bị hủy). Vui lòng liên hệ khách.\nNội dung: $desc";
            } elseif ($amount < (int) $h['deposit']) {
                $action = "$code: thiếu tiền cọc";
                $msg    = "⚠️ CỌC CHƯA ĐỦ $code – nhận " . format_price($amount) . ' / cần ' . format_price((int) $h['deposit'])
                    . "\n{$h['name_product']}\nKhách: {$h['name']} – {$h['phone']}\nChưa tự xác nhận. Xử lý tại: " . hold_manage_url($code);
            } else {
                $r = confirm_hold($code);
                $action = "$code: xác nhận cọc ($r)";
                if ($r === 'ok') {
                    issue_hold_receipt($code, $amount, $when, $ref);
                    $msg = "✅ TỰ ĐỘNG XÁC NHẬN CỌC $code – " . format_price($amount)
                        . "\n{$h['name_product']}\nKhách: {$h['name']} – {$h['phone']}\nĐã giữ hàng " . (int) cfg('hold_hours', 24) . ' giờ.'
                        . receipt_share_text($code, $h['phone'], 'Phiếu xác nhận cọc');
                } elseif ($r !== 'already') {
                    $msg = "⚠️ NHẬN CỌC $code – " . format_price($amount) . " nhưng không tự xác nhận được ($r). Vui lòng kiểm tra: " . hold_manage_url($code);
                }
            }
        } else { // Đơn hàng NP…
            record_order_payment($code, $amount, $when, $txId);
            [$state, $lost] = order_after_payment($code);
            $o = find_order($code);
            $action = "$code: ghi nhận thanh toán ($state)";
            if ($state === 'paid') {
                $lostNames = product_names($lost);
                $msg = "✅ ĐƠN $code ĐÃ THANH TOÁN ĐỦ – " . format_price($amount) . ($from ? " – từ $from" : '')
                    . ($o ? "\nKhách: {$o['name']} – {$o['phone']}" : '')
                    . ($lostNames ? "\n⚠️ Món đã hết giờ giữ và có khách khác giữ/mua: " . implode(', ', $lostNames) . ' – vui lòng liên hệ khách.' : '')
                    . (!empty($o['reserved']) ? "\nSau khi giao hàng, bấm “Hoàn tất” trong trang quản trị để chuyển món độc bản sang “Đã có chủ”." : '')
                    . ($o && find_receipt($code) ? receipt_share_text($code, $o['phone'], 'Phiếu xác nhận thanh toán') : '');
            } elseif ($state === 'partial') {
                $msg = "⚠️ ĐƠN $code CHUYỂN THIẾU – nhận " . format_price(order_paid_amount($code)) . ' / ' . format_price((int) ($o['total'] ?? 0))
                    . "\nXử lý tại: " . order_manage_url($code);
            } else {
                $msg = "💰 TIỀN VÀO " . format_price($amount) . " – mã $code" . ($o ? " (trạng thái: $state)" : ' nhưng không tìm thấy đơn') . "\nNội dung: $desc";
            }
        }
    }
    if (!$codes) {
        $msg = '💵 TIỀN VÀO ' . format_price($amount) . ($from ? " – từ $from" : '') . "\nNội dung: $desc\n(không có mã đơn / mã giữ hàng)";
    }
    save_csv('giao-dich', ['Thời gian GD', 'Số tiền', 'Nội dung', 'Người chuyển', 'Mã tìm thấy', 'Xử lý'],
        [$when, $amount, $desc, $from, implode(' ', $codes), $action]);
    if ($msg !== '') {
        notify_telegram($msg);
    }
    return $action;
}

/** Thông tin thanh toán đã nhận cho một mã đơn (null nếu chưa) */
function payment_for(string $code): ?array
{
    return read_json_store('thanh-toan.json')[$code] ?? null;
}

/**
 * Tìm mã giữ hàng (GIU…) / mã đơn (NP…) trong nội dung chuyển khoản.
 * Ngân hàng hay bỏ dấu "-" hoặc viết liền, nên so khớp sau khi bỏ ký tự đặc biệt.
 */
function extract_payment_codes(string $description): array
{
    $flat  = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $description));
    $codes = [];
    if (preg_match_all('/(GIU|NP)(\d{6})([A-F0-9]{4})/', $flat, $m, PREG_SET_ORDER)) {
        foreach ($m as $x) {
            $codes[] = $x[1] . $x[2] . '-' . $x[3];
        }
    }
    return array_values(array_unique($codes));
}

/** Kiểm tra chữ ký webhook V2 của Casso (header X-Casso-Signature: t=…,v1=…) */
function casso_verify_signature(string $header, array $body, string $key): bool
{
    if (!preg_match('/t=(\d+),v1=([a-f0-9]+)/', $header, $m)) {
        return false;
    }
    $sort = function ($a) use (&$sort) {
        if (!is_array($a)) {
            return $a;
        }
        ksort($a, SORT_STRING);
        foreach ($a as $k => $v) {
            $a[$k] = $sort($v);
        }
        return $a;
    };
    $sorted = $sort($body);
    // Thử cả 2 cách mã hóa JSON (giữ nguyên / thoát ký tự có dấu) để chắc chắn khớp với Casso
    foreach ([JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE] as $flags) {
        if (hash_equals(hash_hmac('sha512', $m[1] . '.' . json_encode($sorted, $flags), $key), $m[2])) {
            return true;
        }
    }
    return false;
}

/** Khóa bí mật của website (tự tạo một lần, lưu trong data/don-hang/.secret) — dùng để ký link quản lý */
/** Dải địa chỉ của Cloudflare (https://www.cloudflare.com/ips/) – chỉ tin header CF-Connecting-IP khi yêu cầu đến từ đây */
const CLOUDFLARE_IPS = [
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
    '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
    '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
    '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
];

function ip_in_range(string $ip, string $cidr): bool
{
    [$net, $bits] = explode('/', $cidr) + [1 => ''];
    $a = @inet_pton($ip);
    $b = @inet_pton($net);
    if ($a === false || $b === false || strlen($a) !== strlen($b)) {
        return false;
    }
    $bits  = $bits === '' ? strlen($a) * 8 : (int) $bits;
    $bytes = intdiv($bits, 8);
    if (strncmp($a, $b, $bytes) !== 0) {
        return false;
    }
    $rest = $bits % 8;
    return $rest === 0 || ((ord($a[$bytes]) ^ ord($b[$bytes])) & (0xFF << (8 - $rest)) & 0xFF) === 0;
}

/**
 * Địa chỉ IP thật của khách. Nếu website chạy sau Cloudflare (đám mây cam), mọi yêu cầu đều đến từ IP của Cloudflare
 * → lấy IP khách trong header CF-Connecting-IP (chỉ khi yêu cầu thật sự đến từ Cloudflare, tránh bị giả mạo).
 */
function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $cf = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
        foreach (CLOUDFLARE_IPS as $range) {
            if (ip_in_range($ip, $range)) {
                return $cf;
            }
        }
    }
    return $ip;
}

function app_secret(): string
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }
    $file = ROOT . '/data/don-hang/.secret';
    $key  = is_file($file) ? trim((string) file_get_contents($file)) : '';
    if (strlen($key) < 32) {
        $key = bin2hex(random_bytes(32));
        @file_put_contents($file, $key);
        @chmod($file, 0600);
    }
    return $key;
}

function sign_code(string $code): string
{
    return substr(hash_hmac('sha256', $code, app_secret()), 0, 32);
}

/** Link quản lý lượt giữ (gửi cho shop qua Telegram / email) */
function hold_manage_url(string $code): string
{
    return absolute_url(base_path() . '/giu-hang.php?quan-ly=' . rawurlencode($code) . '&k=' . sign_code($code));
}

/** Có thể cho vào giỏ / đặt mua ngay không */
function can_buy(array $p): bool
{
    return !is_sold($p) && ($p['stock'] ?? 'con-hang') !== 'het-hang' && !hold_for($p['slug']);
}

function can_hold(array $p): bool
{
    $min = (int) cfg('hold_min_price', 0);
    return $min > 0 && $p['price'] >= $min && can_buy($p);
}

/** Tiền cọc giữ hàng, làm tròn tới 10.000₫ */
function deposit_amount(array $p): int
{
    return (int) (round($p['price'] * (int) cfg('hold_deposit', 10) / 100 / 10000) * 10000);
}

/** Ni vòng (đường kính trong, mm) từ chu vi bàn tay (cm) */
function ni_from_cm(float $cm): int
{
    return (int) round($cm * 10 / M_PI);
}

/**
 * Chu vi ngón tay (mm) từ số đo khách nhập – nhận cả 4 cách đo:
 * chu vi ngón tay bằng mm (42–76) hoặc cm (4–8), đường kính lòng nhẫn cũ bằng mm (13–25) hoặc cm (1,3–2,5). Trả 0 nếu không hợp lệ.
 */
function ring_circumference(float $v): float
{
    if ($v >= 1.3 && $v <= 2.5) {
        $v *= 10;                       // đường kính tính bằng cm → mm
    } elseif ($v >= 4 && $v <= 8) {
        $v *= 10;                       // chu vi tính bằng cm → mm
    }
    $c = $v < 30 ? $v * M_PI : $v;      // dưới 30 mm = đường kính lòng nhẫn
    return ($c >= 42 && $c <= 76) ? $c : 0.0;
}

/** Ni nhẫn theo bảng ni Việt Nam (ni 12 ≈ chu vi 52,4 mm; mỗi ni hơn kém khoảng 1 mm chu vi). 0 = số đo không hợp lệ */
function ring_ni_from_mm(float $v): int
{
    $c = ring_circumference($v);
    return $c ? max(1, (int) round(($c - 39.8) / 1.047)) : 0;
}

/** Khoảng ni nhẫn của sản phẩm [nhỏ nhất, lớn nhất] (null nếu chưa khai báo) */
function ring_range(array $p): ?array
{
    $r = $p['ni_nhan'] ?? null;
    if (!is_array($r) || count($r) < 1 || (int) $r[0] <= 0) {
        return null;
    }
    $lo = (int) $r[0];
    $hi = (int) ($r[1] ?? $lo);
    return [min($lo, $hi), max($lo, $hi)];
}

/* ------------------------------------------------------------------
 * Bộ sưu tập theo dịp & gợi ý quà tặng
 * ------------------------------------------------------------------ */

function all_collections(): array
{
    return array_filter(content_load('bo-suu-tap'), fn($c) => empty($c['hidden']));
}

function find_collection(string $slug): ?array
{
    $all = all_collections();
    return isset($all[$slug]) ? ['slug' => $slug] + $all[$slug] : null;
}

function collection_url(string $slug): string
{
    return pretty() ? base_path() . '/qua-tang/' . rawurlencode($slug) : url('qua-tang', ['bst' => $slug]);
}

function collection_products(string $slug): array
{
    $items = array_filter(all_products(), fn($p) => in_array($slug, $p['dip'] ?? [], true));
    return array_merge(
        array_values(array_filter($items, fn($p) => !is_sold($p))),
        array_values(array_filter($items, 'is_sold'))
    );
}

/** Người nhận quà → các dịp phù hợp */
function gift_recipients(): array
{
    $custom = content_load('ngu-hanh')['nguoi-nhan'] ?? null;
    if (is_array($custom) && $custom) {
        return $custom;
    }
    return [
        'me'       => ['label' => 'Mẹ',                              'dip' => ['tang-me']],
        'vo'       => ['label' => 'Vợ / người yêu',                  'dip' => ['nguoi-thuong', 'trao-dau']],
        'ong-ba'   => ['label' => 'Ông bà, cha mẹ (mừng thọ)',       'dip' => ['mung-tho']],
        'be'       => ['label' => 'Bé (đầy tháng, thôi nôi)',        'dip' => ['cho-be']],
        'doi-tac'  => ['label' => 'Bạn bè, đối tác (tân gia, khai trương)', 'dip' => ['tan-gia']],
        'ban-than' => ['label' => 'Bản thân',                        'dip' => []],
    ];
}

/**
 * Màu ngọc có "kỵ" (khắc) mệnh người nhận không.
 * Hành của màu ngọc = phần tử ĐẦU TIÊN trong 'menh' của sản phẩm (xanh lá → moc, trắng → kim…).
 * Kỵ khi hành của màu khắc mệnh: Kim khắc Mộc, Mộc khắc Thổ, Thổ khắc Thủy, Thủy khắc Hỏa, Hỏa khắc Kim.
 */
function menh_conflict(array $p, string $menh): bool
{
    $list = $p['menh'] ?? [];
    if (!$menh || !$list || in_array($menh, $list, true)) {
        return false;
    }
    $khac = ['kim' => 'moc', 'moc' => 'tho', 'tho' => 'thuy', 'thuy' => 'hoa', 'hoa' => 'kim'];
    return ($khac[$list[0]] ?? '') === $menh;
}

/** Gợi ý quà: lọc theo ngân sách, bỏ món kỵ mệnh, chấm điểm theo dịp + mệnh người nhận */
function gift_finder(string $who, int $year, string $budget, int $limit = 8): array
{
    $dips  = gift_recipients()[$who]['dip'] ?? [];
    $menh  = $year ? (lunar_year_menh($year)['menh'] ?? '') : '';
    $range = price_ranges()[$budget] ?? null;
    $pool  = array_filter(all_products(), fn($p) => can_buy($p) && !menh_conflict($p, $menh)
        && (!$range || ($p['price'] >= $range['min'] && $p['price'] <= $range['max'])));

    $scored = [];
    foreach ($pool as $p) {
        $score = 0;
        if ($dips && array_intersect($dips, $p['dip'] ?? [])) {
            $score += 4;
        }
        if ($menh && in_array($menh, $p['menh'] ?? [], true)) {
            $score += 2;
        }
        if (!empty($p['featured'])) {
            $score += 1;
        }
        if ($dips && $score < 4 && count($pool) > 4) {
            continue; // ưu tiên đúng dịp; nếu quá ít sản phẩm thì vẫn giữ để có gợi ý
        }
        $scored[] = ['p' => $p, 'score' => $score];
    }
    // Cùng điểm: món có giá xếp theo giá tăng dần, món "Liên hệ" (giá 0) xếp sau
    usort($scored, fn($a, $b) => [$b['score'], $a['p']['price'] ?: PHP_INT_MAX] <=> [$a['score'], $b['p']['price'] ?: PHP_INT_MAX]);
    return array_slice(array_column($scored, 'p'), 0, $limit);
}

/** Số món hợp dịp nhưng chưa niêm yết giá (không lọt vào bộ lọc ngân sách) */
function gift_unpriced_count(string $who, int $year): int
{
    return count(array_filter(gift_finder($who, $year, '', PHP_INT_MAX), fn($p) => (int) $p['price'] <= 0));
}

/* ------------------------------------------------------------------
 * Giấy kiểm định
 * ------------------------------------------------------------------ */

function all_certificates(): array
{
    return content_load('kiem-dinh');
}

/** Bỏ gạch nối, khoảng trắng, phân biệt hoa/thường: VNP-2610-0001 = vnp 2610 0001 = VNP26100001 */
function normalize_cert_code(string $code): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
}

function find_certificate(string $code): ?array
{
    $code = normalize_cert_code($code);
    foreach (all_certificates() as $k => $c) {
        if (normalize_cert_code($k) === $code) {
            return ['code' => $k] + $c;
        }
    }
    return null;
}

function certificate_url(string $code): string
{
    return url('tra-cuu-kiem-dinh', ['ma' => $code]);
}

/* ------------------------------------------------------------------
 * Video sản phẩm
 * ------------------------------------------------------------------ */

/** ['type' => 'youtube'|'file', 'src' => ...] hoặc null */
function video_source(string $video): ?array
{
    $video = trim($video);
    if ($video === '') {
        return null;
    }
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|shorts/|embed/))([A-Za-z0-9_-]{11})~', $video, $m)) {
        return ['type' => 'youtube', 'src' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&playsinline=1', 'id' => $m[1]];
    }
    if (preg_match('#^https?://#', $video)) {
        return ['type' => 'file', 'src' => $video];
    }
    $video = ltrim($video, '/');
    if (strncmp($video, 'videos/uploads/', 15) === 0 && ($r2 = r2_url('assets/' . $video)) !== '') {
        return ['type' => 'file', 'src' => $r2];
    }
    return is_file(ROOT . '/assets/' . $video) ? ['type' => 'file', 'src' => base_path() . '/assets/' . $video] : null;
}

/* ------------------------------------------------------------------
 * Thông báo qua Telegram (đơn hàng, giữ hàng, đặt lịch, liên hệ)
 * ------------------------------------------------------------------ */

function notify_telegram(string $text): bool
{
    $token = trim((string) cfg('telegram_bot_token'));
    $chat  = trim((string) cfg('telegram_chat_id'));
    if ($token === '' || $chat === '') {
        return false;
    }
    $url  = 'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage';
    $body = http_build_query(['chat_id' => $chat, 'text' => mb_substr($text, 0, 4000), 'disable_web_page_preview' => 'true']);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 4]);
        $ok = curl_exec($ch) !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return $ok;
    }
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                                             'content' => $body, 'timeout' => 6]]);
    return @file_get_contents($url, false, $ctx) !== false;
}

/* ------------------------------------------------------------------
 * Dữ liệu sản phẩm cho JavaScript (giỏ hàng, yêu thích)
 * ------------------------------------------------------------------ */

function catalog_data(): array
{
    $out = [];
    foreach (all_products() as $p) {
        $out[$p['slug']] = [
            'name'   => $p['name'],
            'price'  => (int) $p['price'],
            'image'  => product_image($p),
            'url'    => product_url($p),
            'stock'  => $p['stock'] ?? 'con-hang',
            'meta'   => $p['color'],
            'unique' => is_unique($p),
            'cert'   => (string) ($p['cert'] ?? ''),
            'buy'    => can_buy($p),
        ];
    }
    return $out;
}

/* ------------------------------------------------------------------
 * Ngũ hành – tra cứu mệnh theo năm sinh âm lịch
 * ------------------------------------------------------------------ */

function menh_info(): array
{
    $base = menh_info_default();
    foreach ((array) (content_load('ngu-hanh')['menh'] ?? []) as $k => $m) {
        if (isset($base[$k]) && is_array($m)) {
            $base[$k] = array_merge($base[$k], array_intersect_key($m, array_flip(['color', 'good', 'avoid', 'jade', 'desc'])));
        }
    }
    return $base;
}

function menh_info_default(): array
{
    return [
        'kim'  => ['name' => 'Kim',  'color' => '#9a9a9a', 'good' => 'Trắng, xám, ghi, vàng, nâu đất', 'avoid' => 'Đỏ, hồng, tím',
                   'jade' => 'Ngọc trắng, trắng băng, ngọc vàng (hoàng phỉ)', 'desc' => 'Thanh lịch, quyết đoán'],
        'moc'  => ['name' => 'Mộc',  'color' => '#2e7d32', 'good' => 'Xanh lá, đen, xanh dương', 'avoid' => 'Trắng, xám',
                   'jade' => 'Ngọc xanh lục, xanh táo, mặc thúy (đen)', 'desc' => 'Sinh sôi, phát triển'],
        'thuy' => ['name' => 'Thủy', 'color' => '#1f4e79', 'good' => 'Đen, xanh dương, trắng, xám', 'avoid' => 'Vàng, nâu đất',
                   'jade' => 'Ngọc trắng băng, mặc thúy (đen)', 'desc' => 'Mềm mại, linh hoạt'],
        'hoa'  => ['name' => 'Hỏa',  'color' => '#b23a2b', 'good' => 'Đỏ, hồng, tím, xanh lá', 'avoid' => 'Đen, xanh dương',
                   'jade' => 'Ngọc xanh lục, tím lavender, hồng phỉ', 'desc' => 'Nhiệt huyết, tỏa sáng'],
        'tho'  => ['name' => 'Thổ',  'color' => '#9a6b1f', 'good' => 'Vàng, nâu đất, đỏ, hồng, tím', 'avoid' => 'Xanh lá',
                   'jade' => 'Ngọc vàng (hoàng phỉ), tím lavender, hồng phỉ', 'desc' => 'Vững vàng, bao dung'],
    ];
}

/** Bảng Can, Chi và 30 nạp âm (dùng chung cho PHP và JavaScript) */
function menh_tables(): array
{
    return [
        'can'   => ['Giáp', 'Ất', 'Bính', 'Đinh', 'Mậu', 'Kỷ', 'Canh', 'Tân', 'Nhâm', 'Quý'],
        'chi'   => ['Tý', 'Sửu', 'Dần', 'Mão', 'Thìn', 'Tỵ', 'Ngọ', 'Mùi', 'Thân', 'Dậu', 'Tuất', 'Hợi'],
        // [tên nạp âm, mã mệnh] — mỗi cặp năm liên tiếp chung một nạp âm, bắt đầu từ Giáp Tý
        'napam' => [
            ['Hải Trung Kim', 'kim'], ['Lư Trung Hỏa', 'hoa'], ['Đại Lâm Mộc', 'moc'], ['Lộ Bàng Thổ', 'tho'],
            ['Kiếm Phong Kim', 'kim'], ['Sơn Đầu Hỏa', 'hoa'], ['Giản Hạ Thủy', 'thuy'], ['Thành Đầu Thổ', 'tho'],
            ['Bạch Lạp Kim', 'kim'], ['Dương Liễu Mộc', 'moc'], ['Tuyền Trung Thủy', 'thuy'], ['Ốc Thượng Thổ', 'tho'],
            ['Tích Lịch Hỏa', 'hoa'], ['Tùng Bách Mộc', 'moc'], ['Trường Lưu Thủy', 'thuy'], ['Sa Trung Kim', 'kim'],
            ['Sơn Hạ Hỏa', 'hoa'], ['Bình Địa Mộc', 'moc'], ['Bích Thượng Thổ', 'tho'], ['Kim Bạch Kim', 'kim'],
            ['Phú Đăng Hỏa', 'hoa'], ['Thiên Hà Thủy', 'thuy'], ['Đại Trạch Thổ', 'tho'], ['Thoa Xuyến Kim', 'kim'],
            ['Tang Đố Mộc', 'moc'], ['Đại Khê Thủy', 'thuy'], ['Sa Trung Thổ', 'tho'], ['Thiên Thượng Hỏa', 'hoa'],
            ['Thạch Lựu Mộc', 'moc'], ['Đại Hải Thủy', 'thuy'],
        ],
    ];
}

/** Trả về can chi, nạp âm, mã mệnh cho năm âm lịch */
function lunar_year_menh(int $year): ?array
{
    if ($year < 1900 || $year > 2100) {
        return null;
    }
    $t = menh_tables();
    $i = (($year - 4) % 60 + 60) % 60;
    [$name, $code] = $t['napam'][intdiv($i, 2)];
    return [
        'canchi' => $t['can'][$i % 10] . ' ' . $t['chi'][$i % 12],
        'napam'  => $name,
        'menh'   => $code,
    ];
}

/* ------------------------------------------------------------------
 * Bài viết & chính sách
 * ------------------------------------------------------------------ */

/** Bài viết đang hiển thị (bỏ bài "Bản nháp / ẩn"), mới nhất trước */
function all_articles(): array
{
    static $items = null;
    if ($items === null) {
        $items = array_values(array_filter(content_load('bai-viet'), fn($a) => empty($a['hidden'])));
        usort($items, fn($a, $b) => strcmp((string) $b['date'], (string) $a['date']));
    }
    return $items;
}

function find_article(string $slug): ?array
{
    foreach (all_articles() as $a) {
        if ($a['slug'] === $slug) {
            return $a;
        }
    }
    return null;
}

function all_policies(): array
{
    return content_load('chinh-sach');
}

/** Banner trang chủ đang bật */
function all_banners(): array
{
    return array_values(array_filter(content_load('banner'), fn($b) => empty($b['hidden'])));
}

/** Ảnh & cảm nhận "Khách đeo thật" đang hiển thị */
function all_feedback(): array
{
    return array_values(array_filter(content_load('khach-hang'), fn($f) => empty($f['hidden'])));
}

/* ------------------------------------------------------------------
 * Bảo mật form
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
        // Ô bẫy chống spam (người thật không nhìn thấy)
        . '<div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
}

/** Kiểm tra token, ô bẫy và giới hạn tần suất gửi. Trả về thông báo lỗi hoặc null. */
function form_guard(string $bucket, int $seconds = 15): ?string
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        return 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.';
    }
    if (!empty($_POST['website'])) {
        return 'Không thể gửi biểu mẫu.';
    }
    $last = $_SESSION['last_submit'][$bucket] ?? 0;
    if (time() - $last < $seconds) {
        return 'Bạn vừa gửi biểu mẫu. Vui lòng đợi vài giây rồi thử lại.';
    }
    return null;
}

function form_mark_sent(string $bucket): void
{
    $_SESSION['last_submit'][$bucket] = time();
}

function clean_text(string $key, int $max = 500): string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
    return mb_substr($v, 0, $max);
}

function valid_phone(string $phone): bool
{
    $digits = preg_replace('/[\s.\-()]/', '', $phone);
    return (bool) preg_match('/^(\+?84|0)[0-9]{9,10}$/', $digits);
}

function flash(?string $message = null, string $type = 'success')
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ------------------------------------------------------------------
 * Lưu dữ liệu & gửi email
 * ------------------------------------------------------------------ */

/** Ghi một dòng vào file CSV trong data/don-hang/ (mở được bằng Excel) */
function save_csv(string $name, array $header, array $row): bool
{
    $dir = ROOT . '/data/don-hang';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    // File đang được mở bằng Excel / WPS… trên máy (bị khóa) → không chờ mãi (website sẽ treo):
    // thử khóa trong ~2 giây, không được thì ghi sang file "...-ghi-tam.csv" bên cạnh để không mất dòng nào.
    foreach ([$name . '-' . date('Y-m') . '.csv', $name . '-' . date('Y-m') . '-ghi-tam.csv'] as $try => $fileName) {
        $file  = $dir . '/' . $fileName;
        $isNew = !is_file($file);
        $fh    = @fopen($file, 'a');
        if (!$fh) {
            return false;
        }
        for ($i = 0, $locked = false; $i < 20 && !($locked = flock($fh, LOCK_EX | LOCK_NB)); $i++) {
            usleep(100000);
        }
        if ($locked) {
            break;
        }
        fclose($fh);
        if ($try === 1) {
            return false;
        }
    }
    if ($isNew) {
        fwrite($fh, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng tiếng Việt
        fputcsv($fh, $header, ',', '"', '\\');
    }
    // Chặn công thức Excel độc hại
    $row = array_map(fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $row);
    fputcsv($fh, $row, ',', '"', '\\');
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}

function send_mail(string $to, string $subject, string $html, string $replyTo = ''): bool
{
    global $MAIL_ERROR;
    $MAIL_ERROR = '';
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $MAIL_ERROR = 'Địa chỉ nhận chưa đúng: ' . $to;
        return false;
    }
    $from = cfg('mail_from') ?: cfg('email');
    // Đã cài SMTP trong trang quản trị → gửi qua SMTP (VPS thường không tự gửi được email)
    if (smtp_enabled()) {
        $body = '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#353333;line-height:1.6">' . $html . '</body></html>';
        $MAIL_ERROR = smtp_send($to, $subject, $body, (string) $from, (string) cfg('site_name'), $replyTo);
        return $MAIL_ERROR === '';
    }
    if (!function_exists('mail')) {
        $MAIL_ERROR = 'Máy chủ không có chức năng gửi email – hãy cài SMTP.';
        return false;
    }
    $fromName = '=?UTF-8?B?' . base64_encode(cfg('site_name')) . '?=';
    $headers  = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'From: ' . $fromName . ' <' . $from . '>',
    ];
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $body = '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#353333;line-height:1.6">' . $html . '</body></html>';
    $params = filter_var($from, FILTER_VALIDATE_EMAIL) ? '-f' . $from : '';
    $ok = @mail(
        $to,
        '=?UTF-8?B?' . base64_encode($subject) . '?=',
        chunk_split(base64_encode($body)),
        implode("\r\n", $headers),
        $params
    );
    if (!$ok) {
        $MAIL_ERROR = mail_function_usable() ? 'Máy chủ không gửi được email.' : 'Máy chủ không tự gửi được email – hãy cài SMTP (Gmail / email tên miền).';
    }
    return $ok;
}

/* ------------------------------------------------------------------
 * Hiển thị
 * ------------------------------------------------------------------ */

function icon(string $name, string $class = ''): string
{
    static $paths = [
        'search'   => '<circle cx="11" cy="11" r="7.5"/><path d="m20.5 20.5-4.2-4.2"/>',
        'bag'      => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'menu'     => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
        'close'    => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'down'     => '<path d="m6 9 6 6 6-6"/>',
        'left'     => '<path d="m15 18-6-6 6-6"/>',
        'right'    => '<path d="m9 18 6-6-6-6"/>',
        'up'       => '<path d="m18 15-6-6-6 6"/>',
        'arrow'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'mail'     => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'clock'    => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>',
        'truck'    => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'return'   => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
        'gem'      => '<path d="M6 3h12l4 6-10 13L2 9Z"/><path d="M11 3 8 9l4 13 4-13-3-6"/><path d="M2 9h20"/>',
        'award'    => '<circle cx="12" cy="8" r="6"/><path d="M15.48 12.89 17 22l-5-3-5 3 1.52-9.11"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'plus'     => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'minus'    => '<path d="M5 12h14"/>',
        'trash'    => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'arrow-up' => '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
        'filter'   => '<path d="M22 3H2l8 9.46V19l4 2v-8.54Z"/>',
        'gift'     => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        'calendar' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
        'chat'     => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'card'     => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
        'copy'     => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'pause'    => '<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>',
        'play'     => '<path d="M7 4v16l13-8Z"/>',
        'sparkle'  => '<path d="M12 3l1.9 5.6L19.5 10.5l-5.6 1.9L12 18l-1.9-5.6L4.5 10.5l5.6-1.9Z"/>',
        'leaf'     => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        'user'     => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'instagram'=> '<rect width="20" height="20" x="2" y="2" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'youtube'  => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
        'tiktok'   => '<path d="M16 3c.4 2.6 2.1 4.3 5 4.5v3.4c-1.8 0-3.5-.6-5-1.6V15a6 6 0 1 1-6-6v3.4A2.6 2.6 0 1 0 12.6 15V3Z"/>',
        'messenger'=> '<path d="M12 2C6.5 2 2 6.1 2 11.2c0 2.9 1.4 5.5 3.7 7.2V22l3.4-1.9c.9.3 1.9.4 2.9.4 5.5 0 10-4.1 10-9.2S17.5 2 12 2Z"/><path d="m7 13.5 3.5-3.7 2.5 2.4 4-2.4-3.5 3.7-2.5-2.4Z"/>',
        'heart'    => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'eye'      => '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
        'video'    => '<path d="m16 13 5.22 3.48a.5.5 0 0 0 .78-.42V7.89a.5.5 0 0 0-.76-.43L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/>',
        'zoom'     => '<circle cx="11" cy="11" r="7.5"/><path d="m20.5 20.5-4.2-4.2"/><path d="M11 8v6"/><path d="M8 11h6"/>',
        'cert'     => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m9 15 2 2 4-4"/>',
        'lock'     => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'image'    => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
        'printer'  => '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/>',
        'receipt'  => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'ruler'    => '<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/>',
        'store'    => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M2 7h20v3a3 3 0 0 1-5 2.2A3 3 0 0 1 12 12a3 3 0 0 1-5 .2A3 3 0 0 1 2 10Z"/>',
    ];
    $inner = $paths[$name] ?? '';
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}

function render_product_card(array $p, string $headingTag = 'h3'): string
{
    ob_start();
    include ROOT . '/includes/product-card.php';
    return ob_get_clean();
}

function render_breadcrumbs(array $items): string
{
    // $items: [['label' => 'Sản phẩm', 'url' => '...'], ['label' => 'Tên trang']]
    $all  = array_merge([['label' => 'Trang chủ', 'url' => url()]], $items);
    $html = '<nav class="breadcrumbs" aria-label="Đường dẫn"><ol>';
    $ld   = [];
    foreach ($all as $i => $it) {
        $last = $i === count($all) - 1;
        $html .= '<li>' . ($last || empty($it['url'])
            ? '<span aria-current="page">' . e($it['label']) . '</span>'
            : '<a href="' . e($it['url']) . '">' . e($it['label']) . '</a>') . '</li>';
        $ld[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $it['label']]
            + (!empty($it['url']) ? ['item' => absolute_url($it['url'])] : []);
    }
    $html .= '</ol></nav>';
    $html .= '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $ld], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';
    return $html;
}

function render_pagination(int $page, int $pages, callable $urlFor): string
{
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="Phân trang"><ul>';
    if ($page > 1) {
        $html .= '<li><a href="' . e($urlFor($page - 1)) . '" rel="prev" aria-label="Trang trước">' . icon('left') . '</a></li>';
    }
    for ($i = 1; $i <= $pages; $i++) {
        $html .= $i === $page
            ? '<li><span aria-current="page">' . $i . '</span></li>'
            : '<li><a href="' . e($urlFor($i)) . '" aria-label="Trang ' . $i . '">' . $i . '</a></li>';
    }
    if ($page < $pages) {
        $html .= '<li><a href="' . e($urlFor($page + 1)) . '" rel="next" aria-label="Trang sau">' . icon('right') . '</a></li>';
    }
    return $html . '</ul></nav>';
}

/** Thay {SITE_NAME}, {HOTLINE}... trong nội dung chính sách */
function fill_placeholders(string $html): string
{
    return strtr($html, array_map('e', [
        '{SITE_NAME}' => cfg('site_name'),
        '{ADDRESS}'   => cfg('address'),
    ] + policy_numbers()) + [
        '{HOTLINE}' => '<a class="nowrap" href="' . e(tel_href(cfg('hotline'))) . '">' . e(cfg('hotline')) . '</a>',
        '{EMAIL}'   => '<a class="nowrap" href="mailto:' . e(cfg('email')) . '">' . e(cfg('email')) . '</a>',
    ]);
}

/** Các con số trong chính sách đặt cọc – lấy từ config.php để chính sách, phiếu và website luôn khớp nhau */
function policy_numbers(): array
{
    return [
        '{GIA_GIU_TU}'     => format_price((int) cfg('hold_min_price', 10000000)),
        '{PHAN_TRAM_COC}'  => (string) (int) cfg('hold_deposit', 10),
        '{PHUT_CHO_COC}'   => (string) (int) cfg('hold_pending_minutes', 60),
        '{GIO_GIU}'        => (string) (int) cfg('hold_hours', 24),
        '{GIO_GIA_HAN}'    => (string) (int) cfg('hold_extend_hours', 24),
        '{THANG_PHIEU_MUA}'=> (string) (int) cfg('deposit_credit_months', 6),
        '{NGAY_HOAN_TIEN}' => (string) (int) cfg('refund_days', 3),
        '{PHUT_CHO_CK}'    => (string) (int) cfg('order_pending_minutes', 60),
        '{PHIEN_BAN_COC}'  => (string) cfg('deposit_policy_version'),
    ];
}

/** % VAT cộng thêm khi đặt hàng (0 = giá đã gồm VAT – mặc định). Bật trong trang quản trị → Cài đặt → Thanh toán */
function vat_rate(): int
{
    return cfg('vat_mode', 'included') === 'added' ? max(0, min(20, (int) cfg('vat_rate', 10))) : 0;
}

function vat_amount(int $base): int
{
    return $base > 0 && vat_rate() > 0 ? (int) round($base * vat_rate() / 100) : 0;
}

/** Có nhận đơn trả tiền khi nhận hàng (COD) không – luôn bật nếu chưa có tài khoản nhận chuyển khoản */
function cod_enabled(): bool
{
    return (bool) cfg('cod_enabled', true) || empty(cfg('bank')['account']);
}

/** Logo (đổi được trong trang quản trị) */
function site_logo(): string
{
    return (string) cfg('logo_image') ?: 'logo.png';
}

/** Link QR chuyển khoản VietQR (ảnh được tạo bởi dịch vụ img.vietqr.io) */
function vietqr_url(int $amount, string $note): string
{
    $bank = cfg('bank', []);
    if (empty($bank['account']) || empty($bank['bank_id'])) {
        return '';
    }
    $q = ['addInfo' => $note];
    if (!empty($bank['holder'])) {
        $q['accountName'] = $bank['holder'];
    }
    if ($amount > 0) {
        $q['amount'] = $amount;
    }
    return 'https://img.vietqr.io/image/' . rawurlencode($bank['bank_id']) . '-' . rawurlencode(preg_replace('/\s+/', '', $bank['account']))
        . '-compact2.png?' . http_build_query($q);
}

function social_links(): array
{
    $out = [];
    foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'shopee' => 'Shopee'] as $k => $label) {
        if (cfg($k)) {
            $out[] = ['key' => $k === 'shopee' ? 'store' : $k, 'label' => $label, 'url' => cfg($k)];
        }
    }
    return $out;
}
