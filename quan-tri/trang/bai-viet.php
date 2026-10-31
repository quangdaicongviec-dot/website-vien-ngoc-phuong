<?php
/** Cẩm nang – bài viết: soạn bằng trình soạn thảo có định dạng, ảnh, bản nháp */
defined('ADMIN') || exit;

$posts = array_values(content_load('bai-viet'));
$slug  = (string) ($_GET['sua'] ?? '');
$find  = function (string $s) use (&$posts): int {
    foreach ($posts as $i => $a) {
        if (($a['slug'] ?? '') === $s) {
            return $i;
        }
    }
    return -1;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    $old = (string) ($_POST['slug_old'] ?? '');
    $i   = $find($old);
    if ($act === 'xoa' && $i >= 0) {
        array_splice($posts, $i, 1);
        content_save('bai-viet', $posts);
        adm_log('Xóa bài viết', $old);
        adm_back('bai-viet', [], 'Đã xóa bài viết.');
    }
    if ($act === 'luu') {
        $title = in_str('title', 200);
        $new   = make_slug(in_str('slug', 100) ?: $title);
        $taken = array_column(array_filter($posts, fn($a) => $a['slug'] !== $old), 'slug');
        if (mb_strlen($title) < 5) {
            adm_back('bai-viet', $old ? ['sua' => $old] : ['moi' => 1], 'Vui lòng nhập tiêu đề bài viết.', 'error');
        }
        if (in_array($new, $taken, true)) {
            adm_back('bai-viet', $old ? ['sua' => $old] : ['moi' => 1], 'Mã đường dẫn “' . $new . '” đã có bài khác dùng.', 'error');
        }
        $content = clean_html((string) ($_POST['content'] ?? ''));
        $a = array_merge($i >= 0 ? $posts[$i] : [], [
            'slug'    => $new,
            'title'   => $title,
            'date'    => preg_match('/^\d{4}-\d{2}-\d{2}$/', in_str('date', 10)) ? in_str('date', 10) : date('Y-m-d'),
            'author'  => in_str('author', 80) ?: cfg('site_name'),
            'image'   => clean_asset_path(in_str('image', 300)),
            'excerpt' => in_str('excerpt', 400) ?: str_limit(preg_replace('#</(p|h[1-6]|li|blockquote|td|th)>|<br\s*/?>#i', ' $0', $content), 200),
            'content' => $content,
        ]);
        if (in_bool('hidden')) {
            $a['hidden'] = true;
        } else {
            unset($a['hidden']);
        }
        if ($i >= 0) {
            $posts[$i] = $a;
        } else {
            array_unshift($posts, $a);
        }
        content_save('bai-viet', $posts);
        adm_log($i >= 0 ? 'Sửa bài viết' : 'Thêm bài viết', $new);
        adm_back('bai-viet', ['sua' => $new], 'Đã lưu bài viết' . (!empty($a['hidden']) ? ' (đang là bản nháp – chưa hiện trên website).' : '.'));
    }
    adm_back('bai-viet');
}

