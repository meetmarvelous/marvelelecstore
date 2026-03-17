<?php
/**
 * MarvelStore v1.0 — Categories Management
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_role('admin');

$pdo = get_db();
$errors = [];

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_str('action') === 'add') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name = input_str('name');
        if (empty($name)) {
            $errors[] = 'Category name is required.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            log_activity('category_add', 'category', (int)$pdo->lastInsertId(), "Added category '{$name}'");
            set_flash('success', 'Category added.');
            redirect('categories.php');
        }
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_str('action') === 'delete') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $id = input_int('id');
        $stn = $pdo->prepare("SELECT name FROM categories WHERE id = ?"); $stn->execute([$id]); $cname = $stn->fetchColumn();
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        log_activity('category_delete', 'category', $id, "Deleted category '{$cname}'");
        set_flash('success', 'Category deleted.');
        redirect('categories.php');
    }
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();

$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_categories = count($categories);

$inv = $pdo->query("
    SELECT 
        COALESCE(SUM(quantity * cost_price),0) as cost_val, 
        COALESCE(SUM(quantity * selling_price),0) as retail_val, 
        SUM(CASE WHEN cost_price <= 0 OR cost_price IS NULL THEN 1 ELSE 0 END) as missing_cost_count,
        SUM(CASE WHEN selling_price <= 0 OR selling_price IS NULL THEN 1 ELSE 0 END) as missing_retail_count
    FROM products
")->fetch();

$page_title = 'Categories';
$current_page = 'categories.php';
$extra_js = [OTIKA_ASSETS . 'bundles/sweetalert/sweetalert.min.js'];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>Categories</h1>
</div>

<div class="section-body">
  <?= render_flash() ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

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

  <div class="row">
    <div class="col-md-5">
      <div class="card">
        <div class="card-header"><h4>Add Category</h4></div>
        <div class="card-body">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
              <label>Category Name</label>
              <input type="text" name="name" class="form-control" required placeholder="e.g. Screen Protectors">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-md-7">
      <div class="card">
        <div class="card-header"><h4>All Categories</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr><th>#</th><th>Name</th><th>Products</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php if (empty($categories)): ?>
                  <tr><td colspan="4" class="text-center text-muted">No categories yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($categories as $i => $cat): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><a href="products.php?category=<?= (int)$cat['id'] ?>" class="text-dark font-weight-bold"><?= e($cat['name']) ?></a></td>
                    <td><a href="products.php?category=<?= (int)$cat['id'] ?>" class="badge badge-light"><?= (int)$cat['product_count'] ?></a></td>
                    <td>
                      <form method="POST" class="d-inline delete-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
                
                <?php 
                // Append Uncategorized row
                $uncategorized_count = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id IS NULL OR category_id = 0")->fetchColumn();
                ?>
                <?php if ($uncategorized_count > 0): ?>
                  <tr class="table-warning">
                    <td class="text-muted">-</td>
                    <td><a href="products.php?category=0" class="text-danger font-italic font-weight-bold">Uncategorized</a></td>
                    <td><a href="products.php?category=0" class="badge badge-danger"><?= (int)$uncategorized_count ?></a></td>
                    <td><span class="text-muted" style="font-size: 11px;">System Default</span></td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>

<script>
$(document).ready(function() {
  $('.delete-form').on('submit', function(e) {
    e.preventDefault();
    var form = this;
    swal({
      title: 'Delete Category?',
      text: 'Products in this category will become uncategorized.',
      icon: 'warning',
      buttons: ['Cancel', 'Yes, delete!'],
      dangerMode: true,
    }).then(function(willDelete) {
      if (willDelete) form.submit();
    });
  });
});
</script>
