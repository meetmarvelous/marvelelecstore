<?php
/**
 * MarvelStore v1.0 — Add Repair Ticket
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $customer_name  = input_str('customer_name');
        $customer_phone = input_str('customer_phone');
        $device_model   = input_str('device_model');
        $device_passcode= input_str('device_passcode');
        $fault           = input_str('fault_description');
        $repair_cost    = (float)input_str('repair_cost');

        if (empty($customer_name)) $errors[] = 'Customer name is required.';
        if (empty($device_model))  $errors[] = 'Device model is required.';
        if (empty($fault))         $errors[] = 'Fault description is required.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO repairs (user_id, customer_name, customer_phone, device_model, device_passcode, fault_description, repair_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([current_user('id'), $customer_name, $customer_phone, $device_model, $device_passcode, $fault, $repair_cost]);
            $rid = (int)$pdo->lastInsertId();
            log_activity('repair_create', 'repair', $rid, "Repair #{$rid} — {$device_model} for {$customer_name}");
            set_flash('success', 'Repair ticket created (#' . $rid . ').');
            redirect('repairs.php');
        }
    }
}

$page_title = 'New Repair';
$current_page = 'repair_add.php';
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>New Repair Ticket</h1>
  <div class="section-header-breadcrumb">
    <div class="breadcrumb-item"><a href="repairs.php">Repairs</a></div>
    <div class="breadcrumb-item active">New</div>
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
      <div class="card-header"><h4>Customer & Device Info</h4></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Customer Name <span class="text-danger">*</span></label>
              <input type="text" name="customer_name" class="form-control" value="<?= e(input_str('customer_name')) ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Phone Number</label>
              <input type="text" name="customer_phone" class="form-control" value="<?= e(input_str('customer_phone')) ?>" placeholder="08012345678">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Device Model <span class="text-danger">*</span></label>
              <input type="text" name="device_model" class="form-control" value="<?= e(input_str('device_model')) ?>" required placeholder="e.g. iPhone 13 Pro Max">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Device Passcode</label>
              <input type="text" name="device_passcode" class="form-control" value="<?= e(input_str('device_passcode')) ?>">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Estimated Cost (₦)</label>
              <input type="number" step="0.01" name="repair_cost" class="form-control" value="<?= e(input_str('repair_cost') ?: '0') ?>">
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Fault Description <span class="text-danger">*</span></label>
          <textarea name="fault_description" class="form-control" rows="4" required placeholder="Describe the issue..."><?= e(input_str('fault_description')) ?></textarea>
        </div>
      </div>
      <div class="card-footer text-right">
        <a href="repairs.php" class="btn btn-secondary mr-1">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Ticket</button>
      </div>
    </form>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
