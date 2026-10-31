<?php
/**
 * Cài đặt cửa hàng – ghi đè config.php (lưu trong data/noi-dung/cai-dat.json).
 * Mỗi nhóm một trang con: ?p=cai-dat&nhom=…
 */
defined('ADMIN') || exit;

$banks = require ROOT . '/includes/ngan-hang.php';
$groups = [
    'cua-hang'   => ['Cửa hàng & pháp lý', 'store'],
    'thanh-toan' => ['Thanh toán & ngân hàng', 'bank'],
    'giao-hang'  => ['Giao hàng & quà tặng', 'truck'],
    'giu-hang'   => ['Giữ hàng & đơn hàng', 'lock'],
    'phieu'      => ['Phiếu xác nhận', 'receipt'],
    'thong-bao'  => ['Thông báo đơn mới', 'bell'],
    'dat-lich'   => ['Đặt lịch xem ngọc', 'calendar'],
    'tiep-thi'   => ['Mạng xã hội & quảng cáo', 'chart'],
    'luu-tru'    => ['Lưu trữ Cloudflare R2', 'cloud'],
    'ky-thuat'   => ['Tên miền & kỹ thuật', 'settings'],
];
$g = isset($groups[$_GET['nhom'] ?? '']) ? (string) $_GET['nhom'] : 'cua-hang';

