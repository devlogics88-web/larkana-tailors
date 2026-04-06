<?php
define('DB_PATH', __DIR__ . '/../data/larkana.db');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
        initSchema($pdo);
    }
    return $pdo;
}

function initSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'worker',
            full_name TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT,
            address TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS stock_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            brand_name TEXT NOT NULL,
            cloth_type TEXT,
            total_meters REAL NOT NULL DEFAULT 0,
            available_meters REAL NOT NULL DEFAULT 0,
            cost_per_meter REAL NOT NULL DEFAULT 0,
            sell_per_meter REAL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_no TEXT UNIQUE NOT NULL,
            customer_id INTEGER NOT NULL,
            order_date TEXT NOT NULL,
            delivery_date TEXT,
            suit_type TEXT,
            stitch_type TEXT,
            cloth_source TEXT DEFAULT 'self',
            stock_item_id INTEGER,
            meters_used REAL,
            brand_name TEXT,
            total_price REAL DEFAULT 0,
            advance_paid REAL DEFAULT 0,
            remaining REAL DEFAULT 0,
            status TEXT DEFAULT 'pending',
            notes TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (stock_item_id) REFERENCES stock_items(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS measurements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER UNIQUE NOT NULL,
            shirt_length REAL,
            sleeve REAL,
            shoulder REAL,
            collar REAL,
            chest REAL,
            waist REAL,
            hip REAL,
            shalwar_length REAL,
            shalwar_bottom REAL,
            shalwar_waist TEXT,
            cuff TEXT,
            trouser_length REAL,
            trouser_bottom REAL,
            front_style TEXT,
            detail TEXT,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS stock_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            stock_item_id INTEGER NOT NULL,
            order_id INTEGER,
            transaction_type TEXT NOT NULL,
            meters REAL NOT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (stock_item_id) REFERENCES stock_items(id),
            FOREIGN KEY (order_id) REFERENCES orders(id)
        );
    ");

    $admin = $pdo->query("SELECT COUNT(*) FROM users WHERE username='larkana'")->fetchColumn();
    if (!$admin) {
        $hash = password_hash('tailor', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, 'admin', ?)")
            ->execute(['larkana', $hash, 'Lakhmir Khan (Admin)']);
    }
}

function generateOrderNo(): string {
    $db = getDB();
    // Use MAX to stay collision-free even after order deletions.
    $maxNo = $db->query("SELECT MAX(CAST(SUBSTR(order_no, 4) AS INTEGER)) FROM orders WHERE order_no LIKE 'LT-%'")->fetchColumn();
    return 'LT-' . str_pad((int)$maxNo + 1, 5, '0', STR_PAD_LEFT);
}
