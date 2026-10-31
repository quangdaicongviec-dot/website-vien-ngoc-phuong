<?php
/** Đơn hàng: danh sách, chi tiết, xác nhận tiền / đơn COD, giao hàng, hoàn tất, hủy, ghi chú */
defined('ADMIN') || exit;

$code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string) ($_GET['ma'] ?? '')));

/* ================= Thao tác ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $code !== '') {
    $act = (string) ($_POST['act'] ?? '');
    $res = match ($act) {
        'da-nhan-tien' => shop_order_mark_paid($code),
        'xac-nhan-don' => shop_order_confirm_cod($code),
        'huy'          => shop_order_cancel($code),
        'dang-giao'    => shop_order_fulfill($code, 'dang-giao'),
        'hoan-tat'     => shop_order_fulfill($code, 'hoan-tat', in_bool('da-co-chu')),
        'chua-giao'    => shop_order_fulfill($code, ''),
        default        => null,
    };
    if ($act === 'ghi-chu') {
        $o = find_order($code);
        if ($o) {
            $o['admin_note'] = in_str('ghi-chu', 2000);
            save_order($o);
            $res = ['success', 'Đã lưu ghi chú nội bộ.'];
        }
    }
    if ($res) {
        adm_log('Đơn ' . $code, $act . ' – ' . $res[1]);
        adm_back('don-hang', ['ma' => $code], $res[1], $res[0]);
    }
    adm_back('don-hang', ['ma' => $code]);
}

$all = read_json_store('don-hang.json');
uasort($all, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));
$payLabels = ['cod' => 'Trả tiền khi nhận (COD)', 'chuyen-khoan' => 'Chuyển khoản'];

/* ================= Chi tiết một đơn ================= */
if ($code !== '') {
    $o = $all[$code] ?? null;
    if (!$o) {
        adm_back('don-hang', [], 'Không tìm thấy đơn ' . $code . ' (có thể đã quá thời gian lưu).', 'error');
    }
    $st      = order_state($o);
    [$label, $tone] = adm_order_status($o);
    $pay     = payment_for($code);
    $paid    = (int) ($pay['amount'] ?? 0);
    $receipt = find_receipt($code);
    $uniqueOpen = array_filter($o['items'], fn($i) => ($p = find_product($i['slug'])) && is_unique($p) && !is_sold($p));
    $fu      = (string) ($o['fulfillment'] ?? '');
    $codDone = $st === 'cod' && !empty($o['confirmed_at']);
    $phone   = (string) $o['phone'];

    adm_head('Đơn ' . $code, 'don-hang',
        '<a class="btn btn--sm btn--outline" href="' . e(order_view_url($code)) . '" target="_blank" rel="noopener">' . adm_icon('external') . 'Trang của khách</a>'
        . ($receipt ? '<a class="btn btn--sm btn--outline" href="' . e(receipt_url($code)) . '" target="_blank" rel="noopener">' . icon('receipt') . 'Phiếu xác nhận</a>' : ''),
        [['Đơn hàng', adm_url('don-hang')]]);
    ?>
    <div class="layout-side">
      <div>
        <section class="card">
          <div class="card__head"><h2>Sản phẩm</h2><?= adm_badge($label, $tone) ?></div>
          <?php if (!empty($o['lost'])): ?>
            <?= adm_notice('<strong>Không giữ được:</strong> ' . e(implode(', ', product_names($o['lost']))) . ' – món đã có khách khác đặt/mua trước khi tiền về. Hãy liên hệ khách để đổi mẫu tương tự hoặc hoàn tiền.', 'warn') ?>
          <?php endif; ?>
          <ul class="lines">
            <?php foreach ($o['items'] as $it): $p = find_product($it['slug']); ?>
              <li>
                <span class="tbl__prod">
                  <?php if ($p): ?><img class="tbl__thumb" src="<?= e(product_image($p)) ?>" alt=""><?php endif; ?>
                  <span><a class="tbl__main" href="<?= e($p ? adm_url('san-pham', ['sua' => $p['slug']]) : '#') ?>"><?= e($it['name']) ?></a>
                    <span class="tbl__sub"><?= e(format_price((int) $it['price'])) ?> × <?= (int) $it['qty'] ?><?php if ($p && is_unique($p)): ?> · <?= is_sold($p) ? 'Đã có chủ' : 'Độc bản' ?><?php endif; ?></span></span>
                </span>
                <strong class="tnum"><?= e($it['price'] > 0 ? format_price($it['price'] * $it['qty']) : 'Liên hệ') ?></strong>
              </li>
            <?php endforeach; ?>
            <li><span>Tạm tính</span><span class="tnum"><?= e(format_price((int) $o['subtotal'])) ?></span></li>
            <li><span>Phí giao hàng</span><span class="tnum"><?= $o['shipping'] > 0 ? e(format_price((int) $o['shipping'])) : 'Miễn phí' ?></span></li>
            <?php foreach (($o['gifts'] ?? []) as $g): ?><li><span><?= e($g['label']) ?></span><span class="tnum"><?= $g['price'] > 0 ? e(format_price((int) $g['price'])) : 'Miễn phí' ?></span></li><?php endforeach; ?>
            <?php if (!empty($o['vat'])): ?><li><span>Thuế VAT (<?= (int) ($o['vat_rate'] ?? 0) ?>%)</span><span class="tnum"><?= e(format_price((int) $o['vat'])) ?></span></li><?php endif; ?>
            <li class="total"><span>Tổng cộng</span><span class="tnum"><?= e($o['total'] > 0 ? format_price((int) $o['total']) : 'Liên hệ') ?></span></li>
          </ul>
          <?php if (!empty($o['card'])): ?><p class="muted" style="margin-top:10px">Lời nhắn trên thiệp: “<?= e($o['card']) ?>”</p><?php endif; ?>
        </section>

        <section class="card">
          <h2>Khách hàng</h2>
          <dl class="dl" style="margin-top:12px">
            <dt>Họ tên</dt><dd><?= e($o['name']) ?></dd>
            <dt>Điện thoại</dt><dd><a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a> · <a href="<?= e(zalo_chat_url($phone)) ?>" target="_blank" rel="noopener">Nhắn Zalo</a></dd>
            <?php if (!empty($o['email'])): ?><dt>Email</dt><dd><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a></dd><?php endif; ?>
            <dt>Địa chỉ</dt><dd><?= e($o['address']) ?></dd>
            <?php if (!empty($o['note'])): ?><dt>Ghi chú của khách</dt><dd><?= nl2br(e($o['note'])) ?></dd><?php endif; ?>
            <dt>Thanh toán</dt><dd><?= e($payLabels[$o['pay']] ?? $o['pay']) ?><?php if ($paid): ?> · đã nhận <strong><?= e(format_price($paid)) ?></strong><?= !empty($pay['time']) ? ' lúc ' . e($pay['time']) : '' ?><?= ($ref = bank_reference((string) ($pay['ref'] ?? ''))) ? ' · mã GD ' . e($ref) : (($pay['ref'] ?? '') === 'xac-nhan-thu-cong' ? ' · xác nhận tay' : '') ?><?php endif; ?></dd>
          </dl>
          <div class="btn-row" style="margin-top:14px">
            <button type="button" class="btn btn--sm btn--outline" data-copy="<?= e(order_view_url($code)) ?>"><?= icon('copy') ?>Sao chép link đơn gửi khách</button>
            <?php if ($receipt): ?><button type="button" class="btn btn--sm btn--outline" data-copy="<?= e(receipt_url($code)) ?>"><?= icon('receipt') ?>Sao chép link phiếu</button><?php endif; ?>
          </div>
        </section>

        <section class="card">
          <h2>Ghi chú nội bộ</h2>
          <p class="card__sub">Chỉ cửa hàng thấy (VD: đã gọi lúc mấy giờ, mã vận đơn, ni tay khách…).</p>
          <form method="post" action="<?= e(adm_url('don-hang', ['ma' => $code])) ?>">
            <?= adm_csrf_field() ?><input type="hidden" name="act" value="ghi-chu">
            <textarea class="ai" name="ghi-chu" rows="3" data-autosize><?= e($o['admin_note'] ?? '') ?></textarea>
            <button type="submit" class="btn btn--sm btn--outline" style="margin-top:8px"><?= adm_icon('save') ?>Lưu ghi chú</button>
          </form>
        </section>
      </div>

      <aside>
        <section class="card">
          <h2>Việc tiếp theo</h2>
          <div class="action-box" style="margin-top:12px">
            <?php if (in_array($st, ['pending', 'partial', 'expired'], true)): ?>
              <p class="muted"><?= $st === 'partial' ? 'Khách mới chuyển ' . e(format_price($paid)) . ' / ' . e(format_price((int) $o['total'])) . '.' : 'Đơn chờ khách chuyển khoản (nội dung: ' . e($code) . ').' ?><?= auto_payments_enabled() ? ' Website tự xác nhận khi tiền về.' : '' ?></p>
              <form method="post" action="<?= e(adm_url('don-hang', ['ma' => $code])) ?>" data-confirm="Xác nhận bạn đã kiểm tra tài khoản và nhận đủ tiền cho đơn <?= e($code) ?>?">
                <?= adm_csrf_field() ?><input type="hidden" name="act" value="da-nhan-tien">
                <button type="submit" class="btn btn--primary"><?= icon('check') ?>Xác nhận đã nhận đủ tiền</button>
              </form>
            <?php elseif ($st === 'cod' && !$codDone): ?>
              <p class="muted">Gọi khách xác nhận đơn rồi bấm nút dưới đây – khách nhận được phiếu xác nhận đơn hàng.</p>
              <form method="post" action="<?= e(adm_url('don-hang', ['ma' => $code])) ?>">
                <?= adm_csrf_field() ?><input type="hidden" name="act" value="xac-nhan-don">
                <button type="submit" class="btn btn--primary"><?= icon('check') ?>Xác nhận đơn (đã gọi khách)</button>
              </form>
            <?php endif; ?>

            <?php if (($st === 'paid' || $codDone) && $fu !== 'hoan-tat'): ?>
              <?php if ($fu === ''): ?>
                <form method="post" action="<?= e(adm_url('don-hang', ['ma' => $code])) ?>">
                  <?= adm_csrf_field() ?><input type="hidden" name="act" value="dang-giao">
                  <button type="submit" class="btn btn--outline"><?= icon('truck') ?>Đánh dấu đang giao hàng</button>
                </form>
              <?php endif; ?>
              <form method="post" action="<?= e(adm_url('don-hang', ['ma' => $code])) ?>" data-confirm="Xác nhận khách đã nhận hàng – hoàn tất đơn <?= e($code) ?>?">
                <?= adm_csrf_field() ?><input type="hidden" name="act" value="hoan-tat">
                <?php if ($uniqueOpen): ?><label class="check" style="margin-bottom:8px"><input type="checkbox" name="da-co-chu" value="1" checked><span>Chuyển <?= count($uniqueOpen) ?> món độc bản sang “Đã có chủ”</span></label><?php endif; ?>
                <button type="submit" class="btn btn--primary"><?= icon('check') ?>Hoàn tất – khách đã nhận hàng</button>
              </form>
            <?php elseif ($fu === 'hoan-tat'): ?>
              <?= adm_notice('Đơn đã hoàn tất lúc ' . e(adm_time((int) ($o['completed_at'] ?? 0))) . '.', 'ok') ?>
              <form method="post" action="<?= e(adm_url('don-hang', ['ma' => $code])) ?>">
                <?= adm_csrf_field() ?><input type="hidden" name="act" value="chua-giao">
                <button type="submit" class="btn btn--sm btn--ghost">Mở lại (chưa giao xong)</button>
              </form>
            <?php endif; ?>

            <?php if ($st === 'cancelled'): ?>
              <?= adm_notice('Đơn đã hủy lúc ' . e(adm_time((int) ($o['cancelled_at'] ?? 0))) . '.' . ($paid ? ' <strong>Khách đã chuyển ' . e(format_price($paid)) . ' – nhớ hoàn tiền.</strong>' : ''), $paid ? 'warn' : 'info') ?>
            <?php elseif ($fu !== 'hoan-tat'): ?>
              <form method="post" action="<?= e(adm_url('don-hang', ['ma' => $code])) ?>" data-confirm="Hủy đơn <?= e($code) ?>?<?= !empty($o['reserved']) ? ' Các món độc bản sẽ được mở bán lại.' : '' ?>">
                <?= adm_csrf_field() ?><input type="hidden" name="act" value="huy">
                <button type="submit" class="btn btn--danger btn--sm"><?= icon('close') ?>Hủy đơn</button>
              </form>
            <?php endif; ?>
          </div>
        </section>
        <section class="card">
          <h2>Lịch sử</h2>
          <ul class="timeline" style="margin-top:12px">
            <li><strong>Đặt hàng</strong> · <?= e(adm_time((int) $o['created'])) ?></li>
            <?php if (!empty($o['reserved']) && !empty($o['reserved_until'])): ?><li>Giữ món độc bản đến <?= e(adm_time((int) $o['reserved_until'])) ?></li><?php endif; ?>
            <?php if ($paid): ?><li><strong>Nhận tiền</strong> <?= e(format_price($paid)) ?><?= !empty($pay['time']) ? ' · ' . e($pay['time']) : '' ?></li><?php endif; ?>
            <?php if (!empty($o['confirmed_at'])): ?><li><strong>Xác nhận đơn</strong> · <?= e(adm_time((int) $o['confirmed_at'])) ?></li><?php endif; ?>
            <?php if ($receipt): ?><li>Lập phiếu xác nhận · <?= e(adm_time((int) ($receipt['issued'] ?? 0))) ?></li><?php endif; ?>
            <?php if (!empty($o['shipping_at'])): ?><li><strong>Đang giao</strong> · <?= e(adm_time((int) $o['shipping_at'])) ?></li><?php endif; ?>
            <?php if (!empty($o['completed_at']) && $fu === 'hoan-tat'): ?><li><strong>Hoàn tất</strong> · <?= e(adm_time((int) $o['completed_at'])) ?></li><?php endif; ?>
            <?php if (!empty($o['cancelled_at'])): ?><li><strong>Hủy</strong> · <?= e(adm_time((int) $o['cancelled_at'])) ?></li><?php endif; ?>
          </ul>
        </section>
      </aside>
    </div>
    <?php
    adm_foot();
    return;
}

