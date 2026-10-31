<?php
/** Chữ trên các trang (tiêu đề, đoạn giới thiệu, cam kết…) – chữ mặc định nằm trong includes/van-ban.php */
defined('ADMIN') || exit;

$reg    = text_registry();
$over   = content_load('van-ban');
$groups = [];
foreach ($reg as $k => $r) {
    $groups[$r['group']][$k] = $r;
}
$names = array_keys($groups);
$g     = (string) ($_GET['nhom'] ?? $names[0]);
$g     = isset($groups[$g]) ? $g : $names[0];
$field = fn(string $k) => 't_' . str_replace('.', '__', $k);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($groups[$g] as $k => $r) {
        $v = $r['type'] === 'image' ? clean_asset_path(in_str($field($k), 300)) : in_str($field($k), 4000);
        if ($v === '' || $v === $r['default'] || in_bool('mac-dinh_' . $field($k))) {
            unset($over[$k]);                       // để trống / bấm "Dùng chữ mặc định" → chữ gốc
        } else {
            $over[$k] = $v;
        }
    }
    content_save('van-ban', $over);
    adm_log('Sửa chữ trên các trang', $g);
    adm_back('van-ban', ['nhom' => $g], 'Đã lưu “' . $g . '”.');
}

adm_head('Chữ trên các trang', 'van-ban');
?>
<nav class="adm-tabs" aria-label="Nhóm"><?php foreach ($names as $n): ?><a href="<?= e(adm_url('van-ban', ['nhom' => $n])) ?>"<?= $n === $g ? ' aria-current="page"' : '' ?>><?= e($n) ?></a><?php endforeach; ?></nav>
<?= adm_notice('Để trống một ô (hoặc tick “Dùng chữ mặc định”) để quay về chữ gốc. Có thể dùng <code>{SITE_NAME}</code> để tự điền tên cửa hàng. Ô nhiều dòng: để <strong>1 dòng trống</strong> giữa các đoạn; ô danh sách: <strong>mỗi dòng 1 mục</strong>.') ?>
<form method="post" action="<?= e(adm_url('van-ban', ['nhom' => $g])) ?>" data-track>
  <?= adm_csrf_field() ?>
  <section class="card">
    <div class="form-grid">
      <?php foreach ($groups[$g] as $k => $r):
          $val  = $over[$k] ?? $r['default'];
          $name = $field($k);
          $hint = isset($over[$k]) ? '<label class="check" style="border:0;padding:0;margin-top:6px"><input type="checkbox" name="mac-dinh_' . e($name) . '" value="1"><span>Dùng chữ mặc định: “' . e(str_limit($r['default'], 120)) . '”</span></label>' : '';
          echo match ($r['type']) {
              'area'  => f_textarea($name, $r['label'], $val, ['wide' => true, 'rows' => 4, 'autosize' => true, 'hint' => $hint]),
              'lines' => f_textarea($name, $r['label'] . ' (mỗi dòng 1 mục)', $val, ['wide' => true, 'rows' => 4, 'autosize' => true, 'hint' => $hint]),
              'image' => f_image($name, $r['label'], $val, ['wide' => true, 'folder' => 'trang', 'max' => 1600]),
              default => f_text($name, $r['label'], $val, ['wide' => mb_strlen($r['default']) > 50, 'hint' => $hint]),
          };
      endforeach; ?>
    </div>
  </section>
  <?= adm_savebar('Lưu ' . mb_strtolower($g)) ?>
</form>
<?php
adm_foot();
