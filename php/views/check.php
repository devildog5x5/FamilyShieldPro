<?php
Layout::start('Check ' . (int) $check['id'], $user);
$analysis = $analysis ?? [];
$level = $analysis['level'] ?? 'unknown';
$riskClass = $level === 'pause' ? 'pause' : ($level === 'caution' ? 'caution' : 'unknown');
?>
<div class="wrap app-main">
  <?php Layout::flash(); ?>
  <p class="risk <?= Http::e($riskClass) ?>"><?= Http::e($analysis['headline'] ?? $check['headline']) ?></p>
  <form method="post" action="/checks/<?= (int) $check['id'] ?>/alert">
    <?= Http::csrfField() ?>
    <button class="btn danger wide" type="submit">Please call me before I pay</button>
  </form>
  <form method="post" action="/checks/<?= (int) $check['id'] ?>/review">
    <?= Http::csrfField() ?>
    <p><button class="btn gold wide" type="submit">Send to family circle</button></p>
  </form>
  <p class="disclaimer">This application offers guidance, not a guarantee.</p>

  <div class="panel" style="margin-top:16px">
    <h2>What you pasted</h2>
    <p><?= nl2br(Http::e($check['text'])) ?></p>
    <?php if (!empty($analysis['phones'])): ?>
      <p><strong>Number:</strong> <?= Http::e(implode(', ', $analysis['phones'])) ?></p>
    <?php endif; ?>
    <?php if (!empty($analysis['urls'])): ?>
      <p><strong>Website:</strong> <?= Http::e(implode(', ', $analysis['urls'])) ?></p>
    <?php endif; ?>
    <?php if (!empty($check['screenshot_token'])): ?>
      <p><img src="/uploads/<?= Http::e($check['screenshot_token']) ?>" alt="Uploaded screenshot" /></p>
    <?php endif; ?>
  </div>

  <div class="panel" style="margin-top:16px">
    <h2>Warning signs</h2>
    <ul class="list">
      <?php foreach ($analysis['signs'] ?? [] as $s): ?>
        <li><?= Http::e($s) ?></li>
      <?php endforeach; ?>
    </ul>
    <h3>Number, domain, or known patterns</h3>
    <ul class="list">
      <?php foreach ($analysis['patterns'] ?? [] as $s): ?>
        <li><?= Http::e($s) ?></li>
      <?php endforeach; ?>
    </ul>
    <h3>What to do</h3>
    <ol class="list">
      <?php foreach ($analysis['next'] ?? [] as $s): ?>
        <li><?= Http::e($s) ?></li>
      <?php endforeach; ?>
    </ol>
    <?php Layout::scamRefs(); ?>
  </div>

  <div class="panel" style="margin-top:16px">
    <h2>Circle notes</h2>
    <?php foreach ($notes ?? [] as $n): ?>
      <p><span class="pill"><?= Http::e($n['kind']) ?></span> <?= Http::e($n['name']) ?>: <?= Http::e($n['body']) ?></p>
    <?php endforeach; ?>
    <form method="post" action="/checks/<?= (int) $check['id'] ?>/review/reply">
      <?= Http::csrfField() ?>
      <label>Leave a note for the circle</label>
      <textarea name="reply" placeholder="I looked — keep pausing."></textarea>
      <p><button class="btn" type="submit">Add note</button></p>
    </form>
  </div>
  <p><a href="/report">If money or passwords already went out → Report &amp; recover</a></p>
</div>
<?php Layout::end($user);
