<?php
/**
 * Sao lưu & khôi phục:
 *  - Tải 1 file .zip chứa toàn bộ dữ liệu (đơn hàng, phiếu, nội dung đã sửa, tài khoản, ảnh đã tải lên).
 *  - Khôi phục từ file .zip đã tải (tự sao lưu trạng thái hiện tại trước khi khôi phục).
 *  - Lịch sử thay đổi từng mục nội dung: quay lại bản đã lưu trước đó.
 */
defined('ADMIN') || exit;

$names = ['san-pham' => 'Sản phẩm', 'danh-muc' => 'Danh mục', 'kiem-dinh' => 'Giấy kiểm định', 'bo-suu-tap' => 'Bộ sưu tập', 'banner' => 'Banner',
          'bai-viet' => 'Bài viết', 'chinh-sach' => 'Chính sách', 'khach-hang' => 'Khách đeo thật', 'cai-dat' => 'Cài đặt', 'van-ban' => 'Chữ trên các trang', 'ngu-hanh' => 'Ngũ hành & quà tặng', 'tai-lieu' => 'Hướng dẫn & tài liệu'];

require_once ROOT . '/includes/sao-luu.php';   // backup_files(), backup_make(), backup_allowed(), backup_auto_list()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    if ($act === 'tai') {
        $tmp = tempnam(sys_get_temp_dir(), 'vnp');
        if (!$tmp || backup_make($tmp) === '') {
            adm_back('sao-luu', [], 'Hosting chưa hỗ trợ nén file (ZipArchive). Hãy sao lưu thư mục data/ và assets/img/uploads/ bằng cPanel → File Manager → Compress.', 'error');
        }
        adm_log('Tải bản sao lưu');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="sao-luu-' . make_slug(cfg('site_name')) . '-' . date('Ymd-Hi') . '.zip"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }
    if ($act === 'tai-ban') {
        $name = basename((string) ($_POST['ban'] ?? ''));
        $file = backup_dir() . '/' . $name;
        if (!preg_match('/^tu-dong-\d{8}\.zip$/', $name) || !is_file($file)) {
            adm_back('sao-luu', [], 'Không tìm thấy bản sao lưu.', 'error');
        }
        adm_log('Tải bản sao lưu tự động', $name);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="sao-luu-' . make_slug(cfg('site_name')) . '-' . substr($name, 8, 8) . '.zip"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
    if ($act === 'khoi-phuc') {
        if (!class_exists('ZipArchive') || empty($_FILES['file']) || ($_FILES['file']['error'] ?? 1) !== UPLOAD_ERR_OK) {
            adm_back('sao-luu', [], !empty($_FILES['file']) ? upload_error_text((int) $_FILES['file']['error']) : 'Chưa chọn file sao lưu.', 'error');
        }
        $zip = new ZipArchive();
        if ($zip->open($_FILES['file']['tmp_name']) !== true) {
            adm_back('sao-luu', [], 'File không phải bản sao lưu .zip hợp lệ.', 'error');
        }
        $ok = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $rel = (string) $zip->getNameIndex($i);
            if (substr($rel, -1) !== '/' && backup_allowed($rel)) {
                $ok[] = $rel;
            }
        }
        if (!$ok) {
            $zip->close();
            adm_back('sao-luu', [], 'File này không chứa dữ liệu website (data/…, assets/img/uploads/…).', 'error');
        }
        // Sao lưu trạng thái hiện tại trước khi ghi đè
        backup_make(backup_dir() . '/truoc-khoi-phuc-' . date('Ymd-His') . '.zip');
        $keepUsers = !in_bool('ca-tai-khoan');
        $n = 0;
        foreach ($ok as $rel) {
            if ($keepUsers && preg_match('#^data/quan-tri/(tai-khoan|dang-nhap-sai|bi-mat)\.json$#', $rel)) {
                continue;
            }
            $dest = ROOT . '/' . $rel;
            if (!is_dir(dirname($dest))) {
                @mkdir(dirname($dest), 0755, true);
            }
            if (@file_put_contents($dest, $zip->getFromName($rel)) !== false) {
                $n++;
            }
        }
        $zip->close();
        adm_log('Khôi phục từ bản sao lưu', $n . ' tệp');
        adm_back('sao-luu', [], 'Đã khôi phục ' . $n . ' tệp. Bản trước khi khôi phục được cất trong data/quan-tri/sao-luu/.');
    }
    if ($act === 'quay-lai') {
        $name = (string) ($_POST['muc'] ?? '');
        $id   = basename((string) ($_POST['ban'] ?? ''));
        $file = content_dir() . '/lich-su/' . $id . '.json';
        $data = isset($names[$name]) && strpos($id, $name . '-') === 0 && is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (!is_array($data)) {
            adm_back('sao-luu', [], 'Không tìm thấy bản đã lưu.', 'error');
        }
        content_save($name, $data);
        adm_log('Quay lại bản cũ', $names[$name] . ' – ' . $id);
        adm_back('sao-luu', [], 'Đã quay lại bản lưu lúc ' . date('H:i d/m/Y', (int) ($_POST['luc'] ?? time())) . ' của mục “' . $names[$name] . '”. (Bản vừa thay thế cũng được cất trong lịch sử.)');
    }
    adm_back('sao-luu');
}

