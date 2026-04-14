<?php
$stocks      = getStockItems();
$buttonTypes = getButtonTypes();
?>
<div class="page-header">
  <h2>&#128229; Stock Management</h2>
  <div style="display:flex;gap:8px;">
    <a href="?action=export_stock_csv" class="btn btn-sm" style="background:#1565c0;color:#fff;">&#11015; Download CSV</a>
    <button type="button" class="btn btn-sm" style="background:#0d47a1;color:#fff;" onclick="toggleImport()">&#11014; Import CSV</button>
  </div>
</div>

<?php if ($msg = flash('stock_ok')): ?>
<div class="alert alert-success"><?= h($msg) ?></div>
<?php endif; ?>
<?php if ($err = flash('stock_err')): ?>
<div class="alert alert-error"><?= h($err) ?></div>
<?php endif; ?>

<!-- IMPORT CSV PANEL -->
<div class="card" id="import-card" style="display:none; margin-bottom:12px;">
  <div class="card-head" style="background:#0d47a1;color:#fff;">&#11014; Import Stock from CSV</div>
  <div class="card-body">
    <p style="margin-bottom:8px;color:#546e7a;font-size:12px;">
      CSV must have columns:
      <strong>ID (ignored), Brand Name, Cloth Type, Stock Date, Total Meters, Available Meters, Cost/Meter, Sell/Meter, Sell Mode (meter/box/both), Box Quantity, Box Price, Notes</strong>.<br>
      First row (header) is skipped automatically. Existing items are NOT overwritten &mdash; only new rows are added.
    </p>
    <form method="POST" action="?action=import_stock_csv" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <input type="file" name="csv_file" accept=".csv,text/csv" required
               style="border:1px solid var(--border);padding:4px;background:#fff;color:#333;font-size:12px;">
        <button type="submit" class="btn btn-success btn-sm">&#11014; Import</button>
        <button type="button" class="btn btn-sm" style="background:#546e7a;color:#fff;"
                onclick="toggleImport()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<div class="form-grid-2" style="align-items:start;">

