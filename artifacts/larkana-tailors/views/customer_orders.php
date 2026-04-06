<?php
$customerId = (int)($_GET['customer_id'] ?? 0);
if (!$customerId) { header('Location: ?page=customers'); exit; }
$customer = getCustomer($customerId);
if (!$customer) { header('Location: ?page=customers'); exit; }
$db = getDB();
$stmt = $db->prepare("
    SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC
");
$stmt->execute([$customerId]);
$customerOrders = $stmt->fetchAll();
$totals = $db->prepare("SELECT SUM(total_price) as t, SUM(advance_paid) as a, SUM(remaining) as r FROM orders WHERE customer_id=?");
$totals->execute([$customerId]);
$sum = $totals->fetch();
?>
<div class="page-header">
  <h2>&#128101; Customer Orders &mdash; <?= h($customer['name']) ?></h2>
  <div class="flex-row">
    <a href="?page=order_new&prefill_customer=<?= h($customerId) ?>" class="btn btn-success btn-sm">+ New Order</a>
    <a href="?page=customers" class="btn btn-sm" style="background:#546e7a;color:#fff;">&#8592; Back</a>
  </div>
</div>

<div class="card">
  <div class="card-head">Customer Info (کسٹمر کی معلومات)</div>
  <div class="card-body">
    <table style="width:auto;">
      <tr><th>Name (نام)</th><td><?= h($customer['name']) ?></td><th>Phone (فون)</th><td><?= h($customer['phone'] ?? '-') ?></td></tr>
      <tr><th>Address (پتہ)</th><td colspan="3"><?= h($customer['address'] ?? '-') ?></td></tr>
      <?php if (isAdmin()): ?>
      <tr>
        <th>Total Orders</th><td><?= count($customerOrders) ?></td>
        <th>Total Sales</th><td><?= formatMoney($sum['t'] ?? 0) ?></td>
      </tr>
      <tr>
        <th>Total Advance</th><td><?= formatMoney($sum['a'] ?? 0) ?></td>
        <th>Remaining</th><td class="bold red"><?= formatMoney($sum['r'] ?? 0) ?></td>
      </tr>
      <?php endif; ?>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-head">Order History (آرڈر تاریخ)</div>
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
          <?php if (isAdmin()): ?><th>Amount</th><th>Advance</th><th>Remaining</th><?php endif; ?>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customerOrders as $o): ?>
        <tr>
          <td class="bold"><?= h($o['order_no']) ?></td>
          <td><?= formatDate($o['order_date']) ?></td>
          <td><?= formatDate($o['delivery_date']) ?></td>
          <td><?= h($o['suit_type'] ?? '-') ?></td>
          <?php if (isAdmin()): ?>
          <td><?= formatMoney($o['total_price']) ?></td>
          <td><?= formatMoney($o['advance_paid']) ?></td>
          <td class="<?= ($o['remaining'] ?? 0) > 0 ? 'red' : 'green' ?> bold"><?= formatMoney($o['remaining']) ?></td>
          <?php endif; ?>
          <td><span class="badge badge-<?= h($o['status']) ?>"><?= h(ucfirst($o['status'])) ?></span></td>
          <td>
            <a href="?page=order_edit&id=<?= h($o['id']) ?>" class="btn btn-info btn-sm">Edit</a>
            <a href="?page=invoice&id=<?= h($o['id']) ?>&type=customer" class="btn btn-primary btn-sm" target="_blank">Invoice</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
