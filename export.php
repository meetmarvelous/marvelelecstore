<?php
/**
 * MarvelStore v2.0 — CSV Export Handler
 * Usage: export.php?type=products|sales|repairs|activity_log
 * Optional: &from=YYYY-MM-DD&to=YYYY-MM-DD
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo  = get_db();
$type = input_str('type');
$from = input_str('from') ?: date('Y-m-01');
$to   = input_str('to')   ?: date('Y-m-d');

// Admin-only exports
if (in_array($type, ['activity_log']) && current_user('role') !== 'admin') {
    http_response_code(403);
    die('Access denied');
}

$filename = $type . '_' . date('Y-m-d') . '.csv';

switch ($type) {
    case 'products':
        $headers = ['ID', 'Name', 'Brand', 'Category', 'SKU', 'Cost Price', 'Selling Price', 'Quantity', 'Low Stock Threshold'];
        $rows = $pdo->query("
            SELECT p.id, p.name, p.brand, c.name as category, p.sku, p.cost_price, p.selling_price, p.quantity, p.low_stock_threshold
            FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name
        ")->fetchAll(PDO::FETCH_NUM);
        break;

    case 'sales':
        $headers = ['Sale ID', 'Date', 'Cashier', 'Customer', 'Payment Method', 'Subtotal', 'Discount', 'Total'];
        $stmt = $pdo->prepare("
            SELECT s.id, s.created_at, u.full_name, cu.name as customer, s.payment_method, s.subtotal, s.discount, s.total
            FROM sales s 
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN customers cu ON s.customer_id = cu.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        break;

    case 'repairs':
        $headers = ['Repair ID', 'Date', 'Technician', 'Customer', 'Phone', 'Device', 'Status', 'Cost'];
        $stmt = $pdo->prepare("
            SELECT r.id, r.created_at, u.full_name, r.customer_name, r.customer_phone, r.device_model, r.status, r.repair_cost
            FROM repairs r LEFT JOIN users u ON r.user_id = u.id
            WHERE DATE(r.created_at) BETWEEN ? AND ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        break;

    case 'activity_log':
        $headers = ['ID', 'Date', 'User', 'Action', 'Entity', 'Description', 'IP'];
        $stmt = $pdo->prepare("
            SELECT al.id, al.created_at, u.full_name, al.action, CONCAT(al.entity_type, '#', al.entity_id), al.description, al.ip_address
            FROM activity_log al LEFT JOIN users u ON al.user_id = u.id
            WHERE DATE(al.created_at) BETWEEN ? AND ?
            ORDER BY al.created_at DESC
        ");
        $stmt->execute([$from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        break;

    case 'customers':
        $headers = ['ID', 'Name', 'Phone', 'Email', 'Address', 'Notes', 'Created'];
        $rows = $pdo->query("SELECT id, name, phone, email, address, notes, created_at FROM customers ORDER BY name")->fetchAll(PDO::FETCH_NUM);
        break;

    default:
        http_response_code(400);
        die('Invalid export type. Allowed: products, sales, repairs, activity_log, customers');
}

// Output CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

$fp = fopen('php://output', 'w');
// BOM for Excel compatibility
fwrite($fp, "\xEF\xBB\xBF");
fputcsv($fp, $headers);
foreach ($rows as $row) {
    fputcsv($fp, $row);
}
fclose($fp);
exit;