/** Ô đơn giản: [mã cấu hình, kiểu, nhãn, tùy chọn] */
$fields = [
    'cua-hang' => [
        ['site_name', 'text', 'Tên cửa hàng', ['required' => true]],
        ['tagline', 'text', 'Khẩu hiệu ngắn', ['hint' => 'Hiện sau tên trên tab trình duyệt trang chủ.']],
        ['description', 'textarea', 'Giới thiệu ngắn (cho Google, chia sẻ Facebook)', ['wide' => true, 'rows' => 3, 'maxlength' => 300]],
        ['logo_image', 'image', 'Logo (đầu trang, chân trang)', ['folder' => 'thuong-hieu', 'max' => 800, 'hint' => 'Để trống = dùng logo hiện tại. Nên PNG nền trong suốt.']],
        ['og_image', 'image', 'Ảnh khi chia sẻ link lên Facebook / Zalo', ['folder' => 'thuong-hieu', 'max' => 1600, 'hint' => 'Nên 1200×630. Trang sản phẩm dùng ảnh sản phẩm.']],
        ['_sec', 'Liên hệ'],
        ['hotline', 'text', 'Hotline (hiển thị)', ['placeholder' => '0912 345 678']],
        ['zalo', 'text', 'Số Zalo', ['placeholder' => '0912345678', 'hint' => 'Viết liền. Nút Zalo nổi và nút “Nhắn Zalo” dùng số này.']],
        ['email', 'text', 'Email liên hệ', ['type' => 'email']],
        ['working_hours', 'text', 'Giờ mở cửa', ['placeholder' => '8:00 – 21:00, Thứ 2 – Chủ nhật']],
        ['address', 'text', 'Địa chỉ cửa hàng', ['wide' => true]],
        ['map_embed', 'text', 'Link bản đồ Google Maps (nhúng)', ['wide' => true, 'hint' => 'Google Maps → Chia sẻ → Nhúng bản đồ → sao chép phần link trong src="…" (bắt đầu bằng https://www.google.com/maps/embed?…).']],
        ['_sec', 'Thông tin pháp lý', 'In ở chân trang và trên phiếu xác nhận – ghi đúng như giấy đăng ký.'],
        ['company_name', 'text', 'Tên hộ kinh doanh / công ty', ['wide' => true]],
        ['tax_code', 'text', 'Mã số thuế / số định danh', ['hint' => 'Hộ kinh doanh: số định danh cá nhân (12 số) của chủ hộ. Công ty: mã số doanh nghiệp (10 số).']],
        ['license_info', 'text', 'Giấy đăng ký kinh doanh', ['placeholder' => 'GCN ĐKHKD số … cấp ngày … tại …']],
        ['bct_url', 'text', 'Link “Đã thông báo Bộ Công Thương” (online.gov.vn)', ['wide' => true, 'hint' => 'Sau khi thông báo website TMĐT, dán link logo xác nhận do online.gov.vn cấp – logo sẽ hiện ở chân trang.']],
    ],
    'giu-hang' => [
        ['_sec', 'Giữ hàng – đặt cọc', 'Áp dụng cho nút “Giữ hàng” trên trang sản phẩm. Chính sách đặt cọc tự cập nhật theo các con số này.'],
        ['hold_min_price', 'money', 'Hiện nút “Giữ hàng” cho món từ giá', ['hint' => 'Để 0 để tắt tính năng giữ hàng.']],
        ['hold_deposit', 'number', 'Tiền cọc (% giá sản phẩm)', ['min' => 1, 'max' => 100, 'suffix' => '%']],
        ['hold_pending_minutes', 'number', 'Thời gian chờ khách chuyển cọc', ['min' => 5, 'max' => 1440, 'suffix' => 'phút']],
        ['hold_hours', 'number', 'Giữ hàng sau khi nhận cọc', ['min' => 1, 'max' => 720, 'suffix' => 'giờ']],
        ['hold_extend_hours', 'number', 'Gia hạn thêm (1 lần)', ['min' => 1, 'max' => 720, 'suffix' => 'giờ']],
        ['deposit_credit_months', 'number', 'Phiếu mua hàng (khi khách đổi ý) dùng trong', ['min' => 1, 'max' => 36, 'suffix' => 'tháng']],
        ['refund_days', 'number', 'Hoàn tiền trong', ['min' => 1, 'max' => 30, 'suffix' => 'ngày làm việc']],
        ['deposit_policy_version', 'text', 'Phiên bản chính sách đặt cọc', ['hint' => 'Tăng số khi sửa nội dung chính sách (in trên phiếu cọc).']],
        ['_sec', 'Đơn hàng có món độc bản'],
        ['order_pending_minutes', 'number', 'Giữ món chờ khách chuyển khoản', ['min' => 5, 'max' => 1440, 'suffix' => 'phút']],
        ['order_cod_hours', 'number', 'Giữ món chờ gọi xác nhận đơn COD', ['min' => 1, 'max' => 168, 'suffix' => 'giờ']],
        ['order_paid_hold_days', 'number', 'Đã thanh toán / xác nhận → giữ món tới khi giao', ['min' => 1, 'max' => 60, 'suffix' => 'ngày', 'hint' => 'Bấm “Hoàn tất” ở đơn hàng để chuyển món sang “Đã có chủ”.']],
        ['order_keep_days', 'number', 'Lưu đơn hàng để khách tra cứu', ['min' => 30, 'max' => 3650, 'suffix' => 'ngày', 'hint' => 'Phiếu xác nhận luôn được lưu lâu dài.']],
    ],
    'phieu' => [
        ['receipt_note', 'textarea', 'Dòng ghi chú cuối phiếu', ['wide' => true, 'rows' => 2]],
    ],
    'thong-bao' => [
        ['_sec', 'Telegram (miễn phí, báo ngay trên điện thoại)'],
        ['telegram_bot_token', 'secret', 'Mã bot Telegram (Bot token)', ['wide' => true, 'placeholder' => '123456789:AA…', 'hint' => 'Mở Telegram → tìm <strong>@BotFather</strong> → gõ /newbot → đặt tên → BotFather gửi lại mã dạng <code>123456789:AAE…</code>.']],
        ['telegram_chat_id', 'text', 'Mã nhóm / người nhận (Chat ID)', ['hint' => 'Nhắn 1 tin bất kỳ cho bot (hoặc thêm bot vào nhóm của cửa hàng), rồi bấm “Tìm mã nhận tin” bên dưới.']],
        ['_sec', 'Email', 'Website gửi email xác nhận đơn cho khách và báo đơn mới cho cửa hàng.'],
        ['order_email', 'text', 'Email nhận thông báo đơn hàng', ['type' => 'email']],
        ['mail_from', 'text', 'Email gửi đi', ['type' => 'email', 'hint' => 'Dùng Gmail thì điền đúng địa chỉ Gmail đăng nhập SMTP bên dưới. Dùng email tên miền: VD no-reply@vienngocphuong.com.']],
        ['_sec', 'Máy chủ gửi thư (SMTP)', 'Website chạy trên VPS cần ô này mới gửi được email. Để trống “Máy chủ SMTP” = dùng chức năng gửi thư có sẵn của hosting.'],
        ['smtp_host', 'text', 'Máy chủ SMTP', ['placeholder' => 'smtp.gmail.com', 'attrs' => 'spellcheck="false" autocapitalize="off"']],
        ['smtp_port', 'number', 'Cổng', ['min' => 1, 'max' => 65535, 'hint' => 'Gmail: 587 (TLS) hoặc 465 (SSL).']],
        ['smtp_secure', 'select', 'Mã hóa', ['options' => ['tls' => 'TLS – thường dùng cổng 587', 'ssl' => 'SSL – thường dùng cổng 465', 'none' => 'Không mã hóa (không khuyên dùng)']]],
        ['smtp_user', 'text', 'Tên đăng nhập SMTP', ['placeholder' => 'ten@gmail.com', 'autocomplete' => 'off', 'attrs' => 'spellcheck="false" autocapitalize="off"']],
        ['smtp_pass', 'vault', 'Mật khẩu SMTP', ['wide' => true, 'hint' => 'Gmail: bật Xác minh 2 bước → vào <strong>myaccount.google.com/apppasswords</strong> → tạo “Mật khẩu ứng dụng” (16 ký tự) và dán vào đây – không dùng mật khẩu Gmail thường. Mật khẩu được lưu mã hóa.']],
    ],
    'luu-tru' => [
        ['r2_account_id', 'vault_text', 'Account ID', ['placeholder' => '32 ký tự chữ và số', 'autocomplete' => 'off', 'attrs' => 'spellcheck="false" autocapitalize="off"', 'required' => true]],
        ['r2_bucket', 'vault_text', 'Tên bucket', ['placeholder' => 'VD: vienngocphuong', 'autocomplete' => 'off', 'attrs' => 'spellcheck="false" autocapitalize="off"', 'required' => true]],
        ['r2_access_key_id', 'vault_text', 'Access Key ID', ['wide' => true, 'autocomplete' => 'off', 'attrs' => 'spellcheck="false" autocapitalize="off"', 'required' => true]],
        ['r2_secret_access_key', 'vault', 'Secret Access Key', ['wide' => true, 'required' => true]],
        ['r2_public_url', 'text', 'Địa chỉ công khai của bucket (không bắt buộc)', ['wide' => true, 'placeholder' => 'https://pub-xxxxxxxx.r2.dev  hoặc  https://cdn.vienngocphuong.com', 'attrs' => 'spellcheck="false" autocapitalize="off"', 'hint' => 'Cần khi muốn khách xem ảnh từ R2. Lấy trong bucket → Settings → Public access (R2.dev subdomain hoặc Custom Domains).']],
        ['_sec', 'Dùng R2 cho'],
        ['r2_upload', 'switch', 'Tự đưa ảnh / video mới tải lên vào R2', ['wide' => true, 'hint' => 'Máy chủ vẫn giữ 1 bản để dự phòng – R2 lỗi cũng không mất ảnh.']],
        ['r2_serve', 'switch', 'Khách xem ảnh / video từ R2 (trang nhẹ hơn, đỡ tải cho máy chủ)', ['wide' => true, 'hint' => 'Cần “Địa chỉ công khai”. Ảnh chưa có trên R2 vẫn lấy từ máy chủ như cũ.']],
        ['r2_backup', 'switch', 'Sao lưu toàn bộ dữ liệu lên R2 mỗi đêm (khoảng 2 giờ sáng)', ['wide' => true, 'hint' => 'Đơn hàng, phiếu, nội dung, tài khoản, ảnh đã tải lên. R2 giữ 31 bản gần nhất (mỗi ngày trong tháng 1 bản), máy chủ giữ 7 bản.']],
    ],
    'dat-lich' => [
        ['appointment_slots', 'list', 'Khung giờ nhận hẹn', ['placeholder' => 'VD: 09:00', 'add' => 'Thêm khung giờ']],
        ['appointment_days', 'number', 'Cho đặt trước tối đa', ['min' => 1, 'max' => 180, 'suffix' => 'ngày']],
    ],
    'tiep-thi' => [
        ['_sec', 'Mạng xã hội', 'Dán link đầy đủ – để trống thì biểu tượng tự ẩn.'],
        ['facebook', 'text', 'Facebook', ['placeholder' => 'https://facebook.com/…']],
        ['messenger', 'text', 'Messenger (nút nổi)', ['placeholder' => 'https://m.me/…']],
        ['instagram', 'text', 'Instagram', []],
        ['tiktok', 'text', 'TikTok', []],
        ['youtube', 'text', 'YouTube', []],
        ['shopee', 'text', 'Shopee', []],
        ['zalo_oa', 'text', 'Zalo OA (trang Zalo của cửa hàng)', ['wide' => true, 'placeholder' => 'https://zalo.me/1234567890123456789']],
        ['_sec', 'Đo lường quảng cáo'],
        ['ga4_id', 'text', 'Google Analytics 4', ['placeholder' => 'G-XXXXXXXXXX']],
        ['fb_pixel_id', 'text', 'Facebook Pixel', ['placeholder' => '123456789012345']],
        ['tiktok_pixel_id', 'text', 'TikTok Pixel', []],
    ],
    'ky-thuat' => [
        ['site_url', 'text', 'Tên miền chính thức', ['wide' => true, 'placeholder' => 'https://vienngocphuong.vn', 'hint' => 'Không có dấu / ở cuối. Dùng cho link chia sẻ, mã QR trên phiếu, sơ đồ trang Google. Để trống = tự nhận diện.']],
        ['pretty_urls', 'switch', 'Đường dẫn đẹp (/san-pham/ten-san-pham)', ['hint' => 'Tắt nếu hosting báo lỗi 404 khi mở trang sản phẩm.']],
    ],
];

