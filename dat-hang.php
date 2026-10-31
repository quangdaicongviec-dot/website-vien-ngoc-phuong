<?php
/**
 * Xử lý đặt hàng.
 * - Đơn hàng được lưu vào data/don-hang/don-hang-YYYY-MM.csv (mở bằng Excel) và data/don-hang/don-hang.json.
 * - Gửi email tới 'order_email' và báo Telegram, kèm LINK QUẢN LÝ ĐƠN (xác nhận đã nhận tiền / hủy đơn).
 * - Đơn chuyển khoản: trạng thái "Chờ thanh toán" → "Đã nhận thanh toán" (tự động qua Casso, hoặc cửa hàng xác nhận).
 *   Món độc bản trong đơn chuyển khoản được giữ riêng 'order_pending_minutes' phút chờ tiền.
 * - Đơn COD có món độc bản: giữ món tối đa 'order_cod_hours' giờ chờ cửa hàng gọi khách và bấm "Xác nhận đơn".
 */
require __DIR__ . '/includes/init.php';

$page_title = 'Đặt hàng';
$head_extra = '<meta name="robots" content="noindex">';
$body_class = 'page-order';
$mini_footer = true;   // trang thanh toán: chân trang gọn
$pay_labels = ['cod' => 'Thanh toán khi nhận hàng (COD)', 'chuyen-khoan' => 'Chuyển khoản ngân hàng'];

/* ============ Bản chạy thử tĩnh: trang khung, nội dung do demo-static.js dựng ============ */
if (STATIC_DEMO && isset($_GET['demo'])) {
    $page_title = 'Đơn hàng của bạn';
    include ROOT . '/includes/header.php';
    echo '<section class="section"><div class="container" data-demo-order></div></section>';
    include ROOT . '/includes/footer.php';
    exit;
}

