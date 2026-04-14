<?php
$customerId = (int)($_GET['customer_id'] ?? 0);
if (!$customerId) { header('Location: ?page=customers'); exit; }
$customer = getCustomer($customerId);
if (!$customer) { header('Location: ?page=customers'); exit; }
$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC");
$stmt->execute([$customerId]);
$customerOrders = $stmt->fetchAll();
$totals = $db->prepare("
    SELECT SUM(total_price) as t, SUM(advance_paid) as a,
           SUM(CASE WHEN COALESCE(dues_cleared,0)=0 THEN COALESCE(remaining,0) ELSE 0 END) AS outstanding,
           SUM(CASE WHEN COALESCE(dues_cleared,0)=1 THEN COALESCE(remaining,0) ELSE 0 END) AS cleared
    FROM orders WHERE customer_id=?
");
$totals->execute([$customerId]);
$sum = $totals->fetch();

$flashOk  = flash('customer_ok');
$flashErr = flash('customer_err');
?>
<div class="page-header">
  <h2>&#128101; Customer Orders &mdash; <?= h($customer['name']) ?></h2>
  <div class="flex-row">
    <a href="?page=order_new&prefill_customer=<?= h($customerId) ?>" class="btn btn-success btn-sm">+ New Order</a>
    <a href="?page=customers" class="btn btn-sm" style="background:#546e7a;color:#fff;">&#8592; Back</a>
  </div>
</div>

<?php if ($flashOk): ?><div class="alert alert-success"><?= h($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="alert alert-error"><?= h($flashErr) ?></div><?php endif; ?>

<div class="card">
  <div class="card-head">Customer Info</div>
  <div class="card-body">
    <table style="width:auto;">
      <tr><th>Name</th><td><?= h($customer['name']) ?></td><th>Phone</th><td><?= h($customer['phone'] ?? '-') ?></td></tr>
      <tr><th>Address</th><td colspan="3"><?= h($customer['address'] ?? '-') ?></td></tr>
      <?php if (isAdmin()): ?>
      <tr>
        <th>Total Orders</th><td><?= count($customerOrders) ?></td>
        <th>Total Sales</th><td><?= formatMoney($sum['t'] ?? 0) ?></td>
      </tr>
      <tr>
        <th>Total Advance</th><td><?= formatMoney($sum['a'] ?? 0) ?></td>
        <th>Outstanding</th>
        <td class="bold <?= (float)($sum['outstanding'] ?? 0) > 0 ? 'red' : 'green' ?>">
          <?= formatMoney($sum['outstanding'] ?? 0) ?>
        </td>
      </tr>
      <?php if ((float)($sum['cleared'] ?? 0) > 0): ?>
      <tr>
        <th>Dues Cleared</th>
        <td class="bold green" colspan="3"><?= formatMoney($sum['cleared'] ?? 0) ?> &#10003; (marked as paid)</td>
      </tr>
      <?php endif; ?>
      <?php endif; ?>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-head">Order History</div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($customerOrders)): ?>
    <p style="padding:12px; color:#999; text-align:center;">No orders yet.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Date</th>
          <th>Delivery</th>
          <th>Suit Type</th>
          <?php if (isAdmin()): ?><th>Amount</th><th>Advance</th><th>Pending</th><?php endif; ?>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customerOrders as $o): ?>
        <?php
          $remaining  = (float)($o['remaining'] ?? 0);
          $isCleared  = (int)($o['dues_cleared'] ?? 0) === 1;
          $hasArrears = $remaining > 0 && !$isCleared;
        ?>
        <tr>
          <td class="bold"><?= h($o['order_no']) ?></td>
          <td><?= formatDate($o['order_date']) ?></td>
          <td><?= formatDate($o['delivery_date']) ?></td>
          <td><?= h($o['suit_type'] ?? '-') ?></td>
          <?php if (isAdmin()): ?>
          <td><?= formatMoney($o['total_price']) ?></td>
          <td><?= formatMoney($o['advance_paid']) ?></td>
          <td class="bold <?= $hasArrears ? 'red' : 'green' ?>">
            <?= formatMoney($remaining) ?>
            <?php if ($isCleared && $remaining > 0): ?>
            <span style="font-size:10px; background:#e8f5e9; color:#2e7d32; padding:1px 4px; border-radius:2px; font-weight:normal;">cleared</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
          <td><span class="badge badge-<?= h($o['status']) ?>"><?= h(ucfirst($o['status'])) ?></span></td>
          <td>
            <a href="?page=order_edit&id=<?= h($o['id']) ?>" class="btn btn-info btn-sm">Edit</a>
            <a href="?page=invoice&id=<?= h($o['id']) ?>&type=customer" class="btn btn-primary btn-sm" target="_blank">Invoice</a>
            <?php if (isAdmin() && $hasArrears): ?>
            <form method="POST" action="?action=clear_dues" style="display:inline;" onsubmit="return confirm('Mark Rs.<?= number_format($remaining,0) ?> as paid/cleared for Order <?= h($o['order_no']) ?>?');">
              <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
              <input type="hidden" name="order_id" value="<?= h($o['id']) ?>">
              <input type="hidden" name="customer_id" value="<?= h($customerId) ?>">
              <button type="submit" class="btn btn-success btn-sm">&#10003; Mark Paid</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