/* ================= Lưu ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? 'luu');
    $set = content_load('cai-dat');

    if ($act === 'thu-telegram') {
        $ok = notify_telegram('✅ Tin thử từ trang quản trị ' . cfg('site_name') . ' – lúc ' . date('H:i d/m/Y') . '. Từ giờ đơn hàng mới sẽ báo về đây.');
        adm_back('cai-dat', ['nhom' => 'thong-bao'], $ok ? 'Đã gửi tin thử – kiểm tra Telegram trên điện thoại.' : 'Chưa gửi được. Kiểm tra lại mã bot và Chat ID (nhớ nhắn cho bot ít nhất 1 tin trước).', $ok ? 'success' : 'error');
    }
    if ($act === 'thu-email') {
        global $MAIL_ERROR;
        $ok = send_mail((string) cfg('order_email'), 'Email thử từ trang quản trị ' . cfg('site_name'), '<p>Nếu bạn đọc được email này, website đã gửi email thành công.</p>');
        adm_back('cai-dat', ['nhom' => 'thong-bao'], $ok ? 'Đã gửi email thử tới ' . cfg('order_email') . ' – kiểm tra cả mục Spam/Quảng cáo.' : 'Chưa gửi được email: ' . ($MAIL_ERROR ?: 'không rõ lỗi') , $ok ? 'success' : 'error');
    }
    if ($act === 'r2-thu') {
        $t = r2_test();
        adm_log('Kiểm tra kết nối R2', $t['ok'] ? 'thành công' : $t['message']);
        adm_back('cai-dat', ['nhom' => 'luu-tru'], $t['message'], $t['ok'] && $t['public'] !== false ? 'success' : 'error');
    }
    if ($act === 'r2-dong-bo') {
        if (!r2_ready()) {
            adm_back('cai-dat', ['nhom' => 'luu-tru'], 'Nhập và lưu thông tin kết nối R2 trước.', 'error');
        }
        @set_time_limit(120);
        $r = r2_sync_batch(25);
        adm_log('Đưa ảnh cũ lên R2', $r['done'] . ' tệp');
        $msg = 'Đã đưa ' . $r['done'] . ' tệp lên R2.' . ($r['left'] ? ' Còn ' . $r['left'] . ' tệp – bấm nút thêm lần nữa để tiếp tục.' : ' Tất cả ảnh / video đã có trên R2.');
        adm_back('cai-dat', ['nhom' => 'luu-tru'], $r['error'] !== '' ? $msg . ' Dừng vì lỗi: ' . $r['error'] : $msg, $r['error'] !== '' ? 'error' : 'success');
    }
    if ($act === 'r2-sao-luu') {
        require_once ROOT . '/includes/sao-luu.php';
        if (!r2_ready()) {
            adm_back('cai-dat', ['nhom' => 'luu-tru'], 'Nhập và lưu thông tin kết nối R2 trước.', 'error');
        }
        @set_time_limit(300);
        $zip = backup_make(backup_dir() . '/thu-cong-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.zip');
        if ($zip === '') {
            adm_back('cai-dat', ['nhom' => 'luu-tru'], 'Không tạo được file sao lưu (máy chủ thiếu ZipArchive hoặc không ghi được thư mục data/quan-tri).', 'error');
        }
        $r = r2_backup_upload($zip, 'thu-cong');
        @unlink($zip);
        adm_log('Sao lưu lên R2', $r['message']);
        adm_back('cai-dat', ['nhom' => 'luu-tru'], $r['message'], $r['ok'] ? 'success' : 'error');
    }
    if ($act === 'r2-xoa') {
        secret_set(['r2_account_id' => '', 'r2_access_key_id' => '', 'r2_secret_access_key' => '', 'r2_bucket' => '']);
        $set['r2_serve'] = false;
        content_save('cai-dat', $set);
        adm_log('Xóa thông tin kết nối R2');
        adm_back('cai-dat', ['nhom' => 'luu-tru'], 'Đã xóa thông tin kết nối R2. Website lấy ảnh từ máy chủ như trước.');
    }
    if ($act === 'tim-chat') {
        $token = trim((string) cfg('telegram_bot_token'));
        $found = [];
        if ($token !== '') {
            $ctx  = stream_context_create(['http' => ['timeout' => 8]]);
            $json = @file_get_contents('https://api.telegram.org/bot' . rawurlencode($token) . '/getUpdates', false, $ctx);
            foreach ((array) (json_decode((string) $json, true)['result'] ?? []) as $u) {
                $chat = $u['message']['chat'] ?? $u['my_chat_member']['chat'] ?? $u['channel_post']['chat'] ?? null;
                if ($chat) {
                    $found[(string) $chat['id']] = trim(($chat['title'] ?? '') . ' ' . ($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? '')) ?: (string) $chat['id'];
                }
            }
        }
        $_SESSION['adm_chats'] = $found;
        adm_back('cai-dat', ['nhom' => 'thong-bao'], $found ? 'Tìm thấy ' . count($found) . ' nơi nhận tin – bấm “Dùng” để chọn.' : 'Chưa thấy tin nhắn nào gửi tới bot. Hãy mở bot trên Telegram, bấm Start / nhắn “xin chào”, rồi thử lại.', $found ? 'success' : 'error');
    }
    if ($act === 'dung-chat') {
        $set['telegram_chat_id'] = preg_replace('/[^0-9-]/', '', (string) ($_POST['chat'] ?? ''));
        content_save('cai-dat', $set);
        unset($_SESSION['adm_chats']);
        adm_log('Cài đặt', 'Chọn Chat ID Telegram');
        adm_back('cai-dat', ['nhom' => 'thong-bao'], 'Đã chọn nơi nhận tin. Bấm “Gửi tin thử” để kiểm tra.');
    }

    // Ô đơn giản của nhóm
    $vault = [];   // ô bí mật → lưu mã hóa trong kho bí mật, không ghi vào cai-dat.json
    foreach ($fields[$g] ?? [] as $f) {
        if ($f[0] === '_sec') {
            continue;
        }
        [$k, $type] = $f;
        if ($type === 'vault' || $type === 'vault_text') {
            $v = in_str($k, 300);
            if (!($type === 'vault' && $v === '' && !empty($_POST[$k . '_keep']))) {
                $vault[$k] = $v;
            }
            continue;
        }
        $set[$k] = match ($type) {
            'select' => isset($f[3]['options'][(string) ($_POST[$k] ?? '')]) ? (string) $_POST[$k] : (string) array_key_first($f[3]['options']),
            'money'  => in_money($k),
            'number' => in_int($k, (int) ($f[3]['min'] ?? 0), (int) ($f[3]['max'] ?? PHP_INT_MAX)),
            'switch' => in_bool($k),
            'list'   => in_list($k, 40),
            'secret' => ($v = in_str($k, 300)) === '' && !empty($_POST[$k . '_keep']) ? (string) cfg($k) : $v,
            'textarea' => in_str($k, 2000),
            'image'    => clean_asset_path(in_str($k, 300)),
            default  => in_str($k, 500),
        };
    }
    if ($g === 'thong-bao') {
        $set['smtp_host'] = strtolower(preg_replace('/\s+/', '', (string) $set['smtp_host']));
        $set['smtp_port'] = $set['smtp_port'] ?: ($set['smtp_secure'] === 'ssl' ? 465 : 587);
    }
    if ($g === 'luu-tru') {
        $acc = (string) ($vault['r2_account_id'] ?? '');
        if (preg_match('/([a-f0-9]{32})/i', $acc, $m)) {            // dán nhầm cả địa chỉ https://<id>.r2.cloudflarestorage.com cũng được
            $vault['r2_account_id'] = strtolower($m[1]);
        } elseif ($acc !== '') {
            adm_back('cai-dat', ['nhom' => $g], 'Account ID gồm 32 ký tự (chữ a–f và số) – xem ở trang R2 Object Storage trên Cloudflare.', 'error');
        }
        $vault['r2_access_key_id'] = preg_replace('/\s+/', '', (string) ($vault['r2_access_key_id'] ?? ''));
        if (isset($vault['r2_secret_access_key'])) {
            $vault['r2_secret_access_key'] = preg_replace('/\s+/', '', $vault['r2_secret_access_key']);
        }
        $bucket = strtolower(trim((string) ($vault['r2_bucket'] ?? '')));
        if ($bucket !== '' && !preg_match('/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/', $bucket)) {
            adm_back('cai-dat', ['nhom' => $g], 'Tên bucket chỉ gồm chữ thường không dấu, số và dấu gạch ngang (3–63 ký tự).', 'error');
        }
        $vault['r2_bucket']    = $bucket;
        $set['r2_public_url'] = rtrim(trim((string) $set['r2_public_url']), '/');
        if ($set['r2_public_url'] !== '' && !preg_match('#^https://[a-z0-9.-]+(/[^\s]*)?$#i', $set['r2_public_url'])) {
            adm_back('cai-dat', ['nhom' => $g], 'Địa chỉ công khai phải bắt đầu bằng https:// – VD https://pub-xxxx.r2.dev', 'error');
        }
        if ($set['r2_serve'] && $set['r2_public_url'] === '') {
            $set['r2_serve'] = false;
        }
    }
    if ($vault && !secret_set($vault)) {
        adm_back('cai-dat', ['nhom' => $g], 'Không lưu được thông tin bí mật – kiểm tra quyền ghi thư mục data/quan-tri và tệp khóa mã hóa.', 'error');
    }
    if ($g === 'cua-hang' && trim((string) $set['site_name']) === '') {
        adm_back('cai-dat', ['nhom' => $g], 'Tên cửa hàng không được để trống.', 'error');
    }
    if ($g === 'ky-thuat') {
        $set['site_url'] = rtrim($set['site_url'], '/');
        if ($set['site_url'] !== '' && !preg_match('#^https?://[a-z0-9.-]+(:\d+)?(/[^\s]*)?$#i', $set['site_url'])) {
            adm_back('cai-dat', ['nhom' => $g], 'Tên miền chưa đúng – ví dụ: https://vienngocphuong.vn', 'error');
        }
    }
    if ($g === 'thanh-toan') {
        $code = (string) ($_POST['bank_id'] ?? '');
        $acc  = preg_replace('/[^0-9A-Za-z]/', '', in_str('account', 30));
        $set['bank'] = $code !== '' && isset($banks[$code]) ? [
            'bank_id' => $code,
            'bank'    => $banks[$code]['short'],
            'account' => $acc,
            'holder'  => strtoupper(vn_fold(in_str('holder', 80))),
            'branch'  => in_str('branch', 120),
        ] : ['bank_id' => '', 'bank' => '', 'account' => '', 'holder' => '', 'branch' => ''];
        if ($code !== '' && $acc === '') {
            adm_back('cai-dat', ['nhom' => $g], 'Vui lòng nhập số tài khoản (hoặc chọn “Không nhận chuyển khoản”).', 'error');
        }
        $set['cod_enabled']      = in_bool('cod_enabled');
        if (!$set['cod_enabled'] && $acc === '') {
            adm_back('cai-dat', ['nhom' => $g], 'Phải bật ít nhất một cách thanh toán: COD hoặc chuyển khoản.', 'error');
        }
        $set['payment_provider'] = in_array($_POST['payment_provider'] ?? '', ['none', 'casso', 'sepay'], true) ? (string) $_POST['payment_provider'] : 'none';
        foreach (['casso_webhook_key', 'sepay_api_key'] as $k) {
            $v = in_str($k, 200);
            $set[$k] = $v === '' && !empty($_POST[$k . '_keep']) ? (string) cfg($k) : $v;
        }
        $set['vat_mode'] = ($_POST['vat_mode'] ?? '') === 'added' ? 'added' : 'included';
        $set['vat_rate'] = in_int('vat_rate', 0, 20);
    }
    if ($g === 'giao-hang') {
        $set['shipping_fee']   = in_money('shipping_fee');
        $set['free_ship_from'] = in_money('free_ship_from');
        $gifts = [];
        foreach (['thiep' => 'Thiệp viết tay theo lời nhắn của bạn', 'hop_qua' => 'Hộp quà cao cấp & túi giấy sang trọng', 'hoa_toc' => 'Giao hỏa tốc trong 2–4 giờ (nội thành)'] as $k => $def) {
            if (in_bool('gift_on_' . $k)) {
                $gifts[$k] = ['label' => in_str('gift_label_' . $k, 120) ?: $def, 'price' => in_money('gift_price_' . $k)];
            }
        }
        $set['gift_options'] = $gifts;
    }
    if ($g === 'phieu') {
        $set['receipt_terms'] = ['coc' => in_list('terms_coc', 600), 'thanh-toan' => in_list('terms_thanh-toan', 600), 'don-hang' => in_list('terms_don-hang', 600)];
    }
    if ($g === 'dat-lich') {
        $slots = array_values(array_filter($set['appointment_slots'], fn($s) => preg_match('/^\d{1,2}:\d{2}$/', $s)));
        sort($slots);
        $set['appointment_slots'] = $slots;
    }
    if (!content_save('cai-dat', $set)) {
        adm_back('cai-dat', ['nhom' => $g], 'Không lưu được – kiểm tra quyền ghi thư mục data/.', 'error');
    }
    adm_log('Cài đặt', $groups[$g][0]);
    if ($g === 'luu-tru' && r2_ready()) {
        // Lưu xong kiểm tra luôn để biết thông tin nhập đúng chưa (dùng cài đặt vừa lưu)
        $GLOBALS['CONFIG'] = array_replace($GLOBALS['CONFIG'], $set);
        $t = r2_test();
        adm_back('cai-dat', ['nhom' => $g], 'Đã lưu. ' . $t['message'], $t['ok'] && $t['public'] !== false ? 'success' : 'error');
    }
    adm_back('cai-dat', ['nhom' => $g], 'Đã lưu “' . $groups[$g][0] . '”.');
}

/* ================= Giao diện ================= */
function cfg_field(array $f): string
{
    if ($f[0] === '_sec') {
        return '<p class="form-sec">' . e($f[1]) . (isset($f[2]) ? '<small>' . e($f[2]) . '</small>' : '') . '</p>';
    }
    [$k, $type, $label] = $f;
    $o = $f[3] ?? [];
    $v = cfg($k, $type === 'list' ? [] : '');
    return match ($type) {
        'money'    => f_money($k, $label, (int) $v, $o),
        'number'   => f_number($k, $label, (int) $v, $o),
        'textarea' => f_textarea($k, $label, (string) $v, $o + ['autosize' => true]),
        'switch'   => f_switch($k, $label, (bool) $v, $o),
        'image'    => f_image($k, $label, (string) $v, $o),
        'list'     => f_list($k, $label, (array) $v, $o + ['wide' => true]),
        'select'   => f_select($k, $label, (string) $v, $o['options'] ?? [], $o),
        'vault_text' => f_text($k, $label, secret_get($k), $o),
        'vault'    => f_text($k, $label, '', array_merge($o, ['type' => 'password', 'autocomplete' => 'new-password', 'required' => !empty($o['required']) && secret_get($k) === '', 'placeholder' => secret_get($k) !== '' ? '•••••••• (đã lưu – để trống nếu không đổi)' : ($o['placeholder'] ?? '')]))
                      . (secret_get($k) !== '' ? '<input type="hidden" name="' . e($k) . '_keep" value="1">' : ''),
        'secret'   => f_text($k, $label, '', $o + ['type' => 'password', 'autocomplete' => 'off', 'placeholder' => $v !== '' ? '•••••••• (đã lưu – để trống nếu không đổi)' : ($o['placeholder'] ?? '')])
                      . ($v !== '' ? '<input type="hidden" name="' . e($k) . '_keep" value="1">' : ''),
        default    => f_text($k, $label, (string) $v, $o),
    };
}

