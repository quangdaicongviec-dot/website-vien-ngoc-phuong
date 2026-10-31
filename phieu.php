<?php
/**
 * Phiếu xác nhận cho khách: đặt cọc / thanh toán / đơn hàng (COD đã xác nhận).
 * - Link có mã ký riêng: phieu.php?ma=MÃ&k=… (gửi qua email, nút trên trang đơn hàng; cửa hàng có thể gửi qua Zalo).
 * - Mã QR in trên phiếu trỏ về chính trang này → ai cầm phiếu cũng kiểm tra được phiếu thật.
 * - Nội dung phiếu do assets/js/receipt.js dựng; Tải PDF / Lưu ảnh / In chạy ngay trên trình duyệt.
 * Phiếu lưu trong data/don-hang/phieu/ (xem includes/functions.php → issue_receipt).
 */
require __DIR__ . '/includes/init.php';

$page_title   = 'Phiếu xác nhận';
$head_extra   = '<meta name="robots" content="noindex, nofollow">';
$body_class   = 'page-receipt';
$mini_footer  = true;   // trang phiếu: chân trang gọn như các trang thanh toán
$foot_scripts = ['js/vendor/qrcode.min.js', 'js/receipt.js'];
header('X-Robots-Tag: noindex, nofollow');

$data = ['logo' => img_url('logo.png'), 'html2canvas' => asset('js/vendor/html2canvas.min.js')];

if (STATIC_DEMO && isset($_GET['demo'])) {
    // Bản chạy thử: phiếu dựng từ đơn / lượt giữ mô phỏng lưu trên trình duyệt (demo-static.js)
    $data += [
        'demo'   => true,
        'shop'   => receipt_shop(),
        'terms'  => array_map(fn($lines) => array_map('receipt_fill', (array) $lines), (array) cfg('receipt_terms', [])),
        'footer' => receipt_fill((string) cfg('receipt_note')),
        'policy' => (string) cfg('deposit_policy_version'),
    ];
    $r = null;
} else {
    $code    = strtoupper(trim((string) ($_GET['ma'] ?? '')));
    $sigOk   = $code !== '' && hash_equals(receipt_sig($code), (string) ($_GET['k'] ?? ''));
    $r       = $sigOk ? find_receipt($code) : null;
    $pending = null;
    if ($sigOk && !$r) {                           // phiếu chưa phát hành → hiện trạng thái đơn / lượt giữ
        $pending = receipt_pending_info($code);
        if (!empty($pending['issued'])) {
            $r       = find_receipt($code);
            $pending = null;
        }
    }
    if (!$r && !$pending) {
        http_response_code(404);
        $page_title = 'Không tìm thấy phiếu';
        include ROOT . '/includes/header.php';
        echo '<section class="section"><div class="container empty-state empty-state--page">' . icon('receipt')
            . '<h1>Không tìm thấy phiếu</h1><p>Link phiếu không đúng hoặc đã bị chỉnh sửa – phiếu này <strong>không được xác thực</strong>.<br>'
            . 'Vui lòng liên hệ ' . e(cfg('site_name')) . ' qua <a href="' . e(tel_href(cfg('hotline'))) . '">' . e(cfg('hotline')) . '</a> để kiểm tra.</p></div></section>';
        include ROOT . '/includes/footer.php';
        exit;
    }
    if ($r) {
        $data += ['receipt' => $r, 'url' => receipt_url($r['code'])];
        $page_title = receipt_title($r['kind']) . ' ' . $r['code'];
    } else {
        $page_title   = receipt_title($pending['kind']) . ' ' . $code;
        $foot_scripts = [];
    }
}

