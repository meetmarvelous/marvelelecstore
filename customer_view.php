<?php
/**
 * MarvelStore v2.0 — Customer Detail View
 * Shows customer info, purchase history, and repair history.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_login();

$pdo = get_db();
$id = input_int('id');
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    set_flash('danger', 'Customer not found.');
    redirect('customers.php');
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_str('action') === 'edit') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name    = input_str('name');
        $phone   = input_str('phone');
        $email   = input_str('email');
        $address = input_str('address');
        $notes   = input_str('notes');

        if (empty($name)) $errors[] = 'Name is required.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=?, address=?, notes=? WHERE id=?");
            $stmt->execute([$name, $phone ?: null, $email ?: null, $address ?: null, $notes ?: null, $id]);
            log_activity('customer_edit', 'customer', $id, "Edited customer '{$name}'");
            set_flash('success', 'Customer updated.');
            redirect('customer_view.php?id=' . $id);
        }
        $customer = array_merge($customer, compact('name', 'phone', 'email', 'address', 'notes'));
    }
}

// Fetch sales history
$sales = $pdo->query("
    SELECT s.*, u.full_name as cashier
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.customer_id = {$id}
    ORDER BY s.created_at DESC
")->fetchAll();

// Fetch repair history
$repairs = $pdo->query("
    SELECT r.*, u.full_name as technician
    FROM repairs r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.customer_id = {$id}
    ORDER BY r.created_at DESC
")->fetchAll();

// Totals
$total_spent = array_sum(array_column($sales, 'total'));
$total_repairs = count($repairs);

$page_title = 'Customer: ' . $customer['name'];
$current_page = 'customer_view.php';
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>Customer Profile</h1>
  <div class="section-header-breadcrumb">
    <div class="breadcrumb-item"><a href="customers.php">Customers</a></div>
    <div class="breadcrumb-item active"><?= e($customer['name']) ?></div>
  </div>
</div>

<div class="section-body">
  <?= render_flash() ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- Customer Info -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h4>Customer Info</h4></div>
        <div class="card-body">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <div class="form-group">
              <label>Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= e($customer['name']) ?>" required>
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($customer['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($customer['email'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Address</label>
              <textarea name="address" class="form-control" rows="2"><?= e($customer['address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label>Notes</label>
              <textarea name="notes" class="form-control" rows="2"><?= e($customer['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Update</button>
          </form>
        </div>
      </div>

      <!-- Summary -->
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span>Total Spent</span>
            <strong><?= format_naira($total_spent) ?></strong>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span>Total Sales</span>
            <strong><?= count($sales) ?></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span>Total Repairs</span>
            <strong><?= $total_repairs ?></strong>
          </div>
        </div>
      </div>
    </div>

    <!-- History -->
    <div class="col-lg-8">
      <!-- Purchase History -->
      <div class="card">
        <div class="card-header"><h4><i class="fas fa-shopping-bag"></i> Purchase History</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>#</th><th>Date</th><th>Cashier</th><th>Payment</th><th>Total</th><th></th></tr></thead>
              <tbody>
                <?php if (empty($sales)): ?>
                  <tr><td colspan="6" class="text-center text-muted">No purchases yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($sales as $s): ?>
                  <tr>
                    <td><?= (int)$s['id'] ?></td>
                    <td><?= e(date('M d, Y', strtotime($s['created_at']))) ?></td>
                    <td><?= e($s['cashier'] ?? 'N/A') ?></td>
                    <td><span class="badge badge-info"><?= ucfirst(e($s['payment_method'])) ?></span></td>
                    <td><?= format_naira($s['total']) ?></td>
                    <td><a href="sale_receipt.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-receipt"></i></a></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Repair History -->
      <div class="card">
        <div class="card-header"><h4><i class="fas fa-tools"></i> Repair History</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>#</th><th>Date</th><th>Device</th><th>Status</th><th>Cost</th><th></th></tr></thead>
              <tbody>
                <?php if (empty($repairs)): ?>
                  <tr><td colspan="6" class="text-center text-muted">No repairs yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($repairs as $r): ?>
                  <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= e(date('M d, Y', strtotime($r['created_at']))) ?></td>
                    <td><?= e($r['device_model']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= format_naira($r['repair_cost']) ?></td>
                    <td><a href="repair_view.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
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