adm_head('Cài đặt cửa hàng', 'cai-dat');
?>
<nav class="adm-tabs" aria-label="Nhóm cài đặt"><?php foreach ($groups as $k => [$l, $ic]): ?><a href="<?= e(adm_url('cai-dat', ['nhom' => $k])) ?>"<?= $k === $g ? ' aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach; ?></nav>

<form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => $g])) ?>" data-track>
  <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu">

<?php if ($g === 'thanh-toan'):
    $bank = cfg('bank', []);
    $bankOptions = ['' => '– Không nhận chuyển khoản (chỉ COD) –'];
    foreach ($banks as $code => $b) {
        $bankOptions[$code] = $b['short'] . ' – ' . $b['name'];
    }
    $qr = vietqr_url(10000, 'THU NGHIEM'); ?>
  <section class="card">
    <h2>Tài khoản nhận chuyển khoản</h2>
    <p class="card__sub">Khách quét mã QR (VietQR) là có sẵn số tài khoản, số tiền và nội dung là mã đơn.</p>
    <div class="form-grid">
      <?= f_select('bank_id', 'Ngân hàng', (string) ($bank['bank_id'] ?? ''), $bankOptions, ['wide' => true]) ?>
      <?= f_text('account', 'Số tài khoản', (string) ($bank['account'] ?? ''), ['inputmode' => 'numeric', 'autocomplete' => 'off']) ?>
      <?= f_text('holder', 'Tên chủ tài khoản', (string) ($bank['holder'] ?? ''), ['placeholder' => 'NGUYEN VAN A', 'hint' => 'Tự viết hoa không dấu khi lưu – phải khớp tên trên ngân hàng.']) ?>
      <?= f_text('branch', 'Chi nhánh (không bắt buộc)', (string) ($bank['branch'] ?? ''), ['wide' => true]) ?>
      <?php if ($qr): ?>
        <div class="af af--wide"><span class="af__label">Mã QR thử (10.000₫) – quét bằng app ngân hàng để kiểm tra đúng tên chủ tài khoản, không cần chuyển</span>
          <img src="<?= e($qr) ?>" alt="Mã QR thử" width="220" height="261" style="border:1px solid var(--c-border);border-radius:10px;background:#fff"></div>
      <?php endif; ?>
      <?= f_switch('cod_enabled', 'Nhận đơn trả tiền khi nhận hàng (COD)', (bool) cfg('cod_enabled', true), ['wide' => true]) ?>
    </div>
  </section>

  <section class="card">
    <h2>Tự động xác nhận tiền vào</h2>
    <p class="card__sub">Dịch vụ theo dõi tài khoản ngân hàng báo về website mỗi khi có tiền vào → website tự xác nhận cọc / đơn theo mã trong nội dung chuyển khoản. Trước khi đăng ký, kiểm tra ngân hàng của bạn có trong danh sách hỗ trợ của dịch vụ.</p>
    <div class="form-grid">
      <div class="af af--wide"><span class="af__label">Dịch vụ</span><div class="checks">
        <?php foreach (['none' => 'Không dùng – cửa hàng tự xác nhận', 'casso' => 'Casso (casso.vn)', 'sepay' => 'SePay (sepay.vn)'] as $k => $l): ?>
          <label class="check"><input type="radio" name="payment_provider" value="<?= e($k) ?>"<?= cfg('payment_provider', cfg('casso_webhook_key') ? 'casso' : 'none') === $k ? ' checked' : '' ?>><span><?= e($l) ?></span></label>
        <?php endforeach; ?>
      </div></div>
      <div class="af af--wide" data-show-if="payment_provider=casso">
        <?= adm_notice('<p><strong>Cách bật Casso:</strong> đăng ký casso.vn → liên kết tài khoản ngân hàng → <em>Tích hợp → Webhook V2</em> → điền link:<br><code>' . e(absolute_url(base_path() . '/webhook-casso.php')) . '</code><br>→ sao chép “Key bảo mật” dán vào ô dưới đây.</p>') ?>
        <?= f_text('casso_webhook_key', 'Key bảo mật Webhook V2 (Casso)', '', ['type' => 'password', 'autocomplete' => 'off', 'placeholder' => cfg('casso_webhook_key') ? '•••••••• (đã lưu – để trống nếu không đổi)' : '']) ?>
        <?php if (cfg('casso_webhook_key')): ?><input type="hidden" name="casso_webhook_key_keep" value="1"><?php endif; ?>
      </div>
      <div class="af af--wide" data-show-if="payment_provider=sepay">
        <?= adm_notice('<p><strong>Cách bật SePay:</strong> đăng ký sepay.vn → liên kết tài khoản ngân hàng → <em>Tích hợp → Webhooks → Thêm webhook</em>: chọn sự kiện <em>Có tiền vào</em>, URL:<br><code>' . e(absolute_url(base_path() . '/webhook-sepay.php')) . '</code><br>Kiểu chứng thực <strong>API Key</strong> – tự đặt một chuỗi bí mật, dán cùng chuỗi đó vào ô dưới đây.</p>') ?>
        <?= f_text('sepay_api_key', 'API Key (SePay)', '', ['type' => 'password', 'autocomplete' => 'off', 'placeholder' => cfg('sepay_api_key') ? '•••••••• (đã lưu – để trống nếu không đổi)' : '']) ?>
        <?php if (cfg('sepay_api_key')): ?><input type="hidden" name="sepay_api_key_keep" value="1"><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="card">
    <h2>Thuế VAT trong báo giá</h2>
    <div class="form-grid">
      <div class="af af--wide"><div class="checks">
        <label class="check"><input type="radio" name="vat_mode" value="included"<?= cfg('vat_mode', 'included') !== 'added' ? ' checked' : '' ?>><span>Giá sản phẩm đã gồm VAT (không cộng thêm)</span></label>
        <label class="check"><input type="radio" name="vat_mode" value="added"<?= cfg('vat_mode', 'included') === 'added' ? ' checked' : '' ?>><span>Giá chưa gồm VAT – cộng thêm VAT khi đặt hàng</span></label>
      </div></div>
      <?= f_number('vat_rate', 'Mức thuế VAT', (int) cfg('vat_rate', 10), ['min' => 0, 'max' => 20, 'suffix' => '%', 'show_if' => 'vat_mode=added', 'hint' => 'Thường 10%, hoặc 8% khi có chính sách giảm thuế.']) ?>
      <div class="af af--wide" data-show-if="vat_mode=added"><?= adm_notice('Hộ kinh doanh doanh thu dưới 3 tỷ đồng/năm tính thuế theo % doanh thu và <strong>không xuất hóa đơn VAT</strong> – thường không cộng VAT riêng cho khách. Nên hỏi kế toán trước khi bật. Khi bật: giỏ hàng, mã QR, đơn hàng, phiếu xác nhận đều cộng dòng VAT.', 'warn') ?></div>
    </div>
  </section>

