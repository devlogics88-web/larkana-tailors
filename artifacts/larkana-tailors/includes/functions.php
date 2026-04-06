<?php
function h(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

function formatMoney(mixed $v): string {
    return 'Rs. ' . number_format((float)$v, 0);
}

function formatDate(?string $d): string {
    if (!$d) return '-';
    return date('d-M-Y', strtotime($d));
}

function searchCustomers(string $query): array {
    $db = getDB();
    $q = '%' . $query . '%';
    $stmt = $db->prepare("SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY name LIMIT 20");
    $stmt->execute([$q, $q]);
    return $stmt->fetchAll();
}

function getCustomer(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function saveCustomer(array $data): int {
    $db = getDB();
    if (!empty($data['id'])) {
        $db->prepare("UPDATE customers SET name=?, phone=?, address=?, notes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
           ->execute([$data['name'], $data['phone'], $data['address'], $data['notes'], $data['id']]);
        return (int)$data['id'];
    }
    $db->prepare("INSERT INTO customers (name, phone, address, notes) VALUES (?,?,?,?)")
       ->execute([$data['name'], $data['phone'], $data['address'], $data['notes']]);
    return (int)$db->lastInsertId();
}

function getOrder(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id
        WHERE o.id = ?
    ");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order) return null;
    $stmt2 = $db->prepare("SELECT * FROM measurements WHERE order_id = ?");
    $stmt2->execute([$id]);
    $order['measurements'] = $stmt2->fetch() ?: [];
    return $order;
}

function saveOrder(array $data, array $measurements): int {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $isEdit = !empty($data['id']);

    $data['remaining'] = ($data['total_price'] ?? 0) - ($data['advance_paid'] ?? 0);

    // For edits, fetch the original stock usage before making any changes.
    $oldStockItemId = null;
    $oldMetersUsed  = 0.0;
    $oldClothSource = 'self';
    if ($isEdit) {
        $oldStmt = $db->prepare("SELECT cloth_source, stock_item_id, meters_used FROM orders WHERE id=?");
        $oldStmt->execute([$data['id']]);
        $old = $oldStmt->fetch();
        if ($old) {
            $oldClothSource = $old['cloth_source'];
            $oldStockItemId = $old['stock_item_id'] ? (int)$old['stock_item_id'] : null;
            $oldMetersUsed  = (float)($old['meters_used'] ?? 0);
        }
    }

    if ($isEdit) {
        $db->prepare("
            UPDATE orders SET customer_id=?, order_date=?, delivery_date=?, suit_type=?, stitch_type=?,
            cloth_source=?, stock_item_id=?, meters_used=?, brand_name=?, total_price=?, advance_paid=?,
            remaining=?, status=?, notes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?
        ")->execute([
            $data['customer_id'], $data['order_date'], $data['delivery_date'], $data['suit_type'],
            $data['stitch_type'], $data['cloth_source'], $data['stock_item_id'] ?: null,
            $data['meters_used'] ?: null, $data['brand_name'], $data['total_price'],
            $data['advance_paid'], $data['remaining'], $data['status'], $data['notes'], $data['id']
        ]);
        $orderId = (int)$data['id'];
    } else {
        $data['order_no'] = generateOrderNo();
        $db->prepare("
            INSERT INTO orders (order_no, customer_id, order_date, delivery_date, suit_type, stitch_type,
            cloth_source, stock_item_id, meters_used, brand_name, total_price, advance_paid, remaining,
            status, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $data['order_no'], $data['customer_id'], $data['order_date'], $data['delivery_date'],
            $data['suit_type'], $data['stitch_type'], $data['cloth_source'],
            $data['stock_item_id'] ?: null, $data['meters_used'] ?: null, $data['brand_name'],
            $data['total_price'], $data['advance_paid'], $data['remaining'],
            $data['status'], $data['notes'], $userId
        ]);
        $orderId = (int)$db->lastInsertId();
    }

    // Upsert measurements.
    $mFields = ['shirt_length','sleeve','shoulder','collar','chest','waist','hip',
                'shalwar_length','shalwar_bottom','shalwar_waist','cuff',
                'trouser_length','trouser_bottom','front_style','detail'];
    $mVals = [];
    foreach ($mFields as $f) $mVals[$f] = $measurements[$f] ?? null;

    $checkM = $db->prepare("SELECT id FROM measurements WHERE order_id=?");
    $checkM->execute([$orderId]);
    $existingM = $checkM->fetch();

    if ($existingM) {
        $sets = implode(', ', array_map(fn($f) => "$f=?", $mFields));
        $db->prepare("UPDATE measurements SET $sets WHERE order_id=?")
           ->execute([...array_values($mVals), $orderId]);
    } else {
        $cols = implode(',', $mFields);
        $placeholders = implode(',', array_fill(0, count($mFields), '?'));
        $db->prepare("INSERT INTO measurements (order_id,$cols) VALUES (?,$placeholders)")
           ->execute([$orderId, ...array_values($mVals)]);
    }

    // Stock reconciliation.
    // For new orders: simply deduct the meters from the chosen stock item.
    // For edits: restore old stock usage first, then apply the new usage, so
    //            net inventory remains correct regardless of what changed.
    $newStockItemId = $data['cloth_source'] === 'shop' ? (int)($data['stock_item_id'] ?? 0) : 0;
    $newMetersUsed  = $data['cloth_source'] === 'shop' ? (float)($data['meters_used'] ?? 0) : 0.0;

    if ($isEdit) {
        // Restore old stock if there was any shop usage.
        if ($oldClothSource === 'shop' && $oldStockItemId && $oldMetersUsed > 0) {
            $db->prepare("UPDATE stock_items SET available_meters = available_meters + ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
               ->execute([$oldMetersUsed, $oldStockItemId]);
        }
        // Apply new stock deduction if shop cloth selected.
        if ($newStockItemId && $newMetersUsed > 0) {
            $db->prepare("UPDATE stock_items SET available_meters = available_meters - ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
               ->execute([$newMetersUsed, $newStockItemId]);
        }
    } else {
        // New order: deduct and record transaction.
        if ($newStockItemId && $newMetersUsed > 0) {
            $db->prepare("UPDATE stock_items SET available_meters = available_meters - ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
               ->execute([$newMetersUsed, $newStockItemId]);
            $db->prepare("INSERT INTO stock_transactions (stock_item_id, order_id, transaction_type, meters, notes) VALUES (?,?,'debit',?,?)")
               ->execute([$newStockItemId, $orderId, $newMetersUsed, 'Order ' . ($data['order_no'] ?? $orderId)]);
        }
    }

    return $orderId;
}

function getOrders(array $filters = []): array {
    $db = getDB();
    $where = ['1=1'];
    $params = [];
    if (!empty($filters['status'])) {
        $where[] = 'o.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(c.name LIKE ? OR c.phone LIKE ? OR o.order_no LIKE ?)';
        $q = '%' . $filters['search'] . '%';
        $params = array_merge($params, [$q, $q, $q]);
    }
    $whereStr = implode(' AND ', $where);
    $stmt = $db->prepare("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone
        FROM orders o LEFT JOIN customers c ON c.id = o.customer_id
        WHERE $whereStr ORDER BY o.created_at DESC LIMIT 200
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getStockItems(): array {
    $db = getDB();
    return $db->query("SELECT * FROM stock_items ORDER BY brand_name")->fetchAll();
}

function saveStockItem(array $data): void {
    $db = getDB();
    if (!empty($data['id'])) {
        $db->prepare("UPDATE stock_items SET brand_name=?, cloth_type=?, total_meters=?, available_meters=?, cost_per_meter=?, sell_per_meter=?, notes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
           ->execute([$data['brand_name'], $data['cloth_type'], $data['total_meters'], $data['available_meters'], $data['cost_per_meter'], $data['sell_per_meter'] ?: null, $data['notes'], $data['id']]);
    } else {
        $db->prepare("INSERT INTO stock_items (brand_name, cloth_type, total_meters, available_meters, cost_per_meter, sell_per_meter, notes) VALUES (?,?,?,?,?,?,?)")
           ->execute([$data['brand_name'], $data['cloth_type'], $data['total_meters'], $data['total_meters'], $data['cost_per_meter'], $data['sell_per_meter'] ?: null, $data['notes']]);
    }
}

function deleteStockItem(int $id): void {
    $db = getDB();
    $db->prepare("DELETE FROM stock_items WHERE id=?")->execute([$id]);
}

function getDashboardStats(): array {
    $db = getDB();
    $stats = [];
    $stats['total_orders'] = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['pending_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $stats['ready_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE status='ready'")->fetchColumn();
    $stats['delivered_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn();
    $stats['total_sales'] = $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders")->fetchColumn();
    $stats['total_advance'] = $db->query("SELECT COALESCE(SUM(advance_paid),0) FROM orders")->fetchColumn();
    $stats['total_remaining'] = $db->query("SELECT COALESCE(SUM(remaining),0) FROM orders")->fetchColumn();
    $stats['recent_orders'] = $db->query("
        SELECT o.*, c.name as customer_name FROM orders o
        LEFT JOIN customers c ON c.id=o.customer_id
        ORDER BY o.created_at DESC LIMIT 10
    ")->fetchAll();
    return $stats;
}

function getReportData(): array {
    $db = getDB();
    $data = [];
    $data['total_orders']    = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $data['total_sales']     = $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders")->fetchColumn();
    $data['total_advance']   = $db->query("SELECT COALESCE(SUM(advance_paid),0) FROM orders")->fetchColumn();
    $data['total_remaining'] = $db->query("SELECT COALESCE(SUM(remaining),0) FROM orders")->fetchColumn();

    $data['stock_cost'] = $db->query("
        SELECT COALESCE(SUM(st.meters * si.cost_per_meter),0)
        FROM stock_transactions st
        JOIN stock_items si ON si.id=st.stock_item_id
        WHERE st.transaction_type='debit'
    ")->fetchColumn();

    $shopOrders = $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE cloth_source='shop'")->fetchColumn();
    $data['estimated_profit'] = $shopOrders - $data['stock_cost'];

    $data['by_status'] = $db->query("
        SELECT status, COUNT(*) as cnt, COALESCE(SUM(total_price),0) as total
        FROM orders GROUP BY status
    ")->fetchAll();

    $data['monthly'] = $db->query("
        SELECT strftime('%Y-%m', order_date) as month,
               COUNT(*) as orders,
               COALESCE(SUM(total_price),0) as sales,
               COALESCE(SUM(advance_paid),0) as advance
        FROM orders GROUP BY month ORDER BY month DESC LIMIT 12
    ")->fetchAll();

    $data['workers'] = $db->query("SELECT * FROM users WHERE role='worker' ORDER BY full_name")->fetchAll();

    return $data;
}
