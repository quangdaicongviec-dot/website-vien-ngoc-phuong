<?php
/**
 * Hàm dùng chung cho TRANG QUẢN TRỊ (đăng nhập, quyền, bố cục, ô nhập liệu, tải ảnh, nhật ký).
 * Tài khoản, nhật ký… lưu trong data/quan-tri/ (đã chặn truy cập từ bên ngoài).
 */
defined('ADMIN') || exit;

const ADM_IDLE_SECONDS   = 8 * 3600;      // không thao tác 8 giờ → đăng nhập lại
const ADM_REMEMBER_DAYS  = 30;            // "Ghi nhớ đăng nhập trên thiết bị này"
const ADM_MAX_FAILS      = 5;             // sai mật khẩu 5 lần …
const ADM_LOCK_MINUTES   = 15;            // … khóa 15 phút

/* ------------------------------------------------------------------
 * Lưu trữ trong data/quan-tri/
 * ------------------------------------------------------------------ */

function adm_dir(): string
{
    $d = ROOT . '/data/quan-tri';
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
    }
    content_protect_dir($d);
    return $d;
}

function adm_read(string $name): array
{
    $f = adm_dir() . '/' . $name . '.json';
    $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
    return is_array($d) ? $d : [];
}

/** Đọc – sửa – ghi có khóa file */
function adm_write(string $name, callable $fn)
{
    $fh = @fopen(adm_dir() . '/' . $name . '.json', 'c+');
    if (!$fh) {
        return false;
    }
    flock($fh, LOCK_EX);
    $data = json_decode((string) stream_get_contents($fh), true);
    $data = is_array($data) ? $data : [];
    $res  = $fn($data);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    flock($fh, LOCK_UN);
    fclose($fh);
    return $res;
}

/* ------------------------------------------------------------------
 * Tài khoản & quyền
 * ------------------------------------------------------------------ */

function adm_roles(): array
{
    return [
        'quan-ly'  => 'Quản lý – toàn quyền',
        'ban-hang' => 'Nhân viên bán hàng – đơn hàng, giữ hàng, phiếu, lịch hẹn, liên hệ',
    ];
}

/** Trang nhân viên bán hàng được vào */
function adm_sales_pages(): array
{
    return ['tong-quan', 'don-hang', 'giu-hang', 'phieu', 'lich-hen', 'lien-he', 'tai-khoan', 'dang-xuat'];
}

function adm_users(): array
{
    return adm_read('tai-khoan');
}

function adm_can(string $page, ?array $u = null): bool
{
    $u = $u ?? adm_user();
    if (!$u) {
        return false;
    }
    return ($u['role'] ?? '') === 'quan-ly' || in_array($page, adm_sales_pages(), true);
}

function adm_is_owner(): bool
{
    return (adm_user()['role'] ?? '') === 'quan-ly';
}

/** Mã ghi nhớ đăng nhập: tên|hạn|phiên-bản-mật-khẩu|chữ-ký */
function adm_remember_token(string $username, int $pv, int $exp): string
{
    $payload = $username . '|' . $exp . '|' . $pv;
    return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . substr(hash_hmac('sha256', 'adm-remember|' . $payload, app_secret()), 0, 40);
}

function adm_cookie_opts(int $exp): array
{
    return ['expires' => $exp, 'path' => base_path() . '/quan-tri/', 'httponly' => true, 'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'];
}

/** Người đang đăng nhập (null nếu chưa) */
function adm_user(): ?array
{
    static $cached = false;
    if ($cached !== false) {
        return $cached;
    }
    $users = adm_users();
    $s     = $_SESSION['adm'] ?? null;

    // Không còn phiên nhưng có "ghi nhớ đăng nhập" hợp lệ → mở lại phiên
    if (!$s && !empty($_COOKIE['vnp_adm']) && strpos((string) $_COOKIE['vnp_adm'], '.') !== false) {
        [$b64, $sig] = explode('.', (string) $_COOKIE['vnp_adm'], 2);
        $payload = (string) base64_decode(strtr($b64, '-_', '+/'));
        $parts   = explode('|', $payload);
        if (count($parts) === 3 && hash_equals(substr(hash_hmac('sha256', 'adm-remember|' . $payload, app_secret()), 0, 40), $sig)
            && (int) $parts[1] > time() && isset($users[$parts[0]]) && (int) ($users[$parts[0]]['pv'] ?? 0) === (int) $parts[2]) {
            session_regenerate_id(true);
            $s = $_SESSION['adm'] = ['u' => $parts[0], 'pv' => (int) $parts[2], 't' => time(), 'a' => time()];
        }
    }
    if (!$s) {
        return $cached = null;
    }
    $u = $users[$s['u']] ?? null;
    if (!$u || (int) ($u['pv'] ?? 0) !== (int) ($s['pv'] ?? -1) || !empty($u['disabled'])) {
        unset($_SESSION['adm']);
        return $cached = null;
    }
    if (time() - (int) ($s['a'] ?? 0) > ADM_IDLE_SECONDS && empty($_COOKIE['vnp_adm'])) {
        unset($_SESSION['adm']);
        flash('Phiên đăng nhập đã hết hạn vì lâu không thao tác. Vui lòng đăng nhập lại.', 'error');
        return $cached = null;
    }
    $_SESSION['adm']['a'] = time();
    return $cached = ['username' => $s['u']] + $u;
}

function adm_login(string $username, bool $remember): void
{
    $u = adm_users()[$username];
    session_regenerate_id(true);
    $_SESSION['adm'] = ['u' => $username, 'pv' => (int) ($u['pv'] ?? 0), 't' => time(), 'a' => time()];
    unset($_SESSION['adm_csrf']);
    if ($remember) {
        $exp = time() + ADM_REMEMBER_DAYS * 86400;
        setcookie('vnp_adm', adm_remember_token($username, (int) ($u['pv'] ?? 0), $exp), adm_cookie_opts($exp));
    }
    adm_write('tai-khoan', function (array &$all) use ($username) {
        if (isset($all[$username])) {
            $all[$username]['last_login'] = time();
        }
    });
}

function adm_logout(): void
{
    unset($_SESSION['adm'], $_SESSION['adm_csrf']);
    setcookie('vnp_adm', '', adm_cookie_opts(time() - 3600));
    session_regenerate_id(true);
}

/** Đặt / đổi mật khẩu (tăng phiên bản → các thiết bị khác phải đăng nhập lại) */
function adm_set_password(string $username, string $password): void
{
    adm_write('tai-khoan', function (array &$all) use ($username, $password) {
        if (isset($all[$username])) {
            $all[$username]['hash'] = password_hash($password, PASSWORD_DEFAULT);
            $all[$username]['pv']   = (int) ($all[$username]['pv'] ?? 0) + 1;
        }
    });
}

function adm_password_problem(string $pw, string $again): ?string
{
    if (mb_strlen($pw) < 8) {
        return 'Mật khẩu cần ít nhất 8 ký tự.';
    }
    if ($pw !== $again) {
        return 'Hai lần nhập mật khẩu chưa khớp.';
    }
    if (preg_match('/^(\d)\1+$|^(12345678|password|matkhau|11111111)/i', $pw)) {
        return 'Mật khẩu quá dễ đoán – hãy chọn mật khẩu khác.';
    }
    return null;
}

/* ------------------------------------------------------------------
 * Chống dò mật khẩu
 * ------------------------------------------------------------------ */

function adm_fail_keys(string $username): array
{
    return ['ip:' . substr(hash('sha256', client_ip()), 0, 16), 'u:' . mb_strtolower($username)];
}

