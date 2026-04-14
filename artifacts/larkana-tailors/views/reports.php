<?php
$data = getReportData();
?>
<div class="page-header">
  <h2>&#128200; Reports &amp; Summary</h2>
  <span class="small">Admin Only &mdash; <?= date('d-M-Y') ?></span>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(4,1fr);">
  <div class="stat-box">
    <div class="stat-label">Total Orders</div>
    <div class="stat-value"><?= h($data['total_orders']) ?></div>
  </div>
  <div class="stat-box green">
    <div class="stat-label">Total Sales</div>
    <div class="stat-value" style="font-size:16px;"><?= formatMoney($data['total_sales']) ?></div>
  </div>
  <div class="stat-box">
    <div class="stat-label">Advance Received</div>
    <div class="stat-value" style="font-size:16px;"><?= formatMoney($data['total_advance']) ?></div>
  </div>
  <div class="stat-box red">
    <div class="stat-label">Outstanding (Unpaid)</div>
    <div class="stat-value" style="font-size:16px;"><?= formatMoney($data['total_remaining']) ?></div>
  </div>
</div>

<?php if ((float)($data['total_cleared_dues'] ?? 0) > 0 || (float)($data['total_cash_collected'] ?? 0) > 0): ?>
<div class="stats-grid" style="grid-template-columns: repeat(3,1fr); margin-bottom:10px;">
  <div class="stat-box gold">
    <div class="stat-label">Dues Cleared / Collected</div>
    <div class="stat-value" style="font-size:16px;"><?= formatMoney($data['total_cleared_dues'] ?? 0) ?></div>
    <div class="stat-sub">Remaining amounts marked as paid</div>
  </div>
  <div class="stat-box green">
    <div class="stat-label">Total Cash Collected</div>
    <div class="stat-value" style="font-size:16px;"><?= formatMoney($data['total_cash_collected'] ?? 0) ?></div>
    <div class="stat-sub">Advance + Cleared Dues</div>
  </div>
  <div class="stat-box" style="text-align:left;">
    <div class="stat-label" style="margin-bottom:4px;">Top Debtors</div>
    <?php foreach (($data['arrears_customers'] ?? []) as $ac): ?>
    <div style="font-size:11px; display:flex; justify-content:space-between; padding:1px 0; border-bottom:1px solid #eee;">
      <span><?= h($ac['name']) ?> <?= $ac['phone'] ? '<span style="color:#9e9e9e;">('.h($ac['phone']).')</span>' : '' ?></span>
      <span class="bold red"><?= formatMoney($ac['outstanding']) ?></span>
    </div>
    <?php endforeach; ?>
    <?php if (empty($data['arrears_customers'])): ?>
    <div class="small" style="color:var(--green);">&#10003; No outstanding arrears!</div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="form-grid-2" style="align-items:start;">

<div class="card">
  <div class="card-head">&#128178; Profit / Loss Estimate</div>
  <div class="card-body">
    <table>
      <tr><th style="width:60%;">Description</th><th>Amount</th></tr>
      <tr><td>Total Revenue (all orders, net after discounts)</td><td class="bold green"><?= formatMoney($data['total_sales']) ?></td></tr>
      <?php if ($data['total_discounts'] > 0): ?>
      <tr><td>Total Discounts Given</td><td class="bold" style="color:#e65100;">- <?= formatMoney($data['total_discounts']) ?></td></tr>
      <?php endif; ?>
      <tr><td>Shop Cloth Cost (cost of cloth used from stock)</td><td class="bold red"><?= formatMoney($data['stock_cost']) ?></td></tr>
      <tr style="background:#e8f5e9;">
        <td class="bold">Estimated Gross Profit <span style="font-weight:normal;font-size:11px;">(Revenue &minus; Shop Cloth Cost)</span></td>
        <td class="bold <?= $data['estimated_profit'] >= 0 ? 'green' : 'red' ?>" style="font-size:14px;">
          <?= formatMoney($data['estimated_profit']) ?>
          <?= $data['estimated_profit'] >= 0 ? ' &#9650;' : ' &#9660;' ?>
        </td>
      </tr>
      <tr><td colspan="2" style="background:#e3f2fd; padding:3px 8px; font-size:10px; color:#555; font-weight:bold;">Cash Collection Breakdown</td></tr>
      <tr><td>Advance Payments Received</td><td class="bold green"><?= formatMoney($data['total_advance']) ?></td></tr>
      <tr>
        <td>Dues Cleared / Collected <span style="font-size:10px; color:#888;">(arrears paid later)</span></td>
        <td class="bold gold"><?= formatMoney($data['total_cleared_dues'] ?? 0) ?></td>
      </tr>
      <tr style="background:#f3e5f5;">
        <td class="bold">Total Cash Collected <span style="font-weight:normal;font-size:11px;">(Advance + Cleared Dues)</span></td>
        <td class="bold" style="color:#6a1b9a; font-size:14px;"><?= formatMoney($data['total_cash_collected'] ?? 0) ?></td>
      </tr>
      <tr>
        <td class="bold red">Outstanding Arrears <span style="font-weight:normal;font-size:11px;">(unpaid remaining)</span></td>
        <td class="bold red"><?= formatMoney($data['total_remaining']) ?></td>
      </tr>
    </table>
    <p class="small" style="margin-top:6px;">* Estimated only. Stitching labour cost not included.</p>
  </div>
</div>

<div class="card">
  <div class="card-head">&#128196; Orders by Status</div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead><tr><th>Status</th><th>Count</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($data['by_status'] as $row): ?>
        <tr>
          <td><span class="badge badge-<?= h($row['status']) ?>"><?= h(ucfirst($row['status'])) ?></span></td>
          <td class="bold text-center"><?= h($row['cnt']) ?></td>
          <td><?= formatMoney($row['total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<?php if (!empty($data['monthly'])): ?>
<div class="card">
  <div class="card-head">&#128197; Monthly Summary</div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead>
        <tr>
          <th>Month</th>
          <th>Orders</th>
          <th>Sales</th>
          <th>Discounts</th>
          <th>Advance</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['monthly'] as $row): ?>
        <tr>
          <td class="bold"><?= h($row['month']) ?></td>
          <td class="text-center bold"><?= h($row['orders']) ?></td>
          <td><?= formatMoney($row['sales']) ?></td>
          <td style="color:#e65100;"><?= $row['discounts'] > 0 ? '- '.formatMoney($row['discounts']) : '-' ?></td>
          <td><?= formatMoney($row['advance']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($data['arrears_customers'])): ?>
<div class="card">
  <div class="card-head">&#9888; Top 10 Customers with Outstanding Arrears</div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead><tr><th>Customer</th><th>Phone</th><th>Outstanding Amount</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($data['arrears_customers'] as $ac): ?>
        <tr>
          <td class="bold"><?= h($ac['name']) ?></td>
          <td><?= h($ac['phone'] ?? '-') ?></td>
          <td class="bold red"><?= formatMoney($ac['outstanding']) ?></td>
          <td>
            <a href="?page=customer_orders&customer_id=<?= h($ac['id']) ?>" class="btn btn-info btn-sm">View Orders</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
