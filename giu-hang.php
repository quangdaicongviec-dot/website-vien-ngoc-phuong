<?php
/**
 * Giữ hàng / đặt cọc — 2 bước:
 *  1. Khách gửi yêu cầu → món được giữ TẠM 'hold_pending_minutes' phút, trang hiện mã QR chuyển cọc.
 *  2. Shop kiểm tra tài khoản, mở link quản lý (gửi qua Telegram / email) và bấm "Xác nhận đã nhận cọc"
 *     → món được giữ chính thức 'hold_hours' giờ. Không xác nhận thì hết giờ tự mở bán lại.
 * Lượt giữ lưu trong data/don-hang/giu-hang.json, nhật ký trong data/don-hang/giu-hang-YYYY-MM.csv.
 */
require __DIR__ . '/includes/init.php';

$page_title = 'Giữ hàng';
$head_extra = '<meta name="robots" content="noindex">';
$body_class = 'page-order';
$mini_footer = true;   // trang thanh toán: chân trang gọn

/* ============ Bản chạy thử tĩnh: trang khung, nội dung do demo-static.js dựng ============ */
if (STATIC_DEMO && isset($_GET['demo'])) {
    $page_title = 'Đặt cọc giữ hàng';
    include ROOT . '/includes/header.php';
    echo '<section class="section"><div class="container" data-demo-hold></div></section>';
    include ROOT . '/includes/footer.php';
    exit;
}

