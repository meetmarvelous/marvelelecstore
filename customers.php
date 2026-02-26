<?php
/**
 * MarvelStore v2.0 — Customer List
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_login();

$pdo = get_db();
$errors = [];

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_str('action') === 'add') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name  = input_str('name');
        $phone = input_str('phone');
        $email = input_str('email');
        $address = input_str('address');
        $notes = input_str('notes');

        if (empty($name)) {
            $errors[] = 'Customer name is required.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone ?: null, $email ?: null, $address ?: null, $notes ?: null]);
            log_activity('customer_add', 'customer', (int)$pdo->lastInsertId(), "Added customer '{$name}'");
            set_flash('success', 'Customer added.');
            redirect('customers.php');
        }
    }
}

$customers = $pdo->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM sales WHERE customer_id = c.id) as sale_count,
        (SELECT COUNT(*) FROM repairs WHERE customer_id = c.id) as repair_count
    FROM customers c
    ORDER BY c.name
")->fetchAll();

$page_title = 'Customers';
$current_page = 'customers.php';
$extra_css = [
    OTIKA_ASSETS . 'bundles/datatables/datatables.min.css',
    OTIKA_ASSETS . 'bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css',
];
$extra_js = [
    OTIKA_ASSETS . 'bundles/datatables/datatables.min.js',
    OTIKA_ASSETS . 'bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js',
];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>Customers</h1>
</div>

<div class="section-body">
  <?= render_flash() ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- Add Customer -->
    <div class="col-md-4">
      <div class="card">
        <div class="card-header"><h4>Add Customer</h4></div>
        <div class="card-body">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
              <label>Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required placeholder="Full name">
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="08012345678">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" class="form-control" placeholder="email@example.com">
            </div>
            <div class="form-group">
              <label>Address</label>
              <textarea name="address" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label>Notes</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Add Customer</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Customer List -->
    <div class="col-md-8">
      <div class="card">
        <div class="card-header"><h4>All Customers</h4></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped" id="customers-table">
              <thead>
                <tr><th>#</th><th>Name</th><th>Phone</th><th>Sales</th><th>Repairs</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php foreach ($customers as $i => $c): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= e($c['name']) ?></td>
                  <td><?= e($c['phone'] ?? '-') ?></td>
                  <td><span class="badge badge-info"><?= (int)$c['sale_count'] ?></span></td>
                  <td><span class="badge badge-warning"><?= (int)$c['repair_count'] ?></span></td>
                  <td><a href="customer_view.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
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
  $('#customers-table').DataTable({ "order": [[1, "asc"]] });
});
</script>
