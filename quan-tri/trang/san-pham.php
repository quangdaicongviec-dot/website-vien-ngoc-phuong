<?php
/** Sản phẩm: danh sách, thêm / sửa / nhân bản / xóa, sắp xếp thứ tự, đổi nhanh tình trạng */
defined('ADMIN') || exit;

global $PRODUCTS, $CATEGORIES;
$products = content_load('san-pham');
$stockLabels = ['con-hang' => 'Còn hàng', 'dat-truoc' => 'Đặt trước / làm theo yêu cầu', 'het-hang' => 'Tạm hết hàng', 'da-ban' => 'Đã có chủ (đã bán)'];
$menhNames   = array_map(fn($m) => $m['name'], menh_info());
$catNames    = array_map(fn($c) => $c['name'], $CATEGORIES);
$dipNames    = array_map(fn($c) => $c['name'], content_load('bo-suu-tap'));
$certs       = content_load('kiem-dinh');

function product_index(array $list, string $slug): int
{
    foreach ($list as $i => $p) {
        if (($p['slug'] ?? '') === $slug) {
            return $i;
        }
    }
    return -1;
}

/** Món đang có khách giữ / đặt → không cho xóa */
function product_busy(string $slug): bool
{
    return (bool) hold_for($slug);
}

/* ================= Lưu ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act  = (string) ($_POST['act'] ?? '');
    $slug = (string) ($_POST['slug_old'] ?? $_POST['slug'] ?? '');
    $idx  = product_index($products, $slug);

    if ($act === 'thu-tu') {
        $order = array_flip(in_list('thu-tu', 120));
        usort($products, fn($a, $b) => ($order[$a['slug']] ?? 9999) <=> ($order[$b['slug']] ?? 9999));
        content_save('san-pham', $products) ? adm_log('Sắp xếp sản phẩm') : null;
        adm_back('san-pham', [], 'Đã lưu thứ tự sản phẩm – website hiển thị theo thứ tự này (mục “Mặc định”).');
    }
    if (in_array($act, ['da-ban', 'ban-lai', 'an', 'hien', 'nhan-ban', 'xoa'], true) && $idx >= 0) {
        $p = $products[$idx];
        $msg = '';
        switch ($act) {
            case 'da-ban':  $products[$idx]['stock'] = 'da-ban'; $msg = '“' . $p['name'] . '” chuyển sang Đã có chủ.'; break;
            case 'ban-lai': $products[$idx]['stock'] = 'con-hang'; $msg = '“' . $p['name'] . '” đã mở bán lại.'; break;
            case 'an':      $products[$idx]['hidden'] = true; $msg = 'Đã ẩn “' . $p['name'] . '” khỏi website.'; break;
            case 'hien':    unset($products[$idx]['hidden']); $msg = '“' . $p['name'] . '” đã hiển thị lại trên website.'; break;
            case 'nhan-ban':
                $copy = $p;
                $copy['slug'] = unique_slug($p['slug'] . '-ban-sao', array_column($products, 'slug'));
                $copy['name'] = $p['name'] . ' (bản sao)';
                $copy['hidden'] = true;
                $copy['stock'] = 'con-hang';
                $copy['cert'] = '';
                array_splice($products, $idx + 1, 0, [$copy]);
                content_save('san-pham', $products);
                adm_log('Nhân bản sản phẩm', $p['slug'] . ' → ' . $copy['slug']);
                adm_back('san-pham', ['sua' => $copy['slug']], 'Đã tạo bản sao (đang ẩn). Sửa tên, ảnh, giá rồi bật hiển thị.');
            case 'xoa':
                if (product_busy($p['slug'])) {
                    adm_back('san-pham', ['sua' => $p['slug']], 'Không xóa được: món này đang có khách giữ hoặc đặt. Hãy xử lý lượt giữ/đơn hàng trước, hoặc chọn “Ẩn khỏi website”.', 'error');
                }
                array_splice($products, $idx, 1);
                $msg = 'Đã xóa “' . $p['name'] . '”. (Bản trước khi xóa được lưu trong Sao lưu → Lịch sử thay đổi.)';
                break;
        }
        content_save('san-pham', $products);
        adm_log('Sản phẩm: ' . $act, $p['slug']);
        adm_back('san-pham', $act === 'xoa' ? [] : array_filter(['sua' => !empty($_POST['tu-trang-sua']) ? $p['slug'] : '']), $msg);
    }

    if ($act === 'luu') {
        $isNew = $idx < 0;
        $old   = $isNew ? [] : $products[$idx];
        $name  = in_str('name', 160);
        $newSlug = make_slug(in_str('slug', 100) ?: $name);
        $taken = array_column(array_filter($products, fn($x) => ($x['slug'] ?? '') !== $slug), 'slug');
        $errs  = [];
        if (mb_strlen($name) < 3) {
            $errs[] = 'Vui lòng nhập tên sản phẩm.';
        }
        if ($newSlug === '') {
            $errs[] = 'Mã đường dẫn chưa hợp lệ.';
        } elseif (in_array($newSlug, $taken, true)) {
            $errs[] = 'Mã đường dẫn “' . $newSlug . '” đã có sản phẩm khác dùng – hãy đổi tên khác.';
        }
        $cat = (string) ($_POST['category'] ?? '');
        if (!isset($CATEGORIES[$cat])) {
            $errs[] = 'Vui lòng chọn danh mục.';
        }
        $primary = (string) ($_POST['menh_chinh'] ?? '');
        $menh = array_values(array_unique(array_filter(array_merge([$primary], in_list('menh', 10)), fn($m) => isset($menhNames[$m]))));
        $images = array_values(array_filter(array_map('clean_asset_path', in_list('images', 300))));
        $price  = in_money('price');
        $oldPr  = in_money('old_price');
        $niLo   = in_int('ni_nhan_tu', 0, 40);
        $niHi   = in_int('ni_nhan_den', 0, 40);
        $p = array_merge($old, [
            'slug'        => $newSlug,
            'name'        => $name,
            'category'    => $cat,
            'price'       => $price,
            'old_price'   => $oldPr > $price ? $oldPr : 0,
            'images'      => $images,
            'color'       => in_str('color', 120),
            'menh'        => $menh,
            'size'        => in_str('size', 160),
            'material'    => in_str('material', 200),
            'stock'       => isset($stockLabels[$_POST['stock'] ?? '']) ? (string) $_POST['stock'] : 'con-hang',
            'doc_ban'     => in_bool('doc_ban'),
            'dip'         => array_values(array_filter(in_list('dip', 40), fn($d) => isset($dipNames[$d]))),
            'cert'        => isset($certs[$_POST['cert'] ?? '']) ? (string) $_POST['cert'] : '',
            'video'       => clean_asset_path(in_str('video', 300)),
            'featured'    => in_bool('featured'),
            'new'         => in_bool('new'),
            'short'       => in_str('short', 400),
            'description' => in_str('description', 8000),
        ]);
        if ($cat === 'vong-ngoc' && in_int('ni', 0, 120) > 0) {
            $p['ni'] = in_int('ni', 0, 120);
        } else {
            unset($p['ni']);
        }
        if ($cat === 'nhan-ngoc' && $niLo > 0) {
            $p['ni_nhan'] = [min($niLo, $niHi ?: $niLo), max($niLo, $niHi ?: $niLo)];
        } else {
            unset($p['ni_nhan']);
        }
        if (in_bool('gia_mo_phong')) {
            $p['gia_mo_phong'] = true;
        } else {
            unset($p['gia_mo_phong']);
        }
        if (in_bool('hidden')) {
            $p['hidden'] = true;
        } else {
            unset($p['hidden']);
        }
        if ($errs) {
            $_SESSION['adm_form'] = ['data' => $p, 'errors' => $errs];
            adm_back('san-pham', $isNew ? ['moi' => 1] : ['sua' => $slug], implode(' ', $errs), 'error');
        }
        if ($isNew) {
            $products[] = $p;
        } else {
            $products[$idx] = $p;
        }
        // Đổi mã đường dẫn → cập nhật giấy kiểm định đang trỏ tới sản phẩm
        if (!$isNew && $newSlug !== $slug) {
            $c2 = $certs;
            foreach ($c2 as $k => $c) {
                if (($c['product'] ?? '') === $slug) {
                    $c2[$k]['product'] = $newSlug;
                }
            }
            if ($c2 !== $certs) {
                content_save('kiem-dinh', $c2);
            }
        }
        if (!content_save('san-pham', $products)) {
            adm_back('san-pham', ['sua' => $slug], 'Không lưu được – kiểm tra quyền ghi thư mục data/ trên hosting.', 'error');
        }
        adm_log($isNew ? 'Thêm sản phẩm' : 'Sửa sản phẩm', $newSlug);
        $PRODUCTS = $products;
        adm_back('san-pham', ['sua' => $newSlug], ($isNew ? 'Đã thêm' : 'Đã lưu') . ' “' . $name . '”.' . (!$images ? ' Lưu ý: sản phẩm chưa có ảnh.' : ''), $images ? 'success' : 'error');
    }
    adm_back('san-pham');
}

/* ================= Thêm / sửa ================= */
$editSlug = (string) ($_GET['sua'] ?? '');
if ($editSlug !== '' || !empty($_GET['moi'])) {
    $idx   = $editSlug !== '' ? product_index($products, $editSlug) : -1;
    if ($editSlug !== '' && $idx < 0) {
        adm_back('san-pham', [], 'Không tìm thấy sản phẩm.', 'error');
    }
    $isNew = $idx < 0;
    $p     = $isNew ? ['slug' => '', 'name' => '', 'category' => (string) ($_GET['danh-muc'] ?? array_key_first($CATEGORIES)), 'price' => 0, 'old_price' => 0, 'images' => [],
                       'color' => '', 'menh' => [], 'size' => '', 'material' => 'Ngọc phỉ thúy (Jadeite) tự nhiên loại A', 'stock' => 'con-hang',
                       'doc_ban' => false, 'dip' => [], 'cert' => '', 'video' => '', 'featured' => false, 'new' => true, 'short' => '', 'description' => '', 'hidden' => true]
                     : $products[$idx];
    $flashForm = $_SESSION['adm_form'] ?? null;
    unset($_SESSION['adm_form']);
    if ($flashForm) {
        $p = $flashForm['data'] + $p;
    }
    $held   = !$isNew ? hold_for($p['slug']) : null;
    $certOptions = ['' => '– Chưa có giấy kiểm định –'];
    foreach ($certs as $k => $c) {
        $certOptions[$k] = $k . (!empty($c['product']) && $c['product'] !== $p['slug'] ? ' (đang gắn: ' . (find_product($c['product'])['name'] ?? $c['product']) . ')' : '');
    }
    $menhPrimary = $p['menh'][0] ?? '';
    $title = $isNew ? 'Thêm sản phẩm' : $p['name'];
    adm_head($title, 'san-pham', !$isNew ? '<a class="btn btn--sm btn--outline" href="' . e(product_url($p)) . '" target="_blank" rel="noopener">' . adm_icon('external') . 'Xem trên website</a>' : '',
        [['Sản phẩm', adm_url('san-pham')]]);
    if ($held) {
        echo adm_notice('Món này đang được <strong>' . e($held['name']) . '</strong> ' . (($held['type'] ?? 'hold') === 'order' ? 'đặt mua (đơn ' . e($held['code']) . ')' : 'giữ (mã ' . e($held['code']) . ')') . ' đến ' . e(hold_time((int) $held['until'])) . '.', 'warn');
    }
    if (!empty($p['hidden']) && !$isNew) {
        echo adm_notice('Sản phẩm đang <strong>ẩn khỏi website</strong> – khách không thấy. Tắt “Ẩn khỏi website” rồi lưu để hiển thị.', 'warn');
    }
    ?>
    <form method="post" action="<?= e(adm_url('san-pham')) ?>" data-track>
      <?= adm_csrf_field() ?>
      <input type="hidden" name="act" value="luu">
      <input type="hidden" name="slug_old" value="<?= e($p['slug']) ?>">
      <div class="layout-side">
        <div>
          <section class="card">
            <h2>Thông tin chính</h2>
            <div class="form-grid" style="margin-top:14px">
              <?= f_text('name', 'Tên sản phẩm', $p['name'], ['required' => true, 'wide' => true, 'maxlength' => 160, 'placeholder' => 'VD: Vòng ngọc phỉ thúy xanh lục đế vương']) ?>
              <?= f_select('category', 'Danh mục', $p['category'], $catNames, ['required' => true]) ?>
              <?= f_text('slug', 'Mã đường dẫn', $p['slug'], ['hint' => $isNew ? 'Tự tạo từ tên. Địa chỉ trang: /san-pham/<em>ma-duong-dan</em>' : 'Đổi mã sẽ đổi địa chỉ trang – link cũ đã chia sẻ sẽ không mở được.', 'attrs' => 'data-slug-from="#f-name" spellcheck="false"', 'pattern' => '[a-z0-9\-]+']) ?>
              <?= f_money('price', 'Giá bán', (int) $p['price'], ['hint' => 'Để trống = hiện “Liên hệ”. Có thể gõ “16,8 triệu”.', 'attrs' => 'data-money data-price-input']) ?>
              <?= f_money('old_price', 'Giá gốc trước giảm (nếu có)', (int) ($p['old_price'] ?? 0), ['hint' => 'Cao hơn giá bán → hiện giá gạch ngang và % giảm.']) ?>
              <?= f_switch('gia_mo_phong', 'Đây là giá mô phỏng (chưa phải giá thật)', !empty($p['gia_mo_phong']), ['wide' => true, 'hint' => 'Trang tổng quan sẽ nhắc thay giá thật. Tự tắt khi bạn sửa giá.']) ?>
            </div>
          </section>

          <section class="card">
            <h2>Ảnh &amp; video</h2>
            <div class="form-grid" style="margin-top:14px">
              <?= f_images('images', 'Ảnh sản phẩm', $p['images'] ?? [], ['wide' => true, 'folder' => 'san-pham', 'max' => 1600, 'hint' => 'Ảnh vuông (VD 1000×1000) đẹp nhất. Ảnh đầu tiên là ảnh đại diện, ảnh thứ hai hiện khi rê chuột / vuốt trên điện thoại. Kéo thả hoặc bấm mũi tên để đổi thứ tự.']) ?>
              <div class="af af--wide">
                <label class="af__label" for="f-video">Video thật (không bắt buộc)</label>
                <div class="ai-group"><input class="ai" type="text" id="f-video" name="video" value="<?= e($p['video'] ?? '') ?>" placeholder="Dán link YouTube, hoặc tải video MP4 lên" data-video-input>
                  <label class="ai-group__btn" for="f-video-file" style="position:relative;overflow:hidden"><?= adm_icon('upload') ?><span class="sr-only">Tải video lên</span><input type="file" id="f-video-file" accept="video/mp4,video/webm" data-video-file style="position:absolute;inset:0;opacity:0"></label></div>
                <p class="af__hint" data-video-status>Video ngắn (dưới <?= e(ini_get('upload_max_filesize')) ?>) tải trực tiếp; video dài nên đăng YouTube rồi dán link.</p>
              </div>
            </div>
          </section>

          <section class="card">
            <h2>Đặc điểm</h2>
            <div class="form-grid" style="margin-top:14px">
              <?= f_text('color', 'Màu sắc', $p['color'] ?? '', ['placeholder' => 'VD: Xanh lục đậm']) ?>
              <?= f_text('material', 'Chất liệu', $p['material'] ?? '', ['placeholder' => 'VD: Ngọc phỉ thúy (Jadeite) tự nhiên loại A, vàng 18K']) ?>
              <?= f_text('size', 'Kích thước / ni (chữ hiển thị)', $p['size'] ?? '', ['wide' => true, 'placeholder' => 'VD: Ni 56 mm (đường kính trong) · Cao khoảng 28 mm']) ?>
              <?= f_number('ni', 'Ni vòng – đường kính trong (mm)', (int) ($p['ni'] ?? 0) ?: '', ['min' => 40, 'max' => 90, 'show_if' => 'category=vong-ngoc', 'hint' => 'Dùng cho “Tìm vòng vừa tay”. VD: 56']) ?>
              <div class="af" data-show-if="category=nhan-ngoc">
                <span class="af__label">Ni nhẫn làm / chỉnh được</span>
                <div class="ai-group"><input class="ai" type="number" name="ni_nhan_tu" min="1" max="40" value="<?= e((string) ($p['ni_nhan'][0] ?? '')) ?>" placeholder="Từ ni" aria-label="Từ ni"><span class="ai-group__suffix">đến</span><input class="ai" type="number" name="ni_nhan_den" min="1" max="40" value="<?= e((string) ($p['ni_nhan'][1] ?? '')) ?>" placeholder="Đến ni" aria-label="Đến ni" style="border-radius:0 7px 7px 0;border-left:0"></div>
                <p class="af__hint">Nhẫn 1 ni: điền cùng một số. Dùng cho “Tìm nhẫn vừa tay”.</p>
              </div>
              <?= f_select('menh_chinh', 'Màu ngọc thuộc hành', $menhPrimary, ['' => '– Chọn –'] + $menhNames, ['hint' => 'Xanh lá → Mộc · trắng → Kim · tím/đỏ/hồng → Hỏa · vàng/nâu → Thổ · đen/xanh dương → Thủy. Dùng để không gợi ý quà kỵ mệnh người nhận.']) ?>
              <?= f_checks('menh', 'Hợp mệnh (hiện trên trang, dùng cho bộ lọc)', $p['menh'] ?? [], $menhNames) ?>
              <?= f_checks('dip', 'Dịp tặng – bộ sưu tập quà', $p['dip'] ?? [], $dipNames, ['wide' => true]) ?>
              <?= f_select('cert', 'Giấy kiểm định', $p['cert'] ?? '', $certOptions, ['wide' => true, 'hint' => 'Thêm giấy mới trong mục <a href="' . e(adm_url('kiem-dinh', ['moi' => 1])) . '">Giấy kiểm định</a>.']) ?>
            </div>
          </section>

          <section class="card">
            <h2>Mô tả</h2>
            <div class="form-grid" style="margin-top:14px">
              <?= f_textarea('short', 'Mô tả ngắn (1–2 câu, hiện dưới giá)', $p['short'] ?? '', ['wide' => true, 'rows' => 2, 'maxlength' => 400, 'autosize' => true]) ?>
              <?= f_textarea('description', 'Mô tả chi tiết', $p['description'] ?? '', ['wide' => true, 'rows' => 8, 'autosize' => true, 'hint' => 'Xuống dòng 2 lần (để 1 dòng trống) để tách đoạn.']) ?>
            </div>
          </section>
        </div>

        <aside>
          <section class="card">
            <h2>Bán hàng</h2>
            <div class="stack" style="margin-top:14px">
              <?= f_select('stock', 'Tình trạng', $p['stock'] ?? 'con-hang', $stockLabels) ?>
              <?= f_switch('doc_ban', 'Độc bản – chỉ có 1 chiếc', !empty($p['doc_ban']), ['hint' => 'Hiện nhãn “Độc bản”, mỗi đơn chỉ mua 1, tự giữ món khi có khách đặt.']) ?>
              <?= f_switch('featured', 'Nổi bật – hiện ở trang chủ', !empty($p['featured'])) ?>
              <?= f_switch('new', 'Hàng mới – nhãn “Mới”', !empty($p['new'])) ?>
              <?= f_switch('hidden', 'Ẩn khỏi website', !empty($p['hidden']), ['hint' => 'Soạn xong mới hiển thị, hoặc tạm ẩn món không bán.']) ?>
            </div>
          </section>
          <?php if (!$isNew): ?>
          <section class="card">
            <h2>Thao tác khác</h2>
            <div class="action-box" style="margin-top:12px">
              <button type="submit" form="pact-nhan-ban" class="btn btn--outline"><?= icon('copy') ?>Nhân bản (tạo món tương tự)</button>
              <button type="submit" form="pact-xoa" class="btn btn--danger btn--sm"<?= $held ? ' disabled title="Đang có khách giữ/đặt"' : '' ?>><?= icon('trash') ?>Xóa sản phẩm</button>
            </div>
          </section>
          <?php endif; ?>
        </aside>
      </div>
      <?= adm_savebar($isNew ? 'Thêm sản phẩm' : 'Lưu sản phẩm') ?>
    </form>
    <?php if (!$isNew): ?>
      <form method="post" action="<?= e(adm_url('san-pham')) ?>" id="pact-nhan-ban"><?= adm_csrf_field() ?><input type="hidden" name="act" value="nhan-ban"><input type="hidden" name="slug" value="<?= e($p['slug']) ?>"></form>
      <form method="post" action="<?= e(adm_url('san-pham')) ?>" id="pact-xoa" data-confirm="Xóa hẳn “<?= e($p['name']) ?>”? Nếu chỉ muốn tạm thời không bán, hãy chọn “Ẩn khỏi website”."><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="slug" value="<?= e($p['slug']) ?>"></form>
    <?php endif; ?>
    <?php
    adm_foot();
    return;
}