<?php elseif ($g === 'giao-hang'): $gifts = cfg('gift_options', []); ?>
  <section class="card">
    <h2>Phí giao hàng</h2>
    <div class="form-grid">
      <?= f_money('shipping_fee', 'Phí giao hàng', (int) cfg('shipping_fee', 0), ['hint' => 'Để trống / 0 = miễn phí giao hàng.']) ?>
      <?= f_money('free_ship_from', 'Miễn phí giao khi đơn từ', (int) cfg('free_ship_from', 0), ['hint' => '0 = luôn tính phí như trên (hoặc luôn miễn phí nếu phí = 0).']) ?>
    </div>
  </section>
  <section class="card">
    <h2>Dịch vụ quà tặng khi đặt hàng</h2>
    <p class="card__sub">Khách tick chọn trong giỏ hàng. Giá 0 = miễn phí.</p>
    <div class="form-grid">
      <?php foreach (['thiep' => 'Thiệp viết tay theo lời nhắn của bạn', 'hop_qua' => 'Hộp quà cao cấp & túi giấy sang trọng', 'hoa_toc' => 'Giao hỏa tốc trong 2–4 giờ (nội thành)'] as $k => $def): $on = isset($gifts[$k]); ?>
        <p class="form-sec"><?= e($def) ?></p>
        <?= f_switch('gift_on_' . $k, 'Bật dịch vụ này', $on) ?>
        <div></div>
        <?= f_text('gift_label_' . $k, 'Tên hiển thị', (string) ($gifts[$k]['label'] ?? $def)) ?>
        <?= f_money('gift_price_' . $k, 'Phí', (int) ($gifts[$k]['price'] ?? 0)) ?>
      <?php endforeach; ?>
    </div>
  </section>

