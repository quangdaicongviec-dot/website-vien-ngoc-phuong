/**
 * Phiếu xác nhận (phieu.php): dựng nội dung phiếu, mã QR kiểm tra phiếu thật, và các nút
 * Tải PDF / Lưu ảnh / In – chạy hoàn toàn trên trình duyệt, không cần cài gì trên hosting.
 * Dữ liệu: <script id="receipt-data"> (website thật) hoặc window.VNP_RECEIPT (bản chạy thử).
 * Thư viện: qrcode-generator (MIT) và html2canvas (MIT, chỉ tải khi bấm Tải PDF / Lưu ảnh) – trong assets/js/vendor/.
 */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var D;
  try { D = JSON.parse($('#receipt-data').textContent); } catch (e) { return; }
  var R = D.receipt || window.VNP_RECEIPT;
  var mount = $('[data-receipt-mount]');
  if (!mount || !R) return;

  var TITLES = { coc: 'Phiếu xác nhận đặt cọc', 'thanh-toan': 'Phiếu xác nhận thanh toán', 'don-hang': 'Phiếu xác nhận đơn hàng' };
  var STAMPS = { coc: 'Đã nhận cọc', 'thanh-toan': 'Đã thanh toán', 'don-hang': 'Đã xác nhận' };
  var cancelled = R.status === 'cancelled';
  var shop = R.shop || D.shop || {};
  var terms = R.terms || (D.terms || {})[R.kind] || [];
  var legal = R.footer != null ? R.footer : (D.footer || '');
  var link = D.url || R.url || location.href;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }
  function money(n) { return String(Math.round(+n || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫'; }

  // Số tiền bằng chữ: 1680000 → "Một triệu sáu trăm tám mươi nghìn đồng"
  function words(n) {
    n = Math.round(+n || 0);
    if (n <= 0) return '';
    var D = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    var U = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ'];
    function three(x, full) {
      var h = Math.floor(x / 100), t = Math.floor(x % 100 / 10), u = x % 10, s = [];
      if (full || h) s.push(D[h], 'trăm');
      if (t > 1) { s.push(D[t], 'mươi'); if (u) s.push(u === 1 ? 'mốt' : u === 5 ? 'lăm' : D[u]); }
      else if (t === 1) { s.push('mười'); if (u) s.push(u === 5 ? 'lăm' : D[u]); }
      else if (u) { if (full || h) s.push('lẻ'); s.push(D[u]); }
      return s.join(' ');
    }
    var g = [];
    while (n > 0) { g.push(n % 1000); n = Math.floor(n / 1000); }
    var out = [];
    for (var i = g.length - 1; i >= 0; i--) if (g[i]) out.push(three(g[i], i < g.length - 1) + (U[i] ? ' ' + U[i] : ''));
    var s = out.join(' ');
    return s.charAt(0).toUpperCase() + s.slice(1) + ' đồng';
  }
  function moneyOr(n, alt) { return +n > 0 ? money(n) : alt; }

  // Giờ Việt Nam, kể cả khi khách mở phiếu ở nước ngoài
  var parts = (function () {
    try {
      var f = new Intl.DateTimeFormat('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh', hourCycle: 'h23', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
      return function (ts) {
        var p = {};
        f.formatToParts(new Date(ts * 1000)).forEach(function (x) { p[x.type] = x.value; });
        return { t: p.hour + ':' + p.minute, d: p.day + '/' + p.month + '/' + p.year };
      };
    } catch (e) {
      return function (ts) {
        var d = new Date(ts * 1000), z = function (n) { return ('0' + n).slice(-2); };
        return { t: z(d.getHours()) + ':' + z(d.getMinutes()), d: z(d.getDate()) + '/' + z(d.getMonth() + 1) + '/' + d.getFullYear() };
      };
    }
  })();
  function at(ts) { var x = parts(ts); return x.t + ' ngày ' + x.d; }

  function qrSrc(text) {
    try { var q = window.qrcode(0, 'M'); q.addData(text); q.make(); return q.createDataURL(8, 0); } catch (e) { return ''; }
  }

  /* ---------------- Nội dung phiếu ---------------- */
  var items = (R.items || []).map(function (it) {
    var sub = [];
    if (it.spec) sub.push(esc(it.spec));
    if (it.cert) sub.push('Giấy kiểm định: ' + esc(it.cert) + (it.lab ? ' (' + esc(it.lab) + ')' : ''));
    if (it.unique) sub.push('Độc bản');
    return '<tr><td><span class="receipt__item">' + esc(it.name) + '</span>' + (sub.length ? '<small>' + sub.join(' · ') + '</small>' : '') + '</td>' +
      '<td class="num">' + (it.qty || 1) + '</td><td class="num">' + moneyOr(it.price * (it.qty || 1), 'Liên hệ') + '</td></tr>';
  }).join('');

  var sums = [];
  function sum(label, value, cls) { sums.push('<div' + (cls ? ' class="' + cls + '"' : '') + '><dt>' + label + '</dt><dd>' + value + '</dd></div>'); }
  if (R.kind === 'coc') {
    sum('Giá sản phẩm', moneyOr(R.total, 'Liên hệ'));
    sum('Tiền cọc đã nhận', money(R.paid), 'is-strong');
    if (R.total > 0) sum('Còn lại khi mua', money(R.remain));
  } else {
    sum('Tạm tính', moneyOr(R.subtotal, 'Liên hệ'));
    sum('Phí giao hàng', R.shipping > 0 ? money(R.shipping) : 'Miễn phí');
    (R.gifts || []).forEach(function (g) { sum(esc(g.label), g.price > 0 ? money(g.price) : 'Miễn phí'); });
    if (R.vat > 0) sum('Thuế VAT (' + (R.vat_rate || '') + '%)', money(R.vat));
    sum('Tổng cộng', moneyOr(R.total, 'Liên hệ'), 'is-total');
    if (R.kind === 'thanh-toan') {
      sum('Đã thanh toán', moneyOr(R.paid, 'Theo thỏa thuận'), 'is-strong');
      if (R.remain > 0) sum('Còn lại', money(R.remain));
    } else {
      sum('Thanh toán khi nhận hàng', moneyOr(R.remain, 'Liên hệ'), 'is-strong');
    }
  }

  // Số tiền chính của phiếu viết bằng chữ
  var main = R.kind === 'coc' ? R.paid : R.kind === 'thanh-toan' ? R.paid : R.remain;
  var inWords = words(main) ? '<p class="receipt__words">Bằng chữ: ' + words(main) + '.</p>' : '';

  var info = [['Hình thức', esc(R.method)]];
  if (R.kind !== 'don-hang' && shop.bank) info.push(['Tài khoản nhận', esc(shop.bank)]);
  if (R.paid_at) info.push([R.kind === 'coc' ? 'Nhận cọc lúc' : 'Nhận tiền lúc', at(R.paid_at)]);
  if (R.kind !== 'don-hang') info.push(['Nội dung chuyển khoản', esc(R.code)]);
  if (R.ref) info.push(['Mã giao dịch ngân hàng', esc(R.ref)]);
  if (R.kind === 'coc' && R.hold_until) info.push(['Giữ hàng đến', '<strong>' + at(R.hold_until) + '</strong>' + (R.extended_at ? ' (đã gia hạn)' : '')]);
  var policyVer = R.policy || D.policy || '';

  var shopLines = [];
  if (shop.name && shop.name.toLowerCase() !== String(shop.brand || '').toLowerCase()) shopLines.push('<span class="receipt__legal-name">' + esc(shop.name) + '</span>');
  if (shop.tax) shopLines.push('<span>Mã số thuế: ' + esc(shop.tax) + '</span>');
  if (shop.license) shopLines.push('<span>' + esc(shop.license) + '</span>');
  if (shop.address) shopLines.push('<span>' + esc(shop.address) + '</span>');
  var contact = [shop.phone ? 'Hotline: ' + esc(shop.phone) : '', esc(shop.email), esc(shop.web)].filter(Boolean).join(' · ');
  if (contact) shopLines.push('<span>' + contact + '</span>');

  var issued = parts(R.issued);
  var stampDate = parts(cancelled ? R.cancelled_at : (R.paid_at || R.issued)).d;

  mount.innerHTML =
    '<article class="receipt' + (cancelled ? ' is-cancelled' : '') + '" id="receipt">' +
      '<header class="receipt__head">' +
        '<img class="receipt__logo" src="' + esc(D.logo) + '" alt="" width="84" height="74">' +
        '<div class="receipt__shop"><strong class="receipt__brand">' + esc(shop.brand) + '</strong>' + shopLines.join('') + '</div>' +
      '</header>' +
      '<div class="receipt__titlebar">' +
        '<div><h1 class="receipt__title">' + TITLES[R.kind] + '</h1>' +
        '<p class="receipt__no">Số phiếu <strong>' + esc(R.code) + '</strong> · Lập lúc ' + issued.t + ' ngày ' + issued.d + '</p></div>' +
        '<div class="receipt__stamp" aria-hidden="true"><div><span>' + (cancelled ? 'Đã hủy' : STAMPS[R.kind]) + '</span><small>' + stampDate + '</small></div></div>' +
      '</div>' +
      '<dl class="receipt__party"><div><dt>Khách hàng</dt><dd>' + esc(R.name) + '</dd></div><div><dt>Điện thoại</dt><dd>' + esc(R.phone) + '</dd></div>' +
        (R.address ? '<div class="receipt__party-wide"><dt>Giao đến</dt><dd>' + esc(R.address) + '</dd></div>' : '') + '</dl>' +
      '<table class="receipt__items"><thead><tr><th>Sản phẩm</th><th class="num">SL</th><th class="num">Thành tiền</th></tr></thead><tbody>' + items + '</tbody></table>' +
      '<div class="receipt__money">' +
        '<dl class="receipt__info">' + info.map(function (r) { return '<div><dt>' + r[0] + '</dt><dd>' + r[1] + '</dd></div>'; }).join('') + '</dl>' +
        '<div><dl class="receipt__sum">' + sums.join('') + '</dl>' + inWords + '</div>' +
      '</div>' +
      (R.note ? '<p class="receipt__note">' + esc(R.note) + '</p>' : '') +
      (cancelled ? '<p class="receipt__note receipt__note--cancel">' + (R.kind === 'coc' ? 'Lượt giữ hàng' : 'Đơn hàng') + ' đã được hủy lúc ' + at(R.cancelled_at) + '.</p>' : '') +
      (terms.length ? '<div class="receipt__terms"><h2>Điều khoản</h2><ul>' + terms.map(function (t) { return '<li>' + esc(t) + '</li>'; }).join('') + '</ul>' +
        (R.kind === 'coc' && policyVer ? '<p class="receipt__agree">Quý khách đã đồng ý Chính sách đặt cọc – giữ hàng (phiên bản ' + esc(policyVer) + ')' + (R.agreed_at ? ' lúc ' + at(R.agreed_at) : '') + '.</p>' : '') +
        '</div>' : '') +
      '<footer class="receipt__foot">' +
        '<img class="receipt__qr" src="' + qrSrc(link) + '" alt="Mã QR kiểm tra phiếu" width="104" height="104">' +
        '<div class="receipt__verify"><strong>Kiểm tra phiếu thật</strong>' +
          '<p>Quét mã QR để mở phiếu này trên website ' + esc(shop.web) + '. Phiếu chỉ có giá trị khi nội dung trùng khớp với trang kiểm tra.</p>' +
          (legal ? '<p class="receipt__legal">' + esc(legal) + '</p>' : '') +
        '</div>' +
      '</footer>' +
    '</article>';

  /* ---------------- Tải PDF / Lưu ảnh / In ---------------- */
  var fileBase = 'Phieu-xac-nhan-' + R.code;
  var hint = $('.receipt-tools__hint');

  function fail() {
    if (hint) hint.innerHTML = '<strong>Chưa tạo được file trên trình duyệt này.</strong> Hãy bấm “In phiếu” rồi chọn “Lưu thành PDF”, hoặc chụp màn hình phiếu.';
  }

  var h2c;
  function loadHtml2canvas() {
    if (window.html2canvas) return Promise.resolve();
    return h2c || (h2c = new Promise(function (ok, no) {
      var s = document.createElement('script');
      s.src = D.html2canvas;
      s.onload = ok;
      s.onerror = function () { h2c = null; no(new Error('html2canvas')); };
      document.head.appendChild(s);
    }));
  }

  // Chụp phiếu ở khổ cố định 760px để ảnh / PDF giống nhau trên điện thoại và máy tính
  function capture() {
    return loadHtml2canvas()
      .then(function () { return document.fonts && document.fonts.ready; })
      .then(function () {
        return window.html2canvas($('#receipt'), {
          scale: 2, backgroundColor: '#ffffff', logging: false, windowWidth: 1200,
          onclone: function (doc) { var c = doc.getElementById('receipt'); if (c) c.classList.add('receipt--capture'); }
        });
      });
  }

  function bytes(str) {
    var a = new Uint8Array(str.length);
    for (var i = 0; i < str.length; i++) a[i] = str.charCodeAt(i) & 255;
    return a;
  }

  // PDF khổ A4 chứa ảnh phiếu (tự viết, không cần thư viện PDF)
  function pdfBlob(canvas) {
    var jpg = bytes(atob(canvas.toDataURL('image/jpeg', 0.92).split(',')[1]));
    var W = 595.28, H = 841.89, M = 28;
    var s = Math.min((W - 2 * M) / canvas.width, (H - 2 * M) / canvas.height);
    var w = canvas.width * s, h = canvas.height * s, x = (W - w) / 2, y = H - M - h;
    var chunks = [], len = 0, offs = [];
    function put(u8) { chunks.push(u8); len += u8.length; }
    function obj(n, body) { offs[n] = len; put(bytes(n + ' 0 obj\n' + body + '\nendobj\n')); }
    put(bytes('%PDF-1.4\n%\xE2\xE3\xCF\xD3\n'));
    obj(1, '<< /Type /Catalog /Pages 2 0 R >>');
    obj(2, '<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
    obj(3, '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' + W + ' ' + H + '] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>');
    offs[4] = len;
    put(bytes('4 0 obj\n<< /Type /XObject /Subtype /Image /Width ' + canvas.width + ' /Height ' + canvas.height +
      ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' + jpg.length + ' >>\nstream\n'));
    put(jpg);
    put(bytes('\nendstream\nendobj\n'));
    var draw = 'q ' + w.toFixed(2) + ' 0 0 ' + h.toFixed(2) + ' ' + x.toFixed(2) + ' ' + y.toFixed(2) + ' cm /Im0 Do Q';
    obj(5, '<< /Length ' + draw.length + ' >>\nstream\n' + draw + '\nendstream');
    obj(6, '<< /Title (' + fileBase + ') /Producer (' + String(shop.web || 'website').replace(/[()\\]/g, '') + ') >>');
    var xref = len, table = 'xref\n0 7\n0000000000 65535 f \n';
    for (var n = 1; n <= 6; n++) table += ('000000000' + offs[n]).slice(-10) + ' 00000 n \n';
    put(bytes(table + 'trailer\n<< /Size 7 /Root 1 0 R /Info 6 0 R >>\nstartxref\n' + xref + '\n%%EOF\n'));
    return new Blob(chunks, { type: 'application/pdf' });
  }

  function save(blob, name) {
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a);
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 60000);
  }

  function busy(btn, on) { btn.classList.toggle('is-loading', on); btn.disabled = on; }

  var pdfBtn = $('[data-receipt-pdf]'), pngBtn = $('[data-receipt-png]'), printBtn = $('[data-receipt-print]');
  if (pdfBtn) pdfBtn.addEventListener('click', function () {
    busy(pdfBtn, true);
    capture().then(function (c) { save(pdfBlob(c), fileBase + '.pdf'); }).catch(fail).then(function () { busy(pdfBtn, false); });
  });

  // Lưu ảnh: hiện ảnh trong hộp thoại (nhấn giữ để lưu – chạy được cả trong trình duyệt của Zalo/Facebook)
  var shot = $('#receipt-shot');
  if (pngBtn) pngBtn.addEventListener('click', function () {
    busy(pngBtn, true);
    capture().then(function (c) {
      return new Promise(function (ok) { c.toBlob(ok, 'image/png'); });
    }).then(function (blob) {
      if (!blob) throw new Error('png');
      var src = URL.createObjectURL(blob);
      $('[data-receipt-shot-img]', shot).src = src;
      var a = $('[data-receipt-shot-save]', shot);
      a.href = src;
      a.download = fileBase + '.png';
      var share = $('[data-receipt-shot-share]', shot);
      var file = window.File ? new File([blob], fileBase + '.png', { type: 'image/png' }) : null;
      share.hidden = !(file && navigator.canShare && navigator.canShare({ files: [file] }));
      share.onclick = function () { navigator.share({ files: [file], title: TITLES[R.kind] + ' ' + R.code }).catch(function () {}); };
      if (shot.showModal) shot.showModal(); else save(blob, fileBase + '.png');
    }).catch(fail).then(function () { busy(pngBtn, false); });
  });

  if (printBtn) printBtn.addEventListener('click', function () { window.print(); });
})();
