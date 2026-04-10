<?php
$type = $_GET['type'] ?? 'customer';
$isLabour = $type === 'labour';
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) { echo '<p>Invalid order.</p>'; return; }
$order = getOrder($orderId);
if (!$order) { echo '<p>Order not found.</p>'; return; }
$m = $order['measurements'] ?? [];

// Pricing breakdown
$stitchingPrice  = (float)($order['stitching_price'] ?? 0);
$stitchingName   = $order['stitching_type_name'] ?? '';
$buttonPrice     = (float)($order['button_price'] ?? 0);
$buttonName      = $order['button_type_name'] ?? '';
$panchaPrice     = (float)($order['pancha_price'] ?? 0);
$panchaName      = $order['pancha_type_name'] ?? '';
$metersUsed      = (float)($order['meters_used'] ?? 0);
$sellPerMeter    = (float)($order['stock_sell_per_meter'] ?? 0);
$clothCost       = ($order['cloth_source'] === 'shop' && $metersUsed > 0) ? $metersUsed * $sellPerMeter : 0;
$discount        = (float)($order['discount'] ?? 0);
$totalPrice      = (float)($order['total_price'] ?? 0);
$advancePaid     = (float)($order['advance_paid'] ?? 0);
$remaining       = (float)($order['remaining'] ?? 0);
$paymentMethod   = $order['payment_method'] ?? 'Cash';
$receivingHand   = $order['receiving_hand'] ?? '';