/* ============ Trạng thái đơn (trang của khách tự hỏi định kỳ) ============ */
if (isset($_GET['thanh-toan'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    $code = (string) $_GET['thanh-toan'];
    $o    = find_order($code);
    echo json_encode(['status' => $o ? order_state($o) : 'none', 'receipt' => (bool) find_receipt($code)]);
    exit;
}

function order_time(int $ts): string
{
    return date('H:i', $ts) . ' ngày ' . date('d/m/Y', $ts);
}

/** Bảng chi tiết đơn (dùng cho trang khách & trang quản lý) */
function order_summary(array $o, array $pay_labels): string
{
    ob_start(); ?>
    <ul class="order-lines">
      <?php foreach ($o['items'] as $it): ?>
        <li><span><?= e($it['name']) ?> <small>× <?= (int) $it['qty'] ?></small><?= in_array($it['slug'], $o['reserved'] ?? [], true) ? ' <span class="badge badge--unique">Độc bản</span>' : '' ?></span><strong><?= e($it['price'] > 0 ? format_price($it['price'] * $it['qty']) : 'Liên hệ') ?></strong></li>
      <?php endforeach; ?>
    </ul>
    <dl>
      <div><dt>Tạm tính</dt><dd><?= e($o['subtotal'] > 0 ? format_price($o['subtotal']) : 'Liên hệ') ?></dd></div>
      <div><dt>Phí giao hàng</dt><dd><?= $o['shipping'] > 0 ? e(format_price($o['shipping'])) : 'Miễn phí' ?></dd></div>
      <?php foreach (($o['gifts'] ?? []) as $g): ?>
        <div><dt><?= e($g['label']) ?></dt><dd><?= $g['price'] > 0 ? e(format_price($g['price'])) : 'Miễn phí' ?></dd></div>
      <?php endforeach; ?>
      <?php if (!empty($o['vat'])): ?><div><dt>Thuế VAT (<?= (int) $o['vat_rate'] ?>%)</dt><dd><?= e(format_price((int) $o['vat'])) ?></dd></div><?php endif; ?>
      <div class="summary__total"><dt>Tổng cộng</dt><dd><?= e($o['total'] > 0 ? format_price($o['total']) : 'Liên hệ') ?></dd></div>
    </dl>
    <p class="summary__note">Thanh toán: <?= e($pay_labels[$o['pay']] ?? '') ?></p>
    <?php if (!empty($o['card'])): ?><p class="summary__note">Lời nhắn trên thiệp: “<?= e($o['card']) ?>”</p><?php endif;
    return ob_get_clean();
}

$state_labels = order_state_labels();

/* ============ Trang quản lý đơn của cửa hàng (link có mã ký) ============ */
$manageCode = (string) ($_GET['quan-ly'] ?? $_POST['quan-ly'] ?? '');
if ($manageCode !== '') {
    $key = (string) ($_GET['k'] ?? $_POST['k'] ?? '');
    if (!hash_equals(sign_code($manageCode), $key)) {
        http_response_code(403);
        $page_title = 'Link không hợp lệ';
        include ROOT . '/includes/header.php';
        echo '<section class="section"><div class="container empty-state empty-state--page">' . icon('lock')
            . '<h1>Link không hợp lệ</h1><p>Link quản lý đơn hàng không đúng hoặc đã bị chỉnh sửa.</p></div></section>';
        include ROOT . '/includes/footer.php';
        exit;
    }
    $selfUrl = base_path() . '/dat-hang.php?quan-ly=' . rawurlencode($manageCode) . '&k=' . $key;
    $o = find_order($manageCode);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $o) {
        $action = (string) ($_POST['action'] ?? '');
        if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
            flash('Phiên làm việc đã hết hạn, vui lòng thử lại.', 'error');
        } else {
            $res = match ($action) {
                'da-nhan-tien' => shop_order_mark_paid($o['code']),
                'xac-nhan-don' => shop_order_confirm_cod($o['code']),
                'huy-don'      => shop_order_cancel($o['code']),
                default        => null,
            };
            if ($res) {
                flash($res[1], $res[0]);
            }
        }
        redirect($selfUrl);
    }

    $state = $o ? order_state($o) : 'none';
    $page_title = 'Quản lý đơn ' . $manageCode;
    include ROOT . '/includes/header.php';
    ?>
    <section class="section">
      <div class="container container--narrow">
        <p class="eyebrow">Dành cho cửa hàng</p>
        <h1 class="section-title">Quản lý đơn <?= e($manageCode) ?></h1>
        <?php if (!$o): ?>
          <p class="notice">Không tìm thấy đơn hàng này.</p>
        <?php else:
          $paidAmt = order_paid_amount($o['code']);
          $holds   = array_filter(read_holds_raw(), fn($h) => ($h['code'] ?? '') === $o['code']); ?>
          <div class="summary hold-manage">
            <dl>
              <?php $codDone = $state === 'cod' && !empty($o['confirmed_at']); ?>
              <div><dt>Trạng thái</dt><dd><span class="hold-status hold-status--<?= e($codDone ? 'confirmed' : (['paid' => 'confirmed', 'cod' => 'pending', 'pending' => 'pending', 'partial' => 'pending'][$state] ?? 'expired')) ?>"><?= e($codDone ? 'Đã xác nhận' : ($state_labels[$state] ?? $state)) ?></span></dd></div>
              <div><dt>Khách hàng</dt><dd><?= e($o['name']) ?> – <a href="<?= e(tel_href($o['phone'])) ?>"><?= e($o['phone']) ?></a></dd></div>
              <div><dt>Địa chỉ</dt><dd><?= e($o['address']) ?></dd></div>
              <div><dt>Đặt lúc</dt><dd><?= e(order_time((int) $o['created'])) ?></dd></div>
              <?php if ($o['pay'] === 'chuyen-khoan'): ?>
                <div><dt>Đã nhận</dt><dd><?= e($paidAmt > 0 ? format_price($paidAmt) : '0₫') ?> / <?= e(format_price((int) $o['total'])) ?></dd></div>
                <div><dt>Nội dung chuyển khoản</dt><dd><strong><?= e($o['code']) ?></strong></dd></div>
              <?php endif; ?>
              <?php if (find_receipt($o['code'])): ?>
                <div><dt>Phiếu xác nhận</dt><dd class="share-btns"><a class="btn btn--sm btn--outline" href="<?= e(receipt_url($o['code'])) ?>" target="_blank" rel="noopener"><?= icon('receipt') ?>Mở phiếu</a> <button type="button" class="btn btn--sm btn--outline" data-copy="<?= e(receipt_url($o['code'])) ?>"><?= icon('copy') ?>Sao chép link</button> <a class="btn btn--sm btn--outline" href="<?= e(zalo_chat_url($o['phone'])) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Nhắn Zalo cho khách</a></dd></div>
              <?php endif; ?>
              <div><dt>Link đơn cho khách</dt><dd class="share-btns"><button type="button" class="btn btn--sm btn--outline" data-copy="<?= e(order_view_url($o['code'])) ?>"><?= icon('copy') ?>Sao chép link xem đơn</button><?php if (!find_receipt($o['code'])): ?> <a class="btn btn--sm btn--outline" href="<?= e(zalo_chat_url($o['phone'])) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Nhắn Zalo cho khách</a><?php endif; ?></dd></div>
              <?php foreach ($holds as $h): ?>
                <div><dt>Giữ món độc bản</dt><dd><?= e($h['name_product']) ?> – <?= e(hold_status($h) === 'expired' ? 'đã hết hạn' : 'đến ' . order_time((int) $h['until'])) ?></dd></div>
              <?php endforeach; ?>
            </dl>
            <h2 class="summary__title" style="margin-top:20px">Chi tiết đơn</h2>
            <?= order_summary($o, $pay_labels) ?>
            <form method="post" action="<?= e($selfUrl) ?>" class="modal__actions hold-manage__actions">
              <?= csrf_field() ?>
              <input type="hidden" name="quan-ly" value="<?= e($manageCode) ?>">
              <input type="hidden" name="k" value="<?= e($key) ?>">
              <?php if ($state === 'cod' && empty($o['confirmed_at'])): ?>
                <button type="submit" name="action" value="xac-nhan-don" class="btn btn--primary"><?= icon('check') ?>Xác nhận đơn (đã gọi khách)</button>
              <?php endif; ?>
              <?php if (in_array($state, ['pending', 'partial', 'expired'], true)): ?>
                <button type="submit" name="action" value="da-nhan-tien" class="btn btn--primary"><?= icon('check') ?>Xác nhận đã nhận đủ tiền</button>
              <?php endif; ?>
              <?php if ($state !== 'cancelled'): ?>
                <button type="submit" name="action" value="huy-don" class="btn btn--outline" data-confirm="Hủy đơn này<?= !empty($o['reserved']) ? ' và mở bán lại món độc bản' : '' ?>?">Hủy đơn</button>
              <?php endif; ?>
            </form>
            <?php if ($state === 'cod'): ?><p class="summary__note">Gọi khách xác nhận đơn rồi bấm <strong>Xác nhận đơn</strong><?= !empty($o['reserved']) ? ' – món độc bản đang được giữ riêng cho khách này và sẽ tự mở bán lại nếu quá ' . (int) cfg('order_cod_hours', 24) . ' giờ chưa xác nhận' : '' ?>.</p><?php endif; ?>
            <p class="summary__note">Chỉ bấm xác nhận khi đã thấy đủ tiền với nội dung <strong><?= e($o['code']) ?></strong> về tài khoản<?= auto_payments_enabled() ? ' (hệ thống thường đã tự xác nhận qua Casso)' : '' ?>. Sau khi giao món độc bản, đổi <code>'stock'</code> của sản phẩm thành <code>'da-ban'</code>.</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
    <?php
    include ROOT . '/includes/footer.php';
    exit;
}

