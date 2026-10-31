<?php
/**
 * Lần đầu mở trang quản trị: tạo tài khoản Quản lý.
 * - Chạy thử trên máy (php -S): tạo ngay.
 * - Trên hosting: cần "mã cài đặt" trong file data/quan-tri/ma-cai-dat.txt (mở bằng cPanel → File Manager)
 *   để người lạ không thể tạo tài khoản trước chủ cửa hàng.
 */
defined('ADMIN') || exit;

$local    = PHP_SAPI === 'cli-server';
$codeFile = adm_dir() . '/ma-cai-dat.txt';
if (!$local && !is_file($codeFile)) {
    @file_put_contents($codeFile, strtoupper(substr(bin2hex(random_bytes(6)), 0, 10)) . "\n");
    @chmod($codeFile, 0600);
}
$setupCode = $local ? '' : trim((string) @file_get_contents($codeFile));

$err = [];
$old = ['name' => '', 'user' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = in_str('ho-ten', 60);
    $old['user'] = mb_strtolower(in_str('ten', 40));
    $pw  = (string) ($_POST['mat-khau'] ?? '');
    $pw2 = (string) ($_POST['mat-khau-2'] ?? '');
    if (!adm_csrf_ok()) {
        $err[] = 'Trang đã mở quá lâu, vui lòng nhập lại.';
    }
    if (!$local && ($setupCode === '' || !hash_equals(strtoupper($setupCode), strtoupper(in_str('ma', 20))))) {
        $err[] = 'Mã cài đặt chưa đúng.';
        adm_login_failed('cai-dat');
    }
    if (adm_login_locked('cai-dat') > 0) {
        $err = ['Nhập sai quá nhiều lần. Vui lòng thử lại sau ' . ADM_LOCK_MINUTES . ' phút.'];
    }
    if (mb_strlen($old['name']) < 2) {
        $err[] = 'Vui lòng nhập tên hiển thị.';
    }
    if (!preg_match('/^[a-z0-9._-]{3,30}$/', $old['user'])) {
        $err[] = 'Tên đăng nhập 3–30 ký tự, chỉ gồm chữ không dấu, số, dấu chấm hoặc gạch.';
    }
    if ($p = adm_password_problem($pw, $pw2)) {
        $err[] = $p;
    }
    if (!$err) {
        adm_write('tai-khoan', function (array &$all) use ($old, $pw) {
            $all[$old['user']] = ['name' => $old['name'], 'role' => 'quan-ly', 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'pv' => 1, 'created' => time()];
        });
        @unlink($codeFile);
        adm_login($old['user'], true);
        adm_log('Tạo tài khoản quản lý đầu tiên', $old['user']);
        flash('Đã tạo tài khoản. Chào mừng bạn đến trang quản trị!');
        redirect(adm_url());
    }
}

adm_bare_head('Cài đặt trang quản trị');
?>
    <h1 class="adm-auth__title">Tạo tài khoản quản lý</h1>
    <p class="adm-auth__sub">Lần đầu mở trang quản trị – hãy tạo tài khoản cho chủ cửa hàng.</p>
    <?php if ($err): ?><div class="adm-flash adm-flash--error" role="alert"><?= adm_icon('alert') ?><p><?= implode('<br>', array_map('e', $err)) ?></p></div><?php endif; ?>
    <?php if (!$local): ?>
      <?= adm_notice('<p><strong>Mã cài đặt</strong> nằm trong file <code>data/quan-tri/ma-cai-dat.txt</code> trên hosting.<br>Mở <strong>cPanel → File Manager</strong>, vào thư mục website → <code>data</code> → <code>quan-tri</code>, mở file đó và chép mã vào ô dưới đây.</p>') ?>
    <?php endif; ?>
    <form method="post" action="<?= e(adm_url()) ?>" class="adm-auth__form">
      <?= adm_csrf_field() ?>
      <?php if (!$local): ?><?= f_text('ma', 'Mã cài đặt', '', ['required' => true, 'autocomplete' => 'off', 'attrs' => 'autocapitalize="characters"']) ?><?php endif; ?>
      <?= f_text('ho-ten', 'Tên hiển thị', $old['name'], ['required' => true, 'placeholder' => 'VD: Chị Phương', 'autocomplete' => 'name']) ?>
      <?= f_text('ten', 'Tên đăng nhập', $old['user'], ['required' => true, 'placeholder' => 'VD: phuong', 'autocomplete' => 'username', 'hint' => 'Chữ không dấu, viết liền (dùng mỗi lần đăng nhập).', 'attrs' => 'autocapitalize="none" spellcheck="false"']) ?>
      <div class="af">
        <label class="af__label" for="f-mat-khau">Mật khẩu</label>
        <div class="ai-group"><input class="ai" type="password" id="f-mat-khau" name="mat-khau" required minlength="8" autocomplete="new-password"><button type="button" class="ai-group__btn" data-show-pass aria-label="Hiện mật khẩu"><?= icon('eye') ?></button></div>
        <p class="af__hint">Ít nhất 8 ký tự. Nên kết hợp chữ, số – không dùng ngày sinh, số điện thoại.</p>
      </div>
      <?= f_text('mat-khau-2', 'Nhập lại mật khẩu', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password']) ?>
      <button type="submit" class="btn btn--primary btn--block" data-submit>Tạo tài khoản &amp; vào trang quản trị</button>
    </form>
<?php
adm_bare_foot();