/* ================= Danh sách ================= */
$filters = [
    'can-xu-ly' => ['Cần xử lý', fn($o) => adm_order_todo($o) !== null],
    ''          => ['Tất cả', fn($o) => true],
    'cho-tien'  => ['Chờ chuyển khoản', fn($o) => in_array(order_state($o), ['pending', 'partial'], true)],
    'cod-moi'   => ['COD chờ xác nhận', fn($o) => order_state($o) === 'cod' && empty($o['confirmed_at'])],
    'cho-giao'  => ['Chờ giao', fn($o) => (order_state($o) === 'paid' || (order_state($o) === 'cod' && !empty($o['confirmed_at']))) && ($o['fulfillment'] ?? '') === ''],
    'dang-giao' => ['Đang giao', fn($o) => ($o['fulfillment'] ?? '') === 'dang-giao' && order_state($o) !== 'cancelled'],
    'hoan-tat'  => ['Hoàn tất', fn($o) => ($o['fulfillment'] ?? '') === 'hoan-tat' && order_state($o) !== 'cancelled'],
    'huy'       => ['Đã hủy / hết hạn', fn($o) => in_array(order_state($o), ['cancelled', 'expired'], true)],
];
$loc   = (string) ($_GET['loc'] ?? 'can-xu-ly');
$loc   = isset($filters[$loc]) ? $loc : '';
$month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['thang'] ?? '')) ? (string) $_GET['thang'] : '';
$months = [];
foreach ($all as $o) {
    $months[date('Y-m', (int) $o['created'])] = true;
}
krsort($months);
$inMonth = $month ? array_filter($all, fn($o) => date('Y-m', (int) $o['created']) === $month) : $all;
$list    = array_filter($inMonth, $filters[$loc][1]);

