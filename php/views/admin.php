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
