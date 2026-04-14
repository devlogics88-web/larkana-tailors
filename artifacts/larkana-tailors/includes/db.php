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
            stitching_price REAL DEFAULT 2300,
            stitching_type_id INTEGER,
            stitching_type_name TEXT,
            button_type_id INTEGER,
            button_type_name TEXT,
            button_price REAL DEFAULT 0,
            pancha_type_id INTEGER,
            pancha_type_name TEXT,
            pancha_price REAL DEFAULT 0,
            discount REAL DEFAULT 0,
            total_price REAL DEFAULT 0,
            advance_paid REAL DEFAULT 0,
            remaining REAL DEFAULT 0,
            payment_method TEXT DEFAULT 'Cash',
            receiving_hand TEXT,
            dues_cleared INTEGER DEFAULT 0,
            cleared_at TEXT,
            cleared_by INTEGER,
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
            shirt_length TEXT,
            sleeve TEXT,
            arm TEXT,
            shoulder TEXT,
            collar TEXT,
            chest TEXT,
            waist TEXT,
            hip TEXT,
            shalwar_length TEXT,
            shalwar_bottom TEXT,
            shalwar_waist TEXT,
            cuff TEXT,
            trouser_length TEXT,
            trouser_bottom TEXT,
            front_style TEXT,
            main_full TEXT,
            main_half TEXT,
            kaf TEXT,
            gera_chorus TEXT,
            size_note TEXT,
            shalwar_style TEXT,
            gera_oval TEXT,
            harmol TEXT,
            chak_patti_button TEXT,
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

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS stitching_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            price REAL NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS button_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            price REAL NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS pancha_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            price REAL NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1
        );
    ");

    // Migrations for existing databases
    $migrations = [
        "ALTER TABLE measurements ADD COLUMN arm TEXT",
        "ALTER TABLE measurements ADD COLUMN main_full TEXT",
        "ALTER TABLE measurements ADD COLUMN main_half TEXT",
        "ALTER TABLE measurements ADD COLUMN kaf TEXT",
        "ALTER TABLE measurements ADD COLUMN gera_chorus TEXT",
        "ALTER TABLE measurements ADD COLUMN size_note TEXT",
        "ALTER TABLE measurements ADD COLUMN shalwar_style TEXT",
        "ALTER TABLE measurements ADD COLUMN gera_oval TEXT",
        "ALTER TABLE orders ADD COLUMN stitching_price REAL DEFAULT 2300",
        "ALTER TABLE orders ADD COLUMN stitching_type_id INTEGER",
        "ALTER TABLE orders ADD COLUMN stitching_type_name TEXT",
        "ALTER TABLE orders ADD COLUMN button_type_id INTEGER",
        "ALTER TABLE orders ADD COLUMN button_type_name TEXT",
        "ALTER TABLE orders ADD COLUMN button_price REAL DEFAULT 0",
        "ALTER TABLE orders ADD COLUMN pancha_type_id INTEGER",
        "ALTER TABLE orders ADD COLUMN pancha_type_name TEXT",
        "ALTER TABLE orders ADD COLUMN pancha_price REAL DEFAULT 0",
        "ALTER TABLE orders ADD COLUMN discount REAL DEFAULT 0",
        "ALTER TABLE orders ADD COLUMN payment_method TEXT DEFAULT 'Cash'",
        "ALTER TABLE orders ADD COLUMN receiving_hand TEXT",
        "ALTER TABLE measurements ADD COLUMN harmol TEXT",
        "ALTER TABLE measurements ADD COLUMN chak_patti_button TEXT",
        "ALTER TABLE orders ADD COLUMN dues_cleared INTEGER DEFAULT 0",
        "ALTER TABLE orders ADD COLUMN cleared_at TEXT",
        "ALTER TABLE orders ADD COLUMN cleared_by INTEGER",
        "ALTER TABLE stock_items ADD COLUMN stock_date DATE",
        "ALTER TABLE stock_items ADD COLUMN has_box INTEGER DEFAULT 0",
        "ALTER TABLE stock_items ADD COLUMN box_quantity REAL DEFAULT 0",
        "ALTER TABLE stock_items ADD COLUMN box_price REAL DEFAULT 0",
    ];
    foreach ($migrations as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $ignored) {}
    }

    // Seed admin user
    $admin = $pdo->query("SELECT COUNT(*) FROM users WHERE username='larkana'")->fetchColumn();
    if (!$admin) {
        $hash = password_hash('tailor', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, 'admin', ?)")
            ->execute(['larkana', $hash, 'Lakhmir Khan (Admin)']);
    }

    // Seed default settings
    $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES
        ('default_stitching_price', '2300'),
        ('shop_name', 'Larkana Fabrics'),
        ('shop_phone', '0300-2151261'),
        ('shop_address', 'SOAN GARDEN, Shahid Arcade, Main Double Road, Islamabad')
    ");
    // Migrations: update existing settings to new values
    $pdo->exec("UPDATE settings SET value='Larkana Fabrics & Tailors' WHERE key='shop_name'");
    $pdo->exec("UPDATE settings SET value='2300' WHERE key='default_stitching_price' AND CAST(value AS REAL) < 2300");

    // Seed stitching types
    $stCount = $pdo->query("SELECT COUNT(*) FROM stitching_types")->fetchColumn();
    if (!$stCount) {
        $pdo->exec("INSERT INTO stitching_types (name, price) VALUES
            ('Single Stitching', 2300),
            ('Double Stitching', 2600),
            ('Gum Silai', 2800)
        ");
    }

    // Seed button types
    $btCount = $pdo->query("SELECT COUNT(*) FROM button_types")->fetchColumn();
    if (!$btCount) {
        $pdo->exec("INSERT INTO button_types (name, price) VALUES
            ('Fancy Button', 200),
            ('Tich Button', 400)
        ");
    }

    // Seed pancha types
    $ptCount = $pdo->query("SELECT COUNT(*) FROM pancha_types")->fetchColumn();
    if (!$ptCount) {
        $pdo->exec("INSERT INTO pancha_types (name, price) VALUES
            ('Pancha Jali', 400),
            ('Karhai', 250)
        ");
    }
}

function generateOrderNo(): string {
    $db = getDB();
    $maxNo = $db->query("SELECT MAX(CAST(SUBSTR(order_no, 4) AS INTEGER)) FROM orders WHERE order_no LIKE 'LT-%'")->fetchColumn();
    return 'LT-' . str_pad((int)$maxNo + 1, 5, '0', STR_PAD_LEFT);
}
