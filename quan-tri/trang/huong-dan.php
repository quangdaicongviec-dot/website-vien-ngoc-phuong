<?php
/**
 * Hướng dẫn & tài liệu: "Chức năng website", "Hướng dẫn sử dụng trang quản trị" và tài liệu riêng của cửa hàng.
 * Mọi tài khoản đọc được; tài khoản Quản lý sửa / thêm / xóa (lưu trong data/noi-dung/tai-lieu.json, có lịch sử).
 * Nội dung gốc của 2 tài liệu có sẵn: includes/tai-lieu-mac-dinh.php
 */
defined('ADMIN') || exit;

$defaults = require ROOT . '/includes/tai-lieu-mac-dinh.php';
$saved    = content_load('tai-lieu');

/** Danh sách tài liệu: 2 tài liệu có sẵn trước, rồi tới tài liệu cửa hàng tự thêm */
function docs_list(array $defaults, array $saved): array
{
    $out = [];
    foreach ($defaults as $k => $d) {
        $out[$k] = array_merge($d, ['co_dinh' => true], is_array($saved[$k] ?? null) ? $saved[$k] : []);
    }
    foreach ($saved as $k => $d) {
        if (!isset($defaults[$k]) && is_array($d)) {
            $out[$k] = $d + ['title' => $k, 'content' => '', 'co_dinh' => false];
        }
    }
    return $out;
}

/** Mã tự thay trong tài liệu (ngoài các mã của chính sách) – không thay bên trong <code>…</code> */
function doc_fill(string $html): string
{
    $extra = array_map('e', [
        '{WEBSITE}'        => preg_replace('#^https?://#', '', site_url()),
        '{GIO_COD}'        => (string) (int) cfg('order_cod_hours', 24),
        '{NGAY_GIU_DA_TT}' => (string) (int) cfg('order_paid_hold_days', 7),
        '{NGAY_LUU_DON}'   => (string) (int) cfg('order_keep_days', 730),
    ]);
    $parts = preg_split('#(<code>.*?</code>)#s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $p) {
        if ($i % 2 === 0) {
            $parts[$i] = fill_placeholders(strtr($p, $extra));
        }
    }
    return implode('', $parts);
}

/**
 * Chuẩn bị nội dung để xem: gắn mã cho tiêu đề (mục lục, nút Hướng dẫn mở đúng phần),
 * chia thành các phần theo tiêu đề H2 (để tìm kiếm). Trả về [html, mục lục].
 */
function doc_render(string $html): array
{
    $toc  = [];
    $used = [];
    $html = preg_replace_callback('#<(h[23])>(.*?)</\1>#s', function ($m) use (&$toc, &$used) {
        $text = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8'));
        $id   = make_slug($text) ?: 'muc';
        $base = $id;
        for ($n = 2; isset($used[$id]); $n++) {
            $id = $base . '-' . $n;
        }
        $used[$id] = true;
        $toc[]     = ['level' => $m[1] === 'h2' ? 2 : 3, 'id' => $id, 'text' => $text];
        return '<' . $m[1] . ' id="' . e($id) . '">' . $m[2] . '</' . $m[1] . '>';
    }, doc_fill($html));
    $out = '';
    foreach (preg_split('#(?=<h2[ >])#', (string) $html) as $chunk) {
        if (trim($chunk) !== '') {
            $out .= '<section class="doc-sec" data-doc-sec>' . $chunk . '</section>';
        }
    }
    return [$out, $toc];
}

$docs = docs_list($defaults, $saved);
$tl   = (string) ($_GET['tl'] ?? 'quan-tri');
$tl   = isset($docs[$tl]) || $tl === 'moi' ? $tl : 'quan-tri';
$me   = adm_user();