/* ============ Kiểm tra trạng thái (trang của khách tự hỏi định kỳ) ============ */
if (isset($_GET['trang-thai'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    $code = (string) $_GET['trang-thai'];
    $h    = find_hold_by_code($code);
    echo json_encode(['status' => $h ? hold_status($h) : 'none', 'receipt' => (bool) find_receipt($code)]);
    exit;
}

/* ============ Trang quản lý của shop (link có mã ký) ============ */
$manageCode = (string) ($_GET['quan-ly'] ?? $_POST['quan-ly'] ?? '');
if ($manageCode !== '') {
    $key = (string) ($_GET['k'] ?? $_POST['k'] ?? '');
    if (!hash_equals(sign_code($manageCode), $key)) {
        http_response_code(403);
        $page_title = 'Link không hợp lệ';
        include ROOT . '/includes/header.php';
        echo '<section class="section"><div class="container empty-state empty-state--page">' . icon('lock')
            . '<h1>Link không hợp lệ</h1><p>Link quản lý giữ hàng không đúng hoặc đã bị chỉnh sửa.</p></div></section>';
        include ROOT . '/includes/footer.php';
        exit;
    }
    $selfUrl = base_path() . '/giu-hang.php?quan-ly=' . rawurlencode($manageCode) . '&k=' . $key;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
            flash('Phiên làm việc đã hết hạn, vui lòng thử lại.', 'error');
        } else {
            $res = match ($action) {
                'xac-nhan' => shop_hold_confirm($manageCode),
                'gia-han'  => shop_hold_extend($manageCode),
                'huy'      => shop_hold_cancel($manageCode),
                default    => null,
            };
            if ($res) {
                flash($res[1], $res[0]);
            }
        }
        redirect($selfUrl);
    }

    $h      = find_hold_by_code($manageCode);
    $status = $h ? hold_status($h) : 'none';
    $p      = $h ? find_product($h['slug']) : null;
    $page_title = 'Quản lý giữ hàng ' . $manageCode;
    include ROOT . '/includes/header.php';
    ?>
    <section class="section">
      <div class="container container--narrow">
        <p class="eyebrow">Dành cho cửa hàng</p>
        <h1 class="section-title">Quản lý giữ hàng <?= e($manageCode) ?></h1>
        <?php if (!$h): ?>
          <p class="notice">Không tìm thấy lượt giữ này. Có thể đã bị hủy, hoặc sản phẩm đã được khách khác giữ.</p>
        <?php else: ?>
          <div class="summary hold-manage">
            <?php if ($p): ?>
              <a class="hold-product" href="<?= e(product_url($p)) ?>">
                <img src="<?= e(product_image($p)) ?>" alt="" width="96" height="96">
                <span><strong><?= e($p['name']) ?></strong><small><?= e(format_price($p['price'])) ?></small></span>
              </a>
            <?php endif; ?>
            <dl>
              <div><dt>Trạng thái</dt><dd><span class="hold-status hold-status--<?= e($status) ?>"><?= e(['pending' => 'Chờ khách chuyển cọc', 'confirmed' => 'Đã nhận cọc – đang giữ', 'expired' => 'Đã hết hạn'][$status] ?? $status) ?></span></dd></div>
              <div><dt>Khách hàng</dt><dd><?= e($h['name']) ?> – <a href="<?= e(tel_href($h['phone'])) ?>"><?= e($h['phone']) ?></a></dd></div>
              <div><dt>Tiền cọc cần nhận</dt><dd><strong><?= e(format_price($h['deposit'])) ?></strong></dd></div>
              <div><dt>Nội dung chuyển khoản</dt><dd><strong><?= e($h['code']) ?></strong></dd></div>
              <div><dt>Gửi yêu cầu lúc</dt><dd><?= e(hold_time((int) $h['created'])) ?></dd></div>
              <div><dt><?= $status === 'confirmed' ? 'Giữ đến' : 'Hạn chờ cọc' ?></dt><dd><?= e(hold_time((int) $h['until'])) ?><?= !empty($h['extended_at']) ? ' (đã gia hạn)' : '' ?></dd></div>
              <?php if (!empty($h['agreed_at'])): ?><div><dt>Đồng ý chính sách cọc</dt><dd>Phiên bản <?= e($h['policy'] ?? '') ?> – lúc <?= e(hold_time((int) $h['agreed_at'])) ?></dd></div><?php endif; ?>
              <?php if (!empty($h['email'])): ?><div><dt>Email khách</dt><dd><?= e($h['email']) ?></dd></div><?php endif; ?>
              <?php if (find_receipt($h['code'])): ?>
                <div><dt>Phiếu xác nhận cọc</dt><dd class="share-btns"><a class="btn btn--sm btn--outline" href="<?= e(receipt_url($h['code'])) ?>" target="_blank" rel="noopener"><?= icon('receipt') ?>Mở phiếu</a> <button type="button" class="btn btn--sm btn--outline" data-copy="<?= e(receipt_url($h['code'])) ?>"><?= icon('copy') ?>Sao chép link</button> <a class="btn btn--sm btn--outline" href="<?= e(zalo_chat_url($h['phone'])) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Nhắn Zalo cho khách</a></dd></div>
              <?php endif; ?>
            </dl>
            <form method="post" action="<?= e($selfUrl) ?>" class="modal__actions hold-manage__actions">
              <?= csrf_field() ?>
              <input type="hidden" name="quan-ly" value="<?= e($manageCode) ?>">
              <input type="hidden" name="k" value="<?= e($key) ?>">
              <?php if ($status !== 'confirmed'): ?>
                <button type="submit" name="action" value="xac-nhan" class="btn btn--primary"><?= icon('check') ?>Xác nhận đã nhận cọc</button>
              <?php elseif (empty($h['extended_at'])): ?>
                <button type="submit" name="action" value="gia-han" class="btn btn--outline" data-confirm="Gia hạn giữ thêm <?= (int) cfg('hold_extend_hours', 24) ?> giờ? (theo chính sách, mỗi lượt giữ chỉ gia hạn 1 lần)"><?= icon('clock') ?>Gia hạn thêm <?= (int) cfg('hold_extend_hours', 24) ?> giờ</button>
              <?php endif; ?>
              <button type="submit" name="action" value="huy" class="btn btn--outline" data-confirm="Hủy giữ hàng và mở bán lại sản phẩm này?">Hủy giữ hàng</button>
            </form>
            <p class="summary__note">Chỉ bấm xác nhận sau khi đã thấy tiền <?= e(format_price($h['deposit'])) ?> với nội dung <strong><?= e($h['code']) ?></strong> về tài khoản. Khi khách đã mua xong, hãy đổi <code>'stock'</code> của sản phẩm thành <code>'da-ban'</code>.</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
    <?php
    include ROOT . '/includes/footer.php';
    exit;
}