/** Số giây còn bị khóa (0 = được thử) */
function adm_login_locked(string $username): int
{
    $all  = adm_read('dang-nhap-sai');
    $wait = 0;
    foreach (adm_fail_keys($username) as $k) {
        $wait = max($wait, (int) ($all[$k]['until'] ?? 0) - time());
    }
    return max(0, $wait);
}

function adm_login_failed(string $username): void
{
    adm_write('dang-nhap-sai', function (array &$all) use ($username) {
        $all = array_filter($all, fn($x) => ($x['last'] ?? 0) > time() - 86400);
        foreach (adm_fail_keys($username) as $k) {
            $x = $all[$k] ?? ['n' => 0];
            if (($x['last'] ?? 0) < time() - ADM_LOCK_MINUTES * 60) {
                $x['n'] = 0;
            }
            $x['n']++;
            $x['last'] = time();
            // theo máy (IP): 5 lần; theo tên đăng nhập: 10 lần – để người lạ khó cố tình khóa tài khoản chủ cửa hàng
            if ($x['n'] >= (strpos($k, 'u:') === 0 ? ADM_MAX_FAILS * 2 : ADM_MAX_FAILS)) {
                $x['until'] = time() + ADM_LOCK_MINUTES * 60;
                $x['n']     = 0;
            }
            $all[$k] = $x;
        }
    });
}

function adm_login_ok(string $username): void
{
    adm_write('dang-nhap-sai', function (array &$all) use ($username) {
        foreach (adm_fail_keys($username) as $k) {
            unset($all[$k]);
        }
    });
}

/* ------------------------------------------------------------------
 * Bảo mật biểu mẫu & nhật ký
 * ------------------------------------------------------------------ */

function adm_csrf(): string
{
    if (empty($_SESSION['adm_csrf'])) {
        $_SESSION['adm_csrf'] = bin2hex(random_bytes(20));
    }
    return $_SESSION['adm_csrf'];
}

function adm_csrf_field(): string
{
    return '<input type="hidden" name="_t" value="' . e(adm_csrf()) . '">';
}

function adm_csrf_ok(): bool
{
    $t = (string) ($_POST['_t'] ?? $_SERVER['HTTP_X_CSRF'] ?? '');
    return $t !== '' && hash_equals(adm_csrf(), $t);
}

function adm_log(string $action, string $detail = ''): void
{
    $u = adm_user();
    adm_write('nhat-ky', function (array &$all) use ($u, $action, $detail) {
        $all[] = ['t' => time(), 'u' => $u['username'] ?? '', 'n' => $u['name'] ?? '', 'a' => $action, 'd' => mb_substr($detail, 0, 300),
                  'ip' => client_ip()];
        if (count($all) > 2000) {
            $all = array_slice($all, -2000);
        }
    });
}

/* ------------------------------------------------------------------
 * Đường dẫn & điều hướng
 * ------------------------------------------------------------------ */

function adm_url(string $page = '', array $q = []): string
{
    $q = array_filter(($page !== '' ? ['p' => $page] : []) + $q, fn($v) => $v !== '' && $v !== null);
    return base_path() . '/quan-tri/' . ($q ? '?' . http_build_query($q) : '');
}

function adm_back(string $page = '', array $q = [], ?string $msg = null, string $type = 'success'): void
{
    if ($msg !== null) {
        flash($msg, $type);
    }
    redirect(adm_url($page, $q));
}

/** Trả JSON cho các yêu cầu từ JavaScript (tải ảnh…) */
function adm_json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ------------------------------------------------------------------
 * Đọc dữ liệu biểu mẫu
 * ------------------------------------------------------------------ */

function in_str(string $key, int $max = 500, array $src = null): string
{
    $src = $src ?? $_POST;
    $v   = $src[$key] ?? '';
    $v   = is_scalar($v) ? (string) $v : '';
    $v   = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', str_replace("\r\n", "\n", $v));
    return mb_substr(trim($v), 0, $max);
}

function in_int(string $key, int $min = 0, int $max = PHP_INT_MAX, array $src = null): int
{
    return max($min, min($max, (int) preg_replace('/[^\d-]/', '', in_str($key, 30, $src))));
}

/** Số tiền: "16.800.000" / "16800000" / "16,8 triệu" → 16800000 */
function in_money(string $key, array $src = null): int
{
    $v = mb_strtolower(in_str($key, 40, $src));
    if (preg_match('/^([\d.,]+)\s*(tr|triệu|trieu)/u', $v, $m)) {
        $num = preg_match('/^\d{1,3}(\.\d{3})+$/', $m[1]) ? str_replace('.', '', $m[1]) : str_replace(',', '.', $m[1]);
        return (int) round((float) $num * 1000000);
    }
    return max(0, (int) preg_replace('/\D/', '', $v));
}

function in_bool(string $key, array $src = null): bool
{
    $src = $src ?? $_POST;
    return !empty($src[$key]) && $src[$key] !== '0';
}

/** Danh sách chuỗi (ô lặp lại name="x[]"), bỏ dòng trống */
function in_list(string $key, int $max = 500, array $src = null): array
{
    $src = $src ?? $_POST;
    $arr = is_array($src[$key] ?? null) ? $src[$key] : [];
    $out = [];
    foreach ($arr as $v) {
        $v = is_scalar($v) ? trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $v)) : '';
        if ($v !== '') {
            $out[] = mb_substr($v, 0, $max);
        }
    }
    return $out;
}

/** Đường dẫn ảnh/tệp trong assets – chỉ nhận đường dẫn tương đối an toàn hoặc link http(s) */
function clean_asset_path(string $p): string
{
    $p = trim($p);
    if ($p === '') {
        return '';
    }
    if (preg_match('#^https?://[^\s"\'<>]+$#i', $p)) {
        return $p;
    }
    $p = ltrim(str_replace('\\', '/', $p), '/');
    return preg_match('#^[A-Za-z0-9._/-]+$#', $p) && strpos($p, '..') === false ? $p : '';
}

/** Tạo mã đường dẫn (slug) từ tiếng Việt: "Vòng ngọc trắng" → "vong-ngoc-trang" */
function make_slug(string $s): string
{
    $s = vn_fold($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(substr($s, 0, 90), '-');
}

/** Slug chưa trùng: thêm -2, -3… */
function unique_slug(string $slug, array $taken): string
{
    $base = $slug !== '' ? $slug : 'muc-moi';
    $try  = $base;
    for ($i = 2; in_array($try, $taken, true); $i++) {
        $try = $base . '-' . $i;
    }
    return $try;
}

/**
 * Làm sạch HTML soạn trong trình soạn thảo (bài viết, chính sách…): chỉ giữ thẻ định dạng an toàn,
 * bỏ script, style, sự kiện onclick…, link javascript:.
 */
function clean_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    $allowed = [
        'p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'hr' => [], 'sub' => [], 'sup' => [], 'small' => [], 'mark' => [],
        'a' => ['href', 'target', 'rel', 'title'], 'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'figure' => [], 'figcaption' => [], 'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];
    $rename = ['b' => 'strong', 'i' => 'em', 'div' => 'p', 'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><body><div id="__root">' . $html . '</div></body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) {
        return e(strip_tags($html));
    }
    $walk = function (DOMNode $node) use (&$walk, $allowed, $rename, $doc) {
        for ($child = $node->firstChild; $child; $child = $next) {
            $next = $child->nextSibling;
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'noscript', 'template', 'link', 'meta'], true)) {
                $node->removeChild($child);
                continue;
            }
            $walk($child);
            if (isset($rename[$tag])) {
                $new = $doc->createElement($rename[$tag]);
                while ($child->firstChild) {
                    $new->appendChild($child->firstChild);
                }
                $node->replaceChild($new, $child);
                $child = $new;
                $tag   = $rename[$tag];
            }
            if (!isset($allowed[$tag])) {          // thẻ lạ (span, font…) → giữ chữ bên trong
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                $val  = trim($attr->value);
                $ok   = in_array($name, $allowed[$tag], true);
                if ($ok && in_array($name, ['href', 'src'], true)) {
                    $ok = (bool) preg_match('#^(https?://|mailto:|tel:|/|\#|[a-z0-9._-]+(/|$))#i', $val) && !preg_match('#^\s*(javascript|data|vbscript):#i', $val);
                }
                if (!$ok) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener');
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $n) {
        $out .= $doc->saveHTML($n);
    }
    // bỏ đoạn trống do trình soạn thảo tạo ra
    $out = preg_replace('#<p>(\s|&nbsp;|<br\s*/?>)*</p>#u', '', $out);
    return trim($out);
}

