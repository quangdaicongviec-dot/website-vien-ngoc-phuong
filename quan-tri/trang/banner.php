<?php
/** Banner trượt trang chủ: kiểu ảnh thiết kế sẵn (có ảnh dọc cho điện thoại) hoặc kiểu chữ + hình */
defined('ADMIN') || exit;

$banners = array_values(content_load('banner'));
$n = isset($_GET['sua']) ? (int) $_GET['sua'] : -1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    $i   = (int) ($_POST['i'] ?? -1);
    if ($act === 'thu-tu') {
        $order = array_map('intval', in_list('thu-tu', 5));
        $new = [];
        foreach ($order as $k) {
            if (isset($banners[$k])) {
                $new[] = $banners[$k];
            }
        }
        content_save('banner', $new);
        adm_log('Sắp xếp banner');
        adm_back('banner', [], 'Đã lưu thứ tự banner.');
    }
    if ($act === 'xoa' && isset($banners[$i])) {
        array_splice($banners, $i, 1);
        content_save('banner', $banners);
        adm_log('Xóa banner', (string) $i);
        adm_back('banner', [], 'Đã xóa banner.');
    }
    if ($act === 'luu') {
        $type = ($_POST['type'] ?? '') === 'image' ? 'image' : 'text';
        $b = $type === 'image'
            ? ['type' => 'image', 'title' => in_str('title', 200), 'image' => clean_asset_path(in_str('image', 300)), 'image_mobile' => clean_asset_path(in_str('image_mobile', 300)),
               'focus' => in_str('focus', 20) ?: '50% 50%', 'fill' => preg_match('/^#[0-9a-f]{3,8}$/i', in_str('fill', 9)) ? in_str('fill', 9) : '', 'link' => in_str('link', 300)]
            : ['eyebrow' => in_str('eyebrow', 80), 'title' => in_str('title', 200), 'text' => in_str('text', 400), 'button' => in_str('button', 60),
               'link' => in_str('link', 300), 'image' => clean_asset_path(in_str('image_text', 300)), 'background' => clean_asset_path(in_str('background', 300))];
        if (in_bool('hidden')) {
            $b['hidden'] = true;
        }
        if ($type === 'image' && $b['image'] === '') {
            adm_back('banner', $i >= 0 ? ['sua' => $i] : ['moi' => 1], 'Banner kiểu ảnh cần có ảnh cho máy tính.', 'error');
        }
        if ($b['title'] === '') {
            adm_back('banner', $i >= 0 ? ['sua' => $i] : ['moi' => 1], 'Vui lòng nhập tiêu đề / mô tả banner.', 'error');
        }
        if ($i >= 0 && isset($banners[$i])) {
            $banners[$i] = $b;
        } else {
            $banners[] = $b;
            $i = count($banners) - 1;
        }
        content_save('banner', $banners);
        adm_log('Lưu banner', $b['title']);
        adm_back('banner', ['sua' => $i], 'Đã lưu banner.' . ($type === 'image' && $b['image_mobile'] === '' ? ' Lưu ý: chưa có ảnh dọc cho điện thoại.' : ''));
    }
    adm_back('banner');
}

