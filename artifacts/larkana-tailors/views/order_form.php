<?php
$isEdit    = isset($order['id']);
$isPrefill = !$isEdit && !empty($order['prefill_customer']);
$prefillCustomer = $isPrefill ? $order['prefill_customer'] : null;
$orderId = $isEdit ? $order['id'] : null;
$m = $isEdit ? ($order['measurements'] ?? []) : [];
$stocks          = getStockItems();
$stitchingTypes  = getStitchingTypes();
$buttonTypes     = getButtonTypes();
$panchaTypes     = getPanchaTypes();
$defaultStitching = (float)getSetting('default_stitching_price', '2300');
$currentStitching = $isEdit ? (float)($order['stitching_price'] ?? $defaultStitching) : 0;
$currentStitchingTypeId = $isEdit ? ($order['stitching_type_id'] ?? '') : '';
$currentButtonTypeId    = $isEdit ? ($order['button_type_id'] ?? '') : '';
$currentButtonPrice     = $isEdit ? (float)($order['button_price'] ?? 0) : 0;
$currentPanchaTypeId    = $isEdit ? ($order['pancha_type_id'] ?? '') : '';
$currentPanchaPrice     = $isEdit ? (float)($order['pancha_price'] ?? 0) : 0;

$stockJson     = json_encode(array_map(fn($s) => ['id'=>(int)$s['id'], 'sell'=>(float)($s['sell_per_meter']??0), 'name'=>$s['brand_name']], $stocks));
$stitchingJson = json_encode(array_map(fn($t) => ['id'=>(int)$t['id'], 'name'=>$t['name'], 'price'=>(float)$t['price']], $stitchingTypes));

$isNewOrder = !$isEdit && !$isPrefill;

// Restore posted customer state (on validation error redirect for new orders)
$_restoredCustomerId  = $isNewOrder ? (int)($order['customer_id'] ?? 0) : 0;
$_restoredCustomerLbl = $isNewOrder ? h($order['customer_name'] ?? '') : '';
$_showCustomerPanel   = $_restoredCustomerId > 0;
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <h2><?= $isEdit ? '&#9999; Edit Order &mdash; ' . h($order['order_no'] ?? '') : '&#43; New Order' ?></h2>
  <a href="?page=orders" class="btn btn-sm" style="background:#546e7a;color:#fff;">&#8592; All Orders</a>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<form method="POST" action="?action=save_order" id="order-form">
<input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
<?php if ($isEdit): ?>
<input type="hidden" name="order_id" value="<?= h($orderId) ?>">
<input type="hidden" name="customer_id" value="<?= h($order['customer_id']) ?>">
<?php elseif ($isPrefill): ?>
<input type="hidden" name="customer_id" id="customer_id" value="<?= h($prefillCustomer['id']) ?>">
<?php else: ?>
<input type="hidden" name="customer_id" id="customer_id" value="<?= $_restoredCustomerId ?: '' ?>">
<?php endif; ?>

<!-- CUSTOMER DISPLAY -->
<?php if ($isEdit): ?>
<div class="card">
  <div class="card-head">&#128101; Customer</div>
  <div class="card-body">
    <div class="customer-panel" style="display:block;">
      <strong><?= h($order['customer_name']) ?></strong>
      &mdash; <?= h($order['customer_phone'] ?? '') ?>
      <?= !empty($order['customer_address']) ? ' &mdash; ' . h($order['customer_address']) : '' ?>
    </div>
  </div>
</div>
<?php elseif ($isPrefill): ?>
<div class="card">
  <div class="card-head">&#128101; Customer</div>
  <div class="card-body">
    <div class="customer-panel" style="display:block;">
      <strong>&#10003; <?= h($prefillCustomer['name']) ?></strong>
      <?= !empty($prefillCustomer['phone']) ? ' &mdash; ' . h($prefillCustomer['phone']) : '' ?>
      <?= !empty($prefillCustomer['address']) ? ' &mdash; ' . h($prefillCustomer['address']) : '' ?>
      <a href="?page=customers" style="margin-left:10px; color:#c62828; font-size:11px;">[Change Customer]</a>
    </div>
  </div>
