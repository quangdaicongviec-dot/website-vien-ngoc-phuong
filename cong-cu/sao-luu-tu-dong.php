<?php
/**
 * SAO LƯU TỰ ĐỘNG – chạy bằng lịch hẹn giờ (cron) trên máy chủ, mỗi đêm 1 lần:
 *   php /var/www/vienngocphuong/cong-cu/sao-luu-tu-dong.php
 * Tạo bản sao lưu .zip trong data/quan-tri/sao-luu/ (giữ 7 bản) và gửi lên Cloudflare R2 nếu đã bật trong trang quản trị.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/includes/init.php';
require ROOT . '/includes/sao-luu.php';

@set_time_limit(0);
$res = backup_auto();
echo date('Y-m-d H:i:s'), ' ', $res['ok'] ? 'OK' : 'LOI', ' ', $res['message'], "\n";
exit($res['ok'] ? 0 : 1);
