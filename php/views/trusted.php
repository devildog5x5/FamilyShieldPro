<?php
Layout::start('Trusted list', $user);
?>
<div class="wrap app-main">
  <?php Layout::flash(); ?>
  <p>Save the legitimate banks, doctors, insurers, utilities, and family numbers before a scare. When a message arrives, we compare it to this list — not to a number the stranger provided.</p>
  <p><strong>Protected contacts</strong> <?= count($rows ?? []) ?> saved. Every contact you add stays here until you remove it.</p>
  <form id="trusted-delete" method="post"><?= Http::csrfField() ?></form>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Kind</th><th>Name</th><th>Notes</th><th>Phone / site</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows ?? [] as $r): ?>
        <tr>
          <td><?= Http::e($r['kind']) ?></td>
          <td><?= Http::e($r['name']) ?></td>
          <td><?= Http::e($r['notes']) ?></td>
          <td>
            <?php if ($r['phone']): ?><?= Http::e(Analyzer::prettyPhone($r['phone'])) ?><br /><?php endif; ?>
            <?= Http::e($r['website']) ?>
          </td>
          <td>
            <button class="btn ghost" type="submit" form="trusted-delete" formaction="/trusted/<?= (int) $r['id'] ?>/delete" onclick="return confirm('Remove this contact?')">Remove</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <form class="panel" method="post" style="margin-top:16px" autocomplete="off">
    <?= Http::csrfField() ?>
    <h2>Add a real contact</h2>
    <label>Name</label>
    <input name="name" required placeholder="Credit union fraud line" autocomplete="off" />
    <label>Phone (from a statement or the back of a card)</label>
    <input name="phone" autocomplete="off" />
    <details class="more">
      <summary>Website, kind, or notes</summary>
      <label>Website</label>
      <input name="website" placeholder="https://" autocomplete="off" />
      <label>Kind</label>
      <select name="kind">
        <option value="other">Other</option>
        <option value="bank">Bank</option>
        <option value="doctor">Doctor / clinic</option>
        <option value="insurer">Insurer</option>
        <option value="utility">Utility</option>
        <option value="family">Family</option>
      </select>
      <label>Notes</label>
      <input name="notes" autocomplete="off" />
    </details>
    <p><button class="btn wide" type="submit">Save on trusted list</button></p>
  </form>
</div>
<?php Layout::end($user);
