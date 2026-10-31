<?php
/** Giữ hàng – đặt cọc: xác nhận cọc, gia hạn, khách đã mua, hủy */
defined('ADMIN') || exit;

$code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string) ($_GET['ma'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $code !== '') {
    $act = (string) ($_POST['act'] ?? '');
    $res = match ($act) {
        'xac-nhan' => shop_hold_confirm($code),
        'gia-han'  => shop_hold_extend($code),
        'da-mua'   => shop_hold_complete($code),
        'huy'      => shop_hold_cancel($code),
        default    => null,
    };
    if ($res) {
        adm_log('Giữ hàng ' . $code, $act . ' – ' . $res[1]);
        flash($res[1], $res[0]);
    }
    adm_back('giu-hang', find_hold_by_code($code) ? ['ma' => $code] : []);
}

$holds = array_filter(read_holds_raw(), fn($h) => ($h['type'] ?? 'hold') === 'hold');
uasort($holds, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));

function hold_badge(array $h): array
{
    $st = hold_status($h);
    if ($st === 'expired') {
        return ['Hết hạn', 'muted'];
    }
    return ($h['status'] ?? '') === 'pending' ? ['Chờ chuyển cọc', 'warn'] : ['Đã nhận cọc – đang giữ', 'ok'];
}

/* ================= Chi tiết ================= */
if ($code !== '') {
    $h = null;
    foreach ($holds as $x) {
        if (($x['code'] ?? '') === $code) {
            $h = $x;
        }
    }
    $receipt = find_receipt($code);
    if (!$h && !$receipt) {
        adm_back('giu-hang', [], 'Không tìm thấy lượt giữ ' . $code . '.', 'error');
    }
    adm_head('Giữ hàng ' . $code, 'giu-hang',
        ($h ? '<a class="btn btn--sm btn--outline" href="' . e(hold_view_url($code)) . '" target="_blank" rel="noopener">' . adm_icon('external') . 'Trang của khách</a>' : '')
        . ($receipt ? '<a class="btn btn--sm btn--outline" href="' . e(receipt_url($code)) . '" target="_blank" rel="noopener">' . icon('receipt') . 'Phiếu đặt cọc</a>' : ''),
        [['Giữ hàng – đặt cọc', adm_url('giu-hang')]]);
    if (!$h) {
        echo adm_notice('Lượt giữ này đã kết thúc (hủy, hết hạn hoặc khách đã mua). Phiếu đặt cọc vẫn còn – trạng thái phiếu: <strong>' . e(receipt_status_labels()[$receipt['status'] ?? 'valid'] ?? '') . '</strong>. <a href="' . e(adm_url('phieu', ['ma' => $code])) . '">Xem phiếu</a>.');
        adm_foot();
        return;
    }
    [$lab, $tone] = hold_badge($h);
    $p = find_product($h['slug']);
    $st = hold_status($h);
    ?>
    <div class="layout-side">
      <div>
        <section class="card">
          <div class="card__head"><h2>Món ngọc được giữ</h2><?= adm_badge($lab, $tone) ?></div>
          <div class="tbl__prod">
            <?php if ($p): ?><img class="tbl__thumb" src="<?= e(product_image($p)) ?>" alt="" style="width:72px;height:72px"><?php endif; ?>
            <div><a class="tbl__main" href="<?= e(adm_url('san-pham', ['sua' => $h['slug']])) ?>"><?= e($h['name_product']) ?></a>
              <span class="tbl__sub">Giá <?= e(format_price((int) ($h['amount'] ?? ($p['price'] ?? 0)))) ?> · cọc <strong><?= e(format_price((int) $h['deposit'])) ?></strong></span></div>
          </div>
          <dl class="dl" style="margin-top:16px">
            <dt>Khách</dt><dd><?= e($h['name']) ?></dd>
            <dt>Điện thoại</dt><dd><a href="<?= e(tel_href($h['phone'])) ?>"><?= e($h['phone']) ?></a> · <a href="<?= e(zalo_chat_url($h['phone'])) ?>" target="_blank" rel="noopener">Nhắn Zalo</a></dd>
            <?php if (!empty($h['email'])): ?><dt>Email</dt><dd><?= e($h['email']) ?></dd><?php endif; ?>
            <dt>Gửi yêu cầu</dt><dd><?= e(adm_time((int) ($h['created'] ?? 0))) ?></dd>
            <dt><?= ($h['status'] ?? '') === 'pending' ? 'Chờ cọc đến' : 'Giữ đến' ?></dt><dd><strong><?= e(hold_time((int) $h['until'])) ?></strong><?= !empty($h['extended_at']) ? ' (đã gia hạn)' : '' ?></dd>
            <?php if (!empty($h['agreed_at'])): ?><dt>Đồng ý chính sách</dt><dd>Phiên bản <?= e($h['policy'] ?? '') ?> lúc <?= e(adm_time((int) $h['agreed_at'])) ?></dd><?php endif; ?>
            <dt>Nội dung chuyển khoản</dt><dd><code><?= e($code) ?></code></dd>
          </dl>
        </section>
      </div>
      <aside>
        <section class="card">
          <h2>Việc tiếp theo</h2>
          <div class="action-box" style="margin-top:12px">
            <?php if ($st !== 'expired' && ($h['status'] ?? '') === 'pending'): ?>
              <p class="muted">Kiểm tra tài khoản đã nhận <strong><?= e(format_price((int) $h['deposit'])) ?></strong> với nội dung <?= e($code) ?>, rồi bấm xác nhận.</p>
              <form method="post" action="<?= e(adm_url('giu-hang', ['ma' => $code])) ?>" data-confirm="Xác nhận đã nhận đủ tiền cọc cho <?= e($code) ?>?">
                <?= adm_csrf_field() ?><input type="hidden" name="act" value="xac-nhan">
                <button type="submit" class="btn btn--primary"><?= icon('check') ?>Xác nhận đã nhận cọc</button>
              </form>
            <?php elseif ($st !== 'expired'): ?>
              <form method="post" action="<?= e(adm_url('giu-hang', ['ma' => $code])) ?>" data-confirm="Khách đã thanh toán và nhận món “<?= e($h['name_product']) ?>”? Sản phẩm sẽ chuyển sang Đã có chủ.">
                <?= adm_csrf_field() ?><input type="hidden" name="act" value="da-mua">
                <button type="submit" class="btn btn--primary"><?= icon('check') ?>Khách đã mua – hoàn tất</button>
              </form>
              <?php if (empty($h['extended_at'])): ?>
                <form method="post" action="<?= e(adm_url('giu-hang', ['ma' => $code])) ?>">
                  <?= adm_csrf_field() ?><input type="hidden" name="act" value="gia-han">
                  <button type="submit" class="btn btn--outline"><?= icon('clock') ?>Gia hạn thêm <?= (int) cfg('hold_extend_hours', 24) ?> giờ</button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <?= adm_notice('Lượt giữ đã hết hạn – sản phẩm đã được mở bán lại.') ?>
            <?php endif; ?>
            <?php if ($st !== 'expired'): ?>
              <form method="post" action="<?= e(adm_url('giu-hang', ['ma' => $code])) ?>" data-confirm="Hủy giữ hàng và mở bán lại sản phẩm này?">
                <?= adm_csrf_field() ?><input type="hidden" name="act" value="huy">
                <button type="submit" class="btn btn--danger btn--sm"><?= icon('close') ?>Hủy giữ hàng</button>
              </form>
            <?php endif; ?>
          </div>
          <p class="af__hint" style="margin-top:12px">Hoàn cọc / chuyển tiền cọc thành phiếu mua hàng: cập nhật trong <a href="<?= e(adm_url('phieu', ['ma' => $code])) ?>">Phiếu xác nhận</a>.</p>
        </section>
      </aside>
    </div>
    <?php
    adm_foot();
    return;
}

