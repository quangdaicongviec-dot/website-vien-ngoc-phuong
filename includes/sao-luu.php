<?php
/**
 * SAO LƯU DỮ LIỆU (dùng chung cho Trang quản trị → Sao lưu và bản sao lưu tự động mỗi đêm)
 * Gồm: đơn hàng, phiếu, lịch hẹn, nội dung đã sửa, cài đặt, tài khoản, ảnh / video đã tải lên.
 * Không gồm khóa giải mã kho bí mật (data/quan-tri/.khoa-bi-mat).
 */

/** Các tệp đưa vào bản sao lưu: [đường dẫn trong zip => đường dẫn thật] */
function backup_files(): array
{
    $out = [];
    $add = function (string $dir) use (&$out) {
        $abs = ROOT . '/' . $dir;
        if (!is_dir($abs)) {
            return;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            $rel = $dir . '/' . str_replace('\\', '/', substr($f->getPathname(), strlen($abs) + 1));
            if ($f->isFile() && strpos($rel, '/sao-luu/') === false && !preg_match('/\.tmp$|\/\.khoa-bi-mat$|\/\.DS_Store$/', $rel)) {
                $out[$rel] = $f->getPathname();
            }
        }
    };
    foreach (['data/don-hang', 'data/noi-dung', 'data/quan-tri', 'assets/img/uploads', 'assets/videos/uploads'] as $d) {
        $add($d);
    }
    return $out;
}

/** Tạo file zip sao lưu tại $target, trả về $target (hoặc '' nếu máy chủ thiếu ZipArchive / không ghi được) */
function backup_make(string $target): string
{
    if (!class_exists('ZipArchive')) {
        return '';
    }
    $zip = new ZipArchive();
    if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return '';
    }
    foreach (backup_files() as $rel => $abs) {
        $zip->addFile($abs, $rel);
    }
    $zip->addFromString('DOC-TRUOC.txt', "Bản sao lưu website " . cfg('site_name') . " lúc " . date('H:i d/m/Y') . ".\n"
        . "Khôi phục: Trang quản trị → Sao lưu & khôi phục → Khôi phục từ file.\n"
        . "Hoặc giải nén rồi tải các thư mục data/ và assets/ lên đúng vị trí trên máy chủ (ghi đè).\n");
    return $zip->close() ? $target : '';
}

/** Đường dẫn trong zip được phép khôi phục (chỉ dữ liệu & ảnh – không bao giờ là mã chạy) */
function backup_allowed(string $rel): bool
{
    if ($rel === '' || strpos($rel, '..') !== false || $rel[0] === '/' || strpos($rel, '\\') !== false) {
        return false;
    }
    return (bool) preg_match('#^(data/(don-hang|noi-dung|quan-tri)/[A-Za-z0-9._/-]+\.(json|csv)|data/don-hang/\.secret|data/don-hang/phieu/[A-Z0-9-]+\.json|assets/(img|videos)/uploads/[A-Za-z0-9._/-]+\.(jpg|jpeg|png|webp|gif|mp4|webm))$#', $rel);
}

/** Thư mục cất bản sao lưu trên máy chủ (không nằm trong bản sao lưu) */
function backup_dir(): string
{
    $dir = ROOT . '/data/quan-tri/sao-luu';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

/** Bản sao lưu tự động mỗi đêm trên máy chủ: [tên tệp => ['size', 'time']] – mới nhất trước */
function backup_auto_list(): array
{
    $out = [];
    foreach (glob(backup_dir() . '/tu-dong-*.zip') ?: [] as $f) {
        $out[basename($f)] = ['size' => filesize($f), 'time' => filemtime($f)];
    }
    uasort($out, fn($a, $b) => $b['time'] <=> $a['time']);
    return $out;
}

/**
 * Sao lưu tự động (cron mỗi đêm – cong-cu/sao-luu-tu-dong.php):
 * tạo .zip trên máy chủ (giữ 7 bản gần nhất) và đưa lên R2 nếu đã bật "Sao lưu lên R2 mỗi đêm".
 */
function backup_auto(): array
{
    $zip = backup_make(backup_dir() . '/tu-dong-' . date('Ymd') . '.zip');
    if ($zip === '') {
        return ['ok' => false, 'message' => 'Không tạo được file sao lưu (thiếu ZipArchive hoặc không ghi được thư mục data/quan-tri/sao-luu).'];
    }
    @chmod($zip, 0600);
    foreach (array_slice(array_keys(backup_auto_list()), 7) as $old) {
        @unlink(backup_dir() . '/' . $old);
    }
    $msg = 'Đã tạo ' . basename($zip) . ' (' . number_format(filesize($zip) / 1048576, 1, ',', '.') . ' MB).';
    if (cfg('r2_backup') && r2_ready()) {
        $r = r2_backup_upload($zip, 'tu-dong');
        return ['ok' => $r['ok'], 'message' => $msg . ' ' . $r['message']];
    }
    return ['ok' => true, 'message' => $msg];
}
