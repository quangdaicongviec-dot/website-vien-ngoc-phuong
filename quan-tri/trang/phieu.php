<?php
/** Phiếu xác nhận đã phát cho khách: xem, đổi trạng thái (hoàn tiền, phiếu mua hàng, đã hủy…) */
defined('ADMIN') || exit;

$code   = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string) ($_GET['ma'] ?? '')));
$kinds  = ['coc' => 'Đặt cọc', 'thanh-toan' => 'Thanh toán', 'don-hang' => 'Đơn hàng (COD)'];
$tones  = ['valid' => 'ok', 'completed' => 'info', 'credit' => 'gold', 'refunded' => 'warn', 'cancelled' => 'muted'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $code !== '') {
    $res = shop_receipt_status($code, (string) ($_POST['trang-thai'] ?? ''), in_str('ghi-chu', 500));
    adm_log('Phiếu ' . $code, $res[1]);
    adm_back('phieu', ['ma' => $code], $res[1], $res[0]);
}

if ($code !== '') {
    $r = find_receipt($code);
    if (!$r) {
        adm_back('phieu', [], 'Không tìm thấy phiếu ' . $code . '.', 'error');
    }
    $st = $r['status'] ?? 'valid';
    adm_head(receipt_title($r['kind']) . ' ' . $code, 'phieu',
        '<a class="btn btn--sm btn--primary" href="' . e(receipt_url($code)) . '" target="_blank" rel="noopener">' . icon('receipt') . 'Mở phiếu</a>'
        . '<button type="button" class="btn btn--sm btn--outline" data-copy="' . e(receipt_url($code)) . '">' . icon('copy') . 'Sao chép link</button>',
        [['Phiếu xác nhận', adm_url('phieu')]]);
    ?>
    <div class="layout-side">
      <section class="card">
        <div class="card__head"><h2>Nội dung phiếu</h2><?= adm_badge(receipt_status_labels()[$st] ?? $st, $tones[$st] ?? 'muted') ?></div>
        <dl class="dl">
          <dt>Loại phiếu</dt><dd><?= e(receipt_title($r['kind'])) ?></dd>
          <dt>Khách</dt><dd><?= e($r['name']) ?> · <?= e($r['phone']) ?></dd>
          <dt>Lập lúc</dt><dd><?= e(adm_time((int) ($r['issued'] ?? 0))) ?></dd>
          <dt>Sản phẩm</dt><dd><?= e(implode(', ', array_map(fn($i) => $i['name'] . ' × ' . $i['qty'], $r['items'] ?? []))) ?></dd>
          <dt>Tổng giá trị</dt><dd><?= e(format_price((int) ($r['total'] ?? 0))) ?></dd>
          <?php if (!empty($r['paid'])): ?><dt>Đã nhận</dt><dd><strong><?= e(format_price((int) $r['paid'])) ?></strong><?= !empty($r['paid_at']) ? ' lúc ' . e(adm_time((int) $r['paid_at'])) : '' ?></dd><?php endif; ?>
          <?php if (!empty($r['hold_until'])): ?><dt>Giữ hàng đến</dt><dd><?= e(adm_time((int) $r['hold_until'])) ?></dd><?php endif; ?>
          <?php if (!empty($r['status_note'])): ?><dt>Ghi chú trạng thái</dt><dd><?= e($r['status_note']) ?></dd><?php endif; ?>
        </dl>
        <p class="af__hint" style="margin-top:14px">Phiếu lưu sẵn thông tin cửa hàng và điều khoản lúc lập – sửa cài đặt sau này không làm thay đổi phiếu đã phát.</p>
      </section>
      <aside>
        <section class="card">
          <h2>Cập nhật trạng thái phiếu</h2>
          <p class="card__sub" style="margin-top:4px">Khách mở link phiếu sẽ thấy trạng thái mới.</p>
          <form method="post" action="<?= e(adm_url('phieu', ['ma' => $code])) ?>" class="stack">
            <?= adm_csrf_field() ?>
            <?= f_select('trang-thai', 'Trạng thái', $st, receipt_status_labels()) ?>
            <?= f_textarea('ghi-chu', 'Ghi chú (hiện trên phiếu)', $r['status_note'] ?? '', ['rows' => 2, 'placeholder' => 'VD: Đã hoàn 4.500.000₫ ngày 22/10, mã GD FT26…']) ?>
            <button type="submit" class="btn btn--primary btn--block"><?= adm_icon('save') ?>Lưu trạng thái</button>
          </form>
        </section>
        <?php if (strpos($code, 'NP') === 0): ?><p><a href="<?= e(adm_url('don-hang', ['ma' => $code])) ?>">→ Xem đơn hàng <?= e($code) ?></a></p>
        <?php else: ?><p><a href="<?= e(adm_url('giu-hang', ['ma' => $code])) ?>">→ Xem lượt giữ <?= e($code) ?></a></p><?php endif; ?>
      </aside>
    </div>
    <?php
    adm_foot();
    return;
}

