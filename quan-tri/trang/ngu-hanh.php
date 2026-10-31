<?php
/** Ngũ hành & quà tặng: gợi ý màu ngọc theo mệnh, khoảng giá bộ lọc, người nhận trong "Chọn quà giúp tôi" */
defined('ADMIN') || exit;

$data    = content_load('ngu-hanh');
$menh    = menh_info();
$ranges  = price_ranges();
$recips  = gift_recipients();
$dipNames = array_map(fn($c) => $c['name'], content_load('bo-suu-tap'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $m = [];
    foreach (menh_info_default() as $k => $def) {
        $m[$k] = [
            'color' => preg_match('/^#[0-9a-f]{6}$/i', in_str('m_color_' . $k, 7)) ? in_str('m_color_' . $k, 7) : $def['color'],
            'good'  => in_str('m_good_' . $k, 200) ?: $def['good'],
            'avoid' => in_str('m_avoid_' . $k, 200) ?: $def['avoid'],
            'jade'  => in_str('m_jade_' . $k, 200) ?: $def['jade'],
            'desc'  => in_str('m_desc_' . $k, 120) ?: $def['desc'],
        ];
    }
    $g = [];
    foreach ((array) ($_POST['r_label'] ?? []) as $i => $label) {
        $label = mb_substr(trim((string) $label), 0, 60);
        $key   = make_slug($label);
        if ($key === '' || isset($g[$key])) {
            continue;
        }
        $min = in_money('r_min_' . $i);
        $max = in_money('r_max_' . $i);
        $g[$key] = ['label' => $label, 'min' => max(1, $min), 'max' => $max > 0 ? max($max, $min) : 0];
    }
    $r = [];
    foreach ($recips as $k => $rec) {
        $r[$k] = ['label' => in_str('n_label_' . $k, 80) ?: $rec['label'], 'dip' => array_values(array_filter(in_list('n_dip_' . $k, 40), fn($d) => isset($dipNames[$d])))];
    }
    content_save('ngu-hanh', ['menh' => $m, 'gia' => $g, 'nguoi-nhan' => $r]);
    adm_log('Lưu ngũ hành & quà tặng');
    adm_back('ngu-hanh', [], 'Đã lưu.');
}

adm_head('Ngũ hành & quà tặng', 'ngu-hanh');
?>
<form method="post" action="<?= e(adm_url('ngu-hanh')) ?>" data-track>
  <?= adm_csrf_field() ?>
  <section class="card">
    <h2>Gợi ý theo mệnh</h2>
    <p class="card__sub" style="margin-top:4px">Hiện ở mục “Tra cứu mệnh” trang chủ, menu “Ngọc theo mệnh” và kết quả tra cứu năm sinh.</p>
    <?php foreach ($menh as $k => $x): ?>
      <p class="form-sec"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= e($x['color']) ?>;margin-right:6px"></span>Mệnh <?= e($x['name']) ?></p>
      <div class="form-grid" style="margin:10px 0 14px">
        <?= f_text('m_good_' . $k, 'Màu tương hợp', $x['good']) ?>
        <?= f_text('m_avoid_' . $k, 'Màu nên tránh', $x['avoid']) ?>
        <?= f_text('m_jade_' . $k, 'Gợi ý ngọc', $x['jade'], ['wide' => true]) ?>
        <?= f_text('m_desc_' . $k, 'Tính cách (ngắn)', $x['desc']) ?>
        <?= f_text('m_color_' . $k, 'Màu chấm', $x['color'], ['type' => 'color']) ?>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="card">
    <h2>Khoảng giá (bộ lọc sản phẩm, ngân sách chọn quà)</h2>
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th>Tên hiển thị</th><th>Từ (₫)</th><th>Đến (₫) – để trống = không giới hạn</th></tr></thead>
      <tbody>
      <?php $i = 0; foreach (array_slice($ranges, 0, 8) + array_fill(count($ranges), max(0, 6 - count($ranges)), ['label' => '', 'min' => 0, 'max' => 0]) as $rg): ?>
        <tr><td><input class="ai" type="text" name="r_label[]" value="<?= e($rg['label']) ?>" aria-label="Tên khoảng giá"></td>
          <td><input class="ai" type="text" inputmode="numeric" data-money name="r_min_<?= $i ?>" value="<?= $rg['min'] > 1 ? e(number_format((int) $rg['min'], 0, ',', '.')) : '' ?>" aria-label="Từ"></td>
          <td><input class="ai" type="text" inputmode="numeric" data-money name="r_max_<?= $i ?>" value="<?= $rg['max'] > 0 && $rg['max'] < PHP_INT_MAX ? e(number_format((int) $rg['max'], 0, ',', '.')) : '' ?>" aria-label="Đến"></td></tr>
      <?php $i++; endforeach; ?>
      </tbody>
    </table></div>
    <p class="af__hint">Xóa chữ ở ô “Tên hiển thị” để bỏ một khoảng giá.</p>
  </section>

  <section class="card">
    <h2>“Chọn quà giúp tôi” – người nhận</h2>
    <p class="card__sub" style="margin-top:4px">Mỗi người nhận gợi ý quà thuộc các bộ sưu tập (dịp) được chọn.</p>
    <div class="form-grid">
      <?php foreach ($recips as $k => $rec): ?>
        <?= f_text('n_label_' . $k, 'Người nhận', $rec['label']) ?>
        <?= f_checks('n_dip_' . $k, 'Dịp phù hợp', $rec['dip'] ?? [], $dipNames) ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?= adm_savebar('Lưu') ?>
</form>
<?php
adm_foot();