// Tải danh sách đang xem dưới dạng Excel (CSV)
if (($_GET['xuat'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="don-hang-' . ($month ?: 'tat-ca') . '-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Mã đơn', 'Thời gian', 'Họ tên', 'Điện thoại', 'Email', 'Địa chỉ', 'Sản phẩm', 'Tổng', 'Thanh toán', 'Đã nhận', 'Trạng thái', 'Ghi chú khách', 'Ghi chú nội bộ'], ',', '"', '\\');
    foreach ($list as $o) {
        $row = [$o['code'], date('d/m/Y H:i', (int) $o['created']), $o['name'], $o['phone'], $o['email'] ?? '', $o['address'] ?? '',
                implode(' | ', array_map(fn($i) => $i['name'] . ' x' . $i['qty'], $o['items'])), (int) $o['total'], $payLabels[$o['pay']] ?? $o['pay'],
                order_paid_amount($o['code']), adm_order_status($o)[0], $o['note'] ?? '', $o['admin_note'] ?? ''];
        fputcsv($out, array_map(fn($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $row), ',', '"', '\\');
    }
    fclose($out);
    exit;
}

$chips = [];
foreach ($filters as $k => [$lab, $fn]) {
    $chips[$k] = [$lab, adm_url('don-hang', ['loc' => $k === '' ? 'tat-ca' : $k, 'thang' => $month]), count(array_filter($inMonth, $fn))];
}

adm_head('Đơn hàng', 'don-hang', '<a class="btn btn--sm btn--outline" href="' . e(adm_url('don-hang', ['loc' => $loc ?: 'tat-ca', 'thang' => $month, 'xuat' => 'csv'])) . '">' . icon('download') . 'Tải Excel</a>');
?>
<div data-filter-scope>
  <div class="adm-filter">
    <label class="adm-filter__search"><span class="sr-only">Tìm đơn</span><?= icon('search') ?><input type="search" placeholder="Tìm mã đơn, tên, số điện thoại…" data-filter-input autocomplete="off"></label>
    <form method="get" action="<?= e(base_path() . '/quan-tri/') ?>" data-autosubmit>
      <input type="hidden" name="p" value="don-hang"><input type="hidden" name="loc" value="<?= e($loc ?: 'tat-ca') ?>">
      <label class="sr-only" for="f-thang">Tháng</label>
      <select class="ai" id="f-thang" name="thang" style="min-height:42px;border-radius:999px">
        <option value="">Mọi tháng</option>
        <?php foreach (array_keys($months) as $m): ?><option value="<?= e($m) ?>"<?= $m === $month ? ' selected' : '' ?>>Tháng <?= e((int) substr($m, 5) . '/' . substr($m, 0, 4)) ?></option><?php endforeach; ?>
      </select>
    </form>
  </div>
  <div class="adm-chips" style="margin:-4px 0 16px">
    <?php foreach ($chips as $k => $c): ?><a class="adm-chip" href="<?= e($c[1]) ?>"<?= (string) $k === $loc ? ' aria-current="true"' : '' ?>><?= e($c[0]) ?> <b><?= (int) $c[2] ?></b></a><?php endforeach; ?>
  </div>

  <section class="card card--flush">
    <?php if (!$list): ?>
      <div class="empty"><?= icon('bag') ?><p><?= $loc === 'can-xu-ly' ? 'Không có đơn nào cần xử lý.' : 'Chưa có đơn hàng nào trong mục này.' ?></p></div>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="tbl tbl--cards">
          <thead><tr><th>Đơn hàng</th><th>Khách</th><th>Sản phẩm</th><th class="num">Tổng</th><th>Trạng thái</th><th class="act"></th></tr></thead>
          <tbody>
          <?php foreach ($list as $o): [$lab, $tone] = adm_order_status($o); $todo = adm_order_todo($o); ?>
            <tr data-filter-item data-filter-text="<?= e($o['code'] . ' ' . $o['name'] . ' ' . $o['phone'] . ' ' . phone_digits($o['phone'])) ?>"<?= in_array(order_state($o), ['cancelled', 'expired'], true) ? ' class="is-dim"' : '' ?>>
              <td class="tbl__first"><a class="tbl__main" href="<?= e(adm_url('don-hang', ['ma' => $o['code']])) ?>"><?= e($o['code']) ?></a><span class="tbl__sub"><?= e(adm_ago((int) $o['created'])) ?> · <?= $o['pay'] === 'chuyen-khoan' ? 'Chuyển khoản' : 'COD' ?></span></td>
              <td data-label="Khách"><span><?= e($o['name']) ?><span class="tbl__sub"><?= e($o['phone']) ?></span></span></td>
              <td data-label="Sản phẩm"><span><?= e($o['items'][0]['name'] ?? '') ?><?= count($o['items']) > 1 ? ' <span class="muted">+' . (count($o['items']) - 1) . ' món</span>' : '' ?></span></td>
              <td class="num" data-label="Tổng"><strong><?= e($o['total'] > 0 ? format_price((int) $o['total']) : 'Liên hệ') ?></strong></td>
              <td data-label="Trạng thái"><span><?= adm_badge($lab, $tone) ?><?php if ($todo): ?><span class="tbl__sub"><?= e($todo) ?></span><?php endif; ?></span></td>
              <td class="act"><a class="btn btn--sm btn--outline" href="<?= e(adm_url('don-hang', ['ma' => $o['code']])) ?>">Xem</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="empty" data-filter-empty hidden>Không có đơn khớp từ khóa.</p>
    <?php endif; ?>
  </section>
</div>
<p class="muted">Đơn hàng được lưu <?= (int) cfg('order_keep_days', 730) ?> ngày (đổi trong Cài đặt → Đơn hàng). Phiếu xác nhận được lưu lâu dài.</p>
<?php
adm_foot();