<?php elseif ($g === 'phieu'): $terms = cfg('receipt_terms', []); ?>
  <?= adm_notice('Phiếu lập khi: cửa hàng nhận cọc · đơn chuyển khoản đã đủ tiền · cửa hàng xác nhận đơn COD. Mỗi phiếu lưu lại điều khoản lúc lập – sửa ở đây chỉ áp dụng cho phiếu lập sau. Tên, mã số thuế, địa chỉ in trên phiếu lấy từ <a href="' . e(adm_url('cai-dat', ['nhom' => 'cua-hang'])) . '">Cửa hàng &amp; pháp lý</a>.') ?>
  <section class="card">
    <div class="form-grid">
      <?= f_list('terms_coc', 'Điều khoản trên phiếu đặt cọc', (array) ($terms['coc'] ?? []), ['wide' => true, 'multiline' => true, 'add' => 'Thêm điều khoản']) ?>
      <?= f_list('terms_thanh-toan', 'Điều khoản trên phiếu thanh toán', (array) ($terms['thanh-toan'] ?? []), ['wide' => true, 'multiline' => true, 'add' => 'Thêm điều khoản']) ?>
      <?= f_list('terms_don-hang', 'Điều khoản trên phiếu đơn hàng (COD)', (array) ($terms['don-hang'] ?? []), ['wide' => true, 'multiline' => true, 'add' => 'Thêm điều khoản']) ?>
      <?php foreach ($fields['phieu'] as $f) echo cfg_field($f); ?>
      <p class="af__hint af--wide">Mã tự thay: <code>{SITE_NAME}</code> <code>{HOTLINE}</code> <code>{EMAIL}</code> <code>{WEBSITE}</code> <code>{LINK_CHINH_SACH}</code> <code>{GIO_GIU}</code> <code>{GIO_GIA_HAN}</code> <code>{PHAN_TRAM_COC}</code> <code>{THANG_PHIEU_MUA}</code> <code>{NGAY_HOAN_TIEN}</code></p>
    </div>
  </section>

