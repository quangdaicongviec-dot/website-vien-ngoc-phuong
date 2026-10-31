<?php
/** Giao dịch ngân hàng (Casso/SePay), đăng ký nhận tin, file Excel gốc theo tháng */
defined('ADMIN') || exit;

if (!empty($_GET['tai'])) {
    csv_download((string) $_GET['tai']);
}

$tabs = ['giao-dich' => 'Giao dịch ngân hàng', 'dang-ky-nhan-tin' => 'Đăng ký nhận tin', 'tep' => 'File Excel gốc'];
$tab  = isset($tabs[$_GET['tab'] ?? '']) ? (string) $_GET['tab'] : 'giao-dich';

adm_head('Giao dịch & đăng ký', 'du-lieu');
?>
<nav class="adm-tabs" aria-label="Loại dữ liệu"><?php foreach ($tabs as $k => $l): ?><a href="<?= e(adm_url('du-lieu', ['tab' => $k])) ?>"<?= $k === $tab ? ' aria-current="page"' : '' ?>><?= e($l) ?></a><?php endforeach; ?></nav>
<?php if ($tab === 'giao-dich'): $rows = csv_records('giao-dich'); ?>
  <?= adm_notice(auto_payments_enabled() ? 'Tiền vào tài khoản được dịch vụ ' . e(cfg('payment_provider', 'casso') === 'sepay' ? 'SePay' : 'Casso') . ' báo về website và tự xác nhận theo mã trong nội dung chuyển khoản.' : 'Chưa bật tự động xác nhận tiền vào – danh sách này sẽ có dữ liệu khi kết nối Casso hoặc SePay (<a href="' . e(adm_url('cai-dat', ['nhom' => 'thanh-toan'])) . '">Cài đặt → Thanh toán</a>).') ?>
  <div data-filter-scope>
    <?= adm_filterbar('Tìm nội dung, mã đơn, số tiền…') ?>
    <section class="card card--flush">
      <?php if (!$rows): ?><div class="empty"><?= adm_icon('bank') ?><p>Chưa có giao dịch nào.</p></div><?php else: ?>
      <div class="tbl-wrap"><table class="tbl tbl--cards">
        <thead><tr><th>Thời gian</th><th class="num">Số tiền</th><th>Nội dung</th><th>Website đã làm</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr data-filter-item>
            <td class="tbl__first"><strong class="tbl__main"><?= e($r['Thời gian GD'] ?? '') ?></strong><span class="tbl__sub"><?= e($r['Người chuyển'] ?? '') ?></span></td>
            <td class="num" data-label="Số tiền"><strong><?= e(format_price((int) ($r['Số tiền'] ?? 0))) ?></strong></td>
            <td data-label="Nội dung"><span class="msg-body"><?= e($r['Nội dung'] ?? '') ?></span></td>
            <td data-label="Website đã làm"><?= e($r['Xử lý'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div><?php endif; ?>
    </section>
  </div>
<?php elseif ($tab === 'dang-ky-nhan-tin'): $rows = csv_records('dang-ky-nhan-tin'); ?>
  <section class="card">
    <div class="card__head"><h2><?= count($rows) ?> email đăng ký nhận tin</h2>
      <?php if ($rows): ?><button type="button" class="btn btn--sm btn--outline" data-copy="<?= e(implode(', ', array_unique(array_column($rows, 'Email')))) ?>"><?= icon('copy') ?>Sao chép tất cả email</button><?php endif; ?></div>
    <?php if (!$rows): ?><p class="muted">Chưa có ai đăng ký.</p><?php else: ?>
      <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Email</th><th>Thời gian</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr><td><?= e($r['Email'] ?? '') ?></td><td><?= e($r['Thời gian'] ?? '') ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </section>
<?php else: ?>
  <section class="card">
    <h2>File Excel gốc (CSV) theo tháng</h2>
    <p class="card__sub" style="margin-top:4px">Website ghi mỗi đơn hàng, lượt giữ hàng, lịch hẹn, liên hệ, giao dịch vào file theo tháng – mở bằng Excel. File “…-ghi-tam” là các dòng ghi bù lúc file chính đang bị mở trên máy.</p>
    <?php foreach (['don-hang' => 'Đơn hàng', 'giu-hang' => 'Giữ hàng', 'dat-lich' => 'Lịch hẹn', 'lien-he' => 'Liên hệ', 'giao-dich' => 'Giao dịch ngân hàng', 'dang-ky-nhan-tin' => 'Đăng ký nhận tin'] as $k => $l): $files = csv_files($k); ?>
      <h3 style="font-size:15px;margin:16px 0 8px"><?= e($l) ?></h3>
      <?php if (!$files): ?><p class="muted">Chưa có.</p><?php else: ?>
        <div class="files"><?php foreach ($files as $f): ?><a class="btn btn--sm btn--outline" href="<?= e(adm_url('du-lieu', ['tai' => $f])) ?>"><?= icon('download') ?><?= e($f) ?></a><?php endforeach; ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
<?php
adm_foot();