/* ============ Trang của khách sau khi đặt ============ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Xem được khi: vừa đặt trong phiên này, hoặc mở bằng link có mã ký riêng (email, Tra cứu đơn hàng)
    $ma        = (string) ($_GET['ma'] ?? '');
    $mine      = $_SESSION['last_order'] ?? null;
    $inSession = $mine && $ma === $mine['code'];
    if (!$inSession && !hash_equals(customer_sig($ma), (string) ($_GET['t'] ?? ''))) {
        redirect(url('tra-cuu-don-hang'));
    }
    $order = find_order($ma) ?: ($inSession ? $mine : null);
    if (!$order) {                                  // đơn đã quá hạn lưu → còn phiếu thì mở phiếu
        redirect(find_receipt($ma) ? receipt_url($ma) : url('tra-cuu-don-hang'));
    }
    $fresh = ($_SESSION['order_fresh'] ?? '') === $ma;   // lần xem đầu tiên ngay sau khi đặt → xóa giỏ hàng
    unset($_SESSION['order_fresh']);
    $state = order_state($order);
    $bank  = cfg('bank', []);
    $paidAmt  = order_paid_amount($order['code']);
    $remain   = max(0, (int) $order['total'] - $paidAmt);
    $qr       = in_array($state, ['pending', 'partial'], true) ? vietqr_url($remain ?: (int) $order['total'], $order['code']) : '';
    $resUntil = (int) ($order['reserved_until'] ?? 0);
    $resNames = array_map(fn($s) => find_product($s)['name'] ?? $s, $order['reserved'] ?? []);
    $receipt  = find_receipt($order['code']);
    // Lưu đơn vào "Đơn hàng của tôi" trên trình duyệt của khách (main.js) để báo khi có phiếu / thay đổi
    $myOrder  = ['code' => $order['code'], 'type' => 'order', 'view' => order_view_url($order['code']), 'receipt' => receipt_url($order['code']),
                 'at' => (int) $order['created'], 'key' => $state . ($receipt ? '+r' : '')];

    $page_title = ['cod' => 'Đặt hàng thành công', 'paid' => 'Đã nhận thanh toán', 'cancelled' => 'Đơn hàng đã hủy'][$state] ?? 'Vui lòng chuyển khoản';
    include ROOT . '/includes/header.php';
    $purchase = $state === 'cod' || $state === 'paid'
        ? ' data-purchase="' . e(json_encode(['id' => $order['code'], 'value' => (int) $order['total'], 'items' => array_map(fn($i) => ['id' => $i['slug'], 'name' => $i['name'], 'price' => $i['price'], 'qty' => $i['qty']], $order['items'])], JSON_UNESCAPED_UNICODE)) . '"'
        : '';
    ?>
    <section class="section">
      <?php $payNow = in_array($state, ['pending', 'partial'], true) && !empty($bank['account']); ?>
      <div class="container order-done<?= $payNow ? ' order-done--pay' : '' ?>"<?= $fresh ? ' data-clear-cart' : '' ?> data-my-order="<?= e(json_encode($myOrder, JSON_UNESCAPED_SLASHES)) ?>"<?= $purchase ?><?= in_array($state, ['pending', 'partial'], true) ? ' data-order-watch="' . e($order['code']) . '" data-order-state="' . e($state) . '"' : '' ?>>
        <div class="order-done__head">
          <?php if ($state === 'cod' && $receipt): ?>
            <span class="order-done__icon"><?= icon('check') ?></span>
            <h1>Đơn hàng đã được xác nhận</h1>
            <p>Đơn <strong class="order-code"><?= e($order['code']) ?></strong> đã được cửa hàng xác nhận. Bạn thanh toán <strong><?= e($order['total'] > 0 ? format_price($order['total']) : 'theo báo giá') ?></strong> khi nhận hàng.</p>
          <?php elseif ($state === 'cod'): ?>
            <span class="order-done__icon"><?= icon('check') ?></span>
            <h1>Đặt hàng thành công!</h1>
            <p>Cảm ơn <?= e($order['name']) ?>. Mã đơn hàng của bạn là <strong class="order-code"><?= e($order['code']) ?></strong><br>
              Chúng tôi sẽ gọi số <strong><?= e($order['phone']) ?></strong> để xác nhận trong thời gian sớm nhất.
              <?php if ($resNames && empty($order['lost'])): ?><br>Món độc bản trong đơn được <strong>giữ riêng cho bạn</strong> trong lúc chờ xác nhận.<?php endif; ?></p>
          <?php elseif ($state === 'paid' && !empty($order['lost'])): ?>
            <span class="order-done__icon order-done__icon--wait"><?= icon('clock') ?></span>
            <h1>Đã nhận thanh toán – cửa hàng sẽ liên hệ bạn</h1>
            <p>Chúng tôi đã nhận <strong><?= e(format_price($paidAmt)) ?></strong> cho đơn <strong class="order-code"><?= e($order['code']) ?></strong><br>
              Rất tiếc, <strong><?= e(implode(', ', array_map(fn($s) => find_product($s)['name'] ?? $s, $order['lost']))) ?></strong> đã có khách khác đặt sau khi hết thời gian giữ món.<br>
              Cửa hàng sẽ gọi số <strong><?= e($order['phone']) ?></strong> để tư vấn mẫu tương tự hoặc hoàn tiền cho bạn.</p>
          <?php elseif ($state === 'paid'): ?>
            <span class="order-done__icon"><?= icon('check') ?></span>
            <h1>Đã nhận thanh toán – cảm ơn bạn!</h1>
            <p>Đơn hàng <strong class="order-code"><?= e($order['code']) ?></strong> đã được thanh toán <strong><?= e(format_price($paidAmt)) ?></strong>.<br>
              Chúng tôi sẽ đóng gói cẩn thận và gọi số <strong><?= e($order['phone']) ?></strong> để hẹn giao hàng.</p>
          <?php elseif ($state === 'cancelled'): ?>
            <span class="order-done__icon order-done__icon--muted"><?= icon('close') ?></span>
            <h1>Đơn hàng đã hủy</h1>
            <p>Đơn <strong class="order-code"><?= e($order['code']) ?></strong> đã được hủy. Nếu bạn đã chuyển khoản, vui lòng liên hệ để được hoàn tiền.</p>
          <?php elseif ($state === 'expired'): ?>
            <span class="order-done__icon order-done__icon--muted"><?= icon('clock') ?></span>
            <h1>Đã hết thời gian giữ món</h1>
            <p>Chúng tôi chưa nhận được thanh toán cho đơn <strong class="order-code"><?= e($order['code']) ?></strong> nên món độc bản đã được mở bán lại.<br>
              Nếu bạn đã chuyển khoản, vui lòng liên hệ ngay – chúng tôi sẽ kiểm tra và giữ lại món cho bạn nếu còn.</p>
          <?php else: ?>
            <span class="order-done__icon order-done__icon--wait"><?= icon('clock') ?></span>
            <h1>Đã nhận đơn hàng – vui lòng chuyển khoản</h1>
            <p>Mã đơn hàng <strong class="order-code"><?= e($order['code']) ?></strong>
              <?php if ($state === 'partial'): ?><br>Chúng tôi đã nhận <strong><?= e(format_price($paidAmt)) ?></strong>, còn thiếu <strong><?= e(format_price($remain)) ?></strong>.<?php endif; ?>
              <?php if ($resNames && $resUntil): ?><br>Món độc bản trong đơn được <strong>giữ riêng cho bạn đến <?= e(date('H:i', $resUntil)) ?></strong>&nbsp;(<?= (int) cfg('order_pending_minutes', 60) ?>&nbsp;phút) để chờ chuyển khoản.<?php endif; ?></p>
          <?php endif; ?>
          <?php if ($state !== 'cancelled' && ($fu = (string) ($order['fulfillment'] ?? '')) !== ''): ?>
            <p class="order-ship"><?= icon($fu === 'hoan-tat' ? 'check' : 'truck') ?><span><?= $fu === 'hoan-tat' ? 'Đơn hàng đã giao thành công – cảm ơn bạn đã tin chọn ' . e(cfg('site_name')) . '!' : 'Đơn hàng <strong>đang được giao</strong> – nhân viên giao hàng sẽ gọi trước khi đến.' ?></span></p>
          <?php endif; ?>
          <?php if (!$payNow && ($receipt || in_array($state, ['cod', 'pending', 'partial', 'paid'], true))): ?>
            <p class="order-done__receipt">
              <a class="btn <?= $receipt ? 'btn--primary' : 'btn--outline' ?>" href="<?= e(receipt_url($order['code'])) ?>"><?= icon('receipt') ?><?= $receipt ? 'Xem &amp; tải phiếu xác nhận' : 'Xem phiếu xác nhận' ?></a>
              <?php if (!$receipt): ?><small><?= $state === 'cod' ? 'Phiếu có ngay khi cửa hàng gọi xác nhận đơn' : 'Phiếu có ngay khi cửa hàng nhận được tiền' ?> – mở link phiếu để theo dõi trạng thái.</small><?php endif; ?>
            </p>
          <?php endif; ?>
        </div>

        <div class="order-done__grid">
          <div class="summary">
            <h2 class="summary__title">Chi tiết đơn hàng</h2>
            <?php if ($order['pay'] === 'chuyen-khoan'): ?>
              <p class="order-state"><span class="hold-status hold-status--<?= e(['paid' => 'confirmed', 'pending' => 'pending', 'partial' => 'pending'][$state] ?? 'expired') ?>"><?= e($state_labels[$state] ?? '') ?></span></p>
            <?php endif; ?>
            <?= order_summary($order, $pay_labels) ?>
            <?php if ($order['pay'] === 'chuyen-khoan' && $state !== 'cancelled'): ?>
              <ol class="steps hold-steps">
                <li class="is-done">Đặt đơn hàng</li>
                <li class="<?= $state === 'paid' ? 'is-done' : 'is-current' ?>">Chuyển khoản <?= e(format_price($state === 'partial' ? $remain : (int) $order['total'])) ?></li>
                <li class="<?= $state === 'paid' ? 'is-done' : '' ?>"><?= auto_payments_enabled() ? 'Tự động xác nhận' : 'Xác nhận thanh toán' ?> – đóng gói &amp; giao hàng</li>
              </ol>
              <?php if (in_array($state, ['pending', 'partial'], true)): ?><p class="summary__note">Trang này sẽ tự cập nhật khi chúng tôi nhận được tiền.</p><?php endif; ?>
            <?php endif; ?>
          </div>

          <?php if (in_array($state, ['pending', 'partial'], true) && !empty($bank['account'])): ?>
            <div class="summary bank-box">
              <h2 class="summary__title">Quét mã để chuyển khoản</h2>
              <?php if ($qr): ?><img class="bank-box__qr" src="<?= e($qr) ?>" alt="Mã QR chuyển khoản cho đơn <?= e($order['code']) ?>" width="300" height="356"><?php endif; ?>
              <p class="bank-box__mobile"><?= icon('phone') ?><span><strong>Trên điện thoại:</strong> nhấn giữ mã QR → <em>Lưu ảnh</em> → mở app ngân hàng, quét ảnh vừa lưu.</span></p>
              <div class="bank-box__receipt">
                <span class="bank-box__receipt-ask">Đã chuyển khoản xong?</span>
                <a class="btn btn--primary" href="<?= e(receipt_url($order['code'])) ?>"><?= icon('receipt') ?>Xem phiếu xác nhận</a>
                <small>Phiếu có ngay khi cửa hàng nhận tiền.</small>
              </div>
              <details class="bank-box__manual">
                <summary><span>Chuyển khoản thủ công</span><?= icon('down') ?></summary>
                <dl>
                <div><dt>Ngân hàng</dt><dd><?= e($bank['bank']) ?></dd></div>
                <div><dt>Số tài khoản</dt><dd><strong><?= e($bank['account']) ?></strong> <button type="button" class="btn btn--sm btn--outline" data-copy="<?= e($bank['account']) ?>"><?= icon('copy') ?>Sao chép</button></dd></div>
                <?php if (!empty($bank['holder'])): ?><div><dt>Chủ tài khoản</dt><dd><?= e($bank['holder']) ?></dd></div><?php endif; ?>
                <?php if ($order['total'] > 0): ?><div><dt>Số tiền</dt><dd><?= e(format_price($remain ?: (int) $order['total'])) ?></dd></div><?php endif; ?>
                <div><dt>Nội dung</dt><dd><strong><?= e($order['code']) ?></strong> <button type="button" class="btn btn--sm btn--outline" data-copy="<?= e($order['code']) ?>"><?= icon('copy') ?>Sao chép</button></dd></div>
                </dl>
                <p class="summary__note">Vui lòng ghi đúng nội dung chuyển khoản là mã đơn hàng. Chỉ chuyển khoản vào tài khoản hiển thị trên website.</p>
              </details>
            </div>
          <?php elseif ($state === 'paid'): ?>
            <div class="summary bank-box bank-box--paid">
              <h2 class="summary__title"><?= icon('check') ?>Bước tiếp theo</h2>
              <ol class="steps">
                <li>Món ngọc được đóng hộp quà cẩn thận, kèm giấy kiểm định.</li>
                <li>Nhân viên gọi điện hẹn thời gian giao hàng.</li>
                <li>Bạn nhận hàng và kiểm tra sản phẩm.</li>
              </ol>
            </div>
          <?php elseif ($state === 'cod'): ?>
            <div class="summary">
              <h2 class="summary__title">Bước tiếp theo</h2>
              <ol class="steps">
                <li>Nhân viên gọi điện xác nhận đơn và tư vấn ni tay/màu sắc (nếu cần).</li>
                <li>Sản phẩm được đóng hộp quà cẩn thận, kèm giấy kiểm định.</li>
                <li>Bạn kiểm tra hàng và thanh toán khi nhận.</li>
              </ol>
              <?php if (cfg('zalo')): ?><a class="btn btn--outline" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Nhắn Zalo cho shop</a><?php endif; ?>
            </div>
          <?php else: ?>
            <div class="summary">
              <h2 class="summary__title">Cần hỗ trợ?</h2>
              <p>Gọi <a href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a><?= cfg('zalo') ? ' hoặc nhắn Zalo' : '' ?> để được hỗ trợ.</p>
              <?php if (cfg('zalo')): ?><a class="btn btn--outline" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Nhắn Zalo cho shop</a><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <p class="order-keep"><?= icon('receipt') ?><span>Bạn có thể xem lại đơn hàng này bất cứ lúc nào tại <a href="<?= e(url('tra-cuu-don-hang')) ?>">Tra cứu đơn hàng</a> (nhập mã <strong><?= e($order['code']) ?></strong> và số điện thoại). Website cũng tự báo khi đơn được xác nhận nếu bạn quay lại bằng thiết bị này.</span></p>
        <div class="empty-state__actions">
          <a class="btn btn--primary" href="<?= e(url('san-pham')) ?>">Tiếp tục mua sắm</a>
          <a class="btn btn--outline" href="<?= e(url()) ?>">Về trang chủ</a>
        </div>
      </div>
    </section>
    <?php
    include ROOT . '/includes/footer.php';
    exit;
}

/* ============ Nhận dữ liệu đơn hàng ============ */
$errors = [];
if ($guard = form_guard('order', 20)) {
    $errors[] = $guard;
}

