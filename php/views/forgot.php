<?php
Layout::start('Forgot password · OurCircle', null, 'auth-page');
?>
<div class="auth-card">
  <h1>Reset with email</h1>
  <p>We’ll send a one-hour link if this email is on a circle. When mail is not connected, the link is saved as <code>password-reset.txt</code> next to the database (not on the public web). We never say whether the email exists.</p>
  <?php Layout::flash(); ?>
  <form method="post" action="/forgot">
    <?= Http::csrfField() ?>
    <label>Email</label>
    <input name="email" type="email" required autocomplete="username" />
    <p><button class="btn wide" type="submit">Send reset link</button></p>
  </form>
  <h2>Or use a recovery code</h2>
  <p>If you turned on 2FA, you were shown one-time codes. That also works if email isn’t set up on this site yet.</p>
  <form method="post" action="/forgot/code">
    <?= Http::csrfField() ?>
    <label>Email</label>
    <input name="email" type="email" required autocomplete="username" />
    <label>Recovery code</label>
    <input name="recovery_code" required placeholder="xxxx-xxxx" autocomplete="off" />
    <label>New password (8+)</label>
    <input name="password" type="password" required minlength="8" autocomplete="new-password" />
    <p><button class="btn ghost wide" type="submit">Reset with recovery code</button></p>
  </form>
  <p><a href="/login">Back to sign in</a></p>
</div>
<?php Layout::end();
