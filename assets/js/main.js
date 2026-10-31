/* ==========================================================================
   Viên Ngọc Phương — tương tác giao diện
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var body = document.body;
  var setScrollbar = function () {
    document.documentElement.style.setProperty('--sbw', (window.innerWidth - document.documentElement.clientWidth) + 'px');
  };
  setScrollbar();
  window.addEventListener('resize', setScrollbar);
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var ICONS = {
    check: '<path d="M20 6 9 17l-5-5"/>',
    close: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    trash: '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
    plus: '<path d="M5 12h14"/><path d="M12 5v14"/>',
    minus: '<path d="M5 12h14"/>',
    arrow: '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    alert: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
    receipt: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
    zoom: '<circle cx="11" cy="11" r="7.5"/><path d="m20.5 20.5-4.2-4.2"/><path d="M11 8v6"/><path d="M8 11h6"/>',
    left: '<path d="m15 18-6-6 6-6"/>',
    right: '<path d="m9 18 6-6-6-6"/>'
  };
  function icon(name) {
    return '<svg class="icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + ICONS[name] + '</svg>';
  }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function formatPrice(n) {
    n = Math.round(Number(n) || 0);
    if (n <= 0) return 'Liên hệ';
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '₫';
  }
  function readJSON(id) {
    var el = document.getElementById(id);
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
  }
  var base = body.dataset.base || '';
  function store(key, fallback) {
    try { var v = JSON.parse(localStorage.getItem(key) || 'null'); return v == null ? fallback : v; } catch (e) { return fallback; }
  }
  function storeSet(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* chế độ riêng tư */ }
  }

  /* Đo lường: gửi sự kiện tới Google Analytics / Facebook / TikTok nếu đã cài mã */
  function track(event, d) {
    d = d || {};
    var items = d.items || (d.id ? [{ item_id: d.id, item_name: d.name, price: d.price, quantity: d.qty || 1 }] : []);
    var value = Number(d.value) || items.reduce(function (s, i) { return s + (Number(i.price) || 0) * (i.quantity || 1); }, 0);
    try {
      if (window.gtag) window.gtag('event', event, { currency: 'VND', value: value, items: items, transaction_id: d.transaction_id });
      var fbMap = { view_item: 'ViewContent', add_to_cart: 'AddToCart', purchase: 'Purchase', begin_checkout: 'InitiateCheckout', add_to_wishlist: 'AddToWishlist' };
      if (window.fbq && fbMap[event]) window.fbq('track', fbMap[event], { currency: 'VND', value: value, content_ids: items.map(function (i) { return i.item_id; }), content_type: 'product' });
      var ttMap = { view_item: 'ViewContent', add_to_cart: 'AddToCart', purchase: 'CompletePayment', begin_checkout: 'InitiateCheckout', add_to_wishlist: 'AddToWishlist' };
      if (window.ttq && ttMap[event]) window.ttq.track(ttMap[event], { currency: 'VND', value: value, contents: items.map(function (i) { return { content_id: i.item_id, content_name: i.item_name, price: i.price, quantity: i.quantity }; }) });
    } catch (e) { /* bỏ qua lỗi đo lường */ }
  }

  /* ---------------------------------------------------------------
   * Thông báo nhanh (toast)
   * ------------------------------------------------------------- */
  var toastRegion = $('[data-toast-region]');
  function toast(message, opts) {
    if (!toastRegion) return;
    opts = opts || {};
    var el = document.createElement('div');
    el.className = 'toast' + (opts.type === 'error' ? ' toast--error' : '');
    el.innerHTML = icon(opts.type === 'error' ? 'alert' : 'check') +
      '<p>' + esc(message) + (opts.link ? ' <a href="' + esc(opts.link.href) + '">' + esc(opts.link.label) + '</a>' : '') + '</p>' +
      '<button type="button" aria-label="Đóng thông báo">' + icon('close') + '</button>';
    toastRegion.appendChild(el);
    var timer;
    function dismiss() {
      clearTimeout(timer);
      el.classList.add('is-leaving');
      setTimeout(function () { el.remove(); }, 220);
    }
    el.querySelector('button').addEventListener('click', dismiss);
    el.addEventListener('mouseenter', function () { clearTimeout(timer); });
    el.addEventListener('mouseleave', function () { timer = setTimeout(dismiss, 2500); });
    timer = setTimeout(dismiss, opts.duration || 4500);
  }
  if (toastRegion && toastRegion.dataset.flash) {
    toast(toastRegion.dataset.flash, { type: toastRegion.dataset.flashType });
  }

  /* ---------------------------------------------------------------
   * Giỏ hàng (lưu trên trình duyệt)
   * ------------------------------------------------------------- */
  var CART_KEY = 'vnp_cart_v1';
  var memoryCart = [];
  var Cart = {
    get: function () {
      try {
        var data = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
        return Array.isArray(data) ? data.filter(function (i) { return i && i.slug && i.qty > 0; }) : [];
      } catch (e) { return memoryCart.slice(); }
    },
    save: function (items) {
      memoryCart = items;
      try { localStorage.setItem(CART_KEY, JSON.stringify(items)); } catch (e) { /* chế độ riêng tư */ }
      document.dispatchEvent(new CustomEvent('cart:change'));
    },
    add: function (slug, qty, unique) {
      var items = Cart.get();
      var found = items.filter(function (i) { return i.slug === slug; })[0];
      if (found && unique) return 'exists';
      if (found) found.qty = Math.min(20, found.qty + qty);
      else items.push({ slug: slug, qty: unique ? 1 : Math.min(20, qty) });
      Cart.save(items);
      return 'added';
    },
    set: function (slug, qty) {
      Cart.save(Cart.get().map(function (i) {
        if (i.slug === slug) i.qty = Math.max(1, Math.min(20, qty));
        return i;
      }));
    },
    remove: function (slug) {
      Cart.save(Cart.get().filter(function (i) { return i.slug !== slug; }));
    },
    clear: function () { Cart.save([]); },
    count: function () { return Cart.get().reduce(function (s, i) { return s + i.qty; }, 0); }
  };

  function updateBadges(animate) {
    var n = Cart.count();
    $$('[data-cart-count]').forEach(function (b) {
      b.textContent = n > 99 ? '99+' : n;
      b.hidden = n === 0;
      if (animate && !reduceMotion) {
        b.classList.remove('bump');
        void b.offsetWidth;
        b.classList.add('bump');
      }
    });
    $$('.cart-link .sr-only').forEach(function (s) { s.textContent = 'Giỏ hàng (' + n + ' sản phẩm)'; });
  }
  updateBadges(false);
  document.addEventListener('cart:change', function () { updateBadges(true); });
  window.addEventListener('storage', function (e) { if (e.key === CART_KEY) updateBadges(false); });

  var cartUrl = body.dataset.cartUrl || 'gio-hang';
  function addedToast(name) {
    toast('Đã thêm “' + name + '” vào giỏ hàng.', { link: { href: cartUrl, label: 'Xem giỏ hàng' } });
  }

  function addToCart(slug, qty, unique, name, goCart) {
    var res = Cart.add(slug, qty, unique);
    if (res === 'added') track('add_to_cart', { id: slug, name: name, qty: qty });
    if (goCart) { window.location.href = cartUrl; return; }
    if (res === 'exists') toast('“' + name + '” là món độc bản và đã có trong giỏ hàng.', { link: { href: cartUrl, label: 'Xem giỏ hàng' } });
    else addedToast(name);
  }
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;
    e.preventDefault();
    addToCart(btn.dataset.addToCart, 1, btn.hasAttribute('data-unique'), btn.dataset.name || 'Sản phẩm', btn.hasAttribute('data-go-cart'));
  });

  // Ô số lượng
  function clampQty(input) {
    var min = Number(input.min) || 1, max = Number(input.max) || 20;
    var v = parseInt(input.value, 10);
    if (isNaN(v)) v = min;
    v = Math.max(min, Math.min(max, v));
    input.value = v;
    var wrap = input.closest('.qty');
    if (wrap) {
      var dec = wrap.querySelector('[data-qty-step="-1"]'), inc = wrap.querySelector('[data-qty-step="1"]');
      if (dec) dec.disabled = v <= min;
      if (inc) inc.disabled = v >= max;
    }
    return v;
  }
  document.addEventListener('click', function (e) {
    var step = e.target.closest('[data-qty-step]');
    if (!step) return;
    var input = step.closest('.qty').querySelector('input');
    input.value = (parseInt(input.value, 10) || 1) + Number(step.dataset.qtyStep);
    clampQty(input);
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
  $$('.qty input').forEach(clampQty);

  // Form mua trên trang chi tiết
  $$('[data-buy-form]').forEach(function (form) {
    var action = 'add';
    $$('button[type="submit"]', form).forEach(function (b) {
      b.addEventListener('click', function () { action = b.dataset.action; });
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var qtyInput = form.querySelector('input[name="qty"]');
      var qty = qtyInput.type === 'hidden' ? 1 : clampQty(qtyInput);
      addToCart(form.dataset.buyForm, qty, form.hasAttribute('data-unique'), form.dataset.name, action === 'buy');
    });
  });

  /* ---------------------------------------------------------------
   * Đầu trang dính, nút lên đầu trang
   * ------------------------------------------------------------- */
  var header = $('.site-header');
  var toTop = $('[data-back-to-top]');
  var ticking = false;
  function onScroll() {
    ticking = false;
    var y = window.pageYOffset;
    if (header) header.classList.toggle('is-stuck', y > 40);
    if (toTop) toTop.hidden = y < 700;
  }
  window.addEventListener('scroll', function () {
    if (!ticking) { ticking = true; requestAnimationFrame(onScroll); }
  }, { passive: true });
  onScroll();
  if (toTop) toTop.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    var main = $('#main');
    if (main) main.focus({ preventScroll: true });
  });

  /* ---------------------------------------------------------------
   * Ô tìm kiếm thả xuống (bấm biểu tượng kính lúp)
   * ------------------------------------------------------------- */
  var searchToggle = $('[data-search-toggle]');
  var searchPanel = $('#header-search');
  if (searchToggle && searchPanel) {
    var searchInput = $('input[type="search"]', searchPanel);
    var setSearch = function (open, returnFocus) {
      searchPanel.hidden = !open;
      searchToggle.setAttribute('aria-expanded', String(open));
      if (open) { searchInput.focus(); searchInput.select(); }
      else if (returnFocus) searchToggle.focus();
    };
    searchToggle.addEventListener('click', function () { setSearch(searchPanel.hidden); });
    $$('[data-search-close]', searchPanel).forEach(function (b) { b.addEventListener('click', function () { setSearch(false, true); }); });
    searchPanel.addEventListener('keydown', function (e) { if (e.key === 'Escape') setSearch(false, true); });
    document.addEventListener('click', function (e) {
      if (!searchPanel.hidden && !searchPanel.contains(e.target) && !searchToggle.contains(e.target)) setSearch(false);
    });
  }

  /* ---------------------------------------------------------------
   * Menu xổ xuống (desktop)
   * ------------------------------------------------------------- */
  var subToggles = $$('.sub-toggle');
  function closeSub(li, focusToggle) {
    li.classList.remove('is-open');
    var t = li.querySelector('.sub-toggle');
    t.setAttribute('aria-expanded', 'false');
    if (focusToggle) t.focus();
  }
  subToggles.forEach(function (t) {
    var li = t.closest('.has-sub');
    t.addEventListener('click', function () {
      var open = !li.classList.contains('is-open');
      $$('.has-sub.is-open').forEach(function (o) { if (o !== li) closeSub(o); });
      li.classList.toggle('is-open', open);
      t.setAttribute('aria-expanded', String(open));
      if (open) { var first = li.querySelector('.submenu a'); if (first) first.focus(); }
    });
    li.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && li.classList.contains('is-open')) closeSub(li, true);
      if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && li.classList.contains('is-open')) {
        var links = $$('.submenu a', li);
        var idx = links.indexOf(document.activeElement);
        if (idx === -1 && e.key === 'ArrowUp') return;
        e.preventDefault();
        var next = e.key === 'ArrowDown' ? Math.min(links.length - 1, idx + 1) : Math.max(0, idx - 1);
        links[next].focus();
      }
    });
    li.addEventListener('focusout', function (e) {
      if (!li.contains(e.relatedTarget)) closeSub(li);
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.has-sub')) $$('.has-sub.is-open').forEach(function (o) { closeSub(o); });
  });

  /* ---------------------------------------------------------------
   * Bảng trượt (menu di động, bộ lọc) – có bẫy focus & phím Esc
   * ------------------------------------------------------------- */
  function focusables(root) {
    return $$('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select, textarea, summary, [tabindex]:not([tabindex="-1"])', root)
      .filter(function (el) { return el.offsetParent !== null; });
  }
  function makePanel(opts) {
    var lastOpener = null, open = false;
    function onKey(e) {
      if (e.key === 'Escape') { close(); return; }
      if (e.key !== 'Tab') return;
      var f = focusables(opts.trap);
      if (!f.length) return;
      if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    }
    function show(opener) {
      if (open) return;
      open = true;
      lastOpener = opener || document.activeElement;
      opts.onShow();
      body.style.overflow = 'hidden';
      $$(opts.openers).forEach(function (o) { o.setAttribute('aria-expanded', 'true'); });
      document.addEventListener('keydown', onKey);
      setTimeout(function () { var f = focusables(opts.trap); if (f.length) f[0].focus(); }, 60);
    }
    function close() {
      if (!open) return;
      open = false;
      opts.onHide();
      body.style.overflow = '';
      $$(opts.openers).forEach(function (o) { o.setAttribute('aria-expanded', 'false'); });
      document.removeEventListener('keydown', onKey);
      if (lastOpener && lastOpener.focus) lastOpener.focus();
    }
    $$(opts.openers).forEach(function (o) { o.addEventListener('click', function () { show(o); }); });
    $$(opts.closers).forEach(function (c) { c.addEventListener('click', close); });
    return { show: show, close: close };
  }

  var drawer = $('#mobile-menu');
  if (drawer) {
    var hideTimer;
    var drawerPanel = makePanel({
      trap: $('.drawer__panel', drawer),
      openers: '[data-drawer-open]',
      closers: '#mobile-menu [data-drawer-close]',
      onShow: function () {
        clearTimeout(hideTimer);
        drawer.hidden = false;
        void drawer.offsetWidth;
        drawer.classList.add('is-open');
      },
      onHide: function () {
        drawer.classList.remove('is-open');
        hideTimer = setTimeout(function () { drawer.hidden = true; }, reduceMotion ? 0 : 320);
      }
    });
    $$('a', drawer).forEach(function (a) {
      a.addEventListener('click', function () { if (a.getAttribute('href').indexOf('#') !== -1) drawerPanel.close(); });
    });
    window.addEventListener('resize', function () { if (window.innerWidth > 991) drawerPanel.close(); });
  }

  var filters = $('#shop-filters');
  if (filters) {
    var backdrop = $('.shop-backdrop');
    var mq = window.matchMedia('(max-width: 991px)');
    var filterPanel = makePanel({
      trap: filters,
      openers: '[data-filter-open]',
      closers: '[data-filter-close]',
      onShow: function () { filters.classList.add('is-open'); if (backdrop) backdrop.hidden = false; filters.setAttribute('role', 'dialog'); filters.setAttribute('aria-modal', 'true'); },
      onHide: function () { filters.classList.remove('is-open'); if (backdrop) backdrop.hidden = true; filters.removeAttribute('role'); filters.removeAttribute('aria-modal'); }
    });
    (mq.addEventListener ? mq.addEventListener.bind(mq, 'change') : mq.addListener.bind(mq))(function (e) { if (!e.matches) filterPanel.close(); });
  }

  // Tự gửi form sắp xếp
  $$('[data-autosubmit] select').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });

  /* ---------------------------------------------------------------
   * Banner trượt
   * ------------------------------------------------------------- */
  $$('[data-slider]').forEach(function (root) {
    var slides = $$('.hero__slide', root);
    if (slides.length < 2) return;
    var dots = $$('[data-slider-dot]', root);
    var toggle = $('[data-slider-toggle]', root);
    var index = 0, timer = null, userPaused = reduceMotion, hovering = false;
    var DELAY = 4000; // thời gian mỗi slide (mili giây): 4000 = 4 giây

    // Slide bị ẩn (VD banner chưa có ảnh dọc trên điện thoại) → nhảy qua
    function shown(n) { return getComputedStyle(slides[n]).display !== 'none'; }
    function go(i, dir) {
      index = (i + slides.length) % slides.length;
      for (var k = 0; k < slides.length && !shown(index); k++) index = (index + (dir || 1) + slides.length) % slides.length;
      slides.forEach(function (s, n) {
        var on = n === index;
        s.classList.toggle('is-active', on);
        if (on) s.removeAttribute('aria-hidden'); else s.setAttribute('aria-hidden', 'true');
        $$('a, button', s).forEach(function (el) { if (on) el.removeAttribute('tabindex'); else el.setAttribute('tabindex', '-1'); });
      });
      dots.forEach(function (d, n) { if (n === index) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current'); });
    }
    function play() {
      stop();
      if (!userPaused && !hovering && !document.hidden) timer = setInterval(function () { go(index + 1); }, DELAY);
    }
    function stop() { clearInterval(timer); timer = null; }
    function syncToggle() { if (toggle) toggle.setAttribute('aria-pressed', String(userPaused)); }

    $('[data-slider-prev]', root).addEventListener('click', function () { go(index - 1, -1); play(); });
    $('[data-slider-next]', root).addEventListener('click', function () { go(index + 1); play(); });
    dots.forEach(function (d) { d.addEventListener('click', function () { go(Number(d.dataset.sliderDot)); play(); }); });
    if (toggle) toggle.addEventListener('click', function () { userPaused = !userPaused; syncToggle(); play(); });
    root.addEventListener('mouseenter', function () { hovering = true; stop(); });
    root.addEventListener('mouseleave', function () { hovering = false; play(); });
    root.addEventListener('focusin', function () { hovering = true; stop(); });
    root.addEventListener('focusout', function (e) { if (!root.contains(e.relatedTarget)) { hovering = false; play(); } });
    document.addEventListener('visibilitychange', play);

    var startX = null;
    root.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    root.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 50) { go(index + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1); play(); }
      startX = null;
    });

    // Xoay máy / đổi cỡ màn hình làm slide đang xem bị ẩn → chuyển sang slide kế tiếp
    window.addEventListener('resize', function () { if (!shown(index)) go(index + 1); });

    syncToggle();
    go(0);
    play();
  });

  /* ---------------------------------------------------------------
   * Tra cứu mệnh theo năm sinh
   * ------------------------------------------------------------- */
  var menhForm = $('[data-menh-form]');
  var menhData = readJSON('menh-data');
  if (menhForm && menhData) {
    var input = $('input', menhForm);
    var error = $('#nam-sinh-error');
    var result = $('[data-menh-result]');
    function setError(msg) {
      error.textContent = msg || '';
      error.hidden = !msg;
      if (msg) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
    }
    input.addEventListener('input', function () { setError(''); });
    menhForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var year = parseInt(input.value, 10);
      if (!input.value.trim()) { setError('Vui lòng nhập năm sinh.'); input.focus(); return; }
      if (isNaN(year) || year < 1900 || year > 2100) { setError('Năm sinh cần trong khoảng 1900 – 2100.'); input.focus(); return; }
      setError('');
      var t = menhData.tables;
      var i = ((year - 4) % 60 + 60) % 60;
      var na = t.napam[Math.floor(i / 2)];
      var info = menhData.info[na[1]];
      var shop = menhData.shop + (menhData.shop.indexOf('?') === -1 ? '?' : '&') + 'menh=' + na[1];
      result.innerHTML =
        '<p class="menh-result__year">Năm ' + year + ' · ' + esc(t.can[i % 10] + ' ' + t.chi[i % 12]) + '</p>' +
        '<div class="menh-result__name" style="--el:' + esc(info.color) + '"><span class="menh-result__badge" aria-hidden="true"></span>' +
        '<div>Mệnh ' + esc(info.name) + '<small>' + esc(na[0]) + '</small></div></div>' +
        '<dl><div><dt>Màu tương hợp</dt><dd>' + esc(info.good) + '</dd></div>' +
        '<div><dt>Màu nên tránh</dt><dd>' + esc(info.avoid) + '</dd></div>' +
        '<div><dt>Gợi ý ngọc</dt><dd>' + esc(info.jade) + '</dd></div></dl>' +
        '<a class="btn btn--gold" href="' + esc(shop) + '">Xem ngọc hợp mệnh ' + esc(info.name) + icon('arrow') + '</a>';
    });
  }

  /* ---------------------------------------------------------------
   * Tab & thư viện ảnh (trang chi tiết)
   * ------------------------------------------------------------- */
  $$('[data-tabs]').forEach(function (root) {
    var tabs = $$('[role="tab"]', root);
    function select(tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
      });
      if (focus) tab.focus();
    }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { select(t); });
      t.addEventListener('keydown', function (e) {
        var n = null;
        if (e.key === 'ArrowRight') n = tabs[(i + 1) % tabs.length];
        if (e.key === 'ArrowLeft') n = tabs[(i - 1 + tabs.length) % tabs.length];
        if (e.key === 'Home') n = tabs[0];
        if (e.key === 'End') n = tabs[tabs.length - 1];
        if (n) { e.preventDefault(); select(n, true); }
      });
    });
  });

  /* Hộp thoại (dialog) dùng chung */
  function openDialog(dlg) {
    if (!dlg) return;
    if (typeof dlg.showModal === 'function') { if (!dlg.open) dlg.showModal(); } else dlg.setAttribute('open', '');
    body.classList.add('has-dialog');
  }
  function closeDialog(dlg) {
    if (!dlg) return;
    if (typeof dlg.close === 'function') dlg.close(); else { dlg.removeAttribute('open'); body.classList.remove('has-dialog'); }
  }
  $$('dialog').forEach(function (dlg) {
    dlg.addEventListener('close', function () { body.classList.remove('has-dialog'); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) closeDialog(dlg); });
  });
  document.addEventListener('click', function (e) {
    var op = e.target.closest('[data-dialog-open]');
    if (op) { e.preventDefault(); openDialog(document.getElementById(op.dataset.dialogOpen)); return; }
    var cl = e.target.closest('[data-dialog-close]');
    if (cl) closeDialog(cl.closest('dialog'));
  });

  var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  /* ---------------------------------------------------------------
   * Soi ảnh chi tiết (ảnh sản phẩm, ảnh khách hàng, giấy kiểm định)
   * Điện thoại: chạm 2 lần hoặc dùng 2 ngón tay để phóng to, kéo để soi vân, vuốt ngang để đổi ảnh.
   * Máy tính: bấm để phóng to tại điểm bấm, cuộn chuột để phóng to / thu nhỏ, kéo để di chuyển.
   * ------------------------------------------------------------- */
  var zoomer = null;
  function buildZoomer() {
    var d = document.createElement('dialog');
    d.className = 'zoomer';
    d.setAttribute('aria-label', 'Soi ảnh chi tiết');
    d.innerHTML =
      '<div class="zoomer__stage" tabindex="-1" autofocus><div class="zoomer__wrap"><img alt="" draggable="false"></div></div>' +
      '<div class="zoomer__bar"><span class="zoomer__count" aria-live="polite"></span>' +
        '<button type="button" class="zoomer__btn" data-z="toggle" aria-label="Phóng to / thu nhỏ">' + icon('zoom') + '</button>' +
        '<button type="button" class="zoomer__btn" data-z="close" aria-label="Đóng">' + icon('close') + '</button></div>' +
      '<button type="button" class="zoomer__nav zoomer__nav--prev" data-z="prev" aria-label="Ảnh trước">' + icon('left') + '</button>' +
      '<button type="button" class="zoomer__nav zoomer__nav--next" data-z="next" aria-label="Ảnh sau">' + icon('right') + '</button>' +
      '<p class="zoomer__hint">' + (finePointer
        ? 'Bấm vào ảnh để phóng to · cuộn chuột để phóng to / thu nhỏ · kéo để di chuyển'
        : 'Chạm 2 lần hoặc dùng 2 ngón tay để phóng to · kéo để soi vân · vuốt ngang để xem ảnh khác') + '</p>';
    document.body.appendChild(d);

    var stage = $('.zoomer__stage', d), wrap = $('.zoomer__wrap', d), img = $('img', wrap);
    var count = $('.zoomer__count', d), hint = $('.zoomer__hint', d);
    var list = [], idx = 0, s = 1, x = 0, y = 0, MAX = 3, pts = {}, g = null, lastTap = 0;

    function bounds() {
      var r = stage.getBoundingClientRect(), nw = img.naturalWidth || r.width, nh = img.naturalHeight || r.height;
      var k = Math.min(r.width / nw, r.height / nh);
      return { r: r, w: nw * k, h: nh * k };
    }
    function clamp() {
      var b = bounds(), mx = Math.max(0, (b.w * s - b.r.width) / 2), my = Math.max(0, (b.h * s - b.r.height) / 2);
      x = Math.max(-mx, Math.min(mx, x));
      y = Math.max(-my, Math.min(my, y));
    }
    function apply(anim) {
      wrap.classList.toggle('is-anim', !!anim);
      wrap.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0) scale(' + s + ')';
      d.classList.toggle('is-zoomed', s > 1.01);
    }
    function zoomAt(ns, cx, cy, anim) {           // cx, cy: điểm trên màn hình giữ nguyên khi phóng
      var r = stage.getBoundingClientRect(), px = cx - r.left - r.width / 2, py = cy - r.top - r.height / 2;
      ns = Math.max(1, Math.min(MAX, ns));
      x = px - (px - x) * ns / s;
      y = py - (py - y) * ns / s;
      s = ns;
      if (s === 1) { x = 0; y = 0; }
      clamp(); apply(anim);
    }
    function reset(anim) { s = 1; x = 0; y = 0; apply(anim); }
    function show(i) {
      idx = (i + list.length) % list.length;
      reset(false);
      img.src = list[idx].src;
      img.alt = list[idx].alt || '';
      count.textContent = list.length > 1 ? (idx + 1) + ' / ' + list.length : '';
      d.classList.toggle('is-single', list.length < 2);
    }

    stage.addEventListener('pointerdown', function (e) {
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      try { stage.setPointerCapture(e.pointerId); } catch (err) { /* bỏ qua */ }
      pts[e.pointerId] = { x: e.clientX, y: e.clientY };
      var ids = Object.keys(pts);
      if (ids.length === 1) {
        g = { type: 'one', sx: e.clientX, sy: e.clientY, ox: x, oy: y, moved: false, mouse: e.pointerType === 'mouse' };
      } else if (ids.length === 2) {
        var a = pts[ids[0]], b = pts[ids[1]];
        g = { type: 'pinch', d0: Math.hypot(a.x - b.x, a.y - b.y) || 1, s0: s, mx: (a.x + b.x) / 2, my: (a.y + b.y) / 2, ox: x, oy: y };
      }
      hint.classList.add('is-hidden');
    });
    stage.addEventListener('pointermove', function (e) {
      if (!pts[e.pointerId] || !g) return;
      pts[e.pointerId] = { x: e.clientX, y: e.clientY };
      var ids = Object.keys(pts);
      if (g.type === 'pinch' && ids.length >= 2) {
        var a = pts[ids[0]], b = pts[ids[1]], r = stage.getBoundingClientRect();
        var ns = Math.max(1, Math.min(MAX, g.s0 * Math.hypot(a.x - b.x, a.y - b.y) / g.d0));
        var px = g.mx - r.left - r.width / 2, py = g.my - r.top - r.height / 2;
        x = px - (px - g.ox) * ns / g.s0 + ((a.x + b.x) / 2 - g.mx);
        y = py - (py - g.oy) * ns / g.s0 + ((a.y + b.y) / 2 - g.my);
        s = ns;
        clamp(); apply(false);
      } else if (g.type === 'one') {
        var dx = e.clientX - g.sx, dy = e.clientY - g.sy;
        if (Math.abs(dx) + Math.abs(dy) > 6) g.moved = true;
        if (s > 1.01) { x = g.ox + dx; y = g.oy + dy; clamp(); apply(false); }
        else if (list.length > 1 && !g.mouse) { wrap.classList.remove('is-anim'); wrap.style.transform = 'translate3d(' + dx + 'px,0,0)'; }
      }
    });
    function end(e) {
      if (!pts[e.pointerId]) return;
      delete pts[e.pointerId];
      if (!g) return;
      if (g.type === 'pinch') {
        var left = Object.keys(pts);
        if (left.length) g = { type: 'one', sx: pts[left[0]].x, sy: pts[left[0]].y, ox: x, oy: y, moved: true };
        else { if (s < 1.05) reset(true); g = null; }
        return;
      }
      var dx = e.clientX - g.sx, dy = e.clientY - g.sy;
      if (s <= 1.01 && g.moved && !g.mouse && list.length > 1) {
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(idx + (dx < 0 ? 1 : -1)); else reset(true);
      } else if (!g.moved) {
        var now = Date.now();
        if (g.mouse || now - lastTap < 320) { zoomAt(s > 1.01 ? 1 : 2.5, e.clientX, e.clientY, true); lastTap = 0; }
        else lastTap = now;
      }
      g = null;
    }
    stage.addEventListener('pointerup', end);
    stage.addEventListener('pointercancel', end);
    stage.addEventListener('wheel', function (e) { e.preventDefault(); zoomAt(s * (e.deltaY < 0 ? 1.2 : 1 / 1.2), e.clientX, e.clientY, false); }, { passive: false });
    d.addEventListener('click', function (e) {
      var b = e.target.closest('[data-z]');
      if (!b) return;
      var r = stage.getBoundingClientRect();
      if (b.dataset.z === 'close') closeDialog(d);
      else if (b.dataset.z === 'prev') show(idx - 1);
      else if (b.dataset.z === 'next') show(idx + 1);
      else zoomAt(s > 1.01 ? 1 : 2.5, r.left + r.width / 2, r.top + r.height / 2, true);
    });
    d.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') show(idx + 1);
      else if (e.key === 'ArrowLeft') show(idx - 1);
    });
    d.addEventListener('close', function () {
      reset(false);
      if (!$('dialog[open]')) body.classList.remove('has-dialog');
    });
    window.addEventListener('resize', function () { if (d.open) { clamp(); apply(false); } });
    return {
      open: function (items, i) {
        list = items;
        show(i || 0);
        hint.classList.remove('is-hidden');
        openDialog(d);
      }
    };
  }
  function openZoom(items, i) {
    if (!items.length) return;
    zoomer = zoomer || buildZoomer();
    zoomer.open(items, i);
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-lightbox-src]');
    if (b) { e.preventDefault(); openZoom([{ src: b.dataset.lightboxSrc, alt: b.getAttribute('aria-label') || '' }], 0); }
  });
  function initGallery(g) {
    var main = $('[data-gallery-main]', g);
    var stage = $('.gallery__stage', g);
    var videoBox = $('[data-gallery-video]', g);
    if (!main) return;
    function showStage() {
      if (videoBox && !videoBox.hidden) {
        var v = $('video', videoBox); if (v) v.pause();
        videoBox.innerHTML = ''; videoBox.hidden = true;
      }
      if (stage) stage.hidden = false;
    }
    $$('[data-gallery-thumb]', g).forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('.gallery__thumb', g).forEach(function (b) { b.removeAttribute('aria-current'); });
        btn.setAttribute('aria-current', 'true');
        showStage();
        if (main.getAttribute('src') === btn.dataset.galleryThumb) return;
        main.classList.add('is-swapping');
        setTimeout(function () { main.src = btn.dataset.galleryThumb; main.classList.remove('is-swapping'); }, reduceMotion ? 0 : 150);
      });
    });
    $$('[data-gallery-video-thumb]', g).forEach(function (btn) {
      btn.addEventListener('click', function () {
        $$('.gallery__thumb', g).forEach(function (b) { b.removeAttribute('aria-current'); });
        btn.setAttribute('aria-current', 'true');
        if (!videoBox) return;
        videoBox.innerHTML = btn.dataset.type === 'youtube'
          ? '<iframe src="' + esc(btn.dataset.src) + '&autoplay=1&mute=1" title="Video sản phẩm" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>'
          : '<video src="' + esc(btn.dataset.src) + '" controls autoplay muted loop playsinline preload="metadata"></video>';
        if (stage) stage.hidden = true;
        videoBox.hidden = false;
      });
    });
    // Phóng to khi rê chuột (máy tính) · bấm / chạm để mở trình soi ảnh (tất cả ảnh của sản phẩm)
    if (!stage) return;
    if (finePointer && stage.hasAttribute('data-zoom')) {
      stage.addEventListener('mousemove', function (e) {
        var r = stage.getBoundingClientRect();
        main.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%';
        stage.classList.add('is-zooming');
      });
      stage.addEventListener('mouseleave', function () { stage.classList.remove('is-zooming'); });
    }
    function thumbs() { return $$('[data-gallery-thumb]', g); }
    function current() { var t = thumbs(); for (var i = 0; i < t.length; i++) if (t[i].getAttribute('aria-current') === 'true') return i; return 0; }
    stage.addEventListener('click', function () {
      var t = thumbs();
      openZoom(t.length ? t.map(function (b) { return { src: b.dataset.galleryThumb, alt: main.alt }; }) : [{ src: main.currentSrc || main.src, alt: main.alt }], current());
    });
    // Điện thoại: vuốt ngang trên ảnh chính để đổi ảnh
    var sx = 0, sy = 0;
    stage.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
      var t = thumbs(), c = e.changedTouches[0], dx = c.clientX - sx, dy = c.clientY - sy;
      if (t.length > 1 && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) t[(current() + (dx < 0 ? 1 : -1) + t.length) % t.length].click();
    }, { passive: true });
  }
  $$('[data-gallery]').forEach(initGallery);

  // Thẻ sản phẩm trên điện thoại: vuốt ngang ảnh để xem ảnh thứ 2 – chấm bên dưới cho biết đang ở ảnh nào
  document.addEventListener('scroll', function (e) {
    var sc = e.target;
    if (!sc.classList || !sc.classList.contains('product-card__img')) return;
    var i = Math.round(sc.scrollLeft / (sc.clientWidth || 1));
    $$('.card-dots i', sc.parentNode).forEach(function (dot, k) { dot.classList.toggle('is-on', k === i); });
  }, { capture: true, passive: true });

  /* ---------------------------------------------------------------
   * Kiểm tra biểu mẫu
   * ------------------------------------------------------------- */
  function fieldError(field, msg) {
    var err = field.getAttribute('aria-describedby') && document.getElementById(field.getAttribute('aria-describedby').split(' ')[0]);
    if (msg) field.setAttribute('aria-invalid', 'true'); else field.removeAttribute('aria-invalid');
    if (err) { err.textContent = msg || ''; err.hidden = !msg; }
  }
  function validateField(field) {
    if (field.type === 'checkbox') return field.required && !field.checked ? (field.dataset.msg || 'Vui lòng đánh dấu ô này.') : '';
    var v = field.value.trim();
    if (field.required && !v) return 'Vui lòng nhập ' + (field.dataset.label || (field.labels && field.labels[0] ? field.labels[0].childNodes[0].textContent.trim().toLowerCase() : 'thông tin này')) + '.';
    if (v && field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return 'Email chưa đúng định dạng.';
    if (v && field.dataset.type === 'phone' && !/^(\+?84|0)\d{9,10}$/.test(v.replace(/[\s.\-()]/g, ''))) return 'Số điện thoại chưa đúng (ví dụ: 0912 345 678).';
    if (field.name === 'dia-chi' && v && v.length < 8) return 'Vui lòng nhập địa chỉ đầy đủ hơn.';
    return '';
  }
  $$('form[data-validate]').forEach(function (form) {
    var fields = $$('input:not([type="hidden"]):not([type="radio"]):not([name="website"]), textarea', form);
    fields.forEach(function (f) {
      f.addEventListener(f.type === 'checkbox' ? 'change' : 'input', function () { if (f.getAttribute('aria-invalid')) fieldError(f, validateField(f)); });
      f.addEventListener('blur', function () { if (f.type !== 'checkbox' && f.value.trim()) fieldError(f, validateField(f)); });
    });
    form.addEventListener('submit', function (e) {
      var firstBad = null;
      fields.forEach(function (f) {
        var msg = validateField(f);
        fieldError(f, msg);
        if (msg && !firstBad) firstBad = f;
      });
      if (form.hasAttribute('data-checkout-form')) {
        var items = Cart.get();
        var formErr = $('[data-form-error]', form) || $('[data-form-error]');
        if (!items.length) {
          e.preventDefault();
          if (formErr) { formErr.textContent = 'Giỏ hàng đang trống.'; formErr.hidden = false; }
          return;
        }
        $('[data-cart-input]', form).value = JSON.stringify(items);
      }
      // Đơn hàng: bắt buộc chọn hình thức thanh toán (không chọn sẵn để khách không đặt nhầm COD)
      var missing = fields.filter(function (f) { return f.getAttribute('aria-invalid'); }).map(function (f) {
        return f.dataset.label || (f.labels && f.labels[0] ? f.labels[0].childNodes[0].textContent.trim() : '');
      });
      var payBox = $('[data-pay-methods]', form);
      if (payBox) {
        var payErr = $('#pay-error', form), noPay = !$('input[name="thanh-toan"]:checked', form);
        if (payErr) { payErr.textContent = noPay ? 'Vui lòng chọn hình thức thanh toán.' : ''; payErr.hidden = !noPay; }
        if (noPay) { missing.push('hình thức thanh toán'); if (!firstBad) firstBad = $('input[name="thanh-toan"]', form); }
      }
      if (firstBad) {
        e.preventDefault();
        var fold = firstBad.closest('details');
        if (fold) fold.open = true;
        // Đưa ô còn thiếu lên nửa trên màn hình, ngay dưới đầu trang (thông báo ở nửa dưới không che) và báo rõ còn thiếu gì
        var target = firstBad.closest('fieldset') || firstBad.closest('.field') || firstBad;
        var head = $('.site-header');
        var y = target.getBoundingClientRect().top + window.pageYOffset - (head ? head.getBoundingClientRect().height : 0) - 16;
        window.scrollTo({ top: Math.max(0, y), behavior: reduceMotion ? 'auto' : 'smooth' });
        try { firstBad.focus({ preventScroll: true }); } catch (err) { firstBad.focus(); }
        if (form.hasAttribute('data-checkout-form') && missing.length) {
          $$('.toast--error').forEach(function (t) { t.remove(); });
          toast('Vui lòng ' + (missing.length === 1 && missing[0] === 'hình thức thanh toán' ? 'chọn ' : 'bổ sung: ') + missing.join(', ').toLowerCase() + '.', { type: 'error', duration: 4000 });
        }
        return;
      }
      // Nút gửi nằm trong form hoặc gắn với form bằng thuộc tính form="…" (ví dụ nút Đặt hàng ở cột tóm tắt)
      var btns = $$('button[type="submit"]', form).concat(form.id ? $$('button[type="submit"][form="' + form.id + '"]') : []);
      setTimeout(function () { btns.forEach(function (b) { b.classList.add('is-loading'); b.disabled = true; }); }, 0);
    });
  });
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) $$('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.disabled = false; });
  });

  /* ---------------------------------------------------------------
   * Trang giỏ hàng
   * ------------------------------------------------------------- */
  var cartPage = $('[data-cart-page]');
  if (cartPage) {
    var catalog = readJSON('catalog-data') || {};
    var ship = readJSON('shipping-data') || { fee: 0, freeFrom: 0 };
    var list = $('[data-cart-list]');
    var empty = $('[data-cart-empty]');
    var checkoutEls = $$('[data-cart-checkout]');
    var cont = $('[data-cart-continue]');
    var bar = $('[data-checkout-bar]');

    var renderCart = function () {
      // Bỏ sản phẩm không còn kinh doanh / hết hàng
      var items = Cart.get();
      var removed = items.filter(function (i) { return catalog[i.slug] && !catalog[i.slug].buy; });
      var valid = items.filter(function (i) { return catalog[i.slug] && catalog[i.slug].buy; })
        .map(function (i) { if (catalog[i.slug].unique) i.qty = 1; return i; });
      if (valid.length !== items.length || removed.length) { memoryCart = valid; try { localStorage.setItem(CART_KEY, JSON.stringify(valid)); } catch (e) {} updateBadges(false); }
      removed.forEach(function (i) {
        toast('“' + catalog[i.slug].name + '” vừa có chủ hoặc đang được giữ nên đã được bỏ khỏi giỏ.', { type: 'error', duration: 7000 });
      });

      $('[data-cart-loading]').hidden = true;
      empty.hidden = valid.length > 0;
      checkoutEls.forEach(function (el) { el.hidden = valid.length === 0; });
      cont.hidden = valid.length === 0;
      syncBar();

      var subtotal = 0, hasContact = false;
      list.innerHTML = valid.map(function (i) {
        var p = catalog[i.slug];
        var line = p.price * i.qty;
        subtotal += line;
        if (p.price <= 0) hasContact = true;
        var id = 'q-' + i.slug;
        return '<li class="cart-item" data-slug="' + esc(i.slug) + '">' +
          '<a class="cart-item__img" href="' + esc(p.url) + '" tabindex="-1" aria-hidden="true"><img src="' + esc(p.image) + '" alt="" width="92" height="92"></a>' +
          '<div class="cart-item__info"><a class="cart-item__name" href="' + esc(p.url) + '">' + esc(p.name) + '</a>' +
          '<p class="cart-item__unit">' + esc(p.meta) + (i.qty > 1 && p.price > 0 ? '<span class="cart-item__each">' + formatPrice(p.price) + ' / chiếc</span>' : '') + '</p></div>' +
          (p.unique
            ? '<p class="qty qty--unique">Độc bản<span class="qty__one"> · 1 chiếc</span></p>'
            : '<div class="qty qty--sm"><label class="sr-only" for="' + esc(id) + '">Số lượng ' + esc(p.name) + '</label>' +
              '<button type="button" class="qty__btn" data-qty-step="-1"' + (i.qty <= 1 ? ' disabled' : '') + '>' + icon('minus') + '<span class="sr-only">Giảm</span></button>' +
              '<input id="' + esc(id) + '" type="number" min="1" max="20" value="' + i.qty + '" inputmode="numeric" data-cart-qty>' +
              '<button type="button" class="qty__btn" data-qty-step="1"' + (i.qty >= 20 ? ' disabled' : '') + '>' + icon('plus') + '<span class="sr-only">Tăng</span></button></div>') +
          '<p class="cart-item__total">' + (p.price > 0 ? formatPrice(line) : 'Liên hệ') + '</p>' +
          '<button type="button" class="icon-btn cart-item__remove" data-cart-remove>' + icon('trash') + '<span class="sr-only">Xóa ' + esc(p.name) + ' khỏi giỏ</span></button>' +
          '</li>';
      }).join('');

      var fee = ship.fee > 0 && !(ship.freeFrom > 0 && subtotal >= ship.freeFrom) ? ship.fee : 0;
      var giftFee = 0, giftAny = false;
      $$('[data-gift-price]').forEach(function (c) { if (c.checked) { giftAny = true; giftFee += Number(c.dataset.giftPrice) || 0; } });
      var giftRow = $('[data-sum-gift-row]');
      if (giftRow) { giftRow.hidden = !giftAny; $('[data-sum-gift]').textContent = giftFee > 0 ? formatPrice(giftFee) : 'Miễn phí'; }
      $('[data-sum-subtotal]').textContent = subtotal > 0 ? formatPrice(subtotal) : 'Liên hệ';
      $('[data-sum-shipping]').textContent = fee > 0 ? formatPrice(fee) : 'Miễn phí';
      var vat = ship.vat > 0 && subtotal > 0 ? Math.round((subtotal + fee + giftFee) * ship.vat / 100) : 0;
      $$('[data-sum-vat]').forEach(function (el) { el.textContent = subtotal > 0 ? formatPrice(vat) : '–'; });
      $$('[data-sum-total]').forEach(function (el) { el.textContent = subtotal > 0 ? formatPrice(subtotal + fee + giftFee + vat) : 'Liên hệ'; });
      var giftSum = $('[data-gift-summary]');
      if (giftSum) {
        var picked = $$('[data-gift-price]').filter(function (c) { return c.checked; }).length;
        giftSum.textContent = picked ? 'đã chọn ' + picked : 'thiệp, hộp quà, giao hỏa tốc';
      }
      $('[data-sum-contact]').hidden = !hasContact;
    };

    list.addEventListener('change', function (e) {
      if (!e.target.matches('[data-cart-qty]')) return;
      var li = e.target.closest('.cart-item');
      var v = clampQty(e.target);
      var focusId = e.target.id;
      Cart.set(li.dataset.slug, v);
      renderCart();
      var again = document.getElementById(focusId);
      if (again && document.activeElement === body) again.focus();
    });
    list.addEventListener('click', function (e) {
      var rm = e.target.closest('[data-cart-remove]');
      var step = e.target.closest('[data-qty-step]');
      var li = e.target.closest('.cart-item');
      if (rm) {
        var name = li.querySelector('.cart-item__name').textContent;
        var next = li.nextElementSibling || li.previousElementSibling;
        var nextSlug = next && next.dataset.slug;
        Cart.remove(li.dataset.slug);
        renderCart();
        toast('Đã xóa “' + name + '” khỏi giỏ hàng.');
        var target = nextSlug && list.querySelector('[data-slug="' + nextSlug + '"] [data-cart-remove]');
        (target || $('[data-cart-empty] a') || $('#main')).focus();
      } else if (step) {
        // Nút +/- đã đổi giá trị và phát sự kiện change ở trên; giữ focus lại nút vừa bấm
        var dir = step.dataset.qtyStep, slug = li.dataset.slug;
        setTimeout(function () {
          var b = list.querySelector('[data-slug="' + slug + '"] [data-qty-step="' + dir + '"]');
          if (b) (b.disabled ? list.querySelector('[data-slug="' + slug + '"] input') : b).focus();
        }, 0);
      }
    });
    $$('[data-gift-price]').forEach(function (c) {
      c.addEventListener('change', function () {
        if (c.hasAttribute('data-gift-note')) {
          var f = $('[data-gift-note-field]'); f.hidden = !c.checked;
          if (c.checked) $('textarea', f).focus();
        }
        renderCart();
      });
    });
    // Điện thoại: thanh "Tổng cộng + Đặt hàng" ở đáy màn hình khi giỏ có hàng.
    // Khi nút "Đặt hàng" trong ô tóm tắt đã hiện trên màn hình (hoặc đã cuộn qua) → thanh đáy trượt xuống ẩn đi, khỏi 2 nút trùng nhau.
    var barAway = false;
    var pageSubmit = $('[data-checkout-submit]');
    if (bar && pageSubmit && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        var en = entries[entries.length - 1];
        barAway = en.isIntersecting || (en.boundingClientRect.height > 0 && en.boundingClientRect.top < 0);
        syncBar();
      }, { rootMargin: '0px 0px -90px 0px' }).observe(pageSubmit);
    }
    function syncBar() {
      if (!bar) return;
      var show = checkoutEls.length > 0 && !checkoutEls[0].hidden;
      bar.hidden = !show;
      bar.classList.toggle('is-away', !!barAway);
      body.classList.toggle('has-checkout-bar', show && !barAway);
    }
    // Hiện hình thức thanh toán đã chọn ở ô tóm tắt và thanh đáy
    var payNames = { cod: ['Khi nhận hàng (COD)', 'Tổng cộng · COD'], 'chuyen-khoan': ['Chuyển khoản ngân hàng', 'Tổng cộng · chuyển khoản'] };
    function syncPay() {
      var c = $('input[name="thanh-toan"]:checked');
      $$('[data-sum-pay]').forEach(function (el) {
        var short = el.dataset.sumPay === 'short', n = c && payNames[c.value];
        el.textContent = n ? n[short ? 1 : 0] : (short ? 'Chưa chọn thanh toán' : 'Chưa chọn');
        el.classList.toggle('is-missing', !n);
      });
      var pe = $('#pay-error');
      if (c && pe) pe.hidden = true;
    }
    $$('input[name="thanh-toan"]').forEach(function (r) { r.addEventListener('change', syncPay); });
    syncPay();
    // Mục tùy chọn (email/ghi chú, quà tặng) đang thu gọn: tự mở nếu đã có dữ liệu (ví dụ khi bấm quay lại)
    $$('[data-checkout-more]').forEach(function (d) {
      if ($$('input, textarea', d).some(function (f) { return f.type === 'checkbox' ? f.checked : f.value.trim(); })) d.open = true;
    });
    var checkoutForm = $('[data-checkout-form]');
    if (checkoutForm) checkoutForm.addEventListener('submit', function () {
      track('begin_checkout', { items: Cart.get().map(function (i) { return { item_id: i.slug, quantity: i.qty, price: (catalog[i.slug] || {}).price }; }) });
    });
    window.addEventListener('storage', function (e) { if (e.key === CART_KEY) renderCart(); });
    renderCart();
  }

  // Đặt hàng thành công → xóa giỏ (chỉ lần xem đầu tiên ngay sau khi đặt)
  if ($('[data-clear-cart]')) Cart.clear();

  // Ghi nhận đơn cho Google/Facebook/TikTok – mỗi đơn 1 lần (khách có thể mở lại link đơn nhiều lần)
  var buyEl = $('[data-purchase]');
  if (buyEl) {
    try {
      var pd = JSON.parse(buyEl.dataset.purchase || 'null');
      var tracked = JSON.parse(localStorage.getItem('vnp_tracked_v1') || '[]');
      if (pd && tracked.indexOf(pd.id) === -1 && !sessionStorage.getItem('vnp_tracked_' + pd.id)) {
        track('purchase', { transaction_id: pd.id, value: pd.value, items: pd.items.map(function (i) { return { item_id: i.id, item_name: i.name, price: i.price, quantity: i.qty }; }) });
        tracked.unshift(pd.id);
        localStorage.setItem('vnp_tracked_v1', JSON.stringify(tracked.slice(0, 30)));
      }
    } catch (e) { /* bỏ qua */ }
  }

  /* ---------------------------------------------------------------
   * "Đơn hàng của tôi": nhớ đơn / lượt giữ hàng trên thiết bị của khách,
   * báo khi cửa hàng đã xác nhận (có phiếu) hoặc đơn bị hủy – kể cả vài ngày sau.
   * ------------------------------------------------------------- */
  var MY_KEY = 'vnp_my_orders_v1';
  function myOrders() {
    try { var a = JSON.parse(localStorage.getItem(MY_KEY) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; }
  }
  function saveMyOrders(list) {
    try { localStorage.setItem(MY_KEY, JSON.stringify(list.slice(0, 10))); } catch (e) { /* chế độ riêng tư */ }
  }
  function markSeen(code, key) {
    saveMyOrders(myOrders().map(function (o) { if (o.code === code) o.seen = key; return o; }));
  }
  var myEl = $('[data-my-order]');
  if (myEl) {
    try {
      var rec = JSON.parse(myEl.dataset.myOrder);
      var rest = myOrders().filter(function (o) { return o.code !== rec.code; });
      rec.seen = rec.key;
      rest.unshift(rec);
      saveMyOrders(rest);
    } catch (e) { /* bỏ qua */ }
  }
  var receiptPage = document.body.classList.contains('page-receipt');
  if (receiptPage && $('[data-receipt-mount]') && !$('.receipt-wait')) {
    var rc = new URLSearchParams(location.search).get('ma');
    myOrders().forEach(function (o) { if (o.code === rc && String(o.seen || '').indexOf('+r') === -1) markSeen(o.code, String(o.seen || '') + '+r'); });
  }

  // Danh sách trên trang Tra cứu đơn hàng
  var myBox = $('[data-my-orders]');
  if (myBox && myOrders().length) {
    var fmtDate = function (ts) { var d = new Date(ts * 1000); return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear(); };
    $('ul', myBox).innerHTML = myOrders().map(function (o) {
      var hasReceipt = String(o.seen || o.key || '').indexOf('+r') !== -1;
      return '<li><span><strong>' + esc(o.code) + '</strong><small>' + (o.type === 'hold' ? 'Giữ hàng' : 'Đơn hàng') + ' · ' + fmtDate(o.at) + '</small></span>' +
        '<span class="my-orders__links"><a class="btn btn--sm btn--outline" href="' + esc(o.view) + '">Xem ' + (o.type === 'hold' ? 'lượt giữ' : 'đơn') + '</a>' +
        (hasReceipt ? '<a class="btn btn--sm btn--primary" href="' + esc(o.receipt) + '">Phiếu xác nhận</a>' : '') + '</span></li>';
    }).join('');
    myBox.hidden = false;
  }

  // Thanh thông báo khi đơn có thay đổi (không hiện trên trang đơn / phiếu / trang quản lý)
  if (!myEl && !receiptPage && !document.body.classList.contains('page-order')) {
    var now = Date.now() / 1000;
    var pending = myOrders().filter(function (o) {
      var seen = String(o.seen || '');
      return now - o.at < 60 * 86400 && seen.indexOf('+r') === -1 && seen !== 'cancelled' && seen !== 'none';
    }).slice(0, 3);
    pending.forEach(function (o) {
      var api = o.type === 'hold' ? '/giu-hang.php?trang-thai=' : '/dat-hang.php?thanh-toan=';
      fetch(base + api + encodeURIComponent(o.code), { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          var key = d.status + (d.receipt ? '+r' : '');
          if (!d.status || key === o.seen || $('.order-alert')) return;
          var msg, link, label;
          if (d.receipt) {
            msg = o.type === 'hold' ? 'Cửa hàng đã nhận cọc <strong>' + esc(o.code) + '</strong> – món ngọc đang được giữ cho bạn.'
              : d.status === 'cod' ? 'Đơn hàng <strong>' + esc(o.code) + '</strong> đã được cửa hàng xác nhận.'
              : 'Cửa hàng đã nhận thanh toán đơn <strong>' + esc(o.code) + '</strong>.';
            link = o.receipt; label = 'Xem phiếu xác nhận';
          } else if (d.status === 'cancelled' || d.status === 'expired') {
            msg = (o.type === 'hold' ? 'Lượt giữ hàng ' : 'Đơn hàng ') + '<strong>' + esc(o.code) + '</strong> ' + (d.status === 'cancelled' ? 'đã bị hủy.' : 'đã hết thời gian giữ món.');
            link = o.view; label = 'Xem chi tiết';
          } else {
            return;
          }
          var bar = document.createElement('div');
          bar.className = 'order-alert';
          bar.setAttribute('role', 'status');
          bar.innerHTML = '<div class="container order-alert__inner">' + icon('receipt') + '<p>' + msg + '</p><a class="btn btn--sm btn--primary" href="' + esc(link) + '">' + label + '</a>' +
            '<button type="button" class="order-alert__close" aria-label="Đóng thông báo">' + icon('close') + '</button></div>';
          var header = $('.site-header');
          if (header && header.parentNode) header.parentNode.insertBefore(bar, header.nextSibling); else document.body.prepend(bar);
          var done = function () { markSeen(o.code, key); };
          $('a', bar).addEventListener('click', done);
          $('.order-alert__close', bar).addEventListener('click', function () { done(); bar.remove(); });
        })
        .catch(function () { /* thử lại lần sau */ });
    });
  }

  // Nút sao chép
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) return;
    var text = b.dataset.copy;
    var done = function () { toast('Đã sao chép: ' + text); };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
    else { fallbackCopy(text); done(); }
  });
  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0';
    body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    ta.remove();
  }

  /* ---------------------------------------------------------------
   * Hiệu ứng hiện dần khi cuộn (tinh tế, tắt khi người dùng giảm chuyển động)
   * ------------------------------------------------------------- */
  var revealIO = null;
  if (!reduceMotion && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('js-reveal');
    revealIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-revealed'); revealIO.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
  }
  function observeReveal(root) {
    $$('[data-reveal]', root).forEach(function (el, i) {
      if (!revealIO) { el.classList.add('is-revealed'); return; }
      el.style.setProperty('--reveal-delay', (i % 4) * 70 + 'ms');
      revealIO.observe(el);
    });
  }
  observeReveal(document);

  /* ---------------------------------------------------------------
   * Yêu thích (lưu trên trình duyệt)
   * ------------------------------------------------------------- */
  var WISH_KEY = 'vnp_wish_v1';
  var wishUrl = $('.wish-link') ? $('.wish-link').getAttribute('href') : base + '/yeu-thich';
  function wishList() { var w = store(WISH_KEY, []); return Array.isArray(w) ? w : []; }
  function syncWish(root) {
    var list = wishList();
    $$('[data-wish]', root).forEach(function (b) {
      var on = list.indexOf(b.dataset.wish) !== -1;
      b.setAttribute('aria-pressed', String(on));
      var label = $('[data-wish-label]', b);
      if (label) label.textContent = on ? 'Đã lưu vào yêu thích' : 'Thêm vào yêu thích';
    });
    $$('[data-wish-count]').forEach(function (c) { c.textContent = list.length; c.hidden = list.length === 0; });
    $$('.wish-link .sr-only').forEach(function (sr) { sr.textContent = 'Sản phẩm yêu thích (' + list.length + ')'; });
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-wish]');
    if (!b) return;
    e.preventDefault();
    var slug = b.dataset.wish, list = wishList(), i = list.indexOf(slug);
    if (i === -1) {
      list.unshift(slug);
      toast('Đã lưu “' + (b.dataset.name || 'sản phẩm') + '” vào yêu thích.', { link: { href: wishUrl, label: 'Xem danh sách' } });
      track('add_to_wishlist', { id: slug, name: b.dataset.name });
    } else {
      list.splice(i, 1);
      toast('Đã bỏ “' + (b.dataset.name || 'sản phẩm') + '” khỏi yêu thích.');
    }
    storeSet(WISH_KEY, list.slice(0, 60));
    syncWish(document);
    if ($('[data-wish-page]')) renderWishPage();
  });
  window.addEventListener('storage', function (e) { if (e.key === WISH_KEY) syncWish(document); });
  syncWish(document);

  /* Lấy thẻ sản phẩm (HTML) theo danh sách mã */
  function loadCards(slugs, target, done) {
    if (!slugs.length) { target.innerHTML = ''; done && done(0); return; }
    fetch(base + '/the-san-pham.php?ds=' + encodeURIComponent(slugs.join(',')), { credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.text() : ''; })
      .then(function (html) {
        target.innerHTML = html;
        syncWish(target);
        observeReveal(target);
        done && done(target.children.length);
      })
      .catch(function () { done && done(0); });
  }

  function renderWishPage() {
    var box = $('[data-wish-list]');
    if (!box) return;
    loadCards(wishList(), box, function (n) { $('[data-wish-empty]').hidden = n > 0; });
  }
  renderWishPage();

  /* ---------------------------------------------------------------
   * Sản phẩm đã xem gần đây
   * ------------------------------------------------------------- */
  var RECENT_KEY = 'vnp_recent_v1';
  var recentBox = $('[data-recent]');
  if (recentBox) {
    var current = recentBox.dataset.recent;
    var recent = store(RECENT_KEY, []);
    if (!Array.isArray(recent)) recent = [];
    var show = recent.filter(function (s) { return s !== current; }).slice(0, current ? 4 : 8);
    if (current) {
      recent = [current].concat(recent.filter(function (s) { return s !== current; })).slice(0, 12);
      storeSet(RECENT_KEY, recent);
    }
    if (show.length) {
      loadCards(show, $('[data-recent-list]', recentBox), function (n) { recentBox.hidden = n === 0; });
    }
  }

  /* Ghi nhận lượt xem sản phẩm */
  $$('[data-view-item]').forEach(function (el) {
    try { var d = JSON.parse(el.dataset.viewItem); track('view_item', { id: d.id, name: d.name, price: d.price }); } catch (e) { /* bỏ qua */ }
  });

  /* ---------------------------------------------------------------
   * Xem nhanh sản phẩm
   * ------------------------------------------------------------- */
  var qv = $('#quick-view');
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-quick-view]');
    if (!b || !qv) return;
    e.preventDefault();
    var bodyEl = $('[data-qv-body]', qv);
    bodyEl.innerHTML = '<p class="qv__loading">Đang tải…</p>';
    openDialog(qv);
    fetch(base + '/xem-nhanh.php?sp=' + encodeURIComponent(b.dataset.quickView), { credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        bodyEl.innerHTML = html;
        $$('[data-gallery]', bodyEl).forEach(initGallery);
        syncWish(bodyEl);
        var t = $('#qv-title', bodyEl); if (t) { t.setAttribute('tabindex', '-1'); t.focus(); }
        var vi = $('[data-view-item]', bodyEl);
        if (vi) { try { var d = JSON.parse(vi.dataset.viewItem); track('view_item', d); } catch (err) { /* bỏ qua */ } }
      })
      .catch(function () { bodyEl.innerHTML = '<p class="qv__error">Không tải được sản phẩm. Vui lòng thử lại.</p>'; });
  });

  /* ---------------------------------------------------------------
   * Trang sản phẩm (điện thoại): thanh "Mua ngay" ở đáy màn hình trượt xuống ẩn
   * khi khung mua hàng (Thêm vào giỏ / Mua ngay) đang hiện → không thấy 2 nút trùng nhau
   * ------------------------------------------------------------- */
  var mobileBuy = $('[data-mobile-buy]'), buyBox = $('[data-buy-form]');
  if (mobileBuy && buyBox && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      mobileBuy.classList.toggle('is-away', entries[entries.length - 1].isIntersecting);
    }, { rootMargin: '0px 0px -84px 0px' }).observe(buyBox);
  }

  /* ---------------------------------------------------------------
   * Tính ni vòng từ chu vi bàn tay
   * ------------------------------------------------------------- */
  $$('[data-size-calc]').forEach(function (form) {
    var input = $('input', form), out = $('[data-size-result]', form);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var cm = parseFloat(String(input.value).replace(',', '.'));
      if (cm >= 120 && cm <= 260) cm = cm / 10; // nhập bằng mm → đổi ra cm
      if (!(cm >= 12 && cm <= 26)) {
        out.innerHTML = '<p class="field-error">Chu vi bàn tay thường từ 12 đến 26 cm – ví dụ: 17,5.</p>';
        input.setAttribute('aria-invalid', 'true'); input.focus();
        return;
      }
      input.removeAttribute('aria-invalid');
      var ni = Math.round(cm * 10 / Math.PI);
      var html = '<p class="size-calc__ni">Ni phù hợp: <strong>' + ni + ' – ' + (ni + 2) + ' mm</strong></p>';
      var own = Number(form.dataset.ni || 0);
      if (own) {
        if (own >= ni && own <= ni + 2) html += '<p class="size-calc__fit is-ok">' + icon('check') + 'Chiếc vòng này (ni ' + own + ' mm) vừa tay bạn.</p>';
        else if (own < ni) html += '<p class="size-calc__fit">Chiếc vòng này (ni ' + own + ' mm) có thể hơi chật với bạn.</p>';
        else html += '<p class="size-calc__fit">Chiếc vòng này (ni ' + own + ' mm) có thể hơi rộng với bạn.</p>';
      }
      html += '<a class="link-more" href="' + esc(form.action + (form.action.indexOf('?') === -1 ? '?' : '&') + 'chu-vi=' + cm) + '">Xem tất cả vòng vừa tay bạn' + icon('arrow') + '</a>';
      out.innerHTML = html;
    });
  });

  /* ---------------------------------------------------------------
   * Tính ni nhẫn (bảng ni Việt Nam: ni 12 ≈ chu vi 52,4 mm, mỗi ni ~1 mm)
   * Số đo dưới 30 mm = đường kính lòng nhẫn cũ, còn lại = chu vi ngón tay (giống ring_ni_from_mm trong PHP)
   * ------------------------------------------------------------- */
  function num1(n) { return (Math.round(n * 10) / 10).toString().replace('.', ','); }
  // Chu vi ngón tay (mm) từ số đo: chu vi mm (42–76) hoặc cm (4–8), đường kính lòng nhẫn mm (13–25) hoặc cm (1,3–2,5); 0 = không hợp lệ
  function ringCirc(v) {
    if (v >= 1.3 && v <= 2.5) v *= 10; else if (v >= 4 && v <= 8) v *= 10;
    var c = v < 30 ? v * Math.PI : v;
    return c >= 42 && c <= 76 ? c : 0;
  }
  $$('[data-ring-calc]').forEach(function (form) {
    var input = $('input', form), out = $('[data-size-result]', form);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var mm = parseFloat(String(input.value).replace(',', '.'));
      var c = mm > 0 ? ringCirc(mm) : 0;
      if (!c) {
        out.innerHTML = '<p class="field-error">Chưa tính được ni từ số đo này. Nhập chu vi ngón tay (VD: 54 mm hoặc 5,4 cm) hoặc đường kính lòng nhẫn (VD: 17 mm).</p>';
        input.setAttribute('aria-invalid', 'true'); input.focus();
        return;
      }
      input.removeAttribute('aria-invalid');
      var ni = Math.max(1, Math.round((c - 39.8) / 1.047));
      var html = '<p class="size-calc__ni">Ni nhẫn của bạn: <strong>' + ni + '</strong></p>' +
        '<p class="size-calc__note">Chu vi ngón tay ≈ ' + num1(c) + '\u00a0mm · đường kính lòng nhẫn ≈ ' + num1(c / Math.PI) + '\u00a0mm</p>';
      var r = String(form.dataset.range || '').split('-').map(Number);
      if (r.length === 2 && r[0]) {
        if (ni >= r[0] && ni <= r[1]) html += '<p class="size-calc__fit is-ok">' + icon('check') + 'Mẫu nhẫn này có ni ' + ni + ' cho bạn. Khi đặt hàng, ghi “Ni ' + ni + '” vào ô Ghi chú.</p>';
        else html += '<p class="size-calc__fit">Mẫu này có ni ' + r[0] + (r[1] !== r[0] ? ' – ' + r[1] : '') + ', chưa có ni ' + ni + '. Nhắn cửa hàng để được tư vấn mẫu vừa tay.</p>';
      }
      html += '<a class="link-more" href="' + esc(form.action + (form.action.indexOf('?') === -1 ? '?' : '&') + 'ngon-tay=' + mm) + '">Xem tất cả nhẫn có ni ' + ni + icon('arrow') + '</a>';
      out.innerHTML = html;
    });
  });

  /* Chuỗi hạt: chu vi cổ tay → độ dài chuỗi nên chọn */
  $$('[data-wrist-calc]').forEach(function (form) {
    var input = $('input', form), out = $('[data-size-result]', form);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var cm = parseFloat(String(input.value).replace(',', '.'));
      if (cm >= 110 && cm <= 250) cm = cm / 10; // nhập bằng mm → đổi ra cm
      if (!(cm >= 11 && cm <= 25)) {
        out.innerHTML = '<p class="field-error">Vui lòng nhập chu vi cổ tay từ 11 đến 25 cm.</p>';
        input.setAttribute('aria-invalid', 'true'); input.focus();
        return;
      }
      input.removeAttribute('aria-invalid');
      out.innerHTML = '<p class="size-calc__ni">Chuỗi nên dài: <strong>' + num1(cm + 1) + ' – ' + num1(cm + 2) + ' cm</strong></p>' +
        '<p class="size-calc__note">Đeo vừa: khoảng ' + num1(cm + 1) + '\u00a0cm · đeo thoải mái: khoảng ' + num1(cm + 2) + '\u00a0cm.</p>' +
        '<p class="size-calc__note">Khi đặt hàng, ghi “Cổ tay ' + num1(cm) + ' cm” vào ô Ghi chú để cửa hàng tư vấn cỡ chuỗi.</p>';
    });
  });

  /* ---------------------------------------------------------------
   * Giữ hàng: tự cập nhật khi shop xác nhận đã nhận cọc
   * ------------------------------------------------------------- */
  var holdBox = $('[data-hold-status="pending"]');
  if (holdBox) {
    var holdTimer = setInterval(function () {
      fetch(base + '/giu-hang.php?trang-thai=' + encodeURIComponent(holdBox.dataset.holdCode), { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.status && d.status !== 'pending') { clearInterval(holdTimer); window.location.reload(); } })
        .catch(function () { /* thử lại lần sau */ });
    }, 15000);
  }

  // Đơn chuyển khoản: tự cập nhật khi đã nhận tiền / hết giờ giữ món / bị hủy
  var orderBox = $('[data-order-watch]');
  if (orderBox) {
    var orderTimer = setInterval(function () {
      fetch(base + '/dat-hang.php?thanh-toan=' + encodeURIComponent(orderBox.dataset.orderWatch), { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.status && d.status !== 'none' && d.status !== orderBox.dataset.orderState) { clearInterval(orderTimer); window.location.reload(); } })
        .catch(function () { /* thử lại lần sau */ });
    }, 15000);
  }

  // Trang phiếu chưa phát hành: tự tải lại khi cửa hàng xác nhận (có phiếu) hoặc trạng thái đổi
  var receiptWatch = $('[data-receipt-watch]');
  if (receiptWatch) {
    var rwTimer = setInterval(function () {
      var api = receiptWatch.dataset.watchType === 'hold' ? '/giu-hang.php?trang-thai=' : '/dat-hang.php?thanh-toan=';
      fetch(base + api + encodeURIComponent(receiptWatch.dataset.receiptWatch), { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.receipt || (d.status && d.status !== receiptWatch.dataset.watchKey)) { clearInterval(rwTimer); window.location.reload(); } })
        .catch(function () { /* thử lại lần sau */ });
    }, 15000);
  }

  // Hỏi lại trước các thao tác quan trọng
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-confirm]');
    if (b && !window.confirm(b.dataset.confirm)) e.preventDefault();
  });

  /* ---------------------------------------------------------------
   * Mục lục chính sách – đánh dấu mục đang đọc
   * ------------------------------------------------------------- */
  // Bảng trong bài viết: ghi tên cột vào từng ô để trên điện thoại hiển thị dạng thẻ (CSS dùng data-label)
  $$('.prose table').forEach(function (t) {
    var heads = $$('thead th, tr:first-child th', t).map(function (th) { return th.textContent.trim(); });
    if (heads.length < 3) return;
    t.classList.add('table--stack');
    $$('tbody tr', t).forEach(function (tr) { $$('td', tr).forEach(function (td, i) { if (heads[i]) td.setAttribute('data-label', heads[i]); }); });
  });

  // Chân trang: các cột danh sách thu gọn trên điện thoại, luôn mở trên máy tính
  var footerMq = window.matchMedia('(max-width: 767px)');
  $$('.footer-acc').forEach(function (d) {
    if (footerMq.matches) d.open = false;
    $('summary', d).addEventListener('click', function (e) { if (!footerMq.matches) e.preventDefault(); });
  });

  var tocLinks = $$('.policy-toc a');
  if (tocLinks.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        tocLinks.forEach(function (a) {
          var on = a.getAttribute('href') === '#' + en.target.id;
          a.classList.toggle('is-current', on);
          if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
          // Điện thoại: mục lục là dải trượt ngang → đưa mục đang đọc vào tầm nhìn
          var strip = a.closest('ul');
          if (on && strip && strip.scrollWidth > strip.clientWidth) strip.scrollTo({ left: a.parentNode.offsetLeft - 16, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
      });
    }, { rootMargin: '-20% 0px -70% 0px' });
    $$('.policy').forEach(function (s) { io.observe(s); });
  }
})();