/* ------------------------------------------------------------------
 * Tải ảnh / video lên
 * ------------------------------------------------------------------ */

function upload_error_text(int $code): string
{
    return [
        UPLOAD_ERR_INI_SIZE   => 'Tệp quá lớn so với giới hạn của hosting (' . ini_get('upload_max_filesize') . '). Hãy chọn ảnh nhỏ hơn.',
        UPLOAD_ERR_FORM_SIZE  => 'Tệp quá lớn.',
        UPLOAD_ERR_PARTIAL    => 'Tải lên bị gián đoạn, vui lòng thử lại.',
        UPLOAD_ERR_NO_FILE    => 'Chưa chọn tệp.',
        UPLOAD_ERR_NO_TMP_DIR => 'Hosting thiếu thư mục tạm – liên hệ nhà cung cấp hosting.',
        UPLOAD_ERR_CANT_WRITE => 'Không ghi được tệp lên hosting.',
    ][$code] ?? 'Không tải lên được tệp.';
}

/** Thư mục lưu tệp tải lên (có .htaccess chặn chạy mã) */
function upload_dir(string $base, string $folder): array
{
    $folder = preg_replace('/[^a-z0-9-]/', '', $folder) ?: 'chung';
    $rel    = 'uploads/' . $folder . '/' . date('Y-m');
    $abs    = ROOT . '/assets/' . $base . '/' . $rel;
    if (!is_dir($abs)) {
        @mkdir($abs, 0755, true);
    }
    $guard = ROOT . '/assets/' . $base . '/uploads/.htaccess';
    if (!is_file($guard)) {
        @file_put_contents($guard, "# Chỉ phục vụ ảnh/video – không chạy mã\nOptions -ExecCGI\n<FilesMatch \"\\.(php\\d?|phtml|phar|pl|py|cgi|sh|html?|svg)$\">\n  Require all denied\n</FilesMatch>\n");
    }
    return [$rel, $abs];
}

/**
 * Lưu ảnh tải lên vào assets/img/uploads/<thư mục>/<năm-tháng>/.
 * Trả về ['path' => 'uploads/…', 'w' => …, 'h' => …] hoặc ['error' => '…'].
 */
function save_uploaded_image(array $file, string $folder, int $maxSide = 2400): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['error' => upload_error_text((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE))];
    }
    if (!is_uploaded_file($file['tmp_name']) || $file['size'] > 20 * 1024 * 1024) {
        return ['error' => 'Ảnh tối đa 20 MB.'];
    }
    $info = @getimagesize($file['tmp_name']);
    $ext  = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2] ?? 0] ?? '';
    if (!$info || $ext === '') {
        return ['error' => 'Chỉ nhận ảnh JPG, PNG, WEBP hoặc GIF.'];
    }
    [$rel, $abs] = upload_dir('img', $folder);
    $name = (make_slug(pathinfo((string) $file['name'], PATHINFO_FILENAME)) ?: 'anh') ;
    $name = substr($name, 0, 60) . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    $w = (int) $info[0];
    $h = (int) $info[1];

    // Ảnh quá lớn → thu nhỏ trên máy chủ (nếu hosting có GD); trình duyệt thường đã thu nhỏ trước khi gửi
    if (max($w, $h) > $maxSide && $ext !== 'gif' && function_exists('imagecreatetruecolor')) {
        $src = match ($ext) {
            'jpg'  => @imagecreatefromjpeg($file['tmp_name']),
            'png'  => @imagecreatefrompng($file['tmp_name']),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false,
            default => false,
        };
        if ($src) {
            $r  = $maxSide / max($w, $h);
            $nw = max(1, (int) round($w * $r));
            $nh = max(1, (int) round($h * $r));
            $dst = imagecreatetruecolor($nw, $nh);
            if ($ext !== 'jpg') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $ok = match ($ext) {
                'jpg'  => imagejpeg($dst, $abs . '/' . $name, 86),
                'png'  => imagepng($dst, $abs . '/' . $name, 7),
                'webp' => imagewebp($dst, $abs . '/' . $name, 86),
            };
            imagedestroy($src);
            imagedestroy($dst);
            if ($ok) {
                return ['path' => $rel . '/' . $name, 'w' => $nw, 'h' => $nh];
            }
        }
    }
    if (!@move_uploaded_file($file['tmp_name'], $abs . '/' . $name)) {
        return ['error' => 'Không lưu được ảnh lên hosting (kiểm tra quyền ghi thư mục assets/img).'];
    }
    @chmod($abs . '/' . $name, 0644);
    return ['path' => $rel . '/' . $name, 'w' => $w, 'h' => $h];
}

/** Video sản phẩm (MP4 / WEBM) → assets/videos/uploads/… ; trả về đường dẫn tính từ assets/ */
function save_uploaded_video(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['error' => upload_error_text((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE))];
    }
    $mime = function_exists('mime_content_type') ? (string) @mime_content_type($file['tmp_name']) : '';
    $ext  = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mp4'][$mime] ?? '';
    if (!is_uploaded_file($file['tmp_name']) || $ext === '') {
        return ['error' => 'Chỉ nhận video MP4 hoặc WEBM. Video dài nên đăng YouTube rồi dán link.'];
    }
    [$rel, $abs] = upload_dir('videos', 'san-pham');
    $name = (make_slug(pathinfo((string) $file['name'], PATHINFO_FILENAME)) ?: 'video') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
    if (!@move_uploaded_file($file['tmp_name'], $abs . '/' . $name)) {
        return ['error' => 'Không lưu được video lên hosting.'];
    }
    return ['path' => 'videos/' . $rel . '/' . $name];
}

/* ------------------------------------------------------------------
 * Ô nhập liệu (in ra HTML)
 * ------------------------------------------------------------------ */

function adm_id(string $name): string
{
    return 'f-' . trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
}

/** Khung chung: nhãn + ô + gợi ý */
function f_wrap(string $name, string $label, string $control, array $o = []): string
{
    $req  = !empty($o['required']) ? ' <span class="req" aria-hidden="true">*</span>' : '';
    $hint = !empty($o['hint']) ? '<p class="af__hint">' . $o['hint'] . '</p>' : '';
    $cls  = 'af' . (!empty($o['wide']) ? ' af--wide' : '') . (!empty($o['class']) ? ' ' . $o['class'] : '');
    $lab  = $label !== '' ? '<label class="af__label" for="' . e($o['id'] ?? adm_id($name)) . '">' . e($label) . $req . '</label>' : '';
    return '<div class="' . $cls . '"' . (!empty($o['show_if']) ? ' data-show-if="' . e($o['show_if']) . '"' : '') . '>' . $lab . $control . $hint . '</div>';
}