</div>
<?php else: ?>
<!-- New Order: customer search -->
<div class="card">
  <div class="card-head">&#128101; Customer</div>
  <div class="card-body">
    <div id="customer_panel" class="customer-panel" style="display:<?= $_showCustomerPanel ? 'block' : 'none' ?>;">
      <strong>&#10003; Customer:</strong>
      <span id="customer_name_display"><?= $_restoredCustomerLbl ?></span>
      <a href="#" onclick="clearCustSearch(); return false;" style="margin-left:10px; color:#c62828; font-size:11px;">[Change]</a>
    </div>
    <div id="customer_search_wrap" style="<?= $_showCustomerPanel ? 'display:none;' : '' ?>">
      <div class="search-box" style="margin:0 0 4px;">
        <input type="text" id="customer_search_q" placeholder="Search customer by name or phone..."
               oninput="searchCustomer()" autocomplete="off">
        <a href="?page=customers" class="btn btn-sm" style="background:#546e7a;color:#fff; white-space:nowrap;">&#128101; Manage Customers</a>
      </div>
      <div id="customer_results" class="search-results" style="display:none;"></div>
      <div style="font-size:11px; color:#9e9e9e; margin-top:4px;">
        Type a name or phone number to find customer &mdash; or <a href="?page=customers" style="color:#4fc3f7;">go to Customers</a> to add a new one.
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ORDER DETAILS -->
<div class="card">
  <div class="card-head">&#128196; Order Details</div>
  <div class="card-body">
    <div class="form-grid">
      <div class="form-group">
        <label>Order Date *</label>
        <input type="date" name="order_date" value="<?= h($order['order_date'] ?? date('Y-m-d')) ?>" required>
      </div>
      <div class="form-group">
        <label>Delivery Date</label>
        <input type="date" name="delivery_date" value="<?= h($order['delivery_date'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <?php foreach (['pending'=>'Pending','ready'=>'Ready','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $v=>$l): ?>
          <option value="<?= h($v) ?>" <?= ($order['status'] ?? 'pending') === $v ? 'selected' : '' ?>><?= h($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Suit Type</label>
        <select name="suit_type">
          <option value="">-- Select --</option>
          <?php foreach (['Shalwar Kameez','Pant Coat','Safari','Kurta','Sherwani','Waistcoat','Other'] as $st): ?>
          <option value="<?= h($st) ?>" <?= ($order['suit_type'] ?? '') === $st ? 'selected' : '' ?>><?= h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Stitching Type</label>
        <select name="stitching_type_id" id="stitching_type_id" onchange="onStitchingTypeChange()">
          <option value="">-- Select Stitching Type --</option>
          <?php foreach ($stitchingTypes as $st): ?>
          <option value="<?= h($st['id']) ?>"
                  data-price="<?= h($st['price']) ?>"
                  <?= (string)$currentStitchingTypeId === (string)$st['id'] ? 'selected' : '' ?>>
            <?= h($st['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Order Notes</label>
        <input type="text" name="notes" value="<?= h($order['notes'] ?? '') ?>" placeholder="Any special instructions...">
      </div>
    </div>
  </div>
</div>

<!-- CLOTH SOURCE -->
<div class="card">
  <div class="card-head">&#129529; Cloth Source</div>
  <div class="card-body">
    <div class="flex-row mb-8">
      <label><input type="radio" name="cloth_source" value="self" <?= ($order['cloth_source'] ?? 'self') === 'self' ? 'checked' : '' ?> onchange="toggleClothSource(this.value)">
        &nbsp;<strong>Self Cloth</strong> &mdash; Customer brings own cloth</label>
      <label style="margin-left:20px;"><input type="radio" name="cloth_source" value="shop" <?= ($order['cloth_source'] ?? '') === 'shop' ? 'checked' : '' ?> onchange="toggleClothSource(this.value)">
        &nbsp;<strong>Shop Stock</strong> &mdash; Buy from shop</label>
    </div>
    <div id="shop_cloth_fields" style="display:none;">
      <div class="form-grid">
        <div class="form-group">
          <label>Select Stock Item</label>
          <select name="stock_item_id" id="stock_item_id" onchange="onStockChange()">
            <option value="">-- Select Cloth Brand --</option>
            <?php foreach ($stocks as $s): ?>
            <option value="<?= h($s['id']) ?>"
                    data-sell="<?= h($s['sell_per_meter'] ?? 0) ?>"
                    <?= ($order['stock_item_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
              <?= h($s['brand_name']) ?> (<?= h($s['available_meters']) ?>m available @ Rs.<?= h($s['sell_per_meter'] ?? 0) ?>/m)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Meters Used</label>
          <input type="number" name="meters_used" step="0.25" min="0" id="meters_used"
                 value="<?= h($order['meters_used'] ?? '') ?>"
                 placeholder="e.g. 3.5" oninput="calcTotal()">
        </div>
        <div class="form-group">
          <label>Brand / Cloth Name</label>
          <input type="text" name="brand_name" value="<?= h($order['brand_name'] ?? '') ?>" placeholder="e.g. Pasha, Gul Ahmed">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MEASUREMENTS -->
<div class="card">
  <div class="card-head" style="display:flex;align-items:center;justify-content:space-between;">
    <span>&#128208; Measurements</span>
    <span style="font-size:11px;font-weight:normal;color:#9bb0bf;">(optional &mdash; can be added or updated later via Edit)</span>
  </div>
  <div class="card-body" style="padding:4px;">

    <div style="width:44%; margin:0 auto;">
      <table class="measure-table" style="width:100%;">
        <colgroup><col style="width:44%;"><col style="width:56%;"></colgroup>
        <tr><td class="label-cell">Shirt Length</td><td><input type="text" name="m_shirt_length" value="<?= h($m['shirt_length'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Bazu</td><td><input type="text" name="m_arm" value="<?= h($m['arm'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Teera</td><td><input type="text" name="m_shoulder" value="<?= h($m['shoulder'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Gala</td><td><input type="text" name="m_collar" value="<?= h($m['collar'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Chest</td><td><input type="text" name="m_chest" value="<?= h($m['chest'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Kamar</td><td><input type="text" name="m_waist" value="<?= h($m['waist'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Ghera</td><td><input type="text" name="m_hip" value="<?= h($m['hip'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Shalwar Length</td><td><input type="text" name="m_shalwar_length" value="<?= h($m['shalwar_length'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Pancha</td><td><input type="text" name="m_shalwar_bottom" value="<?= h($m['shalwar_bottom'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Shalwar Ghera</td><td><input type="text" name="m_shalwar_waist" value="<?= h($m['shalwar_waist'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Color Nok</td><td><input type="text" name="m_cuff" value="<?= h($m['cuff'] ?? '') ?>"></td></tr>
      </table>
    </div>

    <div style="width:44%; margin:5px auto 0; border-top:2px solid var(--blue-md); display:flex; gap:0;">
      <table class="measure-table" style="width:50%; border-right:2px solid var(--blue-md);">
        <colgroup><col style="width:46%;"><col style="width:54%;"></colgroup>
        <tr><td class="label-cell">Front</td><td><input type="text" name="m_front_style" value="<?= h($m['front_style'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Side</td><td><input type="text" name="m_size_note" value="<?= h($m['size_note'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Shalwar</td><td><input type="text" name="m_shalwar_style" value="<?= h($m['shalwar_style'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Ghera Gol</td><td><input type="text" name="m_gera_oval" value="<?= h($m['gera_oval'] ?? '') ?>"></td></tr>
      </table>
      <table class="measure-table" style="width:50%;">
        <colgroup><col style="width:46%;"><col style="width:54%;"></colgroup>
        <tr><td class="label-cell">Bain Full</td><td><input type="text" name="m_main_full" value="<?= h($m['main_full'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Bain Half</td><td><input type="text" name="m_main_half" value="<?= h($m['main_half'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Harmol</td><td><input type="text" name="m_harmol" value="<?= h($m['harmol'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Chak Patti Button</td><td><input type="text" name="m_chak_patti_button" value="<?= h($m['chak_patti_button'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Cuff</td><td><input type="text" name="m_kaf" value="<?= h($m['kaf'] ?? '') ?>"></td></tr>
        <tr><td class="label-cell">Ghera Chorus</td><td><input type="text" name="m_gera_chorus" value="<?= h($m['gera_chorus'] ?? '') ?>"></td></tr>
      </table>
    </div>

    <div style="width:44%; margin:0 auto; border-top:1px solid var(--blue-md);">
      <table class="measure-table" style="width:100%;">
        <tr>
          <td class="label-cell" style="width:44%;">Detail</td>
          <td><input type="text" name="m_detail" value="<?= h($m['detail'] ?? '') ?>" style="width:100%;"></td>
        </tr>
      </table>
    </div>
  </div>
</div>

<!-- PRICING -->
<div class="card">
  <div class="card-head">&#128178; Pricing</div>
  <div class="card-body">

    <div class="form-grid" style="align-items:end; margin-bottom:8px;">
      <div class="form-group">
        <label>Stitching Price Rs.</label>
        <input type="number" name="stitching_price" id="stitching_price"
               step="50" min="0"
               value="<?= h($currentStitching) ?>"
               <?= !isAdmin() ? 'readonly style="background:#f5f5f5;"' : '' ?>
               oninput="calcTotal()">
      </div>
      <div class="form-group">
        <label>Button Type</label>
        <select name="button_type_id" id="button_type_id" onchange="onButtonTypeChange()">
          <option value="">-- No Button --</option>
          <?php foreach ($buttonTypes as $bt): ?>
          <option value="<?= h($bt['id']) ?>"
                  data-price="<?= h($bt['price']) ?>"
                  <?= (string)$currentButtonTypeId === (string)$bt['id'] ? 'selected' : '' ?>>
            <?= h($bt['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Button Price Rs.</label>
        <input type="number" name="button_price" id="button_price"
               step="50" min="0" value="<?= h($currentButtonPrice) ?>"
               readonly style="background:#f5f5f5;">
      </div>
      <div class="form-group">
        <label>Pancha Type</label>
        <select name="pancha_type_id" id="pancha_type_id" onchange="onPanchaTypeChange()">
          <option value="">-- No Pancha --</option>
          <?php foreach ($panchaTypes as $pt): ?>
          <option value="<?= h($pt['id']) ?>"
                  data-price="<?= h($pt['price']) ?>"
                  <?= (string)$currentPanchaTypeId === (string)$pt['id'] ? 'selected' : '' ?>>
            <?= h($pt['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Pancha Price Rs.</label>
        <input type="number" name="pancha_price" id="pancha_price"
               step="50" min="0" value="<?= h($currentPanchaPrice) ?>"
               readonly style="background:#f5f5f5;">
      </div>
    </div>

    <div class="form-grid" style="align-items:end; margin-bottom:8px;">
      <div class="form-group">
        <label>Cloth Cost Rs.</label>
        <input type="number" id="cloth_price_display" step="1" min="0" value="0"
               readonly style="background:#f5f5f5; color:#1B242D; font-weight:bold;">
      </div>
      <div class="form-group">
        <label>Gross Total Rs.</label>
        <input type="number" id="gross_total_display" step="1" min="0" value="0"
               readonly style="background:#f5f5f5; color:#1B242D; font-weight:bold;">
      </div>
      <div class="form-group">
        <label>Discount Rs. <span style="font-weight:normal; font-size:11px;">(optional)</span></label>
        <input type="number" name="discount" id="discount"
               step="1" min="0" value="<?= h($order['discount'] ?? 0) ?>"
               placeholder="0" oninput="calcTotal()">
      </div>
    </div>

    <div class="form-grid" style="align-items:end; margin-bottom:8px;">
      <div class="form-group">
        <label>Total Price Rs.</label>
        <input type="number" name="total_price" id="total_price" step="1" min="0"
               value="<?= h($order['total_price'] ?? '') ?>"
               placeholder="0" oninput="calcRemaining()">
      </div>
      <div class="form-group">
        <label>Advance Paid Rs.</label>
        <input type="number" name="advance_paid" id="advance_paid"
               step="1" min="0"
               value="<?= h($order['advance_paid'] ?? 0) ?>"
               placeholder="0" oninput="calcRemaining()">
      </div>
      <div class="form-group">
        <label>Pending Amount Rs.</label>
        <input type="number" name="remaining" id="remaining"
               step="1"
               value="<?= h($order['remaining'] ?? '') ?>"
               placeholder="0" readonly style="background:#f5f5f5; font-weight:bold; color:#c62828;">
      </div>
    </div>

    <div class="form-grid" style="align-items:end; margin-bottom:8px;">
      <div class="form-group">
        <label>Payment Method</label>
        <select name="payment_method" id="payment_method">
          <?php foreach (['Cash','Online - JazzCash','Online - Easypaisa','Online - Bank Acc'] as $pm): ?>
          <option value="<?= h($pm) ?>" <?= ($order['payment_method'] ?? 'Cash') === $pm ? 'selected' : '' ?>><?= h($pm) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Receiving Hand</label>
        <input type="text" name="receiving_hand" value="<?= h($order['receiving_hand'] ?? '') ?>" placeholder="Name of person receiving payment">
      </div>
    </div>

    <div id="cloth_cost_display" style="background:#e8f5e9; padding:6px 10px; font-size:12px; border:1px solid #a5d6a7; display:none; margin-top:4px;">
      Cloth: <strong id="cloth_cost_label">Rs. 0</strong>
      &nbsp;=&nbsp; <span id="cloth_calc_detail"></span>
    </div>
  </div>
</div>

<div class="flex-row mb-8">
  <button type="submit" class="btn btn-success">&#10003; Save Order</button>
  <?php if ($isEdit): ?>
  <a href="?page=invoice&id=<?= h($orderId) ?>&type=customer" class="btn btn-primary" target="_blank">&#128196; Customer Invoice</a>
  <a href="?page=invoice&id=<?= h($orderId) ?>&type=labour" class="btn btn-print" target="_blank">&#128221; Labour Copy</a>
  <?php endif; ?>
  <a href="?page=orders" class="btn" style="background:#546e7a;color:#fff;">Cancel</a>
</div>
</form>

<script>
var stockData = <?= $stockJson ?>;
var csrfToken = '<?= h(getCsrf()) ?>';

// ===== CUSTOMER SEARCH =====
function searchCustomer() {
    var el = document.getElementById('customer_search_q');
    if (!el) return;
    var q = el.value.trim();
    var res = document.getElementById('customer_results');
    if (!res) return;
    if (q.length < 2) { res.innerHTML=''; res.style.display='none'; return; }
    res.innerHTML = '<div class="search-result-item">Searching...</div>';
    res.style.display = 'block';
    fetch('?action=search_customer&q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data.length) {
                res.innerHTML = '<div class="search-result-item no-result">No customers found. <a href="?page=customers" style="color:#4fc3f7;">Add a new customer</a> first.</div>';
                return;
            }
            res.innerHTML = data.map(function(c){
                return '<div class="search-result-item" onclick="selectCustomer(' + c.id + ',' + JSON.stringify(c.name) + ',' + JSON.stringify(c.phone||'') + ')">'
                    + '<strong>' + c.name + '</strong>'
                    + (c.phone ? ' &mdash; ' + c.phone : '')
                    + '</div>';
            }).join('');
        })
        .catch(function(){ res.innerHTML = '<div class="search-result-item no-result">Error searching.</div>'; });
}

function selectCustomer(id, name, phone) {
    var cidEl = document.getElementById('customer_id');
    if (cidEl) cidEl.value = id;
    var dnEl = document.getElementById('customer_name_display');
    if (dnEl) dnEl.textContent = name + (phone ? ' \u2014 ' + phone : '');
    var panel = document.getElementById('customer_panel');
    if (panel) panel.style.display = 'block';
    var wrap = document.getElementById('customer_search_wrap');
    if (wrap) wrap.style.display = 'none';
    var res = document.getElementById('customer_results');
    if (res) { res.innerHTML=''; res.style.display='none'; }
}

function clearCustSearch() {
    document.getElementById('customer_id').value = '';
    document.getElementById('customer_panel').style.display = 'none';
    var wrap = document.getElementById('customer_search_wrap');
    if (wrap) wrap.style.display = '';
    var q = document.getElementById('customer_search_q');
    if (q) { q.value = ''; q.focus(); }
    var res = document.getElementById('customer_results');
    if (res) { res.innerHTML=''; res.style.display='none'; }
}

function toggleClothSource(val) {
    var f = document.getElementById('shop_cloth_fields');
    if (f) f.style.display = (val === 'shop') ? 'block' : 'none';
    calcTotal();
}

function onStockChange() { calcTotal(); }

function onStitchingTypeChange() {
    var sel = document.getElementById('stitching_type_id');
    var opt = sel ? sel.options[sel.selectedIndex] : null;
    var priceField = document.getElementById('stitching_price');
    if (opt && opt.value && priceField) {
        priceField.value = parseFloat(opt.getAttribute('data-price') || '0');
    }
    calcTotal();
}

function onButtonTypeChange() {
    var sel = document.getElementById('button_type_id');
    var opt = sel ? sel.options[sel.selectedIndex] : null;
    var priceField = document.getElementById('button_price');
    if (priceField) {
        priceField.value = (opt && opt.value) ? parseFloat(opt.getAttribute('data-price') || '0') : 0;
    }
    calcTotal();
}

function onPanchaTypeChange() {
    var sel = document.getElementById('pancha_type_id');
    var opt = sel ? sel.options[sel.selectedIndex] : null;
    var priceField = document.getElementById('pancha_price');
    if (priceField) {
        priceField.value = (opt && opt.value) ? parseFloat(opt.getAttribute('data-price') || '0') : 0;
    }
    calcTotal();
}

function getClothCost() {
    var selEl = document.getElementById('stock_item_id');
    var metersEl = document.getElementById('meters_used');
    if (!selEl || !metersEl) return 0;
    var opt = selEl.options[selEl.selectedIndex];
    if (!opt || !opt.value) return 0;
    var sellPrice = parseFloat(opt.getAttribute('data-sell') || '0');
    var meters = parseFloat(metersEl.value || '0');
    return sellPrice * meters;
}

function calcTotal() {
    var clothCostEl    = document.getElementById('cloth_price_display');
    var grossDisplayEl = document.getElementById('gross_total_display');
    var clothDisplay   = document.getElementById('cloth_cost_display');
    var clothLabel     = document.getElementById('cloth_cost_label');
    var clothDetail    = document.getElementById('cloth_calc_detail');
    var stitchEl       = document.getElementById('stitching_price');
    var buttonEl       = document.getElementById('button_price');
    var panchaEl       = document.getElementById('pancha_price');
    var discountEl     = document.getElementById('discount');
    var totalEl        = document.getElementById('total_price');

    var source = document.querySelector('input[name="cloth_source"]:checked');
    var isShop = source && source.value === 'shop';
    var clothCost = isShop ? getClothCost() : 0;
    if (clothCostEl) clothCostEl.value = Math.round(clothCost);

    if (clothDisplay && isShop && clothCost > 0) {
        var selEl = document.getElementById('stock_item_id');
        var opt   = selEl ? selEl.options[selEl.selectedIndex] : null;
        var sell  = opt ? parseFloat(opt.getAttribute('data-sell')||'0') : 0;
        var m     = parseFloat((document.getElementById('meters_used')||{}).value||'0');
        clothDisplay.style.display = 'block';
        if (clothLabel) clothLabel.textContent = 'Rs. ' + Math.round(clothCost).toLocaleString();
        if (clothDetail) clothDetail.textContent = m + 'm x Rs.' + sell + '/m';
    } else if (clothDisplay) {
        clothDisplay.style.display = 'none';
    }

    // Only include stitching price if a stitching type is actually selected
    var stTypeEl = document.getElementById('stitching_type_id');
    var hasStitchingType = stTypeEl && stTypeEl.value;
    if (!hasStitchingType && stitchEl) stitchEl.value = 0;
    var stitching = (hasStitchingType && stitchEl) ? parseFloat(stitchEl.value || '0') : 0;
    var button    = buttonEl  ? parseFloat(buttonEl.value  || '0') : 0;
    var pancha    = panchaEl  ? parseFloat(panchaEl.value  || '0') : 0;
    var discount  = discountEl ? parseFloat(discountEl.value || '0') : 0;
    if (discount < 0) discount = 0;

    var gross    = Math.round(clothCost + stitching + button + pancha);
    var netTotal = Math.max(0, gross - discount);

    if (grossDisplayEl) grossDisplayEl.value = gross;
    if (totalEl && gross > 0) totalEl.value = netTotal;
    calcRemaining();
}

function calcRemaining() {
    var t = parseFloat((document.getElementById('total_price')||{}).value || '0');
    var a = parseFloat((document.getElementById('advance_paid')||{}).value || '0');
    var r = document.getElementById('remaining');
    if (r) r.value = Math.round(t - a);
}

(function() {
    var src = document.querySelector('input[name="cloth_source"]:checked');
    if (src) toggleClothSource(src.value);
    else calcRemaining();
})();
</script>
