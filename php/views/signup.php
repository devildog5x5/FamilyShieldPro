<?php
Layout::start('Start a circle · OurCircle', null, 'auth-page');
?>
<div class="auth-card">
  <a class="brand" href="<?= Http::e(Http::baseUrl()) ?>"><img src="/static/img/logo.png" alt="" /><div><strong>OurCircle</strong><span>Start a circle</span></div></a>
  <p class="core-rule">Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.</p>
  <?php Layout::flash(); ?>
  <form method="post">
    <?= Http::csrfField() ?>
    <label>Your name</label>
    <input name="name" required autocomplete="name" />
    <label>Email</label>
    <input name="email" type="email" required autocomplete="username" />
    <label>Password (8+ characters)</label>
    <input name="password" type="password" required minlength="8" autocomplete="new-password" />
    <label>Mobile number (so we can text call-me)</label>
    <input name="phone" type="tel" inputmode="tel" autocomplete="tel" />
    <p><button class="btn wide" type="submit">Start the family circle</button></p>
  </form>
  <p><a href="/login">Already have a login</a></p>
</div>
<?php Layout::end();
