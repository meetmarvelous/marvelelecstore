<?php
/**
 * MarvelStore v2.0 — API: Report Data
 * Returns JSON for report charts (category sales, payment methods, top products).
 */
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';

header('Content-Type: application/json');
if (!is_logged_in() || current_user('role') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$pdo  = get_db();
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$data = [];

// 1. Sales by Category
$stmt = $pdo->prepare("
    SELECT c.name as category, COALESCE(SUM(si.line_total), 0) as total
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN sales s ON s.id = si.sale_id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY c.id ORDER BY total DESC
");
$stmt->execute([$from, $to]);
$cat_sales = $stmt->fetchAll();
$data['categories'] = [
    'labels' => array_column($cat_sales, 'category'),
    'values' => array_map('floatval', array_column($cat_sales, 'total')),
];

// 2. Sales by Payment Method
$stmt = $pdo->prepare("
    SELECT payment_method, COUNT(*) as count, COALESCE(SUM(total), 0) as total
    FROM sales WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_method
");
$stmt->execute([$from, $to]);
$pm = $stmt->fetchAll();
$data['payment_methods'] = [
    'labels' => array_map('ucfirst', array_column($pm, 'payment_method')),
    'values' => array_map('floatval', array_column($pm, 'total')),
    'counts' => array_map('intval', array_column($pm, 'count')),
];

// 3. Top 10 Products
$stmt = $pdo->prepare("
    SELECT p.name, SUM(si.quantity) as qty_sold, SUM(si.line_total) as revenue
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON s.id = si.sale_id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY p.id ORDER BY qty_sold DESC LIMIT 10
");
$stmt->execute([$from, $to]);
$top = $stmt->fetchAll();
$data['top_products'] = [
    'labels'  => array_column($top, 'name'),
    'qty'     => array_map('intval', array_column($top, 'qty_sold')),
    'revenue' => array_map('floatval', array_column($top, 'revenue')),
];

// 4. Staff Performance
$stmt = $pdo->prepare("
    SELECT u.full_name, COUNT(s.id) as sale_count,
           COALESCE(SUM(s.total), 0) as revenue,
           COALESCE(AVG(s.total), 0) as avg_sale
    FROM users u
    LEFT JOIN sales s ON u.id = s.user_id AND DATE(s.created_at) BETWEEN ? AND ?
    WHERE u.role IN ('admin','staff') AND u.is_active = 1
    GROUP BY u.id ORDER BY revenue DESC
");
$stmt->execute([$from, $to]);
$data['staff_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Inventory Value
$inv = $pdo->query("
    SELECT
        COALESCE(SUM(quantity * cost_price), 0) as cost_value,
        COALESCE(SUM(quantity * selling_price), 0) as retail_value,
        COUNT(*) as total_products,
        COALESCE(SUM(quantity), 0) as total_units
    FROM products
")->fetch();
$data['inventory'] = $inv;

// 6. Repair Turnaround
$stmt = $pdo->prepare("
    SELECT COALESCE(AVG(DATEDIFF(updated_at, created_at)), 0) as avg_days
    FROM repairs WHERE status = 'collected' AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$data['repair_turnaround'] = round($stmt->fetchColumn(), 1);

echo json_encode($data);