/* ================= Lưu (chỉ tài khoản Quản lý) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adm_is_owner()) {
        adm_back('huong-dan', ['tl' => $tl], 'Chỉ tài khoản Quản lý được sửa tài liệu.', 'error');
    }
    $act = (string) ($_POST['act'] ?? '');
    $key = (string) ($_POST['tl'] ?? '');
    if ($act === 'luu') {
        $title = in_str('title', 150);
        if (mb_strlen($title) < 3) {
            adm_back('huong-dan', $key !== '' ? ['tl' => $key, 'sua' => 1] : ['tl' => 'moi'], 'Vui lòng nhập tên tài liệu.', 'error');
        }
        if ($key === '' || !isset($docs[$key])) {                  // tài liệu mới
            $key = unique_slug('tl-' . (make_slug($title) ?: 'tai-lieu'), array_keys($docs));
        }
        $saved[$key] = ['title' => $title, 'content' => clean_html((string) ($_POST['content'] ?? '')), 'updated' => time(), 'by' => (string) ($me['name'] ?? '')];
        if (!content_save('tai-lieu', $saved)) {
            adm_back('huong-dan', ['tl' => $key, 'sua' => 1], 'Không lưu được – kiểm tra quyền ghi thư mục data/.', 'error');
        }
        adm_log('Sửa tài liệu', $title);
        adm_back('huong-dan', ['tl' => $key], 'Đã lưu “' . $title . '”.');
    }
    if ($act === 'goc' && isset($defaults[$key])) {
        unset($saved[$key]);
        content_save('tai-lieu', $saved);
        adm_log('Khôi phục nội dung gốc tài liệu', $defaults[$key]['title']);
        adm_back('huong-dan', ['tl' => $key], 'Đã khôi phục nội dung gốc của “' . $defaults[$key]['title'] . '”. (Bản vừa thay được cất trong Sao lưu → Lịch sử thay đổi.)');
    }
    if ($act === 'xoa' && isset($saved[$key]) && !isset($defaults[$key])) {
        $title = (string) ($saved[$key]['title'] ?? $key);
        unset($saved[$key]);
        content_save('tai-lieu', $saved);
        adm_log('Xóa tài liệu', $title);
        adm_back('huong-dan', [], 'Đã xóa tài liệu “' . $title . '”.');
    }
    adm_back('huong-dan', ['tl' => $tl]);
}

$codes = '<code>{SITE_NAME}</code> tên cửa hàng · <code>{WEBSITE}</code> tên miền · <code>{HOTLINE}</code> · <code>{EMAIL}</code> · <code>{ADDRESS}</code> · '
    . '<code>{GIA_GIU_TU}</code> giá từ được giữ hàng · <code>{PHAN_TRAM_COC}</code> % cọc · <code>{PHUT_CHO_COC}</code> phút chờ cọc · <code>{GIO_GIU}</code> giờ giữ · '
    . '<code>{GIO_GIA_HAN}</code> giờ gia hạn · <code>{THANG_PHIEU_MUA}</code> tháng phiếu mua hàng · <code>{NGAY_HOAN_TIEN}</code> ngày hoàn tiền · '
    . '<code>{PHUT_CHO_CK}</code> phút giữ món chờ chuyển khoản · <code>{GIO_COD}</code> giờ giữ món chờ xác nhận COD · <code>{NGAY_GIU_DA_TT}</code> ngày giữ món đã thanh toán · <code>{NGAY_LUU_DON}</code> ngày lưu đơn';

$tabs = function () use ($docs, $tl) {
    $h = '<nav class="adm-tabs no-print" aria-label="Tài liệu">';
    foreach ($docs as $k => $d) {
        $h .= '<a href="' . e(adm_url('huong-dan', ['tl' => $k])) . '"' . ($k === $tl ? ' aria-current="page"' : '') . '>' . e($d['title']) . '</a>';
    }
    return $h . '</nav>';
};

/* ================= Sửa / thêm tài liệu ================= */
if (adm_is_owner() && ($tl === 'moi' || !empty($_GET['sua']))) {
    $isNew = $tl === 'moi';
    $d     = $isNew ? ['title' => '', 'content' => '<h2>Mục 1</h2><p>Nội dung…</p>', 'co_dinh' => false] : $docs[$tl];
    adm_head($isNew ? 'Thêm tài liệu' : 'Sửa: ' . $d['title'], 'huong-dan', '', [['Hướng dẫn & tài liệu', adm_url('huong-dan', $isNew ? [] : ['tl' => $tl])]]);
    ?>
    <form method="post" action="<?= e(adm_url('huong-dan', ['tl' => $tl])) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu"><input type="hidden" name="tl" value="<?= $isNew ? '' : e($tl) ?>">
      <section class="card">
        <div class="form-grid">
          <?= f_text('title', 'Tên tài liệu', $d['title'], ['wide' => true, 'required' => true, 'maxlength' => 150, 'placeholder' => 'VD: Quy trình đóng gói & giao hàng']) ?>
          <?= f_editor('content', 'Nội dung', (string) $d['content'], ['folder' => 'tai-lieu', 'hint' => 'Dùng “H2” cho từng mục lớn (tạo mục lục và ô tìm kiếm theo mục), “H3” cho mục nhỏ. Có thể chèn ảnh chụp màn hình, bảng, liên kết.']) ?>
          <p class="af__hint af--wide">Mã tự thay bằng con số trong Cài đặt (đổi cài đặt là tài liệu tự đổi theo): <?= $codes ?>. Đặt mã trong định dạng mã <code>&lt;code&gt;</code> thì giữ nguyên chữ.</p>
        </div>
      </section>
      <?= adm_savebar($isNew ? 'Thêm tài liệu' : 'Lưu tài liệu') ?>
    </form>
    <?php if (!$isNew && !empty($d['co_dinh']) && isset($saved[$tl])): ?>
      <form method="post" action="<?= e(adm_url('huong-dan', ['tl' => $tl])) ?>" data-confirm="Khôi phục nội dung gốc của “<?= e($d['title']) ?>”? Phần bạn đã sửa sẽ được cất trong Lịch sử thay đổi."><?= adm_csrf_field() ?><input type="hidden" name="act" value="goc"><input type="hidden" name="tl" value="<?= e($tl) ?>"><button type="submit" class="btn btn--outline btn--sm"><?= adm_icon('history') ?>Khôi phục nội dung gốc</button></form>
    <?php elseif (!$isNew && empty($d['co_dinh'])): ?>
      <form method="post" action="<?= e(adm_url('huong-dan', ['tl' => $tl])) ?>" data-confirm="Xóa tài liệu “<?= e($d['title']) ?>”?"><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="tl" value="<?= e($tl) ?>"><button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?>Xóa tài liệu</button></form>
    <?php endif; ?>
    <?php
    adm_foot();
    return;
}