function f_attrs(array $o): string
{
    $a = '';
    foreach (['placeholder', 'maxlength', 'min', 'max', 'step', 'pattern', 'inputmode', 'autocomplete', 'list'] as $k) {
        if (isset($o[$k]) && $o[$k] !== '') {
            $a .= ' ' . $k . '="' . e((string) $o[$k]) . '"';
        }
    }
    if (!empty($o['required'])) {
        $a .= ' required';
    }
    if (!empty($o['readonly'])) {
        $a .= ' readonly';
    }
    if (!empty($o['attrs'])) {
        $a .= ' ' . $o['attrs'];
    }
    return $a;
}

function f_text(string $name, string $label, $value = '', array $o = []): string
{
    $type = $o['type'] ?? 'text';
    $ctl  = '<input class="ai" type="' . e($type) . '" id="' . e($o['id'] ?? adm_id($name)) . '" name="' . e($name) . '" value="' . e((string) $value) . '"' . f_attrs($o) . '>';
    if (!empty($o['suffix'])) {
        $ctl = '<div class="ai-group">' . $ctl . '<span class="ai-group__suffix">' . e($o['suffix']) . '</span></div>';
    }
    return f_wrap($name, $label, $ctl, $o);
}

/** Ô tiền: hiện dạng 16.800.000 cho dễ đọc */
function f_money(string $name, string $label, $value = 0, array $o = []): string
{
    $v = (int) $value > 0 ? number_format((int) $value, 0, ',', '.') : '';
    return f_text($name, $label, $v, $o + ['inputmode' => 'numeric', 'suffix' => '₫', 'attrs' => 'data-money', 'placeholder' => '0']);
}

function f_number(string $name, string $label, $value = 0, array $o = []): string
{
    return f_text($name, $label, (string) $value, $o + ['type' => 'number', 'inputmode' => 'numeric']);
}

function f_textarea(string $name, string $label, $value = '', array $o = []): string
{
    $rows = (int) ($o['rows'] ?? 4);
    $ctl  = '<textarea class="ai" id="' . e($o['id'] ?? adm_id($name)) . '" name="' . e($name) . '" rows="' . $rows . '"' . f_attrs($o)
        . (!empty($o['autosize']) ? ' data-autosize' : '') . '>' . e((string) $value) . '</textarea>';
    return f_wrap($name, $label, $ctl, $o);
}

function f_select(string $name, string $label, $value, array $options, array $o = []): string
{
    $ctl = '<select class="ai" id="' . e($o['id'] ?? adm_id($name)) . '" name="' . e($name) . '"' . f_attrs($o) . '>';
    foreach ($options as $k => $v) {
        $ctl .= '<option value="' . e((string) $k) . '"' . ((string) $k === (string) $value ? ' selected' : '') . '>' . e($v) . '</option>';
    }
    return f_wrap($name, $label, $ctl . '</select>', $o);
}

/** Công tắc bật/tắt */
function f_switch(string $name, string $label, bool $on, array $o = []): string
{
    $id  = $o['id'] ?? adm_id($name);
    $ctl = '<label class="sw" for="' . e($id) . '"><input type="hidden" name="' . e($name) . '" value="0"><input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"'
        . ($on ? ' checked' : '') . (!empty($o['attrs']) ? ' ' . $o['attrs'] : '') . '><span class="sw__track" aria-hidden="true"></span><span class="sw__text">' . e($label) . '</span></label>';
    return f_wrap($name, '', $ctl, $o + ['class' => 'af--switch']);
}

/** Nhóm ô đánh dấu (chọn nhiều) */
function f_checks(string $name, string $label, array $values, array $options, array $o = []): string
{
    $ctl = '<div class="checks" role="group" aria-label="' . e($label) . '"><input type="hidden" name="' . e($name) . '[]" value="">';
    foreach ($options as $k => $v) {
        $ctl .= '<label class="check"><input type="checkbox" name="' . e($name) . '[]" value="' . e((string) $k) . '"' . (in_array((string) $k, array_map('strval', $values), true) ? ' checked' : '') . '><span>' . e($v) . '</span></label>';
    }
    $ctl .= '</div>';
    return f_wrap($name, $label, $ctl, $o + ['id' => adm_id($name) . '-0']);
}

/** Ảnh đơn: xem trước + chọn ảnh từ máy (tự thu nhỏ trước khi tải lên) */
function f_image(string $name, string $label, string $value, array $o = []): string
{
    $folder = $o['folder'] ?? 'chung';
    $max    = (int) ($o['max'] ?? 2000);
    $id     = $o['id'] ?? adm_id($name);
    $src    = $value !== '' ? img_url($value) : '';
    $ctl = '<div class="imgf" data-img-field data-folder="' . e($folder) . '" data-max="' . $max . '">'
        . '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '" data-img-value>'
        . '<div class="imgf__preview' . ($src ? '' : ' is-empty') . '">' . ($src ? '<img src="' . e($src) . '" alt="">' : '<span>Chưa có ảnh</span>') . '</div>'
        . '<div class="imgf__side"><label class="btn btn--sm btn--outline imgf__pick" for="' . e($id) . '">' . adm_icon('upload') . 'Chọn ảnh từ máy'
        . '<input type="file" id="' . e($id) . '" accept="image/jpeg,image/png,image/webp,image/gif" data-img-input></label>'
        . '<button type="button" class="btn btn--sm btn--ghost" data-img-clear' . ($src ? '' : ' hidden') . '>' . adm_icon('trash') . 'Bỏ ảnh</button>'
        . '<p class="imgf__status" data-img-status aria-live="polite"></p></div></div>';
    return f_wrap($name, $label, $ctl, $o + ['id' => $id]);
}

/** Nhiều ảnh: kéo thả / mũi tên để đổi thứ tự, ảnh đầu tiên là ảnh đại diện */
function f_images(string $name, string $label, array $values, array $o = []): string
{
    $folder = $o['folder'] ?? 'chung';
    $max    = (int) ($o['max'] ?? 2000);
    $id     = $o['id'] ?? adm_id($name);
    $items  = '';
    foreach ($values as $v) {
        $items .= adm_image_tile($name, (string) $v);
    }
    $ctl = '<div class="imgs" data-imgs-field data-name="' . e($name) . '" data-folder="' . e($folder) . '" data-max="' . $max . '">'
        . '<input type="hidden" name="' . e($name) . '[]" value="">'
        . '<ul class="imgs__list" data-sortable>' . $items . '</ul>'
        . '<label class="imgs__add" for="' . e($id) . '">' . adm_icon('plus') . '<span>Thêm ảnh</span><input type="file" id="' . e($id) . '" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-imgs-input></label>'
        . '<p class="imgf__status" data-img-status aria-live="polite"></p>'
        . '<template data-tile-tools>' . adm_tile_tools() . '</template></div>';
    return f_wrap($name, $label, $ctl, $o + ['id' => $id]);
}

function adm_tile_tools(): string
{
    return '<button type="button" class="icon-mini" data-sort-up title="Lên trước">' . adm_icon('left') . '</button>'
        . '<button type="button" class="icon-mini" data-sort-down title="Ra sau">' . adm_icon('right') . '</button>'
        . '<button type="button" class="icon-mini icon-mini--danger" data-remove-item title="Bỏ ảnh này">' . adm_icon('trash') . '</button>';
}

