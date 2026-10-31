<?php
/** Tổng quan: số liệu, việc cần làm, cài đặt còn thiếu */
defined('ADMIN') || exit;

$orders   = read_json_store('don-hang.json');
$monthTs  = strtotime(date('Y-m-01'));
$todayTs  = strtotime('today');
$revMonth = 0;
$cntMonth = 0;
$todo     = [];
foreach ($orders as $o) {
    if (($o['created'] ?? 0) >= $monthTs && order_state($o) !== 'cancelled') {
        $cntMonth++;
        if (adm_order_counts_revenue($o)) {
            $revMonth += (int) $o['total'];
        }
    }
    if (($t = adm_order_todo($o)) && ($o['created'] ?? 0) > time() - 60 * 86400) {
        $todo[] = ['ts' => (int) $o['created'], 'icon' => 'bag', 'title' => $o['code'] . ' – ' . $o['name'], 'text' => $t . ' · ' . ($o['total'] > 0 ? format_price($o['total']) : 'Liên hệ'), 'url' => adm_url('don-hang', ['ma' => $o['code']])];
    }
}
foreach (read_holds_raw() as $h) {
    if (($h['type'] ?? 'hold') !== 'hold' || hold_status($h) === 'expired') {
        continue;
    }
    $pending = ($h['status'] ?? '') === 'pending';
    $todo[] = ['ts' => (int) ($h['created'] ?? 0), 'icon' => 'lock', 'title' => $h['code'] . ' – ' . $h['name'],
               'text' => ($pending ? 'Chờ chuyển cọc ' . format_price((int) $h['deposit']) . ' đến ' . hold_time((int) $h['until']) : 'Đang giữ ' . $h['name_product'] . ' đến ' . hold_time((int) $h['until'])),
               'url' => adm_url('giu-hang', ['ma' => $h['code']])];
}
$appts = array_filter(csv_records('dat-lich'), fn($r) => ($r['_status'] ?? '') === '' );
foreach (array_slice($appts, 0, 8) as $r) {
    $todo[] = ['ts' => (int) $r['_ts'], 'icon' => 'calendar', 'title' => 'Lịch hẹn – ' . ($r['Họ tên'] ?? ''), 'text' => trim(($r['Ngày hẹn'] ?? '') . ' ' . ($r['Giờ'] ?? '')) . ' · ' . ($r['Điện thoại'] ?? ''), 'url' => adm_url('lich-hen')];
}
foreach (array_slice(array_filter(csv_records('lien-he'), fn($r) => ($r['_status'] ?? '') === ''), 0, 5) as $r) {
    $todo[] = ['ts' => (int) $r['_ts'], 'icon' => 'inbox', 'title' => 'Tin nhắn – ' . ($r['Họ tên'] ?? ''), 'text' => str_limit((string) ($r['Nội dung'] ?? ''), 90), 'url' => adm_url('lien-he')];
}
usort($todo, fn($a, $b) => $b['ts'] <=> $a['ts']);

