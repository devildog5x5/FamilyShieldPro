<?php
Layout::start(($is_operator ?? false) ? 'Reset operator password · Family Shield Pro' : 'Choose a new password · OurCircle', null, 'auth-page');
?>
<div class="auth-card">
  <h1><?= !empty($is_operator) ? 'Reset operator password' : 'Choose a new password' ?></h1>
  <p>Pick a password at least 8 characters long.</p>
  <?php Layout::flash(); ?>
  <?php if ($error !== ''): ?>
    <p class="flash error"><?= Http::e($error) ?></p>
  <?php endif; ?>
  <?php if (!empty($show_form)): ?>
  <form method="post" action="/reset">
    <?= Http::csrfField() ?>
    <input type="hidden" name="token" value="<?= Http::e($token ?? '') ?>" />
    <label>New password</label>
    <input name="password" type="password" required minlength="8" autocomplete="new-password" />
    <label>Confirm password</label>
    <input name="password_confirm" type="password" required minlength="8" autocomplete="new-password" />
    <p><button class="btn wide" type="submit">Update password</button></p>
  </form>
  <?php endif; ?>
  <p>
    <?php if (!empty($is_operator)): ?>
      <a href="/admin/forgot">Request a new link</a> · <a href="/admin/login">Back to operator sign in</a>
    <?php else: ?>
      <a href="/forgot">Request a new link</a> · <a href="/login">Back to sign in</a>
    <?php endif; ?>
  </p>
</div>
<?php Layout::end();
