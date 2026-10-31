/* ==========================================================================
   Viên Ngọc Phương – TRANG QUẢN TRỊ
   Menu điện thoại · xác nhận thao tác · tải ảnh (thu nhỏ trước khi gửi) · sắp xếp
   · danh sách dòng · trình soạn thảo · nhắc lưu thay đổi · lọc nhanh danh sách
   ========================================================================== */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var body = document.body;
  var CSRF = body.dataset.csrf || '';
  var UPLOAD = body.dataset.upload || '';
  var BASE = body.dataset.base || '';

  function toast(msg, isError) {
    var box = $('[data-toast]');
    if (!box) { if (isError) alert(msg); return; }
    var d = document.createElement('div');
    if (isError) d.className = 'is-error';
    d.textContent = msg;
    box.appendChild(d);
    setTimeout(function () { d.remove(); }, isError ? 7000 : 3500);
  }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function imgUrl(p) { return /^(https?:)?\/\//.test(p) ? p : BASE + '/assets/img/' + p.replace(/^\/+/, ''); }

  /* ---------- Menu trên điện thoại ---------- */
  var nav = $('#adm-nav'), navBtn = $('[data-nav-open]');
  function setNav(open) {
    if (!nav) return;
    nav.classList.toggle('is-open', open);
    if (navBtn) navBtn.setAttribute('aria-expanded', String(open));
    body.style.overflow = open ? 'hidden' : '';
  }
  if (navBtn) navBtn.addEventListener('click', function () { setNav(!nav.classList.contains('is-open')); });
  $$('[data-nav-close]').forEach(function (el) { el.addEventListener('click', function () { setNav(false); }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setNav(false); });

  // Đóng menu tài khoản khi bấm ra ngoài
  document.addEventListener('click', function (e) {
    $$('details.adm-user[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
  });

  /* ---------- Thông báo, xác nhận, hiện mật khẩu, sao chép ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-dismiss]');
    if (t) { t.closest('.adm-flash, .adm-note').remove(); return; }
    var sp = e.target.closest('[data-show-pass]');
    if (sp) {
      var inp = sp.parentNode.querySelector('input');
      inp.type = inp.type === 'password' ? 'text' : 'password';
      sp.setAttribute('aria-label', inp.type === 'password' ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
      return;
    }
    var cp = e.target.closest('[data-copy]');
    if (cp) {
      e.preventDefault();
      var text = cp.dataset.copy;
      (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () {
        toast('Đã sao chép');
      }).catch(function () { window.prompt('Sao chép nội dung dưới đây:', text); });
      return;
    }
    var cf = e.target.closest('[data-confirm]');
    if (cf && cf.tagName !== 'FORM' && !window.confirm(cf.dataset.confirm)) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);
  document.addEventListener('submit', function (e) {
    var f = e.target;
    var btn = e.submitter && e.submitter.dataset.confirm ? null : f;
    if (btn && f.dataset.confirm && !window.confirm(f.dataset.confirm)) { e.preventDefault(); return; }
    var uploading = $$('[data-uploading]', f).length;
    if (uploading) { e.preventDefault(); toast('Ảnh đang tải lên – vui lòng đợi xong rồi bấm lưu.', true); return; }
    $$('[data-editor]', f).forEach(syncEditor);
    var s = e.submitter && e.submitter.matches('[data-submit], .btn') ? e.submitter : $('[data-submit]', f);
    if (s && f.checkValidity()) { setTimeout(function () { s.classList.add('is-loading'); }, 0); }
    f.dataset.submitting = '1';
  });

  /* ---------- Ô tiền: 16800000 → 16.800.000 ---------- */
  function fmtMoney(v) { var d = String(v).replace(/\D/g, '').replace(/^0+(?=\d)/, ''); return d ? d.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''; }
  document.addEventListener('input', function (e) {
    var el = e.target;
    if (el.matches && el.matches('[data-money]')) {
      var raw = el.value, pos = el.selectionStart, digitsBefore = raw.slice(0, pos).replace(/\D/g, '').length;
      if (/tr|triệu/i.test(raw)) return;           // cho gõ "16,8 triệu"
      el.value = fmtMoney(raw);
      var n = 0, i = 0;
      for (; i < el.value.length && n < digitsBefore; i++) if (/\d/.test(el.value[i])) n++;
      try { el.setSelectionRange(i, i); } catch (x) {}
    }
  });

  /* ---------- Tự giãn ô nhập nhiều dòng ---------- */
  function autosize(t) { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight + 2, 600) + 'px'; }
  document.addEventListener('input', function (e) { if (e.target.matches && e.target.matches('textarea[data-autosize]')) autosize(e.target); });
  $$('textarea[data-autosize]').forEach(autosize);

  /* ---------- Nhắc lưu thay đổi ---------- */
  $$('form[data-track]').forEach(function (f) {
    var hint = $('[data-dirty-hint]', f) || $('[data-dirty-hint]');
    function dirty() {
      if (f.dataset.dirty) return;
      f.dataset.dirty = '1';
      if (hint) { hint.textContent = 'Có thay đổi chưa lưu'; hint.classList.add('is-dirty'); }
    }
    f.addEventListener('input', dirty);
    f.addEventListener('change', dirty);
    f.addEventListener('adm:changed', dirty);
  });
  window.addEventListener('beforeunload', function (e) {
    var f = $$('form[data-track]').find(function (x) { return x.dataset.dirty && !x.dataset.submitting; });
    if (f) { e.preventDefault(); e.returnValue = ''; }
  });

  /* ---------- Hiện/ẩn ô theo lựa chọn: data-show-if="ten=gia-tri|gia-tri" ---------- */
  function refreshShowIf() {
    $$('[data-show-if]').forEach(function (el) {
      var parts = el.dataset.showIf.split('='), name = parts[0], vals = (parts[1] || '1').split('|');
      var inputs = $$('[name="' + name + '"]');
      var val = '';
      inputs.forEach(function (i) {
        if (i.type === 'checkbox') { if (i.checked) val = i.value; }
        else if (i.type === 'radio') { if (i.checked) val = i.value; }
        else if (i.type !== 'hidden' || inputs.length === 1) val = i.value;
      });
      el.hidden = vals.indexOf(val) === -1;
    });
  }
  document.addEventListener('change', refreshShowIf);
  refreshShowIf();

  /* ---------- Mã đường dẫn tự tạo từ tên ---------- */
  function slugify(s) {
    return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 90);
  }
  $$('[data-slug-from]').forEach(function (slug) {
    var src = $(slug.dataset.slugFrom);
    if (!src) return;
    var touched = slug.value !== '';
    slug.addEventListener('input', function () { touched = slug.value !== ''; });
    src.addEventListener('input', function () { if (!touched) slug.value = slugify(src.value); });
  });

  /* ---------- Lọc nhanh danh sách ---------- */
  $$('[data-filter-input]').forEach(function (inp) {
    var scope = inp.closest('[data-filter-scope]') || document;
    var fold = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd'); };
    var items = $$('[data-filter-item]', scope);
    var empty = $('[data-filter-empty]', scope);
    inp.addEventListener('input', function () {
      var q = fold(inp.value.trim()), shown = 0;
      items.forEach(function (it) {
        var ok = !q || fold(it.dataset.filterText || it.textContent).indexOf(q) !== -1;
        it.hidden = !ok;
        if (ok) shown++;
      });
      if (empty) empty.hidden = shown > 0;
    });
  });

  /* ---------- Thu nhỏ ảnh trước khi tải lên (đỡ chậm, không vượt giới hạn hosting) ---------- */
  function shrink(file, maxSide) {
    return new Promise(function (resolve) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type) || !window.createImageBitmap) return resolve(file);
      createImageBitmap(file).then(function (bmp) {
        var w = bmp.width, h = bmp.height, big = Math.max(w, h);
        if (big <= maxSide && file.size <= 1.6 * 1024 * 1024) { bmp.close && bmp.close(); return resolve(file); }
        var r = Math.min(1, maxSide / big), cw = Math.round(w * r), chh = Math.round(h * r);
        var c = document.createElement('canvas');
        c.width = cw; c.height = chh;
        var ctx = c.getContext('2d');
        var keepAlpha = file.type !== 'image/jpeg';
        if (!keepAlpha) { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, cw, chh); }
        ctx.drawImage(bmp, 0, 0, cw, chh);
        bmp.close && bmp.close();
        var type = keepAlpha ? 'image/webp' : 'image/jpeg';
        c.toBlob(function (blob) {
          if (!blob) return resolve(file);
          if (keepAlpha && blob.type !== 'image/webp') type = 'image/png';     // trình duyệt cũ không xuất được webp
          var ext = type === 'image/jpeg' ? '.jpg' : type === 'image/webp' ? '.webp' : '.png';
          var name = file.name.replace(/\.[^.]+$/, '') + ext;
          resolve(blob.size < file.size || big > maxSide ? new File([blob], name, { type: blob.type || type }) : file);
        }, type, 0.86);
      }).catch(function () { resolve(file); });
    });
  }
  function upload(file, folder, maxSide) {
    return shrink(file, maxSide || 2000).then(function (f) {
      var fd = new FormData();
      fd.append('file', f, f.name);
      fd.append('folder', folder || 'chung');
      fd.append('max', String(maxSide || 2000));
      fd.append('_t', CSRF);
      return fetch(UPLOAD, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF': CSRF } })
        .then(function (r) { return r.json().catch(function () { return { error: 'Máy chủ trả lời không hợp lệ (có thể ảnh quá lớn so với giới hạn hosting).' }; }); })
        .then(function (d) {
          if (!d || d.error || !d.path) throw new Error((d && d.error) || 'Không tải được ảnh.');
          // Lỗi Cloudflare R2: tệp vẫn lưu trên máy chủ – chỉ báo 1 lần mỗi trang
          if (d.r2_error && !upload.warned) {
            upload.warned = true;
            window.alert('Tệp đã lưu trên máy chủ nhưng chưa đưa lên Cloudflare R2:\n' + d.r2_error + '\n\nVào Cài đặt → Lưu trữ Cloudflare R2 để kiểm tra.');
          }
          return d;
        });
    });
  }

  /* Ảnh đơn */
  $$('[data-img-field]').forEach(function (box) {
    var input = $('[data-img-input]', box), val = $('[data-img-value]', box), prev = $('.imgf__preview', box);
    var clear = $('[data-img-clear]', box), status = $('[data-img-status]', box);
    input.addEventListener('change', function () {
      var file = input.files[0];
      if (!file) return;
      box.setAttribute('data-uploading', '1');
      status.className = 'imgf__status'; status.textContent = 'Đang tải ảnh lên…';
      upload(file, box.dataset.folder, +box.dataset.max).then(function (d) {
        val.value = d.path;
        prev.classList.remove('is-empty');
        prev.innerHTML = '<img src="' + esc(imgUrl(d.path)) + '" alt="">';
        clear.hidden = false;
        status.textContent = 'Đã tải lên (' + d.w + '×' + d.h + ' px). Bấm Lưu để áp dụng.';
        box.dispatchEvent(new Event('adm:changed', { bubbles: true }));
      }).catch(function (err) {
        status.className = 'imgf__status is-error'; status.textContent = err.message;
      }).then(function () { box.removeAttribute('data-uploading'); input.value = ''; });
    });
    clear.addEventListener('click', function () {
      val.value = '';
      prev.classList.add('is-empty');
      prev.innerHTML = '<span>Chưa có ảnh</span>';
      clear.hidden = true;
      status.textContent = '';
      box.dispatchEvent(new Event('adm:changed', { bubbles: true }));
    });
  });

  /* Nhiều ảnh */
  $$('[data-imgs-field]').forEach(function (box) {
    var input = $('[data-imgs-input]', box), list = $('.imgs__list', box), status = $('[data-img-status]', box);
    var name = box.dataset.name;
    input.addEventListener('change', function () {
      var files = Array.prototype.slice.call(input.files);
      if (!files.length) return;
      box.setAttribute('data-uploading', '1');
      status.className = 'imgf__status';
      var done = 0, failed = [];
      var next = function () {
        if (!files.length) {
          box.removeAttribute('data-uploading');
          input.value = '';
          status.className = 'imgf__status' + (failed.length ? ' is-error' : '');
          status.textContent = failed.length ? 'Không tải được: ' + failed.join('; ') : 'Đã thêm ' + done + ' ảnh. Kéo ảnh để đổi thứ tự – ảnh đầu tiên là ảnh đại diện. Bấm Lưu để áp dụng.';
          box.dispatchEvent(new Event('adm:changed', { bubbles: true }));
          return;
        }
        var f = files.shift();
        status.textContent = 'Đang tải ảnh ' + (done + failed.length + 1) + '…';
        upload(f, box.dataset.folder, +box.dataset.max).then(function (d) {
          var li = document.createElement('li');
          li.className = 'imgs__item';
          li.setAttribute('data-sort-item', '');
          li.innerHTML = '<img src="' + esc(imgUrl(d.path)) + '" alt=""><input type="hidden" name="' + esc(name) + '[]" value="' + esc(d.path) + '">' +
            '<span class="imgs__badge">Ảnh đại diện</span><span class="imgs__tools">' + $('template[data-tile-tools]') .innerHTML + '</span>';
          list.appendChild(li);
          bindSortItem(li);
          done++;
        }).catch(function (err) { failed.push(f.name + ' – ' + err.message); }).then(next);
      };
      next();
    });
  });

  /* ---------- Kéo thả / mũi tên để đổi thứ tự ---------- */
  var dragEl = null;
  function bindSortItem(it) {
    it.setAttribute('draggable', 'true');
    it.addEventListener('dragstart', function (e) {
      if (e.target.closest('input, textarea, select, button') && e.target !== it) { e.preventDefault(); return; }
      dragEl = it; it.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', ''); } catch (x) {}
    });
    it.addEventListener('dragend', function () { it.classList.remove('is-dragging'); $$('.is-over').forEach(function (x) { x.classList.remove('is-over'); }); dragEl = null; });
    it.addEventListener('dragover', function (e) { if (!dragEl || dragEl.parentNode !== it.parentNode || dragEl === it) return; e.preventDefault(); it.classList.add('is-over'); });
    it.addEventListener('dragleave', function () { it.classList.remove('is-over'); });
    it.addEventListener('drop', function (e) {
      if (!dragEl || dragEl.parentNode !== it.parentNode) return;
      e.preventDefault();
      it.classList.remove('is-over');
      var items = Array.prototype.slice.call(it.parentNode.children);
      it.parentNode.insertBefore(dragEl, items.indexOf(dragEl) < items.indexOf(it) ? it.nextSibling : it);
      changed(it);
    });
  }
  function changed(el) { el.dispatchEvent(new Event('adm:changed', { bubbles: true })); var l = el.closest('[data-sortable]'); if (l) l.dispatchEvent(new Event('adm:sorted', { bubbles: true })); }
  $$('[data-sortable] > [data-sort-item]').forEach(bindSortItem);
  document.addEventListener('click', function (e) {
    var up = e.target.closest('[data-sort-up]'), down = e.target.closest('[data-sort-down]'), rm = e.target.closest('[data-remove-item]');
    var it = (up || down || rm) && (up || down || rm).closest('[data-sort-item]');
    if (!it) return;
    e.preventDefault();
    if (up && it.previousElementSibling) { it.parentNode.insertBefore(it, it.previousElementSibling); changed(it); }
    if (down && it.nextElementSibling) { it.parentNode.insertBefore(it.nextElementSibling, it); changed(it); }
    if (rm) {
      var parent = it.parentNode;
      if (rm.dataset.removeConfirm && !window.confirm(rm.dataset.removeConfirm)) return;
      it.remove();
      parent.dispatchEvent(new Event('adm:changed', { bubbles: true }));
    }
  });

  /* Danh sách dòng: thêm dòng mới từ mẫu */
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-row]');
    if (!add) return;
    e.preventDefault();
    var box = add.closest('[data-rows]'), tpl = $('template[data-row-template]', box), list = $('[data-sortable]', box);
    var wrap = document.createElement('div');
    wrap.innerHTML = tpl.innerHTML.trim();
    var row = wrap.firstElementChild;
    list.appendChild(row);
    bindSortItem(row);
    var f = $('input, textarea, select', row);
    if (f) f.focus();
    box.dispatchEvent(new Event('adm:changed', { bubbles: true }));
  });

  /* Sắp xếp danh sách lớn rồi lưu thứ tự (sản phẩm, banner…) */
  $$('[data-order-form]').forEach(function (form) {
    var list = $('[data-sortable]', form), bar = $('[data-order-save]', form);
    if (!list) return;
    list.addEventListener('adm:sorted', function () { if (bar) bar.hidden = false; });
  });

  /* ---------- Trình soạn thảo văn bản ---------- */
  function cleanPasted(html) {
    var doc = new DOMParser().parseFromString('<div>' + html + '</div>', 'text/html');
    var root = doc.body.firstChild;
    var keep = { P: 1, BR: 1, H2: 1, H3: 1, H4: 1, STRONG: 1, B: 1, EM: 1, I: 1, U: 1, UL: 1, OL: 1, LI: 1, A: 1, BLOCKQUOTE: 1, TABLE: 1, THEAD: 1, TBODY: 1, TR: 1, TH: 1, TD: 1, IMG: 1 };
    (function walk(n) {
      Array.prototype.slice.call(n.childNodes).forEach(function (c) {
        if (c.nodeType === 8 || /^(SCRIPT|STYLE|META|LINK|O:P|XML)$/i.test(c.nodeName)) { c.remove(); return; }
        if (c.nodeType !== 1) return;
        walk(c);
        var tag = c.nodeName === 'H1' ? 'H2' : c.nodeName === 'DIV' ? 'P' : c.nodeName;
        if (!keep[tag]) { while (c.firstChild) n.insertBefore(c.firstChild, c); c.remove(); return; }
        if (tag !== c.nodeName) { var nn = doc.createElement(tag); while (c.firstChild) nn.appendChild(c.firstChild); n.replaceChild(nn, c); c = nn; }
        Array.prototype.slice.call(c.attributes).forEach(function (a) { if (!/^(href|src|alt)$/i.test(a.name)) c.removeAttribute(a.name); });
      });
    })(root);
    return root.innerHTML;
  }
  function syncEditor(box) {
    var area = $('[data-editor-area]', box), src = $('[data-editor-source]', box);
    if (!box.classList.contains('is-html')) src.value = area.innerHTML.replace(/<p><br><\/p>/g, '').trim();
  }
  $$('[data-editor]').forEach(function (box) {
    var area = $('[data-editor-area]', box), src = $('[data-editor-source]', box), file = $('[data-editor-file]', box);
    area.innerHTML = src.value || '<p><br></p>';
    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (x) {}
    var savedRange = null;
    function saveSel() { var s = window.getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) savedRange = s.getRangeAt(0).cloneRange(); }
    function restoreSel() { if (!savedRange) { area.focus(); return; } var s = window.getSelection(); s.removeAllRanges(); s.addRange(savedRange); }
    area.addEventListener('keyup', saveSel);
    area.addEventListener('mouseup', saveSel);
    area.addEventListener('input', function () { saveSel(); box.dispatchEvent(new Event('adm:changed', { bubbles: true })); });
    area.addEventListener('paste', function (e) {
      var cd = e.clipboardData;
      if (!cd) return;
      e.preventDefault();
      var html = cd.getData('text/html'), text = cd.getData('text/plain');
      if (html) document.execCommand('insertHTML', false, cleanPasted(html));
      else document.execCommand('insertHTML', false, text.split(/\n{2,}/).map(function (p) { return '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>'; }).join(''));
    });
    $$('[data-cmd]', box).forEach(function (b) {
      b.addEventListener('mousedown', function (e) { e.preventDefault(); });
      b.addEventListener('click', function () {
        var cmd = b.dataset.cmd;
        if (cmd === 'html') {
          if (box.classList.contains('is-html')) { area.innerHTML = src.value; box.classList.remove('is-html'); src.hidden = true; }
          else { syncEditor(box); box.classList.add('is-html'); src.hidden = false; src.focus(); }
          b.classList.toggle('is-on', box.classList.contains('is-html'));
          return;
        }
        if (box.classList.contains('is-html')) return;
        restoreSel();
        if (cmd === 'p' || cmd === 'h2' || cmd === 'h3') document.execCommand('formatBlock', false, '<' + cmd + '>');
        else if (cmd === 'quote') document.execCommand('formatBlock', false, '<blockquote>');
        else if (cmd === 'bold' || cmd === 'italic') document.execCommand(cmd);
        else if (cmd === 'ul') document.execCommand('insertUnorderedList');
        else if (cmd === 'ol') document.execCommand('insertOrderedList');
        else if (cmd === 'unlink') document.execCommand('unlink');
        else if (cmd === 'clear') { document.execCommand('removeFormat'); document.execCommand('formatBlock', false, '<p>'); }
        else if (cmd === 'link') {
          var u = window.prompt('Dán đường link (VD: https://… hoặc /san-pham/…):', 'https://');
          if (u && u !== 'https://') { restoreSel(); document.execCommand('createLink', false, u); }
        } else if (cmd === 'table') {
          document.execCommand('insertHTML', false, '<table><thead><tr><th>Cột 1</th><th>Cột 2</th><th>Cột 3</th></tr></thead><tbody><tr><td>…</td><td>…</td><td>…</td></tr><tr><td>…</td><td>…</td><td>…</td></tr></tbody></table><p><br></p>');
        } else if (cmd === 'image') { file.click(); return; }
        saveSel();
        box.dispatchEvent(new Event('adm:changed', { bubbles: true }));
      });
    });
    file.addEventListener('change', function () {
      var f = file.files[0];
      if (!f) return;
      box.setAttribute('data-uploading', '1');
      toast('Đang tải ảnh lên…');
      upload(f, box.dataset.folder, 1600).then(function (d) {
        restoreSel();
        document.execCommand('insertHTML', false, '<p><img src="' + esc(imgUrl(d.path)) + '" alt="" width="' + d.w + '" height="' + d.h + '"></p>');
        box.dispatchEvent(new Event('adm:changed', { bubbles: true }));
      }).catch(function (err) { toast(err.message, true); }).then(function () { box.removeAttribute('data-uploading'); file.value = ''; });
    });
  });

  /* ---------- Chọn tất cả (bảng) ---------- */
  $$('[data-check-all]').forEach(function (all) {
    all.addEventListener('change', function () {
      $$('input[type="checkbox"][name="' + all.dataset.checkAll + '"]').forEach(function (c) { if (!c.closest('[hidden]')) c.checked = all.checked; });
    });
  });

  /* ---------- Video sản phẩm: tải MP4 lên ---------- */
  $$('[data-video-file]').forEach(function (inp) {
    var box = inp.closest('.af'), field = $('[data-video-input]', box), status = $('[data-video-status]', box);
    inp.addEventListener('change', function () {
      var f = inp.files[0];
      if (!f) return;
      box.setAttribute('data-uploading', '1');
      status.textContent = 'Đang tải video lên (' + Math.round(f.size / 1048576) + ' MB)…';
      upload(f, 'video').then(function (d) {
        field.value = d.path;
        status.textContent = 'Đã tải video lên. Bấm Lưu để áp dụng.';
        box.dispatchEvent(new Event('adm:changed', { bubbles: true }));
      }).catch(function (err) { status.textContent = err.message; }).then(function () { box.removeAttribute('data-uploading'); inp.value = ''; });
    });
  });

  /* Sửa giá → tự bỏ đánh dấu "giá mô phỏng" */
  $$('[data-price-input]').forEach(function (inp) {
    var sw = document.getElementById('f-gia-mo-phong');
    if (!sw) return;
    var start = inp.value;
    inp.addEventListener('input', function () { if (inp.value !== start) sw.checked = false; });
  });

  /* ---------- Tự gửi biểu mẫu khi đổi lựa chọn (lọc) ---------- */
  $$('[data-autosubmit]').forEach(function (f) { f.addEventListener('change', function () { f.submit(); }); });
})();
