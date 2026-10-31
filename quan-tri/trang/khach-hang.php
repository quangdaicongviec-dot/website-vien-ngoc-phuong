<?php
/** Khách đeo thật: ảnh & cảm nhận khách hàng (trang chủ) */
defined('ADMIN') || exit;

$items = array_values(content_load('khach-hang'));
$n = isset($_GET['sua']) ? (int) $_GET['sua'] : -1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    $i   = (int) ($_POST['i'] ?? -1);
    if ($act === 'thu-tu') {
        $new = [];
        foreach (array_map('intval', in_list('thu-tu', 5)) as $k) {
            if (isset($items[$k])) {
                $new[] = $items[$k];
            }
        }
        content_save('khach-hang', $new);
        adm_back('khach-hang', [], 'Đã lưu thứ tự.');
    }
    if ($act === 'xoa' && isset($items[$i])) {
        array_splice($items, $i, 1);
        content_save('khach-hang', $items);
        adm_log('Xóa cảm nhận khách');
        adm_back('khach-hang', [], 'Đã xóa.');
    }
    if ($act === 'luu') {
        $f = ['name' => in_str('name', 80), 'place' => in_str('place', 80), 'product' => in_str('product', 160),
              'image' => clean_asset_path(in_str('image', 300)), 'content' => in_str('content', 1200)];
        if (in_bool('hidden')) {
            $f['hidden'] = true;
        }
        if ($f['name'] === '' || $f['content'] === '') {
            adm_back('khach-hang', $i >= 0 ? ['sua' => $i] : ['moi' => 1], 'Vui lòng nhập tên khách và nội dung cảm nhận.', 'error');
        }
        if ($i >= 0 && isset($items[$i])) {
            $items[$i] = $f;
        } else {
            $items[] = $f;
            $i = count($items) - 1;
        }
        content_save('khach-hang', $items);
        adm_log('Lưu cảm nhận khách', $f['name']);
        adm_back('khach-hang', [], 'Đã lưu cảm nhận của ' . $f['name'] . '.');
    }
    adm_back('khach-hang');
}

if ($n >= 0 || !empty($_GET['moi'])) {
    $f = $items[$n] ?? ['name' => '', 'place' => '', 'product' => '', 'image' => '', 'content' => ''];
    adm_head($n >= 0 ? 'Sửa cảm nhận' : 'Thêm cảm nhận khách', 'khach-hang', '', [['Khách đeo thật', adm_url('khach-hang')]]);
    echo adm_notice('Chỉ đăng ảnh và cảm nhận <strong>thật</strong>, đã được khách đồng ý.');
    ?>
    <form method="post" action="<?= e(adm_url('khach-hang')) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu"><input type="hidden" name="i" value="<?= $n ?>">
      <section class="card"><div class="form-grid">
        <?= f_text('name', 'Tên khách (hiển thị)', $f['name'], ['required' => true, 'placeholder' => 'VD: Chị Lan']) ?>
        <?= f_text('place', 'Nơi ở', $f['place'] ?? '', ['placeholder' => 'VD: TP. Hồ Chí Minh']) ?>
        <?= f_text('product', 'Món đã mua', $f['product'] ?? '', ['wide' => true, 'placeholder' => 'VD: Vòng ngọc trắng băng']) ?>
        <?= f_textarea('content', 'Cảm nhận của khách', $f['content'], ['wide' => true, 'rows' => 4, 'required' => true]) ?>
        <?= f_image('image', 'Ảnh khách đeo (ảnh dọc 4:5)', (string) ($f['image'] ?? ''), ['wide' => true, 'folder' => 'khach-hang', 'max' => 1200]) ?>
        <?= f_switch('hidden', 'Tạm ẩn', !empty($f['hidden']), ['wide' => true]) ?>
      </div></section>
      <?= adm_savebar('Lưu') ?>
    </form>
    <?php if ($n >= 0): ?><form method="post" action="<?= e(adm_url('khach-hang')) ?>" data-confirm="Xóa cảm nhận này?"><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="i" value="<?= $n ?>"><button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?>Xóa</button></form><?php endif; ?>
    <?php
    adm_foot();
    return;
}

adm_head('Khách đeo thật', 'khach-hang', '<a class="btn btn--primary" href="' . e(adm_url('khach-hang', ['moi' => 1])) . '">' . icon('plus') . 'Thêm cảm nhận</a>');
echo adm_notice('Mục “Ngọc trên tay khách hàng” ở trang chủ chỉ hiện khi có ít nhất 1 cảm nhận.');
?>
<form method="post" action="<?= e(adm_url('khach-hang')) ?>" data-track>
  <?= adm_csrf_field() ?><input type="hidden" name="act" value="thu-tu">
  <section class="card card--flush">
    <?php if (!$items): ?><div class="empty"><?= icon('heart') ?><p>Chưa có cảm nhận nào. Bấm “Thêm cảm nhận” khi khách gửi ảnh đeo thật.</p></div><?php else: ?>
    <ul class="sort-list" data-sortable>
      <?php foreach ($items as $i => $f): ?>
        <?= adm_sort_row((string) $i, !empty($f['image']) ? img_url($f['image']) : '', $f['name'] . (!empty($f['place']) ? ' · ' . $f['place'] : ''), e(str_limit($f['content'], 100)) . (!empty($f['hidden']) ? ' · <strong>đang ẩn</strong>' : ''), adm_url('khach-hang', ['sua' => $i])) ?>
      <?php endforeach; ?>
    </ul><?php endif; ?>
  </section>
  <?php if ($items): ?><?= adm_savebar('Lưu thứ tự') ?><?php endif; ?>
</form>
<?php
adm_foot();