/* ============ Trang trạng thái của khách ============ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Xem được khi: vừa gửi yêu cầu trong phiên này, hoặc mở bằng link có mã ký riêng (email, Tra cứu đơn hàng)
    $mine      = $_SESSION['last_hold'] ?? null;
    $code      = (string) ($_GET['ma'] ?? '');
    $inSession = $mine && $code === $mine['code'];
    if (!$inSession && !hash_equals(customer_sig($code), (string) ($_GET['t'] ?? ''))) {
        redirect(url('tra-cuu-don-hang'));
    }
    $live = find_hold_by_code($code);
    if ($live && ($live['type'] ?? 'hold') !== 'hold') {
        $live = null;
    }
    $status = $live ? hold_status($live) : 'expired';
    // Lượt giữ đã nhận cọc nhưng nay đã kết thúc / bị hủy / đã dọn → trang phiếu (có trạng thái mới nhất)
    if ($status === 'expired' && find_receipt($code)) {
        redirect(receipt_url($code));
    }
    if (!$live && !$inSession) {
        redirect(url('tra-cuu-don-hang'));
    }
    $h      = $live ?: $mine;
    $myHold = ['code' => $h['code'], 'type' => 'hold', 'view' => hold_view_url($h['code']), 'receipt' => receipt_url($h['code']),
               'at' => (int) $h['created'], 'key' => $status . (find_receipt($h['code']) ? '+r' : '')];
    $p      = find_product($h['slug']);
    $bank   = cfg('bank', []);
    $qr     = vietqr_url((int) $h['deposit'], $h['code']);
    $page_title = $status === 'confirmed' ? 'Đã giữ hàng cho bạn' : 'Đặt cọc giữ hàng';
    include ROOT . '/includes/header.php';
    ?>
    <section class="section">
      <?php $payNow = $status === 'pending' && !empty($bank['account']); ?>
      <div class="container order-done<?= $payNow ? ' order-done--pay' : '' ?>" data-hold-status="<?= e($status) ?>" data-hold-code="<?= e($h['code']) ?>" data-my-order="<?= e(json_encode($myHold, JSON_UNESCAPED_SLASHES)) ?>">
        <div class="order-done__head">
          <?php if ($status === 'confirmed'): ?>
            <span class="order-done__icon"><?= icon('check') ?></span>
            <h1>Đã nhận cọc – món ngọc đang được giữ cho bạn!</h1>
            <p><strong><?= e($h['name_product']) ?></strong> được giữ riêng cho <?= e($h['name']) ?> đến <strong class="nowrap"><?= e(hold_time((int) $h['until'])) ?></strong>.<br>Mã giữ hàng <strong class="order-code"><?= e($h['code']) ?></strong></p>
          <?php elseif ($status === 'pending'): ?>
            <span class="order-done__icon order-done__icon--wait"><?= icon('clock') ?></span>
            <h1>Vui lòng chuyển khoản đặt cọc</h1>
            <p>Món ngọc đang được <strong>giữ tạm</strong> cho bạn đến <strong><?= e(date('H:i', (int) $h['until'])) ?></strong>&nbsp;(<?= (int) cfg('hold_pending_minutes', 60) ?>&nbsp;phút).<br>
              <?= auto_payments_enabled() ? 'Hệ thống <strong>tự động xác nhận</strong> ngay khi nhận được cọc (thường chỉ vài phút) và' : 'Ngay khi nhận được cọc, chúng tôi sẽ xác nhận và' ?> giữ món ngọc cho bạn <strong><?= (int) cfg('hold_hours', 24) ?> giờ</strong>.<br>
              Mã giữ hàng <strong class="order-code"><?= e($h['code']) ?></strong></p>
          <?php else: ?>
            <span class="order-done__icon order-done__icon--muted"><?= icon('clock') ?></span>
            <h1>Đã hết thời gian chờ đặt cọc</h1>
            <p>Lượt giữ tạm <strong><?= e($h['code']) ?></strong> đã hết hạn và sản phẩm được mở bán lại.<br>Nếu bạn đã chuyển khoản, vui lòng liên hệ ngay để chúng tôi kiểm tra và hỗ trợ.</p>
          <?php endif; ?>
          <?php $holdReceipt = find_receipt($h['code']); if (!$payNow && ($holdReceipt || $status === 'pending')): ?>
            <p class="order-done__receipt">
              <a class="btn <?= $holdReceipt ? 'btn--primary' : 'btn--outline' ?>" href="<?= e(receipt_url($h['code'])) ?>"><?= icon('receipt') ?><?= $holdReceipt ? 'Xem &amp; tải phiếu xác nhận đặt cọc' : 'Xem phiếu xác nhận đặt cọc' ?></a>
              <?php if (!$holdReceipt): ?><small>Phiếu có ngay khi cửa hàng nhận được tiền cọc – mở link phiếu để theo dõi trạng thái.</small><?php endif; ?>
            </p>
          <?php endif; ?>
        </div>

        <div class="order-done__grid">
          <div class="summary">
            <h2 class="summary__title">Thông tin giữ hàng</h2>
            <?php if ($p): ?>
              <a class="hold-product" href="<?= e(product_url($p)) ?>">
                <img src="<?= e(product_image($p)) ?>" alt="" width="96" height="96">
                <span><strong><?= e($p['name']) ?></strong><small><?= e(format_price($p['price'])) ?></small></span>
              </a>
            <?php endif; ?>
            <dl>
              <div><dt>Trạng thái</dt><dd><span class="hold-status hold-status--<?= e($status) ?>"><?= e(['pending' => 'Chờ đặt cọc', 'confirmed' => 'Đã nhận cọc', 'expired' => 'Đã hết hạn'][$status] ?? '') ?></span></dd></div>
              <div><dt><?= $status === 'confirmed' ? 'Giữ đến' : 'Chuyển cọc trước' ?></dt><dd><?= e(hold_time((int) $h['until'])) ?></dd></div>
              <div class="summary__total"><dt>Tiền đặt cọc</dt><dd><?= e(format_price((int) $h['deposit'])) ?></dd></div>
            </dl>
            <ol class="steps hold-steps">
              <li class="is-done">Gửi yêu cầu giữ hàng</li>
              <li class="<?= $status === 'confirmed' ? 'is-done' : 'is-current' ?>">Chuyển khoản đặt cọc <?= e(format_price((int) $h['deposit'])) ?></li>
              <li class="<?= $status === 'confirmed' ? 'is-done' : '' ?>"><?= auto_payments_enabled() ? 'Tự động xác nhận' : 'Shop xác nhận' ?> – giữ hàng <?= (int) cfg('hold_hours', 24) ?> giờ</li>
            </ol>
            <?php if ($status === 'pending'): ?><p class="summary__note" data-hold-watch>Trang này sẽ tự cập nhật khi chúng tôi xác nhận đã nhận cọc.</p><?php endif; ?>
          </div>

          <?php if ($status === 'pending' && !empty($bank['account'])): ?>
            <div class="summary bank-box">
              <h2 class="summary__title">Quét mã để chuyển cọc</h2>
              <?php if ($qr): ?><img class="bank-box__qr" src="<?= e($qr) ?>" alt="Mã QR đặt cọc cho <?= e($h['code']) ?>" width="300" height="356"><?php endif; ?>
              <p class="bank-box__mobile"><?= icon('phone') ?><span><strong>Trên điện thoại:</strong> nhấn giữ mã QR → <em>Lưu ảnh</em> → mở app ngân hàng, quét ảnh vừa lưu.</span></p>
              <div class="bank-box__receipt">
                <span class="bank-box__receipt-ask">Đã chuyển cọc xong?</span>
                <a class="btn btn--primary" href="<?= e(receipt_url($h['code'])) ?>"><?= icon('receipt') ?>Xem phiếu đặt cọc</a>
                <small>Phiếu có ngay khi cửa hàng nhận tiền cọc.</small>
              </div>
              <details class="bank-box__manual">
                <summary><span>Chuyển khoản thủ công</span><?= icon('down') ?></summary>
                <dl>
                <div><dt>Ngân hàng</dt><dd><?= e($bank['bank']) ?></dd></div>
                <div><dt>Số tài khoản</dt><dd><strong><?= e($bank['account']) ?></strong> <button type="button" class="btn btn--sm btn--outline" data-copy="<?= e($bank['account']) ?>"><?= icon('copy') ?>Sao chép</button></dd></div>
                <?php if (!empty($bank['holder'])): ?><div><dt>Chủ tài khoản</dt><dd><?= e($bank['holder']) ?></dd></div><?php endif; ?>
                <div><dt>Số tiền</dt><dd><?= e(format_price((int) $h['deposit'])) ?></dd></div>
                <div><dt>Nội dung</dt><dd><strong><?= e($h['code']) ?></strong> <button type="button" class="btn btn--sm btn--outline" data-copy="<?= e($h['code']) ?>"><?= icon('copy') ?>Sao chép</button></dd></div>
                </dl>
                <p class="summary__note">Vui lòng ghi đúng nội dung chuyển khoản là mã giữ hàng. Chỉ chuyển vào tài khoản hiển thị trên website.</p>
              </details>
            </div>
          <?php elseif ($status === 'pending'): ?>
            <div class="summary">
              <h2 class="summary__title">Đặt cọc thế nào?</h2>
              <p>Nhân viên sẽ liên hệ và gửi thông tin chuyển khoản đặt cọc cho bạn ngay.</p>
              <?php if (cfg('zalo')): ?><a class="btn btn--outline" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Nhắn Zalo cho shop</a><?php endif; ?>
            </div>
          <?php else: ?>
            <div class="summary">
              <h2 class="summary__title">Cần hỗ trợ?</h2>
              <p>Gọi <a href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a><?= cfg('zalo') ? ' hoặc nhắn Zalo' : '' ?> để được tư vấn thêm.</p>
              <?php if (cfg('zalo')): ?><a class="btn btn--outline" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Nhắn Zalo cho shop</a><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <p class="order-keep"><?= icon('receipt') ?><span>Bạn có thể xem lại lượt giữ hàng này bất cứ lúc nào tại <a href="<?= e(url('tra-cuu-don-hang')) ?>">Tra cứu đơn hàng</a> (nhập mã <strong><?= e($h['code']) ?></strong> và số điện thoại). Website cũng tự báo khi cửa hàng đã nhận cọc nếu bạn quay lại bằng thiết bị này.</span></p>
        <div class="empty-state__actions">
          <?php if ($p): ?><a class="btn btn--primary" href="<?= e(product_url($p)) ?>">Quay lại sản phẩm</a><?php endif; ?>
          <a class="btn btn--outline" href="<?= e(url('san-pham')) ?>">Xem sản phẩm khác</a>
        </div>
      </div>
    </section>
    <?php
    include ROOT . '/includes/footer.php';
    exit;
}

/* ============ Nhận yêu cầu giữ hàng của khách ============ */
$errors = [];
if ($guard = form_guard('hold', 30)) {
    $errors[] = $guard;
}
$name  = clean_text('ho-ten', 80);
$phone = clean_text('dien-thoai', 20);
$email = clean_text('email', 120);
$p     = find_product((string) ($_POST['sp'] ?? ''));
$digits = preg_replace('/\D/', '', $phone);

