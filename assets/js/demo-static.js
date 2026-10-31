/* ==========================================================================
   Viên Ngọc Phương — BẢN CHẠY THỬ TĨNH (Netlify)
   Chỉ được nạp khi xuất bản chạy thử (VNP_STATIC=1). Thay vai trò máy chủ PHP cho:
   lọc/tìm/sắp xếp sản phẩm, gợi ý quà, tra cứu kiểm định, xem nhanh, yêu thích,
   đặt hàng, giữ hàng, đặt lịch, liên hệ (không gửi dữ liệu đi đâu cả).
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var body = document.body;
  var base = body.dataset.base || '';
  var P = new URLSearchParams(location.search);
  var CFG = (function () { try { return JSON.parse($('#demo-cfg').textContent); } catch (e) { return {}; } })();

  var ICONS = {
    check: '<path d="M20 6 9 17l-5-5"/>',
    cert: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m9 15 2 2 4-4"/>',
    zoom: '<circle cx="11" cy="11" r="7.5"/><path d="m20.5 20.5-4.2-4.2"/><path d="M11 8v6"/><path d="M8 11h6"/>',
    clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    lock: '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    copy: '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
    chat: '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
    gift: '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
    close: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    down: '<path d="m6 9 6 6 6-6"/>',
    phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
    receipt: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>'
  };
  function icon(n) {
    return '<svg class="icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + (ICONS[n] || '') + '</svg>';
  }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }
  function price(n) {
    n = Math.round(Number(n) || 0);
    return n <= 0 ? 'Liên hệ' : String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫';
  }
  function fold(s) {
    return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd');
  }
  function store(k, v) {
    try { if (v === undefined) return JSON.parse(sessionStorage.getItem(k) || 'null'); sessionStorage.setItem(k, JSON.stringify(v)); }
    catch (e) { try { if (v === undefined) return JSON.parse(localStorage.getItem(k) || 'null'); localStorage.setItem(k, JSON.stringify(v)); } catch (e2) { return null; } }
  }
  function code(prefix) {
    var d = new Date(), p2 = function (n) { return ('0' + n).slice(-2); };
    return prefix + String(d.getFullYear()).slice(-2) + p2(d.getMonth() + 1) + p2(d.getDate()) + '-' + Math.random().toString(16).slice(2, 6).toUpperCase();
  }
  function dmy(ts) { var d = new Date(ts), p2 = function (n) { return ('0' + n).slice(-2); }; return p2(d.getHours()) + ':' + p2(d.getMinutes()) + ' ngày ' + p2(d.getDate()) + '/' + p2(d.getMonth() + 1) + '/' + d.getFullYear(); }
  // Trạng thái đơn / lượt giữ mô phỏng (thay cho máy chủ) – dùng cho thông báo "Đơn hàng của tôi"
  function demoStatus(kind, c) {
    if (kind === 'dat-hang') {
      var o = store('vnp_demo_order');
      if (!o || o.code !== c) return { status: 'none', receipt: false };
      var transfer = o.pay === 'chuyen-khoan' && (CFG.bank || {}).account;
      return transfer ? { status: o.paid ? 'paid' : 'pending', receipt: !!o.paid } : { status: 'cod', receipt: !!o.confirmed };
    }
    var h = store('vnp_demo_hold');
    return h && h.code === c ? { status: h.status, receipt: h.status === 'confirmed' } : { status: 'none', receipt: false };
  }
  // Nút "Xem phiếu xác nhận" dưới tiêu đề trang đơn / giữ hàng (luôn có; chưa xác nhận thì mở trang trạng thái)
  function receiptLink(c, ready, waitText, suffix) {
    return '<p class="order-done__receipt"><a class="btn ' + (ready ? 'btn--primary' : 'btn--outline') + '" href="' + esc(receiptUrl(c)) + '">' + icon('receipt') +
      (ready ? 'Xem &amp; tải phiếu xác nhận' : 'Xem phiếu xác nhận') + (suffix || '') + '</a>' +
      (ready ? '' : '<small>' + esc(waitText) + ' – mở link phiếu để theo dõi trạng thái.</small>') + '</p>';
  }
  function qrMobileHint() {
    return '<p class="bank-box__mobile">' + icon('phone') + '<span><strong>Trên điện thoại:</strong> nhấn giữ mã QR → <em>Lưu ảnh</em> → mở app ngân hàng, quét ảnh vừa lưu.</span></p>';
  }
  // Thông tin chuyển khoản thủ công: thu gọn (quét mã đã có đủ số tiền và nội dung)
  function manualTransfer(amount, note) {
    return '<details class="bank-box__manual"><summary><span>Chuyển khoản thủ công</span>' + icon('down') + '</summary><dl>' + bankRows(amount, note) + '</dl>' + DEMO_NOTE + '</details>';
  }
  function qrReceipt(c, hold) {
    return '<div class="bank-box__receipt"><span class="bank-box__receipt-ask">' + (hold ? 'Đã chuyển cọc xong?' : 'Đã chuyển khoản xong?') + '</span>' +
      '<a class="btn btn--primary" href="' + esc(receiptUrl(c)) + '">' + icon('receipt') + (hold ? 'Xem phiếu đặt cọc' : 'Xem phiếu xác nhận') + '</a>' +
      '<small>Phiếu có ngay khi cửa hàng nhận tiền' + (hold ? ' cọc' : '') + '.</small></div>';
  }
  function keepNote(c, hold) {
    return '<p class="order-keep">' + icon('receipt') + '<span>Bạn có thể xem lại ' + (hold ? 'lượt giữ hàng' : 'đơn hàng') + ' này bất cứ lúc nào tại <a href="' + esc((CFG.urls || {}).lookup || '#') + '">Tra cứu đơn hàng</a> (nhập mã <strong>' + esc(c) + '</strong> và số điện thoại). Website cũng tự báo khi ' + (hold ? 'cửa hàng đã nhận cọc' : 'đơn được xác nhận') + ' nếu bạn quay lại bằng thiết bị này.</span></p>';
  }
  function receiptUrl(c) { return ((CFG.urls || {}).receipt || base + '/phieu/') + '?ma=' + encodeURIComponent(c); }
  function maskPhone(p) { var d = String(p || '').replace(/\D/g, ''); return d.length >= 8 ? d.slice(0, 4) + '•••' + d.slice(-3) : p; }
  function hm(ts) { var d = new Date(ts); return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2); }
  function qr(amount, note) {
    var b = CFG.bank || {};
    if (!b.account || !b.bank_id) return '';
    var q = new URLSearchParams({ addInfo: note });
    if (b.holder) q.set('accountName', b.holder);
    if (amount > 0) q.set('amount', amount);
    return 'https://img.vietqr.io/image/' + encodeURIComponent(b.bank_id) + '-' + encodeURIComponent(String(b.account).replace(/\s+/g, '')) + '-compact2.png?' + q.toString();
  }
  function bankRows(amount, note) {
    var b = CFG.bank || {};
    return '<div><dt>Ngân hàng</dt><dd>' + esc(b.bank) + '</dd></div>' +
      '<div><dt>Số tài khoản</dt><dd><strong>' + esc(b.account) + '</strong> <button type="button" class="btn btn--sm btn--outline" data-copy="' + esc(b.account) + '">' + icon('copy') + 'Sao chép</button></dd></div>' +
      (b.holder ? '<div><dt>Chủ tài khoản</dt><dd>' + esc(b.holder) + '</dd></div>' : '') +
      (amount > 0 ? '<div><dt>Số tiền</dt><dd>' + price(amount) + '</dd></div>' : '') +
      '<div><dt>Nội dung</dt><dd><strong>' + esc(note) + '</strong> <button type="button" class="btn btn--sm btn--outline" data-copy="' + esc(note) + '">' + icon('copy') + 'Sao chép</button></dd></div>';
  }
  var DEMO_NOTE = '<p class="summary__note"><strong>Bản chạy thử:</strong> thông tin chuyển khoản là tài khoản thử nghiệm – vui lòng không chuyển tiền thật.</p>';

  /* Dữ liệu lớn (sản phẩm, giấy kiểm định…) – chỉ tải khi cần */
  var dataPromise = null;
  var realFetch = window.fetch.bind(window);
  function data() { return dataPromise || (dataPromise = realFetch((CFG.urls || {}).data || base + '/demo-data.json').then(function (r) { return r.json(); })); }

  /* Kho thẻ sản phẩm dựng sẵn (dùng cho yêu thích, đã xem, gợi ý quà) */
  var poolPromise = null;
  function cardPool() {
    return poolPromise || (poolPromise = realFetch(base + '/the-san-pham.html').then(function (r) { return r.text(); }).then(function (html) {
      var box = document.createElement('div'); box.innerHTML = html;
      var map = {};
      $$('.product-card', box).forEach(function (c) { map[c.dataset.slug] = c.outerHTML; });
      return map;
    }));
  }
  function revealCards(root) {
    var wish = [];
    try { wish = JSON.parse(localStorage.getItem('vnp_wish_v1') || '[]') || []; } catch (e) { /* bỏ qua */ }
    $$('[data-reveal]', root).forEach(function (el) { el.classList.add('is-revealed'); });
    $$('[data-wish]', root).forEach(function (b) { b.setAttribute('aria-pressed', String(wish.indexOf(b.dataset.wish) !== -1)); });
  }

  /* Thay các lời gọi máy chủ bằng file tĩnh đã xuất sẵn */
  window.fetch = function (input, init) {
    var url = typeof input === 'string' ? input : (input && input.url) || '';
    var m = url.match(/\/xem-nhanh\.php\?sp=([^&#]+)/);
    if (m) return realFetch(base + '/xem-nhanh/' + m[1] + '.html', init);
    m = url.match(/\/(dat-hang|giu-hang)\.php\?(?:thanh-toan|trang-thai)=([^&#]+)/);
    if (m) return Promise.resolve(new Response(JSON.stringify(demoStatus(m[1], decodeURIComponent(m[2]))), { headers: { 'Content-Type': 'application/json' } }));
    m = url.match(/\/the-san-pham\.php\?ds=([^&#]*)/);
    if (m) {
      var slugs = decodeURIComponent(m[1]).split(',');
      return cardPool().then(function (map) { return new Response(slugs.map(function (s) { return map[s] || ''; }).join(''), { headers: { 'Content-Type': 'text/html' } }); });
    }
    return realFetch(input, init);
  };

  /* Thanh thông báo bản chạy thử */
  var bar = $('[data-demo-bar]');
  if (bar) {
    if (store('vnp_demo_bar_hidden')) bar.hidden = true;
    var x = $('[data-demo-bar-close]', bar);
    if (x) x.addEventListener('click', function () { bar.hidden = true; store('vnp_demo_bar_hidden', true); });
  }

  /* ------------------------------------------------------------------
   * 1. Danh sách sản phẩm: lọc, tìm, sắp xếp, tìm vòng vừa tay
   * ------------------------------------------------------------------ */
  (function listing() {
    var main = $('.shop-main');
    if (!main) return;
    var info = CFG.menhInfo || {}, ranges = CFG.ranges || {};
    var st = {
      menh: info[P.get('menh')] ? P.get('menh') : '',
      gia: ranges[P.get('gia')] ? P.get('gia') : '',
      q: (P.get('q') || '').trim().slice(0, 80),
      sort: P.get('sap-xep') || '',
      cm: (function (v) { return v >= 120 && v <= 260 ? v / 10 : v; })(parseFloat(String(P.get('chu-vi') || '').replace(',', '.')) || 0),
      mm: parseFloat(String(P.get('ngon-tay') || '').replace(',', '.')) || 0
    };
    var ni = st.cm >= 12 && st.cm <= 26 ? Math.round(st.cm * 10 / Math.PI) : 0;
    if (!ni) st.cm = 0;
    // Ni nhẫn (giống ring_ni_from_mm trong PHP): dưới 30 mm là đường kính lòng nhẫn, còn lại là chu vi ngón tay
    var rv = st.mm; if (rv >= 1.3 && rv <= 2.5) rv *= 10; else if (rv >= 4 && rv <= 8) rv *= 10;
    var ringC = rv < 30 ? rv * Math.PI : rv;
    var ring = st.mm > 0 && ringC >= 42 && ringC <= 76 ? Math.max(1, Math.round((ringC - 39.8) / 1.047)) : 0;
    var ringMm = String(Math.round(ringC * 10) / 10).replace('.', ',');
    if (!ring) st.mm = 0;
    var catMatch = location.pathname.match(/\/danh-muc\/([a-z0-9-]+)/);
    var cat = catMatch ? catMatch[1] : '';

    function link(over) {
      var s = { cat: cat, menh: st.menh, gia: st.gia, q: st.q, 'sap-xep': st.sort, 'chu-vi': st.cm ? String(st.cm) : '', 'ngon-tay': st.mm ? String(st.mm) : '' };
      for (var k in over) s[k] = over[k];
      var qs = new URLSearchParams();
      ['menh', 'gia', 'q', 'sap-xep', 'chu-vi', 'ngon-tay'].forEach(function (k) { if (s[k]) qs.set(k, s[k]); });
      var path = s.cat ? base + '/danh-muc/' + s.cat : (CFG.urls || {}).shop || base + '/san-pham';
      return path + (qs.toString() ? '?' + qs.toString() : '');
    }

    var grid = $('.product-grid', main);
    var cards = grid ? $$('.product-card', grid) : [];
    var words = fold(st.q).split(/\s+/).filter(Boolean);
    var range = st.gia ? ranges[st.gia] : null;
    var shown = cards.filter(function (c) {
      var ok = true;
      if (st.menh) ok = (' ' + c.dataset.menh + ' ').indexOf(' ' + st.menh + ' ') !== -1;
      if (ok && range) { var pr = +c.dataset.price; ok = pr >= range.min && pr <= range.max; }
      if (ok && ni) { var n = +c.dataset.ni; ok = n > 0 && n >= ni && n <= ni + 2; }
      if (ok && ring) { var rr = String(c.dataset.ring || '').split('-'); ok = rr.length === 2 && ring >= +rr[0] && ring <= +rr[1]; }
      if (ok && words.length) { var h = c.dataset.search || ''; ok = words.every(function (w) { return h.indexOf(w) !== -1; }); }
      c.hidden = !ok;
      return ok;
    });

    // Sắp xếp (món đã có chủ luôn xếp cuối)
    var key = function (c) { return { o: +c.dataset.order, p: +c.dataset.price, n: +c.dataset.new, sold: +c.dataset.sold, name: ($('.product-card__name', c) || c).textContent.trim() }; };
    var cmp = {
      moi: function (a, b) { return (b.n - a.n) || (a.o - b.o); },
      'gia-tang': function (a, b) { return (a.p || 1e15) - (b.p || 1e15); },
      'gia-giam': function (a, b) { return b.p - a.p; },
      ten: function (a, b) { return a.name.localeCompare(b.name, 'vi'); }
    }[st.sort] || function (a, b) { return a.o - b.o; };
    if (grid) {
      cards.map(function (c) { return { c: c, k: key(c) }; })
        .sort(function (a, b) { return (a.k.sold - b.k.sold) || cmp(a.k, b.k); })
        .forEach(function (x) { grid.appendChild(x.c); });
    }

    // Tiêu đề
    var title = $('.page-hero__title'), desc = $('.page-hero__desc');
    if (!cat && title) {
      var t = null, d = null;
      if (st.q) { t = 'Kết quả tìm kiếm'; d = 'Từ khóa: “' + st.q + '”'; }
      else if (ring) { t = 'Nhẫn ngọc vừa tay bạn'; d = 'Chu vi ngón tay khoảng ' + ringMm + ' mm → ni nhẫn ' + ring + '.'; }
      else if (ni) { t = 'Vòng ngọc vừa tay bạn'; d = 'Chu vi bàn tay ' + String(st.cm).replace('.', ',') + ' cm → ni phù hợp ' + ni + ' – ' + (ni + 2) + ' mm.'; }
      else if (st.menh) { var mi = info[st.menh]; t = 'Ngọc hợp mệnh ' + mi.name; d = 'Màu tương hợp: ' + mi.good + '. Gợi ý: ' + mi.jade + '.'; }
      if (t) { title.textContent = t; if (desc) desc.textContent = d; document.title = t + ' | ' + (CFG.site || ''); }
    }

    // Số lượng, trạng thái trống
    var count = $('.shop-toolbar__count', main);
    if (count) count.innerHTML = shown.length ? 'Hiển thị <strong>' + shown.length + '</strong> sản phẩm' : 'Không có sản phẩm phù hợp';
    if (grid && !shown.length) {
      grid.hidden = true;
      var empty = document.createElement('div');
      empty.className = 'empty-state';
      empty.innerHTML = '<h2>Chưa tìm thấy sản phẩm phù hợp</h2><p>Hãy thử bỏ bớt bộ lọc, tìm với từ khóa khác, hoặc liên hệ để được tư vấn mẫu theo yêu cầu.</p>' +
        '<div class="empty-state__actions"><a class="btn btn--primary" href="' + esc((CFG.urls || {}).shop) + '">Xem tất cả sản phẩm</a>' +
        (CFG.zalo ? '<a class="btn btn--outline" href="' + esc(CFG.zalo) + '" target="_blank" rel="noopener">Tư vấn qua Zalo</a>' : '') + '</div>';
      grid.parentNode.insertBefore(empty, grid.nextSibling);
    }

    // Bộ lọc đang áp dụng
    var old = $('.active-filters', main);
    if (old) old.remove();
    var chips = [];
    var catLink = $('.filter-list a[aria-current="true"]', main);
    if (cat && catLink) chips.push([catLink.firstChild.textContent.trim(), link({ cat: '' })]);
    if (st.menh) chips.push(['Mệnh ' + info[st.menh].name, link({ menh: '' })]);
    if (st.gia) chips.push([ranges[st.gia].label, link({ gia: '' })]);
    if (st.q) chips.push(['“' + st.q + '”', link({ q: '' })]);
    if (ni) chips.push(['Ni ' + ni + '–' + (ni + 2) + ' mm', link({ 'chu-vi': '' })]);
    if (ring) chips.push(['Ni nhẫn ' + ring, link({ 'ngon-tay': '' })]);
    if (chips.length) {
      var ul = document.createElement('ul');
      ul.className = 'active-filters';
      ul.setAttribute('aria-label', 'Bộ lọc đang áp dụng');
      ul.innerHTML = chips.map(function (c) { return '<li><a href="' + esc(c[1]) + '">' + esc(c[0]) + icon('close') + '<span class="sr-only">Bỏ lọc</span></a></li>'; }).join('');
      var tb = $('.shop-toolbar', main);
      tb.parentNode.insertBefore(ul, tb.nextSibling);
    }

    // Liên kết bộ lọc bên trái giữ các bộ lọc khác
    $$('.shop-sidebar .filter-list a').forEach(function (a) {
      var u = new URL(a.getAttribute('href'), location.href);
      var m = u.pathname.match(/\/danh-muc\/([a-z0-9-]+)/);
      if (u.searchParams.has('menh')) {
        var v = u.searchParams.get('menh'), on = v === st.menh;
        a.setAttribute('href', link({ menh: on ? '' : v }));
        if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
      } else if (u.searchParams.has('gia')) {
        var g = u.searchParams.get('gia'), on2 = g === st.gia;
        a.setAttribute('href', link({ gia: on2 ? '' : g }));
        if (on2) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
      } else {
        a.setAttribute('href', link({ cat: m ? m[1] : '' }));
      }
    });

    // Form sắp xếp & ô nhập
    var sf = $('.sort-form', main);
    if (sf) {
      $$('input[type="hidden"]', sf).forEach(function (i) { i.remove(); });
      [['menh', st.menh], ['gia', st.gia], ['q', st.q], ['chu-vi', st.cm ? String(st.cm) : ''], ['ngon-tay', st.mm ? String(st.mm) : '']].forEach(function (kv) {
        if (!kv[1]) return;
        var i = document.createElement('input'); i.type = 'hidden'; i.name = kv[0]; i.value = kv[1]; sf.insertBefore(i, sf.firstChild);
      });
      var sel = $('select', sf); if (sel) sel.value = st.sort;
    }
    ['#shop-q', '#header-q'].forEach(function (s) { var i = $(s); if (i && st.q) i.value = st.q; });
    var cmIn = $('#sf-cm'); if (cmIn && st.cm) cmIn.value = st.cm;
    var mmIn = $('#sf-mm'); if (mmIn && st.mm) mmIn.value = st.mm;
    // Số đo không hợp lệ → báo lỗi ngay dưới ô (giống bản PHP)
    [['#sf-cm', 'chu-vi', ni, 'Chu vi bàn tay thường từ 12 đến 26 cm – ví dụ: 17,5.'],
     ['#sf-mm', 'ngon-tay', ring, 'Chưa tính được ni từ số đo này. Nhập chu vi ngón tay (VD: 54 mm hoặc 5,4 cm) hoặc đường kính lòng nhẫn (VD: 17 mm).']].forEach(function (x) {
      var inp = $(x[0]), raw = (P.get(x[1]) || '').trim();
      if (!inp || !raw || x[2]) return;
      inp.value = raw; inp.setAttribute('aria-invalid', 'true');
      var er = document.createElement('p'); er.className = 'field-error size-finder__error'; er.setAttribute('role', 'alert'); er.textContent = x[3];
      inp.closest('form').appendChild(er);
    });
    var ri = $('[data-ring-info]'); if (ri && ring) ri.innerHTML = 'Chu vi ngón tay khoảng ' + ringMm + ' mm → <strong class="nowrap">ni nhẫn ' + ring + '</strong>.';
  })();

  /* ------------------------------------------------------------------
   * 2. "Chọn quà giúp tôi"
   * ------------------------------------------------------------------ */
  (function giftFinder() {
    var quiz = $('.gift-quiz');
    var who = P.get('cho') || '';
    if (!quiz || !who) return;
    data().then(function (D) {
      var rec = D.recipients[who];
      if (!rec) return;
      var year = parseInt(P.get('nam-sinh'), 10) || 0;
      if (year < 1900 || year > 2100) year = 0;
      var budget = D.ranges && CFG.ranges[P.get('ngan-sach')] ? P.get('ngan-sach') : '';

      // điền lại lựa chọn vào form
      $$('input[name="cho"]', quiz).forEach(function (r) { r.checked = r.value === who; });
      $$('input[name="ngan-sach"]', quiz).forEach(function (r) { r.checked = r.value === budget; });
      var yi = $('#g-nam'); if (yi && year) yi.value = year;

      var lunar = null;
      if (year) {
        var t = D.menhTables, i = ((year - 4) % 60 + 60) % 60, na = t.napam[Math.floor(i / 2)];
        lunar = { canchi: t.can[i % 10] + ' ' + t.chi[i % 12], menh: na[1] };
      }
      var menh = lunar ? lunar.menh : '';
      var khac = { kim: 'moc', moc: 'tho', tho: 'thuy', thuy: 'hoa', hoa: 'kim' };
      var conflict = function (p) { return menh && p.menh.length && p.menh.indexOf(menh) === -1 && khac[p.menh[0]] === menh; };
      function find(bud, limit) {
        var r = bud ? CFG.ranges[bud] : null;
        var pool = Object.keys(D.products).filter(function (s) {
          var p = D.products[s];
          return p.buy && !conflict(p) && (!r || (p.price >= r.min && p.price <= r.max));
        });
        var out = [];
        pool.forEach(function (s) {
          var p = D.products[s], score = 0;
          if (rec.dip.length && p.dip.some(function (d) { return rec.dip.indexOf(d) !== -1; })) score += 4;
          if (menh && p.menh.indexOf(menh) !== -1) score += 2;
          if (p.featured) score += 1;
          if (rec.dip.length && score < 4 && pool.length > 4) return;
          out.push({ s: s, score: score, price: p.price || 1e15, order: p.order });
        });
        out.sort(function (a, b) { return (b.score - a.score) || (a.price - b.price) || (a.order - b.order); });
        return out.slice(0, limit).map(function (x) { return x.s; });
      }
      var results = find(budget, 8);
      var hidden = budget ? find('', 1e6).filter(function (s) { return D.products[s].price <= 0; }).length : 0;

      cardPool().then(function (map) {
        var mi = lunar ? CFG.menhInfo[lunar.menh] : null;
        var html = '<div class="gift-results" id="ket-qua" tabindex="-1">' +
          '<h2 class="section-title section-title--sm">Gợi ý quà tặng ' + esc(rec.label.toLocaleLowerCase('vi')) + '</h2>' +
          (mi ? '<p class="notice notice--menh" style="--el: ' + esc(mi.color) + '">Người nhận sinh năm ' + year + ' (' + esc(lunar.canchi) + ') – mệnh <strong>' + esc(mi.name) + '</strong>. Màu ngọc hợp: ' + esc(mi.jade) + '. Những món hợp mệnh được ưu tiên, món có màu kỵ mệnh (' + esc(mi.avoid.toLocaleLowerCase('vi')) + ') không được gợi ý.</p>' : '') +
          (results.length ? '<div class="product-grid">' + results.map(function (s) { return map[s] || ''; }).join('') + '</div>' : '') +
          (hidden ? '<p class="notice gift-unpriced">Còn <strong>' + hidden + ' mẫu</strong> phù hợp nhưng giá cần liên hệ (mỗi món ngọc độc bản được báo giá riêng). <a href="' + esc(CFG.urls.gift + '?cho=' + encodeURIComponent(who) + (year ? '&nam-sinh=' + year : '')) + '#ket-qua">Xem cả các mẫu này</a></p>' : '') +
          (!results.length && !hidden ? '<div class="empty-state">' + icon('gift') + '<h3>Chưa có mẫu phù hợp hoàn toàn</h3><p>Hãy thử chọn ngân sách khác, hoặc nhắn cho chúng tôi để được tư vấn riêng.</p></div>' : '') +
          '</div>';
        quiz.insertAdjacentHTML('afterend', html);
        var box = $('#ket-qua');
        revealCards(box);
        if (location.hash === '#ket-qua') { box.scrollIntoView(); box.focus({ preventScroll: true }); }
      });
    });
  })();

  /* ------------------------------------------------------------------
   * 3. Tra cứu giấy kiểm định
   * ------------------------------------------------------------------ */
  (function certLookup() {
    var input = $('#cert-code');
    var q = (P.get('ma') || '').trim().slice(0, 40);
    if (!input || !q) return;
    input.value = q;
    data().then(function (D) {
      var key = q.toUpperCase().replace(/[^A-Z0-9]/g, '');
      var c = D.certs.filter(function (x) { return x.key === key; })[0];
      var wrap = $('.page-cert .section--tight .container--narrow');
      if (!wrap) return;
      var html;
      if (!c) {
        html = '<div class="empty-state" role="status">' + icon('cert') + '<h2>Không tìm thấy mã “' + esc(q) + '”</h2><p>Vui lòng kiểm tra lại mã (gồm chữ và số, ví dụ VNP-2610-0001). Nếu vẫn không tìm thấy, hãy liên hệ để được hỗ trợ đối chiếu.</p>' +
          '<div class="empty-state__actions">' + (CFG.zalo ? '<a class="btn btn--primary" href="' + esc(CFG.zalo) + '" target="_blank" rel="noopener">Hỏi qua Zalo</a>' : '') + '<a class="btn btn--outline" href="' + esc(CFG.urls.contact) + '">Liên hệ</a></div></div>';
      } else {
        var rows = '<div><dt>Kết luận</dt><dd><strong>' + esc(c.result) + '</strong></dd></div>';
        Object.keys(c.details || {}).forEach(function (k) { rows += '<div><dt>' + esc(k) + '</dt><dd>' + esc(c.details[k]) + '</dd></div>'; });
        rows += '<div><dt>Đơn vị kiểm định</dt><dd>' + esc(c.lab) + '</dd></div><div><dt>Ngày cấp</dt><dd>' + esc(c.date) + '</dd></div>';
        html = (c.mau ? '<p class="notice">Đây là <strong>dữ liệu mẫu</strong> để minh họa tính năng tra cứu.</p>' : '') +
          '<article class="cert-result card" role="status"><div class="cert-result__head"><span class="cert-result__badge">' + icon('check') + 'Mã hợp lệ</span><h2>Giấy kiểm định <span class="cert-code">' + esc(c.code) + '</span></h2></div>' +
          '<div class="cert-result__grid"><button type="button" class="cert-result__img" data-lightbox-src="' + esc(c.image) + '" aria-label="Phóng to ảnh giấy kiểm định"><img src="' + esc(c.image) + '" alt="Ảnh giấy kiểm định ' + esc(c.code) + '" width="1000" height="700"><span class="cert-result__zoom">' + icon('zoom') + '</span></button>' +
          '<dl class="spec-list">' + rows + '</dl></div>' +
          (c.product ? '<div class="cert-result__product"><p class="eyebrow">Sản phẩm tương ứng</p><a class="hold-product" href="' + esc(c.product.url) + '"><img src="' + esc(c.product.image) + '" alt="" width="96" height="96"><span><strong>' + esc(c.product.name) + '</strong><small>' + esc(c.product.price) + '</small></span></a></div>' : '') +
          '</article>';
      }
      wrap.insertAdjacentHTML('afterbegin', html);
    });
  })();

  /* ------------------------------------------------------------------
   * 4. Điền sẵn form liên hệ / đặt lịch khi đi từ một sản phẩm (?sp=)
   * ------------------------------------------------------------------ */
  if (P.get('sp') && ($('#c-noi-dung') || $('#a-sp'))) {
    data().then(function (D) {
      var p = D.products[P.get('sp')];
      if (!p) return;
      var ta = $('#c-noi-dung');
      if (ta && !ta.value) ta.value = 'Tôi muốn tìm mẫu tương tự "' + p.name + '" (' + p.color + (p.size ? ', ' + p.size : '') + '). Ngân sách khoảng: ';
      var sp = $('#a-sp');
      if (sp && !sp.value) sp.value = p.name;
    });
  }

  /* ------------------------------------------------------------------
   * 5. Biểu mẫu: không gửi đi đâu, hiển thị kết quả mô phỏng
   * ------------------------------------------------------------------ */
  function readJSON(id) { var el = document.getElementById(id); try { return el ? JSON.parse(el.textContent) : null; } catch (e) { return null; } }

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (e.defaultPrevented || !(f instanceof HTMLFormElement)) return;
    var action = f.getAttribute('action') || '';
    if (f.hasAttribute('data-checkout-form')) { e.preventDefault(); checkout(f); }
    else if (/giu-hang/.test(action)) { e.preventDefault(); hold(f); }
    else if (f.hasAttribute('data-order-lookup')) { e.preventDefault(); lookup(f); }
    else if (f.id === 'lien-he-form') { e.preventDefault(); replaceForm(f, '<strong>Cảm ơn bạn!</strong> Chúng tôi đã nhận được tin nhắn và sẽ liên hệ lại sớm.'); }
    else if (/dat-lich/.test(action) && f.method.toLowerCase() === 'post') {
      e.preventDefault();
      var slot = $('input[name="gio"]:checked', f), day = f.elements.ngay ? f.elements.ngay.value : '';
      if (!slot) { window.alert('Vui lòng chọn khung giờ.'); resetBtn(f); return; }
      var parts = day.split('-');
      replaceForm(f, '<strong>Đã đặt lịch thành công!</strong> Hẹn gặp ' + esc(f.elements['ho-ten'].value) + ' lúc <strong>' + esc(slot.value + ' ' + parts[2] + '/' + parts[1] + '/' + parts[0]) + '</strong>. Chúng tôi sẽ gọi xác nhận trước buổi hẹn.');
    }
  });
  // Tra cứu đơn hàng (mô phỏng): chỉ tra được đơn / lượt giữ vừa tạo thử trên trình duyệt này
  function lookup(f) {
    var flat = function (c) { return String(c || '').toUpperCase().replace(/[^A-Z0-9]/g, ''); };
    var digits = function (p) { return String(p || '').replace(/\D/g, '').replace(/^84(\d{9})$/, '0$1'); };
    var c = flat(f.elements.ma.value), ph = digits(f.elements['dien-thoai'].value);
    var o = store('vnp_demo_order'), h = store('vnp_demo_hold'), to = '';
    if (o && flat(o.code) === c && digits(o.phone) === ph) to = (CFG.urls || {}).order;
    else if (h && flat(h.code) === c && digits(h.phone) === ph) to = (CFG.urls || {}).hold;
    if (to) { location.href = to; return; }
    var err = $('[data-lookup-error]', f);
    if (err) {
      err.innerHTML = 'Không tìm thấy đơn hàng khớp với mã và số điện thoại này. <strong>Bản chạy thử:</strong> chỉ tra được đơn hàng / lượt giữ hàng bạn vừa tạo thử trên trình duyệt này.';
      err.hidden = false;
    }
    resetBtn(f);
  }
  function resetBtn(f) { setTimeout(function () { $$('.is-loading', f).forEach(function (b) { b.classList.remove('is-loading'); b.disabled = false; }); }, 10); }
  function replaceForm(f, msg) {
    var box = document.createElement('div');
    box.className = 'card';
    box.setAttribute('tabindex', '-1');
    box.innerHTML = '<div class="notice notice--success" role="status"><p>' + msg + '</p></div><div class="demo-sim"><p><strong>Bản chạy thử:</strong> thông tin chưa được gửi tới cửa hàng. Trên website thật, cửa hàng nhận ngay tin báo qua Telegram/email và dữ liệu được lưu vào file Excel.</p></div>';
    f.parentNode.replaceChild(box, f);
    box.focus();
  }

  function checkout(f) {
    var cat = readJSON('catalog-data') || {}, ship = readJSON('shipping-data') || { fee: 0, freeFrom: 0 };
    var items = [];
    try { items = JSON.parse(f.elements.items.value || '[]'); } catch (e) { items = []; }
    var lines = items.filter(function (i) { return cat[i.slug] && cat[i.slug].buy; }).map(function (i) {
      var p = cat[i.slug];
      return { name: p.name, price: p.price, unique: !!p.unique, cert: p.cert || '', qty: p.unique ? 1 : Math.max(1, Math.min(20, +i.qty || 1)) };
    });
    var subtotal = lines.reduce(function (s, l) { return s + l.price * l.qty; }, 0);
    var fee = ship.fee > 0 && !(ship.freeFrom > 0 && subtotal >= ship.freeFrom) ? ship.fee : 0;
    var gifts = $$('[data-gift-price]:checked', f).map(function (c) { return { label: c.closest('label').querySelector('span').textContent.trim(), price: +c.dataset.giftPrice || 0 }; });
    var giftFee = gifts.reduce(function (s, g) { return s + g.price; }, 0);
    var card = $('[data-gift-note]:checked', f) && f.elements['loi-nhan'] ? f.elements['loi-nhan'].value.trim() : '';
    var payEl = $('input[name="thanh-toan"]:checked', f);
    store('vnp_demo_order', {
      created: Date.now(), code: code('NP'), name: f.elements['ho-ten'].value.trim(), phone: f.elements['dien-thoai'].value.trim(),
      pay: payEl ? payEl.value : 'cod', lines: lines, subtotal: subtotal, shipping: fee, gifts: gifts, card: card,
      vat: ship.vat > 0 && subtotal > 0 ? Math.round((subtotal + fee + giftFee) * ship.vat / 100) : 0, vat_rate: ship.vat || 0,
      total: subtotal > 0 ? subtotal + fee + giftFee + (ship.vat > 0 ? Math.round((subtotal + fee + giftFee) * ship.vat / 100) : 0) : 0
    });
    location.href = (CFG.urls || {}).order || base + '/dat-hang/';
  }

  function hold(f) {
    var view = {};
    try { view = JSON.parse(($('[data-view-item]') || {}).dataset.viewItem || '{}'); } catch (e) { view = {}; }
    var pct = (CFG.hold || {}).percent || 10;
    var img = $('[data-gallery-main]');
    store('vnp_demo_hold', {
      code: code('GIU'), slug: view.id, product: view.name, price: view.price,
      deposit: Math.round((view.price || 0) * pct / 100 / 10000) * 10000,
      image: img ? img.getAttribute('src') : '', url: location.pathname,
      name: f.elements['ho-ten'].value.trim(), phone: f.elements['dien-thoai'].value.trim(),
      status: 'pending', created: Date.now(), until: Date.now() + ((CFG.hold || {}).pending || 60) * 60000
    });
    location.href = (CFG.urls || {}).hold || base + '/giu-hang/';
  }

  /* ------------------------------------------------------------------
   * 6. Trang "Đặt hàng thành công" (mô phỏng)
   * ------------------------------------------------------------------ */
  (function orderPage() {
    var box = $('[data-demo-order]');
    if (!box) return;
    function render() {
      var o = store('vnp_demo_order');
      if (!o) {
        box.innerHTML = '<div class="empty-state empty-state--page">' + icon('gift') + '<h1>Chưa có đơn hàng</h1><p>Hãy thêm sản phẩm vào giỏ và đặt hàng để xem thử bước này.</p><div class="empty-state__actions"><a class="btn btn--primary" href="' + esc(CFG.urls.shop) + '">Xem sản phẩm</a></div></div>';
        return;
      }
      box.className = 'container order-done';
      box.setAttribute('data-clear-cart', '');
      var bank = CFG.bank || {};
      var transfer = o.pay === 'chuyen-khoan' && bank.account;
      var paid = transfer && o.paid;
      var codOk = !transfer && o.confirmed;
      var hasReceipt = transfer ? paid : codOk;
      var payNow = transfer && !paid;
      box.classList.toggle('order-done--pay', !!payNow);
      var receiptBlock = payNow ? '' : receiptLink(o.code, hasReceipt, transfer ? 'Phiếu có ngay khi cửa hàng nhận được tiền' : 'Phiếu có ngay khi cửa hàng gọi xác nhận đơn', '');
      var st = demoStatus('dat-hang', o.code);
      box.setAttribute('data-my-order', JSON.stringify({ code: o.code, type: 'order', view: (CFG.urls || {}).order, receipt: receiptUrl(o.code), at: Math.round((o.created || Date.now()) / 1000), key: st.status + (st.receipt ? '+r' : '') }));
      var mins = (CFG.hold || {}).orderPending || 60;
      if (transfer && !o.until) { o.until = Date.now() + mins * 60000; store('vnp_demo_order', o); }
      var uniq = o.lines.filter(function (l) { return l.unique; });
      var lines = o.lines.map(function (l) { return '<li><span>' + esc(l.name) + ' <small>× ' + l.qty + '</small>' + (transfer && l.unique ? ' <span class="badge badge--unique">Độc bản</span>' : '') + '</span><strong>' + (l.price > 0 ? price(l.price * l.qty) : 'Liên hệ') + '</strong></li>'; }).join('');
      var gifts = o.gifts.map(function (g) { return '<div><dt>' + esc(g.label) + '</dt><dd>' + (g.price > 0 ? price(g.price) : 'Miễn phí') + '</dd></div>'; }).join('');
      var head;
      if (!transfer) {
        head = '<span class="order-done__icon">' + icon('check') + '</span><h1>Đặt hàng thành công!</h1><p>Cảm ơn ' + esc(o.name) + '. Mã đơn hàng của bạn là <strong class="order-code">' + esc(o.code) + '</strong><br>Chúng tôi sẽ gọi số <strong>' + esc(o.phone) + '</strong> để xác nhận trong thời gian sớm nhất.' +
          (uniq.length ? '<br>Món độc bản trong đơn được <strong>giữ riêng cho bạn</strong> trong lúc chờ xác nhận.' : '') + '</p>';
      } else if (paid) {
        head = '<span class="order-done__icon">' + icon('check') + '</span><h1>Đã nhận thanh toán – cảm ơn bạn!</h1><p>Đơn hàng <strong class="order-code">' + esc(o.code) + '</strong> đã được thanh toán <strong>' + price(o.total) + '</strong>.<br>Chúng tôi sẽ đóng gói cẩn thận và gọi số <strong>' + esc(o.phone) + '</strong> để hẹn giao hàng.</p>';
      } else {
        head = '<span class="order-done__icon order-done__icon--wait">' + icon('clock') + '</span><h1>Đã nhận đơn hàng – vui lòng chuyển khoản</h1><p>Mã đơn hàng <strong class="order-code">' + esc(o.code) + '</strong>' +
          (uniq.length ? '<br>Món độc bản trong đơn được <strong>giữ riêng cho bạn đến ' + hm(o.until) + '</strong> (' + mins + ' phút) để chờ chuyển khoản.' : '') + '</p>';
      }
      var state = transfer ? '<p class="order-state"><span class="hold-status hold-status--' + (paid ? 'confirmed">Đã nhận thanh toán' : 'pending">Chờ thanh toán') + '</span></p>' : '';
      var steps = transfer ? '<ol class="steps hold-steps"><li class="is-done">Đặt đơn hàng</li><li class="' + (paid ? 'is-done' : 'is-current') + '">Chuyển khoản ' + price(o.total) + '</li><li class="' + (paid ? 'is-done' : '') + '">Xác nhận thanh toán – đóng gói &amp; giao hàng</li></ol>' : '';
      var side;
      if (transfer && !paid) {
        side = '<div class="summary bank-box"><h2 class="summary__title">Quét mã để chuyển khoản</h2><img class="bank-box__qr" src="' + esc(qr(o.total, o.code)) + '" alt="Mã QR chuyển khoản cho đơn ' + esc(o.code) + '" width="300" height="356">' + qrMobileHint() + qrReceipt(o.code, false) + manualTransfer(o.total, o.code) + '</div>';
      } else if (paid) {
        side = '<div class="summary bank-box bank-box--paid"><h2 class="summary__title">' + icon('check') + 'Bước tiếp theo</h2><ol class="steps"><li>Món ngọc được đóng hộp quà cẩn thận, kèm giấy kiểm định.</li><li>Nhân viên gọi điện hẹn thời gian giao hàng.</li><li>Bạn nhận hàng và kiểm tra sản phẩm.</li></ol></div>';
      } else {
        side = '<div class="summary"><h2 class="summary__title">Bước tiếp theo</h2><ol class="steps"><li>Nhân viên gọi điện xác nhận đơn và tư vấn ni tay/màu sắc (nếu cần).</li><li>Sản phẩm được đóng hộp quà cẩn thận, kèm giấy kiểm định.</li><li>Bạn kiểm tra hàng và thanh toán khi nhận.</li></ol>' +
          (codOk ? '<p class="summary__note"><strong>Cửa hàng đã xác nhận đơn hàng của bạn.</strong></p>' : '') + '</div>';
      }
      box.innerHTML =
        '<div class="order-done__head">' + head + receiptBlock + '</div>' +
        '<div class="order-done__grid"><div class="summary"><h2 class="summary__title">Chi tiết đơn hàng</h2>' + state + '<ul class="order-lines">' + lines + '</ul>' +
        '<dl><div><dt>Tạm tính</dt><dd>' + price(o.subtotal) + '</dd></div><div><dt>Phí giao hàng</dt><dd>' + (o.shipping > 0 ? price(o.shipping) : 'Miễn phí') + '</dd></div>' + gifts +
        (o.vat > 0 ? '<div><dt>Thuế VAT (' + o.vat_rate + '%)</dt><dd>' + price(o.vat) + '</dd></div>' : '') +
        '<div class="summary__total"><dt>Tổng cộng</dt><dd>' + price(o.total) + '</dd></div></dl>' +
        '<p class="summary__note">Thanh toán: ' + (o.pay === 'chuyen-khoan' ? 'Chuyển khoản ngân hàng' : 'Thanh toán khi nhận hàng (COD)') + '</p>' +
        (o.card ? '<p class="summary__note">Lời nhắn trên thiệp: “' + esc(o.card) + '”</p>' : '') + steps + '</div>' + side + '</div>' +
        (transfer && !paid
          ? '<div class="demo-sim"><p><strong>Bản chạy thử:</strong> trên website thật, trang này tự chuyển sang “Đã nhận thanh toán” khi tiền về tài khoản (tự động qua dịch vụ theo dõi giao dịch, hoặc cửa hàng bấm xác nhận). Món độc bản được giữ riêng trong thời gian chờ, khách khác không đặt trùng được. Bấm nút dưới đây để xem bước tiếp theo.</p><button type="button" class="btn btn--primary btn--sm" data-demo-paid>' + icon('check') + 'Mô phỏng: cửa hàng đã nhận tiền</button></div>'
          : '<div class="demo-sim"><p><strong>Bản chạy thử:</strong> đơn hàng này không được gửi tới cửa hàng. Trên website thật, cửa hàng nhận ngay tin báo qua Telegram/email' + (!transfer && uniq.length ? ', món độc bản được giữ riêng cho khách tới khi cửa hàng gọi xác nhận đơn – khách khác không đặt trùng được' : '') +
            (paid ? '; khi tiền về, khách nhận <strong>phiếu xác nhận thanh toán</strong> (xem, tải PDF, lưu ảnh, có mã QR kiểm tra phiếu thật).</p>'
              : codOk ? '; sau khi gọi xác nhận, khách nhận <strong>phiếu xác nhận đơn hàng</strong>.</p>'
              : '. Sau khi gọi khách, cửa hàng bấm “Xác nhận đơn” – khách nhận phiếu xác nhận đơn hàng.</p><button type="button" class="btn btn--primary btn--sm" data-demo-cod>' + icon('check') + 'Mô phỏng: cửa hàng đã gọi xác nhận đơn</button>') + '</div>') +
        keepNote(o.code, false) + '<div class="empty-state__actions"><a class="btn btn--primary" href="' + esc(CFG.urls.shop) + '">Tiếp tục mua sắm</a><a class="btn btn--outline" href="' + esc(base + '/') + '">Về trang chủ</a></div>';
      var b = $('[data-demo-paid]', box);
      if (b) b.addEventListener('click', function () { o.paid = true; o.paidAt = Date.now(); store('vnp_demo_order', o); render(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
      var c = $('[data-demo-cod]', box);
      if (c) c.addEventListener('click', function () { o.confirmed = Date.now(); store('vnp_demo_order', o); render(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
    }
    render();
  })();

  /* ------------------------------------------------------------------
   * 7. Trang "Đặt cọc giữ hàng" (mô phỏng, có nút giả lập cửa hàng xác nhận cọc)
   * ------------------------------------------------------------------ */
  (function holdPage() {
    var box = $('[data-demo-hold]');
    if (!box) return;
    function render() {
      var h = store('vnp_demo_hold');
      if (!h) {
        box.innerHTML = '<div class="empty-state empty-state--page">' + icon('lock') + '<h1>Chưa có yêu cầu giữ hàng</h1><p>Mở một sản phẩm từ 10 triệu trở lên và bấm “Giữ hàng 24 giờ” để xem thử bước này.</p><div class="empty-state__actions"><a class="btn btn--primary" href="' + esc(CFG.urls.shop) + '">Xem sản phẩm</a></div></div>';
        return;
      }
      var hcfg = CFG.hold || {}, bank = CFG.bank || {};
      var ok = h.status === 'confirmed';
      box.className = 'container order-done';
      box.classList.toggle('order-done--pay', !ok && !!bank.account);
      box.setAttribute('data-my-order', JSON.stringify({ code: h.code, type: 'hold', view: (CFG.urls || {}).hold, receipt: receiptUrl(h.code), at: Math.round((h.created || Date.now()) / 1000), key: h.status + (ok ? '+r' : '') }));
      box.innerHTML =
        '<div class="order-done__head">' +
        (ok ? '<span class="order-done__icon">' + icon('check') + '</span><h1>Đã nhận cọc – món ngọc đang được giữ cho bạn!</h1><p><strong>' + esc(h.product) + '</strong> được giữ riêng cho ' + esc(h.name) + ' đến <strong>' + dmy(h.until) + '</strong>.<br>Mã giữ hàng: <strong class="order-code">' + esc(h.code) + '</strong></p>' +
              receiptLink(h.code, true, '', ' đặt cọc')
            : '<span class="order-done__icon order-done__icon--wait">' + icon('clock') + '</span><h1>Vui lòng chuyển khoản đặt cọc</h1><p>Món ngọc đang được <strong>giữ tạm</strong> cho bạn đến <strong>' + hm(h.until) + '</strong> (' + (hcfg.pending || 60) + ' phút).<br>Hệ thống xác nhận ngay khi nhận được cọc và giữ món ngọc cho bạn <strong>' + (hcfg.hours || 24) + ' giờ</strong>.<br>Mã giữ hàng: <strong class="order-code">' + esc(h.code) + '</strong></p>' + (bank.account ? '' : receiptLink(h.code, false, 'Phiếu có ngay khi cửa hàng nhận được tiền cọc', ' đặt cọc'))) +
        '</div><div class="order-done__grid"><div class="summary"><h2 class="summary__title">Thông tin giữ hàng</h2>' +
        '<a class="hold-product" href="' + esc(h.url) + '"><img src="' + esc(h.image) + '" alt="" width="96" height="96"><span><strong>' + esc(h.product) + '</strong><small>' + price(h.price) + '</small></span></a>' +
        '<dl><div><dt>Trạng thái</dt><dd><span class="hold-status hold-status--' + (ok ? 'confirmed">Đã nhận cọc' : 'pending">Chờ đặt cọc') + '</span></dd></div>' +
        '<div><dt>' + (ok ? 'Giữ đến' : 'Chuyển cọc trước') + '</dt><dd>' + dmy(h.until) + '</dd></div>' +
        '<div class="summary__total"><dt>Tiền đặt cọc</dt><dd>' + price(h.deposit) + '</dd></div></dl>' +
        '<ol class="steps hold-steps"><li class="is-done">Gửi yêu cầu giữ hàng</li><li class="' + (ok ? 'is-done' : 'is-current') + '">Chuyển khoản đặt cọc ' + price(h.deposit) + '</li><li class="' + (ok ? 'is-done' : '') + '">Xác nhận – giữ hàng ' + (hcfg.hours || 24) + ' giờ</li></ol></div>' +
        (!ok && bank.account
          ? '<div class="summary bank-box"><h2 class="summary__title">Quét mã để chuyển cọc</h2><img class="bank-box__qr" src="' + esc(qr(h.deposit, h.code)) + '" alt="Mã QR đặt cọc cho ' + esc(h.code) + '" width="300" height="356">' + qrMobileHint() + qrReceipt(h.code, true) + manualTransfer(h.deposit, h.code) + '</div>'
          : '<div class="summary"><h2 class="summary__title">Cần hỗ trợ?</h2><p>Gọi <a href="' + esc(CFG.tel) + '">' + esc(CFG.hotline) + '</a>' + (CFG.zalo ? ' hoặc nhắn Zalo' : '') + ' để được tư vấn thêm.</p></div>') +
        '</div>' +
        (!ok ? '<div class="demo-sim"><p><strong>Bản chạy thử:</strong> trên website thật, bước xác nhận diễn ra tự động khi tiền cọc về tài khoản (hoặc cửa hàng bấm xác nhận). Bấm nút dưới đây để xem trang chuyển sang trạng thái đã nhận cọc.</p><button type="button" class="btn btn--primary btn--sm" data-demo-confirm>' + icon('check') + 'Mô phỏng: cửa hàng đã nhận cọc</button></div>'
             : '<div class="demo-sim"><p><strong>Bản chạy thử:</strong> trạng thái giữ hàng chỉ lưu trên trình duyệt của bạn, sản phẩm vẫn hiển thị bình thường với khách khác.</p></div>') +
        keepNote(h.code, true) + '<div class="empty-state__actions"><a class="btn btn--primary" href="' + esc(h.url) + '">Quay lại sản phẩm</a><a class="btn btn--outline" href="' + esc(CFG.urls.shop) + '">Xem sản phẩm khác</a></div>';
      var b = $('[data-demo-confirm]', box);
      if (b) b.addEventListener('click', function () {
        h.status = 'confirmed';
        h.confirmedAt = Date.now();
        h.until = Date.now() + (hcfg.hours || 24) * 3600000;
        store('vnp_demo_hold', h);
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
    render();
  })();
  /* ------------------------------------------------------------------
   * 8. Phiếu xác nhận (mô phỏng): dựng từ đơn / lượt giữ lưu trên trình duyệt, receipt.js vẽ phiếu
   * ------------------------------------------------------------------ */
  (function receiptPage() {
    var mount = $('[data-receipt-mount]');
    if (!mount || !$('#receipt-data')) return;
    var ma = P.get('ma') || '', sec = function (ms) { return Math.round((ms || Date.now()) / 1000); }, R = null;
    if (ma.indexOf('GIU') === 0) {
      var h = store('vnp_demo_hold');
      if (h && h.code === ma && h.status === 'confirmed') {
        R = { kind: 'coc', code: h.code, name: h.name, phone: maskPhone(h.phone), status: 'valid',
          items: [{ name: h.product, qty: 1, price: h.price, cert: '', unique: false }],
          total: h.price, paid: h.deposit, remain: Math.max(0, h.price - h.deposit), method: 'Chuyển khoản ngân hàng',
          paid_at: sec(h.confirmedAt), issued: sec(h.confirmedAt), hold_until: sec(h.until), agreed_at: sec(h.created) };
      }
    } else {
      var o = store('vnp_demo_order');
      var transfer = o && o.pay === 'chuyen-khoan' && (CFG.bank || {}).account;
      if (o && o.code === ma && (transfer ? o.paid : o.confirmed)) {
        R = { kind: transfer ? 'thanh-toan' : 'don-hang', code: o.code, name: o.name, phone: maskPhone(o.phone), status: 'valid',
          items: o.lines.map(function (l) { return { name: l.name, qty: l.qty, price: l.price, cert: l.cert || '', unique: !!l.unique }; }),
          subtotal: o.subtotal, shipping: o.shipping, gifts: o.gifts || [], vat: o.vat || 0, vat_rate: o.vat_rate || 0, total: o.total,
          paid: transfer ? o.total : 0, remain: transfer ? 0 : o.total,
          method: transfer ? 'Chuyển khoản ngân hàng' : 'Thanh toán khi nhận hàng (COD)',
          paid_at: transfer ? sec(o.paidAt) : 0, issued: sec(transfer ? o.paidAt : o.confirmed) };
      }
    }
    if (R) { window.VNP_RECEIPT = R; return; }
    $$('[data-receipt-tools], .receipt-tools__hint').forEach(function (el) { el.hidden = true; });
    var po = store('vnp_demo_order'), ph = store('vnp_demo_hold'), pend = null;
    if (po && po.code === ma) {
      var tr = po.pay === 'chuyen-khoan' && (CFG.bank || {}).account;
      pend = { hold: false, cod: !tr, state: tr ? 'pending' : 'cod', lines: po.lines.map(function (l) { return l.name + ' × ' + l.qty; }), amount: po.total, phone: po.phone, view: (CFG.urls || {}).order };
    } else if (ph && ph.code === ma) {
      pend = { hold: true, cod: false, state: 'pending', lines: [ph.product], amount: ph.deposit, phone: ph.phone, view: (CFG.urls || {}).hold };
    }
    if (pend) {
      var title = pend.hold ? 'Phiếu xác nhận đặt cọc' : pend.cod ? 'Phiếu xác nhận đơn hàng' : 'Phiếu xác nhận thanh toán';
      var chip = pend.hold ? 'Chờ xác nhận tiền cọc' : pend.cod ? 'Chờ cửa hàng xác nhận' : 'Chờ xác nhận thanh toán';
      var text = pend.hold ? 'Bạn đã chuyển cọc? Phiếu xác nhận đặt cọc sẽ hiện tại trang này ngay khi cửa hàng nhận được tiền. Chưa chuyển thì bấm “Xem mã QR đặt cọc”.'
        : pend.cod ? 'Cửa hàng sẽ gọi số ' + maskPhone(pend.phone) + ' để xác nhận đơn. Phiếu xác nhận đơn hàng sẽ có tại trang này ngay sau đó.'
        : 'Bạn đã chuyển khoản? Phiếu xác nhận thanh toán sẽ hiện tại trang này ngay khi cửa hàng nhận được tiền. Chưa chuyển thì bấm “Xem mã QR chuyển khoản”.';
      var steps = pend.hold ? ['Gửi yêu cầu giữ hàng', 'Chuyển khoản đặt cọc ' + price(pend.amount), 'Cửa hàng nhận cọc – phát hành phiếu']
        : pend.cod ? ['Đặt đơn hàng', 'Cửa hàng gọi xác nhận đơn', 'Phát hành phiếu xác nhận đơn hàng'] : ['Đặt đơn hàng', 'Chuyển khoản ' + price(pend.amount), 'Cửa hàng nhận tiền – phát hành phiếu'];
      mount.innerHTML = '<div class="receipt-wait card" data-receipt-watch="' + esc(ma) + '" data-watch-type="' + (pend.hold ? 'hold' : 'order') + '" data-watch-key="' + pend.state + '">' +
        '<span class="receipt-wait__icon">' + icon('clock') + '</span><p class="eyebrow">' + title + '</p><h1 class="receipt-wait__title">Phiếu chưa được phát hành</h1>' +
        '<p class="receipt-wait__code">' + (pend.hold ? 'Mã giữ hàng' : 'Mã đơn hàng') + ' <strong>' + esc(ma) + '</strong> <span class="hold-status hold-status--pending">' + chip + '</span></p>' +
        '<p class="receipt-wait__text">' + esc(text) + '</p><ul class="receipt-wait__lines">' + pend.lines.map(function (l) { return '<li>' + esc(l) + '</li>'; }).join('') +
        (pend.amount > 0 ? '<li class="receipt-wait__total"><span>' + (pend.hold ? 'Tiền đặt cọc' : 'Tổng cộng') + '</span><strong>' + price(pend.amount) + '</strong></li>' : '') + '</ul>' +
        '<ol class="steps hold-steps"><li class="is-done">' + steps[0] + '</li><li class="is-current">' + steps[1] + '</li><li>' + steps[2] + '</li></ol>' +
        '<div class="receipt-wait__actions"><a class="btn btn--primary" href="' + esc(pend.view) + '">' + (pend.cod ? 'Xem đơn hàng' : pend.hold ? 'Xem mã QR đặt cọc' : 'Xem mã QR chuyển khoản') + '</a></div>' +
        '<p class="receipt-wait__note"><strong>Bản chạy thử:</strong> quay lại trang ' + (pend.hold ? 'giữ hàng' : 'đơn hàng') + ' và bấm nút “Mô phỏng: cửa hàng đã …” – trang này sẽ hiện phiếu. Trên website thật, trang tự cập nhật khi cửa hàng xác nhận.</p></div>';
      return;
    }
    mount.innerHTML = '<div class="empty-state empty-state--page">' + icon('receipt') + '<h1>Chưa có phiếu để xem</h1><p>Hãy đặt hàng hoặc giữ hàng, rồi bấm “Mô phỏng: cửa hàng đã nhận tiền / đã xác nhận” để xem phiếu xác nhận.</p><div class="empty-state__actions"><a class="btn btn--primary" href="' + esc(CFG.urls.shop) + '">Xem sản phẩm</a></div></div>';
  })();
})();
