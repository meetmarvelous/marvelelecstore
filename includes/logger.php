<?php
/**
 * MarvelStore v2.0 — Activity Logger
 * Non-deletable audit trail for all important system actions.
 *
 * Usage:
 *   log_activity('product_add', 'product', $id, "Added product 'iPhone Case' (qty: 50)");
 */

/**
 * Log an activity to the audit trail.
 *
 * @param string      $action      Action identifier (e.g. 'product_add', 'sale_create')
 * @param string|null $entity_type Entity type (e.g. 'product', 'sale', 'repair', 'user', 'category')
 * @param int|null    $entity_id   Entity ID
 * @param string      $description Human-readable description
 */
function log_activity(string $action, ?string $entity_type = null, ?int $entity_id = null, string $description = ''): void {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $entity_type,
            $entity_id,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);
    } catch (Exception $e) {
        // Silently fail — logging should never break the app
        error_log('Activity log error: ' . $e->getMessage());
    }
}

/**
 * Get a human-readable label for an action.
 */
function action_label(string $action): string {
    $map = [
        'login'            => '<span class="badge badge-success">Login</span>',
        'logout'           => '<span class="badge badge-secondary">Logout</span>',
        'product_add'      => '<span class="badge badge-primary">Product Added</span>',
        'product_edit'     => '<span class="badge badge-info">Product Edited</span>',
        'product_delete'   => '<span class="badge badge-danger">Product Deleted</span>',
        'category_add'     => '<span class="badge badge-primary">Category Added</span>',
        'category_delete'  => '<span class="badge badge-danger">Category Deleted</span>',
        'sale_create'      => '<span class="badge badge-success">Sale Created</span>',
        'repair_create'    => '<span class="badge badge-primary">Repair Created</span>',
        'repair_status'    => '<span class="badge badge-info">Repair Status</span>',
        'repair_part'      => '<span class="badge badge-warning">Part Added</span>',
        'user_create'      => '<span class="badge badge-primary">User Created</span>',
        'user_toggle'      => '<span class="badge badge-warning">User Toggled</span>',
    ];
    return $map[$action] ?? '<span class="badge badge-dark">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $action))) . '</span>';
}
