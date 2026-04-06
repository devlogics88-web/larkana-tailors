<?php
$isEdit    = isset($order['id']);
$isPrefill = !$isEdit && !empty($order['prefill_customer']);
$prefillCustomer = $isPrefill ? $order['prefill_customer'] : null;
$orderId = $isEdit ? $order['id'] : null;
$m = $isEdit ? ($order['measurements'] ?? []) : [];
$stocks = getStockItems();
$defaultStitching = (float)getSetting('default_stitching_price', '2000');
$currentStitching = $isEdit ? (float)($order['stitching_price'] ?? $defaultStitching) : $defaultStitching;
$pageTitle = $isEdit ? 'Edit Order #' . h($order['order_no']) : 'New Order (نیا آرڈر)';

// Build JS stock data for auto-pricing
$stockJson = json_encode(array_column(
    array_map(fn($s) => ['id'=>(int)$s['id'], 'sell'=>(float)($s['sell_per_meter']??0), 'name'=>$s['brand_name']], $stocks),
    null
));
?>
<div class="page-header">
  <h2><?= $isEdit ? '&#9999; Edit Order &mdash; ' . h($order['order_no'] ?? '') : '&#43; New Order (نیا آرڈر)' ?></h2>
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
<?php
$_restoredCustomerId  = (int)($order['customer_id'] ?? 0);
$_restoredCustomerLbl = h($order['customer_name'] ?? '');
$_restoredNewName     = h($order['_post_new_name']  ?? '');
$_restoredNewPhone    = h($order['_post_new_phone'] ?? '');
$_restoredNewAddr     = h($order['_post_new_addr']  ?? '');
$_showCustomerPanel   = $_restoredCustomerId > 0;
$_showNewSection      = !$_showCustomerPanel && $_restoredNewName !== '';
?>
<input type="hidden" name="customer_id" id="customer_id" value="<?= $_restoredCustomerId ?: '' ?>">
<?php endif; ?>

<!-- CUSTOMER SECTION -->
<?php if (!$isEdit && !$isPrefill): ?>
<div class="card">
  <div class="card-head">&#128101; Customer (کسٹمر) &mdash; Search or Add New</div>
  <div class="card-body">
    <div class="search-box">
      <input type="text" id="customer_search_q" placeholder="Search by Name or Phone... (نام یا فون سے تلاش کریں)" onkeyup="if(event.key==='Enter')searchCustomer()">
      <button type="button" class="btn btn-primary" onclick="searchCustomer()">&#128269; Search</button>
      <button type="button" class="btn" style="background:#546e7a;color:#fff;" onclick="setNewCustomer()">+ New Customer</button>
    </div>
    <div id="customer_results" style="border:1px solid #ddd; max-height:160px; overflow-y:auto; display:none; background:#fff;"></div>
    <div id="customer_panel" class="customer-panel" style="display:<?= $_showCustomerPanel ? 'block' : 'none' ?>;">
      <strong>&#10003; Selected Customer:</strong>
      <span id="customer_name_display"><?= $_restoredCustomerLbl ?></span>
      <a href="#" onclick="clearCustomer();return false;" style="margin-left:10px; color:#c62828; font-size:11px;">[Change]</a>
    </div>
    <div id="new_customer_section" style="display:<?= $_showNewSection ? 'block' : 'none' ?>;">
      <div class="section-divider">New Customer Details (نئے کسٹمر کی معلومات)</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Name (نام) *</label>
          <input type="text" name="new_name" placeholder="Customer Full Name" value="<?= $_restoredNewName ?>">
        </div>
        <div class="form-group">
          <label>Phone (فون نمبر)</label>
          <input type="text" name="new_phone" placeholder="0300-0000000" value="<?= $_restoredNewPhone ?>">
        </div>
        <div class="form-group">
          <label>Address (پتہ)</label>
          <input type="text" name="new_address" placeholder="Address / City" value="<?= $_restoredNewAddr ?>">
        </div>
      </div>
    </div>
  </div>
</div>
<?php elseif ($isPrefill): ?>
<div class="card">
  <div class="card-head">&#128101; Customer (کسٹمر)</div>
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
<div class="card">
  <div class="card-head">&#128101; Customer (کسٹمر)</div>
  <div class="card-body">
    <div class="customer-panel" style="display:block;">
      <strong><?= h($order['customer_name']) ?></strong>
      &mdash; <?= h($order['customer_phone'] ?? '') ?>
      <?= !empty($order['customer_address']) ? ' &mdash; ' . h($order['customer_address']) : '' ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ORDER DETAILS -->
