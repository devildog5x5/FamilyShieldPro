<?php
Layout::start('Start a circle · OurCircle', null, 'auth-page');
$plan = $plan ?? '';
?>
<div class="auth-card">
  <?php Layout::brand(); ?>
  <p class="core-rule">Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.</p>
  <?php Layout::flash(); ?>
  <form method="post">
    <?= Http::csrfField() ?>
    <?php if (!empty($plan) && in_array($plan, ['monthly', 'yearly'], true)): ?>
      <input type="hidden" name="plan" value="<?= Http::e($plan) ?>" />
      <p class="muted">Next: <?= $plan === 'monthly' ? 'Family monthly ($14.99/month)' : 'Family yearly ($119.99/year)' ?>.</p>
    <?php endif; ?>
    <label>Your name</label>
    <input name="name" required autocomplete="name" />
    <label>Email</label>
    <input name="email" type="email" required autocomplete="username" />
    <label>Password (8+ characters)</label>
    <input name="password" type="password" required minlength="8" autocomplete="new-password" />
    <label>Mobile number (optional)</label>
    <input name="phone" type="tel" inputmode="tel" autocomplete="tel" />
    <p class="muted">Password reset uses your email, not this number. We only text this number for circle invites and alerts if SMS is turned on.</p>
    <p class="muted">Every new circle includes a <strong>14-day trial</strong>. Then the owner pays $14.99/month or $119.99/year to keep checking new requests. If the trial ends, you still have the trusted list, past checks, and other personal information you entered — we do not lock you out of it, and we do not sell people’s information.</p>
    <?php Layout::agreeCheckbox(); ?>
    <p><button class="btn wide" type="submit">Start the 14-day trial</button></p>
  </form>
  <p><a href="/login">Already have a login</a></p>
</div>
<?php Layout::end();