$name    = clean_text('ho-ten', 80);
$phone   = clean_text('dien-thoai', 20);
$email   = clean_text('email', 120);
$address = clean_text('dia-chi', 300);
$note    = clean_text('ghi-chu', 1000);
$payIn   = (string) ($_POST['thanh-toan'] ?? '');
$pay     = ($payIn === 'chuyen-khoan' || !cod_enabled()) && !empty(cfg('bank')['account']) ? 'chuyen-khoan' : 'cod';

if (mb_strlen($name) < 2)                                  $errors[] = 'Vui lòng nhập họ và tên.';
if (!valid_phone($phone))                                  $errors[] = 'Số điện thoại chưa đúng (ví dụ: 0912 345 678).';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email chưa đúng định dạng.';
if (mb_strlen($address) < 8)                               $errors[] = 'Vui lòng nhập địa chỉ nhận hàng đầy đủ.';
// Có tài khoản ngân hàng → khách phải tự chọn COD hay chuyển khoản (không chọn sẵn, tránh đặt nhầm)
if (!empty(cfg('bank')['account']) && cod_enabled() && !in_array($payIn, ['cod', 'chuyen-khoan'], true)) $errors[] = 'Vui lòng chọn hình thức thanh toán.';

// Giỏ hàng: [{"slug": "...", "qty": 1}, ...] — giá luôn tính lại theo dữ liệu trên máy chủ
$raw   = json_decode((string) ($_POST['items'] ?? ''), true);
$items = [];
if (is_array($raw)) {
    foreach (array_slice($raw, 0, 50) as $row) {
        $prod = is_array($row) ? find_product((string) ($row['slug'] ?? '')) : null;
        if (!$prod) {
            continue;
        }
        if (!can_buy($prod)) {
            $errors[] = '“' . $prod['name'] . '” ' . (is_sold($prod) ? 'đã có chủ' : (hold_for($prod['slug']) ? 'đang được khách khác giữ' : 'tạm hết hàng'))
                . ' – vui lòng xóa khỏi giỏ hàng hoặc liên hệ để được tư vấn mẫu tương tự.';
            continue;
        }
        $qty = is_unique($prod) ? 1 : max(1, min(20, (int) ($row['qty'] ?? 1)));
        $items[$prod['slug']] = ['slug' => $prod['slug'], 'name' => $prod['name'], 'price' => (int) $prod['price'],
                                 'qty' => is_unique($prod) ? 1 : ($items[$prod['slug']]['qty'] ?? 0) + $qty];
    }
}
if (!$items && !$errors) {
    $errors[] = 'Giỏ hàng trống hoặc sản phẩm không còn kinh doanh.';
}