function adm_image_tile(string $name, string $path): string
{
    return '<li class="imgs__item" data-sort-item><img src="' . e(img_url($path)) . '" alt="" loading="lazy">'
        . '<input type="hidden" name="' . e($name) . '[]" value="' . e($path) . '">'
        . '<span class="imgs__badge">Ảnh đại diện</span>'
        . '<span class="imgs__tools">' . adm_tile_tools() . '</span></li>';
}

/** Trình soạn thảo văn bản có định dạng (tiêu đề, đậm, danh sách, liên kết, ảnh…) */
function f_editor(string $name, string $label, string $html, array $o = []): string
{
    $id  = $o['id'] ?? adm_id($name);
    $btn = fn($cmd, $icon, $title, $extra = '') => '<button type="button" class="rte__btn" data-cmd="' . $cmd . '"' . $extra . ' title="' . e($title) . '" aria-label="' . e($title) . '">' . $icon . '</button>';
    $bar = '<div class="rte__bar" role="toolbar" aria-label="Định dạng">'
        . $btn('p', '<span class="rte__txt">Đoạn</span>', 'Đoạn văn thường')
        . $btn('h2', '<span class="rte__txt">H2</span>', 'Tiêu đề lớn')
        . $btn('h3', '<span class="rte__txt">H3</span>', 'Tiêu đề nhỏ')
        . '<span class="rte__sep"></span>'
        . $btn('bold', '<strong>B</strong>', 'In đậm')
        . $btn('italic', '<em>I</em>', 'In nghiêng')
        . $btn('ul', adm_icon('list'), 'Danh sách chấm')
        . $btn('ol', adm_icon('list-ol'), 'Danh sách số')
        . $btn('quote', adm_icon('quote'), 'Trích dẫn')
        . '<span class="rte__sep"></span>'
        . $btn('link', adm_icon('link'), 'Chèn liên kết')
        . $btn('unlink', adm_icon('unlink'), 'Bỏ liên kết')
        . $btn('image', adm_icon('image'), 'Chèn ảnh')
        . $btn('table', adm_icon('table'), 'Chèn bảng 3 cột')
        . $btn('clear', adm_icon('eraser'), 'Bỏ định dạng')
        . '<span class="rte__sep"></span>'
        . $btn('html', '<span class="rte__txt">&lt;/&gt;</span>', 'Xem / sửa mã HTML')
        . '</div>';
    $ctl = '<div class="rte" data-editor data-folder="' . e($o['folder'] ?? 'bai-viet') . '">' . $bar
        . '<div class="rte__area prose" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="' . e($id) . '-lb" data-editor-area></div>'
        . '<textarea class="ai rte__html" id="' . e($id) . '" name="' . e($name) . '" rows="14" hidden data-editor-source>' . e($html) . '</textarea>'
        . '<input type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden data-editor-file></div>';
    return '<div class="af af--wide"><p class="af__label" id="' . e($id) . '-lb">' . e($label) . '</p>' . $ctl
        . (!empty($o['hint']) ? '<p class="af__hint">' . $o['hint'] . '</p>' : '') . '</div>';
}

/** Danh sách dòng chữ có thể thêm/bớt/đổi thứ tự (điều khoản, khung giờ…) */
function f_list(string $name, string $label, array $values, array $o = []): string
{
    $multi = !empty($o['multiline']);
    $row = function (string $v) use ($name, $multi, $o) {
        $input = $multi
            ? '<textarea class="ai" name="' . e($name) . '[]" rows="2" data-autosize>' . e($v) . '</textarea>'
            : '<input class="ai" type="text" name="' . e($name) . '[]" value="' . e($v) . '"' . (isset($o['placeholder']) ? ' placeholder="' . e($o['placeholder']) . '"' : '') . '>';
        return '<li class="rows__item" data-sort-item>' . $input . '<span class="rows__tools">'
            . '<button type="button" class="icon-mini" data-sort-up title="Lên">' . adm_icon('up') . '</button>'
            . '<button type="button" class="icon-mini" data-sort-down title="Xuống">' . adm_icon('down') . '</button>'
            . '<button type="button" class="icon-mini icon-mini--danger" data-remove-item title="Xóa dòng">' . adm_icon('trash') . '</button></span></li>';
    };
    $items = implode('', array_map($row, $values ?: (($o['min_one'] ?? true) ? [''] : [])));
    $ctl = '<div class="rows" data-rows><ul class="rows__list" data-sortable>' . $items . '</ul>'
        . '<template data-row-template>' . $row('') . '</template>'
        . '<button type="button" class="btn btn--sm btn--ghost" data-add-row>' . adm_icon('plus') . e($o['add'] ?? 'Thêm dòng') . '</button></div>';
    return f_wrap($name, $label, $ctl, $o + ['id' => adm_id($name) . '-x']);
}

/** Bảng 2 cột "Tên – Giá trị" (thông tin giấy kiểm định…) */
function f_pairs(string $name, string $label, array $pairs, array $o = []): string
{
    $row = fn(string $k, string $v) => '<li class="rows__item rows__item--pair" data-sort-item>'
        . '<input class="ai" type="text" name="' . e($name) . '[k][]" value="' . e($k) . '" placeholder="' . e($o['k'] ?? 'Tên') . '">'
        . '<input class="ai" type="text" name="' . e($name) . '[v][]" value="' . e($v) . '" placeholder="' . e($o['v'] ?? 'Giá trị') . '">'
        . '<span class="rows__tools"><button type="button" class="icon-mini" data-sort-up title="Lên">' . adm_icon('up') . '</button>'
        . '<button type="button" class="icon-mini" data-sort-down title="Xuống">' . adm_icon('down') . '</button>'
        . '<button type="button" class="icon-mini icon-mini--danger" data-remove-item title="Xóa dòng">' . adm_icon('trash') . '</button></span></li>';
    $items = '';
    foreach ($pairs ?: ['' => ''] as $k => $v) {
        $items .= $row((string) $k, (string) $v);
    }
    $ctl = '<div class="rows" data-rows><ul class="rows__list" data-sortable>' . $items . '</ul>'
        . '<template data-row-template>' . $row('', '') . '</template>'
        . '<button type="button" class="btn btn--sm btn--ghost" data-add-row>' . adm_icon('plus') . e($o['add'] ?? 'Thêm dòng') . '</button></div>';
    return f_wrap($name, $label, $ctl, $o + ['id' => adm_id($name) . '-x']);
}

function in_pairs(string $key): array
{
    $k   = (array) ($_POST[$key]['k'] ?? []);
    $v   = (array) ($_POST[$key]['v'] ?? []);
    $out = [];
    foreach ($k as $i => $name) {
        $name = trim(mb_substr((string) $name, 0, 100));
        $val  = trim(mb_substr((string) ($v[$i] ?? ''), 0, 300));
        if ($name !== '') {
            $out[$name] = $val;
        }
    }
    return $out;
}

/* ------------------------------------------------------------------
 * Hiển thị
 * ------------------------------------------------------------------ */

