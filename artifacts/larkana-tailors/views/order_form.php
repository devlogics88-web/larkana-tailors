<?php
$isEdit    = isset($order['id']);
$isPrefill = !$isEdit && !empty($order['prefill_customer']);
$prefillCustomer = $isPrefill ? $order['prefill_customer'] : null;
$orderId = $isEdit ? $order['id'] : null;
$m = $isEdit ? ($order['measurements'] ?? []) : [];
$stocks = getStockItems();
$pageTitle = $isEdit ? 'Edit Order #' . h($order['order_no']) : 'New Order (نیا آرڈر)';
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
<input type="hidden" name="customer_id" id="customer_id" value="">
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
    <div id="customer_panel" class="customer-panel" style="display:none;">
      <strong>&#10003; Selected Customer:</strong>
      <span id="customer_name_display"></span>
      <a href="#" onclick="clearCustomer();return false;" style="margin-left:10px; color:#c62828; font-size:11px;">[Change]</a>
    </div>
    <div id="new_customer_section" style="display:none;">
      <div class="section-divider">New Customer Details (نئے کسٹمر کی معلومات)</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Name (نام) *</label>
          <input type="text" name="new_name" placeholder="Customer Full Name">
        </div>
        <div class="form-group">
          <label>Phone (فون نمبر)</label>
          <input type="text" name="new_phone" placeholder="0300-0000000">
        </div>
        <div class="form-group">
          <label>Address (پتہ)</label>
          <input type="text" name="new_address" placeholder="Address / City">
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
          <select name="stock_item_id" id="stock_item_id">
            <option value="">-- Select Cloth Brand --</option>
            <?php foreach ($stocks as $s): ?>
            <option value="<?= h($s['id']) ?>" <?= ($order['stock_item_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
              <?= h($s['brand_name']) ?> (<?= h($s['available_meters']) ?>m available)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Meters Used (استعمال شدہ میٹر)</label>
          <input type="number" name="meters_used" step="0.25" min="0" id="meters_used" value="<?= h($order['meters_used'] ?? '') ?>" placeholder="e.g. 3.5">
        </div>
        <div class="form-group">
          <label>Brand/Cloth Name</label>
          <input type="text" name="brand_name" value="<?= h($order['brand_name'] ?? '') ?>" placeholder="e.g. Pasha, Gul Ahmed">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MEASUREMENTS -->
<div class="card">
  <div class="card-head">&#128208; Measurements (پیمائش)</div>
  <div class="card-body" style="padding:6px;">
    <table class="measure-table">
      <tr>
        <td class="label-cell">Shirt Length (لمبائی قمیص)</td>
        <td><input type="text" name="m_shirt_length" value="<?= h($m['shirt_length'] ?? '') ?>" placeholder="e.g. 45½"></td>
        <td class="label-cell">Sleeve (آستین)</td>
        <td><input type="text" name="m_sleeve" value="<?= h($m['sleeve'] ?? '') ?>" placeholder="e.g. 25½"></td>
      </tr>
      <tr>
        <td class="label-cell">Arm / Bazu (بازو)</td>
        <td><input type="text" name="m_arm" value="<?= h($m['arm'] ?? '') ?>" placeholder="e.g. 14½"></td>
        <td class="label-cell">Shoulder (تیرہ)</td>
        <td><input type="text" name="m_shoulder" value="<?= h($m['shoulder'] ?? '') ?>" placeholder="e.g. 19½"></td>
      </tr>
      <tr>
        <td class="label-cell">Collar / Neck (گلا)</td>
        <td><input type="text" name="m_collar" value="<?= h($m['collar'] ?? '') ?>" placeholder="e.g. 18"></td>
        <td class="label-cell">Chest (چپٹ)</td>
        <td><input type="text" name="m_chest" value="<?= h($m['chest'] ?? '') ?>" placeholder="e.g. 28"></td>
      </tr>
      <tr>
        <td class="label-cell">Waist (کمر)</td>
        <td><input type="text" name="m_waist" value="<?= h($m['waist'] ?? '') ?>" placeholder="e.g. 32"></td>
        <td class="label-cell">Hip / Seat (گیرہ)</td>
        <td><input type="text" name="m_hip" value="<?= h($m['hip'] ?? '') ?>" placeholder="e.g. 30½"></td>
      </tr>
      <tr>
        <td class="label-cell">Cuff / Karnok (کارنوک)</td>
        <td><input type="text" name="m_cuff" value="<?= h($m['cuff'] ?? '') ?>" placeholder="e.g. 2½"></td>
        <td class="label-cell">Shalwar Length (شلوار لمبائی)</td>
        <td><input type="text" name="m_shalwar_length" value="<?= h($m['shalwar_length'] ?? '') ?>" placeholder="e.g. 40"></td>
      </tr>
      <tr>
        <td class="label-cell">Shalwar Bottom / Pancha (پانچہ)</td>
        <td><input type="text" name="m_shalwar_bottom" value="<?= h($m['shalwar_bottom'] ?? '') ?>" placeholder="e.g. 9"></td>
        <td class="label-cell">Shalwar Waist (شلوار گیرہ)</td>
        <td><input type="text" name="m_shalwar_waist" value="<?= h($m['shalwar_waist'] ?? '') ?>" placeholder="e.g. 22,18"></td>
      </tr>
      <tr>
        <td class="label-cell">Trouser Length (ٹراؤزر لمبائی)</td>
        <td><input type="text" name="m_trouser_length" value="<?= h($m['trouser_length'] ?? '') ?>" placeholder="e.g. 40"></td>
        <td class="label-cell">Trouser Bottom (ٹراؤزر پائنچہ)</td>
        <td><input type="text" name="m_trouser_bottom" value="<?= h($m['trouser_bottom'] ?? '') ?>" placeholder="e.g. 8"></td>
      </tr>
      <tr>
        <td class="label-cell">Front Style (فرنٹ)</td>
        <td colspan="3">
          <select name="m_front_style" style="width:100%;border:none;padding:3px 4px;font-size:12px;">
            <option value="">-- Select --</option>
            <?php foreach (['Main Full','Main Half','Cuff','Gera Gores','Simple','Fancy'] as $fs): ?>
            <option value="<?= h($fs) ?>" <?= ($m['front_style'] ?? '') === $fs ? 'selected' : '' ?>><?= h($fs) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="label-cell">Detail / Notes (تفصیل)</td>
        <td colspan="3"><input type="text" name="m_detail" value="<?= h($m['detail'] ?? '') ?>" placeholder="Any specific stitching detail or design note..." style="width:100%;"></td>
      </tr>
    </table>
  </div>
</div>

<!-- PRICING -->
<div class="card">
  <div class="card-head">&#128178; Pricing (قیمت)</div>
  <div class="card-body">
    <div class="form-grid">
      <div class="form-group">
        <label>Total Price (کل قیمت) Rs.</label>
        <input type="number" name="total_price" id="total_price" step="1" min="0" value="<?= h($order['total_price'] ?? '') ?>" placeholder="0" required oninput="calcRemaining()">
      </div>
      <div class="form-group">
        <label>Advance Paid (ایڈوانس) Rs.</label>
        <input type="number" name="advance_paid" id="advance_paid" step="1" min="0" value="<?= h($order['advance_paid'] ?? 0) ?>" placeholder="0" oninput="calcRemaining()">
      </div>
      <div class="form-group">
        <label>Remaining (باقی) Rs.</label>
        <input type="number" name="remaining" id="remaining" step="1" value="<?= h($order['remaining'] ?? '') ?>" placeholder="0" readonly style="background:#f5f5f5;">
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
// Show search results dropdown with click-outside to close
document.addEventListener('click', function(e) {
    const res = document.getElementById('customer_results');
    const box = document.getElementById('customer_search_q');
    if (res && box && !res.contains(e.target) && e.target !== box) {
        res.innerHTML = '';
    }
});
document.getElementById('customer_search_q')?.addEventListener('keyup', function() {
    const res = document.getElementById('customer_results');
    if (res) res.style.display = 'block';
});
// Override searchCustomer to also show results div (guard against load-order issues).
document.addEventListener('DOMContentLoaded', function() {
    if (typeof searchCustomer === 'function') {
        const _origSearch = searchCustomer;
        window.searchCustomer = function() {
            const res = document.getElementById('customer_results');
            if (res) res.style.display = 'block';
            _origSearch();
        };
    }
});
</script>
