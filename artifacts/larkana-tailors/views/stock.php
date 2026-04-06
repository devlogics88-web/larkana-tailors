<?php
$stocks = getStockItems();
?>
<div class="page-header">
  <h2>&#128229; Stock Management (اسٹاک)</h2>
</div>

<?php if ($msg = flash('stock_ok')): ?>
<div class="alert alert-success"><?= h($msg) ?></div>
<?php endif; ?>
<?php if ($err = flash('stock_err')): ?>
<div class="alert alert-error"><?= h($err) ?></div>
<?php endif; ?>

<div class="form-grid-2" style="align-items:start;">

<!-- ADD/EDIT FORM -->
<div class="card" id="stock-form">
  <div class="card-head">
    <span id="stock_form_title">Add New Stock Item (نیا اسٹاک آئٹم)</span>
    <a href="#" onclick="resetStock();return false;" class="btn btn-sm" style="float:right;background:#78909c;color:#fff;">&#8635; Reset</a>
  </div>
  <div class="card-body">
    <form method="POST" action="?action=save_stock">
      <input type="hidden" name="stock_id" id="stock_id" value="">
      <div class="form-group mb-8">
        <label>Brand Name (برانڈ نام) *</label>
        <input type="text" name="brand_name" id="brand_name" required placeholder="e.g. Pasha, Gul Ahmed, J.">
      </div>
      <div class="form-group mb-8">
        <label>Cloth Type (کپڑے کی قسم)</label>
        <input type="text" name="cloth_type" id="cloth_type" placeholder="e.g. Cotton, Lawn, Khaddar, Wash&Wear">
      </div>
      <div class="form-grid-2 mb-8">
        <div class="form-group">
          <label>Total Meters (کل میٹر) *</label>
          <input type="number" name="total_meters" id="total_meters" step="0.5" min="0" required placeholder="e.g. 100">
        </div>
        <div class="form-group">
          <label>Available Meters (موجود میٹر) *</label>
          <input type="number" name="avail_meters" id="avail_meters" step="0.5" min="0" required placeholder="e.g. 80">
        </div>
      </div>
      <div class="form-grid-2 mb-8">
        <div class="form-group">
          <label>Cost / Meter (خریداری قیمت) Rs.</label>
          <input type="number" name="cost_meter" id="cost_meter" step="1" min="0" placeholder="e.g. 350">
        </div>
        <div class="form-group">
          <label>Sell / Meter (فروخت قیمت) Rs.</label>
          <input type="number" name="sell_meter" id="sell_meter" step="1" min="0" placeholder="e.g. 500">
        </div>
      </div>
      <div class="form-group mb-8">
        <label>Notes (نوٹس)</label>
        <input type="text" name="stock_notes" id="stock_notes" placeholder="Any notes...">
      </div>
      <button type="submit" class="btn btn-success">&#10003; Save Stock Item</button>
    </form>
  </div>
</div>

<!-- STOCK LIST -->
<div class="card">
  <div class="card-head">Stock List (اسٹاک فہرست) &mdash; <?= count($stocks) ?> items</div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($stocks)): ?>
    <p style="padding:12px; color:#999; text-align:center;">No stock items added yet.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Brand</th>
          <th>Type</th>
          <th>Total M</th>
          <th>Avail M</th>
          <th>Cost/M</th>
          <th>Sell/M</th>
          <th>Value</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stocks as $s): 
          $low = $s['available_meters'] < 5;
        ?>
        <tr>
          <td class="bold"><?= h($s['brand_name']) ?></td>
          <td><?= h($s['cloth_type'] ?? '-') ?></td>
          <td><?= h($s['total_meters']) ?>m</td>
          <td class="<?= $low ? 'red bold' : 'green bold' ?>"><?= h($s['available_meters']) ?>m <?= $low ? '&#9888;' : '' ?></td>
          <td><?= $s['cost_per_meter'] ? formatMoney($s['cost_per_meter']) : '-' ?></td>
          <td><?= $s['sell_per_meter'] ? formatMoney($s['sell_per_meter']) : '-' ?></td>
          <td><?= formatMoney($s['available_meters'] * $s['cost_per_meter']) ?></td>
          <td style="white-space:nowrap;">
            <a href="#" class="btn btn-info btn-sm" onclick='editStock(<?= json_encode($s) ?>);return false;'>Edit</a>
            <a href="?action=delete_stock&id=<?= h($s['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Delete stock item: <?= h($s['brand_name']) ?>?')">Del</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

</div>
