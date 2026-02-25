<?php
/**
 * MarvelStore v1.0 — Edit Product
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo = get_db();
$id = input_int('id');

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Product not found.');
    redirect('products.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name         = input_str('name');
        $brand        = input_str('brand');
        $category_id  = input_int('category_id') ?: null;
        $sku          = input_str('sku') ?: null;
        $imei_serial  = input_str('imei_serial') ?: null;
        $cost_price   = (float)input_str('cost_price');
        $selling_price= (float)input_str('selling_price');
        $quantity     = input_int('quantity');
        $threshold    = input_int('low_stock_threshold') ?: 5;

        if (empty($name)) $errors[] = 'Product name is required.';
        if ($selling_price <= 0) $errors[] = 'Selling price must be greater than zero.';

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE products SET category_id=?, name=?, brand=?, sku=?, imei_serial=?, cost_price=?, selling_price=?, quantity=?, low_stock_threshold=? WHERE id=?");
                $stmt->execute([$category_id, $name, $brand, $sku, $imei_serial, $cost_price, $selling_price, $quantity, $threshold, $id]);
                set_flash('success', 'Product updated successfully.');
                redirect('products.php');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = 'A product with this SKU already exists.';
                } else {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
        // Re-populate product with submitted data for sticky form
        $product = array_merge($product, compact('name','brand','category_id','sku','imei_serial','cost_price','selling_price','quantity') + ['low_stock_threshold' => $threshold]);
    }
}

$page_title = 'Edit Product';
$current_page = 'product_edit.php';
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>Edit Product</h1>
  <div class="section-header-breadcrumb">
    <div class="breadcrumb-item"><a href="products.php">Products</a></div>
    <div class="breadcrumb-item active">Edit</div>
  </div>
</div>

<div class="section-body">
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Product Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= e($product['name']) ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Brand</label>
              <input type="text" name="brand" class="form-control" value="<?= e($product['brand'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label>Category</label>
              <select name="category_id" class="form-control">
                <option value="">— Select —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>SKU</label>
              <input type="text" name="sku" class="form-control" value="<?= e($product['sku'] ?? '') ?>">
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <label>IMEI / Serial Number</label>
              <input type="text" name="imei_serial" class="form-control" value="<?= e($product['imei_serial'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label>Cost Price (₦)</label>
              <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= e($product['cost_price']) ?>">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Selling Price (₦) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="selling_price" class="form-control" value="<?= e($product['selling_price']) ?>" required>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Quantity</label>
              <input type="number" name="quantity" class="form-control" value="<?= (int)$product['quantity'] ?>">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Low Stock Threshold</label>
              <input type="number" name="low_stock_threshold" class="form-control" value="<?= (int)$product['low_stock_threshold'] ?>">
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer text-right">
        <a href="products.php" class="btn btn-secondary mr-1">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
      </div>
    </form>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
