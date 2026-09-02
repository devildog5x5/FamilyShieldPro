<?php
Layout::start('Database · Operator console', null, 'app-bare');
$table = $table ?? 'circles';
$cols = $cols ?? [];
$rows = $rows ?? [];
$tables = $tables ?? [];
$secrets = $secrets ?? [];
$edit = $edit ?? null;
?>
<div class="wrap app-main">
  <p class="muted"><a href="/admin">← Circles</a></p>
  <h1>Database</h1>
  <?php Layout::flash(); ?>
  <nav class="admin-tabs">
    <?php foreach ($tables as $t): ?>
      <a class="<?= $t === $table ? 'btn sm' : 'btn ghost sm' ?>" href="/admin/data?table=<?= Http::e($t) ?>"><?= Http::e($t) ?></a>
    <?php endforeach; ?>
  </nav>
  <p class="muted"><?= count($rows) ?> row(s) in <code><?= Http::e($table) ?></code> (newest 200). Password and 2FA secrets are hidden here; leave those fields blank when editing to keep the current value. New passwords are hashed.</p>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <?php foreach ($cols as $c): ?><th><?= Http::e($c) ?></th><?php endforeach; ?>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <?php foreach ($cols as $c): ?>
            <td>
              <?php
                $v = (string) ($r[$c] ?? '');
                if (in_array($c, $secrets, true) && $v !== '') {
                    echo '••••';
                } else {
                    $show = strlen($v) > 80 ? substr($v, 0, 80) . '…' : $v;
                    echo Http::e($show);
                }
              ?>
            </td>
          <?php endforeach; ?>
          <td>
            <a href="/admin/data?table=<?= Http::e($table) ?>&amp;id=<?= (int) $r['id'] ?>">Edit</a>
            <form method="post" action="/admin/data/delete" style="display:inline" onsubmit="return confirm('Delete <?= Http::e($table) ?> row <?= (int) $r['id'] ?>?');">
              <?= Http::csrfField() ?>
              <input type="hidden" name="table" value="<?= Http::e($table) ?>" />
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>" />
              <button class="btn ghost sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="grid-2" style="margin-top:24px">
    <form class="panel" method="post" action="<?= $edit ? '/admin/data/save' : '/admin/data/insert' ?>">
      <?= Http::csrfField() ?>
      <input type="hidden" name="table" value="<?= Http::e($table) ?>" />
      <?php if ($edit): ?>
        <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>" />
        <h2>Edit <?= Http::e($table) ?> #<?= (int) $edit['id'] ?></h2>
        <p><a href="/admin/data?table=<?= Http::e($table) ?>">Cancel edit / add new</a></p>
      <?php else: ?>
        <h2>Add <?= Http::e($table) ?> row</h2>
      <?php endif; ?>
      <?php foreach ($cols as $c): ?>
        <?php if ($c === 'id') { continue; } ?>
        <label><?= Http::e($c) ?></label>
        <?php
          $secret = in_array($c, $secrets, true);
          $cur = $edit ? (string) ($edit[$c] ?? '') : '';
          $long = !$secret && (strlen($cur) > 120 || $c === 'analysis_json' || $c === 'text' || $c === 'body' || $c === 'recovery_codes');
        ?>
        <?php if ($long): ?>
          <textarea name="<?= Http::e($c) ?>" rows="4"><?= Http::e($cur) ?></textarea>
        <?php elseif ($secret): ?>
          <input name="<?= Http::e($c) ?>" type="password" autocomplete="new-password" placeholder="<?= $edit ? 'Leave blank to keep' : '' ?>" />
        <?php else: ?>
          <input name="<?= Http::e($c) ?>" value="<?= Http::e($cur) ?>" />
        <?php endif; ?>
      <?php endforeach; ?>
      <p><button class="btn" type="submit"><?= $edit ? 'Save changes' : 'Insert row' ?></button></p>
    </form>

    <form class="panel" method="post" action="/admin/sql">
      <?= Http::csrfField() ?>
      <h2>SQL</h2>
      <p class="muted">One statement. <code>ATTACH</code>, <code>DETACH</code>, and <code>VACUUM INTO</code> are blocked. Re-enter the operator password to run anything.</p>
      <label>Statement</label>
      <textarea class="sql-box" name="sql" rows="8" placeholder="SELECT * FROM users LIMIT 20"><?= Http::e($sql ?? '') ?></textarea>
      <label>Operator password</label>
      <input name="password" type="password" required autocomplete="current-password" />
      <p><button class="btn danger" type="submit">Run SQL</button></p>
      <?php if ($sql_count !== null): ?>
        <p>Rows affected: <?= (int) $sql_count ?></p>
      <?php endif; ?>
      <?php if (is_array($sql_rows)): ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><?php foreach ($sql_cols ?? [] as $c): ?><th><?= Http::e((string) $c) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($sql_rows as $sr): ?>
              <tr>
                <?php foreach ($sql_cols ?? [] as $c): ?>
                  <td><?= Http::e(strlen((string) ($sr[$c] ?? '')) > 120 ? substr((string) $sr[$c], 0, 120) . '…' : (string) ($sr[$c] ?? '')) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>
<?php Layout::end();
