<?php
/** Nhật ký thao tác trong trang quản trị (ai làm gì, lúc nào) – 2.000 dòng gần nhất */
defined('ADMIN') || exit;

$log  = array_reverse(adm_read('nhat-ky'));
$who  = (string) ($_GET['ai'] ?? '');
$users = adm_users();
if ($who !== '' && isset($users[$who])) {
    $log = array_values(array_filter($log, fn($x) => ($x['u'] ?? '') === $who));
}
adm_head('Nhật ký thao tác', 'nhat-ky');
$chips = ['' => ['Tất cả', adm_url('nhat-ky')]];
foreach ($users as $k => $u) {
    $chips[$k] = [$u['name'], adm_url('nhat-ky', ['ai' => $k])];
}
?>
<div data-filter-scope>
  <?= adm_filterbar('Tìm thao tác…', $chips, $who) ?>
  <section class="card card--flush">
    <?php if (!$log): ?><div class="empty"><?= adm_icon('history') ?><p>Chưa có thao tác nào.</p></div><?php else: ?>
    <div class="tbl-wrap"><table class="tbl tbl--cards">
      <thead><tr><th>Lúc</th><th>Người làm</th><th>Thao tác</th></tr></thead>
      <tbody>
      <?php foreach (array_slice($log, 0, 500) as $x): ?>
        <tr data-filter-item>
          <td class="tbl__first"><span class="tbl__main"><?= e(adm_ago((int) $x['t'])) ?></span><span class="tbl__sub"><?= e(date('H:i:s d/m/Y', (int) $x['t'])) ?></span></td>
          <td data-label="Người làm"><?= e($x['n'] ?: $x['u']) ?></td>
          <td data-label="Thao tác"><span><strong><?= e($x['a']) ?></strong><?php if (!empty($x['d'])): ?><span class="tbl__sub"><?= e($x['d']) ?></span><?php endif; ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div><?php endif; ?>
  </section>
</div>
<?php
adm_foot();