// ---- Cài đặt / nội dung còn thiếu ----
$products = content_load('san-pham');
$checks   = [];
$bank     = cfg('bank', []);
$add = function (string $tone, string $title, string $text, string $url, string $btn = 'Sửa ngay') use (&$checks) {
    $checks[] = compact('tone', 'title', 'text', 'url', 'btn');
};
if (preg_match('/0900 ?000 ?000|tenmiencuaban|Số nhà, Tên đường/u', cfg('hotline') . cfg('email') . cfg('address'))) {
    $add('warn', 'Thông tin liên hệ còn là dữ liệu mẫu', 'Hotline, email hoặc địa chỉ đang là chữ mẫu – khách sẽ thấy trên mọi trang và trên phiếu xác nhận.', adm_url('cai-dat', ['nhom' => 'cua-hang']));
}
if (empty($bank['account'])) {
    $add('info', 'Chưa có tài khoản nhận chuyển khoản', 'Khách chỉ đặt được hàng trả tiền khi nhận (COD). Thêm tài khoản để nhận chuyển khoản bằng mã QR.', adm_url('cai-dat', ['nhom' => 'thanh-toan']));
} elseif (empty($bank['holder'])) {
    $add('warn', 'Kiểm tra tài khoản ngân hàng nhận tiền', 'Tài khoản ' . ($bank['bank'] ?? '') . ' ' . $bank['account'] . ' chưa có tên chủ tài khoản – hãy chọn đúng ngân hàng, số tài khoản chính thức và điền tên chủ tài khoản.', adm_url('cai-dat', ['nhom' => 'thanh-toan']));
}
if (!cfg('telegram_bot_token') || !cfg('telegram_chat_id')) {
    $add('warn', 'Chưa bật báo đơn qua Telegram', 'Khi có đơn hàng, giữ hàng, lịch hẹn… bạn sẽ không nhận được tin báo trên điện thoại.', adm_url('cai-dat', ['nhom' => 'thong-bao']));
}
if (!auto_payments_enabled() && !empty($bank['account'])) {
    $add('info', 'Xác nhận tiền vào đang làm bằng tay', 'Kết nối Casso hoặc SePay để website tự xác nhận khi khách chuyển khoản đúng mã.', adm_url('cai-dat', ['nhom' => 'thanh-toan']), 'Xem cách bật');
}
if (!cfg('tax_code')) {
    $add('info', 'Chưa có mã số thuế / số định danh', 'In trên chân trang và phiếu xác nhận. Hộ kinh doanh dùng số định danh cá nhân (12 số) của chủ hộ.', adm_url('cai-dat', ['nhom' => 'cua-hang']));
}
$sample = array_filter($products, fn($p) => !empty($p['gia_mo_phong']));
if ($sample) {
    $add('warn', count($sample) . ' sản phẩm đang dùng giá mô phỏng', 'Giá đặt tạm để chạy thử – hãy thay bằng giá bán thật.', adm_url('san-pham', ['loc' => 'gia-mo-phong']));
}
$sizeTodo = array_filter($products, fn($p) => strpos((string) ($p['size'] ?? ''), '(cập nhật') !== false);
if ($sizeTodo) {
    $add('info', count($sizeTodo) . ' sản phẩm chưa có kích thước thật', 'Ô "Kích thước / ni" đang ghi tạm “(cập nhật…)”.', adm_url('san-pham', ['loc' => 'thieu-kich-thuoc']));
}
$sampleCerts = array_filter(all_certificates(), fn($c) => !empty($c['mau']));
if ($sampleCerts) {
    $add('warn', count($sampleCerts) . ' giấy kiểm định mẫu', 'Đang là dữ liệu minh họa – thay bằng giấy kiểm định thật trước khi bán.', adm_url('kiem-dinh'));
}
$bannersNoMobile = array_filter(all_banners(), fn($b) => ($b['type'] ?? '') === 'image' && empty($b['image_mobile']));
if ($bannersNoMobile) {
    $add('info', count($bannersNoMobile) . ' banner chưa có ảnh dọc cho điện thoại', 'Banner ảnh thiếu ảnh dọc sẽ tạm ẩn trên điện thoại.', adm_url('banner'));
}
if (!smtp_enabled() && !mail_function_usable()) {
    $add('warn', 'Website chưa gửi được email', 'Máy chủ không tự gửi email – khách sẽ không nhận được email xác nhận đơn. Cài SMTP (Gmail hoặc email tên miền) trong mục Thông báo.', adm_url('cai-dat', ['nhom' => 'thong-bao']));
}
if (secret_unreadable()) {
    $add('warn', 'Cần nhập lại mật khẩu SMTP / khóa R2', 'Thông tin bí mật lưu trước đây không đọc được (website vừa chuyển máy chủ hoặc mất khóa mã hóa).', adm_url('cai-dat', ['nhom' => 'luu-tru']));
}
$r2st = r2_status();
if (!r2_ready()) {
    $add('info', 'Chưa kết nối Cloudflare R2', 'Kết nối để ảnh / video tải lên được lưu thêm trên R2 và dữ liệu được sao lưu lên R2 mỗi đêm (phòng khi máy chủ hỏng).', adm_url('cai-dat', ['nhom' => 'luu-tru']), 'Kết nối');
}
if (cfg('r2_backup') && r2_ready() && !empty($r2st['backup']) && empty($r2st['backup']['ok'])) {
    $add('warn', 'Sao lưu lên Cloudflare R2 bị lỗi', (string) $r2st['backup']['message'], adm_url('cai-dat', ['nhom' => 'luu-tru']), 'Kiểm tra');
} elseif (r2_ready() && !empty($r2st['upload_error']) && time() - (int) $r2st['upload_error']['time'] < 3 * 86400) {
    $add('info', 'Có ảnh chưa đưa lên Cloudflare R2', (string) $r2st['upload_error']['error'], adm_url('cai-dat', ['nhom' => 'luu-tru']), 'Kiểm tra');
}
if (!cfg('site_url')) {
    $add('info', 'Chưa khai báo tên miền chính thức', 'Điền tên miền (VD: https://vienngocphuong.vn) sau khi đưa website lên hosting để link chia sẻ, mã QR trên phiếu luôn đúng.', adm_url('cai-dat', ['nhom' => 'ky-thuat']));
}

$live   = array_filter($products, fn($p) => empty($p['hidden']) && !is_sold($p));
$sold   = array_filter($products, fn($p) => is_sold($p));
$counts = adm_counts();
$hour   = (int) date('G');
$hello  = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
$weekday = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'][(int) date('w')];

adm_head('Tổng quan', 'tong-quan', adm_is_owner()
    ? '<a class="btn btn--primary" href="' . e(adm_url('san-pham', ['moi' => 1])) . '">' . icon('plus') . 'Thêm sản phẩm</a>'
    : '');
?>
<p class="muted" style="margin-top:-10px"><?= e($hello . ', ' . ($me['name'] ?? '')) ?> · <?= e($weekday . ', ' . date('d/m/Y')) ?></p>