// Dịch vụ quà tặng
$giftCfg = cfg('gift_options', []);
$gifts   = [];
foreach ((array) ($_POST['qua-tang'] ?? []) as $key) {
    if (is_string($key) && isset($giftCfg[$key])) {
        $gifts[$key] = ['label' => $giftCfg[$key]['label'], 'price' => (int) $giftCfg[$key]['price']];
    }
}
$card     = isset($gifts['thiep']) ? clean_text('loi-nhan', 300) : '';
$giftFee  = array_sum(array_column($gifts, 'price'));

$items    = array_values($items);
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $items));
$shipping = (int) cfg('shipping_fee', 0);
$freeFrom = (int) cfg('free_ship_from', 0);
if ($shipping > 0 && $freeFrom > 0 && $subtotal >= $freeFrom) {
    $shipping = 0;
}
$vat      = $subtotal > 0 ? vat_amount($subtotal + $shipping + $giftFee) : 0;
$vat_rate = $vat ? vat_rate() : 0;
$total = $subtotal > 0 ? $subtotal + $shipping + $giftFee + $vat : 0;
$code  = 'NP' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

// Đơn chuyển khoản có món độc bản → giữ riêng các món đó chờ tiền (tất cả hoặc không)
// Món độc bản: đơn chuyển khoản giữ chờ tiền, đơn COD giữ chờ cửa hàng gọi xác nhận (tất cả hoặc không)
$reserved = array_values(array_map(fn($i) => $i['slug'], array_filter($items, fn($i) => is_unique(find_product($i['slug'])))));
$reservedUntil = 0;
if (!$errors && $reserved) {
    $reservedUntil = $pay === 'chuyen-khoan'
        ? time() + max(5, (int) cfg('order_pending_minutes', 60)) * 60
        : time() + max(1, (int) cfg('order_cod_hours', 24)) * 3600;
    $taken = reserve_order_items($reserved, ['type' => 'order', 'code' => $code, 'name' => $name, 'phone' => $phone, 'pay' => $pay,
        'amount' => $total, 'deposit' => $total, 'status' => 'pending', 'created' => time(), 'until' => $reservedUntil]);
    foreach ($taken as $s) {
        $errors[] = 'Rất tiếc, “' . (find_product($s)['name'] ?? $s) . '” vừa được khách khác đặt trước. Vui lòng xóa khỏi giỏ hàng hoặc liên hệ để được tư vấn mẫu tương tự.';
    }
}

