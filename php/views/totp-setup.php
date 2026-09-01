<?php
Layout::start('Turn on 2FA', $user);
?>
<div class="wrap app-main">
  <?php Layout::flash(); ?>
  <p>Scan the square with Google Authenticator, Authy, 1Password, or iCloud Keychain. Then enter the 6-digit code to confirm.</p>
  <p><a class="btn" href="<?= Http::e($uri) ?>">Open authenticator app</a></p>
  <p class="muted">On a phone, “Open authenticator app” should hand the key to the app. If nothing happens (usual on a computer), paste the key.</p>
  <p>Setup key</p>
  <p class="otp-secret"><?= Http::e($secret) ?></p>
  <form method="post">
    <?= Http::csrfField() ?>
    <label>6-digit code</label>
    <input class="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" required />
    <p><button class="btn" type="submit">Confirm and turn on 2FA</button>
       <a class="btn ghost" href="/account">Cancel</a></p>
  </form>
</div>
<?php Layout::end($user);
