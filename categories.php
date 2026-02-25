<?php
/**
 * MarvelStore v1.0 — Categories Management
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

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
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
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
                    <td><?= e($cat['name']) ?></td>
                    <td><span class="badge badge-light"><?= (int)$cat['product_count'] ?></span></td>
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
