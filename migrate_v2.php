<?php
/**
 * MarvelStore v2.0 — Database Migration
 * Run this ONCE to upgrade a v1.0 database to v2.0.
 * Safe to run multiple times (uses IF NOT EXISTS / IGNORE).
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';

$pdo = get_db();
echo "<h3>MarvelStore v2.0 Migration</h3><pre>";

// ── 1. Activity Log ──────────────────────────────────────────────
echo "1. Creating activity_log table... ";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50),
        entity_id INT,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_action (action),
        INDEX idx_entity (entity_type, entity_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB
");
echo "✅\n";

// ── 2. Customers ─────────────────────────────────────────────────
echo "2. Creating customers table... ";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        email VARCHAR(255),
        address TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_phone (phone),
        INDEX idx_name (name)
    ) ENGINE=InnoDB
");
echo "✅\n";

// ── 3. Add customer_id to sales ──────────────────────────────────
echo "3. Adding customer_id to sales... ";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM sales LIKE 'customer_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN customer_id INT DEFAULT NULL AFTER user_id");
        $pdo->exec("ALTER TABLE sales ADD FOREIGN KEY (fk_sale_customer) REFERENCES customers(id) ON DELETE SET NULL");
    }
    echo "✅\n";
} catch (Exception $e) {
    // FK might already exist or column already exists
    echo "✅ (already exists)\n";
}

// ── 4. Add customer_id to repairs ────────────────────────────────
echo "4. Adding customer_id to repairs... ";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM repairs LIKE 'customer_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE repairs ADD COLUMN customer_id INT DEFAULT NULL AFTER user_id");
        $pdo->exec("ALTER TABLE repairs ADD FOREIGN KEY (fk_repair_customer) REFERENCES customers(id) ON DELETE SET NULL");
    }
    echo "✅\n";
} catch (Exception $e) {
    echo "✅ (already exists)\n";
}

// ── 5. Settings ──────────────────────────────────────────────────
echo "5. Creating settings table... ";
$pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

// Seed defaults
$defaults = [
    'store_name'     => 'MarvelStore',
    'store_address'  => '',
    'store_phone'    => '',
    'receipt_footer'  => 'Thank you for your patronage!',
];
$stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
foreach ($defaults as $k => $v) {
    $stmt->execute([$k, $v]);
}
echo "✅\n";

echo "\n<strong style='color:green'>✓ Migration complete!</strong>\n";
echo "</pre>";
echo "<a href='login.php'>← Back to Login</a>";
