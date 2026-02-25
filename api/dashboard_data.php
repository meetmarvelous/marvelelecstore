<?php
/**
 * MarvelStore v1.0 — API: Dashboard Chart Data
 * Returns last 7 days of revenue for ApexCharts.
 */
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = get_db();

$labels  = [];
$revenue = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('M d', strtotime($date));

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $revenue[] = (float)$stmt->fetchColumn();
}

echo json_encode([
    'labels'  => $labels,
    'revenue' => $revenue,
]);
