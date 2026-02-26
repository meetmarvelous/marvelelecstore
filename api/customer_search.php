<?php
/**
 * MarvelStore v2.0 — API: Customer Search
 * Returns JSON for Select2 AJAX.
 * Usage: GET /api/customer_search.php?q=john
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
    SELECT id, name, phone, email
    FROM customers
    WHERE name LIKE ? OR phone LIKE ? OR email LIKE ?
    ORDER BY name
    LIMIT 20
");
$term = "%{$q}%";
$stmt->execute([$term, $term, $term]);
$customers = $stmt->fetchAll();

$results = [];
foreach ($customers as $c) {
    $label = $c['name'];
    if ($c['phone']) $label .= ' (' . $c['phone'] . ')';

    $results[] = [
        'id'    => (int)$c['id'],
        'text'  => $label,
        'name'  => $c['name'],
        'phone' => $c['phone'] ?? '',
        'email' => $c['email'] ?? '',
    ];
}

echo json_encode($results);
