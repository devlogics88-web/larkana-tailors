<?php
$stocks      = getStockItems();
$buttonTypes = getButtonTypes();
?>
<div class="page-header">
  <h2>&#128229; Stock Management</h2>
</div>

<?php if ($msg = flash('stock_ok')): ?>
<div class="alert alert-success"><?= h($msg) ?></div>
<?php endif; ?>
<?php if ($err = flash('stock_err')): ?>
<div class="alert alert-error"><?= h($err) ?></div>
<?php endif; ?>

<div class="form-grid-2" style="align-items:start;">

<!-- ADD/EDIT CLOTH STOCK FORM -->
<div class="card" id="stock-form">
  <div class="card-head">
    <span id="stock_form_title">Add New Stock Item</span>
    <a href="#" onclick="resetStock();return false;" class="btn btn-sm" style="float:right;background:#78909c;color:#fff;">&#8635; Reset</a>
  </div>
  <div class="card-body">
    <form method="POST" action="?action=save_stock">
      <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
      <input type="hidden" name="stock_id" id="stock_id" value="">
      <div class="form-group mb-8">
        <label>Brand Name *</label>
        <input type="text" name="brand_name" id="brand_name" required placeholder="e.g. Pasha, Gul Ahmed, J.">
      </div>
      <div class="form-group mb-8">
        <label>Cloth Type</label>
        <input type="text" name="cloth_type" id="cloth_type" placeholder="e.g. Cotton, Lawn, Khaddar, Wash&Wear">
      </div>
      <div class="form-grid-2 mb-8">
        <div class="form-group">
          <label>Total Meters *</label>
          <input type="number" name="total_meters" id="total_meters" step="0.5" min="0" required placeholder="e.g. 100">
        </div>
        <div class="form-group">
          <label>Available Meters *</label>
          <input type="number" name="avail_meters" id="avail_meters" step="0.5" min="0" required placeholder="e.g. 80">
        </div>
      </div>
      <div class="form-grid-2 mb-8">
        <div class="form-group">
          <label>Cost / Meter Rs.</label>
          <input type="number" name="cost_meter" id="cost_meter" step="1" min="0" placeholder="e.g. 350">
        </div>
        <div class="form-group">
          <label>Sell / Meter Rs.</label>
          <input type="number" name="sell_meter" id="sell_meter" step="1" min="0" placeholder="e.g. 500">
        </div>
      </div>
      <div class="form-group mb-8">
        <label>Notes</label>
        <input type="text" name="stock_notes" id="stock_notes" placeholder="Any notes...">
      </div>
      <button type="submit" class="btn btn-success">&#10003; Save Stock Item</button>
    </form>
  </div>
</div>

<!-- CLOTH STOCK LIST -->
<div class="card">
  <div class="card-head">Cloth Stock &mdash; <?= count($stocks) ?> items</div>
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
            <a href="#" class="btn btn-info btn-sm"
               data-stock="<?= h(json_encode($s, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)) ?>"
               onclick="editStockFromData(this);return false;">Edit</a>
            <form method="POST" action="?action=delete_stock" style="display:inline;" onsubmit="return confirmDelete('Delete stock item: <?= h($s['brand_name']) ?>?')">
              <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
              <input type="hidden" name="id"   value="<?= h($s['id']) ?>">
              <button type="submit" class="btn btn-danger btn-sm">Del</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

</div><!-- end cloth stock grid -->

<!-- BUTTONS SECTION -->
<div class="form-grid-2" style="align-items:start; margin-top:0;">

<!-- ADD/EDIT BUTTON TYPE FORM -->
<div class="card" id="btn-form">
  <div class="card-head">
    <span id="btn_form_title">Add Button Type</span>
    <a href="#" onclick="resetBtn();return false;" class="btn btn-sm" style="float:right;background:#78909c;color:#fff;">&#8635; Reset</a>
  </div>
  <div class="card-body">
    <form method="POST" action="?action=save_button_type">
      <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
      <input type="hidden" name="bt_id" id="bt_id" value="">
      <div class="form-group mb-8">
        <label>Button Name *</label>
        <input type="text" name="bt_name" id="bt_name" required placeholder="e.g. Fancy Button, Tich Button">
      </div>
      <div class="form-group mb-8">
        <label>Price Rs. *</label>
        <input type="number" name="bt_price" id="bt_price" min="0" step="50" required placeholder="e.g. 200">
      </div>
      <button type="submit" class="btn btn-success">&#10003; Save Button Type</button>
    </form>
  </div>
</div>

<!-- BUTTON TYPE LIST -->
<div class="card">
  <div class="card-head">Buttons &mdash; <?= count($buttonTypes) ?> types</div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($buttonTypes)): ?>
    <p style="padding:12px; color:#999; text-align:center;">No button types added yet.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Button Name</th>
          <th>Price</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($buttonTypes as $bt): ?>
        <tr>
          <td class="bold"><?= h($bt['name']) ?></td>
          <td class="bold"><?= formatMoney($bt['price']) ?></td>
          <td style="white-space:nowrap;">
            <a href="#" class="btn btn-info btn-sm"
               onclick="editBtn(<?= h($bt['id']) ?>, <?= h(json_encode($bt['name'])) ?>, <?= h($bt['price']) ?>);return false;">Edit</a>
            <form method="POST" action="?action=delete_button_type" style="display:inline;"
                  onsubmit="return confirm('Delete button type: <?= h($bt['name']) ?>?')">
              <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
              <input type="hidden" name="bt_id" value="<?= h($bt['id']) ?>">
              <button type="submit" class="btn btn-danger btn-sm">Del</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

</div><!-- end button section grid -->

<script>
function resetStock() {
    document.getElementById('stock_id').value = '';
    document.getElementById('stock_form_title').textContent = 'Add New Stock Item';
    ['brand_name','cloth_type','total_meters','avail_meters','cost_meter','sell_meter','stock_notes'].forEach(function(id){
        var el = document.getElementById(id); if(el) el.value = '';
    });
}
function editStockFromData(el) {
    var s = JSON.parse(el.getAttribute('data-stock'));
    document.getElementById('stock_id').value = s.id;
    document.getElementById('stock_form_title').textContent = 'Edit Stock Item';
    document.getElementById('brand_name').value  = s.brand_name  || '';
    document.getElementById('cloth_type').value  = s.cloth_type  || '';
    document.getElementById('total_meters').value= s.total_meters|| '';
    document.getElementById('avail_meters').value= s.available_meters|| '';
    document.getElementById('cost_meter').value  = s.cost_per_meter || '';
    document.getElementById('sell_meter').value  = s.sell_per_meter || '';
    document.getElementById('stock_notes').value = s.notes || '';
    document.getElementById('stock-form').scrollIntoView({behavior:'smooth'});
}
function resetBtn() {
    document.getElementById('bt_id').value = '';
    document.getElementById('bt_name').value = '';
    document.getElementById('bt_price').value = '';
    document.getElementById('btn_form_title').textContent = 'Add Button Type';
}
function editBtn(id, name, price) {
    document.getElementById('bt_id').value = id;
    document.getElementById('bt_name').value = name;
    document.getElementById('bt_price').value = price;
    document.getElementById('btn_form_title').textContent = 'Edit Button Type';
    document.getElementById('btn-form').scrollIntoView({behavior:'smooth'});
}
function confirmDelete(msg) { return confirm(msg); }
</script>
