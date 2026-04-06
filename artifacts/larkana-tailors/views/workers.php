<?php
$db = getDB();
$workers = $db->query("SELECT id, username, role, full_name, created_at FROM users ORDER BY role DESC, full_name")->fetchAll();
?>
<div class="page-header">
  <h2>&#128119; Workers / Users (ورکرز)</h2>
</div>

<?php if ($msg = flash('worker_ok')): ?>
<div class="alert alert-success"><?= h($msg) ?></div>
<?php endif; ?>
<?php if ($err = flash('worker_err')): ?>
<div class="alert alert-error"><?= h($err) ?></div>
<?php endif; ?>

<div class="form-grid-2" style="align-items:start;">

<div class="card">
  <div class="card-head">Add New Worker (نیا ورکر شامل کریں)</div>
  <div class="card-body">
    <form method="POST" action="?action=add_worker">
      <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
      <div class="form-group mb-8">
        <label>Full Name (پورا نام)</label>
        <input type="text" name="full_name" required placeholder="Worker Full Name">
      </div>
      <div class="form-group mb-8">
        <label>Username (یوزر نیم) *</label>
        <input type="text" name="username" required placeholder="Login username" autocomplete="off">
      </div>
      <div class="form-group mb-8">
        <label>Password (پاس ورڈ) *</label>
        <input type="password" name="password" required placeholder="Login password" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-success">&#10003; Add Worker</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head">Users List (یوزرز)</div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Role</th><th>Added</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($workers as $w): ?>
        <tr>
          <td><?= h($w['id']) ?></td>
          <td class="bold"><?= h($w['full_name'] ?: '-') ?></td>
          <td><?= h($w['username']) ?></td>
          <td><span class="badge <?= $w['role']==='admin'?'badge-ready':'badge-pending' ?>"><?= h(ucfirst($w['role'])) ?></span></td>
          <td><?= formatDate($w['created_at']) ?></td>
          <td>
            <?php if ($w['role'] !== 'admin'): ?>
            <a href="?action=delete_worker&id=<?= h($w['id']) ?>&csrf=<?= h(getCsrf()) ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Delete worker: <?= h($w['username']) ?>?')">Del</a>
            <?php else: ?>
            <span class="small">Admin</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