/** Biểu tượng riêng của trang quản trị (bổ sung cho icon() của website) */
function adm_icon(string $name): string
{
    static $extra = [
        'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'box'       => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'tag'       => '<path d="M12.6 2.6A2 2 0 0 0 11.2 2H4a2 2 0 0 0-2 2v7.2a2 2 0 0 0 .6 1.4l8.7 8.7a2.4 2.4 0 0 0 3.4 0l6.6-6.6a2.4 2.4 0 0 0 0-3.4Z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
        'settings'  => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'upload'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
        'edit'      => '<path d="M12 20h9"/><path d="M16.38 3.62a1 1 0 0 1 3 3L7.37 18.64a2 2 0 0 1-.86.5l-2.87.84a.5.5 0 0 1-.62-.62l.84-2.87a2 2 0 0 1 .5-.86Z"/>',
        'external'  => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'list'      => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>',
        'list-ol'   => '<path d="M10 6h11"/><path d="M10 12h11"/><path d="M10 18h11"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/>',
        'quote'     => '<path d="M3 21c3 0 7-1 7-8V5c0-1.25-.76-2.02-2-2H4c-1.25 0-2 .75-2 1.97V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .01-1 1.03V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.76-2.02-2-2h-4c-1.25 0-2 .75-2 1.97V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>',
        'link'      => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'unlink'    => '<path d="m18.84 12.25 1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71"/><path d="m5.17 11.75-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71"/><path d="M8 2v3"/><path d="M2 8h3"/><path d="M16 22v-3"/><path d="M22 16h-3"/>',
        'table'     => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/><path d="M15 3v18"/>',
        'eraser'    => '<path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/>',
        'cloud'     => '<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>',
        'history'   => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'database'  => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'text'      => '<path d="M17 6.1H3"/><path d="M21 12.1H3"/><path d="M15.1 18H3"/>',
        'layout'    => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',
        'bank'      => '<path d="M3 22h18"/><path d="M6 18v-7"/><path d="M10 18v-7"/><path d="M14 18v-7"/><path d="M18 18v-7"/><path d="m12 2 8 5H4Z"/>',
        'bell'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'alert'     => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'info'      => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'save'      => '<path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/>',
        'drag'      => '<circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/>',
        'eye-off'   => '<path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><path d="m2 2 20 20"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>',
        'refresh'   => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
        'inbox'     => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
    ];
    if (isset($extra[$name])) {
        return '<svg class="icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $extra[$name] . '</svg>';
    }
    return icon($name);
}

/** Nhãn trạng thái màu */
function adm_badge(string $text, string $tone = 'muted'): string
{
    return '<span class="badge badge--' . e($tone) . '">' . e($text) . '</span>';
}

function adm_time(int $ts, bool $withYear = true): string
{
    if ($ts <= 0) {
        return '–';
    }
    return date($withYear ? 'H:i d/m/Y' : 'H:i d/m', $ts);
}

/** "3 phút trước", "hôm qua 14:20"… */
function adm_ago(int $ts): string
{
    $d = time() - $ts;
    if ($ts <= 0) {
        return '–';
    }
    if ($d < 60) {
        return 'vừa xong';
    }
    if ($d < 3600) {
        return floor($d / 60) . ' phút trước';
    }
    if ($d < 86400 && date('Ymd', $ts) === date('Ymd')) {
        return 'hôm nay ' . date('H:i', $ts);
    }
    if (date('Ymd', $ts) === date('Ymd', time() - 86400)) {
        return 'hôm qua ' . date('H:i', $ts);
    }
    return date('d/m/Y H:i', $ts);
}

/** Số việc đang chờ (hiện trên menu) */
function adm_counts(): array
{
    static $c = null;
    if ($c !== null) {
        return $c;
    }
    $orders = read_json_store('don-hang.json');
    $c = ['don-hang' => 0, 'giu-hang' => 0, 'lich-hen' => 0, 'lien-he' => 0];
    foreach ($orders as $o) {
        if (adm_order_todo($o) !== null && ($o['created'] ?? 0) > time() - 60 * 86400) {
            $c['don-hang']++;
        }
    }
    foreach (read_holds_raw() as $h) {
        if (($h['type'] ?? 'hold') === 'hold' && hold_status($h) !== 'expired') {
            $c['giu-hang']++;
        }
    }
    $c['lich-hen'] = count(array_filter(csv_records('dat-lich'), fn($r) => ($r['_status'] ?? '') === '' && ($r['_ts'] ?? 0) >= strtotime('today')));
    $c['lien-he']  = count(array_filter(csv_records('lien-he'), fn($r) => ($r['_status'] ?? '') === ''));
    return $c;
}

/* ------------------------------------------------------------------
 * Đọc các file CSV (lịch hẹn, liên hệ, giao dịch…) + trạng thái do cửa hàng đánh dấu
 * ------------------------------------------------------------------ */

/** Mọi dòng của các file data/don-hang/<tên>-YYYY-MM(.csv|-ghi-tam.csv), mới nhất trước */
function csv_records(string $name): array
{
    static $cache = [];
    if (isset($cache[$name])) {
        return $cache[$name];
    }
    $files  = glob(ROOT . '/data/don-hang/' . $name . '-[0-9][0-9][0-9][0-9]-[0-9][0-9]*.csv') ?: [];
    $status = adm_read('trang-thai-' . $name);
    $rows   = [];
    foreach ($files as $f) {
        $fh = @fopen($f, 'r');
        if (!$fh) {
            continue;
        }
        $head = null;
        while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            if ($head === null) {
                $r[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $r[0]);
                $head = $r;
                continue;
            }
            if (count($r) < 2) {
                continue;
            }
            $row = [];
            foreach ($head as $i => $k) {
                $row[$k] = preg_replace("/^'(?=[=+\-@])/", '', (string) ($r[$i] ?? ''));
            }
            $id  = substr(sha1(basename($f) . '|' . implode('|', $r)), 0, 16);
            $ts  = csv_time((string) reset($row));
            $rows[] = $row + ['_id' => $id, '_ts' => $ts, '_file' => basename($f), '_status' => $status[$id]['s'] ?? '', '_note' => $status[$id]['n'] ?? ''];
        }
        fclose($fh);
    }
    usort($rows, fn($a, $b) => $b['_ts'] <=> $a['_ts']);
    return $cache[$name] = $rows;
}

/** "21/10/2026 09:15" hoặc "2026-10-21 09:15:00" → timestamp */
function csv_time(string $s): int
{
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})(?:\s+(\d{1,2}):(\d{2}))?#', trim($s), $m)) {
        return mktime((int) ($m[4] ?? 0), (int) ($m[5] ?? 0), 0, (int) $m[2], (int) $m[1], (int) $m[3]) ?: 0;
    }
    return strtotime($s) ?: 0;
}

function csv_set_status(string $name, string $id, string $status, string $note = ''): void
{
    adm_write('trang-thai-' . $name, function (array &$all) use ($id, $status, $note) {
        $all[$id] = ['s' => $status, 'n' => $note, 't' => time(), 'u' => adm_user()['username'] ?? ''];
    });
}

/* ------------------------------------------------------------------
 * Bố cục trang
 * ------------------------------------------------------------------ */

/** Menu bên trái: [nhóm => [[trang, nhãn, biểu tượng, (tham số thêm)], …]] */
function adm_menu(): array
{
    return [
        ''           => [['tong-quan', 'Tổng quan', 'dashboard']],
        'Bán hàng'   => [['don-hang', 'Đơn hàng', 'bag'], ['giu-hang', 'Giữ hàng – đặt cọc', 'lock'], ['phieu', 'Phiếu xác nhận', 'receipt'],
                         ['lich-hen', 'Lịch hẹn xem ngọc', 'calendar'], ['lien-he', 'Tin nhắn liên hệ', 'inbox'], ['du-lieu', 'Giao dịch & đăng ký', 'chart']],
        'Sản phẩm'   => [['san-pham', 'Sản phẩm', 'gem'], ['danh-muc', 'Danh mục', 'tag'], ['kiem-dinh', 'Giấy kiểm định', 'cert'], ['bo-suu-tap', 'Bộ sưu tập quà tặng', 'gift']],
        'Nội dung'   => [['banner', 'Banner trang chủ', 'image'], ['bai-viet', 'Cẩm nang (bài viết)', 'text'], ['chinh-sach', 'Chính sách', 'shield'],
                         ['khach-hang', 'Khách đeo thật', 'heart'], ['van-ban', 'Chữ trên các trang', 'layout'], ['ngu-hanh', 'Ngũ hành & quà tặng', 'leaf']],
        'Cài đặt'    => [['cai-dat', 'Cài đặt cửa hàng', 'settings'], ['cai-dat', 'Lưu trữ Cloudflare R2', 'cloud', ['nhom' => 'luu-tru']],
                         ['tai-khoan', 'Tài khoản', 'users'], ['sao-luu', 'Sao lưu & khôi phục', 'database'], ['nhat-ky', 'Nhật ký thao tác', 'history']],
    ];
}

