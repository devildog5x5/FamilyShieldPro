<?php
Layout::start('Account', $user);
?>
<div class="wrap app-main">
  <?php Layout::flash(); ?>
  <p><?= Http::e($user['email']) ?></p>
  <div class="grid-2">
    <form class="panel" method="post" action="/account/password">
      <?= Http::csrfField() ?>
      <h2>Password</h2>
      <label>Current password</label>
      <input name="current_password" type="password" required autocomplete="current-password" />
      <label>New password (8+)</label>
      <input name="password" type="password" required minlength="8" autocomplete="new-password" />
      <p><button class="btn" type="submit">Change password</button></p>
    </form>
    <form class="panel" method="post" action="/account/phone">
      <?= Http::csrfField() ?>
      <h2>Mobile and SMS</h2>
      <label>Mobile number (optional)</label>
      <input name="phone" type="tel" inputmode="tel" value="<?= Http::e($user['phone'] ?? '') ?>" placeholder="(555) 010-1234" autocomplete="tel" />
      <label class="check-row"><input type="checkbox" name="sms_opt_out" value="1" <?= !empty($user['sms_opt_out']) ? 'checked' : '' ?> /> Opt out of Family Shield Pro texts</label>
      <p><button class="btn" type="submit">Save mobile</button></p>
      <p class="disclaimer">Password reset uses your email, not this number. When SMS is connected, we can text circle invites and “Please call me before I pay” alerts. This is not a customer-service hotline. Reply STOP to opt out.</p>
    </form>
  </div>
  <div class="panel" style="margin-top:16px">
    <h2>Two-factor authentication</h2>
    <?php if (!empty($user['totp_enabled'])): ?>
      <p>2FA is on. Use your authenticator app after the password.</p>
    <?php else: ?>
      <p>2FA is off. An authenticator app (Google Authenticator, Authy, 1Password, iCloud Keychain) adds a second step after the password.</p>
      <p><a class="btn" href="/account/2fa/setup">Turn on 2FA</a></p>
    <?php endif; ?>
    <p>Password reset: email link, a file next to the database when mail is off, or a 2FA recovery code on the <a href="/forgot">forgot-password page</a>.</p>
  </div>
</div>
<?php Layout::end($user);