// Customer ID formatted
$customerId  = (int)($order['customer_id'] ?? 0);
$customerRef = $customerId ? 'CID-' . str_pad($customerId, 5, '0', STR_PAD_LEFT) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $isLabour ? 'Labour Copy' : 'Customer Invoice' ?> - <?= h($order['order_no']) ?></title>
<link rel="stylesheet" href="assets/style.css">
<style>
body { background: #fff; margin: 0; padding: 10px; }
@media screen { .invoice-wrapper { max-width: 400px; margin: 0 auto; } }
</style>
</head>
<body>
<div style="margin-bottom:10px;" class="no-print">
  <button onclick="window.print()" class="btn btn-success">&#128424; Print</button>
  <button onclick="window.close()" class="btn" style="background:#546e7a;color:#fff;">Close</button>
  <a href="?page=order_edit&id=<?= h($orderId) ?>" class="btn btn-info">Edit Order</a>
</div>

<div class="invoice-wrapper">
  <div class="copy-title"><?= $isLabour ? 'STITCHING LABOUR COPY' : 'CUSTOMER COPY' ?></div>

  <div style="text-align:center; margin-bottom:4px;">
    <img src="assets/logo.jpeg" alt="Logo" style="height:40px; width:auto;">
  </div>
  <div class="inv-shop-name">Larkana Fabrics</div>
  <div class="inv-shop-sub">Gents Specialist &mdash; Lakhmir Khan</div>
  <div class="inv-shop-sub"><?= h(getSetting('shop_address','SOAN GARDEN, Shahid Arcade, Islamabad')) ?></div>
  <div class="inv-shop-sub">&#128222; <?= h(getSetting('shop_phone','0300-2151261')) ?></div>
  <div class="inv-divider"></div>

  <!-- Order header — always shown -->
  <div class="inv-section">
    <label>Order #:</label> <strong><?= h($order['order_no']) ?></strong>
    &nbsp;&nbsp; <label>Date:</label> <?= formatDate($order['order_date']) ?>
  </div>
  <?php if ($order['delivery_date']): ?>
  <div class="inv-section">
    <label>Delivery:</label> <strong><?= formatDate($order['delivery_date']) ?></strong>
  </div>
  <?php endif; ?>

  <?php if (!$isLabour): ?>
  <!-- Customer details — customer copy only -->
  <div class="inv-section">
    <label>Customer:</label> <strong><?= h($order['customer_name']) ?></strong>
    <?php if ($customerRef): ?>&nbsp;<span style="color:#888;font-size:9px;">(<?= h($customerRef) ?>)</span><?php endif; ?>
  </div>
  <?php if ($order['customer_phone']): ?>
  <div class="inv-section">
    <label>Phone:</label> <?= h($order['customer_phone']) ?>
  </div>
  <?php endif; ?>
  <?php if ($order['suit_type'] || $order['stitching_type_name']): ?>
  <div class="inv-section">
    <label>Suit:</label> <?= h($order['suit_type'] ?? '') ?>
    <?= $order['stitching_type_name'] ? ' / ' . h($order['stitching_type_name']) : '' ?>
  </div>
  <?php endif; ?>
  <?php if ($order['cloth_source'] === 'shop'): ?>
  <div class="inv-section">
    <label>Cloth:</label>
    <?= h($order['brand_name'] ?: ($order['stock_brand_name'] ?? '')) ?>
    <?php if ($metersUsed > 0): ?>
      &mdash; <strong><?= h($metersUsed) ?>m</strong>
      <?php if ($sellPerMeter > 0): ?>
        @ Rs.<?= h(number_format($sellPerMeter,0)) ?>/m
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php elseif ($order['cloth_source'] === 'self'): ?>
  <div class="inv-section"><label>Cloth:</label> Self (customer's own)</div>
  <?php endif; ?>
  <?php endif; ?>

  <div class="inv-divider"></div>

  <!-- MEASUREMENTS — always shown -->
  <div style="font-size:11px; font-weight:bold; margin-bottom:3px;">Measurements:</div>

  <?php
  $mainRows = [
    ['Shirt Length',            'shirt_length'],
    ['Arm / Bazu',              'arm'],
    ['Shoulder',                'shoulder'],
    ['Collar / Neck',           'collar'],
    ['Chest',                   'chest'],
    ['Waist',                   'waist'],
    ['Hip',                     'hip'],
    ['Shalwar Length',          'shalwar_length'],
    ['Pancha (Shalwar Bottom)', 'shalwar_bottom'],
    ['Shalwar Waist',           'shalwar_waist'],
    ['Cuff / Karnok',           'cuff'],
  ];
  $hasMain = false;
  foreach ($mainRows as [,$k]) { if (!empty($m[$k])) { $hasMain = true; break; } }
  if ($hasMain):
  ?>
  <table style="width:100%; border-collapse:collapse; font-size:10px; margin-bottom:2px;">
    <?php foreach ($mainRows as [$label, $key]):
      $val = $m[$key] ?? '';
      if (!$val) continue;
    ?>
    <tr>
      <td style="border:1px solid #ccc; padding:2px 4px; background:#e6eaed; font-size:9px; width:45%;"><?= h($label) ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; font-size:12px;"><?= h($val) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php
  $bottomRows = [
    ['Main Full',    'main_full',    'Front',        'front_style'],
    ['Main Half',    'main_half',    'Side',         'size_note'],
    ['Kaf',          'kaf',          'Shalwar Style','shalwar_style'],
    ['Gera Chorus',  'gera_chorus',  'Gera Oval',    'gera_oval'],
    ['Harmol',       'harmol',       'Chak Patti Button','chak_patti_button'],
  ];
  $hasBottom = false;
  foreach ($bottomRows as $r) {
      if (!empty($m[$r[1]]) || !empty($m[$r[3]])) { $hasBottom = true; break; }
  }
  if ($hasBottom):
  ?>
  <table style="width:100%; border-collapse:collapse; font-size:10px; margin-top:2px;">
    <?php foreach ($bottomRows as [$lL,$kL,$lR,$kR]):
      $vL = $m[$kL] ?? ''; $vR = $m[$kR] ?? '';
      if (!$vL && !$vR) continue;
    ?>
    <tr>
      <td style="border:1px solid #ccc; padding:2px 4px; font-size:9px; background:#e6eaed; width:22%;"><?= h($lL) ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; width:28%;"><?= h($vL) ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-size:9px; background:#e6eaed; width:22%;"><?= h($lR) ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; width:28%;"><?= h($vR) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php
  $extraRows = [['Sleeve','sleeve'],['Trouser Length','trouser_length'],['Trouser Bottom','trouser_bottom']];
  $hasExtra  = false;
  foreach ($extraRows as [,$k]) { if (!empty($m[$k])) { $hasExtra = true; break; } }
  if ($hasExtra):
  ?>
  <table style="width:100%; border-collapse:collapse; font-size:10px; margin-top:2px;">
    <?php foreach ($extraRows as [$label,$key]):
      $val = $m[$key] ?? ''; if (!$val) continue;
    ?>
    <tr>
      <td style="border:1px solid #ccc; padding:2px 4px; background:#e6eaed; font-size:9px; width:45%;"><?= h($label) ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; font-size:12px;"><?= h($val) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php if (!empty($m['detail']) && !$isLabour): ?>
  <div class="inv-section" style="margin-top:3px;">
    <label>Detail:</label> <?= h($m['detail']) ?>
  </div>
  <?php endif; ?>
  <?php if ($order['notes'] && !$isLabour): ?>
  <div class="inv-section">
    <label>Notes:</label> <?= h($order['notes']) ?>
  </div>
  <?php endif; ?>

  <?php if (!$isLabour): ?>
  <div class="inv-divider"></div>

  <!-- ITEMIZED PRICING — customer copy only -->
  <table style="width:100%; border-collapse:collapse; font-size:11px; margin-bottom:4px;">
    <?php if ($order['cloth_source'] === 'shop' && $clothCost > 0): ?>
    <tr>
      <td style="padding:2px 4px;">Cloth (<?= h($metersUsed) ?>m &times; Rs.<?= number_format($sellPerMeter,0) ?>)</td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold;"><?= formatMoney($clothCost) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($stitchingPrice > 0): ?>
    <tr>
      <td style="padding:2px 4px;">Stitching<?= $stitchingName ? ' — ' . h($stitchingName) : '' ?></td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold;"><?= formatMoney($stitchingPrice) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($buttonPrice > 0): ?>
    <tr>
      <td style="padding:2px 4px;">Button<?= $buttonName ? ' — ' . h($buttonName) : '' ?></td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold;"><?= formatMoney($buttonPrice) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($panchaPrice > 0): ?>
    <tr>
      <td style="padding:2px 4px;">Pancha<?= $panchaName ? ' — ' . h($panchaName) : '' ?></td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold;"><?= formatMoney($panchaPrice) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($discount > 0): ?>
    <tr>
      <td style="padding:2px 4px; color:#2e7d32;">Discount</td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold; color:#2e7d32;">- <?= formatMoney($discount) ?></td>
    </tr>
    <?php endif; ?>
    <tr style="background:#e6eaed;">
      <td style="padding:3px 4px; font-weight:bold; font-size:13px;">Total Amount</td>
      <td style="padding:3px 4px; text-align:right; font-weight:bold; font-size:15px; color:#1B242D;"><?= formatMoney($totalPrice) ?></td>
    </tr>
    <tr>
      <td style="padding:2px 4px;">Advance Paid</td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold; color:#2e7d32;"><?= formatMoney($advancePaid) ?></td>
    </tr>
    <tr style="background:<?= $remaining > 0 ? '#fce4ec' : '#e8f5e9' ?>;">
      <td style="padding:3px 4px; font-weight:bold; font-size:12px;">Balance Due</td>
      <td style="padding:3px 4px; text-align:right; font-weight:bold; font-size:14px; color:<?= $remaining > 0 ? '#c62828' : '#2e7d32' ?>;"><?= formatMoney($remaining) ?></td>
    </tr>
  </table>

  <?php if ($paymentMethod || $receivingHand): ?>
  <div style="font-size:10px; margin-top:4px;">
    <?php if ($paymentMethod): ?>
    <span><strong>Payment:</strong> <?= h($paymentMethod) ?></span>
    <?php endif; ?>
    <?php if ($receivingHand): ?>
    &nbsp;&nbsp; <span><strong>Received by:</strong> <?= h($receivingHand) ?></span>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <div class="inv-divider"></div>
  <div class="inv-footer">
    Thank you for your trust!<br>
    Larkana Fabrics<br>
    <?= h(getSetting('shop_phone','0300-2151261')) ?>
  </div>
</div>
</body>
</html>
