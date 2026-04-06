<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page   = $_GET['page']   ?? 'dashboard';
$action = $_GET['action'] ?? '';

// Initialize DB on every request
try {
    getDB();
} catch (Exception $e) {
    die('<p style="color:red;font-family:Arial;padding:20px;">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// ========================
// ACTION HANDLERS
// ========================
if ($action) {
    switch ($action) {
        case 'login':
            $error = handleLogin();
            if ($error) {
                require __DIR__ . '/views/login.php';
                exit;
            }
            break;

        case 'logout':
            handleLogout();
            break;

        case 'save_order':
            requireLogin();
            verifyCsrf();
            $error = null;
            try {
                $customerId = (int)($_POST['customer_id'] ?? 0);

                // Handle new customer creation
                if (!$customerId && !empty($_POST['new_name'])) {
                    $customerId = saveCustomer([
                        'name'    => trim($_POST['new_name'] ?? ''),
                        'phone'   => trim($_POST['new_phone'] ?? ''),
                        'address' => trim($_POST['new_address'] ?? ''),
                        'notes'   => '',
                    ]);
                }
                if (!$customerId) {
                    $error = 'Please select or add a customer.';
                    throw new RuntimeException($error);
                }

                $orderData = [
                    'id'            => (int)($_POST['order_id'] ?? 0) ?: null,
                    'customer_id'   => $customerId,
                    'order_date'    => $_POST['order_date'] ?? date('Y-m-d'),
                    'delivery_date' => $_POST['delivery_date'] ?? null,
                    'suit_type'     => $_POST['suit_type'] ?? '',
                    'stitch_type'   => $_POST['stitch_type'] ?? '',
                    'cloth_source'  => $_POST['cloth_source'] ?? 'self',
                    'stock_item_id' => ($_POST['cloth_source'] ?? '') === 'shop' ? (int)($_POST['stock_item_id'] ?? 0) : null,
                    'meters_used'   => ($_POST['cloth_source'] ?? '') === 'shop' ? (float)($_POST['meters_used'] ?? 0) : null,
                    'brand_name'    => $_POST['brand_name'] ?? '',
                    'total_price'   => (float)($_POST['total_price'] ?? 0),
                    'advance_paid'  => (float)($_POST['advance_paid'] ?? 0),
                    'remaining'     => (float)($_POST['remaining'] ?? 0),
                    'status'        => $_POST['status'] ?? 'pending',
                    'notes'         => trim($_POST['notes'] ?? ''),
                ];

                $measurements = [
                    'shirt_length'   => $_POST['m_shirt_length'] ?? null,
                    'sleeve'         => $_POST['m_sleeve'] ?? null,
                    'arm'            => $_POST['m_arm'] ?? null,
                    'shoulder'       => $_POST['m_shoulder'] ?? null,
                    'collar'         => $_POST['m_collar'] ?? null,
                    'chest'          => $_POST['m_chest'] ?? null,
                    'waist'          => $_POST['m_waist'] ?? null,
                    'hip'            => $_POST['m_hip'] ?? null,
                    'shalwar_length' => $_POST['m_shalwar_length'] ?? null,
                    'shalwar_bottom' => $_POST['m_shalwar_bottom'] ?? null,
                    'shalwar_waist'  => $_POST['m_shalwar_waist'] ?? null,
                    'cuff'           => $_POST['m_cuff'] ?? null,
                    'trouser_length' => $_POST['m_trouser_length'] ?? null,
                    'trouser_bottom' => $_POST['m_trouser_bottom'] ?? null,
                    'front_style'    => $_POST['m_front_style'] ?? null,
                    'detail'         => $_POST['m_detail'] ?? null,
                ];

                $orderId = saveOrder($orderData, $measurements);
                flash('order_ok', 'Order saved successfully!');
                header("Location: ?page=order_edit&id=$orderId&saved=1");
                exit;

            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            } catch (PDOException $e) {
                $error = 'A database error occurred. Please try again.';
            }
            // Fall through to show order form with error
            requireLogin();
            $orderId = (int)($_POST['order_id'] ?? 0);
            $order   = $orderId ? getOrder($orderId) : null;
            if (!$order) $order = [];
            require __DIR__ . '/includes/header.php';
            require __DIR__ . '/views/order_form.php';
            require __DIR__ . '/includes/footer.php';
            exit;

        case 'search_customer':
            requireLogin();
            header('Content-Type: application/json');
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 2) { echo '[]'; exit; }
            echo json_encode(searchCustomers($q));
            exit;

        case 'save_stock':
            requireAdmin();
            verifyCsrf();
            try {
                $data = [
                    'id'              => (int)($_POST['stock_id'] ?? 0) ?: null,
                    'brand_name'      => trim($_POST['brand_name'] ?? ''),
                    'cloth_type'      => trim($_POST['cloth_type'] ?? ''),
                    'total_meters'    => (float)($_POST['total_meters'] ?? 0),
                    'available_meters'=> (float)($_POST['avail_meters'] ?? $_POST['total_meters'] ?? 0),
                    'cost_per_meter'  => (float)($_POST['cost_meter'] ?? 0),
                    'sell_per_meter'  => ($_POST['sell_meter'] ?? '') !== '' ? (float)$_POST['sell_meter'] : null,
                    'notes'           => trim($_POST['stock_notes'] ?? ''),
                ];
                if (!$data['brand_name']) throw new RuntimeException('Brand name is required.');
                saveStockItem($data);
                flash('stock_ok', 'Stock item saved.');
            } catch (RuntimeException $e) {
                flash('stock_err', $e->getMessage());
            } catch (PDOException $e) {
                flash('stock_err', 'A database error occurred. Please try again.');
            }
            header('Location: ?page=stock');
            exit;

        case 'delete_stock':
            requireAdmin();
            verifyCsrf();
            $id = (int)($_GET['id'] ?? 0);
            if ($id) {
                try {
                    deleteStockItem($id);
                    flash('stock_ok', 'Stock item deleted.');
                } catch (PDOException $e) {
                    flash('stock_err', 'Cannot delete: this cloth item is referenced by existing orders.');
                }
            }
            header('Location: ?page=stock');
            exit;

        case 'delete_order':
            requireAdmin();
            verifyCsrf();
            $id = (int)($_GET['id'] ?? 0);
            if ($id) {
                $db = getDB();
                // Restore stock meters before deleting.
                $oStmt = $db->prepare("SELECT cloth_source, stock_item_id, meters_used FROM orders WHERE id=?");
                $oStmt->execute([$id]);
                $oRow = $oStmt->fetch();
                if ($oRow && $oRow['cloth_source'] === 'shop' && $oRow['stock_item_id'] && $oRow['meters_used'] > 0) {
                    $db->prepare("UPDATE stock_items SET available_meters = available_meters + ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                       ->execute([$oRow['meters_used'], $oRow['stock_item_id']]);
                }
                // Remove dependent stock transactions first (FK constraint).
                $db->prepare("DELETE FROM stock_transactions WHERE order_id=?")->execute([$id]);
                $db->prepare("DELETE FROM measurements WHERE order_id=?")->execute([$id]);
                $db->prepare("DELETE FROM orders WHERE id=?")->execute([$id]);
            }
            header('Location: ?page=orders');
            exit;

        case 'add_worker':
            requireAdmin();
            verifyCsrf();
            $err = handleAddWorker();
            if ($err) flash('worker_err', $err);
            else flash('worker_ok', 'Worker added successfully.');
            header('Location: ?page=workers');
            exit;

        case 'delete_worker':
            requireAdmin();
            verifyCsrf();
            $id = (int)($_GET['id'] ?? 0);
            if ($id && $id !== (int)$_SESSION['user_id']) {
                $db = getDB();
                $db->prepare("DELETE FROM users WHERE id=? AND role!='admin'")->execute([$id]);
            }
            flash('worker_ok', 'Worker deleted.');
            header('Location: ?page=workers');
            exit;
    }
}

// ========================
// PAGE ROUTING
// ========================

// Invoice pages — standalone HTML (no shell)
if ($page === 'invoice') {
    requireLogin();
    require __DIR__ . '/views/invoice.php';
    exit;
}

// Login page — no shell
if ($page === 'login') {
    if (isLoggedIn()) {
        header('Location: ?page=dashboard');
        exit;
    }
    $error = null;
    require __DIR__ . '/views/login.php';
    exit;
}

// All other pages require login
requireLogin();

// Prepare page-specific data
$order = null;
$error = null;

if ($page === 'order_edit' || ($page === 'order_new' && isset($_GET['id']))) {
    $page = 'order_edit';
    $orderId = (int)($_GET['id'] ?? 0);
    $order   = getOrder($orderId);
    if (!$order) {
        $page  = 'order_new';
        $order = [];
    }
}

if ($page === 'order_new' && isset($_GET['prefill_customer'])) {
    $cid = (int)$_GET['prefill_customer'];
    $customer = getCustomer($cid);
    $order = ['prefill_customer' => $customer];
}

// Order saved flash
if (isset($_GET['saved'])) {
    $flashOk = flash('order_ok') ?? 'Order saved successfully!';
}

// Render shell + page view
require __DIR__ . '/includes/header.php';

if (!empty($flashOk)) echo '<div class="alert alert-success">' . h($flashOk) . '</div>';
if (!empty($error))   echo '<div class="alert alert-error">' . h($error) . '</div>';

$viewFile = __DIR__ . '/views/' . match($page) {
    'dashboard'       => 'dashboard.php',
    'order_new'       => 'order_form.php',
    'order_edit'      => 'order_form.php',
    'orders'          => 'order_list.php',
    'customers'       => 'customers.php',
    'customer_orders' => 'customer_orders.php',
    'stock'           => isAdmin() ? 'stock.php' : 'dashboard.php',
    'reports'         => isAdmin() ? 'reports.php' : 'dashboard.php',
    'workers'         => isAdmin() ? 'workers.php' : 'dashboard.php',
    default           => 'dashboard.php',
};

if (file_exists($viewFile)) {
    require $viewFile;
} else {
    echo '<div class="alert alert-error">Page not found.</div>';
}

require __DIR__ . '/includes/footer.php';