/* ================= Sắp xếp thứ tự ================= */
if (!empty($_GET['sap-xep'])) {
    adm_head('Sắp xếp sản phẩm', 'san-pham', '', [['Sản phẩm', adm_url('san-pham')]]);
    echo adm_notice('Kéo thả (hoặc bấm mũi tên) để đổi thứ tự hiển thị trên website khi khách chọn “Mặc định”. Món đã có chủ luôn xếp cuối danh sách.');
    ?>
    <form method="post" action="<?= e(adm_url('san-pham')) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="thu-tu">
      <section class="card card--flush">
        <ul class="sort-list" data-sortable>
          <?php foreach ($products as $p): ?>
            <li class="sort-row" data-sort-item>
              <span class="sort-row__grip"><?= adm_icon('drag') ?></span>
              <img class="tbl__thumb" src="<?= e(product_image($p)) ?>" alt="">
              <span class="sort-row__main"><strong><?= e($p['name']) ?></strong><span class="tbl__sub"><?= e($catNames[$p['category']] ?? '') ?><?= !empty($p['hidden']) ? ' · đang ẩn' : '' ?><?= is_sold($p) ? ' · đã có chủ' : '' ?></span></span>
              <input type="hidden" name="thu-tu[]" value="<?= e($p['slug']) ?>">
              <span class="rows__tools"><button type="button" class="icon-mini" data-sort-up title="Lên"><?= adm_icon('up') ?></button><button type="button" class="icon-mini" data-sort-down title="Xuống"><?= adm_icon('down') ?></button></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?= adm_savebar('Lưu thứ tự') ?>
    </form>
    <?php
    adm_foot();
    return;
}

