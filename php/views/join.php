<?php
Layout::start('Join a circle · OurCircle', null, 'auth-page');
$inv = $invite;
?>
<div class="auth-card">
  <a class="brand" href="<?= Http::e(Http::baseUrl()) ?>"><img src="/static/img/logo.png" alt="" /><div><strong>OurCircle</strong><span>Join a circle</span></div></a>
  <h1>Join this family circle</h1>
  <p class="core-rule">Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.</p>
  <?php Layout::flash(); ?>
  <p>Invite for <?= Http::e($inv['email']) ?></p>
  <form method="post" action="/join/<?= Http::e($inv['token']) ?>">
    <?= Http::csrfField() ?>
    <?php if (!empty($inv['name'])): ?>
      <input type="hidden" name="name" value="<?= Http::e($inv['name']) ?>" />
      <p>Joining as <strong><?= Http::e($inv['name']) ?></strong></p>
    <?php else: ?>
      <label>Your name</label>
      <input name="name" required />
    <?php endif; ?>
    <label>Choose a password (8+)</label>
    <input name="password" type="password" required minlength="8" />
    <details class="more">
      <summary>Mobile (optional — for call-me texts)</summary>
      <input name="phone" type="tel" inputmode="tel" placeholder="(555) 010-1234" autocomplete="tel" value="<?= Http::e($inv['phone'] ?? '') ?>" />
    </details>
    <p><button class="btn wide" type="submit">Join the circle</button></p>
  </form>
</div>
<?php Layout::end();
