<?php
/** Tra cứu giấy kiểm định theo mã in trên giấy — dữ liệu trong data/certificates.php */
require __DIR__ . '/includes/init.php';

$code   = mb_substr(trim((string) ($_GET['ma'] ?? '')), 0, 40);
$cert   = $code !== '' ? find_certificate($code) : null;
$cert_p = $cert && !empty($cert['product']) ? find_product($cert['product']) : null;

$page_title = $cert ? 'Giấy kiểm định ' . $cert['code'] : 'Tra cứu giấy kiểm định';
$page_desc  = 'Tra cứu giấy kiểm định ngọc phỉ thúy của ' . cfg('site_name') . ' bằng mã in trên giấy – minh bạch nguồn gốc và chất lượng từng viên ngọc.';
$body_class = 'page-cert';
if ($code !== '') {
    $head_extra = '<meta name="robots" content="noindex, follow">';
}
$guide = find_article('ngoc-phi-thuy-loai-a-b-c-la-gi');

include ROOT . '/includes/header.php';
?>

<section class="page-hero page-hero--center">
  <div class="container">
    <?= render_breadcrumbs([['label' => 'Tra cứu giấy kiểm định']]) ?>
    <span class="page-hero__icon"><?= icon('cert') ?></span>
    <h1 class="page-hero__title"><?= e(txt('cert.title')) ?></h1>
    <p class="page-hero__desc"><?= e(txt('cert.lead')) ?></p>
    <form class="search-form search-form--wide cert-search" action="<?= e(url('tra-cuu-kiem-dinh')) ?>" method="get" role="search">
      <label class="sr-only" for="cert-code">Mã giấy kiểm định</label>
      <input id="cert-code" name="ma" type="search" value="<?= e($code) ?>" placeholder="Nhập mã, ví dụ: VNP-2610-0001" autocomplete="off" required>
      <button type="submit" class="search-form__btn"><?= icon('search') ?><span class="sr-only">Tra cứu</span></button>
    </form>
  </div>
</section>

<section class="section section--tight">
  <div class="container container--narrow">
    <?php if ($code !== '' && !$cert): ?>
      <div class="empty-state" role="status">
        <?= icon('cert') ?>
        <h2>Không tìm thấy mã “<?= e($code) ?>”</h2>
        <p>Vui lòng kiểm tra lại mã (gồm chữ và số, ví dụ VNP-2610-0001). Nếu vẫn không tìm thấy, hãy liên hệ để được hỗ trợ đối chiếu.</p>
        <div class="empty-state__actions">
          <?php if (cfg('zalo')): ?><a class="btn btn--primary" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener">Hỏi qua Zalo</a><?php endif; ?>
          <a class="btn btn--outline" href="<?= e(url('lien-he')) ?>">Liên hệ</a>
        </div>
      </div>
    <?php elseif ($cert): ?>
      <?php if (!empty($cert['mau'])): ?>
        <p class="notice">Đây là <strong>dữ liệu mẫu</strong> để minh họa tính năng tra cứu.</p>
      <?php endif; ?>
      <article class="cert-result card" role="status">
        <div class="cert-result__head">
          <span class="cert-result__badge"><?= icon('check') ?>Mã hợp lệ</span>
          <h2>Giấy kiểm định <span class="cert-code"><?= e($cert['code']) ?></span></h2>
        </div>
        <div class="cert-result__grid">
          <button type="button" class="cert-result__img" data-lightbox-src="<?= e(img_url($cert['image'])) ?>" aria-label="Phóng to ảnh giấy kiểm định">
            <img src="<?= e(img_url($cert['image'])) ?>" alt="Ảnh giấy kiểm định <?= e($cert['code']) ?>" width="1000" height="700">
            <span class="cert-result__zoom"><?= icon('zoom') ?></span>
          </button>
          <dl class="spec-list">
            <div><dt>Kết luận</dt><dd><strong><?= e($cert['result']) ?></strong></dd></div>
            <?php foreach (($cert['details'] ?? []) as $k => $v): ?>
              <div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div>
            <?php endforeach; ?>
            <div><dt>Đơn vị kiểm định</dt><dd><?= e($cert['lab']) ?></dd></div>
            <div><dt>Ngày cấp</dt><dd><?= e(format_date($cert['date'])) ?></dd></div>
          </dl>
        </div>
        <?php if ($cert_p): ?>
          <div class="cert-result__product">
            <p class="eyebrow">Sản phẩm tương ứng</p>
            <a class="hold-product" href="<?= e(product_url($cert_p)) ?>">
              <img src="<?= e(product_image($cert_p)) ?>" alt="" width="96" height="96">
              <span><strong><?= e($cert_p['name']) ?></strong><small><?= is_sold($cert_p) ? 'Đã có chủ' : e(format_price($cert_p['price'])) ?></small></span>
            </a>
          </div>
        <?php endif; ?>
      </article>
    <?php endif; ?>

    <div class="cert-help">
      <h2 class="section-title section-title--sm">Vì sao nên kiểm tra giấy kiểm định?</h2>
      <div class="prose">
        <p>Ngọc phỉ thúy trên thị trường có thể là <strong>loại A</strong> (tự nhiên, không xử lý), <strong>loại B</strong> (đã tẩy và bơm keo) hoặc <strong>loại C</strong> (đã nhuộm màu). Bằng mắt thường rất khó phân biệt – giấy kiểm định từ phòng kiểm định đá quý uy tín là căn cứ đáng tin cậy nhất.</p>
        <ul>
          <li>Mã kiểm định thường in ở góc trên hoặc dưới của giấy.</li>
          <li>Ảnh trên giấy cần khớp với hình dáng, màu sắc viên ngọc bạn nhận.</li>
          <li>Kích thước, khối lượng ghi trên giấy cần trùng với sản phẩm thực tế.</li>
        </ul>
        <?php if ($guide): ?><p><a href="<?= e(article_url($guide)) ?>">Đọc thêm: <?= e($guide['title']) ?></a></p><?php endif; ?>
      </div>
    </div>
  </div>
</section>


<?php include ROOT . '/includes/footer.php'; ?>
