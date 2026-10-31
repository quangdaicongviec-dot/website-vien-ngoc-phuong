<?php
/** Giấy kiểm định: mã tra cứu, kết luận, đơn vị kiểm định, ảnh giấy, sản phẩm tương ứng */
defined('ADMIN') || exit;

$certs = content_load('kiem-dinh');
$key   = (string) ($_GET['sua'] ?? '');
$productOptions = ['' => '– Không gắn sản phẩm (VD: món đã bán) –'];
foreach (content_load('san-pham') as $p) {
    $productOptions[$p['slug']] = $p['name'] . (is_sold($p) ? ' (đã có chủ)' : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    $old = (string) ($_POST['key_old'] ?? '');
    if ($act === 'xoa' && isset($certs[$old])) {
        unset($certs[$old]);
        content_save('kiem-dinh', $certs);
        products_map(fn($p) => ($p['cert'] ?? '') === $old ? ['cert' => ''] + $p : $p);
        adm_log('Xóa giấy kiểm định', $old);
        adm_back('kiem-dinh', [], 'Đã xóa giấy kiểm định ' . $old . '.');
    }
    if ($act === 'luu') {
        $code = strtoupper(trim(preg_replace('/[^A-Za-z0-9.\/ -]/', '', in_str('code', 40))));
        if ($code === '') {
            adm_back('kiem-dinh', $old ? ['sua' => $old] : ['moi' => 1], 'Vui lòng nhập mã in trên giấy kiểm định.', 'error');
        }
        foreach ($certs as $k => $c) {
            if ($k !== $old && normalize_cert_code($k) === normalize_cert_code($code)) {
                adm_back('kiem-dinh', $old ? ['sua' => $old] : ['moi' => 1], 'Mã ' . $code . ' đã có trong danh sách.', 'error');
            }
        }
        $product = (string) ($_POST['product'] ?? '');
        $item = array_merge($certs[$old] ?? [], [
            'product' => isset($productOptions[$product]) ? $product : '',
            'lab'     => in_str('lab', 200),
            'date'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', in_str('date', 10)) ? in_str('date', 10) : '',
            'result'  => in_str('result', 300),
            'details' => in_pairs('details'),
            'image'   => clean_asset_path(in_str('image', 300)),
        ]);
        if (in_bool('mau')) {
            $item['mau'] = true;
        } else {
            unset($item['mau']);
        }
        $certs = keyed_rename($certs, $old ?: $code, $code, $item);
        content_save('kiem-dinh', $certs);
        // Gắn mã vào sản phẩm (và gỡ khỏi sản phẩm cũ nếu đổi)
        products_map(function ($p) use ($old, $code, $item) {
            $cert = (string) ($p['cert'] ?? '');
            if ($old !== '' && $cert === $old) {
                $cert = $code;                                   // đổi mã giấy
            }
            if ($cert === $code && $p['slug'] !== $item['product']) {
                $cert = '';                                      // giấy đã chuyển sang sản phẩm khác
            }
            if ($item['product'] !== '' && $p['slug'] === $item['product']) {
                $cert = $code;
            }
            return ['cert' => $cert] + $p;
        });
        adm_log($old ? 'Sửa giấy kiểm định' : 'Thêm giấy kiểm định', $code);
        adm_back('kiem-dinh', ['sua' => $code], 'Đã lưu giấy kiểm định ' . $code . '.');
    }
    adm_back('kiem-dinh');
}

if ($key !== '' || !empty($_GET['moi'])) {
    if ($key !== '' && !isset($certs[$key])) {
        adm_back('kiem-dinh', [], 'Không tìm thấy giấy kiểm định.', 'error');
    }
    $c = $certs[$key] ?? ['product' => (string) ($_GET['sp'] ?? ''), 'lab' => '', 'date' => date('Y-m-d'), 'result' => 'Ngọc phỉ thúy (Jadeite) tự nhiên – loại A',
                          'details' => ['Loại sản phẩm' => '', 'Màu sắc' => '', 'Kích thước' => '', 'Khối lượng' => ''], 'image' => ''];
    adm_head($key ? 'Giấy kiểm định ' . $key : 'Thêm giấy kiểm định', 'kiem-dinh',
        $key ? '<a class="btn btn--sm btn--outline" href="' . e(certificate_url($key)) . '" target="_blank" rel="noopener">' . adm_icon('external') . 'Xem trang tra cứu</a>' : '', [['Giấy kiểm định', adm_url('kiem-dinh')]]);
    if (!empty($c['mau'])) {
        echo adm_notice('Đây là <strong>dữ liệu mẫu</strong> – trang tra cứu đang ghi rõ “dữ liệu mẫu”. Điền đúng như giấy thật rồi tắt ô “Dữ liệu mẫu”, hoặc xóa.', 'warn');
    }
    ?>
    <form method="post" action="<?= e(adm_url('kiem-dinh')) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu"><input type="hidden" name="key_old" value="<?= e($key) ?>">
      <section class="card">
        <div class="form-grid">
          <?= f_text('code', 'Mã in trên giấy kiểm định', $key, ['required' => true, 'placeholder' => 'VD: VNP-2610-0001', 'hint' => 'Khách nhập mã này để tra cứu (không phân biệt hoa/thường, có hay không gạch nối).']) ?>
          <?= f_text('date', 'Ngày kiểm định', $c['date'] ?? '', ['type' => 'date']) ?>
          <?= f_text('lab', 'Đơn vị kiểm định', $c['lab'] ?? '', ['wide' => true, 'placeholder' => 'Ghi đúng tên phòng kiểm định trên giấy']) ?>
          <?= f_text('result', 'Kết luận', $c['result'] ?? '', ['wide' => true]) ?>
          <?= f_select('product', 'Sản phẩm tương ứng', $c['product'] ?? '', $productOptions, ['wide' => true, 'hint' => 'Trang tra cứu hiện sản phẩm này; trang sản phẩm hiện mã giấy kiểm định.']) ?>
          <?= f_pairs('details', 'Thông tin chi tiết (ghi đúng như trên giấy)', (array) ($c['details'] ?? []), ['wide' => true, 'k' => 'VD: Màu sắc', 'v' => 'VD: Xanh lục']) ?>
          <?= f_image('image', 'Ảnh chụp / scan giấy kiểm định', (string) ($c['image'] ?? ''), ['wide' => true, 'class' => '', 'folder' => 'kiem-dinh', 'max' => 2000]) ?>
          <?= f_switch('mau', 'Dữ liệu mẫu (chỉ để minh họa)', !empty($c['mau']), ['wide' => true]) ?>
        </div>
      </section>
      <?= adm_savebar($key ? 'Lưu giấy kiểm định' : 'Thêm giấy kiểm định') ?>
    </form>
    <?php if ($key): ?><form method="post" action="<?= e(adm_url('kiem-dinh')) ?>" data-confirm="Xóa giấy kiểm định <?= e($key) ?>? Khách sẽ không tra cứu được mã này nữa."><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="key_old" value="<?= e($key) ?>"><button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?>Xóa giấy kiểm định</button></form><?php endif; ?>
    <?php
    adm_foot();
    return;
}

adm_head('Giấy kiểm định', 'kiem-dinh', '<a class="btn btn--primary" href="' . e(adm_url('kiem-dinh', ['moi' => 1])) . '">' . icon('plus') . 'Thêm giấy kiểm định</a>');
?>
<div data-filter-scope>
  <?= adm_filterbar('Tìm mã, sản phẩm…') ?>
  <section class="card card--flush">
    <?php if (!$certs): ?><div class="empty"><?= icon('cert') ?><p>Chưa có giấy kiểm định nào.</p></div><?php else: ?>
    <div class="tbl-wrap"><table class="tbl tbl--cards">
      <thead><tr><th>Mã</th><th>Sản phẩm</th><th>Kết luận</th><th>Ngày</th><th class="act"></th></tr></thead>
      <tbody>
      <?php $bySlug = array_column(content_load('san-pham'), null, 'slug');
      foreach ($certs as $k => $c): $p = $bySlug[$c['product'] ?? ''] ?? null; ?>
        <tr data-filter-item data-filter-text="<?= e($k . ' ' . ($p['name'] ?? '')) ?>">
          <td class="tbl__first"><a class="tbl__main" href="<?= e(adm_url('kiem-dinh', ['sua' => $k])) ?>"><?= e($k) ?></a><?php if (!empty($c['mau'])): ?><span class="tbl__sub"><?= adm_badge('Dữ liệu mẫu', 'warn') ?></span><?php endif; ?></td>
          <td data-label="Sản phẩm"><?= e($p['name'] ?? '–') ?></td>
          <td data-label="Kết luận"><?= e($c['result'] ?? '') ?></td>
          <td data-label="Ngày"><?= e(!empty($c['date']) ? format_date($c['date']) : '') ?></td>
          <td class="act"><a class="btn btn--sm btn--outline" href="<?= e(adm_url('kiem-dinh', ['sua' => $k])) ?>"><?= adm_icon('edit') ?>Sửa</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>
</div>
<?php
adm_foot();
