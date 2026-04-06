<?php
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$orders = getOrders(['search' => $search, 'status' => $status]);
?>
<div class="page-header">
  <h2>&#128196; All Orders (تمام آرڈرز)</h2>
  <a href="?page=order_new" class="btn btn-success btn-sm">+ New Order</a>
</div>

<div class="card">
  <div class="card-body" style="padding:8px;">
    <form method="GET" action="" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <input type="hidden" name="page" value="orders">
      <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search order#, customer name or phone..." style="flex:1; min-width:200px; padding:5px 8px; border:1px solid #ccc; font-size:13px;">
      <select name="status" style="padding:5px; border:1px solid #ccc; font-size:13px;">
        <option value="">All Status</option>
        <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
        <option value="ready" <?= $status==='ready'?'selected':'' ?>>Ready</option>
        <option value="delivered" <?= $status==='delivered'?'selected':'' ?>>Delivered</option>
        <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>Cancelled</option>
      </select>
      <button type="submit" class="btn btn-primary">&#128269; Filter</button>
      <a href="?page=orders" class="btn" style="background:#78909c;color:#fff;">Clear</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0;">
    <?php if (empty($orders)): ?>
    <p style="padding:14px; text-align:center; color:#999;">No orders found.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer (کسٹمر)</th>
          <th>Phone</th>
          <th>Suit Type</th>
          <th>Order Date</th>
          <th>Delivery Date</th>
          <?php if (isAdmin()): ?><th>Amount</th><th>Advance</th><th>Remaining</th><?php endif; ?>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td class="bold"><?= h($o['order_no']) ?></td>
          <td><?= h($o['customer_name']) ?></td>
          <td><?= h($o['customer_phone'] ?? '') ?></td>
          <td><?= h($o['suit_type'] ?? '-') ?></td>
          <td><?= formatDate($o['order_date']) ?></td>
          <td <?= strtotime($o['delivery_date'] ?? '') < time() && ($o['status'] ?? '') === 'pending' ? 'style="color:#c62828;font-weight:bold;"' : '' ?>>
            <?= formatDate($o['delivery_date']) ?>
          </td>
          <?php if (isAdmin()): ?>
          <td class="bold"><?= formatMoney($o['total_price']) ?></td>
          <td><?= formatMoney($o['advance_paid']) ?></td>
          <td class="<?= ($o['remaining'] ?? 0) > 0 ? 'red' : 'green' ?> bold"><?= formatMoney($o['remaining']) ?></td>
          <?php endif; ?>
          <td><span class="badge badge-<?= h($o['status']) ?>"><?= h(ucfirst($o['status'])) ?></span></td>
          <td style="white-space:nowrap;">
            <a href="?page=order_edit&id=<?= h($o['id']) ?>" class="btn btn-info btn-sm">Edit</a>
            <a href="?page=invoice&id=<?= h($o['id']) ?>&type=customer" class="btn btn-primary btn-sm" target="_blank">Inv.</a>
            <a href="?page=invoice&id=<?= h($o['id']) ?>&type=labour" class="btn btn-print btn-sm" target="_blank">Labour</a>
            <?php if (isAdmin()): ?>
            <a href="?action=delete_order&id=<?= h($o['id']) ?>&csrf=<?= h(getCsrf()) ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Delete order <?= h($o['order_no']) ?>?')">Del</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="padding:6px 10px; color:#666; font-size:11px;">Showing <?= count($orders) ?> orders</div>
    <?php endif; ?>
  </div>
</div>
