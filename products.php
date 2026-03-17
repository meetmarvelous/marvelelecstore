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
$category_id = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$category_name = null;

if ($category_id !== null) {
    if ($category_id === 0) {
        $category_name = 'Uncategorized';
        $products = $pdo->query("
            SELECT p.*, 'Uncategorized' as category_name
            FROM products p
            WHERE p.category_id IS NULL OR p.category_id = 0
            ORDER BY p.created_at DESC
        ")->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category_name = $stmt->fetchColumn();

        if ($category_name) {
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
            // Category not found
            $products = [];
        }
    }
} else {
    $products = $pdo->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC
    ")->fetchAll();
}

$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

$inv = $pdo->query("
    SELECT 
        COALESCE(SUM(quantity * cost_price),0) as cost_val, 
        COALESCE(SUM(quantity * selling_price),0) as retail_val, 
        SUM(CASE WHEN cost_price <= 0 OR cost_price IS NULL THEN 1 ELSE 0 END) as missing_cost_count,
        SUM(CASE WHEN selling_price <= 0 OR selling_price IS NULL THEN 1 ELSE 0 END) as missing_retail_count
    FROM products
")->fetch();

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

  <div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-warning"><i class="fas fa-boxes"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Total Products</h4></div>
          <div class="card-body"><?= (int)$total_products ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-primary"><i class="fas fa-tags"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Categories</h4></div>
          <div class="card-body"><?= (int)$total_categories ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-danger"><i class="fas fa-money-bill-wave"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Inv. Cost Value</h4></div>
          <div class="card-body" style="font-size: 16px; white-space: nowrap; letter-spacing: -0.5px;"><?= format_naira($inv['cost_val']) ?></div>
          <?php if ($inv['missing_cost_count'] > 0): ?>
          <div class="text-small text-danger" style="margin-top: 2px; font-weight: bold; line-height: 1.1; font-size: 11px;"><?= (int)$inv['missing_cost_count'] ?> item(s) missing cost</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Inv. Retail Value</h4></div>
          <div class="card-body" style="font-size: 16px; white-space: nowrap; letter-spacing: -0.5px;"><?= format_naira($inv['retail_val']) ?></div>
          <?php if ($inv['missing_retail_count'] > 0): ?>
          <div class="text-small text-danger" style="margin-top: 2px; font-weight: bold; line-height: 1.1; font-size: 11px;"><?= (int)$inv['missing_retail_count'] ?> item(s) missing price</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

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
