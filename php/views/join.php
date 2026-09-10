<?php
Layout::start('Join a circle · OurCircle', null, 'auth-page');
$inv = $invite;
?>
<div class="auth-card">
  <?php Layout::brand(); ?>
  <h1>Join this family circle</h1>
  <p class="core-rule">Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.</p>
  <?php Layout::flash(); ?>
  <p>Invite for <?= Http::e($inv['email']) ?></p>
  <?php if (!empty($trialEnded)): ?>
    <p>This circle’s 14-day trial has ended. Ask the owner to continue Family Shield Pro before new people join.</p>
  <?php else: ?>
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
      <summary>Mobile (optional)</summary>
      <input name="phone" type="tel" inputmode="tel" placeholder="(555) 010-1234" autocomplete="tel" value="<?= Http::e($inv['phone'] ?? '') ?>" />
      <p class="muted">Password reset uses your email. Texts are only for circle invites and alerts if SMS is on.</p>
    </details>
    <?php Layout::agreeCheckbox(); ?>
    <p><button class="btn wide" type="submit">Join the circle</button></p>
  </form>
  <?php endif; ?>
</div>
<?php Layout::end();