/* ================= Danh sách ================= */
$sets = [
    ''                 => ['Tất cả', fn($p) => true],
    'dang-ban'         => ['Đang bán', fn($p) => empty($p['hidden']) && !is_sold($p) && ($p['stock'] ?? '') !== 'het-hang'],
    'da-ban'           => ['Đã có chủ', fn($p) => is_sold($p)],
    'an'               => ['Đang ẩn', fn($p) => !empty($p['hidden'])],
    'gia-mo-phong'     => ['Giá mô phỏng', fn($p) => !empty($p['gia_mo_phong'])],
    'thieu-kich-thuoc' => ['Thiếu kích thước', fn($p) => strpos((string) ($p['size'] ?? ''), '(cập nhật') !== false || trim((string) ($p['size'] ?? '')) === ''],
];
$loc = (string) ($_GET['loc'] ?? '');
$loc = isset($sets[$loc]) ? $loc : '';
$cat = isset($CATEGORIES[$_GET['danh-muc'] ?? '']) ? (string) $_GET['danh-muc'] : '';
$base = $cat ? array_filter($products, fn($p) => $p['category'] === $cat) : $products;
$list = array_filter($base, $sets[$loc][1]);
$chips = [];
foreach ($sets as $k => [$l, $fn]) {
    $chips[$k] = [$l, adm_url('san-pham', ['loc' => $k, 'danh-muc' => $cat]), count(array_filter($base, $fn))];
}
adm_head('Sản phẩm', 'san-pham',
    '<a class="btn btn--sm btn--outline" href="' . e(adm_url('san-pham', ['sap-xep' => 1])) . '">' . adm_icon('drag') . 'Sắp xếp</a>'
    . '<a class="btn btn--primary" href="' . e(adm_url('san-pham', ['moi' => 1])) . '">' . icon('plus') . 'Thêm sản phẩm</a>');
