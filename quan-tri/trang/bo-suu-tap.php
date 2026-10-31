<?php
/** Bộ sưu tập quà tặng theo dịp (trao dâu, mừng thọ…) */
defined('ADMIN') || exit;

$cols = content_load('bo-suu-tap');
$key  = (string) ($_GET['sua'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    $old = (string) ($_POST['key_old'] ?? '');
    if ($act === 'thu-tu') {
        content_save('bo-suu-tap', keyed_reorder($cols, in_list('thu-tu', 100)));
        adm_log('Sắp xếp bộ sưu tập');
        adm_back('bo-suu-tap', [], 'Đã lưu thứ tự bộ sưu tập.');
    }
    if ($act === 'xoa' && isset($cols[$old])) {
        unset($cols[$old]);
        content_save('bo-suu-tap', $cols);
        products_map(fn($p) => in_array($old, $p['dip'] ?? [], true) ? ['dip' => array_values(array_diff($p['dip'], [$old]))] + $p : $p);
        adm_log('Xóa bộ sưu tập', $old);
        adm_back('bo-suu-tap', [], 'Đã xóa bộ sưu tập.');
    }
    if ($act === 'luu') {
        $name = in_str('name', 120);
        $new  = make_slug(in_str('slug', 60) ?: $name);
        if (mb_strlen($name) < 2 || $new === '') {
            adm_back('bo-suu-tap', $old ? ['sua' => $old] : ['moi' => 1], 'Vui lòng nhập tên bộ sưu tập.', 'error');
        }
        if ($new !== $old && isset($cols[$new])) {
            adm_back('bo-suu-tap', $old ? ['sua' => $old] : ['moi' => 1], 'Mã “' . $new . '” đã được dùng.', 'error');
        }
        $item = array_merge($cols[$old] ?? [], ['name' => $name, 'short' => in_str('short', 160), 'desc' => in_str('desc', 1500), 'image' => clean_asset_path(in_str('image', 300))]);
        if (in_bool('hidden')) {
            $item['hidden'] = true;
        } else {
            unset($item['hidden']);
        }
        $cols = keyed_rename($cols, $old ?: $new, $new, $item);
        content_save('bo-suu-tap', $cols);
        // Gắn / gỡ sản phẩm vào bộ sưu tập
        $chosen = in_list('products', 120);
        products_map(function ($p) use ($old, $new, $chosen) {
            $dip = array_values(array_diff($p['dip'] ?? [], [$old, $new]));
            if (in_array($p['slug'], $chosen, true)) {
                $dip[] = $new;
            }
            return ['dip' => $dip] + $p;
        });
        adm_log($old ? 'Sửa bộ sưu tập' : 'Thêm bộ sưu tập', $new);
        adm_back('bo-suu-tap', ['sua' => $new], 'Đã lưu bộ sưu tập “' . $name . '”.');
    }
    adm_back('bo-suu-tap');
}

if ($key !== '' || !empty($_GET['moi'])) {
    if ($key !== '' && !isset($cols[$key])) {
        adm_back('bo-suu-tap', [], 'Không tìm thấy bộ sưu tập.', 'error');
    }
    $c = $cols[$key] ?? ['name' => '', 'short' => '', 'desc' => '', 'image' => ''];
    $prodOptions = [];
    $inCol = [];
    foreach (content_load('san-pham') as $p) {
        $prodOptions[$p['slug']] = $p['name'];
        if ($key !== '' && in_array($key, $p['dip'] ?? [], true)) {
            $inCol[] = $p['slug'];
        }
    }
    adm_head($key ? $c['name'] : 'Thêm bộ sưu tập', 'bo-suu-tap', $key ? '<a class="btn btn--sm btn--outline" href="' . e(collection_url($key)) . '" target="_blank" rel="noopener">' . adm_icon('external') . 'Xem trên website</a>' : '', [['Bộ sưu tập quà tặng', adm_url('bo-suu-tap')]]);
    ?>
    <form method="post" action="<?= e(adm_url('bo-suu-tap')) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu"><input type="hidden" name="key_old" value="<?= e($key) ?>">
      <section class="card">
        <div class="form-grid">
          <?= f_text('name', 'Tên bộ sưu tập', $c['name'], ['required' => true, 'placeholder' => 'VD: Quà mừng thọ ông bà, cha mẹ']) ?>
          <?= f_text('slug', 'Mã đường dẫn', $key, ['attrs' => 'data-slug-from="#f-name" spellcheck="false"', 'hint' => 'Địa chỉ: /qua-tang/<em>ma</em>' . ($key ? '. Mã này còn dùng trong “Chọn quà giúp tôi” (mục Ngũ hành &amp; quà tặng).' : '')]) ?>
          <?= f_text('short', 'Dòng giới thiệu ngắn (trên ảnh ở trang chủ)', $c['short'] ?? '', ['wide' => true]) ?>
          <?= f_textarea('desc', 'Mô tả', $c['desc'] ?? '', ['wide' => true, 'rows' => 4, 'autosize' => true]) ?>
          <?= f_image('image', 'Ảnh đại diện', (string) ($c['image'] ?? ''), ['wide' => true, 'folder' => 'bo-suu-tap', 'max' => 1200]) ?>
          <?= f_checks('products', 'Sản phẩm trong bộ sưu tập', $inCol, $prodOptions, ['wide' => true, 'hint' => 'Cũng chọn được trong trang sửa từng sản phẩm (mục “Dịp tặng”).']) ?>
          <?= f_switch('hidden', 'Tạm ẩn bộ sưu tập này', !empty($c['hidden']), ['wide' => true]) ?>
        </div>
      </section>
      <?= adm_savebar($key ? 'Lưu bộ sưu tập' : 'Thêm bộ sưu tập') ?>
    </form>
    <?php if ($key): ?><form method="post" action="<?= e(adm_url('bo-suu-tap')) ?>" data-confirm="Xóa bộ sưu tập “<?= e($c['name']) ?>”? Sản phẩm không bị xóa."><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="key_old" value="<?= e($key) ?>"><button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?>Xóa bộ sưu tập</button></form><?php endif; ?>
    <?php
    adm_foot();
    return;
}

adm_head('Bộ sưu tập quà tặng', 'bo-suu-tap', '<a class="btn btn--primary" href="' . e(adm_url('bo-suu-tap', ['moi' => 1])) . '">' . icon('plus') . 'Thêm bộ sưu tập</a>');
?>
<form method="post" action="<?= e(adm_url('bo-suu-tap')) ?>" data-track>
  <?= adm_csrf_field() ?><input type="hidden" name="act" value="thu-tu">
  <section class="card card--flush"><ul class="sort-list" data-sortable>
    <?php foreach ($cols as $k => $c): $n = count(array_filter(content_load('san-pham'), fn($p) => in_array($k, $p['dip'] ?? [], true))); ?>
      <?= adm_sort_row($k, !empty($c['image']) ? img_url($c['image']) : '', $c['name'], e($n . ' sản phẩm · ' . ($c['short'] ?? '')) . (!empty($c['hidden']) ? ' · <strong>đang ẩn</strong>' : ''), adm_url('bo-suu-tap', ['sua' => $k])) ?>
    <?php endforeach; ?>
  </ul></section>
  <?= adm_savebar('Lưu thứ tự') ?>
</form>
<?php
adm_foot();
