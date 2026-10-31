<?php
/**
 * CLOUDFLARE R2 – LƯU ẢNH / VIDEO / BẢN SAO LƯU
 * ------------------------------------------------------------------
 * - Thông tin kết nối (Account ID, Access Key ID, Secret Access Key, Bucket) nhập trong
 *   Trang quản trị → Cài đặt → Lưu trữ Cloudflare R2. Lưu MÃ HÓA trong kho bí mật (includes/bi-mat.php)
 *   và được đọc lại mỗi lần tải tệp lên – đổi khóa là có hiệu lực ngay, không cần sửa code / VPS.
 * - Ảnh / video tải lên trang quản trị luôn được lưu trên máy chủ trước (bản dự phòng), rồi đưa lên R2.
 *   Danh sách tệp đã có trên R2: data/quan-tri/r2-tep.json.
 * - Bật "Khách xem ảnh từ R2" (cần địa chỉ công khai của bucket) → website lấy ảnh / video đã tải lên từ R2.
 *   Tệp chưa có trên R2 vẫn lấy từ máy chủ như cũ.
 * - Sao lưu dữ liệu lên R2: thư mục khó đoán sao-luu-<mã ngẫu nhiên>/ trong bucket.
 */

/** Thông tin kết nối – đọc lại từ kho bí mật mỗi lần gọi */
function r2_settings(): array
{
    $s = secret_all();
    return [
        'account' => strtolower(trim($s['r2_account_id'] ?? '')),
        'key'     => trim($s['r2_access_key_id'] ?? ''),
        'secret'  => trim($s['r2_secret_access_key'] ?? ''),
        'bucket'  => trim($s['r2_bucket'] ?? ''),
    ];
}

function r2_complete(array $s): bool
{
    return ($s['account'] ?? '') !== '' && ($s['key'] ?? '') !== '' && ($s['secret'] ?? '') !== '' && ($s['bucket'] ?? '') !== '';
}

function r2_ready(): bool
{
    return r2_complete(r2_settings());
}

/** Tự đưa ảnh / video mới tải lên vào R2 */
function r2_upload_on(): bool
{
    return (bool) cfg('r2_upload') && r2_ready();
}

/** Địa chỉ công khai của bucket (https://pub-….r2.dev hoặc tên miền riêng), không có / ở cuối */
function r2_public_base(): string
{
    return rtrim(trim((string) cfg('r2_public_url')), '/');
}

/** Khách xem ảnh / video đã tải lên từ R2 */
function r2_serve_on(): bool
{
    return (bool) cfg('r2_serve') && r2_public_base() !== '' && !STATIC_DEMO;
}

/** Máy chủ R2 của tài khoản (biến môi trường VNP_R2_ENDPOINT chỉ dùng khi kiểm thử với máy chủ S3 giả lập) */
function r2_endpoint(array $s): string
{
    $test = (string) getenv('VNP_R2_ENDPOINT');
    return $test !== '' ? rtrim($test, '/') : 'https://' . $s['account'] . '.r2.cloudflarestorage.com';
}

function r2_path_encode(string $path): string
{
    return implode('/', array_map('rawurlencode', explode('/', $path)));
}

/**
 * Chữ ký AWS Signature V4 (R2 dùng chuẩn S3). Trả về giá trị header Authorization.
 * $headers phải có host, x-amz-date, x-amz-content-sha256 (và các header khác muốn ký).
 */
