<?php
Layout::start('2FA is on', $user);
?>
<div class="wrap app-main">
  <p>Two-factor authentication is on. Save these one-time recovery codes somewhere the rest of the family can find if you lose the phone. Each code works once.</p>
  <ul class="recovery-codes">
    <?php foreach ($codes as $c): ?>
      <li><?= Http::e($c) ?></li>
    <?php endforeach; ?>
  </ul>
  <p><a class="btn" href="/account">Back to account</a></p>
</div>
<?php Layout::end($user);