<?php elseif ($g === 'luu-tru'): $r2ok = r2_ready(); $r2bad = array_intersect(secret_unreadable(), ['r2_account_id', 'r2_access_key_id', 'r2_secret_access_key', 'r2_bucket']); ?>
  <?php if ($r2bad): ?><?= adm_notice('Thông tin kết nối R2 đã lưu trước đây không đọc được (website vừa chuyển máy chủ hoặc mất khóa mã hóa) – hãy nhập lại 4 ô bên dưới.', 'warn') ?><?php endif; ?>
  <?= adm_notice('<p><strong>Cách lấy thông tin</strong> (làm 1 lần trên <strong>dash.cloudflare.com</strong>):</p>
    <ol style="margin:6px 0 0 18px;padding:0">
      <li>Vào <em>R2 Object Storage</em> → <em>Create bucket</em> → đặt tên (VD <code>vienngocphuong</code>) → <em>Create bucket</em>.</li>
      <li><strong>Account ID</strong>: ở trang tổng quan R2 (mục <em>Account Details</em>) – chuỗi 32 ký tự.</li>
      <li><em>Account Details → API Tokens → Manage</em> → <em>Create Account API token</em> (hoặc User API token) → quyền <strong>Object Read &amp; Write</strong> → chọn bucket vừa tạo → <em>Create</em> → sao chép <strong>Access Key ID</strong> và <strong>Secret Access Key</strong> (Secret chỉ hiện 1 lần).</li>
      <li>Muốn khách xem ảnh từ R2: vào bucket → <em>Settings → Public access</em> → bật <em>R2.dev subdomain</em> (hoặc gắn <em>Custom Domain</em>) → dán địa chỉ vào ô “Địa chỉ công khai”.</li>
    </ol>
    <p style="margin-top:6px">Các khóa được lưu <strong>mã hóa</strong> trên máy chủ, đổi lúc nào cũng được – website đọc lại mỗi lần tải tệp lên R2.</p>') ?>
  <section class="card">
    <h2>Thông tin kết nối <?= $r2ok ? adm_badge('Đã nhập đủ', 'ok') : adm_badge('Chưa kết nối', 'muted') ?></h2>
    <div class="form-grid">
      <?php foreach (array_slice($fields['luu-tru'], 0, 5) as $f) echo cfg_field($f); ?>
    </div>
  </section>
  <section class="card">
    <h2>Dùng R2 cho</h2>
    <div class="form-grid">
      <?php foreach (array_slice($fields['luu-tru'], 6) as $f) echo cfg_field($f); ?>
    </div>
    <?php if (r2_public_base() !== '' && preg_match('#\.r2\.dev$#', (string) parse_url(r2_public_base(), PHP_URL_HOST))): ?>
      <?= adm_notice('Địa chỉ dạng <code>….r2.dev</code> Cloudflare chỉ khuyên dùng để thử (bị giới hạn tốc độ). Dùng lâu dài nên gắn tên miền riêng cho bucket, VD <code>cdn.vienngocphuong.com</code> (cần quản lý DNS tên miền trên Cloudflare).', 'warn') ?>
    <?php endif; ?>
  </section>

<?php else: ?>
  <section class="card">
    <div class="form-grid">
      <?php foreach ($fields[$g] ?? [] as $f) echo cfg_field($f); ?>
    </div>
  </section>
<?php endif; ?>

  <?= adm_savebar('Lưu cài đặt') ?>
</form>

<?php if ($g === 'luu-tru'):
    $st      = r2_status();
    $onR2    = count(array_intersect_key(r2_manifest(), array_flip(r2_local_uploads())));
    $pending = count(r2_pending());
    $r2ok    = r2_ready();
    $dis     = $r2ok ? '' : ' disabled title="Nhập và lưu thông tin kết nối trước"'; ?>
  <section class="card">
    <h2>Kiểm tra &amp; đồng bộ</h2>
    <dl class="dl" style="margin-top:10px">
      <dt>Ảnh / video đã có trên R2</dt><dd><?= e((string) $onR2) ?> tệp<?= $pending ? ' · <strong>' . e((string) $pending) . ' tệp chưa đưa lên</strong>' : '' ?></dd>
      <dt>Lần kiểm tra gần nhất</dt><dd><?= !empty($st['test']) ? e(adm_time((int) $st['test']['time'])) . ' – ' . (!empty($st['test']['ok']) ? adm_badge('Thành công', 'ok') : adm_badge('Lỗi', 'danger')) . ' ' . e((string) $st['test']['message']) : 'Chưa kiểm tra' ?></dd>
      <dt>Lần sao lưu lên R2 gần nhất</dt><dd><?= !empty($st['backup']) ? e(adm_time((int) $st['backup']['time'])) . ' – ' . (!empty($st['backup']['ok']) ? adm_badge('Thành công', 'ok') : adm_badge('Lỗi', 'danger')) . ' ' . e((string) $st['backup']['message']) : 'Chưa có' ?></dd>
      <?php if (!empty($st['upload_error']) && time() - (int) $st['upload_error']['time'] < 7 * 86400): ?>
        <dt>Lỗi tải lên R2 gần đây</dt><dd><?= e(adm_time((int) $st['upload_error']['time'])) ?> – <?= e((string) $st['upload_error']['error']) ?></dd>
      <?php endif; ?>
    </dl>
    <div class="btn-row" style="margin-top:14px">
      <form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => 'luu-tru'])) ?>" class="inline-form"><?= adm_csrf_field() ?><button type="submit" name="act" value="r2-thu" class="btn btn--outline btn--sm"<?= $dis ?>><?= icon('check') ?>Kiểm tra kết nối</button></form>
      <form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => 'luu-tru'])) ?>" class="inline-form"><?= adm_csrf_field() ?><button type="submit" name="act" value="r2-dong-bo" class="btn btn--outline btn--sm"<?= $r2ok && $pending ? '' : ' disabled' ?>><?= adm_icon('upload') ?><?= $pending ? 'Đưa ' . e((string) $pending) . ' ảnh / video cũ lên R2' : 'Ảnh / video đã có đủ trên R2' ?></button></form>
      <form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => 'luu-tru'])) ?>" class="inline-form"><?= adm_csrf_field() ?><button type="submit" name="act" value="r2-sao-luu" class="btn btn--outline btn--sm"<?= $dis ?>><?= icon('download') ?>Sao lưu lên R2 ngay</button></form>
      <?php if ($r2ok): ?>
      <form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => 'luu-tru'])) ?>" class="inline-form" data-confirm="Xóa thông tin kết nối R2? Website sẽ lấy ảnh từ máy chủ như trước (tệp trên R2 không bị xóa)."><?= adm_csrf_field() ?><button type="submit" name="act" value="r2-xoa" class="btn btn--danger btn--sm"><?= icon('trash') ?>Xóa thông tin kết nối</button></form>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
