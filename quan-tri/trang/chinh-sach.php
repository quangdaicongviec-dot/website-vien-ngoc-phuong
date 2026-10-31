<?php
/** Chính sách cửa hàng (bảo hành, đổi trả, vận chuyển, thanh toán, đặt cọc, bảo mật…) */
defined('ADMIN') || exit;

$pols = content_load('chinh-sach');
$key  = (string) ($_GET['sua'] ?? '');
$fixed = ['bao-hanh', 'doi-tra', 'van-chuyen', 'thanh-toan', 'dat-coc', 'bao-mat'];   // có link cố định ở chân trang / giỏ hàng

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    $old = (string) ($_POST['key_old'] ?? '');
    if ($act === 'thu-tu') {
        content_save('chinh-sach', keyed_reorder($pols, in_list('thu-tu', 60)));
        adm_log('Sắp xếp chính sách');
        adm_back('chinh-sach', [], 'Đã lưu thứ tự chính sách.');
    }
    if ($act === 'xoa' && isset($pols[$old]) && !in_array($old, $fixed, true)) {
        unset($pols[$old]);
        content_save('chinh-sach', $pols);
        adm_log('Xóa chính sách', $old);
        adm_back('chinh-sach', [], 'Đã xóa mục chính sách.');
    }
    if ($act === 'luu') {
        $title = in_str('title', 120);
        $new   = in_array($old, $fixed, true) ? $old : make_slug(in_str('slug', 60) ?: $title);
        if (mb_strlen($title) < 3 || $new === '') {
            adm_back('chinh-sach', $old ? ['sua' => $old] : ['moi' => 1], 'Vui lòng nhập tên chính sách.', 'error');
        }
        if ($new !== $old && isset($pols[$new])) {
            adm_back('chinh-sach', $old ? ['sua' => $old] : ['moi' => 1], 'Mã “' . $new . '” đã được dùng.', 'error');
        }
        $pols = keyed_rename($pols, $old ?: $new, $new, ['title' => $title, 'content' => clean_html((string) ($_POST['content'] ?? ''))]);
        content_save('chinh-sach', $pols);
        // Chính sách đặt cọc: cập nhật số phiên bản (in trên phiếu cọc, khách đồng ý theo phiên bản)
        if ($new === 'dat-coc' && ($ver = in_str('version', 60)) !== '' && $ver !== cfg('deposit_policy_version')) {
            $set = content_load('cai-dat');
            $set['deposit_policy_version'] = $ver;
            content_save('cai-dat', $set);
        }
        adm_log($old ? 'Sửa chính sách' : 'Thêm chính sách', $new);
        adm_back('chinh-sach', ['sua' => $new], 'Đã lưu “' . $title . '”.');
    }
    adm_back('chinh-sach');
}

$codes = '<code>{SITE_NAME}</code> tên cửa hàng · <code>{HOTLINE}</code> · <code>{EMAIL}</code> · <code>{ADDRESS}</code> địa chỉ · <code>{GIA_GIU_TU}</code> giá từ được giữ hàng · <code>{PHAN_TRAM_COC}</code> % cọc · '
    . '<code>{PHUT_CHO_COC}</code> phút chờ cọc · <code>{GIO_GIU}</code> giờ giữ · <code>{GIO_GIA_HAN}</code> giờ gia hạn · <code>{THANG_PHIEU_MUA}</code> tháng dùng phiếu mua hàng · '
    . '<code>{NGAY_HOAN_TIEN}</code> ngày hoàn tiền · <code>{PHUT_CHO_CK}</code> phút giữ món chờ chuyển khoản · <code>{PHIEN_BAN_COC}</code> phiên bản chính sách cọc';

if ($key !== '' || !empty($_GET['moi'])) {
    if ($key !== '' && !isset($pols[$key])) {
        adm_back('chinh-sach', [], 'Không tìm thấy mục chính sách.', 'error');
    }
    $p = $pols[$key] ?? ['title' => '', 'content' => ''];
    adm_head($key ? $p['title'] : 'Thêm chính sách', 'chinh-sach', '<a class="btn btn--sm btn--outline" href="' . e(url('chinh-sach') . ($key ? '#' . $key : '')) . '" target="_blank" rel="noopener">' . adm_icon('external') . 'Xem trên website</a>', [['Chính sách', adm_url('chinh-sach')]]);
    if ($key === 'dat-coc') {
        echo adm_notice('Khách phải đánh dấu đồng ý chính sách này khi giữ hàng; phiếu đặt cọc ghi lại <strong>phiên bản</strong> khách đã đồng ý. Khi sửa nội dung, hãy tăng số phiên bản bên dưới. Các con số (giờ giữ, % cọc…) sửa trong <a href="' . e(adm_url('cai-dat', ['nhom' => 'giu-hang'])) . '">Cài đặt → Giữ hàng</a> – chính sách tự cập nhật theo.', 'warn');
    }
    ?>
    <form method="post" action="<?= e(adm_url('chinh-sach')) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu"><input type="hidden" name="key_old" value="<?= e($key) ?>">
      <section class="card">
        <div class="form-grid">
          <?= f_text('title', 'Tên chính sách', $p['title'], ['required' => true]) ?>
          <?php if (in_array($key, $fixed, true)): ?>
            <?= $key === 'dat-coc' ? f_text('version', 'Phiên bản chính sách', (string) cfg('deposit_policy_version'), ['hint' => 'VD: 1.1 – 01/11/2026']) : '<div></div>' ?>
          <?php else: ?>
            <?= f_text('slug', 'Mã (dùng làm #neo trên trang Chính sách)', $key, ['attrs' => 'data-slug-from="#f-title" spellcheck="false"']) ?>
          <?php endif; ?>
          <?= f_editor('content', 'Nội dung', (string) $p['content'], ['folder' => 'chinh-sach', 'hint' => 'Mã tự thay bằng thông tin thật: ' . $codes]) ?>
        </div>
      </section>
      <?= adm_savebar('Lưu chính sách') ?>
    </form>
    <?php if ($key && !in_array($key, $fixed, true)): ?><form method="post" action="<?= e(adm_url('chinh-sach')) ?>" data-confirm="Xóa mục “<?= e($p['title']) ?>”?"><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="key_old" value="<?= e($key) ?>"><button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?>Xóa mục này</button></form><?php endif; ?>
    <?php
    adm_foot();
    return;
}

adm_head('Chính sách', 'chinh-sach', '<a class="btn btn--primary" href="' . e(adm_url('chinh-sach', ['moi' => 1])) . '">' . icon('plus') . 'Thêm mục</a>');
echo adm_notice('Các chính sách hiện trên trang <a href="' . e(url('chinh-sach')) . '" target="_blank" rel="noopener">Chính sách</a> theo thứ tự dưới đây. Nên nhờ luật sư / kế toán đọc lại trước khi bán chính thức.');
?>
<form method="post" action="<?= e(adm_url('chinh-sach')) ?>" data-track>
  <?= adm_csrf_field() ?><input type="hidden" name="act" value="thu-tu">
  <section class="card card--flush"><ul class="sort-list" data-sortable>
    <?php foreach ($pols as $k => $p): ?>
      <?= adm_sort_row($k, '', $p['title'], e('#' . $k . ' · ' . str_limit((string) $p['content'], 90)), adm_url('chinh-sach', ['sua' => $k])) ?>
    <?php endforeach; ?>
  </ul></section>
  <?= adm_savebar('Lưu thứ tự') ?>
</form>
<?php
adm_foot();
