<?php
$type = $_GET['type'] ?? 'customer';
$isLabour = $type === 'labour';
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) { echo '<p>Invalid order.</p>'; return; }
$order = getOrder($orderId);
if (!$order) { echo '<p>Order not found.</p>'; return; }
$m = $order['measurements'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $isLabour ? 'Labour Copy' : 'Customer Invoice' ?> - <?= h($order['order_no']) ?></title>
<link rel="stylesheet" href="assets/style.css">
<style>
body { background: #fff; margin: 0; padding: 10px; }
@media screen { .invoice-wrapper { max-width: 380px; margin: 0 auto; } }
</style>
</head>
<body>
<div style="margin-bottom:10px;" class="no-print">
  <button onclick="window.print()" class="btn btn-success">&#128424; Print</button>
  <button onclick="window.close()" class="btn" style="background:#546e7a;color:#fff;">Close</button>
  <a href="?page=order_edit&id=<?= h($orderId) ?>" class="btn btn-info">Edit Order</a>
</div>

<div class="invoice-wrapper">
  <div class="copy-title"><?= $isLabour ? 'STITCHING LABOUR COPY (لیبر کاپی)' : 'CUSTOMER COPY (کسٹمر کاپی)' ?></div>

  <div class="inv-shop-name">Larkana Tailors &amp; Cloth House</div>
  <div class="inv-shop-sub">Gents Specialist &mdash; Lakhmir Khan</div>
  <div class="inv-shop-sub">SOAN GARDEN, Shahid Arcade, Main Double Road<br>Opposite Bank Islami, Islamabad</div>
  <div class="inv-shop-sub">&#128222; 0300-2151261</div>
  <div class="inv-divider"></div>

  <div class="inv-section">
    <label>Order #:</label> <strong><?= h($order['order_no']) ?></strong>
    &nbsp;&nbsp; <label>Date:</label> <?= formatDate($order['order_date']) ?>
  </div>
  <div class="inv-section">
    <label>Customer:</label> <strong><?= h($order['customer_name']) ?></strong>
  </div>
  <?php if ($order['customer_phone']): ?>
  <div class="inv-section">
    <label>Phone:</label> <?= h($order['customer_phone']) ?>
  </div>
  <?php endif; ?>
  <?php if ($order['delivery_date']): ?>
  <div class="inv-section">
    <label>Delivery:</label> <strong><?= formatDate($order['delivery_date']) ?></strong>
  </div>
  <?php endif; ?>
  <?php if ($order['suit_type'] || $order['stitch_type']): ?>
  <div class="inv-section">
    <label>Suit:</label> <?= h($order['suit_type'] ?? '') ?>
    <?= $order['stitch_type'] ? ' / ' . h($order['stitch_type']) : '' ?>
  </div>
  <?php endif; ?>
  <?php if ($order['cloth_source'] === 'shop' && $order['brand_name']): ?>
  <div class="inv-section">
    <label>Cloth:</label> <?= h($order['brand_name']) ?>
    <?= $order['meters_used'] ? ' (' . h($order['meters_used']) . 'm)' : '' ?>
  </div>
  <?php elseif ($order['cloth_source'] === 'self'): ?>
  <div class="inv-section"><label>Cloth:</label> Self (اپنا کپڑا)</div>
  <?php endif; ?>

  <div class="inv-divider"></div>

  <!-- MEASUREMENTS -->
  <div style="font-size:11px; font-weight:bold; margin-bottom:4px;">Measurements (پیمائش):</div>
  <div class="inv-measure-grid">
    <?php
    $fields = [
      'm_shirt_length'  => ['Shirt Length', 'لمبائی قمیص', 'shirt_length'],
      'm_sleeve'        => ['Sleeve', 'آستین', 'sleeve'],
      'm_arm'           => ['Arm', 'بازو', 'arm'],
      'm_shoulder'      => ['Shoulder', 'تیرہ', 'shoulder'],
      'm_collar'        => ['Collar', 'گلا', 'collar'],
      'm_chest'         => ['Chest', 'چپٹ', 'chest'],
      'm_waist'         => ['Waist', 'کمر', 'waist'],
      'm_hip'           => ['Hip', 'گیرہ', 'hip'],
      'm_cuff'          => ['Cuff', 'کارنوک', 'cuff'],
      'm_shalwar_length'=> ['Shlwr Length', 'شلوار لمبائی', 'shalwar_length'],
      'm_shalwar_bottom'=> ['Pancha', 'پانچہ', 'shalwar_bottom'],
      'm_shalwar_waist' => ['Shlwr Waist', 'شلوار گیرہ', 'shalwar_waist'],
      'm_trouser_length' => ['Trouser L', 'ٹراؤزر', 'trouser_length'],
      'm_trouser_bottom' => ['Trouser Bottom', 'موہری ٹراؤزر', 'trouser_bottom'],
    ];
    foreach ($fields as [$en, $ur, $key]):
      $val = $m[$key] ?? '';
      if (!$val) continue;
    ?>
    <div class="inv-measure-item">
      <div class="im-label"><?= $en ?></div>
      <div class="im-val"><?= h($val) ?></div>
    </div>
    <?php endforeach; ?>
    <?php if (!empty($m['front_style'])): ?>
    <div class="inv-measure-item">
      <div class="im-label">Front Style</div>
      <div class="im-val"><?= h($m['front_style']) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($m['detail'])): ?>
  <div class="inv-section" style="margin-top:4px;">
    <label>Detail:</label> <?= h($m['detail']) ?>
  </div>
  <?php endif; ?>
  <?php if ($order['notes']): ?>
  <div class="inv-section">
    <label>Notes:</label> <?= h($order['notes']) ?>
  </div>
  <?php endif; ?>

  <?php if (!$isLabour): ?>
  <?php
  // Policy: pricing (Total Amount / Advance / Remaining) is intentionally shown
  // on the customer copy for ALL roles, including workers. Workers at the counter
  // need these figures to collect payment and hand the receipt to customers.
  // The labour copy (stitching slip) never shows pricing data.
  // Financial analysis (profit/loss, aggregates) is restricted to Admin only
  // via the Reports page and dashboard columns.
  ?>
  <div class="inv-divider"></div>
  <div class="inv-section" style="font-size:13px;">
    <label>Total Amount:</label> <strong style="font-size:15px;"><?= formatMoney($order['total_price']) ?></strong>
  </div>
  <div class="inv-section">
    <label>Advance Paid:</label> <strong><?= formatMoney($order['advance_paid']) ?></strong>
  </div>
  <div class="inv-section" style="color:<?= ($order['remaining'] ?? 0) > 0 ? '#c62828' : '#2e7d32' ?>; font-weight:bold;">
    <label>Remaining:</label> <strong style="font-size:14px;"><?= formatMoney($order['remaining']) ?></strong>
  </div>
  <?php endif; ?>

  <div class="inv-divider"></div>
  <div class="inv-footer">
    Thank you for your trust! (شکریہ)<br>
    Larkana Tailors &amp; Cloth House<br>
    0300-2151261
  </div>
</div>
</body>
</html>
