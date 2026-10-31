<?php
/** Tài khoản: đổi mật khẩu của mình; Quản lý thêm / khóa / đặt lại mật khẩu cho nhân viên */
defined('ADMIN') || exit;

$users = adm_users();
$owner = adm_is_owner();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string) ($_POST['act'] ?? '');
    if ($act === 'doi-mat-khau') {
        $u = $users[$me['username']];
        if (!password_verify((string) ($_POST['cu'] ?? ''), $u['hash'])) {
            adm_back('tai-khoan', [], 'Mật khẩu hiện tại chưa đúng.', 'error');
        }
        if ($p = adm_password_problem((string) ($_POST['moi'] ?? ''), (string) ($_POST['moi2'] ?? ''))) {
            adm_back('tai-khoan', [], $p, 'error');
        }
        adm_set_password($me['username'], (string) $_POST['moi']);
        adm_login($me['username'], !empty($_COOKIE['vnp_adm']));       // giữ đăng nhập trên máy này
        adm_log('Đổi mật khẩu');
        adm_back('tai-khoan', [], 'Đã đổi mật khẩu. Các thiết bị khác đang đăng nhập bằng tài khoản này sẽ phải đăng nhập lại.');
    }
    if ($act === 'ten-hien-thi') {
        $name = in_str('ho-ten', 60);
        if (mb_strlen($name) >= 2) {
            adm_write('tai-khoan', function (array &$all) use ($me, $name) { $all[$me['username']]['name'] = $name; });
        }
        adm_back('tai-khoan', [], 'Đã lưu tên hiển thị.');
    }
    if (!$owner) {
        adm_back('tai-khoan');
    }
    if ($act === 'them') {
        $user = mb_strtolower(in_str('nv-ten', 40));
        $name = in_str('nv-ho-ten', 60);
        $role = isset(adm_roles()[$_POST['vai-tro'] ?? '']) ? (string) $_POST['vai-tro'] : 'ban-hang';
        if (!preg_match('/^[a-z0-9._-]{3,30}$/', $user)) {
            adm_back('tai-khoan', [], 'Tên đăng nhập 3–30 ký tự, chỉ chữ không dấu, số, dấu chấm hoặc gạch.', 'error');
        }
        if (isset($users[$user])) {
            adm_back('tai-khoan', [], 'Tên đăng nhập “' . $user . '” đã có người dùng.', 'error');
        }
        if (mb_strlen($name) < 2) {
            adm_back('tai-khoan', [], 'Vui lòng nhập tên hiển thị.', 'error');
        }
        if ($p = adm_password_problem((string) ($_POST['nv-mat-khau'] ?? ''), (string) ($_POST['nv-mat-khau'] ?? ''))) {
            adm_back('tai-khoan', [], $p, 'error');
        }
        adm_write('tai-khoan', function (array &$all) use ($user, $name, $role) {
            $all[$user] = ['name' => $name, 'role' => $role, 'hash' => password_hash((string) $_POST['nv-mat-khau'], PASSWORD_DEFAULT), 'pv' => 1, 'created' => time()];
        });
        adm_log('Thêm tài khoản', $user . ' (' . $role . ')');
        adm_back('tai-khoan', [], 'Đã tạo tài khoản “' . $user . '”. Gửi tên đăng nhập và mật khẩu cho người dùng (nên đổi mật khẩu sau khi đăng nhập).');
    }
    $target = (string) ($_POST['ten'] ?? '');
    if (!isset($users[$target]) || $target === $me['username']) {
        adm_back('tai-khoan', [], 'Không thao tác được với tài khoản này.', 'error');
    }
    if ($act === 'dat-lai') {
        if ($p = adm_password_problem((string) ($_POST['mat-khau'] ?? ''), (string) ($_POST['mat-khau'] ?? ''))) {
            adm_back('tai-khoan', [], $p, 'error');
        }
        adm_set_password($target, (string) $_POST['mat-khau']);
        adm_log('Đặt lại mật khẩu', $target);
        adm_back('tai-khoan', [], 'Đã đặt lại mật khẩu cho “' . $target . '”.');
    }
    if ($act === 'khoa' || $act === 'mo-khoa') {
        adm_write('tai-khoan', function (array &$all) use ($target, $act) {
            if ($act === 'khoa') {
                $all[$target]['disabled'] = true;
                $all[$target]['pv'] = (int) ($all[$target]['pv'] ?? 0) + 1;     // đăng xuất ngay mọi thiết bị
            } else {
                unset($all[$target]['disabled']);
            }
        });
        adm_log($act === 'khoa' ? 'Khóa tài khoản' : 'Mở khóa tài khoản', $target);
        adm_back('tai-khoan', [], $act === 'khoa' ? 'Đã khóa “' . $target . '” – tài khoản bị đăng xuất ngay.' : 'Đã mở khóa “' . $target . '”.');
    }
    if ($act === 'vai-tro' && isset(adm_roles()[$_POST['vai-tro'] ?? ''])) {
        adm_write('tai-khoan', function (array &$all) use ($target) { $all[$target]['role'] = (string) $_POST['vai-tro']; });
        adm_log('Đổi quyền', $target . ' → ' . $_POST['vai-tro']);
        adm_back('tai-khoan', [], 'Đã đổi quyền cho “' . $target . '”.');
    }
    if ($act === 'xoa') {
        adm_write('tai-khoan', function (array &$all) use ($target) { unset($all[$target]); });
        adm_log('Xóa tài khoản', $target);
        adm_back('tai-khoan', [], 'Đã xóa tài khoản “' . $target . '”.');
    }
    adm_back('tai-khoan');
}