/* ================= Xem tài liệu ================= */
$d = $docs[$tl === 'moi' ? 'quan-tri' : $tl];
[$body, $toc] = doc_render((string) $d['content']);
$actions = '<button type="button" class="btn btn--sm btn--outline" data-print>' . icon('download') . 'In / lưu PDF</button>';
if (adm_is_owner()) {
    $actions .= '<a class="btn btn--sm btn--outline" href="' . e(adm_url('huong-dan', ['tl' => 'moi'])) . '">' . icon('plus') . 'Thêm tài liệu</a>'
        . '<a class="btn btn--sm btn--primary" href="' . e(adm_url('huong-dan', ['tl' => $tl, 'sua' => 1])) . '">' . adm_icon('edit') . 'Sửa tài liệu</a>';
}
adm_head('Hướng dẫn & tài liệu', 'huong-dan', $actions);
echo $tabs();
?>
<div class="layout-side doc" data-doc>
  <article class="card doc-body">
    <h2 class="doc-title"><?= e($d['title']) ?></h2>
    <p class="muted doc-meta"><?= !empty($d['updated']) ? 'Cập nhật lúc ' . e(adm_time((int) $d['updated'])) . (!empty($d['by']) ? ' · ' . e($d['by']) : '') : 'Nội dung gốc' ?></p>
    <div class="prose">
      <?= $body ?: '<p class="muted">Tài liệu chưa có nội dung.</p>' ?>
    </div>
    <p class="muted doc-empty" data-doc-empty hidden>Không tìm thấy mục nào có chữ này.</p>
  </article>
  <aside class="doc-aside no-print">
    <section class="card">
      <label class="af__label" for="doc-search">Tìm trong tài liệu</label>
      <input class="ai" type="search" id="doc-search" placeholder="VD: hoàn tất, cọc, ảnh…" data-doc-search autocomplete="off">
      <?php if ($toc): ?>
        <details class="doc-toc-wrap" open>
          <summary class="af__label">Mục lục</summary>
          <ol class="doc-toc">
            <?php foreach ($toc as $t): ?>
              <li class="doc-toc__l<?= $t['level'] ?>"><a href="#<?= e($t['id']) ?>"><?= e($t['text']) ?></a></li>
            <?php endforeach; ?>
          </ol>
        </details>
      <?php endif; ?>
    </section>
  </aside>
</div>
<?php
adm_foot();