<!-- ADD/EDIT CLOTH STOCK FORM -->
<div class="card" id="stock-form">
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;">
    <span id="stock_form_title">Add New Stock Item</span>
    <a href="#" onclick="resetStock();return false;" class="btn btn-sm" style="background:#78909c;color:#fff;">&#8635; Reset</a>
  </div>
  <div class="card-body">
    <form method="POST" action="?action=save_stock">
      <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
      <input type="hidden" name="stock_id" id="stock_id" value="">
      <div class="form-grid-2 mb-8">
        <div class="form-group">
          <label>Brand Name *</label>
          <input type="text" name="brand_name" id="brand_name" required placeholder="e.g. Pasha, Gul Ahmed">
        </div>
        <div class="form-group">
          <label>Cloth Type</label>
          <input type="text" name="cloth_type" id="cloth_type" placeholder="e.g. Cotton, Lawn, Khaddar">
        </div>
      </div>
      <div class="form-group mb-8">
        <label>Date of Stock Received</label>
        <input type="date" name="stock_date" id="stock_date" value="<?= date('Y-m-d') ?>">
      </div>

      <!-- SELL BY — 3-state radio -->
      <div class="form-group mb-8">
        <label style="font-weight:bold;">Sell By</label>
        <div style="display:flex;gap:16px;margin-top:4px;">
          <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-weight:normal;">
            <input type="radio" name="sell_mode" id="sm_meter" value="meter" checked onchange="onSellModeChange()">
            Meter
          </label>
          <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-weight:normal;">
            <input type="radio" name="sell_mode" id="sm_box" value="box" onchange="onSellModeChange()">
            Box
          </label>
          <label style="display:flex;align-items:center;gap:4px;cursor:pointer;font-weight:normal;">
            <input type="radio" name="sell_mode" id="sm_both" value="both" onchange="onSellModeChange()">
            Both (Meter &amp; Box)
          </label>
        </div>
      </div>

      <!-- METER FIELDS — shown when sell_mode = meter or both -->
      <div id="meter-fields">
        <div class="form-grid-2 mb-8">
          <div class="form-group">
            <label>Total Meters</label>
            <input type="number" name="total_meters" id="total_meters" step="0.5" min="0" placeholder="e.g. 100">
          </div>
          <div class="form-group">
            <label>Available Meters</label>
            <input type="number" name="avail_meters" id="avail_meters" step="0.5" min="0" placeholder="e.g. 80">
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
      </div>

      <!-- BOX FIELDS — shown when sell_mode = box or both -->
      <div id="box-fields" style="display:none; background:#e8f5e9; padding:8px; border:1px solid #a5d6a7; margin-bottom:8px;">
        <div class="form-grid-2">
          <div class="form-group">
            <label>Box Qty (suits / pieces per box)</label>
            <input type="number" name="box_quantity" id="box_quantity" step="0.5" min="0" placeholder="e.g. 5">
          </div>
          <div class="form-group">
            <label>Box Set Price Rs.</label>
            <input type="number" name="box_price" id="box_price" step="50" min="0" placeholder="e.g. 8000">
          </div>
        </div>
      </div>

      <div class="form-group mb-8">
        <label>Notes</label>
        <input type="text" name="stock_notes" id="stock_notes" placeholder="Any notes about this batch...">
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
          <th>Date</th>
          <th>Sell By</th>
          <th>Meters</th>
          <th>Box Info</th>
          <th>Cost/M</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stocks as $s):
          $low      = (float)$s['available_meters'] < 5;
          $sellMode = $s['sell_mode'] ?? 'meter';
        ?>
        <tr>
          <td class="bold"><?= h($s['brand_name']) ?></td>
          <td><?= h($s['cloth_type'] ?? '-') ?></td>
          <td style="white-space:nowrap;font-size:11px;">
            <?= $s['stock_date'] ? date('d-M-Y', strtotime($s['stock_date'])) : '-' ?>
          </td>
          <td>
            <?php if ($sellMode === 'meter'): ?>
              <span style="background:#1565c0;color:#fff;padding:1px 5px;border-radius:3px;font-size:10px;">Meter</span>
            <?php elseif ($sellMode === 'box'): ?>
              <span style="background:#2e7d32;color:#fff;padding:1px 5px;border-radius:3px;font-size:10px;">Box</span>
            <?php else: ?>
              <span style="background:#6a1b9a;color:#fff;padding:1px 5px;border-radius:3px;font-size:10px;">Both</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($sellMode !== 'box'): ?>
              <span class="<?= $low ? 'red bold' : 'green bold' ?>"><?= h($s['available_meters']) ?>m</span>
              <?= $low ? ' &#9888;' : '' ?>
              <?php if ($s['sell_per_meter']): ?>
                <br><span style="font-size:10px;color:#555;">@ <?= formatMoney($s['sell_per_meter']) ?>/m</span>
              <?php endif; ?>
            <?php else: ?>
              <span style="color:#999;font-size:11px;">N/A</span>
            <?php endif; ?>
          </td>
          <td style="font-size:11px;">
            <?php if ($sellMode === 'box' || $sellMode === 'both'): ?>
              <strong><?= h($s['box_quantity'] ?? 0) ?></strong> pcs<br>
              <span style="color:#2e7d32;font-weight:bold;"><?= formatMoney($s['box_price'] ?? 0) ?></span>/box
            <?php else: ?>
              <span style="color:#999;">-</span>
            <?php endif; ?>
          </td>
          <td><?= $s['cost_per_meter'] ? formatMoney($s['cost_per_meter']) : '-' ?></td>
          <td style="white-space:nowrap;">
            <a href="#" class="btn btn-info btn-sm"
               data-stock="<?= h(json_encode($s, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)) ?>"
               onclick="editStockFromData(this);return false;">Edit</a>
            <form method="POST" action="?action=delete_stock" style="display:inline;"
                  onsubmit="return confirm('Delete stock item: <?= h($s['brand_name']) ?>?')">
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
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;">
    <span id="btn_form_title">Add Button Type</span>
    <a href="#" onclick="resetBtn();return false;" class="btn btn-sm" style="background:#78909c;color:#fff;">&#8635; Reset</a>
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
               data-btid="<?= (int)$bt['id'] ?>"
               data-btname="<?= h($bt['name']) ?>"
               data-btprice="<?= h($bt['price']) ?>"
               onclick="editBtnFromData(this);return false;">Edit</a>
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