/** Trang "phiếu chưa phát hành": trạng thái hiện tại + các bước; tự tải lại khi cửa hàng xác nhận (main.js) */
function receipt_pending_html(array $p): string
{
    $hold   = $p['type'] === 'hold';
    $cod    = $p['kind'] === 'don-hang';
    $money  = $p['amount'] > 0 ? format_price($p['amount']) : '';
    $info   = [
        'pending'   => $hold ? ['Chờ xác nhận tiền cọc', 'pending', 'Bạn đã chuyển cọc? Phiếu xác nhận đặt cọc sẽ hiện tại trang này ngay khi cửa hàng nhận được tiền' . (auto_payments_enabled() ? ' (hệ thống tự kiểm tra, thường chỉ vài phút)' : '') . '. Chưa chuyển thì bấm “Xem mã QR đặt cọc”.', 'Xem mã QR đặt cọc']
                             : ['Chờ xác nhận thanh toán', 'pending', 'Bạn đã chuyển khoản? Phiếu xác nhận thanh toán sẽ hiện tại trang này ngay khi cửa hàng nhận được tiền' . (auto_payments_enabled() ? ' (hệ thống tự kiểm tra, thường chỉ vài phút)' : '') . '. Chưa chuyển thì bấm “Xem mã QR chuyển khoản”.', 'Xem mã QR chuyển khoản'],
        'partial'   => ['Đã nhận một phần', 'pending', 'Cửa hàng đã nhận ' . format_price($p['paid']) . ', còn thiếu ' . format_price(max(0, $p['amount'] - $p['paid'])) . '. Phiếu sẽ có ngay khi nhận đủ tiền.', 'Xem mã QR chuyển khoản'],
        'cod'       => ['Chờ cửa hàng xác nhận', 'pending', 'Cửa hàng sẽ gọi số ' . mask_phone($p['phone']) . ' để xác nhận đơn. Phiếu xác nhận đơn hàng sẽ có tại trang này ngay sau đó.', 'Xem đơn hàng'],
        'expired'   => $hold ? ['Hết thời gian chờ cọc', 'expired', 'Chưa nhận được tiền cọc nên món ngọc đã được mở bán lại. Nếu bạn đã chuyển khoản, vui lòng liên hệ cửa hàng.', 'Xem lượt giữ hàng']
                             : ['Hết thời gian giữ món', 'expired', 'Chưa nhận được thanh toán nên món độc bản đã được mở bán lại. Nếu bạn đã chuyển khoản, vui lòng liên hệ cửa hàng.', 'Xem đơn hàng'],
        'cancelled' => ['Đã hủy', 'expired', 'Đơn hàng đã được hủy nên không có phiếu xác nhận. Nếu bạn đã chuyển khoản, vui lòng liên hệ cửa hàng để được hoàn tiền.', 'Xem đơn hàng'],
    ][$p['state']] ?? ['Đang xử lý', 'pending', 'Phiếu sẽ có tại trang này ngay khi cửa hàng xác nhận.', 'Xem đơn hàng'];
    $active = $info[1] === 'pending';
    $steps  = $hold ? ['Gửi yêu cầu giữ hàng', 'Chuyển khoản đặt cọc ' . $money, 'Cửa hàng nhận cọc – phát hành phiếu']
                    : ($cod ? ['Đặt đơn hàng', 'Cửa hàng gọi xác nhận đơn', 'Phát hành phiếu xác nhận đơn hàng']
                            : ['Đặt đơn hàng', 'Chuyển khoản ' . $money, 'Cửa hàng nhận tiền – phát hành phiếu']);
    ob_start(); ?>
    <div class="receipt-wait card"<?php if ($active): ?> data-receipt-watch="<?= e($p['code']) ?>" data-watch-type="<?= e($p['type']) ?>" data-watch-key="<?= e($p['state']) ?>"<?php endif; ?>>
      <span class="receipt-wait__icon<?= $active ? '' : ' is-muted' ?>"><?= icon($active ? 'clock' : 'receipt') ?></span>
      <p class="eyebrow"><?= e(receipt_title($p['kind'])) ?></p>
      <h1 class="receipt-wait__title"><?= $active ? 'Phiếu chưa được phát hành' : 'Không có phiếu xác nhận' ?></h1>
      <p class="receipt-wait__code"><?= $hold ? 'Mã giữ hàng' : 'Mã đơn hàng' ?> <strong class="order-code"><?= e($p['code']) ?></strong> <span class="hold-status hold-status--<?= e($info[1]) ?>"><?= e($info[0]) ?></span></p>
      <p class="receipt-wait__text"><?= e($info[2]) ?></p>
      <ul class="receipt-wait__lines">
        <?php foreach ($p['lines'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
        <?php if ($money): ?><li class="receipt-wait__total"><span><?= $hold ? 'Tiền đặt cọc' : 'Tổng cộng' ?></span><strong><?= e($money) ?></strong></li><?php endif; ?>
      </ul>
      <?php if ($active): ?>
        <ol class="steps hold-steps">
          <li class="is-done"><?= e($steps[0]) ?></li>
          <li class="is-current"><?= e($steps[1]) ?></li>
          <li><?= e($steps[2]) ?></li>
        </ol>
      <?php endif; ?>
      <div class="receipt-wait__actions">
        <a class="btn btn--primary" href="<?= e($p['view']) ?>"><?= e($info[3]) ?></a>
        <?php if (!$active && cfg('zalo')): ?><a class="btn btn--outline" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Nhắn Zalo cho shop</a><?php endif; ?>
      </div>
      <?php if ($active): ?><p class="receipt-wait__note">Trang này tự cập nhật – phiếu sẽ hiện ngay khi cửa hàng xác nhận. Bạn có thể lưu link này, hoặc mở lại tại <a href="<?= e(url('tra-cuu-don-hang')) ?>">Tra cứu đơn hàng</a>.</p><?php endif; ?>
    </div>
    <?php return ob_get_clean();
}

/** Dòng trạng thái hiện tại (khác nội dung phiếu: phiếu là bản chụp lúc lập, dòng này luôn cập nhật) */
function receipt_live_status(array $r): string
{
    $at = fn(int $ts) => date('H:i', $ts) . ' ngày ' . date('d/m/Y', $ts);
    $note = !empty($r['status_note']) ? ' ' . $r['status_note'] : '';
    if (($r['status'] ?? '') === 'cancelled') {
        return ($r['kind'] === 'coc' ? 'Lượt giữ hàng' : 'Đơn hàng') . ' đã được hủy lúc ' . $at((int) ($r['cancelled_at'] ?? 0)) . '.'
            . ($r['kind'] === 'don-hang' || $note ? '' : ' Vui lòng liên hệ cửa hàng về khoản tiền đã thanh toán.') . $note;
    }
    // Trạng thái do cửa hàng cập nhật trong trang quản trị
    $done = [
        'completed' => 'Khách đã hoàn tất mua hàng lúc %s.',
        'refunded'  => 'Cửa hàng đã hoàn tiền cho khách lúc %s.',
        'credit'    => 'Tiền cọc đã được chuyển thành phiếu mua hàng lúc %s (dùng trong ' . (int) cfg('deposit_credit_months', 6) . ' tháng).',
    ][$r['status'] ?? ''] ?? '';
    if ($done !== '') {
        return sprintf($done, $at((int) ($r[$r['status'] . '_at'] ?? time()))) . $note;
    }
    if ($r['kind'] === 'coc') {
        $until = (int) ($r['hold_until'] ?? 0);
        return $until > time() ? 'Món ngọc đang được giữ riêng cho khách đến ' . $at($until) . '.' : 'Thời gian giữ hàng đã kết thúc lúc ' . $at($until) . '.';
    }
    return $r['kind'] === 'thanh-toan' ? 'Cửa hàng đã nhận thanh toán cho đơn hàng này.' : 'Đơn hàng đã được cửa hàng xác nhận.';
}

include ROOT . '/includes/header.php';
?>
<section class="section receipt-page">
  <div class="container" data-receipt-page>
    <?php if (!empty($pending)): ?>
      <?= receipt_pending_html($pending) ?>
  </div>
</section>
<?php include ROOT . '/includes/footer.php'; exit; endif; ?>
    <?php if ($r): ?>
      <div class="receipt-check<?= in_array($r['status'] ?? '', ['cancelled', 'refunded'], true) ? ' receipt-check--warn' : '' ?>">
        <?= icon('shield') ?>
        <p><strong>Phiếu thật – do <?= e(cfg('site_name')) ?> phát hành.</strong> Nội dung dưới đây được lấy trực tiếp từ dữ liệu của cửa hàng.<br><?= e(receipt_live_status($r)) ?></p>
      </div>
    <?php else: ?>
      <div class="receipt-check receipt-check--demo">
        <?= icon('shield') ?>
        <p><strong>Bản chạy thử – phiếu mô phỏng, không có giá trị.</strong> Trên website thật, phiếu được lập tự động khi cửa hàng nhận cọc / nhận thanh toán / xác nhận đơn COD, và mã QR dẫn tới trang kiểm tra phiếu thật.</p>
      </div>
    <?php endif; ?>

    <div class="receipt-tools" data-receipt-tools>
      <button type="button" class="btn btn--primary" data-receipt-pdf><?= icon('download') ?><span class="btn__label">Tải PDF</span><span class="spinner" aria-hidden="true"></span></button>
      <button type="button" class="btn btn--outline" data-receipt-png><?= icon('image') ?><span class="btn__label">Lưu ảnh</span><span class="spinner" aria-hidden="true"></span></button>
      <button type="button" class="btn btn--outline" data-receipt-print><?= icon('printer') ?>In phiếu</button>
    </div>
    <div class="receipt-wrap" data-receipt-mount>
      <noscript><p class="notice">Vui lòng bật JavaScript để xem phiếu.</p></noscript>
    </div>
    <p class="receipt-tools__hint">Hãy lưu phiếu vào điện thoại để tiện đối chiếu khi nhận hàng, bảo hành. Nếu mở link trong Zalo/Facebook mà không tải được, bấm <strong>⋯ → Mở bằng trình duyệt</strong> rồi thử lại.</p>
  </div>
</section>

<dialog class="modal receipt-shot" id="receipt-shot" aria-label="Ảnh phiếu xác nhận">
  <div class="modal__body">
    <button type="button" class="icon-btn modal__close" data-dialog-close><?= icon('close') ?><span class="sr-only">Đóng</span></button>
    <p class="receipt-shot__hint"><strong>Trên điện thoại:</strong> nhấn giữ vào ảnh → <em>Lưu ảnh</em>. Trên máy tính: bấm “Tải ảnh về”.</p>
    <img alt="Ảnh phiếu xác nhận" data-receipt-shot-img>
    <div class="modal__actions">
      <a class="btn btn--primary" data-receipt-shot-save download><?= icon('download') ?>Tải ảnh về</a>
      <button type="button" class="btn btn--outline" data-receipt-shot-share hidden>Chia sẻ</button>
    </div>
  </div>
</dialog>

<script type="application/json" id="receipt-data"><?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php include ROOT . '/includes/footer.php'; ?>
