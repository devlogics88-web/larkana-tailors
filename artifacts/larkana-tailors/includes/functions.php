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

function getSetting(string $key, string $default = ''): string {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key=?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (string)$val : $default;
}

function setSetting(string $key, string $value): void {
    $db = getDB();
    $db->prepare("INSERT INTO settings (key, value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value")
       ->execute([$key, $value]);
}

function formatDate(?string $d): string {
    if (!$d) return '-';
    return date('d-M-Y', strtotime($d));
}

function searchCustomers(string $query): array {
    $db   = getDB();
    $like = '%' . $query . '%';
    $stmt = $db->prepare("
        SELECT c.*, COALESCE(oc.cnt, 0) AS order_count
        FROM customers c
        LEFT JOIN (SELECT customer_id, COUNT(*) AS cnt FROM orders GROUP BY customer_id) oc
            ON oc.customer_id = c.id
        WHERE c.name LIKE ? OR c.phone LIKE ?
        ORDER BY c.name LIMIT 20
    ");
    $stmt->execute([$like, $like]);
    return $stmt->fetchAll();
}

function getAllCustomers(int $limit = 200): array {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT c.*, COALESCE(oc.cnt, 0) AS order_count
        FROM customers c
        LEFT JOIN (SELECT customer_id, COUNT(*) AS cnt FROM orders GROUP BY customer_id) oc
            ON oc.customer_id = c.id
        ORDER BY c.id DESC LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getCustomer(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function saveCustomer(array $data): int {
    $name = trim($data['name'] ?? '');
    if ($name === '') {
        throw new \InvalidArgumentException('Customer name is required.');
    }
    $db = getDB();
    if (!empty($data['id'])) {
        $db->prepare("UPDATE customers SET name=?, phone=?, address=?, notes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
           ->execute([$name, $data['phone'], $data['address'], $data['notes'], $data['id']]);
        return (int)$data['id'];
    }
    $db->prepare("INSERT INTO customers (name, phone, address, notes) VALUES (?,?,?,?)")
       ->execute([$name, $data['phone'], $data['address'], $data['notes']]);
    return (int)$db->lastInsertId();
}

function getOrder(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
               c.id as customer_id_fk,
               si.sell_per_meter as stock_sell_per_meter, si.brand_name as stock_brand_name
        FROM orders o
        LEFT JOIN customers c ON c.id = o.customer_id
        LEFT JOIN stock_items si ON si.id = o.stock_item_id
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

    $totalPrice  = (float)($data['total_price'] ?? 0);
    $advancePaid = (float)($data['advance_paid'] ?? 0);
    if ($totalPrice < 0)   throw new RuntimeException('Total price cannot be negative.');
    if ($advancePaid < 0)  throw new RuntimeException('Advance paid cannot be negative.');
    if ($advancePaid > $totalPrice) throw new RuntimeException('Advance paid cannot exceed total price.');
    if (($data['cloth_source'] ?? '') === 'shop') {
        if (empty($data['stock_item_id'])) throw new RuntimeException('Please select a cloth item from stock.');
        if ((float)($data['meters_used'] ?? 0) <= 0) throw new RuntimeException('Meters used must be greater than zero for shop cloth.');
    }

    $data['remaining'] = $totalPrice - $advancePaid;

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

    $newStockItemId = $data['cloth_source'] === 'shop' ? (int)($data['stock_item_id'] ?? 0) : 0;
    $newMetersUsed  = $data['cloth_source'] === 'shop' ? (float)($data['meters_used'] ?? 0) : 0.0;

    if ($newStockItemId && $newMetersUsed > 0) {
        $availStmt = $db->prepare("SELECT available_meters FROM stock_items WHERE id=?");
        $availStmt->execute([$newStockItemId]);
        $availNow = (float)($availStmt->fetchColumn() ?? 0);
        $effectiveAvail = $availNow + (
            $isEdit && $oldClothSource === 'shop' && $oldStockItemId === $newStockItemId
                ? $oldMetersUsed : 0
        );
        if ($effectiveAvail < $newMetersUsed) {
            throw new RuntimeException(
                "Not enough cloth in stock: {$effectiveAvail}m available, {$newMetersUsed}m requested."
            );
        }
    }

    $ownTx = !$db->inTransaction();
    if ($ownTx) $db->beginTransaction();
    try {
        if ($isEdit) {
            $db->prepare("
                UPDATE orders SET customer_id=?, order_date=?, delivery_date=?, suit_type=?, stitch_type=?,
                cloth_source=?, stock_item_id=?, meters_used=?, brand_name=?, stitching_price=?,
                stitching_type_id=?, stitching_type_name=?,
                button_type_id=?, button_type_name=?, button_price=?,
                pancha_type_id=?, pancha_type_name=?, pancha_price=?,
                discount=?, total_price=?, advance_paid=?, remaining=?,
                payment_method=?, receiving_hand=?,
                status=?, notes=?, updated_at=CURRENT_TIMESTAMP
                WHERE id=?
            ")->execute([
                $data['customer_id'], $data['order_date'], $data['delivery_date'], $data['suit_type'],
                $data['stitch_type'], $data['cloth_source'], $data['stock_item_id'] ?: null,
                $data['meters_used'] ?: null, $data['brand_name'], $data['stitching_price'],
                $data['stitching_type_id'] ?: null, $data['stitching_type_name'] ?: null,
                $data['button_type_id'] ?: null, $data['button_type_name'] ?: null, $data['button_price'] ?? 0,
                $data['pancha_type_id'] ?: null, $data['pancha_type_name'] ?: null, $data['pancha_price'] ?? 0,
                $data['discount'] ?? 0,
                $data['total_price'], $data['advance_paid'], $data['remaining'],
                $data['payment_method'] ?? 'Cash', $data['receiving_hand'] ?? null,
                $data['status'], $data['notes'], $data['id']
            ]);
            $orderId = (int)$data['id'];
        } else {
            $insertStmt = $db->prepare("
                INSERT INTO orders (order_no, customer_id, order_date, delivery_date, suit_type, stitch_type,
                cloth_source, stock_item_id, meters_used, brand_name, stitching_price,
                stitching_type_id, stitching_type_name,
                button_type_id, button_type_name, button_price,
                pancha_type_id, pancha_type_name, pancha_price,
                discount, total_price, advance_paid, remaining,
                payment_method, receiving_hand,
                status, notes, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $orderId = null;
            for ($attempt = 0; $attempt < 3; $attempt++) {
                try {
                    $data['order_no'] = generateOrderNo();
                    $insertStmt->execute([
                        $data['order_no'], $data['customer_id'], $data['order_date'], $data['delivery_date'],
                        $data['suit_type'], $data['stitch_type'], $data['cloth_source'],
                        $data['stock_item_id'] ?: null, $data['meters_used'] ?: null, $data['brand_name'],
                        $data['stitching_price'],
                        $data['stitching_type_id'] ?: null, $data['stitching_type_name'] ?: null,
                        $data['button_type_id'] ?: null, $data['button_type_name'] ?: null, $data['button_price'] ?? 0,
                        $data['pancha_type_id'] ?: null, $data['pancha_type_name'] ?: null, $data['pancha_price'] ?? 0,
                        $data['discount'] ?? 0,
                        $data['total_price'], $data['advance_paid'],
                        $data['remaining'],
                        $data['payment_method'] ?? 'Cash', $data['receiving_hand'] ?? null,
                        $data['status'], $data['notes'], $userId
                    ]);
                    $orderId = (int)$db->lastInsertId();
                    break;
                } catch (PDOException $e) {
                    if ($attempt < 2 && str_contains($e->getMessage(), 'UNIQUE')) continue;
                    throw $e;
                }
            }
        }

        // Upsert measurements.
        $mFields = ['shirt_length','sleeve','arm','shoulder','collar','chest','waist','hip',
                    'shalwar_length','shalwar_bottom','shalwar_waist','cuff',
                    'trouser_length','trouser_bottom','front_style',
                    'main_full','main_half','kaf','gera_chorus','size_note','shalwar_style','gera_oval',
                    'harmol','chak_patti_button',
                    'detail'];
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
        if ($isEdit) {
            if ($oldClothSource === 'shop' && $oldStockItemId && $oldMetersUsed > 0) {
                $db->prepare("UPDATE stock_items SET available_meters = available_meters + ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                   ->execute([$oldMetersUsed, $oldStockItemId]);
                $db->prepare("INSERT INTO stock_transactions (stock_item_id, order_id, transaction_type, meters, notes) VALUES (?,?,'credit',?,?)")
                   ->execute([$oldStockItemId, $orderId, $oldMetersUsed, 'Edit reversal for order #' . $orderId]);
            }
            if ($newStockItemId && $newMetersUsed > 0) {
                $db->prepare("UPDATE stock_items SET available_meters = available_meters - ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                   ->execute([$newMetersUsed, $newStockItemId]);
                $db->prepare("INSERT INTO stock_transactions (stock_item_id, order_id, transaction_type, meters, notes) VALUES (?,?,'debit',?,?)")
                   ->execute([$newStockItemId, $orderId, $newMetersUsed, 'Edit update for order #' . $orderId]);
            }
        } else {
            if ($newStockItemId && $newMetersUsed > 0) {
                $db->prepare("UPDATE stock_items SET available_meters = available_meters - ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                   ->execute([$newMetersUsed, $newStockItemId]);
                $db->prepare("INSERT INTO stock_transactions (stock_item_id, order_id, transaction_type, meters, notes) VALUES (?,?,'debit',?,?)")
                   ->execute([$newStockItemId, $orderId, $newMetersUsed, 'Order ' . ($data['order_no'] ?? $orderId)]);
            }
        }

        if ($ownTx) $db->commit();
    } catch (Exception $e) {
        if ($ownTx) $db->rollBack();
        throw $e;
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
           ->execute([$data['brand_name'], $data['cloth_type'], $data['total_meters'], $data['available_meters'], $data['cost_per_meter'], $data['sell_per_meter'] ?: null, $data['notes']]);
    }
}

function deleteStockItem(int $id): void {
    $db = getDB();
    $db->prepare("DELETE FROM stock_items WHERE id=?")->execute([$id]);
}

// ---- Stitching Types ----

function getStitchingTypes(): array {
    $db = getDB();
    return $db->query("SELECT * FROM stitching_types ORDER BY price")->fetchAll();
}

function saveStitchingType(array $data): void {
    $db = getDB();
    $name  = trim($data['name'] ?? '');
    $price = (float)($data['price'] ?? 0);
    if ($name === '') throw new \InvalidArgumentException('Stitching type name is required.');
    if ($price < 0) throw new \InvalidArgumentException('Stitching type price cannot be negative.');
    if (!empty($data['id'])) {
        $db->prepare("UPDATE stitching_types SET name=?, price=? WHERE id=?")
           ->execute([$name, $price, (int)$data['id']]);
    } else {
        $db->prepare("INSERT INTO stitching_types (name, price) VALUES (?,?)")
           ->execute([$name, $price]);
    }
}

function deleteStitchingType(int $id): void {
    $db = getDB();
    $db->prepare("DELETE FROM stitching_types WHERE id=?")->execute([$id]);
}

// ---- Button Types ----

function getButtonTypes(): array {
    $db = getDB();
    return $db->query("SELECT * FROM button_types ORDER BY name")->fetchAll();
}

function saveButtonType(array $data): void {
    $db = getDB();
    $name  = trim($data['name'] ?? '');
    $price = (float)($data['price'] ?? 0);
    if ($name === '') throw new \InvalidArgumentException('Button type name is required.');
    if ($price < 0) throw new \InvalidArgumentException('Button type price cannot be negative.');
    if (!empty($data['id'])) {
        $db->prepare("UPDATE button_types SET name=?, price=? WHERE id=?")
           ->execute([$name, $price, (int)$data['id']]);
    } else {
        $db->prepare("INSERT INTO button_types (name, price) VALUES (?,?)")
           ->execute([$name, $price]);
    }
}

function deleteButtonType(int $id): void {
    $db = getDB();
    $db->prepare("DELETE FROM button_types WHERE id=?")->execute([$id]);
}

// ---- Pancha Types ----

function getPanchaTypes(): array {
    $db = getDB();
    return $db->query("SELECT * FROM pancha_types ORDER BY name")->fetchAll();
}

function savePanchaType(array $data): void {
    $db = getDB();
    $name  = trim($data['name'] ?? '');
    $price = (float)($data['price'] ?? 0);
    if ($name === '') throw new \InvalidArgumentException('Pancha type name is required.');
    if ($price < 0) throw new \InvalidArgumentException('Pancha type price cannot be negative.');
    if (!empty($data['id'])) {
        $db->prepare("UPDATE pancha_types SET name=?, price=? WHERE id=?")
           ->execute([$name, $price, (int)$data['id']]);
    } else {
        $db->prepare("INSERT INTO pancha_types (name, price) VALUES (?,?)")
           ->execute([$name, $price]);
    }
}

function deletePanchaType(int $id): void {
    $db = getDB();
    $db->prepare("DELETE FROM pancha_types WHERE id=?")->execute([$id]);
}

// ---- Dashboard & Reports ----

function getDashboardStats(): array {
    $db = getDB();
    $stats = [];
    $stats['total_orders']    = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['pending_orders']  = $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $stats['ready_orders']    = $db->query("SELECT COUNT(*) FROM orders WHERE status='ready'")->fetchColumn();
    $stats['delivered_orders']= $db->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn();
    $stats['total_sales']     = $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders")->fetchColumn();
    $stats['total_advance']   = $db->query("SELECT COALESCE(SUM(advance_paid),0) FROM orders")->fetchColumn();
    $stats['total_remaining'] = $db->query("SELECT COALESCE(SUM(remaining),0) FROM orders")->fetchColumn();
    $stats['recent_orders']   = $db->query("
        SELECT o.*, c.name as customer_name, c.phone as customer_phone FROM orders o
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
    $data['total_discounts'] = $db->query("SELECT COALESCE(SUM(COALESCE(discount,0)),0) FROM orders")->fetchColumn();
    $data['total_advance']   = $db->query("SELECT COALESCE(SUM(advance_paid),0) FROM orders")->fetchColumn();
    $data['total_remaining']     = $db->query("SELECT COALESCE(SUM(remaining),0) FROM orders WHERE COALESCE(dues_cleared,0)=0")->fetchColumn();
    $data['total_cleared_dues']  = $db->query("SELECT COALESCE(SUM(remaining),0) FROM orders WHERE COALESCE(dues_cleared,0)=1")->fetchColumn();
    $data['total_cash_collected']= (float)$data['total_advance'] + (float)$data['total_cleared_dues'];

    $data['arrears_customers'] = $db->query("
        SELECT c.id, c.name, c.phone,
               SUM(CASE WHEN COALESCE(o.dues_cleared,0)=0 THEN COALESCE(o.remaining,0) ELSE 0 END) AS outstanding
        FROM customers c
        JOIN orders o ON o.customer_id = c.id
        WHERE COALESCE(o.dues_cleared,0)=0 AND COALESCE(o.remaining,0) > 0
        GROUP BY c.id
        ORDER BY outstanding DESC
        LIMIT 10
    ")->fetchAll();

    $data['stock_cost'] = $db->query("
        SELECT COALESCE(SUM(o.meters_used * si.cost_per_meter), 0)
        FROM orders o
        JOIN stock_items si ON si.id = o.stock_item_id
        WHERE o.cloth_source = 'shop' AND o.meters_used IS NOT NULL
    ")->fetchColumn();

    $data['estimated_profit'] = $data['total_sales'] - $data['stock_cost'];

    $data['by_status'] = $db->query("
        SELECT status, COUNT(*) as cnt, COALESCE(SUM(total_price),0) as total
        FROM orders GROUP BY status
    ")->fetchAll();

    $data['monthly'] = $db->query("
        SELECT strftime('%Y-%m', order_date) as month,
               COUNT(*) as orders,
               COALESCE(SUM(total_price),0) as sales,
               COALESCE(SUM(advance_paid),0) as advance,
               COALESCE(SUM(COALESCE(discount,0)),0) as discounts
        FROM orders GROUP BY month ORDER BY month DESC LIMIT 12
    ")->fetchAll();

    $data['workers'] = $db->query("SELECT * FROM users WHERE role='worker' ORDER BY full_name")->fetchAll();

    return $data;
}

function getCustomersWithBalance(string $search = ''): array {
    $db = getDB();
    $params = [];
    $whereClause = '';
    if ($search !== '') {
        $like = '%' . $search . '%';
        $whereClause = 'WHERE c.name LIKE ? OR c.phone LIKE ?';
        $params = [$like, $like];
    }
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.phone, c.address,
               COALESCE(o.order_count, 0) AS order_count,
               COALESCE(o.total_outstanding, 0) AS total_outstanding,
               COALESCE(o.total_cleared, 0) AS total_cleared
        FROM customers c
        LEFT JOIN (
            SELECT customer_id,
                   COUNT(*) AS order_count,
                   SUM(CASE WHEN COALESCE(dues_cleared,0)=0 THEN COALESCE(remaining,0) ELSE 0 END) AS total_outstanding,
                   SUM(CASE WHEN COALESCE(dues_cleared,0)=1 THEN COALESCE(remaining,0) ELSE 0 END) AS total_cleared
            FROM orders
            GROUP BY customer_id
        ) o ON o.customer_id = c.id
        $whereClause
        ORDER BY COALESCE(o.total_outstanding,0) DESC, c.name ASC
        LIMIT 300
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCustomerOrders(int $customerId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, order_no, order_date, delivery_date, suit_type, stitching_type_name,
               total_price, advance_paid, remaining, dues_cleared, cleared_at, status, notes
        FROM orders WHERE customer_id=? ORDER BY created_at DESC
    ");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll();
}

function clearOrderDues(int $orderId): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, remaining, dues_cleared FROM orders WHERE id=?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) throw new RuntimeException('Order not found.');
    if ((int)$order['dues_cleared'] === 1) throw new RuntimeException('Dues already cleared for this order.');
    if ((float)$order['remaining'] <= 0) throw new RuntimeException('No outstanding amount to clear for this order.');
    $userId = $_SESSION['user_id'] ?? 0;
    $db->prepare("UPDATE orders SET dues_cleared=1, cleared_at=CURRENT_TIMESTAMP, cleared_by=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
       ->execute([$userId, $orderId]);
}

function deleteCustomer(int $id): void {
    $db = getDB();
    $db->beginTransaction();
    try {
        $orders = $db->prepare("SELECT id, cloth_source, stock_item_id, meters_used FROM orders WHERE customer_id=?");
        $orders->execute([$id]);
        foreach ($orders->fetchAll() as $o) {
            if ($o['cloth_source'] === 'shop' && $o['stock_item_id'] && (float)($o['meters_used'] ?? 0) > 0) {
                $db->prepare("UPDATE stock_items SET available_meters = available_meters + ?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                   ->execute([$o['meters_used'], $o['stock_item_id']]);
                $db->prepare("DELETE FROM stock_transactions WHERE order_id=?")->execute([$o['id']]);
            }
            $db->prepare("DELETE FROM measurements WHERE order_id=?")->execute([$o['id']]);
        }
        $db->prepare("DELETE FROM orders WHERE customer_id=?")->execute([$id]);
        $db->prepare("DELETE FROM customers WHERE id=?")->execute([$id]);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function deleteAllCustomers(): void {
    $db = getDB();
    $db->beginTransaction();
    try {
        $db->exec("DELETE FROM stock_transactions WHERE order_id IN (SELECT id FROM orders)");
        $db->exec("DELETE FROM measurements");
        $db->exec("DELETE FROM orders");
        $db->exec("DELETE FROM customers");
        // Reset stock available_meters to total_meters since all orders are gone
        $db->exec("UPDATE stock_items SET available_meters = total_meters, updated_at=CURRENT_TIMESTAMP");
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function deleteAllStocks(): void {
    $db = getDB();
    $db->beginTransaction();
    try {
        // Nullify stock references in orders, then delete stock data
        $db->exec("UPDATE orders SET stock_item_id=NULL, cloth_source='self', meters_used=NULL, updated_at=CURRENT_TIMESTAMP WHERE cloth_source='shop'");
        $db->exec("DELETE FROM stock_transactions");
        $db->exec("DELETE FROM stock_items");
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