if ($errors) {
    $page_title = 'Chưa đặt được hàng';
    include ROOT . '/includes/header.php';
    ?>
    <section class="section">
      <div class="container empty-state empty-state--page">
        <?= icon('gem') ?>
        <h1>Chưa đặt được hàng</h1>
        <ul class="error-list" role="alert">
          <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul>
        <p>Giỏ hàng của bạn vẫn được giữ nguyên. Vui lòng quay lại và kiểm tra thông tin, hoặc gọi <a href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a> để đặt hàng nhanh.</p>
        <div class="empty-state__actions">
          <a class="btn btn--primary" href="<?= e(url('gio-hang')) ?>">Quay lại giỏ hàng</a>
        </div>
      </div>
    </section>
    <?php
    include ROOT . '/includes/footer.php';
    exit;
}

$gifts = array_values($gifts);
$created = time();
$order = compact('code', 'created', 'name', 'phone', 'email', 'address', 'note', 'pay', 'items', 'subtotal', 'shipping', 'total', 'gifts', 'card');
if ($vat) {
    $order['vat']      = $vat;
    $order['vat_rate'] = $vat_rate;
}
$order['reserved']       = $reserved;
$order['reserved_until'] = $reservedUntil;
save_order($order);
$giftText = implode(', ', array_column($gifts, 'label')) . ($card ? ' | Lời nhắn: ' . $card : '');
$manage   = order_manage_url($code);

