<?php
$search = trim($_GET['q'] ?? '');
$customers = $search ? searchCustomers($search) : getAllCustomers();
?>
<div class="page-header">
  <h2>&#128101; Customers</h2>
  <a href="?page=order_new" class="btn btn-success btn-sm">+ New Order</a>
</div>

<?php if ($msg = flash('customer_ok')): ?>
<div class="alert alert-success"><?= h($msg) ?></div>
<?php endif; ?>
<?php if ($err = flash('customer_err')): ?>
<div class="alert alert-error"><?= h($err) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="GET" action="" class="search-box">
      <input type="hidden" name="page" value="customers">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by name or phone number..." autofocus>
      <button type="submit" class="btn btn-primary">&#128269; Search</button>
    </form>
    <?php if ($search && empty($customers)): ?>
    <div class="alert alert-info">No customers found for "<?= h($search) ?>". <a href="?page=order_new">Create new order</a> to add a new customer.</div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($customers)): ?>
<div class="card">
  <div class="card-head">&#128101; <?= $search ? 'Search Results' : 'All Customers' ?> (<?= count($customers) ?>)</div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead>
        <tr>
          <th>Customer ID</th>
          <th>Name</th>
          <th>Phone</th>
          <th>Address</th>
          <th>Registered</th>
          <th>Orders</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
          <td style="font-family:monospace; font-size:11px; color:#1B242D; font-weight:bold;">CID-<?= str_pad($c['id'], 5, '0', STR_PAD_LEFT) ?></td>
          <td class="bold"><?= h($c['name']) ?></td>
          <td><?= h($c['phone'] ?? '-') ?></td>
          <td><?= h($c['address'] ?? '-') ?></td>
          <td><?= formatDate($c['created_at']) ?></td>
          <td class="bold text-center"><?= h($c['order_count'] ?? 0) ?></td>
          <td>
            <a href="?page=customer_orders&customer_id=<?= h($c['id']) ?>" class="btn btn-info btn-sm">View Orders</a>
            <a href="?page=order_new&prefill_customer=<?= h($c['id']) ?>" class="btn btn-success btn-sm">New Order</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php elseif (!$search): ?>
<div class="alert alert-info">No customers yet. Add new customers via the "New Order" form.</div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<!-- DELETE ALL CUSTOMERS -->
<div class="card" style="border:2px solid #c62828; margin-top:16px;">
  <div class="card-head" style="background:#c62828; color:#fff;">&#9888; Danger Zone — Delete All Customer Records</div>
  <div class="card-body">
    <p style="color:#c62828; font-weight:bold; margin-bottom:8px;">
      WARNING: This will permanently delete ALL customers, orders, measurements and related data. This cannot be undone.
    </p>
    <button type="button" class="btn btn-danger" onclick="showDeleteAllCustomers()">&#128465; Delete All Customer Records</button>
    <div id="delete-customers-confirm" style="display:none; margin-top:12px; background:#fff3e0; padding:10px; border:1px solid #e65100;">
      <p style="margin:0 0 8px; color:#bf360c; font-weight:bold;">Type <strong>OK</strong> to confirm deletion of all records:</p>
      <form method="POST" action="?action=delete_all_customers" onsubmit="return validateDeleteAll(this, 'customers')">
        <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
        <input type="text" name="confirm_word" id="confirm_word_customers" autocomplete="off"
               style="width:100px; margin-right:8px; font-size:14px; font-weight:bold; letter-spacing:2px;" placeholder="OK">
        <button type="submit" class="btn btn-danger">Confirm Delete All</button>
        <button type="button" class="btn" style="background:#546e7a;color:#fff;" onclick="document.getElementById('delete-customers-confirm').style.display='none'">Cancel</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function showDeleteAllCustomers() {
    document.getElementById('delete-customers-confirm').style.display = 'block';
    document.getElementById('confirm_word_customers').focus();
}
function validateDeleteAll(form, type) {
    var wordField = form.querySelector('[name="confirm_word"]');
    if (!wordField || wordField.value.trim() !== 'OK') {
        alert('You must type exactly OK to confirm.');
        return false;
    }
    return confirm('FINAL WARNING: All ' + type + ' data will be permanently deleted. Proceed?');
}
</script>
