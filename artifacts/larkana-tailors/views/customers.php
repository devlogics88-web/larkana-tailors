<?php
$search = trim($_GET['q'] ?? '');
$customers = $search ? searchCustomers($search) : [];
?>
<div class="page-header">
  <h2>&#128101; Customer Search (کسٹمر تلاش)</h2>
  <a href="?page=order_new" class="btn btn-success btn-sm">+ New Order</a>
</div>

<div class="card">
  <div class="card-body">
    <form method="GET" action="" class="search-box">
      <input type="hidden" name="page" value="customers">
      <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by name or phone number... (نام یا فون نمبر سے تلاش کریں)" autofocus>
      <button type="submit" class="btn btn-primary">&#128269; Search</button>
    </form>
    <?php if ($search && empty($customers)): ?>
    <div class="alert alert-info">No customers found for "<?= h($search) ?>". <a href="?page=order_new">Create new order</a> to add a new customer.</div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($customers)): ?>
<div class="card">
  <div class="card-head">Results (<?= count($customers) ?>)</div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name (نام)</th>
          <th>Phone (فون)</th>
          <th>Address (پتہ)</th>
          <th>Registered</th>
          <th>Orders</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c):
          $db = getDB();
          $orderCount = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=?")->execute([$c['id']]) ? 0 : 0;
          $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=?");
          $stmt->execute([$c['id']]);
          $orderCount = $stmt->fetchColumn();
        ?>
        <tr>
          <td><?= h($c['id']) ?></td>
          <td class="bold"><?= h($c['name']) ?></td>
          <td><?= h($c['phone'] ?? '-') ?></td>
          <td><?= h($c['address'] ?? '-') ?></td>
          <td><?= formatDate($c['created_at']) ?></td>
          <td class="bold text-center"><?= h($orderCount) ?></td>
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
<div class="alert alert-info">Enter a customer name or phone number to search. (نام یا فون نمبر درج کریں)</div>
<?php endif; ?>
