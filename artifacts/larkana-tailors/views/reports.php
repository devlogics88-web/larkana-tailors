<?php
$data = getReportData();
?>
<div class="page-header">
  <h2>&#128200; Reports &amp; Summary (رپورٹس)</h2>
  <span class="small">Admin Only &mdash; <?= date('d-M-Y') ?></span>
</div>

<div class="stats-grid">
  <div class="stat-box">
    <div class="stat-label">Total Orders</div>
    <div class="stat-value"><?= h($data['total_orders']) ?></div>
  </div>
  <div class="stat-box green">
    <div class="stat-label">Total Sales (کل فروخت)</div>
    <div class="stat-value" style="font-size:16px;"><?= formatMoney($data['total_sales']) ?></div>
  </div>
  <div class="stat-box">
    <div class="stat-label">Advance Received (ایڈوانس)</div>
    <div class="stat-value" style="font-size:16px;"><?= formatMoney($data['total_advance']) ?></div>
  </div>
  <div class="stat-box red">
    <div class="stat-label">Outstanding (باقی)</div>
    <div class="stat-value" style="font-size:16px;"><?= formatMoney($data['total_remaining']) ?></div>
  </div>
</div>

<div class="form-grid-2" style="align-items:start;">

<div class="card">
  <div class="card-head">&#128178; Profit / Loss Estimate (منافع / نقصان)</div>
  <div class="card-body">
    <table>
      <tr><th style="width:60%;">Description</th><th>Amount</th></tr>
      <tr><td>Total Revenue (all orders, all cloth sources)</td><td class="bold green"><?= formatMoney($data['total_sales']) ?></td></tr>
      <tr><td>Shop Cloth Cost (cost of cloth used from stock)</td><td class="bold red"><?= formatMoney($data['stock_cost']) ?></td></tr>
      <tr style="background:#e8f5e9;">
        <td class="bold">Estimated Gross Profit <span style="font-weight:normal;font-size:11px;">(Revenue &minus; Shop Cloth Cost)</span></td>
        <td class="bold <?= $data['estimated_profit'] >= 0 ? 'green' : 'red' ?>" style="font-size:14px;">
          <?= formatMoney($data['estimated_profit']) ?>
          <?= $data['estimated_profit'] >= 0 ? ' &#9650;' : ' &#9660;' ?>
        </td>
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
  <div class="card-head">&#128197; Monthly Summary (ماہانہ خلاصہ)</div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead>
        <tr>
          <th>Month</th>
          <th>Orders</th>
          <th>Sales</th>
          <th>Advance</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data['monthly'] as $row): ?>
        <tr>
          <td class="bold"><?= h($row['month']) ?></td>
          <td class="text-center bold"><?= h($row['orders']) ?></td>
          <td><?= formatMoney($row['sales']) ?></td>
          <td><?= formatMoney($row['advance']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