<?php if ($g === 'thong-bao'): $chats = $_SESSION['adm_chats'] ?? []; ?>
  <section class="card">
    <h2>Kiểm tra</h2>
    <div class="btn-row" style="margin-top:12px">
      <form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => 'thong-bao'])) ?>" class="inline-form"><?= adm_csrf_field() ?><button type="submit" name="act" value="tim-chat" class="btn btn--outline btn--sm"<?= cfg('telegram_bot_token') ? '' : ' disabled title="Lưu mã bot trước"' ?>><?= icon('search') ?>Tìm mã nhận tin (Chat ID)</button></form>
      <form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => 'thong-bao'])) ?>" class="inline-form"><?= adm_csrf_field() ?><button type="submit" name="act" value="thu-telegram" class="btn btn--outline btn--sm"<?= cfg('telegram_bot_token') && cfg('telegram_chat_id') ? '' : ' disabled' ?>><?= adm_icon('bell') ?>Gửi tin Telegram thử</button></form>
      <form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => 'thong-bao'])) ?>" class="inline-form"><?= adm_csrf_field() ?><button type="submit" name="act" value="thu-email" class="btn btn--outline btn--sm"><?= icon('mail') ?>Gửi email thử</button></form>
    </div>
    <?php if ($chats): ?>
      <ul class="todo" style="margin-top:14px">
        <?php foreach ($chats as $id => $name): ?>
          <li><span class="todo__icon todo__icon--info"><?= icon('chat') ?></span><div class="todo__body"><strong><?= e($name) ?></strong><p>Chat ID: <?= e($id) ?></p></div>
            <form method="post" action="<?= e(adm_url('cai-dat', ['nhom' => 'thong-bao'])) ?>"><?= adm_csrf_field() ?><input type="hidden" name="act" value="dung-chat"><input type="hidden" name="chat" value="<?= e($id) ?>"><button type="submit" class="btn btn--sm btn--primary">Dùng</button></form></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
<?php endif; ?>
<?php
adm_foot();
