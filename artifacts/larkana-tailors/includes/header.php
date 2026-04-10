<?php
$page = $_GET['page'] ?? 'dashboard';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Larkana Fabrics</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="topbar">
  <div class="topbar-left" style="display:flex;align-items:center;gap:8px;">
    <img src="assets/logo.jpeg" alt="Logo" style="height:26px;width:auto;border-radius:2px;">
    <span class="shop-name">Larkana Fabrics</span>
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
      <a href="?page=order_new" class="nav-item<?= $page==='order_new'?' active':'' ?>">&#43; New Order</a>
      <a href="?page=orders" class="nav-item<?= $page==='orders'?' active':'' ?>">&#128196; All Orders</a>
      <a href="?page=customers" class="nav-item<?= $page==='customers'?' active':'' ?>">&#128101; Customers</a>
      <?php if (isAdmin()): ?>
      <a href="?page=stock" class="nav-item<?= $page==='stock'?' active':'' ?>">&#128229; Stock</a>
      <a href="?page=reports" class="nav-item<?= $page==='reports'?' active':'' ?>">&#128200; Reports</a>
      <a href="?page=workers" class="nav-item<?= $page==='workers'?' active':'' ?>">&#128119; Workers</a>
      <a href="?page=settings" class="nav-item<?= $page==='settings'?' active':'' ?>">&#9881; Settings</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <small>Lakhmir Khan<br>0300-2151261</small>
    </div>
  </div>
  <div class="main-content">