<div class="stats">
  <a class="stat<?= $counts['don-hang'] ? ' stat--alert' : '' ?>" href="<?= e(adm_url('don-hang', ['loc' => 'can-xu-ly'])) ?>"><span class="stat__label"><?= icon('bag') ?>Đơn cần xử lý</span><span class="stat__value"><?= (int) $counts['don-hang'] ?></span><span class="stat__foot">Xác nhận, đóng gói, giao hàng</span></a>
  <a class="stat<?= $counts['giu-hang'] ? ' stat--alert' : '' ?>" href="<?= e(adm_url('giu-hang')) ?>"><span class="stat__label"><?= icon('lock') ?>Đang giữ hàng</span><span class="stat__value"><?= (int) $counts['giu-hang'] ?></span><span class="stat__foot">Chờ cọc hoặc đã nhận cọc</span></a>
  <a class="stat" href="<?= e(adm_url('lich-hen')) ?>"><span class="stat__label"><?= icon('calendar') ?>Lịch hẹn mới</span><span class="stat__value"><?= (int) $counts['lich-hen'] ?></span><span class="stat__foot">Chưa liên hệ xác nhận</span></a>
  <a class="stat" href="<?= e(adm_url('lien-he')) ?>"><span class="stat__label"><?= adm_icon('inbox') ?>Tin nhắn mới</span><span class="stat__value"><?= (int) $counts['lien-he'] ?></span><span class="stat__foot">Từ trang Liên hệ</span></a>
  <?php if (adm_is_owner()): ?>
  <a class="stat" href="<?= e(adm_url('don-hang', ['thang' => date('Y-m')])) ?>"><span class="stat__label"><?= adm_icon('chart') ?>Doanh thu tháng <?= date('n') ?></span><span class="stat__value stat__value--sm"><?= e($revMonth ? format_price($revMonth) : '0₫') ?></span><span class="stat__foot"><?= (int) $cntMonth ?> đơn · đã thanh toán / đã xác nhận</span></a>
  <a class="stat" href="<?= e(adm_url('san-pham')) ?>"><span class="stat__label"><?= icon('gem') ?>Sản phẩm đang bán</span><span class="stat__value"><?= count($live) ?></span><span class="stat__foot"><?= count($sold) ?> món đã có chủ</span></a>
  <?php endif; ?>
</div>

<div class="layout-side">
  <section class="card">
    <div class="card__head"><h2>Việc cần làm</h2><?php if ($todo): ?><span class="badge badge--warn"><?= count($todo) ?> việc</span><?php endif; ?></div>
    <?php if (!$todo): ?>
      <div class="empty"><?= icon('check') ?><p>Không có việc nào đang chờ. Đơn hàng, giữ hàng, lịch hẹn mới sẽ hiện ở đây.</p></div>
    <?php else: ?>
      <ul class="todo">
        <?php foreach (array_slice($todo, 0, 12) as $t): ?>
          <li><span class="todo__icon todo__icon--info"><?= adm_icon($t['icon']) ?></span><div class="todo__body"><strong><?= e($t['title']) ?></strong><p><?= e($t['text']) ?> · <?= e(adm_ago($t['ts'])) ?></p></div><a class="btn btn--sm btn--outline" href="<?= e($t['url']) ?>">Xem</a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <aside>
    <?php if (adm_is_owner()): ?>
    <section class="card">
      <div class="card__head"><h2>Cần hoàn thiện</h2><?php if ($checks): ?><span class="badge badge--gold"><?= count($checks) ?></span><?php endif; ?></div>
      <?php if (!$checks): ?>
        <p class="muted">Tuyệt vời – các thông tin quan trọng đã đầy đủ.</p>
      <?php else: ?>
        <ul class="todo">
          <?php foreach ($checks as $c): ?>
            <li><span class="todo__icon<?= $c['tone'] === 'info' ? ' todo__icon--info' : '' ?>"><?= adm_icon($c['tone'] === 'info' ? 'info' : 'alert') ?></span><div class="todo__body"><strong><?= e($c['title']) ?></strong><p><?= e($c['text']) ?></p><a href="<?= e($c['url']) ?>"><?= e($c['btn']) ?> →</a></div></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
    <?php endif; ?>
    <section class="card">
      <h2>Lối tắt</h2>
      <div class="btn-row" style="margin-top:12px">
        <a class="btn btn--sm btn--outline" href="<?= e(adm_url('don-hang')) ?>"><?= icon('bag') ?>Đơn hàng</a>
        <?php if (adm_is_owner()): ?>
          <a class="btn btn--sm btn--outline" href="<?= e(adm_url('san-pham')) ?>"><?= icon('gem') ?>Sản phẩm</a>
          <a class="btn btn--sm btn--outline" href="<?= e(adm_url('banner')) ?>"><?= icon('image') ?>Banner</a>
          <a class="btn btn--sm btn--outline" href="<?= e(adm_url('bai-viet', ['moi' => 1])) ?>"><?= icon('plus') ?>Viết bài</a>
          <a class="btn btn--sm btn--outline" href="<?= e(adm_url('sao-luu')) ?>"><?= adm_icon('database') ?>Tải bản sao lưu</a>
        <?php endif; ?>
        <a class="btn btn--sm btn--ghost" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= adm_icon('external') ?>Mở website</a>
      </div>
    </section>
  </aside>
</div>
<?php
adm_foot();