// ---- Lưu file Excel ----
$lines = implode(' | ', array_map(fn($i) => $i['name'] . ' x' . $i['qty'], $items));
save_csv('don-hang',
    ['Thời gian', 'Mã đơn', 'Họ tên', 'Điện thoại', 'Email', 'Địa chỉ', 'Sản phẩm', 'Tạm tính', 'Phí giao', 'Dịch vụ quà tặng', 'Tổng', 'Thanh toán', 'Ghi chú'],
    [date('d/m/Y H:i'), $code, $name, $phone, $email, $address, $lines, $subtotal, $shipping, $giftText, $total, $pay_labels[$pay], $note]
);

// ---- Gửi email & Telegram ----
$rows = '';
foreach ($items as $i) {
    $rows .= '<tr><td style="padding:6px 8px;border-bottom:1px solid #eee">' . e($i['name']) . '</td>'
        . '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center">' . (int) $i['qty'] . '</td>'
        . '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right">' . e($i['price'] > 0 ? format_price($i['price'] * $i['qty']) : 'Liên hệ') . '</td></tr>';
}
$table = '<table style="border-collapse:collapse;width:100%;max-width:600px">'
    . '<tr style="background:#0b5a32;color:#fff"><th style="padding:8px;text-align:left">Sản phẩm</th><th style="padding:8px">SL</th><th style="padding:8px;text-align:right">Thành tiền</th></tr>'
    . $rows
    . '<tr><td colspan="2" style="padding:8px;text-align:right"><strong>Tổng cộng</strong></td><td style="padding:8px;text-align:right"><strong>' . e($total > 0 ? format_price($total) : 'Liên hệ') . '</strong></td></tr></table>';