/* ================= Danh sách ================= */
adm_head('Giữ hàng – đặt cọc', 'giu-hang');
echo adm_notice('Khách bấm “Giữ hàng” với món từ ' . e(format_price((int) cfg('hold_min_price', 0))) . ', cọc ' . (int) cfg('hold_deposit', 10) . '%. Chưa cọc sau ' . (int) cfg('hold_pending_minutes', 60) . ' phút → tự mở bán lại. Đổi các con số trong <a href="' . e(adm_url('cai-dat', ['nhom' => 'giu-hang'])) . '">Cài đặt → Giữ hàng</a>. Lượt giữ đã kết thúc được dọn sau 7 ngày (phiếu cọc vẫn lưu).');
?>
<section class="card card--flush">
  <?php if (!$holds): ?>
    <div class="empty"><?= icon('lock') ?><p>Chưa có lượt giữ hàng nào.</p></div>
  <?php else: ?>
    <div class="tbl-wrap"><table class="tbl tbl--cards">
      <thead><tr><th>Mã giữ</th><th>Sản phẩm</th><th>Khách</th><th class="num">Tiền cọc</th><th>Trạng thái</th><th class="act"></th></tr></thead>
      <tbody>
      <?php foreach ($holds as $h): [$lab, $tone] = hold_badge($h); $p = find_product($h['slug']); ?>
        <tr<?= hold_status($h) === 'expired' ? ' class="is-dim"' : '' ?>>
          <td class="tbl__first"><a class="tbl__main" href="<?= e(adm_url('giu-hang', ['ma' => $h['code']])) ?>"><?= e($h['code']) ?></a><span class="tbl__sub"><?= e(adm_ago((int) ($h['created'] ?? 0))) ?></span></td>
          <td data-label="Sản phẩm"><span class="tbl__prod"><?php if ($p): ?><img class="tbl__thumb" src="<?= e(product_image($p)) ?>" alt=""><?php endif; ?><span><?= e($h['name_product']) ?></span></span></td>
          <td data-label="Khách"><span><?= e($h['name']) ?><span class="tbl__sub"><?= e($h['phone']) ?></span></span></td>
          <td class="num" data-label="Tiền cọc"><?= e(format_price((int) $h['deposit'])) ?></td>
          <td data-label="Trạng thái"><span><?= adm_badge($lab, $tone) ?><span class="tbl__sub"><?= hold_status($h) === 'expired' ? '' : 'đến ' . e(hold_time((int) $h['until'])) ?></span></span></td>
          <td class="act"><a class="btn btn--sm btn--outline" href="<?= e(adm_url('giu-hang', ['ma' => $h['code']])) ?>">Xem</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</section>
<?php
adm_foot();