function r2_sign(string $method, string $path, array $query, array $headers, string $payloadHash, string $accessKey, string $secret, string $amzDate, string $region = 'auto', string $service = 's3'): string
{
    $date = substr($amzDate, 0, 8);
    $h    = [];
    foreach ($headers as $k => $v) {
        $h[strtolower((string) $k)] = trim((string) preg_replace('/\s+/', ' ', (string) $v));
    }
    ksort($h, SORT_STRING);
    $canonHeaders = '';
    foreach ($h as $k => $v) {
        $canonHeaders .= $k . ':' . $v . "\n";
    }
    $signed = implode(';', array_keys($h));
    $q      = [];
    foreach ($query as $k => $v) {
        $q[rawurlencode((string) $k)] = rawurlencode((string) $v);
    }
    ksort($q, SORT_STRING);
    $canonQuery = implode('&', array_map(fn($k, $v) => $k . '=' . $v, array_keys($q), $q));
    $canonical  = implode("\n", [$method, r2_path_encode($path), $canonQuery, $canonHeaders, $signed, $payloadHash]);
    $scope      = $date . '/' . $region . '/' . $service . '/aws4_request';
    $toSign     = "AWS4-HMAC-SHA256\n" . $amzDate . "\n" . $scope . "\n" . hash('sha256', $canonical);
    $k = hash_hmac('sha256', $date, 'AWS4' . $secret, true);
    $k = hash_hmac('sha256', $region, $k, true);
    $k = hash_hmac('sha256', $service, $k, true);
    $k = hash_hmac('sha256', 'aws4_request', $k, true);
    return 'AWS4-HMAC-SHA256 Credential=' . $accessKey . '/' . $scope . ', SignedHeaders=' . $signed . ', Signature=' . hash_hmac('sha256', $toSign, $k);
}

/**
 * Gửi 1 yêu cầu tới R2. $opt: settings (thông tin kết nối – mặc định đọc từ kho bí mật), file (đường dẫn tệp gửi lên),
 * body (chuỗi gửi lên), headers (header ký thêm), query, timeout.
 * Trả về ['status' => mã HTTP, 'body' => nội dung, 'error' => lỗi kết nối].
 */
function r2_request(string $method, string $key, array $opt = []): array
{
    $s = $opt['settings'] ?? r2_settings();
    if (!r2_complete($s)) {
        return ['status' => 0, 'body' => '', 'error' => 'Chưa nhập đủ thông tin kết nối R2.'];
    }
    if (!function_exists('curl_init')) {
        return ['status' => 0, 'body' => '', 'error' => 'Máy chủ thiếu tiện ích curl của PHP.'];
    }
    $base  = parse_url(r2_endpoint($s));
    $host  = ($base['host'] ?? '') . (isset($base['port']) ? ':' . $base['port'] : '');
    $path  = '/' . $s['bucket'] . ($key !== '' ? '/' . ltrim($key, '/') : '');
    $query = $opt['query'] ?? [];
    $file  = $opt['file'] ?? null;
    $body  = (string) ($opt['body'] ?? '');
    $hash  = $file !== null ? hash_file('sha256', $file) : hash('sha256', $body);
    $amz   = gmdate('Ymd\THis\Z');
    $headers = ['host' => $host, 'x-amz-content-sha256' => $hash, 'x-amz-date' => $amz] + ($opt['headers'] ?? []);
    $auth  = r2_sign($method, $path, $query, $headers, $hash, $s['key'], $s['secret'], $amz);

    $qs = [];
    foreach ($query as $k => $v) {
        $qs[] = rawurlencode((string) $k) . '=' . rawurlencode((string) $v);
    }
    $ch  = curl_init(($base['scheme'] ?? 'https') . '://' . $host . r2_path_encode($path) . ($qs ? '?' . implode('&', $qs) : ''));
    $hdr = ['Authorization: ' . $auth, 'Expect:'];
    foreach ($headers as $k => $v) {
        if ($k !== 'host') {
            $hdr[] = $k . ': ' . $v;
        }
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $hdr,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => (int) ($opt['timeout'] ?? 300),
    ]);
    $fh = null;
    if ($method === 'HEAD') {
        curl_setopt($ch, CURLOPT_NOBODY, true);
    } elseif ($file !== null) {
        $fh = fopen($file, 'rb');
        curl_setopt_array($ch, [CURLOPT_UPLOAD => true, CURLOPT_INFILE => $fh, CURLOPT_INFILESIZE => filesize($file)]);
    } elseif ($method === 'PUT' || $method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = $resp === false ? curl_error($ch) : '';
    if ($fh) {
        fclose($fh);
    }
    return ['status' => $status, 'body' => is_string($resp) ? $resp : '', 'error' => $error];
}