if (!content_customized('san-pham')) {
    echo adm_notice('Đang dùng danh sách sản phẩm mẫu. Lần đầu bạn lưu bất kỳ thay đổi nào, website sẽ chuyển sang dữ liệu do trang quản trị quản lý.');
}
?>
<div data-filter-scope>
  <div class="adm-filter">
    <label class="adm-filter__search"><span class="sr-only">Tìm sản phẩm</span><?= icon('search') ?><input type="search" placeholder="Tìm tên, màu, mã…" data-filter-input autocomplete="off"></label>
    <form method="get" action="<?= e(base_path() . '/quan-tri/') ?>" data-autosubmit>
      <input type="hidden" name="p" value="san-pham"><input type="hidden" name="loc" value="<?= e($loc) ?>">
      <label class="sr-only" for="f-dm">Danh mục</label>
      <select class="ai" id="f-dm" name="danh-muc" style="min-height:42px;border-radius:999px"><option value="">Mọi danh mục</option><?php foreach ($catNames as $k => $n): ?><option value="<?= e($k) ?>"<?= $k === $cat ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select>
    </form>
  </div>
  <div class="adm-chips" style="margin:-4px 0 16px"><?php foreach ($chips as $k => $c): ?><a class="adm-chip" href="<?= e($c[1]) ?>"<?= (string) $k === $loc ? ' aria-current="true"' : '' ?>><?= e($c[0]) ?> <b><?= (int) $c[2] ?></b></a><?php endforeach; ?></div>
  <section class="card card--flush">
    <?php if (!$list): ?><div class="empty"><?= icon('gem') ?><p>Không có sản phẩm nào trong mục này.</p></div><?php else: ?>
    <div class="tbl-wrap"><table class="tbl tbl--cards">
      <thead><tr><th>Sản phẩm</th><th class="num">Giá</th><th>Tình trạng</th><th class="act"></th></tr></thead>
      <tbody>
      <?php foreach ($list as $p): $held = hold_for($p['slug']); ?>
        <tr data-filter-item data-filter-text="<?= e($p['name'] . ' ' . ($p['color'] ?? '') . ' ' . $p['slug'] . ' ' . ($p['cert'] ?? '')) ?>"<?= !empty($p['hidden']) ? ' class="is-dim"' : '' ?>>
          <td class="tbl__first"><span class="tbl__prod"><img class="tbl__thumb" src="<?= e(product_image($p)) ?>" alt="" loading="lazy"><span>
            <a class="tbl__main" href="<?= e(adm_url('san-pham', ['sua' => $p['slug']])) ?>"><?= e($p['name']) ?></a>
            <span class="tbl__sub"><?= e($catNames[$p['category']] ?? '') ?><?= !empty($p['doc_ban']) ? ' · Độc bản' : '' ?><?= !empty($p['featured']) ? ' · Nổi bật' : '' ?><?= !empty($p['new']) ? ' · Mới' : '' ?></span></span></span></td>
          <td class="num" data-label="Giá"><span><strong><?= e(format_price((int) $p['price'])) ?></strong><?php if (!empty($p['gia_mo_phong'])): ?><span class="tbl__sub"><?= adm_badge('Giá mô phỏng', 'warn') ?></span><?php endif; ?></span></td>
          <td data-label="Tình trạng"><span><?php
            if (!empty($p['hidden'])) echo adm_badge('Đang ẩn', 'muted') . ' ';
            if (is_sold($p)) echo adm_badge('Đã có chủ', 'muted');
            elseif ($held) echo adm_badge(($held['type'] ?? 'hold') === 'order' ? 'Có khách đặt' : 'Đang giữ', 'gold');
            else echo adm_badge($stockLabels[$p['stock'] ?? 'con-hang'] ?? '', ($p['stock'] ?? '') === 'het-hang' ? 'warn' : 'ok');
          ?></span></td>
          <td class="act">
            <a class="btn btn--sm btn--outline" href="<?= e(adm_url('san-pham', ['sua' => $p['slug']])) ?>"><?= adm_icon('edit') ?>Sửa</a>
            <form method="post" action="<?= e(adm_url('san-pham', ['loc' => $loc, 'danh-muc' => $cat])) ?>" class="inline-form"><?= adm_csrf_field() ?><input type="hidden" name="slug" value="<?= e($p['slug']) ?>">
              <?php if (is_sold($p)): ?><button type="submit" name="act" value="ban-lai" class="btn btn--sm btn--ghost" data-confirm="Mở bán lại “<?= e($p['name']) ?>”?">Bán lại</button>
              <?php elseif (!empty($p['doc_ban'])): ?><button type="submit" name="act" value="da-ban" class="btn btn--sm btn--ghost" data-confirm="Chuyển “<?= e($p['name']) ?>” sang Đã có chủ?">Đã có chủ</button><?php endif; ?>
              <?php if (!empty($p['hidden'])): ?><button type="submit" name="act" value="hien" class="btn btn--sm btn--ghost">Hiện</button><?php else: ?><button type="submit" name="act" value="an" class="btn btn--sm btn--ghost">Ẩn</button><?php endif; ?>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <p class="empty" data-filter-empty hidden>Không có sản phẩm khớp từ khóa.</p>
    <?php endif; ?>
  </section>
</div>
<?php
adm_foot();