$files = glob(ROOT . '/data/don-hang/phieu/*.json') ?: [];
$list  = [];
foreach ($files as $f) {
    $r = json_decode((string) file_get_contents($f), true);
    if (is_array($r) && !empty($r['code'])) {
        $list[] = $r;
    }
}
usort($list, fn($a, $b) => ($b['issued'] ?? 0) <=> ($a['issued'] ?? 0));
$kind = (string) ($_GET['loai'] ?? '');
$chips = ['' => ['Tất cả', adm_url('phieu'), count($list)]];
foreach ($kinds as $k => $l) {
    $chips[$k] = [$l, adm_url('phieu', ['loai' => $k]), count(array_filter($list, fn($r) => $r['kind'] === $k))];
}
if (isset($kinds[$kind])) {
    $list = array_filter($list, fn($r) => $r['kind'] === $kind);
}

adm_head('Phiếu xác nhận', 'phieu');
?>
<div data-filter-scope>
  <?= adm_filterbar('Tìm mã phiếu, tên khách…', $chips, $kind) ?>
  <section class="card card--flush">
    <?php if (!$list): ?>
      <div class="empty"><?= icon('receipt') ?><p>Chưa có phiếu nào. Phiếu được lập tự động khi cửa hàng nhận cọc, nhận tiền hoặc xác nhận đơn COD.</p></div>
    <?php else: ?>
      <div class="tbl-wrap"><table class="tbl tbl--cards">
        <thead><tr><th>Số phiếu</th><th>Loại</th><th>Khách</th><th class="num">Số tiền</th><th>Trạng thái</th><th class="act"></th></tr></thead>
        <tbody>
        <?php foreach ($list as $r): $st = $r['status'] ?? 'valid'; ?>
          <tr data-filter-item data-filter-text="<?= e($r['code'] . ' ' . $r['name']) ?>">
            <td class="tbl__first"><a class="tbl__main" href="<?= e(adm_url('phieu', ['ma' => $r['code']])) ?>"><?= e($r['code']) ?></a><span class="tbl__sub"><?= e(adm_ago((int) ($r['issued'] ?? 0))) ?></span></td>
            <td data-label="Loại"><?= e($kinds[$r['kind']] ?? $r['kind']) ?></td>
            <td data-label="Khách"><?= e($r['name']) ?></td>
            <td class="num" data-label="Số tiền"><?= e(format_price((int) ($r['paid'] ?: $r['total']))) ?></td>
            <td data-label="Trạng thái"><?= adm_badge(receipt_status_labels()[$st] ?? $st, $tones[$st] ?? 'muted') ?></td>
            <td class="act"><a class="btn btn--sm btn--outline" href="<?= e(adm_url('phieu', ['ma' => $r['code']])) ?>">Xem</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <p class="empty" data-filter-empty hidden>Không có phiếu khớp từ khóa.</p>
    <?php endif; ?>
  </section>
</div>
<?php
adm_foot();