/** Câu báo lỗi dễ hiểu từ kết quả r2_request() */
function r2_error_text(array $r, array $s): string
{
    if ($r['error'] !== '') {
        if ($r['status'] === 0 && preg_match('/resolve|handshake|SSL|certificate|connect/i', $r['error'])) {
            return 'Không kết nối được tới R2 – kiểm tra lại Account ID' . ($s['account'] !== '' ? ' (' . $s['account'] . ')' : '') . '.';
        }
        return 'Lỗi kết nối R2: ' . $r['error'];
    }
    preg_match('#<Code>([^<]+)</Code>#', $r['body'], $m);
    $code = $m[1] ?? '';
    return match ($code) {
        'InvalidAccessKeyId'    => 'Access Key ID không đúng (hoặc khóa đã bị xóa trên Cloudflare).',
        'SignatureDoesNotMatch' => 'Secret Access Key không đúng.',
        'NoSuchBucket'          => 'Không có bucket tên “' . $s['bucket'] . '” trong tài khoản này.',
        'AccessDenied'          => 'Khóa API không có quyền ghi vào bucket “' . $s['bucket'] . '” – tạo khóa với quyền “Object Read & Write”.',
        'InvalidBucketName'     => 'Tên bucket không hợp lệ.',
        'RequestTimeTooSkewed'  => 'Đồng hồ máy chủ bị lệch giờ so với Cloudflare.',
        default                 => 'R2 báo lỗi ' . $r['status'] . ($code !== '' ? ' (' . $code . ')' : '') . '.',
    };
}