$resText = $reserved ? ($pay === 'chuyen-khoan' ? 'Giữ món độc bản chờ chuyển khoản đến ' : 'Giữ món độc bản chờ gọi xác nhận (bấm "Xác nhận đơn" trong link) đến ') . date('H:i d/m', $reservedUntil) : '';
$info = '<p><strong>Mã đơn:</strong> ' . e($code) . '<br><strong>Khách hàng:</strong> ' . e($name)
    . '<br><strong>Điện thoại:</strong> ' . e($phone) . ($email ? '<br><strong>Email:</strong> ' . e($email) : '')
    . '<br><strong>Địa chỉ:</strong> ' . e($address) . '<br><strong>Thanh toán:</strong> ' . e($pay_labels[$pay])
    . ($note ? '<br><strong>Ghi chú:</strong> ' . nl2br(e($note)) : '')
    . ($giftText ? '<br><strong>Dịch vụ quà tặng:</strong> ' . e($giftText) : '')
    . ($resText ? '<br><strong>' . e($resText) . '</strong>' : '') . '</p>';

send_mail(cfg('order_email'), 'Đơn hàng mới ' . $code . ' – ' . $name, '<h2>Đơn hàng mới</h2>' . $info . $table
    . '<p><a href="' . e($manage) . '" style="display:inline-block;padding:10px 18px;background:#0b5a32;color:#fff;border-radius:6px;text-decoration:none">Quản lý đơn (xác nhận tiền / hủy)</a></p>', $email);
notify_telegram("🛍 ĐƠN HÀNG MỚI $code" . ($pay === 'chuyen-khoan' ? ' – CHỜ CHUYỂN KHOẢN' : '') . "\n"
    . implode("\n", array_map(fn($i) => '• ' . $i['name'] . ' x' . $i['qty'], $items))
    . "\nTổng: " . ($total > 0 ? format_price($total) : 'Liên hệ') . ' – ' . $pay_labels[$pay]
    . "\nKhách: $name – $phone (Zalo: " . zalo_chat_url($phone) . ")\nĐịa chỉ: $address" . ($giftText ? "\nQuà tặng: $giftText" : '') . ($note ? "\nGhi chú: $note" : '')
    . ($resText ? "\n🔒 $resText" : '') . "\n\nQuản lý đơn:\n$manage");

if ($email) {
    send_mail($email, cfg('site_name') . ' – ' . ($pay === 'chuyen-khoan' ? 'Đơn hàng ' . $code . ' chờ chuyển khoản' : 'Xác nhận đơn hàng ' . $code),
        '<h2>Cảm ơn bạn đã đặt hàng!</h2><p>' . e(cfg('site_name')) . ' đã nhận được đơn hàng của bạn'
        . ($pay === 'chuyen-khoan' ? ' và đang chờ chuyển khoản với nội dung <strong>' . e($code) . '</strong>.' : ' và sẽ liên hệ xác nhận trong thời gian sớm nhất.') . '</p>'
        . $info . $table
        . '<p><a href="' . e(order_view_url($code)) . '" style="display:inline-block;padding:10px 18px;background:#0b5a32;color:#fff;border-radius:6px;text-decoration:none">Xem đơn hàng</a></p>'
        . '<p style="color:#666;font-size:13px">Link trên dùng được bất cứ lúc nào để xem trạng thái đơn' . ($pay === 'chuyen-khoan' ? ', mã QR chuyển khoản' : '') . ' và tải phiếu xác nhận khi cửa hàng đã xác nhận.<br>Hotline: ' . e(cfg('hotline')) . '</p>', cfg('email'));
}

form_mark_sent('order');
$_SESSION['last_order']  = $order;
$_SESSION['order_fresh'] = $code;
redirect(order_view_url($code));
