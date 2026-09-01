<?php
Layout::start('Sign in · OurCircle', null, 'auth-page');
$next = $next ?? '/home';
$showDemo = !empty($showDemo);
$needOtp = !empty($needOtp);
?>
<div class="auth-card">
  <a class="brand" href="<?= Http::e(Http::baseUrl()) ?>"><img src="/static/img/logo.png" alt="" /><div><strong>OurCircle</strong><span>Sign in</span></div></a>
  <?php Layout::flash(); ?>
  <form method="post">
    <?= Http::csrfField() ?>
    <input type="hidden" name="next" value="<?= Http::e($next) ?>" />
    <?php if ($needOtp): ?>
      <p>Enter the 6-digit code from your authenticator app.</p>
      <label>6-digit code</label>
      <input class="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" required />
    <?php else: ?>
      <label>Email</label>
      <input name="email" type="email" required autocomplete="username" />
      <label>Password</label>
      <input name="password" type="password" required autocomplete="current-password" />
    <?php endif; ?>
    <p><button class="btn wide" type="submit">Sign in</button></p>
  </form>
  <?php if ($showDemo): ?>
    <p class="muted">Demo circle: family@ourcircle.app / password123</p>
  <?php endif; ?>
  <p class="disclaimer">This application offers guidance, not a guarantee.</p>
  <p><a href="/forgot">Forgot password</a> · <a href="/signup">Start a circle</a></p>
</div>
<?php Layout::end();
