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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=dashboard');
                exit;
            }
            verifyCsrf();
            handleLogout();
            break;

        case 'save_order':
            requireLogin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=orders'); exit; }
            verifyCsrf();
            $error = null;
            try {
                $customerId  = (int)($_POST['customer_id'] ?? 0);
                $newName     = trim($_POST['new_name'] ?? '');
                $clothSource = $_POST['cloth_source'] ?? 'self';
                $totalPrice  = (float)($_POST['total_price'] ?? 0);
                $advancePaid = (float)($_POST['advance_paid'] ?? 0);

                if ($totalPrice < 0) throw new RuntimeException('Total price cannot be negative.');
                if ($advancePaid > $totalPrice) throw new RuntimeException('Advance paid cannot exceed total price.');
                if ($clothSource === 'shop' && !(int)($_POST['stock_item_id'] ?? 0)) {
                    throw new RuntimeException('Please select a stock item for shop cloth.');
                }
                if (!$customerId && !$newName) {
                    throw new RuntimeException('Please select or add a customer.');
                }

                // Resolve stitching type — name and price always from DB, never from POST
                $stitchingTypeId   = (int)($_POST['stitching_type_id'] ?? 0) ?: null;
                $stitchingTypeName = null;
                $stitchingPriceFromType = null;
                if ($stitchingTypeId) {
                    $stStmt = getDB()->prepare("SELECT name, price FROM stitching_types WHERE id=?");
                    $stStmt->execute([$stitchingTypeId]);
                    $stRow = $stStmt->fetch();
                    if ($stRow) {
                        $stitchingTypeName      = $stRow['name'];
                        $stitchingPriceFromType = (float)$stRow['price'];
                    }
                }

                // Resolve button type — price always from DB
                $buttonTypeId   = (int)($_POST['button_type_id'] ?? 0) ?: null;
                $buttonTypeName = null;
                $buttonPrice    = 0.0;
                if ($buttonTypeId) {
                    $btStmt = getDB()->prepare("SELECT name, price FROM button_types WHERE id=?");
                    $btStmt->execute([$buttonTypeId]);
                    $btRow = $btStmt->fetch();
                    if ($btRow) {
                        $buttonTypeName = $btRow['name'];
                        $buttonPrice    = (float)$btRow['price'];
                    }
                }

                // Resolve pancha type — price always from DB
                $panchaTypeId   = (int)($_POST['pancha_type_id'] ?? 0) ?: null;
                $panchaTypeName = null;
                $panchaPrice    = 0.0;
                if ($panchaTypeId) {
                    $ptStmt = getDB()->prepare("SELECT name, price FROM pancha_types WHERE id=?");
                    $ptStmt->execute([$panchaTypeId]);
                    $ptRow = $ptStmt->fetch();
                    if ($ptRow) {
                        $panchaTypeName = $ptRow['name'];
                        $panchaPrice    = (float)$ptRow['price'];
                    }
                }

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
                    'main_full'      => $_POST['m_main_full'] ?? null,
                    'main_half'      => $_POST['m_main_half'] ?? null,
                    'kaf'            => $_POST['m_kaf'] ?? null,
                    'gera_chorus'    => $_POST['m_gera_chorus'] ?? null,
                    'size_note'        => $_POST['m_size_note'] ?? null,
                    'shalwar_style'    => $_POST['m_shalwar_style'] ?? null,
                    'gera_oval'        => $_POST['m_gera_oval'] ?? null,
                    'harmol'           => $_POST['m_harmol'] ?? null,
                    'chak_patti_button'=> $_POST['m_chak_patti_button'] ?? null,
                    'detail'           => $_POST['m_detail'] ?? null,
                ];

                $db = getDB();
                $db->beginTransaction();
                try {
                    if (!$customerId && $newName !== '') {
                        $customerId = saveCustomer([
                            'name'    => $newName,
                            'phone'   => trim($_POST['new_phone'] ?? ''),
                            'address' => trim($_POST['new_address'] ?? ''),
                            'notes'   => '',
                        ]);
                    }
                    if (!$customerId) {
                        throw new RuntimeException('Please select or add a customer.');
                    }
                    $orderData = [
                        'id'                  => (int)($_POST['order_id'] ?? 0) ?: null,
                        'customer_id'         => $customerId,
                        'order_date'          => $_POST['order_date'] ?? date('Y-m-d'),
                        'delivery_date'       => $_POST['delivery_date'] ?? null,
                        'suit_type'           => $_POST['suit_type'] ?? '',
                        'stitch_type'         => $_POST['stitch_type'] ?? '',
                        'cloth_source'        => $clothSource,
                        'stock_item_id'       => $clothSource === 'shop' ? (int)($_POST['stock_item_id'] ?? 0) : null,
                        'meters_used'         => $clothSource === 'shop' ? (float)($_POST['meters_used'] ?? 0) : null,
                        'brand_name'          => $_POST['brand_name'] ?? '',
                        'stitching_price'     => $stitchingPriceFromType ?? max(0.0, (float)($_POST['stitching_price'] ?? 0)),
                        'stitching_type_id'   => $stitchingTypeId,
                        'stitching_type_name' => $stitchingTypeName,
                        'button_type_id'      => $buttonTypeId,
                        'button_type_name'    => $buttonTypeName,
                        'button_price'        => $buttonPrice,
                        'pancha_type_id'      => $panchaTypeId,
                        'pancha_type_name'    => $panchaTypeName,
                        'pancha_price'        => $panchaPrice,
                        'discount'            => max(0.0, (float)($_POST['discount'] ?? 0)),
                        'total_price'         => $totalPrice,
                        'advance_paid'        => $advancePaid,
                        'remaining'           => (float)($_POST['remaining'] ?? 0),
                        'payment_method'      => in_array($_POST['payment_method'] ?? '', ['Cash','Online - JazzCash','Online - Easypaisa','Online - Bank Acc'], true) ? $_POST['payment_method'] : 'Cash',
                        'receiving_hand'      => trim($_POST['receiving_hand'] ?? ''),
                        'status'              => in_array($_POST['status'] ?? '', ['pending','ready','delivered','cancelled'], true) ? $_POST['status'] : 'pending',
                        'notes'               => trim($_POST['notes'] ?? ''),
                    ];
                    $orderId = saveOrder($orderData, $measurements);
                    $db->commit();
                } catch (Exception $inner) {
                    $db->rollBack();
                    throw $inner;
                }

                flash('order_ok', 'Order saved successfully!');
                header("Location: ?page=order_edit&id=$orderId&saved=1");
                exit;

            } catch (RuntimeException|\InvalidArgumentException $e) {
                $error = $e->getMessage();
            } catch (PDOException $e) {
                $error = 'A database error occurred. Please try again.';
            }
            // Fall through to show order form with error.
            requireLogin();
            $orderId = (int)($_POST['order_id'] ?? 0);
            $order   = $orderId ? getOrder($orderId) : null;

            $postMeasurements = [
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
                'main_full'      => $_POST['m_main_full'] ?? null,
                'main_half'      => $_POST['m_main_half'] ?? null,
                'kaf'            => $_POST['m_kaf'] ?? null,
                'gera_chorus'    => $_POST['m_gera_chorus'] ?? null,
                'size_note'        => $_POST['m_size_note'] ?? null,
                'shalwar_style'    => $_POST['m_shalwar_style'] ?? null,
                'gera_oval'        => $_POST['m_gera_oval'] ?? null,
                'harmol'           => $_POST['m_harmol'] ?? null,
                'chak_patti_button'=> $_POST['m_chak_patti_button'] ?? null,
                'detail'           => $_POST['m_detail'] ?? null,
            ];

            if ($order) {
                $order['order_date']         = $_POST['order_date']   ?? $order['order_date'];
                $order['delivery_date']      = $_POST['delivery_date']?? $order['delivery_date'];
                $order['suit_type']          = $_POST['suit_type']    ?? $order['suit_type'];
                $order['stitch_type']        = $_POST['stitch_type']  ?? $order['stitch_type'];
                $order['cloth_source']       = $_POST['cloth_source'] ?? $order['cloth_source'];
                $order['stock_item_id']      = (int)($_POST['stock_item_id'] ?? 0) ?: $order['stock_item_id'];
                $order['meters_used']        = isset($_POST['meters_used']) ? (float)$_POST['meters_used'] : $order['meters_used'];
                $order['brand_name']         = $_POST['brand_name']   ?? $order['brand_name'];
                $order['stitching_type_id']  = $_POST['stitching_type_id'] ?? $order['stitching_type_id'];
                $order['button_type_id']     = $_POST['button_type_id'] ?? $order['button_type_id'];
                $order['button_price']       = $_POST['button_price'] ?? $order['button_price'];
                $order['pancha_type_id']     = $_POST['pancha_type_id'] ?? $order['pancha_type_id'];
                $order['pancha_price']       = $_POST['pancha_price'] ?? $order['pancha_price'];
                $order['total_price']        = (float)($_POST['total_price']  ?? $order['total_price']);
                $order['advance_paid']       = (float)($_POST['advance_paid'] ?? $order['advance_paid']);
                $order['remaining']          = (float)($_POST['remaining']    ?? $order['remaining']);
                $order['status']             = in_array($_POST['status'] ?? '', ['pending','ready','delivered','cancelled'], true) ? $_POST['status'] : $order['status'];
                $order['notes']              = trim($_POST['notes']   ?? $order['notes']);
                $order['measurements']       = array_merge($order['measurements'] ?? [], $postMeasurements);
            } elseif (!$order) {
                $order = [
                    'id'                  => null,
                    'customer_id'         => (int)($_POST['customer_id'] ?? 0),
                    'order_date'          => $_POST['order_date'] ?? date('Y-m-d'),
                    'delivery_date'       => $_POST['delivery_date'] ?? '',
                    'suit_type'           => $_POST['suit_type'] ?? '',
                    'stitch_type'         => $_POST['stitch_type'] ?? '',
                    'cloth_source'        => $_POST['cloth_source'] ?? 'self',
                    'stock_item_id'       => (int)($_POST['stock_item_id'] ?? 0) ?: null,
                    'meters_used'         => (float)($_POST['meters_used'] ?? 0) ?: null,
                    'brand_name'          => $_POST['brand_name'] ?? '',
                    'stitching_price'     => (float)($_POST['stitching_price'] ?? getSetting('default_stitching_price','2300')),
                    'stitching_type_id'   => $_POST['stitching_type_id'] ?? '',
                    'button_type_id'      => $_POST['button_type_id'] ?? '',
                    'button_price'        => (float)($_POST['button_price'] ?? 0),
                    'pancha_type_id'      => $_POST['pancha_type_id'] ?? '',
                    'pancha_price'        => (float)($_POST['pancha_price'] ?? 0),
                    'discount'            => (float)($_POST['discount'] ?? 0),
                    'total_price'         => (float)($_POST['total_price'] ?? 0),
                    'advance_paid'        => (float)($_POST['advance_paid'] ?? 0),
                    'remaining'           => (float)($_POST['remaining'] ?? 0),
                    'payment_method'      => $_POST['payment_method'] ?? 'Cash',
                    'receiving_hand'      => trim($_POST['receiving_hand'] ?? ''),
                    'status'              => in_array($_POST['status'] ?? '', ['pending','ready','delivered','cancelled'], true) ? $_POST['status'] : 'pending',
                    'notes'               => trim($_POST['notes'] ?? ''),
                    'customer_name'       => (function(int $cid): string {
                        if (!$cid) return '';
                        $db = getDB();
                        $s  = $db->prepare("SELECT name FROM customers WHERE id=?");
                        $s->execute([$cid]);
                        return (string)($s->fetchColumn() ?? '');
                    })((int)($_POST['customer_id'] ?? 0)),
                    '_post_new_name'  => trim($_POST['new_name']  ?? ''),
                    '_post_new_phone' => trim($_POST['new_phone'] ?? ''),
                    '_post_new_addr'  => trim($_POST['new_address'] ?? ''),
                    'measurements'    => $postMeasurements,
                ];
            }
            require __DIR__ . '/includes/header.php';
            require __DIR__ . '/views/order_form.php';
            require __DIR__ . '/includes/footer.php';
            exit;

        case 'search_customer':
            requireLogin();
            header('Content-Type: application/json');
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 2) { echo '[]'; exit; }
            $rows = searchCustomers($q);
            $slim = array_map(fn($c) => [
                'id'      => $c['id'],
                'name'    => $c['name'],
                'phone'   => $c['phone'],
                'address' => $c['address'],
            ], $rows);
            echo json_encode($slim);
            exit;

        case 'get_customers_sidebar':
            requireLogin();
            header('Content-Type: application/json');
            $q = trim($_GET['q'] ?? '');
            $rows = getCustomersWithBalance($q);
            echo json_encode(array_map(fn($c) => [
                'id'          => (int)$c['id'],
                'name'        => $c['name'],
                'phone'       => $c['phone'] ?? '',
                'address'     => $c['address'] ?? '',
                'outstanding' => (float)$c['total_outstanding'],
                'cleared'     => (float)$c['total_cleared'],
                'order_count' => (int)$c['order_count'],
                'has_arrears' => (int)$c['has_arrears'],
            ], $rows));
            exit;

        case 'save_customer_ajax':
            requireLogin();
            header('Content-Type: application/json');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
            // CSRF check for AJAX (return JSON error instead of dying)
            $sessionTok = $_SESSION['csrf_token'] ?? '';
            $postedTok  = $_POST['csrf'] ?? '';
            if (!$sessionTok || !$postedTok || !hash_equals($sessionTok, $postedTok)) {
                echo json_encode(['success'=>false,'error'=>'Invalid security token. Please refresh and try again.']);
                exit;
            }
            try {
                $cid = saveCustomer([
                    'id'      => (int)($_POST['customer_id'] ?? 0) ?: null,
                    'name'    => trim($_POST['name'] ?? ''),
                    'phone'   => trim($_POST['phone'] ?? ''),
                    'address' => trim($_POST['address'] ?? ''),
                    'notes'   => '',
                ]);
                $c = getCustomer($cid);
                // Get outstanding from DB
                $db2 = getDB();
                $outs = $db2->prepare("SELECT COALESCE(SUM(CASE WHEN COALESCE(dues_cleared,0)=0 THEN COALESCE(remaining,0) ELSE 0 END),0) AS outs, COUNT(*) AS cnt FROM orders WHERE customer_id=?");
                $outs->execute([$cid]);
                $outRow = $outs->fetch();
                echo json_encode([
                    'success'     => true,
                    'id'          => $cid,
                    'name'        => $c['name'],
                    'phone'       => $c['phone'] ?? '',
                    'address'     => $c['address'] ?? '',
                    'outstanding' => (float)($outRow['outs'] ?? 0),
                    'order_count' => (int)($outRow['cnt'] ?? 0),
                ]);
            } catch (\InvalidArgumentException $e) {
                echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
            } catch (PDOException $e) {
                echo json_encode(['success'=>false,'error'=>'Database error. Please try again.']);
            }
            exit;

        case 'delete_customer_ajax':
            requireAdmin();
            header('Content-Type: application/json');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'POST required']); exit; }
            // CSRF check for AJAX
            $sessionTok = $_SESSION['csrf_token'] ?? '';
            $postedTok  = $_POST['csrf'] ?? '';
            if (!$sessionTok || !$postedTok || !hash_equals($sessionTok, $postedTok)) {
                echo json_encode(['success'=>false,'error'=>'Invalid security token. Please refresh and try again.']);
                exit;
            }
            $cid = (int)($_POST['customer_id'] ?? 0);
            if (!$cid) { echo json_encode(['success'=>false,'error'=>'Invalid customer ID']); exit; }
            try {
                deleteCustomer($cid);
                echo json_encode(['success'=>true]);
            } catch (Exception $e) {
                echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
            }
            exit;

        case 'save_customer_only':
            requireLogin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=order_new'); exit; }
            verifyCsrf();
            $cName    = trim($_POST['name'] ?? '');
            $cPhone   = trim($_POST['phone'] ?? '');
            $cAddress = trim($_POST['address'] ?? '');
            $editId   = (int)($_POST['customer_id'] ?? 0) ?: null;
            if ($cName !== '') {
                try {
                    $cid = saveCustomer(['id'=>$editId, 'name'=>$cName, 'phone'=>$cPhone, 'address'=>$cAddress, 'notes'=>'']);
                    flash('customer_ok', 'Customer "' . $cName . '" saved. Select them from the list to create an order.');
                    header("Location: ?page=order_new&prefill_customer=$cid");
                } catch (\InvalidArgumentException $e) {
                    flash('customer_err', $e->getMessage());
                    header('Location: ?page=order_new');
                } catch (PDOException $e) {
                    flash('customer_err', 'Database error saving customer.');
                    header('Location: ?page=order_new');
                }
            } else {
                flash('customer_err', 'Customer name is required.');
                header('Location: ?page=order_new');
            }
            exit;

        case 'clear_dues':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=customers'); exit; }
            verifyCsrf();
            $orderId = (int)($_POST['order_id'] ?? 0);
            $backUrl = trim($_POST['back_url'] ?? '?page=customers');
            if ($orderId) {
                try {
                    clearOrderDues($orderId);
                    flash('customer_ok', 'Dues cleared for order. Payment marked as received.');
                } catch (RuntimeException $e) {
                    flash('customer_err', $e->getMessage());
                } catch (PDOException $e) {
                    flash('customer_err', 'Database error. Please try again.');
                }
            }
            $customerId = (int)($_POST['customer_id'] ?? 0);
            header('Location: ' . ($customerId ? "?page=customer_orders&customer_id=$customerId" : '?page=customers'));
            exit;

        case 'export_stock_csv':
            requireAdmin();
            exportStockCsv();
            exit;

        case 'import_stock_csv':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=stock'); exit; }
            verifyCsrf();
            if (empty($_FILES['csv_file']['tmp_name'])) {
                flash('stock_err', 'No file uploaded.');
                header('Location: ?page=stock'); exit;
            }
            $tmpPath = $_FILES['csv_file']['tmp_name'];
            $handle  = fopen($tmpPath, 'r');
            if (!$handle) { flash('stock_err', 'Could not read the uploaded file.'); header('Location: ?page=stock'); exit; }
            $imported = 0; $skipped = 0; $firstRow = true;
            while (($row = fgetcsv($handle)) !== false) {
                if ($firstRow) { $firstRow = false; continue; } // skip header
                if (count($row) < 5) { $skipped++; continue; }
                // Columns: 0=ID(skip), 1=Brand Name, 2=Cloth Type, 3=Stock Date, 4=Total Meters, 5=Available Meters, 6=Cost/Meter, 7=Sell/Meter, 8=Has Box, 9=Box Qty, 10=Box Price, 11=Notes
                $brand = trim($row[1] ?? '');
                if (!$brand) { $skipped++; continue; }
                try {
                    saveStockItem([
                        'id'               => null,
                        'brand_name'       => $brand,
                        'cloth_type'       => trim($row[2] ?? ''),
                        'stock_date'       => trim($row[3] ?? '') ?: date('Y-m-d'),
                        'total_meters'     => (float)($row[4] ?? 0),
                        'available_meters' => (float)($row[5] ?? $row[4] ?? 0),
                        'cost_per_meter'   => (float)($row[6] ?? 0),
                        'sell_per_meter'   => ($row[7] ?? '') !== '' ? (float)$row[7] : null,
                        'sell_mode'        => in_array(strtolower(trim($row[8] ?? '')), ['meter','box','both'], true) ? strtolower(trim($row[8])) : 'meter',
                        'box_quantity'     => (float)($row[9] ?? 0),
                        'box_price'        => (float)($row[10] ?? 0),
                        'notes'            => trim($row[11] ?? ''),
                    ]);
                    $imported++;
                } catch (Exception $e) { $skipped++; }
            }
            fclose($handle);
            flash('stock_ok', "CSV import complete: $imported items imported, $skipped skipped.");
            header('Location: ?page=stock');
            exit;

        case 'save_stock':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=stock'); exit; }
            verifyCsrf();
            try {
                $data = [
                    'id'              => (int)($_POST['stock_id'] ?? 0) ?: null,
                    'brand_name'      => trim($_POST['brand_name'] ?? ''),
                    'cloth_type'      => trim($_POST['cloth_type'] ?? ''),
                    'stock_date'      => trim($_POST['stock_date'] ?? '') ?: date('Y-m-d'),
                    'total_meters'    => (float)($_POST['total_meters'] ?? 0),
                    'available_meters'=> (float)($_POST['avail_meters'] ?? $_POST['total_meters'] ?? 0),
                    'cost_per_meter'  => (float)($_POST['cost_meter'] ?? 0),
                    'sell_per_meter'  => ($_POST['sell_meter'] ?? '') !== '' ? (float)$_POST['sell_meter'] : null,
                    'sell_mode'       => in_array($_POST['sell_mode'] ?? '', ['meter','box','both'], true) ? $_POST['sell_mode'] : 'meter',
                    'box_quantity'    => (float)($_POST['box_quantity'] ?? 0),
                    'box_price'       => (float)($_POST['box_price'] ?? 0),
                    'notes'           => trim($_POST['stock_notes'] ?? ''),
                ];
                if (!$data['brand_name']) throw new RuntimeException('Brand name is required.');
                if ($data['total_meters'] < 0) throw new RuntimeException('Total meters cannot be negative.');
                if ($data['available_meters'] < 0) throw new RuntimeException('Available meters cannot be negative.');
                if ($data['cost_per_meter'] < 0) throw new RuntimeException('Cost per meter cannot be negative.');
                if ($data['sell_per_meter'] !== null && $data['sell_per_meter'] < 0) throw new RuntimeException('Selling price per meter cannot be negative.');
                if ($data['available_meters'] > $data['total_meters']) {
                    throw new RuntimeException('Available meters cannot exceed total meters.');
                }
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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=stock'); exit; }
            verifyCsrf();
            $id = (int)($_POST['id'] ?? 0);
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

        case 'save_stitching_type':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=settings'); exit; }
            verifyCsrf();
            try {
                saveStitchingType([
                    'id'    => (int)($_POST['st_id'] ?? 0) ?: null,
                    'name'  => trim($_POST['st_name'] ?? ''),
                    'price' => (float)($_POST['st_price'] ?? 0),
                ]);
                flash('settings_ok', 'Stitching type saved.');
            } catch (\InvalidArgumentException $e) {
                flash('settings_err', $e->getMessage());
            } catch (PDOException $e) {
                flash('settings_err', 'Database error. Please try again.');
            }
            header('Location: ?page=settings');
            exit;

        case 'delete_stitching_type':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=settings'); exit; }
            verifyCsrf();
            $id = (int)($_POST['st_id'] ?? 0);
            if ($id) {
                deleteStitchingType($id);
                flash('settings_ok', 'Stitching type deleted.');
            }
            header('Location: ?page=settings');
            exit;

        case 'save_button_type':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=stock'); exit; }
            verifyCsrf();
            try {
                saveButtonType([
                    'id'    => (int)($_POST['bt_id'] ?? 0) ?: null,
                    'name'  => trim($_POST['bt_name'] ?? ''),
                    'price' => (float)($_POST['bt_price'] ?? 0),
                ]);
                flash('stock_ok', 'Button type saved.');
            } catch (\InvalidArgumentException $e) {
                flash('stock_err', $e->getMessage());
            } catch (PDOException $e) {
                flash('stock_err', 'Database error. Please try again.');
            }
            header('Location: ?page=stock');
            exit;

        case 'delete_button_type':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=stock'); exit; }
            verifyCsrf();
            $id = (int)($_POST['bt_id'] ?? 0);
            if ($id) {
                deleteButtonType($id);
                flash('stock_ok', 'Button type deleted.');
            }
            header('Location: ?page=stock');
            exit;

        case 'save_pancha_type':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=settings'); exit; }
            verifyCsrf();
            try {
                savePanchaType([
                    'id'    => (int)($_POST['pt_id'] ?? 0) ?: null,
                    'name'  => trim($_POST['pt_name'] ?? ''),
                    'price' => (float)($_POST['pt_price'] ?? 0),
                ]);
                flash('settings_ok', 'Pancha type saved.');
            } catch (\InvalidArgumentException $e) {
                flash('settings_err', $e->getMessage());
            } catch (PDOException $e) {
                flash('settings_err', 'Database error. Please try again.');
            }
            header('Location: ?page=settings');
            exit;

        case 'delete_pancha_type':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=settings'); exit; }
            verifyCsrf();
            $id = (int)($_POST['pt_id'] ?? 0);
            if ($id) {
                deletePanchaType($id);
                flash('settings_ok', 'Pancha type deleted.');
            }
            header('Location: ?page=settings');
            exit;

        case 'delete_order':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=orders'); exit; }
            verifyCsrf();
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $db = getDB();
                $db->beginTransaction();
                try {
                    $oStmt = $db->prepare("SELECT cloth_source, stock_item_id, meters_used FROM orders WHERE id=?");
                    $oStmt->execute([$id]);
                    $oRow = $oStmt->fetch();
                    if ($oRow && $oRow['cloth_source'] === 'shop' && $oRow['stock_item_id'] && $oRow['meters_used'] > 0) {
                        $db->prepare("UPDATE stock_items SET available_meters = available_meters + ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                           ->execute([$oRow['meters_used'], $oRow['stock_item_id']]);
                        $db->prepare("INSERT INTO stock_transactions (stock_item_id, order_id, transaction_type, meters, notes) VALUES (?,?,?,?,?)")
                           ->execute([$oRow['stock_item_id'], null, 'restore', $oRow['meters_used'], "Order #$id deleted — meters restored"]);
                    }
                    $db->prepare("DELETE FROM stock_transactions WHERE order_id=?")->execute([$id]);
                    $db->prepare("DELETE FROM measurements WHERE order_id=?")->execute([$id]);
                    $db->prepare("DELETE FROM orders WHERE id=?")->execute([$id]);
                    $db->commit();
                    flash('order_ok', 'Order deleted.');
                } catch (Exception $e) {
                    $db->rollBack();
                    flash('order_err', 'Could not delete order. Please try again.');
                }
            }
            header('Location: ?page=orders');
            exit;

        case 'delete_all_customers':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=customers'); exit; }
            verifyCsrf();
            if (trim($_POST['confirm_word'] ?? '') === 'OK') {
                try {
                    deleteAllCustomers();
                    flash('customer_ok', 'All customer records, orders and measurements have been deleted.');
                } catch (Exception $e) {
                    flash('customer_err', 'Error: ' . $e->getMessage());
                }
            } else {
                flash('customer_err', 'Confirmation word did not match. No records deleted.');
            }
            header('Location: ?page=customers');
            exit;

        case 'delete_all_stocks':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=stock'); exit; }
            verifyCsrf();
            if (trim($_POST['confirm_word'] ?? '') === 'OK') {
                try {
                    deleteAllStocks();
                    flash('stock_ok', 'All stock items and transactions have been deleted.');
                } catch (Exception $e) {
                    flash('stock_err', 'Error: ' . $e->getMessage());
                }
            } else {
                flash('stock_err', 'Confirmation word did not match. No stocks deleted.');
            }
            header('Location: ?page=stock');
            exit;

        case 'backup_db':
            requireAdmin();
            $dbPath = __DIR__ . '/data/larkana.db';
            if (!file_exists($dbPath)) { echo 'Database not found.'; exit; }
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="larkana_backup_' . date('Ymd_His') . '.db"');
            header('Content-Length: ' . filesize($dbPath));
            readfile($dbPath);
            exit;

        case 'save_settings':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=settings'); exit; }
            verifyCsrf();
            $keys = ['default_stitching_price', 'shop_name', 'shop_phone', 'shop_address'];
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    setSetting($k, trim($_POST[$k]));
                }
            }
            flash('settings_ok', 'Settings saved successfully.');
            header('Location: ?page=settings');
            exit;

        case 'add_worker':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=workers'); exit; }
            verifyCsrf();
            $err = handleAddWorker();
            if ($err) flash('worker_err', $err);
            else flash('worker_ok', 'Worker added successfully.');
            header('Location: ?page=workers');
            exit;

        case 'delete_worker':
            requireAdmin();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?page=workers'); exit; }
            verifyCsrf();
            $id = (int)($_POST['id'] ?? 0);
            if ($id && $id !== (int)$_SESSION['user_id']) {
                try {
                    $db = getDB();
                    $stmt = $db->prepare("DELETE FROM users WHERE id=? AND role!='admin'");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        flash('worker_ok', 'Worker deleted.');
                    } else {
                        flash('worker_err', 'Worker not found or cannot be deleted.');
                    }
                } catch (PDOException $e) {
                    flash('worker_err', 'Cannot delete this worker — they have existing orders on record. Remove their orders first or keep the account.');
                }
            } else {
                flash('worker_err', 'Cannot delete this account.');
            }
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
        header($_SESSION['role'] === 'admin' ? 'Location: ?page=dashboard' : 'Location: ?page=orders');
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
    'settings'        => isAdmin() ? 'settings.php' : 'dashboard.php',
    default           => 'dashboard.php',
};

if (file_exists($viewFile)) {
    require $viewFile;
} else {
    echo '<div class="alert alert-error">Page not found.</div>';
}

require __DIR__ . '/includes/footer.php';
