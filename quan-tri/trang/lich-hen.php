<?php
/** Lịch hẹn xem ngọc (từ trang Đặt lịch) – đánh dấu đã gọi xác nhận, đã đến, hủy */
defined('ADMIN') || exit;

$statuses = ['' => 'Mới – chưa liên hệ', 'da-xac-nhan' => 'Đã gọi xác nhận', 'da-den' => 'Khách đã đến', 'huy' => 'Hủy / không đến'];
$tones    = ['' => 'warn', 'da-xac-nhan' => 'info', 'da-den' => 'ok', 'huy' => 'muted'];
csv_status_post('dat-lich', 'lich-hen', $statuses);

$rows  = csv_records('dat-lich');
foreach ($rows as &$r) {
    $r['_when'] = csv_time(($r['Ngày hẹn'] ?? '') . ' ' . ($r['Giờ'] ?? ''));
}
unset($r);
$loc   = (string) ($_GET['loc'] ?? 'sap-toi');
$today = strtotime('today');
$sets  = [
    'sap-toi' => ['Sắp tới', fn($r) => $r['_when'] >= $today && $r['_status'] !== 'huy'],
    'moi'     => ['Chưa liên hệ', fn($r) => $r['_status'] === ''],
    ''        => ['Tất cả', fn($r) => true],
];
$loc  = isset($sets[$loc]) ? $loc : '';
$list = array_values(array_filter($rows, $sets[$loc][1]));
if ($loc === 'sap-toi') {
    usort($list, fn($a, $b) => $a['_when'] <=> $b['_when']);
}
$chips = [];
foreach ($sets as $k => [$l, $fn]) {
    $chips[$k] = [$l, adm_url('lich-hen', ['loc' => $k === '' ? 'tat-ca' : $k]), count(array_filter($rows, $fn))];
}

adm_head('Lịch hẹn xem ngọc', 'lich-hen', adm_is_owner() ? '<a class="btn btn--sm btn--outline" href="' . e(adm_url('cai-dat', ['nhom' => 'dat-lich'])) . '">' . adm_icon('settings') . 'Khung giờ nhận hẹn</a>' : '');
?>
<div data-filter-scope>
  <?= adm_filterbar('Tìm tên, số điện thoại, sản phẩm…', $chips, $loc) ?>
  <section class="card card--flush">
    <?php if (!$list): ?>
      <div class="empty"><?= icon('calendar') ?><p>Chưa có lịch hẹn nào trong mục này.</p></div>
    <?php else: ?>
      <div class="tbl-wrap"><table class="tbl tbl--cards">
        <thead><tr><th>Ngày hẹn</th><th>Khách</th><th>Quan tâm</th><th>Gửi lúc</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php foreach ($list as $r): ?>
          <tr data-filter-item<?= $r['_status'] === 'huy' ? ' class="is-dim"' : '' ?>>
            <td class="tbl__first"><strong class="tbl__main"><?= e(($r['Giờ'] ?? '') . ' · ' . ($r['Ngày hẹn'] ?? '')) ?></strong><?php if ($r['_when'] >= $today && $r['_when'] < $today + 86400): ?><span class="tbl__sub"><?= adm_badge('Hôm nay', 'gold') ?></span><?php endif; ?></td>
            <td data-label="Khách"><span><?= e($r['Họ tên'] ?? '') ?><span class="tbl__sub"><a href="<?= e(tel_href($r['Điện thoại'] ?? '')) ?>"><?= e($r['Điện thoại'] ?? '') ?></a> · <a href="<?= e(zalo_chat_url($r['Điện thoại'] ?? '')) ?>" target="_blank" rel="noopener">Zalo</a></span></span></td>
            <td data-label="Quan tâm"><span><?= e($r['Sản phẩm quan tâm'] ?? '') ?: '<span class="muted">–</span>' ?><?php if (!empty($r['Ghi chú'])): ?><span class="tbl__sub msg-body"><?= e($r['Ghi chú']) ?></span><?php endif; ?></span></td>
            <td data-label="Gửi lúc"><?= e(adm_ago((int) $r['_ts'])) ?></td>
            <td data-label="Trạng thái"><?= csv_status_form('lich-hen', $r, $statuses) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <p class="empty" data-filter-empty hidden>Không có lịch hẹn khớp từ khóa.</p>
    <?php endif; ?>
  </section>
</div>
<?php
adm_foot();
