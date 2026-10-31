<?php
/** Danh mục sản phẩm: tên, mô tả, ảnh đại diện, thứ tự trên menu */
defined('ADMIN') || exit;

$cats = content_load('danh-muc');
$key  = (string) ($_GET['sua'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    if ($act === 'thu-tu') {
        content_save('danh-muc', keyed_reorder($cats, in_list('thu-tu', 100)));
        adm_log('Sắp xếp danh mục');
        adm_back('danh-muc', [], 'Đã lưu thứ tự danh mục (menu, trang chủ, bộ lọc).');
    }
    $old = (string) ($_POST['key_old'] ?? '');
    if ($act === 'xoa' && isset($cats[$old])) {
        $n = count(array_filter(content_load('san-pham'), fn($p) => $p['category'] === $old));
        if ($n) {
            adm_back('danh-muc', ['sua' => $old], 'Không xóa được: còn ' . $n . ' sản phẩm trong danh mục này. Hãy chuyển sản phẩm sang danh mục khác trước.', 'error');
        }
        unset($cats[$old]);
        content_save('danh-muc', $cats);
        adm_log('Xóa danh mục', $old);
        adm_back('danh-muc', [], 'Đã xóa danh mục.');
    }
    if ($act === 'luu') {
        $name = in_str('name', 80);
        $new  = make_slug(in_str('slug', 60) ?: $name);
        if (mb_strlen($name) < 2 || $new === '') {
            adm_back('danh-muc', $old ? ['sua' => $old] : ['moi' => 1], 'Vui lòng nhập tên danh mục.', 'error');
        }
        if ($new !== $old && isset($cats[$new])) {
            adm_back('danh-muc', $old ? ['sua' => $old] : ['moi' => 1], 'Mã “' . $new . '” đã có danh mục khác dùng.', 'error');
        }
        $item = array_merge($cats[$old] ?? [], ['name' => $name, 'desc' => in_str('desc', 600), 'image' => clean_asset_path(in_str('image', 300))]);
        $cats = keyed_rename($cats, $old ?: $new, $new, $item);
        content_save('danh-muc', $cats);
        if ($old !== '' && $new !== $old) {
            products_map(fn($p) => $p['category'] === $old ? ['category' => $new] + $p : $p);
        }
        adm_log($old ? 'Sửa danh mục' : 'Thêm danh mục', $new);
        adm_back('danh-muc', ['sua' => $new], 'Đã lưu danh mục “' . $name . '”.');
    }
    adm_back('danh-muc');
}

if ($key !== '' || !empty($_GET['moi'])) {
    $c = $cats[$key] ?? ['name' => '', 'desc' => '', 'image' => ''];
    if ($key !== '' && !isset($cats[$key])) {
        adm_back('danh-muc', [], 'Không tìm thấy danh mục.', 'error');
    }
    $count = $key !== '' ? count(array_filter(content_load('san-pham'), fn($p) => $p['category'] === $key)) : 0;
    adm_head($key ? $c['name'] : 'Thêm danh mục', 'danh-muc', $key ? '<a class="btn btn--sm btn--outline" href="' . e(category_url($key)) . '" target="_blank" rel="noopener">' . adm_icon('external') . 'Xem trên website</a>' : '', [['Danh mục', adm_url('danh-muc')]]);
    ?>
    <form method="post" action="<?= e(adm_url('danh-muc')) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu"><input type="hidden" name="key_old" value="<?= e($key) ?>">
      <section class="card">
        <div class="form-grid">
          <?= f_text('name', 'Tên danh mục', $c['name'], ['required' => true, 'placeholder' => 'VD: Vòng tay ngọc']) ?>
          <?= f_text('slug', 'Mã đường dẫn', $key, ['attrs' => 'data-slug-from="#f-name" spellcheck="false"', 'hint' => 'Địa chỉ: /danh-muc/<em>ma</em>' . ($key ? ' · Đổi mã sẽ tự cập nhật ' . $count . ' sản phẩm.' : '') . ($key === 'vong-ngoc' || $key === 'nhan-ngoc' || $key === 'chuoi-hat' ? '<br><strong>Lưu ý:</strong> giữ nguyên mã này để công cụ tính ni vòng / ni nhẫn / cỡ chuỗi hoạt động.' : '')]) ?>
          <?= f_textarea('desc', 'Mô tả (hiện đầu trang danh mục)', $c['desc'] ?? '', ['wide' => true, 'rows' => 3, 'autosize' => true]) ?>
          <?= f_image('image', 'Ảnh đại diện (ô danh mục ở trang chủ)', (string) ($c['image'] ?? ''), ['wide' => true, 'folder' => 'danh-muc', 'max' => 1000, 'hint' => 'Ảnh vuông, nền sáng.']) ?>
        </div>
      </section>
      <?= adm_savebar($key ? 'Lưu danh mục' : 'Thêm danh mục') ?>
    </form>
    <?php if ($key): ?>
      <form method="post" action="<?= e(adm_url('danh-muc')) ?>" data-confirm="Xóa danh mục “<?= e($c['name']) ?>”?"><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="key_old" value="<?= e($key) ?>"><button type="submit" class="btn btn--danger btn--sm"<?= $count ? ' disabled title="Còn sản phẩm"' : '' ?>><?= icon('trash') ?>Xóa danh mục</button><?php if ($count): ?> <span class="muted">Còn <?= $count ?> sản phẩm – không xóa được.</span><?php endif; ?></form>
    <?php endif; ?>
    <?php
    adm_foot();
    return;
}

adm_head('Danh mục', 'danh-muc', '<a class="btn btn--primary" href="' . e(adm_url('danh-muc', ['moi' => 1])) . '">' . icon('plus') . 'Thêm danh mục</a>');
echo adm_notice('Thứ tự dưới đây là thứ tự trên menu, trang chủ và bộ lọc. Kéo thả hoặc bấm mũi tên rồi bấm “Lưu thứ tự”.');
?>
<form method="post" action="<?= e(adm_url('danh-muc')) ?>" data-track>
  <?= adm_csrf_field() ?><input type="hidden" name="act" value="thu-tu">
  <section class="card card--flush"><ul class="sort-list" data-sortable>
    <?php foreach ($cats as $k => $c): $n = count(array_filter(content_load('san-pham'), fn($p) => $p['category'] === $k)); ?>
      <?= adm_sort_row($k, !empty($c['image']) ? img_url($c['image']) : '', $c['name'], e($n . ' sản phẩm · /danh-muc/' . $k), adm_url('danh-muc', ['sua' => $k])) ?>
    <?php endforeach; ?>
  </ul></section>
  <?= adm_savebar('Lưu thứ tự') ?>
</form>
<?php
adm_foot();