adm_head('Tài khoản', 'tai-khoan');
?>
<div class="grid-2">
  <section class="card">
    <h2>Đổi mật khẩu của bạn</h2>
    <p class="card__sub">Đăng nhập: <strong><?= e($me['username']) ?></strong> · <?= e(adm_roles()[$me['role']] ?? '') ?></p>
    <form method="post" action="<?= e(adm_url('tai-khoan')) ?>" class="stack">
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="doi-mat-khau">
      <?= f_text('cu', 'Mật khẩu hiện tại', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
      <?= f_text('moi', 'Mật khẩu mới', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'hint' => 'Ít nhất 8 ký tự.']) ?>
      <?= f_text('moi2', 'Nhập lại mật khẩu mới', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password']) ?>
      <button type="submit" class="btn btn--primary"><?= icon('lock') ?>Đổi mật khẩu</button>
    </form>
  </section>
  <section class="card">
    <h2>Tên hiển thị</h2>
    <form method="post" action="<?= e(adm_url('tai-khoan')) ?>" class="stack" style="margin-top:12px">
      <?= adm_csrf_field() ?><input type="hidden" name="act" value="ten-hien-thi">
      <?= f_text('ho-ten', 'Tên hiển thị', $me['name'], ['required' => true]) ?>
      <button type="submit" class="btn btn--outline"><?= adm_icon('save') ?>Lưu tên</button>
    </form>
    <?= adm_notice('Bảo mật: không chia sẻ mật khẩu qua tin nhắn công khai; mỗi người dùng một tài khoản riêng để nhật ký ghi đúng ai đã thao tác.') ?>
  </section>
</div>

<?php if ($owner): ?>
<section class="card card--flush">
  <div class="card__head"><h2>Tất cả tài khoản</h2></div>
  <div class="tbl-wrap"><table class="tbl tbl--cards">
    <thead><tr><th>Tài khoản</th><th>Quyền</th><th>Đăng nhập gần nhất</th><th class="act"></th></tr></thead>
    <tbody>
    <?php foreach ($users as $name => $u): $self = $name === $me['username']; ?>
      <tr<?= !empty($u['disabled']) ? ' class="is-dim"' : '' ?>>
        <td class="tbl__first"><strong class="tbl__main"><?= e($u['name']) ?><?= $self ? ' (bạn)' : '' ?></strong><span class="tbl__sub"><?= e($name) ?><?= !empty($u['disabled']) ? ' · đã khóa' : '' ?></span></td>
        <td data-label="Quyền"><?php if ($self): ?><?= adm_badge(($u['role'] ?? '') === 'quan-ly' ? 'Quản lý' : 'Bán hàng', 'dark') ?><?php else: ?>
          <form method="post" action="<?= e(adm_url('tai-khoan')) ?>" class="csv-status" data-autosubmit><?= adm_csrf_field() ?><input type="hidden" name="act" value="vai-tro"><input type="hidden" name="ten" value="<?= e($name) ?>">
            <select class="ai" name="vai-tro" aria-label="Quyền"><?php foreach (adm_roles() as $k => $l): ?><option value="<?= e($k) ?>"<?= ($u['role'] ?? '') === $k ? ' selected' : '' ?>><?= e(strtok($l, '–')) ?></option><?php endforeach; ?></select></form><?php endif; ?></td>
        <td data-label="Đăng nhập gần nhất"><?= e(!empty($u['last_login']) ? adm_ago((int) $u['last_login']) : 'Chưa') ?></td>
        <td class="act"><?php if (!$self): ?>
          <details class="inline-form"><summary class="btn btn--sm btn--outline">Đặt lại mật khẩu</summary>
            <form method="post" action="<?= e(adm_url('tai-khoan')) ?>" class="btn-row" style="margin-top:8px"><?= adm_csrf_field() ?><input type="hidden" name="act" value="dat-lai"><input type="hidden" name="ten" value="<?= e($name) ?>">
              <input class="ai" type="text" name="mat-khau" minlength="8" required placeholder="Mật khẩu mới" autocomplete="off" style="width:180px"><button type="submit" class="btn btn--sm btn--primary">Lưu</button></form></details>
          <form method="post" action="<?= e(adm_url('tai-khoan')) ?>" class="inline-form"><?= adm_csrf_field() ?><input type="hidden" name="ten" value="<?= e($name) ?>">
            <?php if (!empty($u['disabled'])): ?><button type="submit" name="act" value="mo-khoa" class="btn btn--sm btn--ghost">Mở khóa</button>
            <?php else: ?><button type="submit" name="act" value="khoa" class="btn btn--sm btn--ghost" data-confirm="Khóa tài khoản “<?= e($name) ?>”? Người này bị đăng xuất ngay.">Khóa</button><?php endif; ?>
            <button type="submit" name="act" value="xoa" class="btn btn--sm btn--ghost" data-confirm="Xóa hẳn tài khoản “<?= e($name) ?>”?">Xóa</button></form>
        <?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<section class="card">
  <h2>Thêm tài khoản</h2>
  <p class="card__sub" style="margin-top:4px">Nhân viên bán hàng chỉ xem và xử lý đơn hàng, giữ hàng, phiếu, lịch hẹn, tin nhắn – không sửa sản phẩm, cài đặt.</p>
  <form method="post" action="<?= e(adm_url('tai-khoan')) ?>">
    <?= adm_csrf_field() ?><input type="hidden" name="act" value="them">
    <div class="form-grid">
      <?= f_text('nv-ho-ten', 'Tên hiển thị', '', ['required' => true, 'placeholder' => 'VD: Bạn Hà – bán hàng']) ?>
      <?= f_text('nv-ten', 'Tên đăng nhập', '', ['required' => true, 'placeholder' => 'VD: ha', 'attrs' => 'autocapitalize="none" spellcheck="false"']) ?>
      <?= f_text('nv-mat-khau', 'Mật khẩu ban đầu', '', ['required' => true, 'autocomplete' => 'off', 'hint' => 'Ít nhất 8 ký tự – nhắc người dùng đổi sau khi đăng nhập.']) ?>
      <?= f_select('vai-tro', 'Quyền', 'ban-hang', adm_roles()) ?>
    </div>
    <button type="submit" class="btn btn--primary" style="margin-top:14px"><?= icon('plus') ?>Tạo tài khoản</button>
  </form>
</section>
<?php endif; ?>
<?php
adm_foot();