if ($slug !== '' || !empty($_GET['moi'])) {
    $i = $slug !== '' ? $find($slug) : -1;
    if ($slug !== '' && $i < 0) {
        adm_back('bai-viet', [], 'Không tìm thấy bài viết.', 'error');
    }
    $a = $posts[$i] ?? ['slug' => '', 'title' => '', 'date' => date('Y-m-d'), 'author' => cfg('site_name'), 'image' => '', 'excerpt' => '', 'content' => '', 'hidden' => true];
    adm_head($i >= 0 ? 'Sửa bài viết' : 'Viết bài mới', 'bai-viet', $i >= 0 && empty($a['hidden']) ? '<a class="btn btn--sm btn--outline" href="' . e(article_url($a)) . '" target="_blank" rel="noopener">' . adm_icon('external') . 'Xem bài trên website</a>' : '', [['Cẩm nang', adm_url('bai-viet')]]);
    ?>
    <form method="post" action="<?= e(adm_url('bai-viet')) ?>" data-track>
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="luu"><input type="hidden" name="slug_old" value="<?= e($a['slug']) ?>">
      <div class="layout-side">
        <section class="card">
          <div class="form-grid">
            <?= f_text('title', 'Tiêu đề bài viết', $a['title'], ['wide' => true, 'required' => true]) ?>
            <?= f_editor('content', 'Nội dung', (string) $a['content'], ['folder' => 'bai-viet', 'hint' => 'Dùng “H2” cho các mục lớn, “H3” cho mục nhỏ. Dán chữ từ Word / Google Docs được – định dạng lạ sẽ tự bỏ.']) ?>
            <?= f_textarea('excerpt', 'Đoạn tóm tắt (hiện ở danh sách bài, Google)', $a['excerpt'] ?? '', ['wide' => true, 'rows' => 3, 'maxlength' => 400, 'hint' => 'Để trống = tự lấy đầu bài.']) ?>
          </div>
        </section>
        <aside>
          <section class="card">
            <div class="stack">
              <?= f_switch('hidden', 'Bản nháp – chưa đăng', !empty($a['hidden'])) ?>
              <?= f_text('date', 'Ngày đăng', $a['date'] ?? '', ['type' => 'date']) ?>
              <?= f_text('author', 'Tác giả', $a['author'] ?? '') ?>
              <?= f_text('slug', 'Mã đường dẫn', $a['slug'], ['attrs' => 'data-slug-from="#f-title" spellcheck="false"', 'hint' => 'Địa chỉ: /cam-nang/<em>ma</em>']) ?>
              <?= f_image('image', 'Ảnh đại diện', (string) ($a['image'] ?? ''), ['folder' => 'bai-viet', 'max' => 1600]) ?>
            </div>
          </section>
        </aside>
      </div>
      <?= adm_savebar($i >= 0 ? 'Lưu bài viết' : 'Lưu bài viết') ?>
    </form>
    <?php if ($i >= 0): ?><form method="post" action="<?= e(adm_url('bai-viet')) ?>" data-confirm="Xóa hẳn bài “<?= e($a['title']) ?>”?"><?= adm_csrf_field() ?><input type="hidden" name="act" value="xoa"><input type="hidden" name="slug_old" value="<?= e($a['slug']) ?>"><button type="submit" class="btn btn--danger btn--sm"><?= icon('trash') ?>Xóa bài viết</button></form><?php endif; ?>
    <?php
    adm_foot();
    return;
}

usort($posts, fn($x, $y) => strcmp((string) $y['date'], (string) $x['date']));
adm_head('Cẩm nang – bài viết', 'bai-viet', '<a class="btn btn--primary" href="' . e(adm_url('bai-viet', ['moi' => 1])) . '">' . icon('plus') . 'Viết bài mới</a>');
?>
<div data-filter-scope>
  <?= adm_filterbar('Tìm tiêu đề…') ?>
  <section class="card card--flush">
    <?php if (!$posts): ?><div class="empty"><?= adm_icon('text') ?><p>Chưa có bài viết.</p></div><?php else: ?>
    <div class="tbl-wrap"><table class="tbl tbl--cards">
      <thead><tr><th>Bài viết</th><th>Ngày đăng</th><th>Trạng thái</th><th class="act"></th></tr></thead>
      <tbody>
      <?php foreach ($posts as $a): ?>
        <tr data-filter-item<?= !empty($a['hidden']) ? ' class="is-dim"' : '' ?>>
          <td class="tbl__first"><span class="tbl__prod"><?php if (!empty($a['image'])): ?><img class="tbl__thumb" src="<?= e(img_url($a['image'])) ?>" alt="" loading="lazy"><?php endif; ?><a class="tbl__main" href="<?= e(adm_url('bai-viet', ['sua' => $a['slug']])) ?>"><?= e($a['title']) ?></a></span></td>
          <td data-label="Ngày đăng"><?= e(format_date((string) $a['date'])) ?></td>
          <td data-label="Trạng thái"><?= !empty($a['hidden']) ? adm_badge('Bản nháp', 'muted') : adm_badge('Đã đăng', 'ok') ?></td>
          <td class="act"><a class="btn btn--sm btn--outline" href="<?= e(adm_url('bai-viet', ['sua' => $a['slug']])) ?>"><?= adm_icon('edit') ?>Sửa</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>
</div>
<?php
adm_foot();
