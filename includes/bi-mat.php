<?php
/**
 * KHO THÔNG TIN BÍ MẬT (mật khẩu email SMTP, khóa Cloudflare R2…)
 * ------------------------------------------------------------------
 * - Nhập / đổi trong Trang quản trị. Lưu ở data/quan-tri/bi-mat.json, mỗi giá trị được MÃ HÓA
 *   (XChaCha20-Poly1305, hoặc AES-256-GCM nếu máy chủ thiếu thư viện sodium).
 * - Khóa giải mã để RIÊNG với dữ liệu:
 *     + Trên VPS: tệp ghi trong biến môi trường VNP_SECRET_FILE (/etc/vienngocphuong/khoa-bi-mat – nằm ngoài thư mục website).
 *     + Trên hosting / máy chạy thử: data/quan-tri/.khoa-bi-mat (tự tạo lần đầu).
 *   Bản sao lưu .zip KHÔNG chứa khóa này → lộ file sao lưu cũng không đọc được mật khẩu.
 * - Chuyển sang máy chủ khác mà không mang theo khóa → các ô bí mật trống, chỉ cần nhập lại trong trang quản trị.
 */

function secret_store_file(): string
{
    return ROOT . '/data/quan-tri/bi-mat.json';
}

/** Khóa mã hóa 32 byte ('' nếu không đọc / tạo được) */
function secret_key(): string
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }
    $env = (string) getenv('VNP_SECRET_FILE');
    if ($env !== '') {
        $raw = is_readable($env) ? trim((string) file_get_contents($env)) : '';
    } else {
        $file = ROOT . '/data/quan-tri/.khoa-bi-mat';
        $raw  = is_file($file) ? trim((string) file_get_contents($file)) : '';
        if (strlen($raw) < 64) {
            if (!is_dir(dirname($file))) {
                @mkdir(dirname($file), 0755, true);
            }
            $raw = bin2hex(random_bytes(32));
            if (@file_put_contents($file, $raw, LOCK_EX) === false) {
                $raw = '';
            }
            @chmod($file, 0600);
        }
    }
    return $key = strlen($raw) >= 64 && ctype_xdigit(substr($raw, 0, 64)) ? hex2bin(substr($raw, 0, 64)) : '';
}

function secret_encrypt(string $plain, string $name): string
{
    $key = secret_key();
    if ($key === '') {
        return '';
    }
    if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        return 'x1:' . base64_encode($nonce . sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plain, $name, $nonce, $key));
    }
    $iv  = random_bytes(12);
    $tag = '';
    $enc = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $name);
    return $enc === false ? '' : 'g1:' . base64_encode($iv . $tag . $enc);
}

/** Giải mã; trả về null nếu sai khóa / dữ liệu hỏng */
function secret_decrypt(string $box, string $name): ?string
{
    $key = secret_key();
    $raw = base64_decode(substr($box, 3), true);
    if ($key === '' || $raw === false) {
        return null;
    }
    if (strncmp($box, 'x1:', 3) === 0 && function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
        $n   = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $out = strlen($raw) > $n ? sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(substr($raw, $n), $name, substr($raw, 0, $n), $key) : false;
        return $out === false ? null : $out;
    }
    if (strncmp($box, 'g1:', 3) === 0 && strlen($raw) > 28) {
        $out = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), $name);
        return $out === false ? null : $out;
    }
    return null;
}

/** Đọc toàn bộ ô bí mật (đọc lại từ đĩa mỗi lần gọi – đổi trong trang quản trị là có hiệu lực ngay) */
function secret_all(): array
{
    $file = secret_store_file();
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    $out  = [];
    foreach ((array) $data as $name => $box) {
        $val = is_string($box) ? secret_decrypt($box, (string) $name) : null;
        if ($val !== null) {
            $out[(string) $name] = $val;
        }
    }
    return $out;
}

function secret_get(string $name, string $default = ''): string
{
    return secret_all()[$name] ?? $default;
}

/** Ô bí mật có trong kho nhưng không giải mã được (đã đổi máy chủ / mất khóa) */
function secret_unreadable(): array
{
    $file = secret_store_file();
    $data = is_file($file) ? (array) json_decode((string) file_get_contents($file), true) : [];
    return array_values(array_diff(array_keys($data), array_keys(secret_all())));
}

/**
 * Ghi các ô bí mật: [tên => giá trị]; giá trị '' = xóa ô đó.
 * Trả về true nếu ghi được.
 */
function secret_set(array $values): bool
{
    if (secret_key() === '') {
        return false;
    }
    $file = secret_store_file();
    if (!is_dir(dirname($file))) {
        @mkdir(dirname($file), 0755, true);
    }
    $data = is_file($file) ? (array) json_decode((string) file_get_contents($file), true) : [];
    foreach ($values as $name => $val) {
        $val = (string) $val;
        if ($val === '') {
            unset($data[$name]);
            continue;
        }
        $box = secret_encrypt($val, (string) $name);
        if ($box === '') {
            return false;
        }
        $data[$name] = $box;
    }
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
        return false;
    }
    @chmod($tmp, 0600);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}