$files = backup_files();
$size  = array_sum(array_map('filesize', $files));
adm_head('Sao lưu & khôi phục', 'sao-luu');
?>
<div class="grid-2">
  <section class="card">
    <h2>Tải bản sao lưu</h2>
    <p class="card__sub" style="margin-top:4px">Một file .zip gồm đơn hàng, phiếu xác nhận, lịch hẹn, nội dung đã sửa, cài đặt, tài khoản và ảnh đã tải lên (<?= count($files) ?> tệp, khoảng <?= e(number_format($size / 1048576, 1, ',', '.')) ?> MB). Nên tải mỗi tuần và cất ở nơi an toàn (Google Drive…).</p>
    <form method="post" action="<?= e(adm_url('sao-luu')) ?>"><?= adm_csrf_field() ?><input type="hidden" name="act" value="tai">
      <button type="submit" class="btn btn--primary"><?= icon('download') ?>Tải bản sao lưu (.zip)</button></form>
    <?= adm_notice('File sao lưu có thông tin khách hàng và mật khẩu đã mã hóa – không gửi cho người lạ.', 'warn') ?>
  </section>
  <section class="card">
    <h2>Khôi phục từ file</h2>
    <p class="card__sub" style="margin-top:4px">Chọn file .zip đã tải từ trang này. Dữ liệu hiện tại sẽ được tự sao lưu trước khi ghi đè. Chỉ khôi phục dữ liệu và ảnh – không ghi đè mã nguồn website.</p>
    <form method="post" action="<?= e(adm_url('sao-luu')) ?>" enctype="multipart/form-data" class="stack" data-confirm="Khôi phục dữ liệu từ file này? Dữ liệu hiện tại sẽ bị thay bằng dữ liệu trong file (đã tự sao lưu trước).">
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="khoi-phuc">
      <input class="ai" type="file" name="file" accept=".zip,application/zip" required>
      <label class="check"><input type="checkbox" name="ca-tai-khoan" value="1"><span>Khôi phục cả tài khoản đăng nhập (và mật khẩu SMTP, khóa R2) trong file</span></label>
      <button type="submit" class="btn btn--outline"><?= adm_icon('upload') ?>Khôi phục</button>
    </form>
  </section>
</div>

