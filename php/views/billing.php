<?php
Layout::start('Plans', $user);
$plan = $plan ?? 'yearly';
$isOwner = !empty($isOwner);
$stripe = !empty($stripe);
$testMode = !empty($testMode);
$hasCustomer = !empty($hasCustomer);
?>
<div class="wrap app-main">
  <?php Layout::flash(); ?>
  <?php if (!empty($payError)): ?>
    <div class="flash error">Last checkout error: <?= Http::e((string) $payError) ?></div>
  <?php endif; ?>
  <p>This household is on <strong><?= Http::e($plan) ?></strong>.</p>
  <?php
    $trial = $trial ?? [];
    if (!empty($trial['active_trial'])):
  ?>
    <p><strong>14-day trial.</strong>
      <?= !empty($trial['ends_today'])
        ? 'It ends today.'
        : ((int) ($trial['days_left'] ?? 0) . ' days left, through ' . Http::e((string) ($trial['ends_label'] ?? '')) . '.')
      ?>
      Then the owner pays to keep checking new requests.</p>
  <?php elseif (!empty($trial['expired'])): ?>
    <p><strong>The 14-day trial has ended.</strong> Pay below to check new requests, invite family, and use call-me. You can still view the trusted list and past checks.</p>
  <?php endif; ?>
  <p class="disclaimer">Paying for a plan does not make a request safe.</p>
  <?php if ($testMode): ?>
    <p class="disclaimer">Test payments are on. Use Stripe card <strong>4242 4242 4242 4242</strong>, any future date, any CVC, any ZIP. No real charge.</p>
  <?php endif; ?>
  <div class="plans">
    <div class="panel">
      <h3>Family monthly</h3>
      <p><strong>$14.99/month</strong></p>
      <p>Up to five people in one circle. Pause, trusted list, and call-me-before-I-pay.</p>
      <?php if ($isOwner): ?>
        <form method="post" action="/billing/choose">
          <?= Http::csrfField() ?>
          <input type="hidden" name="plan" value="monthly" />
          <p><button class="btn wide" type="submit"><?= $stripe ? 'Pay Family monthly' : 'Choose Family monthly' ?></button></p>
        </form>
      <?php else: ?>
        <p class="muted">Only the circle owner can change the plan.</p>
      <?php endif; ?>
    </div>
    <div class="panel featured">
      <h3>Family yearly</h3>
      <p><strong>$119.99/year</strong></p>
      <p>Same circle. Pay once a year — about $10 a month.</p>
      <?php if ($isOwner): ?>
        <form method="post" action="/billing/choose">
          <?= Http::csrfField() ?>
          <input type="hidden" name="plan" value="yearly" />
          <p><button class="btn gold wide" type="submit"><?= $stripe ? 'Pay Family yearly' : 'Choose Family yearly' ?></button></p>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($isOwner && $hasCustomer): ?>
    <form method="post" action="/billing/portal">
      <?= Http::csrfField() ?>
      <p><button class="btn wide" type="submit">Manage card or cancel</button></p>
    </form>
  <?php endif; ?>
  <?php if (!$stripe): ?>
    <p class="disclaimer">Card payments are not connected yet, so a plan choice is only saved on this circle. Nothing is charged until payments are turned on.</p>
  <?php endif; ?>
  <p>Churches, senior centers, and veterans groups: ask us about a shared license.</p>
</div>
<?php Layout::end($user);