function adm_head(string $title, string $active, string $actions = '', array $crumbs = []): void
{
    $u      = adm_user();
    $counts = adm_counts();
    header('Content-Type: text/html; charset=UTF-8');
    ?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Quản trị <?= e(cfg('site_name')) ?></title>
<link rel="icon" href="<?= e(base_path()) ?>/favicon.ico" sizes="any">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="adm" data-csrf="<?= e(adm_csrf()) ?>" data-upload="<?= e(adm_url('tai-anh')) ?>" data-base="<?= e(base_path()) ?>">
<a class="skip-link" href="#adm-main">Bỏ qua menu</a>
<header class="adm-top">
  <button type="button" class="adm-top__menu icon-btn" data-nav-open aria-controls="adm-nav" aria-expanded="false"><?= icon('menu') ?><span class="sr-only">Mở menu</span></button>
  <a class="adm-top__brand" href="<?= e(adm_url()) ?>"><img src="<?= e(img_url('logo-emblem.png')) ?>" alt="" width="34" height="30"><span><strong><?= e(cfg('site_name')) ?></strong><small>Trang quản trị</small></span></a>
  <div class="adm-top__right">
    <a class="btn btn--sm btn--ghost adm-top__site" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= adm_icon('external') ?><span>Xem website</span></a>
    <details class="adm-user">
      <summary><span class="adm-user__avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['name'] ?? '?', 0, 1))) ?></span><span class="adm-user__name"><?= e($u['name'] ?? '') ?></span><?= icon('down') ?></summary>
      <div class="adm-user__menu">
        <p><strong><?= e($u['name'] ?? '') ?></strong><small><?= e(adm_roles()[$u['role'] ?? ''] ?? '') ?></small></p>
        <a href="<?= e(adm_url('tai-khoan')) ?>"><?= icon('lock') ?>Đổi mật khẩu</a>
        <form method="post" action="<?= e(adm_url('dang-xuat')) ?>"><?= adm_csrf_field() ?><button type="submit"><?= adm_icon('logout') ?>Đăng xuất</button></form>
      </div>
    </details>
  </div>
</header>
<div class="adm-shell">
  <nav class="adm-nav" id="adm-nav" aria-label="Menu quản trị">
    <div class="adm-nav__overlay" data-nav-close></div>
    <div class="adm-nav__panel">
      <?php foreach (adm_menu() as $group => $items):
          $items = array_filter($items, fn($it) => adm_can($it[0], $u));
          if (!$items) continue; ?>
        <?php if ($group !== ''): ?><p class="adm-nav__group"><?= e($group) ?></p><?php endif; ?>
        <ul>
          <?php foreach ($items as $it):
              [$page, $label, $ic] = $it;
              $q = $it[3] ?? [];
              // Mục có tham số (VD Cài đặt → Lưu trữ R2) sáng khi đúng nhóm; mục chung sáng khi không thuộc nhóm riêng nào
              $on = $page === $active && ($q ? array_intersect_assoc($q, $_GET) === $q : !in_array($_GET['nhom'] ?? '', ['luu-tru'], true)); ?>
            <li><a href="<?= e(adm_url($page, $q)) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= adm_icon($ic) ?><span><?= e($label) ?></span><?php if (!$q && !empty($counts[$page])): ?><b class="adm-nav__count"><?= (int) $counts[$page] ?></b><?php endif; ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    </div>
  </nav>
  <main class="adm-main" id="adm-main" tabindex="-1">
    <div class="adm-page-head">
      <div>
        <?php if ($crumbs): ?><p class="adm-crumbs"><?php foreach ($crumbs as $c): ?><a href="<?= e($c[1]) ?>"><?= e($c[0]) ?></a><span aria-hidden="true">/</span><?php endforeach; ?></p><?php endif; ?>
        <h1><?= e($title) ?></h1>
      </div>
      <?php if ($actions): ?><div class="adm-page-head__actions"><?= $actions ?></div><?php endif; ?>
    </div>
    <?php if ($f = flash()): ?><div class="adm-flash adm-flash--<?= e($f['type']) ?>" role="status"><?= adm_icon($f['type'] === 'error' ? 'alert' : 'check') ?><p><?= e($f['message']) ?></p><button type="button" class="icon-mini" data-dismiss aria-label="Đóng"><?= icon('close') ?></button></div><?php endif; ?>
<?php
}

function adm_foot(): void
{
    ?>
  </main>
</div>
<div class="adm-toast" aria-live="polite" data-toast></div>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
<?php
}

/** Trang đơn giản (đăng nhập, cài đặt lần đầu) */
function adm_bare_head(string $title): void
{
    header('Content-Type: text/html; charset=UTF-8');
    ?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · <?= e(cfg('site_name')) ?></title>
<link rel="icon" href="<?= e(base_path()) ?>/favicon.ico" sizes="any">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="adm adm--bare">
<main class="adm-auth">
  <div class="adm-auth__card">
    <img class="adm-auth__logo" src="<?= e(img_url('logo.png')) ?>" alt="<?= e(cfg('site_name')) ?>" width="183" height="160">
<?php
}

function adm_bare_foot(): void
{
    ?>
  </div>
  <p class="adm-auth__back"><a href="<?= e(url()) ?>"><?= icon('left') ?>Về trang chủ website</a></p>
</main>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
<?php
}

/** Hộp thông báo trong trang */
function adm_notice(string $html, string $tone = 'info'): string
{
    return '<div class="adm-note adm-note--' . e($tone) . '">' . adm_icon($tone === 'warn' ? 'alert' : ($tone === 'ok' ? 'check' : 'info')) . '<div>' . $html . '</div></div>';
}

/** Thanh nút lưu dính dưới đáy màn hình */
function adm_savebar(string $label = 'Lưu thay đổi', string $extra = ''): string
{
    return '<div class="adm-savebar"><div class="adm-savebar__inner"><span class="adm-savebar__hint" data-dirty-hint>Chưa có thay đổi</span>' . $extra
        . '<button type="submit" class="btn btn--primary" data-submit>' . adm_icon('save') . e($label) . '</button></div></div>';
}

/** Ô tìm kiếm + lọc nhanh trên danh sách (lọc ngay trên trình duyệt) */
function adm_filterbar(string $placeholder, array $chips = [], string $active = ''): string
{
    $html = '<div class="adm-filter"><label class="adm-filter__search"><span class="sr-only">Tìm</span>' . icon('search')
        . '<input type="search" placeholder="' . e($placeholder) . '" data-filter-input autocomplete="off"></label>';
    if ($chips) {
        $html .= '<div class="adm-chips" role="group" aria-label="Lọc">';
        foreach ($chips as $k => $c) {
            $html .= '<a class="adm-chip" href="' . e($c[1]) . '"' . ((string) $k === $active ? ' aria-current="true"' : '') . '>' . e($c[0])
                . (isset($c[2]) ? ' <b>' . (int) $c[2] . '</b>' : '') . '</a>';
        }
        $html .= '</div>';
    }
    return $html . '</div>';
}

