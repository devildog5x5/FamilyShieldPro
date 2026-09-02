<?php
Layout::start('Operator console · Family Shield Pro', null, 'app-bare');
?>
<div class="wrap app-main">
  <h1>Circles</h1>
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
</div>
<?php Layout::end();
