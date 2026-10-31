<?php
/** Đăng nhập trang quản trị */
defined('ADMIN') || exit;

if (adm_user()) {
    redirect(adm_url());
}

$back  = (string) ($_GET['ve'] ?? $_POST['ve'] ?? '');
$back  = preg_match('#^' . preg_quote(base_path() . '/quan-tri/', '#') . '[^\s]*$#', $back) ? $back : '';
$error = '';
$userIn = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userIn = mb_strtolower(in_str('ten', 60));
    $pass   = (string) ($_POST['mat-khau'] ?? '');
    $wait   = adm_login_locked($userIn);
    if (!adm_csrf_ok()) {
        $error = 'Trang đăng nhập đã mở quá lâu. Vui lòng nhập lại.';
    } elseif ($wait > 0) {
        $error = 'Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau ' . max(1, (int) ceil($wait / 60)) . ' phút.';
    } else {
        $u = adm_users()[$userIn] ?? null;
        // luôn kiểm tra mật khẩu (kể cả khi sai tên) để thời gian phản hồi như nhau
        $ok = password_verify($pass, $u['hash'] ?? '$2y$10$0000000000000000000000000000000000000000000000000000.');
        if ($u && $ok && empty($u['disabled'])) {
            adm_login_ok($userIn);
            adm_login($userIn, in_bool('nho'));
            if (password_needs_rehash($u['hash'], PASSWORD_DEFAULT)) {
                adm_write('tai-khoan', function (array &$all) use ($userIn, $pass) {
                    $all[$userIn]['hash'] = password_hash($pass, PASSWORD_DEFAULT);
                });
            }
            adm_log('Đăng nhập');
            redirect($back ?: adm_url());
        }
        adm_login_failed($userIn);
        usleep(400000);
        $error = $u && !empty($u['disabled']) ? 'Tài khoản này đã bị khóa. Vui lòng liên hệ người quản lý.' : 'Tên đăng nhập hoặc mật khẩu chưa đúng.';
    }
}

adm_bare_head('Đăng nhập quản trị');
$f = flash();
?>
    <h1 class="adm-auth__title">Đăng nhập quản trị</h1>
    <p class="adm-auth__sub"><?= e(cfg('site_name')) ?></p>
    <?php if ($f): ?><div class="adm-flash adm-flash--<?= e($f['type']) ?>" role="status"><p><?= e($f['message']) ?></p></div><?php endif; ?>
    <?php if ($error): ?><div class="adm-flash adm-flash--error" role="alert"><?= adm_icon('alert') ?><p><?= e($error) ?></p></div><?php endif; ?>
    <form method="post" action="<?= e(adm_url('dang-nhap')) ?>" class="adm-auth__form">
      <?= adm_csrf_field() ?>
      <input type="hidden" name="ve" value="<?= e($back) ?>">
      <?= f_text('ten', 'Tên đăng nhập', $userIn, ['required' => true, 'autocomplete' => 'username', 'attrs' => 'autocapitalize="none" spellcheck="false"' . ($userIn ? '' : ' autofocus')]) ?>
      <div class="af">
        <label class="af__label" for="f-mat-khau">Mật khẩu</label>
        <div class="ai-group"><input class="ai" type="password" id="f-mat-khau" name="mat-khau" required autocomplete="current-password"<?= $userIn ? ' autofocus' : '' ?>><button type="button" class="ai-group__btn" data-show-pass aria-label="Hiện mật khẩu"><?= icon('eye') ?></button></div>
      </div>
      <label class="check"><input type="checkbox" name="nho" value="1" checked><span>Ghi nhớ đăng nhập trên thiết bị này (30 ngày)</span></label>
      <p class="af__hint">Bỏ chọn nếu đang dùng máy tính chung, máy của người khác.</p>
      <button type="submit" class="btn btn--primary btn--block" data-submit>Đăng nhập</button>
    </form>
    <details class="adm-auth__help">
      <summary>Quên mật khẩu?</summary>
      <p>Nhờ người có tài khoản <strong>Quản lý</strong> đặt lại mật khẩu cho bạn trong mục <em>Tài khoản</em>.</p>
      <p>Nếu là chủ cửa hàng và quên mật khẩu: vào <strong>cPanel → File Manager</strong>, xóa file <code>data/quan-tri/tai-khoan.json</code>, rồi mở lại trang này để tạo tài khoản mới (dữ liệu đơn hàng, sản phẩm không bị ảnh hưởng).</p>
    </details>
<?php
adm_bare_foot();