function r2_content_type(string $file): string
{
    return [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
        'svg' => 'image/svg+xml', 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'zip' => 'application/zip',
        'txt' => 'text/plain; charset=utf-8', 'json' => 'application/json',
    ][strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
}

/**
 * Đưa 1 tệp lên R2. $rel: đường dẫn tính từ thư mục website (VD assets/img/uploads/…) hoặc đường dẫn tuyệt đối.
 * Trả về '' nếu thành công, hoặc câu báo lỗi.
 */
function r2_put_file(string $rel, ?string $key = null, ?array $s = null): string
{
    $abs = $rel !== '' && $rel[0] === '/' ? $rel : ROOT . '/' . $rel;
    if (!is_file($abs)) {
        return 'Không thấy tệp ' . basename($abs) . ' trên máy chủ.';
    }
    $s       = $s ?? r2_settings();
    $headers = ['content-type' => r2_content_type($abs)];
    if (preg_match('#^assets/(img|videos)/uploads/#', $rel)) {
        $headers['cache-control'] = 'public, max-age=31536000, immutable';   // tên tệp có mã ngẫu nhiên → không bao giờ đổi nội dung
    }
    $r = r2_request('PUT', $key ?? $rel, ['file' => $abs, 'settings' => $s, 'headers' => $headers]);
    return $r['status'] === 200 ? '' : r2_error_text($r, $s);
}

/* ------------------------------------------------------------------
 * Danh sách tệp đã có trên R2 & trạng thái
 * ------------------------------------------------------------------ */

function r2_manifest_file(): string
{
    return ROOT . '/data/quan-tri/r2-tep.json';
}

/** [đường dẫn tệp => thời điểm đưa lên R2] */
function r2_manifest(bool $reload = false): array
{
    global $R2_MANIFEST;
    if ($R2_MANIFEST === null || $reload) {
        $file        = r2_manifest_file();
        $data        = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        $R2_MANIFEST = is_array($data) ? $data : [];
    }
    return $R2_MANIFEST;
}

function r2_manifest_add(array $rels): void
{
    global $R2_MANIFEST;
    if (!$rels) {
        return;
    }
    $file = r2_manifest_file();
    if (!is_dir(dirname($file))) {
        @mkdir(dirname($file), 0755, true);
    }
    $fh = @fopen($file, 'c+');
    if (!$fh) {
        return;
    }
    flock($fh, LOCK_EX);
    $data = json_decode((string) stream_get_contents($fh), true);
    $data = is_array($data) ? $data : [];
    foreach ($rels as $rel) {
        $data[$rel] = time();
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    flock($fh, LOCK_UN);
    fclose($fh);
    $R2_MANIFEST = $data;
}

/** Trạng thái (lần kiểm tra / sao lưu gần nhất) */
function r2_status(): array
{
    $file = ROOT . '/data/quan-tri/r2-trang-thai.json';
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    return is_array($data) ? $data : [];
}

function r2_status_set(string $key, array $val): void
{
    $data       = r2_status();
    $data[$key] = $val + ['time' => time()];
    @file_put_contents(ROOT . '/data/quan-tri/r2-trang-thai.json', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

/* ------------------------------------------------------------------
 * Ảnh / video tải lên
 * ------------------------------------------------------------------ */

/**
 * Gọi ngay sau khi lưu ảnh / video tải lên: đưa lên R2 nếu đã bật.
 * $rel: đường dẫn tính từ thư mục website. Trả về '' (đã đưa lên / không bật) hoặc câu báo lỗi.
 */
function r2_mirror(string $rel): string
{
    if (!r2_upload_on()) {
        return '';
    }
    $err = r2_put_file($rel);
    if ($err === '') {
        r2_manifest_add([$rel]);
    } else {
        r2_status_set('upload_error', ['file' => $rel, 'error' => $err]);
    }
    return $err;
}

/** Địa chỉ ảnh / video trên R2 ('' nếu không dùng R2 hoặc tệp chưa có trên R2) */
function r2_url(string $rel): string
{
    if (!r2_serve_on() || !isset(r2_manifest()[$rel])) {
        return '';
    }
    return r2_public_base() . '/' . r2_path_encode($rel);
}

/** Toàn bộ ảnh / video đã tải lên trên máy chủ (đường dẫn tính từ thư mục website) */
function r2_local_uploads(): array
{
    $out = [];
    foreach (['assets/img/uploads', 'assets/videos/uploads'] as $dir) {
        $abs = ROOT . '/' . $dir;
        if (!is_dir($abs)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getFilename()[0] !== '.' && preg_match('/\.(jpe?g|png|webp|gif|mp4|webm)$/i', $f->getFilename())) {
                $out[] = $dir . '/' . str_replace('\\', '/', substr($f->getPathname(), strlen($abs) + 1));
            }
        }
    }
    sort($out);
    return $out;
}

/** Ảnh / video trên máy chủ chưa có trên R2 */
function r2_pending(): array
{
    $m = r2_manifest(true);
    return array_values(array_filter(r2_local_uploads(), fn($rel) => !isset($m[$rel])));
}

/** Đưa dần ảnh / video cũ lên R2 trong tối đa $seconds giây. Trả về ['done' => số tệp, 'left' => còn lại, 'error' => lỗi] */
function r2_sync_batch(int $seconds = 20): array
{
    $start = microtime(true);
    $s     = r2_settings();
    $done  = [];
    $error = '';
    foreach (r2_pending() as $rel) {
        if (microtime(true) - $start > $seconds) {
            break;
        }
        $error = r2_put_file($rel, null, $s);
        if ($error !== '') {
            break;
        }
        $done[] = $rel;
        if (count($done) % 25 === 0) {
            r2_manifest_add(array_slice($done, -25));     // ghi dần – lỡ bị ngắt giữa chừng cũng không phải đưa lại từ đầu
        }
    }
    r2_manifest_add($done);
    return ['done' => count($done), 'left' => count(r2_pending()), 'error' => $error];
}

/* ------------------------------------------------------------------
 * Kiểm tra kết nối & sao lưu
 * ------------------------------------------------------------------ */

/**
 * Kiểm tra: ghi thử 1 tệp nhỏ → (đọc qua địa chỉ công khai nếu có) → xóa.
 * Trả về ['ok' => bool, 'message' => câu báo, 'public' => null|bool].
 */
function r2_test(?array $s = null): array
{
    $s = $s ?? r2_settings();
    if (!r2_complete($s)) {
        return ['ok' => false, 'message' => 'Chưa nhập đủ 4 thông tin: Account ID, Access Key ID, Secret Access Key, tên bucket.', 'public' => null];
    }
    $key   = '_kiem-tra-ket-noi/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.txt';
    $token = 'vnp-' . bin2hex(random_bytes(8));
    $r     = r2_request('PUT', $key, ['settings' => $s, 'body' => $token, 'headers' => ['content-type' => 'text/plain; charset=utf-8'], 'timeout' => 30]);
    if ($r['status'] !== 200) {
        $msg = r2_error_text($r, $s);
        r2_status_set('test', ['ok' => false, 'message' => $msg]);
        return ['ok' => false, 'message' => $msg, 'public' => null];
    }
    $public = null;
    $base   = r2_public_base();
    if ($base !== '') {
        $ch = curl_init($base . '/' . r2_path_encode($key));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => true]);
        $got    = curl_exec($ch);
        $public = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 && trim((string) $got) === $token;
    }
    r2_request('DELETE', $key, ['settings' => $s, 'timeout' => 30]);
    $msg = 'Kết nối R2 thành công – ghi thử vào bucket “' . $s['bucket'] . '” được.';
    if ($public === true) {
        $msg .= ' Địa chỉ công khai hoạt động.';
    } elseif ($public === false) {
        $msg .= ' Nhưng địa chỉ công khai (' . $base . ') chưa mở được tệp – kiểm tra đã bật Public access / gắn tên miền cho bucket chưa.';
    }
    r2_status_set('test', ['ok' => true, 'message' => $msg, 'public' => $public]);
    return ['ok' => true, 'message' => $msg, 'public' => $public];
}

/** Thư mục chứa bản sao lưu trong bucket – có mã ngẫu nhiên để không ai đoán được đường dẫn */
function r2_backup_prefix(): string
{
    $t = secret_get('r2_backup_token');
    if ($t === '') {
        $t = bin2hex(random_bytes(12));
        secret_set(['r2_backup_token' => $t]);
    }
    return 'sao-luu-' . $t;
}

/** Đưa 1 file sao lưu .zip lên R2; $kind = 'tu-dong' (mỗi đêm, giữ 31 ngày) | 'thu-cong'. Trả về ['ok', 'message'] */
function r2_backup_upload(string $zip, string $kind = 'thu-cong'): array
{
    if (!r2_ready()) {
        return ['ok' => false, 'message' => 'Chưa kết nối R2.'];
    }
    // Bản tự động đặt tên theo ngày trong tháng → tự ghi đè sau 1 tháng, bucket không phình mãi
    $key = r2_backup_prefix() . '/' . ($kind === 'tu-dong' ? 'tu-dong-ngay-' . date('d') . '.zip' : 'thu-cong-' . date('Ymd-His') . '.zip');
    $err = r2_put_file($zip, $key);
    $res = $err === ''
        ? ['ok' => true, 'message' => 'Đã sao lưu lên R2 (' . number_format(filesize($zip) / 1048576, 1, ',', '.') . ' MB).', 'key' => $key]
        : ['ok' => false, 'message' => 'Sao lưu lên R2 thất bại: ' . $err];
    r2_status_set('backup', $res + ['kind' => $kind]);
    return $res;
}