/* ------------------------------------------------------------------
 * Đơn hàng – trạng thái hiển thị trong trang quản trị
 * ------------------------------------------------------------------ */

/** [nhãn, màu] cho một đơn: gộp thanh toán + giao hàng */
function adm_order_status(array $o): array
{
    $st = order_state($o);
    $fu = (string) ($o['fulfillment'] ?? '');
    if ($st === 'cancelled') {
        return ['Đã hủy', 'muted'];
    }
    if ($fu === 'hoan-tat') {
        return ['Hoàn tất', 'ok'];
    }
    if ($fu === 'dang-giao') {
        return ['Đang giao', 'info'];
    }
    return match ($st) {
        'cod'     => empty($o['confirmed_at']) ? ['COD – chờ gọi xác nhận', 'warn'] : ['COD – đã xác nhận, chờ giao', 'gold'],
        'pending' => ['Chờ chuyển khoản', 'warn'],
        'partial' => ['Chuyển thiếu', 'danger'],
        'paid'    => ['Đã thanh toán, chờ giao', 'gold'],
        'expired' => ['Hết giờ giữ – chưa có tiền', 'muted'],
        default   => [$st, 'muted'],
    };
}

/** Đơn cần cửa hàng làm gì tiếp theo (null nếu không cần) */
function adm_order_todo(array $o): ?string
{
    $st = order_state($o);
    $fu = (string) ($o['fulfillment'] ?? '');
    if ($st === 'cancelled' || $fu === 'hoan-tat') {
        return null;
    }
    if ($st === 'cod' && empty($o['confirmed_at'])) {
        return 'Gọi khách xác nhận đơn';
    }
    if ($st === 'partial') {
        return 'Khách chuyển thiếu – liên hệ khách';
    }
    if ($st === 'paid' || ($st === 'cod' && !empty($o['confirmed_at']))) {
        return $fu === 'dang-giao' ? 'Đang giao – bấm Hoàn tất khi khách nhận' : 'Đóng gói & giao hàng';
    }
    return null;
}

/** Doanh thu được tính: đơn đã thanh toán hoặc COD đã xác nhận (không tính đơn hủy) */
function adm_order_counts_revenue(array $o): bool
{
    $st = order_state($o);
    return $st === 'paid' || ($st === 'cod' && (!empty($o['confirmed_at']) || ($o['fulfillment'] ?? '') === 'hoan-tat'));
}

/* ------------------------------------------------------------------
 * Danh sách từ file CSV có đánh dấu trạng thái (lịch hẹn, liên hệ)
 * ------------------------------------------------------------------ */

/** Xử lý đổi trạng thái 1 dòng CSV rồi quay lại trang */
function csv_status_post(string $name, string $page, array $statuses): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $id = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['id'] ?? ''));
    $st = (string) ($_POST['trang-thai'] ?? '');
    if ($id !== '' && isset($statuses[$st])) {
        csv_set_status($name, $id, $st, in_str('ghi-chu', 500));
        adm_log('Cập nhật ' . $page, $statuses[$st]);
        flash('Đã cập nhật: ' . $statuses[$st] . '.');
    }
    redirect(adm_url($page, array_filter(['loc' => (string) ($_GET['loc'] ?? '')])));
}

/** Ô đổi trạng thái gọn trên mỗi dòng */
function csv_status_form(string $page, array $row, array $statuses): string
{
    $html = '<form method="post" action="' . e(adm_url($page, array_filter(['loc' => (string) ($_GET['loc'] ?? '')]))) . '" class="csv-status" data-autosubmit>'
        . adm_csrf_field() . '<input type="hidden" name="id" value="' . e($row['_id']) . '">'
        . '<label class="sr-only" for="st-' . e($row['_id']) . '">Trạng thái</label><select class="ai" id="st-' . e($row['_id']) . '" name="trang-thai">';
    foreach ($statuses as $k => $v) {
        $html .= '<option value="' . e($k) . '"' . ($k === $row['_status'] ? ' selected' : '') . '>' . e($v) . '</option>';
    }
    return $html . '</select><noscript><button type="submit" class="btn btn--sm">Lưu</button></noscript></form>';
}

/** Tải tệp CSV trong data/don-hang/ (chỉ đúng mẫu tên tệp) */
function csv_download(string $file): void
{
    $file = basename($file);
    $path = ROOT . '/data/don-hang/' . $file;
    if (!preg_match('/^[a-z-]+-\d{4}-\d{2}(-ghi-tam)?\.csv$/', $file) || !is_file($path)) {
        http_response_code(404);
        exit('Không tìm thấy tệp.');
    }
    adm_log('Tải tệp', $file);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

/** Danh sách tệp CSV theo tháng của một loại */
function csv_files(string $name): array
{
    $files = glob(ROOT . '/data/don-hang/' . $name . '-[0-9][0-9][0-9][0-9]-[0-9][0-9]*.csv') ?: [];
    rsort($files);
    return array_map('basename', $files);
}

/* ------------------------------------------------------------------
 * Danh sách có khóa (danh mục, giấy kiểm định, bộ sưu tập…)
 * ------------------------------------------------------------------ */

/** Đổi tên khóa mà giữ nguyên thứ tự */
function keyed_rename(array $arr, string $old, string $new, array $value): array
{
    $out = [];
    foreach ($arr as $k => $v) {
        $out[$k === $old ? $new : $k] = $k === $old ? $value : $v;
    }
    if (!array_key_exists($old, $arr)) {
        $out[$new] = $value;
    }
    return $out;
}

/** Sắp xếp lại theo danh sách khóa */
function keyed_reorder(array $arr, array $keys): array
{
    $out = [];
    foreach ($keys as $k) {
        if (array_key_exists($k, $arr)) {
            $out[$k] = $arr[$k];
        }
    }
    return $out + $arr;
}

/** Sửa hàng loạt sản phẩm (đổi mã danh mục / giấy kiểm định / dịp…) */
function products_map(callable $fn): void
{
    global $PRODUCTS;
    $all = content_load('san-pham');
    $new = array_map($fn, $all);
    if ($new !== $all) {
        content_save('san-pham', $new);
        $PRODUCTS = $new;
    }
}

/** Thanh sắp xếp + danh sách dòng (dùng cho trang danh sách có thứ tự) */
function adm_sort_row(string $key, string $thumb, string $title, string $sub, string $editUrl, string $extra = ''): string
{
    return '<li class="sort-row" data-sort-item data-filter-item><span class="sort-row__grip">' . adm_icon('drag') . '</span>'
        . ($thumb !== '' ? '<img class="tbl__thumb" src="' . e($thumb) . '" alt="" loading="lazy">' : '')
        . '<span class="sort-row__main"><a class="tbl__main" href="' . e($editUrl) . '">' . e($title) . '</a><span class="tbl__sub">' . $sub . '</span></span>'
        . '<input type="hidden" name="thu-tu[]" value="' . e($key) . '">' . $extra
        . '<span class="rows__tools"><button type="button" class="icon-mini" data-sort-up title="Lên">' . adm_icon('up') . '</button><button type="button" class="icon-mini" data-sort-down title="Xuống">' . adm_icon('down') . '</button>'
        . '<a class="icon-mini" href="' . e($editUrl) . '" title="Sửa">' . adm_icon('edit') . '</a></span></li>';
}
