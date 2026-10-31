<?php
/** Phần chân trang dùng chung */
$flash = flash();
?>
</main>

<?php if (!empty($mini_footer)): ?>
<!-- Trang giỏ hàng / thanh toán: chân trang gọn (giống các trang thanh toán chuyên nghiệp) -->
<footer class="site-footer site-footer--mini">
  <div class="container mini-footer">
    <p>© <?= date('Y') ?> <?= e(cfg('site_name')) ?><?php if (cfg('company_name')): ?> · <?= e(cfg('company_name')) ?><?php endif; ?></p>
    <ul class="mini-footer__links">
      <?php if (cfg('hotline')): ?><li><?= icon('phone') ?><a href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a></li><?php endif; ?>
      <li><a href="<?= e(url('chinh-sach')) ?>#doi-tra">Đổi trả</a></li>
      <li><a href="<?= e(url('chinh-sach')) ?>#bao-mat">Bảo mật</a></li>
      <li><a href="<?= e(url('tra-cuu-don-hang')) ?>">Tra cứu đơn hàng</a></li>
    </ul>
  </div>
</footer>
<?php else: ?>
<footer class="site-footer">
  <div class="footer-commit">
    <div class="container footer-commit__grid">
      <?php foreach (['gem', 'truck', 'return', 'shield'] as $i => $ic): ?>
        <div class="commit"><?= icon($ic) ?><div><strong><?= e(txt('footer.c' . ($i + 1) . '_title')) ?></strong><span><?= e(txt('footer.c' . ($i + 1) . '_text')) ?></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="footer-brand__logo" href="<?= e(url()) ?>">
        <img src="<?= e(img_url(site_logo())) ?>" alt="<?= e(cfg('site_name')) ?>" width="183" height="160" loading="lazy">
      </a>
      <p><?= e(txt('footer.brand_text')) ?></p>
      <?php if ($socials = social_links()): ?>
        <ul class="socials" aria-label="Mạng xã hội">
          <?php foreach ($socials as $s): ?>
            <li><a href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>"><?= icon($s['key']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <details class="footer-col footer-acc" open>
      <summary><h2 class="footer-title">Sản phẩm</h2><?= icon('down') ?></summary>
      <ul class="footer-links">
        <?php foreach ($CATEGORIES as $slug => $c): ?>
          <li><a href="<?= e(category_url($slug)) ?>"><?= e($c['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </details>

    <details class="footer-col footer-acc" open>
      <summary><h2 class="footer-title">Hỗ trợ khách hàng</h2><?= icon('down') ?></summary>
      <ul class="footer-links">
        <li><a href="<?= e(url('chinh-sach')) ?>#bao-hanh">Chính sách bảo hành</a></li>
        <li><a href="<?= e(url('chinh-sach')) ?>#doi-tra">Chính sách đổi trả</a></li>
        <li><a href="<?= e(url('chinh-sach')) ?>#van-chuyen">Chính sách vận chuyển</a></li>
        <li><a href="<?= e(url('chinh-sach')) ?>#thanh-toan">Hình thức thanh toán</a></li>
        <li><a href="<?= e(url('chinh-sach')) ?>#dat-coc">Chính sách đặt cọc – giữ hàng</a></li>
        <li><a href="<?= e(url('chinh-sach')) ?>#bao-mat">Bảo mật thông tin</a></li>
        <li><a href="<?= e(url('tra-cuu-don-hang')) ?>">Tra cứu đơn hàng</a></li>
        <li><a href="<?= e(url('tra-cuu-kiem-dinh')) ?>">Tra cứu giấy kiểm định</a></li>
        <li><a href="<?= e(url('dat-lich')) ?>">Đặt lịch xem ngọc</a></li>
        <li><a href="<?= e(url('qua-tang')) ?>">Chọn quà tặng</a></li>
        <li><a href="<?= e(url('cam-nang')) ?>">Cẩm nang ngọc phỉ thúy</a></li>
        <li><a href="<?= e(url('gioi-thieu')) ?>">Về chúng tôi</a></li>
      </ul>
    </details>

    <div class="footer-col">
      <h2 class="footer-title">Liên hệ</h2>
      <ul class="footer-contact">
        <?php if (cfg('address')): ?><li><?= icon('pin') ?><span><?= e(cfg('address')) ?></span></li><?php endif; ?>
        <?php if (cfg('hotline')): ?><li><?= icon('phone') ?><a href="<?= e(tel_href(cfg('hotline'))) ?>"><?= e(cfg('hotline')) ?></a></li><?php endif; ?>
        <?php if (cfg('email')): ?><li><?= icon('mail') ?><a href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></li><?php endif; ?>
        <?php if (cfg('working_hours')): ?><li><?= icon('clock') ?><span><?= e(cfg('working_hours')) ?></span></li><?php endif; ?>
      </ul>

      <?php if (cfg('zalo_oa') || cfg('zalo')): ?>
      <div class="zalo-follow">
        <p class="zalo-follow__title"><?= e(txt('footer.zalo_title')) ?></p>
        <p class="zalo-follow__text"><?= e(txt('footer.zalo_text')) ?></p>
        <a class="btn btn--gold" href="<?= e(cfg('zalo_oa') ?: zalo_href()) ?>" target="_blank" rel="noopener"><span class="zalo-mark" aria-hidden="true">Zalo</span><?= cfg('zalo_oa') ? 'Quan tâm Zalo OA' : 'Kết nối Zalo' ?></a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container footer-bottom__inner">
      <p>
        <strong><?= e(cfg('company_name')) ?></strong>
        <?php if (cfg('tax_code')): ?> · MST/GPKD: <?= e(cfg('tax_code')) ?><?php endif; ?>
        <?php if (cfg('license_info')): ?> · <?= e(cfg('license_info')) ?><?php endif; ?>
      </p>
      <p>© <?= date('Y') ?> <?= e(cfg('site_name')) ?>. Bảo lưu mọi quyền.</p>
      <?php if (cfg('bct_url')): ?><a class="footer-bct" href="<?= e(cfg('bct_url')) ?>" target="_blank" rel="noopener"><img src="https://online.gov.vn/Content/EndUser/LogoCCDVSaleNoti/logoSaleNoti.png" alt="Đã thông báo Bộ Công Thương" width="120" height="45" loading="lazy"></a><?php endif; ?>
    </div>
  </div>
</footer>
<?php endif; ?>

<div class="float-contact" aria-label="Liên hệ nhanh">
  <?php if (cfg('zalo')): ?>
    <a class="float-btn float-btn--zalo" href="<?= e(zalo_href()) ?>" target="_blank" rel="noopener"><span aria-hidden="true">Zalo</span><span class="sr-only">Chat qua Zalo</span></a>
  <?php endif; ?>
  <?php if (cfg('messenger')): ?>
    <a class="float-btn float-btn--messenger" href="<?= e(cfg('messenger')) ?>" target="_blank" rel="noopener"><?= icon('messenger') ?><span class="sr-only">Nhắn tin Messenger</span></a>
  <?php endif; ?>
  <?php if (cfg('hotline')): ?>
    <a class="float-btn float-btn--phone" href="<?= e(tel_href(cfg('hotline'))) ?>"><?= icon('phone') ?><span class="sr-only">Gọi hotline <?= e(cfg('hotline')) ?></span></a>
  <?php endif; ?>
  <button type="button" class="float-btn float-btn--top" data-back-to-top hidden><?= icon('arrow-up') ?><span class="sr-only">Lên đầu trang</span></button>
</div>

<dialog class="modal modal--wide" id="quick-view" aria-labelledby="qv-title">
  <button type="button" class="icon-btn modal__close" data-dialog-close><?= icon('close') ?><span class="sr-only">Đóng</span></button>
  <div class="modal__body" data-qv-body></div>
</dialog>

<div class="toast-region" aria-live="polite" aria-atomic="true" data-toast-region<?php if ($flash): ?> data-flash="<?= e($flash['message']) ?>" data-flash-type="<?= e($flash['type']) ?>"<?php endif; ?>></div>

<?php if (STATIC_DEMO): ?><script src="<?= e(asset('js/demo-static.js')) ?>" defer></script><?php endif; ?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
<?php foreach (($foot_scripts ?? []) as $src): ?><script src="<?= e(asset($src)) ?>" defer></script><?php endforeach; ?>
</body>
</html>
