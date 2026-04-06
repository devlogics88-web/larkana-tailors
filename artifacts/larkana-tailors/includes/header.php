<?php
$page = $_GET['page'] ?? 'dashboard';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Larkana Tailors &amp; Cloth House</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="topbar">
  <div class="topbar-left">
    <span class="shop-name">&#x2702; Larkana Tailors &amp; Cloth House</span>
    <span class="shop-sub">Gents Specialist &mdash; Islamabad</span>
  </div>
  <div class="topbar-right">
    <span class="user-info">&#128100; <?= h($user['full_name'] ?: $user['username']) ?> (<?= h(ucfirst($user['role'])) ?>)</span>
    <form method="POST" action="?action=logout" style="display:inline;" onsubmit="return confirm('Logout?')">
      <input type="hidden" name="csrf" value="<?= h(getCsrf()) ?>">
      <button type="submit" class="btn-logout">Logout</button>
    </form>
  </div>
</div>
<div class="layout">
  <div class="sidebar">
    <nav>
      <a href="?page=dashboard" class="nav-item<?= $page==='dashboard'?' active':'' ?>">&#128202; Dashboard</a>
      <a href="?page=order_new" class="nav-item<?= $page==='order_new'?' active':'' ?>">&#43; New Order (&#9654; آرڈر)</a>
      <a href="?page=orders" class="nav-item<?= $page==='orders'?' active':'' ?>">&#128196; All Orders (آرڈرز)</a>
      <a href="?page=customers" class="nav-item<?= $page==='customers'?' active':'' ?>">&#128101; Customer Search (کسٹمر)</a>
      <?php if (isAdmin()): ?>
      <a href="?page=stock" class="nav-item<?= $page==='stock'?' active':'' ?>">&#128229; Stock (اسٹاک)</a>
      <a href="?page=reports" class="nav-item<?= $page==='reports'?' active':'' ?>">&#128200; Reports (رپورٹس)</a>
      <a href="?page=workers" class="nav-item<?= $page==='workers'?' active':'' ?>">&#128119; Workers (ورکرز)</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <small>Lakhmir Khan<br>0300-2151261</small>
    </div>
  </div>
  <div class="main-content">