if (mb_strlen($name) < 2)  $errors[] = 'Vui lòng nhập họ và tên.';
if (!valid_phone($phone))  $errors[] = 'Số điện thoại chưa đúng (ví dụ: 0912 345 678).';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email chưa đúng định dạng.';
if (empty($_POST['dong-y']))  $errors[] = 'Vui lòng đánh dấu đồng ý Chính sách đặt cọc – giữ hàng.';
if (!$p)                   $errors[] = 'Không tìm thấy sản phẩm.';
elseif (!can_hold($p))     $errors[] = 'Sản phẩm này hiện không thể giữ (đã có chủ, đang có khách giữ hoặc không áp dụng giữ hàng).';
// Mỗi số điện thoại chỉ có 1 yêu cầu đang chờ cọc (chống giữ hàng ảo)
foreach (all_holds() as $other) {
    if (($other['type'] ?? 'hold') === 'hold' && ($other['status'] ?? '') === 'pending' && preg_replace('/\D/', '', $other['phone']) === $digits) {
        $errors[] = 'Số điện thoại này đang có một yêu cầu giữ hàng chờ đặt cọc (' . $other['name_product'] . '). Vui lòng hoàn tất đặt cọc trước khi giữ món khác.';
        break;
    }
}

$hold = null;
if (!$errors) {
    $hold = [
        'code'         => 'GIU' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
        'slug'         => $p['slug'],
        'name_product' => $p['name'],
        'name'         => $name,
        'phone'        => $phone,
        'email'        => $email,
        'deposit'      => deposit_amount($p),
        'agreed_at'    => time(),                              // khách đánh dấu đồng ý chính sách đặt cọc
        'policy'       => (string) cfg('deposit_policy_version'),
        'status'       => 'pending',
        'created'      => time(),
        'until'        => time() + max(5, (int) cfg('hold_pending_minutes', 60)) * 60,
    ];
    if (!save_hold($p['slug'], $hold)) {
        $errors[] = 'Rất tiếc, vừa có khách khác giữ món này. Vui lòng liên hệ để được tư vấn mẫu tương tự.';
    }
}

