<?php
Layout::start('Operator console · Family Shield Pro', null, 'app-bare');
?>
<div class="wrap app-main">
  <h1>Circles</h1>
  <p><a class="btn" href="/admin/data">Open database</a></p>
  <?php Layout::flash(); ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Plan</th><th>People</th><th>Created</th></tr></thead>
      <tbody>
      <?php foreach ($circles ?? [] as $c): ?>
        <tr>
          <td><?= (int) $c['id'] ?></td>
          <td><?= Http::e($c['name']) ?></td>
          <td><?= Http::e($c['plan']) ?></td>
          <td><?= (int) $c['people'] ?></td>
          <td><?= Http::e($c['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
    $stripe = $stripe ?? Billing::status();
    $yn = static function (bool $ok): string {
        return $ok ? 'Yes' : 'Missing';
    };
  ?>
  <div class="panel" style="margin-top:32px">
    <h2>Stripe check</h2>
    <p>This page checks keys, prices, and the sandbox webhook, then writes <code>data/stripe-check.txt</code> (blocked from the web). Secret keys are never shown. Click inside the box and copy if you want to save it.</p>
    <p>
      Secret key: <strong><?= Http::e($yn(!empty($stripe['has_secret']))) ?></strong>
      · Test mode key: <strong><?= Http::e($yn(!empty($stripe['test_key']))) ?></strong>
      · Monthly price: <strong><?= Http::e($yn(!empty($stripe['has_monthly_price']))) ?></strong>
      · Yearly price: <strong><?= Http::e($yn(!empty($stripe['has_yearly_price']))) ?></strong>
      · Webhook secret: <strong><?= Http::e($yn(!empty($stripe['has_webhook']))) ?></strong>
      · Checkout ready: <strong><?= Http::e($yn(!empty($stripe['ready']))) ?></strong>
    </p>
    <label for="stripe-log">Stripe log</label>
    <textarea id="stripe-log" class="stripe-log" readonly rows="22"><?= Http::e((string) ($stripeReport ?? '')) ?></textarea>
    <form method="post" action="/admin/stripe-check">
      <?= Http::csrfField() ?>
      <p><button class="btn" type="submit">Run Stripe check again</button></p>
    </form>
    <p class="muted">Paste only the two test keys into server <code>.env</code> if they are missing. The create button below adds Family Shield Pro products next to your other Stripe app. It does not change that app’s prices.</p>
    <?php if (!empty($stripe['live_key'])): ?>
      <p class="disclaimer">A live key is in <code>.env</code>. Switch to <code>sk_test_</code> before using this button.</p>
    <?php elseif (empty($stripe['can_provision'])): ?>
      <p class="disclaimer">Add the test secret key to <code>.env</code>, reload this page, then click the button.</p>
    <?php else: ?>
      <form method="post" action="/admin/stripe-setup">
        <?= Http::csrfField() ?>
        <p><button class="btn" type="submit"><?= !empty($stripe['ready']) ? 'Refresh Family Shield Pro prices in Stripe' : 'Create Family Shield Pro prices in Stripe' ?></button></p>
      </form>
    <?php endif; ?>
  </div>
  <form class="panel" method="post" action="/admin/factory-reset" style="margin-top:32px" onsubmit="return confirm('This permanently deletes all circles, checks, and screenshots, then reseeds the demo. Continue?');">
    <?= Http::csrfField() ?>
    <h2>Factory reset</h2>
    <p>Deletes every circle, member, check, invite, trusted contact, and screenshot. Recreates the Foster demo circle (<code>family@ourcircle.app</code> / <code>password123</code>) and restores the operator password from <code>OPERATOR_PASSWORD</code> in server configuration.</p>
    <label>Type FACTORY to confirm</label>
    <input name="confirm" required autocomplete="off" placeholder="FACTORY" />
    <label>Operator password</label>
    <input name="password" type="password" required autocomplete="current-password" />
    <p><button class="btn danger" type="submit">Reset database to factory settings</button></p>
  </form>
</div>
<?php Layout::end();
