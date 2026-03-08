<?php
/**
 * MarvelStore v1.0 — Products Listing
 * DataTables listing of all products.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_role('admin');

$pdo = get_db();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_str('action') === 'delete') {
    if (!validate_csrf(input_str('csrf_token'))) {
        set_flash('danger', 'Invalid CSRF token.');
    } else {
        $id = input_int('id');
        // Get name before deleting for log
        $stn = $pdo->prepare("SELECT name FROM products WHERE id = ?"); $stn->execute([$id]); $pname = $stn->fetchColumn();
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        log_activity('product_delete', 'product', $id, "Deleted product '{$pname}'");
        set_flash('success', 'Product deleted successfully.');
    }
    redirect('products.php');
}

// Category filter
$category_id = input_int('category');
$category_name = null;
if ($category_id) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category_name = $stmt->fetchColumn();
}

if ($category_id && $category_name) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$category_id]);
    $products = $stmt->fetchAll();
} else {
    $products = $pdo->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
    ")->fetchAll();
}

$page_title = $category_name ? "Products: {$category_name}" : 'All Products';
$current_page = 'products.php';
$extra_css = [
    OTIKA_ASSETS . 'bundles/datatables/datatables.min.css',
    OTIKA_ASSETS . 'bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css',
];
$extra_js = [
    OTIKA_ASSETS . 'bundles/datatables/datatables.min.js',
    OTIKA_ASSETS . 'bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js',
    OTIKA_ASSETS . 'bundles/sweetalert/sweetalert.min.js',
];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1><?= $category_name ? 'Products: ' . e($category_name) : 'All Products' ?></h1>
  <div class="section-header-button">
    <?php if ($category_name): ?>
    <a href="products.php" class="btn btn-outline-secondary mr-2"><i class="fas fa-times"></i> Clear Filter</a>
    <?php endif; ?>
    <a href="product_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
  </div>
</div>

<div class="section-body">
  <?= render_flash() ?>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped" id="products-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Brand</th>
              <th>Category</th>
              <th>SKU</th>
              <th>Stock</th>
              <th>Cost</th>
              <th>Price</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $i => $p): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= e($p['name']) ?></td>
              <td><?= e($p['brand'] ?? '-') ?></td>
              <td><?= e($p['category_name'] ?? 'Uncategorized') ?></td>
              <td><code><?= e($p['sku'] ?? '-') ?></code></td>
              <td>
                <span class="badge badge-<?= $p['quantity'] <= $p['low_stock_threshold'] ? ($p['quantity'] <= 0 ? 'danger' : 'warning') : 'success' ?>">
                  <?= (int)$p['quantity'] ?>
                </span>
              </td>
              <td><?= format_naira($p['cost_price']) ?></td>
              <td><?= format_naira($p['selling_price']) ?></td>
              <td>
                <a href="product_edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                <form method="POST" class="d-inline delete-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>

<script>
$(document).ready(function() {
  $('#products-table').DataTable({ "order": [[0, "asc"]] });

  $('.delete-form').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    swal({
      title: 'Delete Product?',
      text: 'This action cannot be undone!',
      icon: 'warning',
      buttons: ['Cancel', 'Yes, delete it!'],
      dangerMode: true,
    }).then(function(willDelete) {
      if (willDelete) form.submit();
    });
  });
});
</script>