if ($errors) {
    $page_title = 'Chưa giữ được hàng';
    include ROOT . '/includes/header.php';
    ?>
    <section class="section">
      <div class="container empty-state empty-state--page">
        <?= icon('lock') ?>
        <h1>Chưa giữ được hàng</h1>
        <ul class="error-list" role="alert"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
        <div class="empty-state__actions">
          <?php if ($p): ?><a class="btn btn--primary" href="<?= e(product_url($p)) ?>">Quay lại sản phẩm</a><?php endif; ?>
          <?php if (cfg('zalo')): ?><a class="btn btn--outline" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener">Nhắn Zalo cho shop</a><?php endif; ?>
        </div>
      </div>
    </section>
    <?php
    include ROOT . '/includes/footer.php';
    exit;
}

save_csv('giu-hang', ['Thời gian', 'Mã giữ', 'Sản phẩm', 'Giá', 'Tiền cọc', 'Hạn chờ cọc', 'Họ tên', 'Điện thoại', 'Email'],
    [date('d/m/Y H:i'), $hold['code'], $p['name'], $p['price'], $hold['deposit'], date('d/m/Y H:i', $hold['until']), $name, $phone, $email]);
$manage = hold_manage_url($hold['code']);
$msg = "🔒 YÊU CẦU GIỮ HÀNG {$hold['code']} – CHỜ CỌC\n{$p['name']} – " . format_price($p['price'])
    . "\nKhách: $name – $phone" . ($email ? " – $email" : '') . " (Zalo: " . zalo_chat_url($phone) . ")\nCọc cần nhận: " . format_price($hold['deposit']) . " (nội dung: {$hold['code']})"
    . "\nGiữ tạm đến: " . date('H:i d/m', $hold['until'])
    . "\n\nKhi đã thấy tiền về, mở link để xác nhận:\n$manage";