<div class="card">
  <div class="card-head">&#128196; Order Details (آرڈر کی تفصیل)</div>
  <div class="card-body">
    <div class="form-grid">
      <div class="form-group">
        <label>Order Date (تاریخ آرڈر) *</label>
        <input type="date" name="order_date" value="<?= h($order['order_date'] ?? date('Y-m-d')) ?>" required>
      </div>
      <div class="form-group">
        <label>Delivery Date (ڈیلیوری تاریخ)</label>
        <input type="date" name="delivery_date" value="<?= h($order['delivery_date'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Status (حالت)</label>
        <select name="status">
          <?php foreach (['pending'=>'Pending','ready'=>'Ready','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $v=>$l): ?>
          <option value="<?= h($v) ?>" <?= ($order['status'] ?? 'pending') === $v ? 'selected' : '' ?>><?= h($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Suit Type (سوٹ قسم)</label>
        <select name="suit_type">
          <option value="">-- Select --</option>
          <?php foreach (['Shalwar Kameez','Pant Coat','Safari','Kurta','Sherwani','Waistcoat','Other'] as $st): ?>
          <option value="<?= h($st) ?>" <?= ($order['suit_type'] ?? '') === $st ? 'selected' : '' ?>><?= h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Stitch Type (سلائی قسم)</label>
        <select name="stitch_type">
          <option value="">-- Select --</option>
          <?php foreach (['Machine Stitch','Hand Stitch','Semi-Hand','Fancy'] as $st): ?>
          <option value="<?= h($st) ?>" <?= ($order['stitch_type'] ?? '') === $st ? 'selected' : '' ?>><?= h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Order Notes (نوٹس)</label>
        <input type="text" name="notes" value="<?= h($order['notes'] ?? '') ?>" placeholder="Any special instructions...">
      </div>
    </div>
  </div>
</div>

<!-- CLOTH SOURCE -->
<div class="card">
  <div class="card-head">&#129529; Cloth Source (کپڑے کا ذریعہ)</div>
  <div class="card-body">
    <div class="flex-row mb-8">
      <label><input type="radio" name="cloth_source" value="self" <?= ($order['cloth_source'] ?? 'self') === 'self' ? 'checked' : '' ?> onchange="toggleClothSource(this.value)">
        &nbsp;<strong>Self Cloth</strong> &mdash; Customer brings own cloth (اپنا کپڑا)</label>
      <label style="margin-left:20px;"><input type="radio" name="cloth_source" value="shop" <?= ($order['cloth_source'] ?? '') === 'shop' ? 'checked' : '' ?> onchange="toggleClothSource(this.value)">
        &nbsp;<strong>Shop Stock</strong> &mdash; Buy from shop (دکان کا کپڑا)</label>
    </div>
    <div id="shop_cloth_fields" style="display:none;">
      <div class="form-grid">
        <div class="form-group">
          <label>Select Stock Item (اسٹاک آئٹم)</label>
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
          <label>Meters Used (استعمال شدہ میٹر)</label>
          <input type="number" name="meters_used" step="0.25" min="0" id="meters_used"
                 value="<?= h($order['meters_used'] ?? '') ?>"
                 placeholder="e.g. 3.5" oninput="calcTotal()">
        </div>
        <div class="form-group">
          <label>Brand/Cloth Name</label>
          <input type="text" name="brand_name" value="<?= h($order['brand_name'] ?? '') ?>" placeholder="e.g. Pasha, Gul Ahmed">
        </div>
      </div>
      <div id="cloth_cost_display" style="background:#e8f5e9; padding:6px 10px; font-size:12px; border:1px solid #a5d6a7; display:none;">
        Cloth Cost: <strong id="cloth_cost_label">Rs. 0</strong>
        &nbsp;=&nbsp; <span id="cloth_calc_detail"></span>
      </div>
    </div>
  </div>
</div>

<!-- MEASUREMENTS matching the physical measurement card layout -->
<div class="card">
  <div class="card-head">&#128208; Measurements (پیمائش) &mdash; قمیص / شلوار</div>
  <div class="card-body" style="padding:4px;">
    <table class="measure-table">
      <tr>
        <td class="label-cell">لمبائی قمیص<br><small>Shirt Length</small></td>
        <td><input type="text" name="m_shirt_length" value="<?= h($m['shirt_length'] ?? '') ?>" placeholder="45½"></td>
        <td class="label-cell">بازو<br><small>Arm / Bazu</small></td>
        <td><input type="text" name="m_arm" value="<?= h($m['arm'] ?? '') ?>" placeholder="9½"></td>
      </tr>
      <tr>
        <td class="label-cell">تیرہ<br><small>Shoulder</small></td>
        <td><input type="text" name="m_shoulder" value="<?= h($m['shoulder'] ?? '') ?>" placeholder="19½"></td>
        <td class="label-cell">گلا<br><small>Collar / Neck</small></td>
        <td><input type="text" name="m_collar" value="<?= h($m['collar'] ?? '') ?>" placeholder="18"></td>
      </tr>
      <tr>
        <td class="label-cell">چسٹ<br><small>Chest</small></td>
        <td><input type="text" name="m_chest" value="<?= h($m['chest'] ?? '') ?>" placeholder="28"></td>
        <td class="label-cell">کمر<br><small>Waist / Kamar</small></td>
        <td><input type="text" name="m_waist" value="<?= h($m['waist'] ?? '') ?>" placeholder="32"></td>
      </tr>
      <tr>
        <td class="label-cell">گیرہ<br><small>Hip / Seat</small></td>
        <td><input type="text" name="m_hip" value="<?= h($m['hip'] ?? '') ?>" placeholder="30½"></td>
        <td class="label-cell">شلوار لمبائی<br><small>Shalwar Length</small></td>
        <td><input type="text" name="m_shalwar_length" value="<?= h($m['shalwar_length'] ?? '') ?>" placeholder="40"></td>
      </tr>
      <tr>
        <td class="label-cell">پانچہ<br><small>Shalwar Bottom</small></td>
        <td><input type="text" name="m_shalwar_bottom" value="<?= h($m['shalwar_bottom'] ?? '') ?>" placeholder="9"></td>
        <td class="label-cell">شلوار گیرہ<br><small>Shalwar Waist</small></td>
        <td><input type="text" name="m_shalwar_waist" value="<?= h($m['shalwar_waist'] ?? '') ?>" placeholder="22,18"></td>
      </tr>
      <tr>
        <td class="label-cell">کارنوک<br><small>Cuff / Karnok</small></td>
        <td><input type="text" name="m_cuff" value="<?= h($m['cuff'] ?? '') ?>" placeholder="2½"></td>
        <td class="label-cell">آستین<br><small>Sleeve</small></td>
        <td><input type="text" name="m_sleeve" value="<?= h($m['sleeve'] ?? '') ?>" placeholder="25½"></td>
      </tr>
    </table>

    <!-- Bottom detail section matching the physical card -->
    <div style="margin-top:6px; border-top:2px solid var(--blue-md); padding-top:4px;">
      <table class="measure-table">
        <tr>
          <td class="label-cell">فرنٹ<br><small>Front Style</small></td>
          <td><input type="text" name="m_front_style" value="<?= h($m['front_style'] ?? '') ?>" placeholder="e.g. V-Neck, Band"></td>
          <td class="label-cell">مین فل<br><small>Main Full</small></td>
          <td><input type="text" name="m_main_full" value="<?= h($m['main_full'] ?? '') ?>" placeholder=""></td>
        </tr>
        <tr>
          <td class="label-cell">مین ہاف<br><small>Main Half</small></td>
          <td><input type="text" name="m_main_half" value="<?= h($m['main_half'] ?? '') ?>" placeholder=""></td>
          <td class="label-cell">کف<br><small>Kaf</small></td>
          <td><input type="text" name="m_kaf" value="<?= h($m['kaf'] ?? '') ?>" placeholder=""></td>
        </tr>
        <tr>
          <td class="label-cell">گیراچورس<br><small>Gera Chorus</small></td>
          <td><input type="text" name="m_gera_chorus" value="<?= h($m['gera_chorus'] ?? '') ?>" placeholder=""></td>
          <td class="label-cell">سائز<br><small>Size</small></td>
          <td><input type="text" name="m_size_note" value="<?= h($m['size_note'] ?? '') ?>" placeholder=""></td>
        </tr>
        <tr>
          <td class="label-cell">شلوار<br><small>Shalwar Style</small></td>
          <td><input type="text" name="m_shalwar_style" value="<?= h($m['shalwar_style'] ?? '') ?>" placeholder=""></td>
          <td class="label-cell">گیراول<br><small>Gera Oval</small></td>
          <td><input type="text" name="m_gera_oval" value="<?= h($m['gera_oval'] ?? '') ?>" placeholder=""></td>
        </tr>
        <tr>
          <td class="label-cell" colspan="1">ٹراؤزر<br><small>Trouser Length</small></td>
          <td><input type="text" name="m_trouser_length" value="<?= h($m['trouser_length'] ?? '') ?>" placeholder="40"></td>
          <td class="label-cell">موہری ٹراؤزر<br><small>Trouser Bottom</small></td>
          <td><input type="text" name="m_trouser_bottom" value="<?= h($m['trouser_bottom'] ?? '') ?>" placeholder="8"></td>
        </tr>
        <tr>
          <td class="label-cell">تفصیل<br><small>Detail / Notes</small></td>
          <td colspan="3"><input type="text" name="m_detail" value="<?= h($m['detail'] ?? '') ?>" placeholder="Any stitching notes..." style="width:100%;"></td>
        </tr>
      </table>
    </div>
  </div>
</div>

<!-- PRICING -->
<div class="card">
  <div class="card-head">&#128178; Pricing (قیمت)</div>
  <div class="card-body">
    <div class="form-grid" style="align-items:end;">
      <div class="form-group">
        <label>Stitching Price (سلائی) Rs.
          <?php if (isAdmin()): ?><small style="font-weight:normal;color:#666;"> (admin can change)</small><?php endif; ?>
        </label>
        <input type="number" name="stitching_price" id="stitching_price"
               step="50" min="0"
               value="<?= h($currentStitching) ?>"
               <?= !isAdmin() ? 'readonly style="background:#f5f5f5;"' : '' ?>
               oninput="calcTotal()">
      </div>
      <div class="form-group">
        <label>Cloth Cost (کپڑے کی قیمت) Rs.</label>
        <input type="number" id="cloth_price_display" step="1" min="0" value="0"
               readonly style="background:#f5f5f5; color:#1B242D; font-weight:bold;">
        <small style="color:#666;font-size:10px;">Auto-calculated from stock</small>
      </div>
      <div class="form-group">
        <label>Total Price (کل قیمت) Rs. *</label>
        <input type="number" name="total_price" id="total_price" step="1" min="0"
               value="<?= h($order['total_price'] ?? '') ?>"
               placeholder="0" required oninput="calcRemaining()">
        <small style="color:#666;font-size:10px;">Stitching + Cloth (editable)</small>
      </div>
      <div class="form-group">
        <label>Advance Paid (ایڈوانس) Rs.</label>
        <input type="number" name="advance_paid" id="advance_paid"
               step="1" min="0"
               value="<?= h($order['advance_paid'] ?? 0) ?>"
               placeholder="0" oninput="calcRemaining()">
      </div>
      <div class="form-group">
        <label>Remaining (باقی) Rs.</label>
        <input type="number" name="remaining" id="remaining"
               step="1"
               value="<?= h($order['remaining'] ?? '') ?>"
               placeholder="0" readonly style="background:#f5f5f5; font-weight:bold; color:#c62828;">
      </div>
    </div>
  </div>
</div>

<div class="flex-row mb-8">
  <button type="submit" class="btn btn-success">&#10003; Save Order (محفوظ کریں)</button>
  <?php if ($isEdit): ?>
  <a href="?page=invoice&id=<?= h($orderId) ?>&type=customer" class="btn btn-primary" target="_blank">&#128196; Customer Invoice</a>
  <a href="?page=invoice&id=<?= h($orderId) ?>&type=labour" class="btn btn-print" target="_blank">&#128221; Labour Copy</a>
  <?php endif; ?>
  <a href="?page=orders" class="btn" style="background:#546e7a;color:#fff;">Cancel</a>
</div>
</form>

<script>
var stockData = <?= $stockJson ?>;

function searchCustomer() {
    var q = document.getElementById('customer_search_q').value.trim();
    if (!q) return;
    var res = document.getElementById('customer_results');
    res.innerHTML = '<div class="search-result-item">Searching...</div>';
    res.style.display = 'block';
    fetch('?action=search_customer&q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data.length) {
                res.innerHTML = '<div class="search-result-item no-result">No customers found.</div>';
                return;
            }
            res.innerHTML = data.map(function(c){
                return '<div class="search-result-item" onclick="selectCustomer(' + c.id + ',\'' + c.name.replace(/'/g,"\'") + '\',\'' + (c.phone||'').replace(/'/g,"\'") + '\')">'
                    + '<strong>' + c.name + '</strong>'
                    + (c.phone ? ' &mdash; ' + c.phone : '')
                    + (c.address ? ' &mdash; ' + c.address : '')
                    + '</div>';
            }).join('');
        })
        .catch(function(){ res.innerHTML = '<div class="search-result-item no-result">Error searching.</div>'; });
}

function selectCustomer(id, name, phone) {
    document.getElementById('customer_id').value = id;
    document.getElementById('customer_name_display').textContent = name + (phone ? ' — ' + phone : '');
    document.getElementById('customer_panel').style.display = 'block';
    document.getElementById('new_customer_section').style.display = 'none';
    var res = document.getElementById('customer_results');
    if (res) { res.innerHTML=''; res.style.display='none'; }
}

function clearCustomer() {
    document.getElementById('customer_id').value = '';
    document.getElementById('customer_panel').style.display = 'none';
    if(document.getElementById('customer_search_q')) document.getElementById('customer_search_q').value = '';
}

function setNewCustomer() {
    document.getElementById('customer_id').value = '';
    document.getElementById('customer_panel').style.display = 'none';
    var ns = document.getElementById('new_customer_section');
    if (ns) ns.style.display = 'block';
}

document.addEventListener('click', function(e) {
    var res = document.getElementById('customer_results');
    var box = document.getElementById('customer_search_q');
    if (res && box && !res.contains(e.target) && e.target !== box) {
        res.innerHTML = '';
        res.style.display = 'none';
    }
});

function toggleClothSource(val) {
    var f = document.getElementById('shop_cloth_fields');
    if (f) f.style.display = (val === 'shop') ? 'block' : 'none';
    calcTotal();
}

function onStockChange() {
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
    // Update cloth cost display
    var clothCostEl = document.getElementById('cloth_price_display');
    var clothDisplay = document.getElementById('cloth_cost_display');
    var clothLabel = document.getElementById('cloth_cost_label');
    var clothDetail = document.getElementById('cloth_calc_detail');
    var stitchEl = document.getElementById('stitching_price');
    var totalEl = document.getElementById('total_price');

    var source = document.querySelector('input[name="cloth_source"]:checked');
    var isShop = source && source.value === 'shop';

    var clothCost = isShop ? getClothCost() : 0;
    if (clothCostEl) clothCostEl.value = Math.round(clothCost);

    if (clothDisplay && isShop && clothCost > 0) {
        var selEl = document.getElementById('stock_item_id');
        var opt = selEl ? selEl.options[selEl.selectedIndex] : null;
        var sell = opt ? parseFloat(opt.getAttribute('data-sell')||'0') : 0;
        var m = parseFloat(document.getElementById('meters_used').value||'0');
        clothDisplay.style.display = 'block';
        if (clothLabel) clothLabel.textContent = 'Rs. ' + Math.round(clothCost).toLocaleString();
        if (clothDetail) clothDetail.textContent = m + 'm × Rs.' + sell + '/m';
    } else if (clothDisplay) {
        clothDisplay.style.display = 'none';
    }

    var stitching = stitchEl ? parseFloat(stitchEl.value || '0') : 0;
    var newTotal = Math.round(clothCost + stitching);
    if (totalEl && newTotal > 0) totalEl.value = newTotal;
    calcRemaining();
}

function calcRemaining() {
    var t = parseFloat(document.getElementById('total_price').value || '0');
    var a = parseFloat(document.getElementById('advance_paid').value || '0');
    var r = document.getElementById('remaining');
    if (r) r.value = Math.round(t - a);
}

// Init on load
(function() {
    var src = document.querySelector('input[name="cloth_source"]:checked');
    if (src) toggleClothSource(src.value);
    else calcRemaining();
})();
</script>
