<?php
/** Tin nhắn gửi từ trang Liên hệ */
defined('ADMIN') || exit;

$statuses = ['' => 'Mới – chưa trả lời', 'da-tra-loi' => 'Đã trả lời', 'luu' => 'Lưu lại', 'rac' => 'Tin rác'];
csv_status_post('lien-he', 'lien-he', $statuses);

$rows = csv_records('lien-he');
$loc  = (string) ($_GET['loc'] ?? 'moi');
$sets = [
    'moi' => ['Chưa trả lời', fn($r) => $r['_status'] === ''],
    ''    => ['Tất cả', fn($r) => $r['_status'] !== 'rac'],
    'rac' => ['Tin rác', fn($r) => $r['_status'] === 'rac'],
];
$loc  = isset($sets[$loc]) ? $loc : '';
$list = array_filter($rows, $sets[$loc][1]);
$chips = [];
foreach ($sets as $k => [$l, $fn]) {
    $chips[$k] = [$l, adm_url('lien-he', ['loc' => $k === '' ? 'tat-ca' : $k]), count(array_filter($rows, $fn))];
}

adm_head('Tin nhắn liên hệ', 'lien-he');
?>
<div data-filter-scope>
  <?= adm_filterbar('Tìm tên, số điện thoại, nội dung…', $chips, $loc) ?>
  <?php if (!$list): ?>
    <section class="card"><div class="empty"><?= adm_icon('inbox') ?><p>Không có tin nhắn nào trong mục này.</p></div></section>
  <?php endif; ?>
  <?php foreach ($list as $r): $ph = (string) ($r['Điện thoại'] ?? ''); ?>
    <section class="card" data-filter-item>
      <div class="card__head">
        <div><h2><?= e($r['Họ tên'] ?? '') ?></h2><span class="muted"><?= e(adm_ago((int) $r['_ts'])) ?></span></div>
        <?= csv_status_form('lien-he', $r, $statuses) ?>
      </div>
      <p class="msg-body"><?= e($r['Nội dung'] ?? '') ?></p>
      <div class="btn-row">
        <?php if ($ph !== ''): ?><a class="btn btn--sm btn--outline" href="<?= e(tel_href($ph)) ?>"><?= icon('phone') ?><?= e($ph) ?></a><a class="btn btn--sm btn--outline" href="<?= e(zalo_chat_url($ph)) ?>" target="_blank" rel="noopener"><?= icon('chat') ?>Zalo</a><?php endif; ?>
        <?php if (!empty($r['Email'])): ?><a class="btn btn--sm btn--outline" href="mailto:<?= e($r['Email']) ?>"><?= icon('mail') ?><?= e($r['Email']) ?></a><?php endif; ?>
      </div>
    </section>
  <?php endforeach; ?>
  <p class="empty" data-filter-empty hidden>Không có tin nhắn khớp từ khóa.</p>
</div>
<?php
adm_foot();