<?php if (isAdmin()): ?>
<!-- DELETE ALL STOCKS -->
<div class="card" style="border:2px solid #c62828; margin-top:16px;">
  <div class="card-head" style="background:#c62828; color:#fff;">&#9888; Danger Zone — Delete All Stocks</div>
  <div class="card-body">
    <p style="color:#c62828; font-weight:bold; margin-bottom:8px;">
      WARNING: This will permanently delete ALL stock items. This cannot be undone.
    </p>
    <button type="button" class="btn btn-danger" onclick="showDeleteAllStocks()">&#128465; Delete All Stocks</button>
    <div id="delete-stocks-confirm" style="display:none; margin-top:12px; background:#fff3e0; padding:10px; border:1px solid #e65100;">
      <p style="margin:0 0 8px; color:#bf360c; font-weight:bold;">Type <strong>OK</strong> to confirm deletion of all stock data:</p>
      <form method="POST" action="?action=delete_all_stocks" onsubmit="return validateStockDelete(this)">
        <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
        <input type="text" name="confirm_word" id="confirm_word_stocks" autocomplete="off"
               style="width:100px;margin-right:8px;font-size:14px;font-weight:bold;letter-spacing:2px;" placeholder="OK">
        <button type="submit" class="btn btn-danger">Confirm Delete All Stocks</button>
        <button type="button" class="btn" style="background:#546e7a;color:#fff;"
                onclick="document.getElementById('delete-stocks-confirm').style.display='none'">Cancel</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function toggleImport() {
    var c = document.getElementById('import-card');
    c.style.display = c.style.display === 'none' ? 'block' : 'none';
}

function onSellModeChange() {
    var mf  = document.getElementById('meter-fields');
    var bf  = document.getElementById('box-fields');
    var val = document.querySelector('input[name="sell_mode"]:checked')?.value || 'meter';
    mf.style.display = (val === 'meter' || val === 'both') ? '' : 'none';
    bf.style.display = (val === 'box'   || val === 'both') ? '' : 'none';
}

function showDeleteAllStocks() {
    document.getElementById('delete-stocks-confirm').style.display = 'block';
    document.getElementById('confirm_word_stocks').focus();
}
function validateStockDelete(form) {
    var wordField = form.querySelector('[name="confirm_word"]');
    if (!wordField || wordField.value.trim() !== 'OK') {
        alert('You must type exactly OK to confirm.');
        return false;
    }
    return confirm('FINAL WARNING: All stock data will be permanently deleted. Proceed?');
}

function resetStock() {
    document.getElementById('stock_id').value = '';
    document.getElementById('stock_form_title').textContent = 'Add New Stock Item';
    ['brand_name','cloth_type','total_meters','avail_meters','cost_meter','sell_meter','stock_notes','box_quantity','box_price'].forEach(function(id){
        var el = document.getElementById(id); if(el) el.value = '';
    });
    document.getElementById('stock_date').value = '<?= date('Y-m-d') ?>';
    var r = document.getElementById('sm_meter');
    if (r) r.checked = true;
    onSellModeChange();
}

function editStockFromData(el) {
    var s = JSON.parse(el.getAttribute('data-stock'));
    document.getElementById('stock_id').value       = s.id;
    document.getElementById('stock_form_title').textContent = 'Edit Stock Item';
    document.getElementById('brand_name').value     = s.brand_name  || '';
    document.getElementById('cloth_type').value     = s.cloth_type  || '';
    document.getElementById('stock_date').value     = s.stock_date  || '<?= date('Y-m-d') ?>';
    document.getElementById('total_meters').value   = s.total_meters || '';
    document.getElementById('avail_meters').value   = s.available_meters || '';
    document.getElementById('cost_meter').value     = s.cost_per_meter || '';
    document.getElementById('sell_meter').value     = s.sell_per_meter || '';
    document.getElementById('stock_notes').value    = s.notes || '';
    document.getElementById('box_quantity').value   = s.box_quantity || '';
    document.getElementById('box_price').value      = s.box_price || '';

    var mode = s.sell_mode || 'meter';
    var radio = document.querySelector('input[name="sell_mode"][value="' + mode + '"]');
    if (radio) radio.checked = true;
    onSellModeChange();
    document.getElementById('stock-form').scrollIntoView({behavior:'smooth'});
}

function resetBtn() {
    document.getElementById('bt_id').value = '';
    document.getElementById('bt_name').value = '';
    document.getElementById('bt_price').value = '';
    document.getElementById('btn_form_title').textContent = 'Add Button Type';
}
function editBtnFromData(el) {
    document.getElementById('bt_id').value    = el.getAttribute('data-btid');
    document.getElementById('bt_name').value  = el.getAttribute('data-btname');
    document.getElementById('bt_price').value = el.getAttribute('data-btprice');
    document.getElementById('btn_form_title').textContent = 'Edit Button Type';
    document.getElementById('btn-form').scrollIntoView({behavior:'smooth'});
}
</script>