notify_telegram($msg);
send_mail(cfg('order_email'), 'Yêu cầu giữ hàng ' . $hold['code'] . ' – chờ cọc ' . format_price($hold['deposit']),
    nl2br(e($msg)) . '<p><a href="' . e($manage) . '" style="display:inline-block;padding:10px 18px;background:#0b5a32;color:#fff;border-radius:6px;text-decoration:none">Xác nhận / hủy giữ hàng</a></p>');

if ($email) {
    send_mail($email, cfg('site_name') . ' – Yêu cầu giữ hàng ' . $hold['code'] . ' – vui lòng chuyển cọc',
        '<h2>Yêu cầu giữ hàng ' . e($hold['code']) . '</h2><p>Xin chào ' . e($name) . ',</p><p><strong>' . e($p['name']) . '</strong> đang được giữ tạm cho bạn đến <strong>'
        . date('H:i', $hold['until']) . ' ngày ' . date('d/m/Y', $hold['until']) . '</strong>. Vui lòng chuyển khoản đặt cọc <strong>' . e(format_price($hold['deposit']))
        . '</strong> với nội dung <strong>' . e($hold['code']) . '</strong> (mã QR có trong link bên dưới).</p>'
        . '<p><a href="' . e(hold_view_url($hold['code'])) . '" style="display:inline-block;padding:10px 18px;background:#0b5a32;color:#fff;border-radius:6px;text-decoration:none">Xem lượt giữ hàng &amp; mã QR</a></p>'
        . '<p style="color:#666;font-size:13px">Khi cửa hàng nhận được cọc, bạn sẽ nhận email kèm phiếu xác nhận đặt cọc.<br>Hotline: ' . e(cfg('hotline')) . '</p>',
        cfg('email'));
}

form_mark_sent('hold');
$_SESSION['last_hold'] = $hold;
redirect(hold_view_url($hold['code']));
