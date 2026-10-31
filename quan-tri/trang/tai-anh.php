<?php
/** Nhận ảnh tải lên từ trang quản trị (gọi bằng JavaScript) → trả JSON {path, w, h} */
defined('ADMIN') || exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    adm_json(['error' => 'Chỉ nhận POST'], 405);
}
if (empty($_FILES['file'])) {
    // Tệp lớn hơn post_max_size → PHP bỏ toàn bộ dữ liệu gửi lên
    adm_json(['error' => 'Không nhận được ảnh – có thể ảnh lớn hơn giới hạn của hosting (' . ini_get('post_max_size') . ').'], 400);
}
$folder = preg_replace('/[^a-z0-9-]/', '', (string) ($_POST['folder'] ?? 'chung')) ?: 'chung';
$max    = max(400, min(4000, (int) ($_POST['max'] ?? 2000)));
$res    = $folder === 'video' ? save_uploaded_video($_FILES['file']) : save_uploaded_image($_FILES['file'], $folder, max($max, 2400));
if (!empty($res['error'])) {
    adm_json($res, 400);
}
adm_log('Tải lên', $res['path']);
// Đưa lên Cloudflare R2 nếu đã bật (bản trên máy chủ luôn được giữ làm dự phòng; lỗi R2 không làm hỏng việc tải lên)
$r2err = r2_mirror(($folder === 'video' ? 'assets/' : 'assets/img/') . $res['path']);
adm_json($res + ['url' => img_url($res['path'])] + ($r2err !== '' ? ['r2_error' => $r2err] : []));
