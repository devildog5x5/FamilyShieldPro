<?php
Layout::start('Reset operator password · Family Shield Pro', null, 'auth-page');
?>
<div class="auth-card">
  <h1>Reset operator password</h1>
  <p>Enter the operator email for this site. We’ll send a one-hour link, or save it next to the database if mail is not connected. We never say whether the email matches.</p>
  <?php Layout::flash(); ?>
  <form method="post" action="/admin/forgot">
    <?= Http::csrfField() ?>
    <label>Operator email</label>
    <input name="email" type="email" required autocomplete="username" />
    <p><button class="btn wide" type="submit">Send reset link</button></p>
  </form>
  <p><a href="/admin/login">Back to operator sign in</a></p>
</div>
<?php Layout::end();
