<?php $stats = getDashboardStats(); ?>
<div class="page-header">
  <h2>&#128202; Dashboard (ڈیش بورڈ)</h2>
  <span class="small"><?= date('l, d-M-Y') ?></span>
</div>

<div class="stats-grid">
  <div class="stat-box">
    <div class="stat-label">Total Orders</div>
    <div class="stat-value"><?= h($stats['total_orders']) ?></div>
    <div class="stat-sub">All Time</div>
  </div>
  <div class="stat-box orange">
    <div class="stat-label">Pending</div>
    <div class="stat-value"><?= h($stats['pending_orders']) ?></div>
    <div class="stat-sub">In Progress</div>
  </div>
  <div class="stat-box green">
    <div class="stat-label">Ready</div>
    <div class="stat-value"><?= h($stats['ready_orders']) ?></div>
    <div class="stat-sub">For Delivery</div>
  </div>
  <div class="stat-box">
    <div class="stat-label">Delivered</div>
    <div class="stat-value"><?= h($stats['delivered_orders']) ?></div>
    <div class="stat-sub">Completed</div>
  </div>
</div>

<?php if (isAdmin()): ?>
<div class="stats-grid">
  <div class="stat-box">
    <div class="stat-label">Total Sales (کل فروخت)</div>
    <div class="stat-value bold" style="font-size:16px;"><?= formatMoney($stats['total_sales']) ?></div>
    <div class="stat-sub">All Orders</div>
  </div>
  <div class="stat-box green">
    <div class="stat-label">Advance Received (ایڈوانس)</div>
    <div class="stat-value bold" style="font-size:16px;"><?= formatMoney($stats['total_advance']) ?></div>
    <div class="stat-sub">Collected</div>
  </div>
  <div class="stat-box red">
    <div class="stat-label">Remaining (باقی)</div>
    <div class="stat-value bold" style="font-size:16px;"><?= formatMoney($stats['total_remaining']) ?></div>
    <div class="stat-sub">Outstanding</div>
  </div>
  <div class="stat-box gold">
    <div class="stat-label">Due Today</div>
    <?php
    $dueToday = getDB()->prepare("SELECT COUNT(*) FROM orders WHERE delivery_date=? AND status!='delivered'")->execute([date('Y-m-d')]);
    $dueToday = getDB()->prepare("SELECT COUNT(*) FROM orders WHERE delivery_date=? AND status!='delivered'");
    $dueToday->execute([date('Y-m-d')]);
    $dueTodayCount = $dueToday->fetchColumn();
    ?>
    <div class="stat-value bold" style="font-size:16px;"><?= h($dueTodayCount) ?></div>
    <div class="stat-sub">Orders Due</div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    &#128196; Recent Orders (حالیہ آرڈرز)
    <a href="?page=order_new" class="btn btn-success btn-sm" style="float:right;">+ New Order</a>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($stats['recent_orders'])): ?>
    <p style="padding:12px; color:#999; text-align:center;">No orders yet. <a href="?page=order_new">Create first order</a></p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer (کسٹمر)</th>
          <th>Phone</th>
          <th>Date</th>
          <th>Delivery</th>
          <th>Amount</th>
          <th>Advance</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stats['recent_orders'] as $o): ?>
        <tr>
          <td class="bold"><?= h($o['order_no']) ?></td>
          <td><?= h($o['customer_name']) ?></td>
          <td><?= h($o['customer_phone'] ?? '') ?></td>
          <td><?= formatDate($o['order_date']) ?></td>
          <td><?= formatDate($o['delivery_date']) ?></td>
          <td class="bold"><?= formatMoney($o['total_price']) ?></td>
          <td><?= formatMoney($o['advance_paid']) ?></td>
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
