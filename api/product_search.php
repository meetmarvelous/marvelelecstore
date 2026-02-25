<?php
/**
 * MarvelStore v1.0 — API: Product Search
 * Returns JSON for Select2 AJAX.
 * Usage: GET /api/product_search.php?q=iphone
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

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$pdo = get_db();
$stmt = $pdo->prepare("
    SELECT id, name, brand, sku, selling_price, cost_price, quantity
    FROM products
    WHERE (name LIKE ? OR brand LIKE ? OR sku LIKE ?)
    AND quantity > 0
    ORDER BY name
    LIMIT 20
");
$term = "%{$q}%";
$stmt->execute([$term, $term, $term]);
$products = $stmt->fetchAll();

$results = [];
foreach ($products as $p) {
    $label = $p['name'];
    if ($p['brand']) $label .= ' (' . $p['brand'] . ')';
    if ($p['sku'])   $label .= ' [' . $p['sku'] . ']';

    $results[] = [
        'id'    => (int)$p['id'],
        'text'  => $label,
        'price' => (float)$p['selling_price'],
        'cost'  => (float)$p['cost_price'],
        'stock' => (int)$p['quantity'],
    ];
}

echo json_encode($results);
