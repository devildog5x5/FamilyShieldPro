<?php
Layout::start('14-day trial ended', $user);
$trial = $trial ?? ($user['trial'] ?? []);
$next = Http::safeNext($next ?? '/home');
$isOwner = !empty($trial['is_owner']);
?>
<div class="wrap app-main">
  <div class="panel trial-gate">
    <h1>Your 14-day trial has ended</h1>
    <p>Family Shield Pro is a 14-day trial. After that the circle owner pays so the family can keep checking new requests, inviting people, and using call-me.</p>
    <p>You can still view the <strong>trusted list</strong>, <strong>past checks</strong>, and other personal information you entered. We do not lock you out of that, and we do not sell people’s information. This reminder pauses for 10 seconds, then you may continue with that limited access.</p>
    <?php if ($isOwner): ?>
      <p><a class="btn gold wide" href="/billing">Pay now to keep the full circle</a></p>
    <?php else: ?>
      <p>Ask the circle owner to open <strong>Plans</strong> and continue Family Shield Pro.</p>
    <?php endif; ?>
    <form method="post" action="/trial/continue" id="trial-continue-form">
      <?= Http::csrfField() ?>
      <input type="hidden" name="next" value="<?= Http::e($next) ?>" />
      <p><button class="btn wide" type="submit" id="trial-wait" disabled>Continue in 10 seconds</button></p>
    </form>
    <p class="disclaimer">Paying does not make a request safe. This application offers guidance, not a guarantee.</p>
  </div>
</div>
<script src="/static/js/fsp-trial.js?v=<?= Http::e(Layout::asset()) ?>"></script>
<?php Layout::end($user);
