<?php
$type = $_GET['type'] ?? 'customer';
$isLabour = $type === 'labour';
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) { echo '<p>Invalid order.</p>'; return; }
$order = getOrder($orderId);
if (!$order) { echo '<p>Order not found.</p>'; return; }
$m = $order['measurements'] ?? [];

// Pricing breakdown
$stitchingPrice = (float)($order['stitching_price'] ?? 0);
$metersUsed     = (float)($order['meters_used'] ?? 0);
$sellPerMeter   = (float)($order['stock_sell_per_meter'] ?? 0);
$clothCost      = ($order['cloth_source'] === 'shop' && $metersUsed > 0) ? $metersUsed * $sellPerMeter : 0;
$totalPrice     = (float)($order['total_price'] ?? 0);
$advancePaid    = (float)($order['advance_paid'] ?? 0);
$remaining      = (float)($order['remaining'] ?? 0);

// Customer ID formatted
$customerId = (int)($order['customer_id'] ?? 0);
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
  <div class="copy-title"><?= $isLabour ? 'STITCHING LABOUR COPY (لیبر کاپی)' : 'CUSTOMER COPY (کسٹمر کاپی)' ?></div>

  <div style="text-align:center; margin-bottom:4px;">
    <img src="assets/logo.jpeg" alt="Logo" style="height:40px; width:auto;">
  </div>
  <div class="inv-shop-name">Larkana Tailors &amp; Cloth House</div>
  <div class="inv-shop-sub">Gents Specialist &mdash; Lakhmir Khan</div>
  <div class="inv-shop-sub"><?= h(getSetting('shop_address','SOAN GARDEN, Shahid Arcade, Islamabad')) ?></div>
  <div class="inv-shop-sub">&#128222; <?= h(getSetting('shop_phone','0300-2151261')) ?></div>
  <div class="inv-divider"></div>

  <div class="inv-section">
    <label>Order #:</label> <strong><?= h($order['order_no']) ?></strong>
    &nbsp;&nbsp; <label>Date:</label> <?= formatDate($order['order_date']) ?>
  </div>
  <div class="inv-section">
    <label>Customer:</label> <strong><?= h($order['customer_name']) ?></strong>
    <?php if ($customerRef): ?>&nbsp;<span style="color:#888;font-size:9px;">(<?= h($customerRef) ?>)</span><?php endif; ?>
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
  <?php if ($order['cloth_source'] === 'shop'): ?>
  <div class="inv-section">
    <label>Cloth:</label>
    <?= h($order['brand_name'] ?: ($order['stock_brand_name'] ?? '')) ?>
    <?php if ($metersUsed > 0): ?>
      — <strong><?= h($metersUsed) ?>m</strong>
      <?php if ($sellPerMeter > 0): ?>
        @ Rs.<?= h(number_format($sellPerMeter,0)) ?>/m
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php elseif ($order['cloth_source'] === 'self'): ?>
  <div class="inv-section"><label>Cloth:</label> Self (اپنا کپڑا)</div>
  <?php endif; ?>

  <div class="inv-divider"></div>

  <!-- MEASUREMENTS matching physical card layout -->
  <div style="font-size:11px; font-weight:bold; margin-bottom:3px;">Measurements (پیمائش):</div>
  <table style="width:100%; border-collapse:collapse; font-size:10px;">
    <?php
    $rows = [
      ['لمبائی قمیص','Shirt Length','shirt_length',  'بازو','Arm/Bazu','arm'],
      ['تیرہ','Shoulder','shoulder',                  'گلا','Collar','collar'],
      ['چسٹ','Chest','chest',                         'کمر','Waist','waist'],
      ['گیرہ','Hip','hip',                            'شلوار لمبائی','Shlwr Length','shalwar_length'],
      ['پانچہ','Shalwar Bottom','shalwar_bottom',     'شلوار گیرہ','Shlwr Waist','shalwar_waist'],
      ['کارنوک','Cuff','cuff',                        'آستین','Sleeve','sleeve'],
    ];
    foreach ($rows as [$urL,$enL,$keyL, $urR,$enR,$keyR]):
      $vL = $m[$keyL] ?? ''; $vR = $m[$keyR] ?? '';
      if (!$vL && !$vR) continue;
    ?>
    <tr>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; font-size:9px; width:22%; background:#e6eaed;"><?= $urL ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; font-size:12px; width:28%;"><?= h($vL) ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; font-size:9px; width:22%; background:#e6eaed;"><?= $urR ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; font-size:12px; width:28%;"><?= h($vR) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <?php
  // Bottom style section
  $styleFields = [
    ['فرنٹ','front_style'], ['مین فل','main_full'], ['مین ہاف','main_half'],
    ['کف','kaf'], ['گیراچورس','gera_chorus'], ['سائز','size_note'],
    ['شلوار','shalwar_style'], ['گیراول','gera_oval'],
    ['ٹراؤزر','trouser_length'], ['موہری','trouser_bottom'],
  ];
  $hasStyle = false;
  foreach ($styleFields as [,$k]) { if (!empty($m[$k])) { $hasStyle = true; break; } }
  if ($hasStyle):
  ?>
  <table style="width:100%; border-collapse:collapse; font-size:10px; margin-top:2px;">
    <?php
    $pairs = array_chunk($styleFields, 2);
    foreach ($pairs as $pair):
      $p0 = $pair[0]; $p1 = $pair[1] ?? null;
      $v0 = $m[$p0[1]] ?? ''; $v1 = $p1 ? ($m[$p1[1]] ?? '') : '';
      if (!$v0 && !$v1) continue;
    ?>
    <tr>
      <td style="border:1px solid #ccc; padding:2px 4px; font-size:9px; background:#e6eaed; width:22%;"><?= $p0[0] ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; width:28%;"><?= h($v0) ?></td>
      <?php if ($p1): ?>
      <td style="border:1px solid #ccc; padding:2px 4px; font-size:9px; background:#e6eaed; width:22%;"><?= $p1[0] ?></td>
      <td style="border:1px solid #ccc; padding:2px 4px; font-weight:bold; width:28%;"><?= h($v1) ?></td>
      <?php else: ?>
      <td colspan="2"></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php if (!empty($m['detail'])): ?>
  <div class="inv-section" style="margin-top:3px;">
    <label>Detail:</label> <?= h($m['detail']) ?>
  </div>
  <?php endif; ?>
  <?php if ($order['notes']): ?>
  <div class="inv-section">
    <label>Notes:</label> <?= h($order['notes']) ?>
  </div>
  <?php endif; ?>

  <?php if (!$isLabour): ?>
  <div class="inv-divider"></div>

  <!-- ITEMIZED PRICING -->
  <table style="width:100%; border-collapse:collapse; font-size:11px; margin-bottom:4px;">
    <?php if ($order['cloth_source'] === 'shop' && $clothCost > 0): ?>
    <tr>
      <td style="padding:2px 4px;">Cloth (<?= h($metersUsed) ?>m × Rs.<?= number_format($sellPerMeter,0) ?>)</td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold;"><?= formatMoney($clothCost) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($stitchingPrice > 0): ?>
    <tr>
      <td style="padding:2px 4px;">Stitching (سلائی)</td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold;"><?= formatMoney($stitchingPrice) ?></td>
    </tr>
    <?php endif; ?>
    <tr style="background:#e6eaed;">
      <td style="padding:3px 4px; font-weight:bold; font-size:13px;">Total Amount (کل قیمت)</td>
      <td style="padding:3px 4px; text-align:right; font-weight:bold; font-size:15px; color:#1B242D;"><?= formatMoney($totalPrice) ?></td>
    </tr>
    <tr>
      <td style="padding:2px 4px;">Advance Paid (ایڈوانس)</td>
      <td style="padding:2px 4px; text-align:right; font-weight:bold; color:#2e7d32;"><?= formatMoney($advancePaid) ?></td>
    </tr>
    <tr style="background:<?= $remaining > 0 ? '#fce4ec' : '#e8f5e9' ?>;">
      <td style="padding:3px 4px; font-weight:bold; font-size:12px;">Balance Due (باقی رقم)</td>
      <td style="padding:3px 4px; text-align:right; font-weight:bold; font-size:14px; color:<?= $remaining > 0 ? '#c62828' : '#2e7d32' ?>;"><?= formatMoney($remaining) ?></td>
    </tr>
  </table>
  <?php endif; ?>

  <div class="inv-divider"></div>
  <div class="inv-footer">
    Thank you for your trust! (شکریہ)<br>
    Larkana Tailors &amp; Cloth House<br>
    <?= h(getSetting('shop_phone','0300-2151261')) ?>
  </div>
</div>
</body>
</html>
