<?php
Layout::start('Check this', $user);
$members = $members ?? [];
$pending = $pending ?? [];
$trusted = $trusted ?? [];
$checks = $checks ?? [];
$alert = $alert ?? null;
$total = count($members) + count($pending);
?>
<div class="wrap app-main">
  <?php Layout::flash(); ?>
  <?php if ($alert): ?>
    <div class="flash error">
      <strong>Urgent circle alert</strong>
      PLEASE CALL <?= Http::e($alert['payer_name']) ?> BEFORE THEY PAY.
      They asked the circle to stop a payment or information request.
      Open OurCircle and look at check #<?= (int) $alert['check_id'] ?>.
      <a href="/checks/<?= (int) $alert['check_id'] ?>">Open this check</a>
    </div>
  <?php endif; ?>

  <div class="grid-2">
    <form class="panel" method="post" action="/check" enctype="multipart/form-data">
      <?= Http::csrfField() ?>
      <h2>What landed in your lap?</h2>
      <p class="muted">Paste the message or drop a screenshot. We pull numbers and links out of the paste — you do not have to retype them.</p>
      <label>Message, email, or offer</label>
      <textarea name="text" placeholder="Paste the whole thing. Do not tap links inside it."></textarea>
      <label>Screenshot (if that is all you have)</label>
      <input name="screenshot" type="file" accept="image/*" />
      <details class="more">
        <summary>Add a number or website separately</summary>
        <label>Phone number</label>
        <input name="phone" placeholder="(555) 010-1234" />
        <label>Website</label>
        <input name="url" placeholder="https://" />
      </details>
      <p><button class="btn wide" type="submit">Check this with OurCircle</button></p>
      <p class="disclaimer">This will not say the request is safe. It will help you pause. This application offers guidance, not a guarantee.</p>
    </form>

    <div>
      <div class="panel">
        <h3>Your circle</h3>
        <p><?= (int) $total ?> of 5 people · <?= count($pending) ?> invites waiting</p>
        <?php foreach ($members as $m): ?>
          <p><?= Http::e($m['name']) ?> · <?= Http::e($m['email']) ?></p>
        <?php endforeach; ?>
        <?php foreach ($pending as $p): ?>
          <p><?= Http::e($p['name'] ?: $p['email']) ?> · <?= Http::e($p['email']) ?></p>
        <?php endforeach; ?>
        <form method="post" action="/circle">
          <?= Http::csrfField() ?>
          <input type="hidden" name="return" value="home" />
          <label>Invite by email</label>
          <input name="email" type="email" required placeholder="family@example.com" autocomplete="off" />
          <p><button class="btn wide" type="submit">Send invite</button></p>
        </form>
        <p><a href="/circle">Everyone in the circle</a></p>
      </div>
      <div class="panel" style="margin-top:16px">
        <h3>Trusted list</h3>
        <p><?= count($trusted) ?> saved banks, doctors, utilities, and family numbers.</p>
        <?php foreach (array_slice($trusted, 0, 4) as $t): ?>
          <p>
            <?php if (!empty($t['phone'])): ?>
              <a href="tel:+1<?= Http::e($t['phone']) ?>"><?= Http::e($t['phone']) ?></a>
            <?php else: ?>
              <?= Http::e($t['name']) ?>
            <?php endif; ?>
          </p>
        <?php endforeach; ?>
        <p><a href="/trusted">Open list</a></p>
      </div>
      <div class="panel" style="margin-top:16px">
        <?php Layout::scamRefs(); ?>
        <p><a href="/report">If money already went out</a></p>
      </div>
      <div class="panel" style="margin-top:16px">
        <h3>Recent checks</h3>
        <?php if (!$checks): ?>
          <p>None yet. Paste the odd message in the box to the left, then check it. Do not reply.</p>
        <?php else: ?>
          <?php foreach ($checks as $c): ?>
            <p><a href="/checks/<?= (int) $c['id'] ?>"><?= Http::e($c['level']) ?> · <?= Http::e(substr((string) $c['text'], 0, 80)) ?></a></p>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php Layout::end($user);