<?php $auto = backup_auto_list(); $r2st = r2_status()['backup'] ?? null; ?>
<section class="card">
  <h2>Sao lưu tự động mỗi đêm</h2>
  <p class="card__sub" style="margin-top:4px">Máy chủ tự sao lưu toàn bộ dữ liệu lúc khoảng 2 giờ sáng và giữ 7 bản gần nhất<?= cfg('r2_backup') && r2_ready() ? ', đồng thời gửi lên Cloudflare R2 (giữ 31 bản)' : '' ?>. <?= !r2_ready() ? 'Kết nối <a href="' . e(adm_url('cai-dat', ['nhom' => 'luu-tru'])) . '">Cloudflare R2</a> để có thêm 1 bản cất ở nơi khác phòng khi máy chủ hỏng.' : '' ?></p>
  <?php if ($r2st): ?><p style="margin-top:8px">Lần gửi lên R2 gần nhất: <?= e(adm_time((int) $r2st['time'])) ?> – <?= !empty($r2st['ok']) ? adm_badge('Thành công', 'ok') : adm_badge('Lỗi', 'danger') ?> <?= e((string) $r2st['message']) ?></p><?php endif; ?>
  <?php if ($auto): ?>
    <ul class="todo" style="margin-top:8px">
      <?php foreach ($auto as $name => $b): ?>
        <li><span class="todo__icon todo__icon--info"><?= adm_icon('database') ?></span><div class="todo__body"><strong>Bản lúc <?= e(date('H:i d/m/Y', $b['time'])) ?></strong><p><?= e(number_format($b['size'] / 1048576, 1, ',', '.')) ?> MB</p></div>
          <form method="post" action="<?= e(adm_url('sao-luu')) ?>"><?= adm_csrf_field() ?><input type="hidden" name="act" value="tai-ban"><input type="hidden" name="ban" value="<?= e($name) ?>"><button type="submit" class="btn btn--sm btn--outline"><?= icon('download') ?>Tải về</button></form></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?><p class="muted" style="margin-top:8px">Chưa có bản sao lưu tự động nào (bản đầu tiên sẽ có sau 2 giờ sáng).</p><?php endif; ?>
</section>

<section class="card">
  <h2>Lịch sử thay đổi nội dung</h2>
  <p class="card__sub" style="margin-top:4px">Mỗi lần bấm Lưu, bản trước đó được cất lại (30 bản gần nhất mỗi mục). Lỡ tay xóa / sửa sai thì quay lại bản cũ ở đây.</p>
  <?php $any = false; foreach ($names as $k => $label): $hist = content_history($k); if (!$hist) continue; $any = true; ?>
    <details style="border-top:1px solid var(--c-border);padding:10px 0">
      <summary style="cursor:pointer;font-weight:600"><?= e($label) ?> <span class="muted">· <?= count($hist) ?> bản · gần nhất <?= e(adm_ago($hist[0]['time'])) ?></span></summary>
      <ul class="todo" style="margin-top:8px">
        <?php foreach (array_slice($hist, 0, 15) as $h): ?>
          <li><span class="todo__icon todo__icon--info"><?= adm_icon('history') ?></span><div class="todo__body"><strong>Bản lưu lúc <?= e(date('H:i:s d/m/Y', $h['time'])) ?></strong></div>
            <form method="post" action="<?= e(adm_url('sao-luu')) ?>" data-confirm="Quay lại bản lưu lúc <?= e(date('H:i d/m/Y', $h['time'])) ?> của mục “<?= e($label) ?>”?"><?= adm_csrf_field() ?><input type="hidden" name="act" value="quay-lai"><input type="hidden" name="muc" value="<?= e($k) ?>"><input type="hidden" name="ban" value="<?= e($h['id']) ?>"><input type="hidden" name="luc" value="<?= (int) $h['time'] ?>"><button type="submit" class="btn btn--sm btn--outline">Quay lại bản này</button></form></li>
        <?php endforeach; ?>
      </ul>
    </details>
  <?php endforeach; ?>
  <?php if (!$any): ?><p class="muted">Chưa có thay đổi nào.</p><?php endif; ?>
</section>
<?php
adm_foot();
