<?php
Layout::start('Operator console · Family Shield Pro', null, 'auth-page');
?>
<div class="auth-card">
  <h1>Operator console</h1>
  <?php Layout::flash(); ?>
  <form method="post">
    <?= Http::csrfField() ?>
    <label>Operator password</label>
    <input name="password" type="password" required autocomplete="current-password" />
    <p><button class="btn wide" type="submit">Open console</button></p>
  </form>
  <p class="muted">After several wrong tries, operator sign-in pauses for 15 minutes.</p>
  <p><a href="/admin/forgot">Forgot operator password</a></p>
</div>
<?php Layout::end();
