<?php
require dirname(__DIR__) . '/app.php';
require dirname(__DIR__) . '/layout.php';
$user = current_user();
if ($user && in_array($auth_page, ['login', 'register'])) redirect(home($user['role']));
$titles = ['login' => 'Welcome back', 'register' => 'Create your account', 'forgot' => 'Forgot your password?', 'reset' => 'Set a new password'];
$paths = ['login' => 'pages/auth/login.php', 'register' => 'pages/auth/signup.php', 'forgot' => 'pages/auth/forgot-password.php', 'reset' => 'pages/auth/reset-password.php'];
if ($auth_page === 'reset' && (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_email']) || ($_SESSION['reset_verified_at'] ?? 0) < time() - 600)) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_verified_at']);
    redirect('pages/auth/forgot-password.php');
}
page_start($titles[$auth_page]);
?><section class="panel auth-panel"><span class="eyebrow">Verdant Haven</span>
    <h1><?= h($titles[$auth_page]) ?></h1>
    <?php if ($auth_page === 'forgot'): ?><p class="muted">Enter your account email address to continue.</p><?php endif;
    $return = $paths[$auth_page];
    post_form($auth_page, $return);
    if ($auth_page === 'register'): ?>
        <label>Full name<input name="name" maxlength="120" required autocomplete="name" value="<?= h(old('name')) ?>"></label>
        <label>Mobile number<input name="phone" type="tel" pattern="01[3-9][0-9]{8}" maxlength="11" required autocomplete="tel" placeholder="01700000000" value="<?= h(old('phone')) ?>"></label>
    <?php endif;
    if ($auth_page === 'login'): ?>
        <label>Email or phone<input name="login" maxlength="190" required autocomplete="username" value="<?= h(old('login')) ?>"></label>
    <?php elseif ($auth_page !== 'reset'): ?>
        <label>Email address<input name="email" type="email" maxlength="190" required autocomplete="email" value="<?= h(old('email')) ?>"></label>
    <?php else: ?>
        <p class="muted">Account found: <?= h($_SESSION['reset_email']) ?></p>
    <?php endif;
    if ($auth_page !== 'forgot'): ?>
        <label>Password<input name="password" type="password" minlength="<?= $auth_page === 'login' ? 1 : 8 ?>" <?= $auth_page === 'login' ? '' : 'maxlength="72"' ?> required autocomplete="<?= $auth_page === 'login' ? 'current-password' : 'new-password' ?>"></label>
    <?php endif;
    if (in_array($auth_page, ['register', 'reset'])): ?>
        <label>Confirm password<input name="confirm_password" type="password" minlength="8" maxlength="72" required autocomplete="new-password"></label>
    <?php endif; ?>
    <button class="btn"><?= h(['login' => 'Log in', 'register' => 'Sign up', 'forgot' => 'Verify', 'reset' => 'Save password'][$auth_page]) ?></button></form>
    <div class="row space"><?php if ($auth_page === 'login'): ?><a href="<?= h(url('pages/auth/forgot-password.php')) ?>">Forgot password?</a><a href="<?= h(url('pages/auth/signup.php')) ?>">Create an account</a><?php else: ?><a href="<?= h(url('pages/auth/login.php')) ?>">Back to log in</a><?php endif; ?></div>
</section><?php page_end(); ?>