if ($n >= 0 || !empty($_GET['moi'])) {
    if ($n >= 0 && !isset($banners[$n])) {
        adm_back('banner', [], 'Không tìm thấy banner.', 'error');
    }
    $b = $banners[$n] ?? ['type' => 'image', 'title' => '', 'image' => '', 'image_mobile' => '', 'focus' => '50% 50%', 'fill' => '', 'link' => ''];
    $type = ($b['type'] ?? '') === 'image' ? 'image' : 'text';
    $first = array_values(array_filter($banners, fn($x) => ($x['type'] ?? '') === 'image'))[0] ?? null;
    $frame = $first ? image_size($first['image']) : null;
    adm_head($n >= 0 ? 'Sửa banner ' . ($n + 1) : 'Thêm banner', 'banner', '', [['Banner trang chủ', adm_url('banner')]]);
    ?>
    <form method="post" action="<?= e(adm_url('banner')) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu"><input type="hidden" name="i" value="<?= $n ?>">
      <section class="card">
        <div class="form-grid">
          <div class="af af--wide"><span class="af__label">Kiểu banner</span>
            <div class="checks">
              <label class="check"><input type="radio" name="type" value="image"<?= $type === 'image' ? ' checked' : '' ?>><span>Ảnh thiết kế sẵn (Canva…) – chữ nằm trong ảnh</span></label>
              <label class="check"><input type="radio" name="type" value="text"<?= $type === 'text' ? ' checked' : '' ?>><span>Chữ + hình sản phẩm (website tự dàn trang)</span></label>
            </div></div>
          <?= f_text('title', 'Tiêu đề', $b['title'] ?? '', ['wide' => true, 'required' => true, 'hint' => 'Kiểu ảnh: mô tả ngắn nội dung ảnh (cho Google và người khiếm thị, không hiện trên ảnh). Kiểu chữ: dòng chữ lớn trên banner.']) ?>
          <?= f_text('link', 'Bấm vào banner thì mở', $b['link'] ?? '', ['wide' => true, 'placeholder' => 'VD: san-pham?sap-xep=moi · danh-muc/vong-ngoc · san-pham/ma-san-pham · #tra-cuu-menh', 'hint' => 'Đường dẫn trong website (không cần tên miền), hoặc link đầy đủ https://…']) ?>

          <div class="af af--wide" data-show-if="type=image">
            <div class="form-grid">
              <?= f_image('image', 'Ảnh cho máy tính (ngang)', (string) ($b['image'] ?? ''), ['folder' => 'banner', 'max' => 2400, 'class' => 'imgf--wide', 'hint' => 'Nên 1920×942 px.' . ($frame ? ' Các banner ảnh nên cùng kích thước với banner ảnh đầu tiên (' . $frame[0] . '×' . $frame[1] . ').' : '')]) ?>
              <?= f_image('image_mobile', 'Ảnh dọc cho điện thoại', (string) ($b['image_mobile'] ?? ''), ['folder' => 'banner', 'max' => 1400, 'hint' => 'Nên 1080×1350 px (4:5). Thiếu ảnh này, banner sẽ tạm ẩn trên điện thoại cầm dọc.']) ?>
              <?= f_select('focus', 'Căn ảnh khi khung lệch tỉ lệ', $b['focus'] ?? '50% 50%', ['50% 50%' => 'Giữa', '50% 0%' => 'Trên', '50% 100%' => 'Dưới', '0% 50%' => 'Trái', '100% 50%' => 'Phải']) ?>
              <?= f_text('fill', 'Màu nền viền (nếu ảnh khác tỉ lệ)', $b['fill'] ?? '', ['type' => 'color', 'hint' => 'Hiếm khi cần.']) ?>
            </div>
          </div>
          <div class="af af--wide" data-show-if="type=text">
            <div class="form-grid">
              <?= f_text('eyebrow', 'Dòng nhỏ phía trên', $b['eyebrow'] ?? '', ['placeholder' => 'VD: Bộ sưu tập vòng ngọc']) ?>
              <?= f_text('button', 'Chữ trên nút', $b['button'] ?? '', ['placeholder' => 'VD: Khám phá vòng ngọc']) ?>
              <?= f_textarea('text', 'Đoạn mô tả', $b['text'] ?? '', ['wide' => true, 'rows' => 2]) ?>
              <?= f_image('image_text', 'Hình sản phẩm (nền trong suốt)', $type === 'text' ? (string) ($b['image'] ?? '') : '', ['folder' => 'banner', 'max' => 1400, 'hint' => 'Ảnh PNG/WEBP nền trong suốt, khoảng 900×700.']) ?>
              <?= f_image('background', 'Ảnh nền (không bắt buộc)', (string) ($b['background'] ?? ''), ['folder' => 'banner', 'max' => 2400]) ?>
            </div>
          </div>
          <?= f_switch('hidden', 'Tạm tắt banner này', !empty($b['hidden']), ['wide' => true]) ?>
        </div>
      </section>
      <?= adm_savebar($n >= 0 ? 'Lưu banner' : 'Thêm banner') ?>
    </form>
    <?php if ($n >= 0): ?><form method="post" action="<?= e(adm_url('banner')) ?>" data-confirm="Xóa banner này?"><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="i" value="<?= $n ?>"><button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?>Xóa banner</button></form><?php endif; ?>
    <?php
    adm_foot();
    return;
}

adm_head('Banner trang chủ', 'banner', '<a class="btn btn--primary" href="' . e(adm_url('banner', ['moi' => 1])) . '">' . icon('plus') . 'Thêm banner</a>');
echo adm_notice('Banner tự chuyển sau mỗi 4 giây. Kéo thả để đổi thứ tự rồi bấm “Lưu thứ tự”. Banner ảnh: máy tính dùng ảnh ngang 1920×942, điện thoại dùng ảnh dọc 1080×1350.');
?>
<form method="post" action="<?= e(adm_url('banner')) ?>" data-track>
  <?= adm_csrf_field() ?><input type="hidden" name="act" value="thu-tu">
  <section class="card card--flush"><ul class="sort-list" data-sortable>
    <?php foreach ($banners as $i => $b):
        $isImg = ($b['type'] ?? '') === 'image';
        $sub = ($isImg ? 'Ảnh thiết kế sẵn' : 'Chữ + hình') . ($isImg && empty($b['image_mobile']) ? ' · <strong style="color:#8a5300">thiếu ảnh điện thoại</strong>' : ($isImg ? ' · có ảnh điện thoại' : '')) . (!empty($b['hidden']) ? ' · <strong>đang tắt</strong>' : ''); ?>
      <?= adm_sort_row((string) $i, !empty($b['image']) ? img_url($b['image']) : '', $b['title'] ?: 'Banner ' . ($i + 1), $sub, adm_url('banner', ['sua' => $i])) ?>
    <?php endforeach; ?>
  </ul></section>
  <?= adm_savebar('Lưu thứ tự') ?>
</form>
<?php
adm_foot();
