<?php
Layout::start('Family circle', $user);
$isOwner = ($user['role'] ?? '') === 'owner';
?>
<div class="wrap app-main">
  <?php Layout::flash(); ?>
  <h2>People in this circle</h2>
  <p>Up to five people. Invite the person who will actually answer the phone.</p>
  <p class="status-legend">
    Status:
    <span class="pill status-invited">Invited</span>
    <span class="status-arrow">&gt;</span>
    <span class="pill status-sent">Invite sent</span>
    <span class="status-arrow">&gt;</span>
    <span class="pill status-accepted">Invite Accepted</span>
    <span class="status-arrow">&gt;</span>
    <span class="pill status-access">User Accesses the Circle</span>
  </p>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Role</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($members ?? [] as $m): ?>
        <tr>
          <td><?= Http::e($m['name']) ?></td>
          <td><?= Http::e($m['email']) ?></td>
          <td><?= Http::e($m['phone'] ? Analyzer::prettyPhone($m['phone']) : '—') ?></td>
          <td><?= Http::e($m['role']) ?></td>
          <td><?= $m['status'] === 'access' ? 'User Accesses the Circle' : Http::e($m['status']) ?></td>
          <td>
            <?php if ($isOwner && (int) $m['id'] !== (int) $user['id'] && $m['role'] !== 'owner'): ?>
              <form method="post" action="/circle/remove" onsubmit="return confirm('Remove this person from the circle?')">
                <?= Http::csrfField() ?>
                <input type="hidden" name="user_id" value="<?= (int) $m['id'] ?>" />
                <button class="btn ghost" type="submit">Remove</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php foreach ($pending ?? [] as $p): ?>
        <tr>
          <td><?= Http::e($p['name'] ?: '—') ?></td>
          <td><?= Http::e($p['email']) ?></td>
          <td><?= Http::e($p['phone'] ?: '—') ?></td>
          <td>invited</td>
          <td>
            Invite sent
            <div class="join-url"><a href="/join/<?= Http::e($p['token']) ?>"><?= Http::e(Http::baseUrl() . '/join/' . $p['token']) ?></a></div>
            <form id="circle-resend-<?= (int) $p['id'] ?>" method="post" action="/circle/resend">
              <?= Http::csrfField() ?>
              <input type="hidden" name="invite_id" value="<?= (int) $p['id'] ?>" />
            </form>
            <button class="btn ghost resend-btn" type="submit" form="circle-resend-<?= (int) $p['id'] ?>">Resend invite</button>
            <?php if ($isOwner): ?>
              <form method="post" action="/circle/invite/<?= (int) $p['id'] ?>/cancel" style="display:inline">
                <?= Http::csrfField() ?>
                <button class="btn ghost resend-btn" type="submit">Cancel invite</button>
              </form>
            <?php endif; ?>
          </td>
          <td></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <form class="panel" method="post" style="margin-top:16px">
    <?= Http::csrfField() ?>
    <h2>Invite someone</h2>
    <label>Email</label>
    <input name="email" type="email" required autocomplete="off" placeholder="family@example.com" />
    <details class="more">
      <summary>Name or mobile (optional)</summary>
      <label>Their name</label>
      <input name="name" autocomplete="name" />
      <label>Mobile</label>
      <input name="phone" type="tel" inputmode="tel" placeholder="(555) 010-1234" autocomplete="off" />
    </details>
    <p><button class="btn wide" type="submit">Send invite</button></p>
    <p class="disclaimer">We email a tap-to-open join link when mail is set up. Share the join link in a call you already trust — not inside a suspicious thread. Reply STOP on texts to opt out.</p>
  </form>

  <?php if (!empty($alert)): ?>
    <div class="flash error" style="margin-top:16px">
      <?= Http::e($alert['created_at']) ?> —
      PLEASE CALL <?= Http::e($alert['payer_name']) ?> BEFORE THEY PAY.
      <a href="/checks/<?= (int) $alert['check_id'] ?>">Open this check</a>
    </div>
  <?php endif; ?>
</div>
<?php Layout::end($user);
