<?php
/**
 * MarvelStore v1.0 — Repair Detail & Status Workflow
 * Status: Pending → Repairing → Ready → Collected
 * Parts: link inventory items to repair job.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo = get_db();
$id = input_int('id');
$errors = [];

// Fetch repair
$stmt = $pdo->prepare("SELECT r.*, u.full_name as created_by FROM repairs r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch();

if (!$repair) {
    set_flash('danger', 'Repair ticket not found.');
    redirect('repairs.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_str('action') === 'update_status') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $new_status = input_str('status');
        $allowed = ['pending', 'repairing', 'ready', 'collected'];
        if (in_array($new_status, $allowed)) {
            $stmt = $pdo->prepare("UPDATE repairs SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            set_flash('success', 'Status updated to ' . ucfirst($new_status) . '.');
            redirect('repair_view.php?id=' . $id);
        }
    }
}

// Handle add part
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_str('action') === 'add_part') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $product_id = input_int('product_id');
        $qty = max(1, input_int('part_qty'));

        // Get product info
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $errors[] = 'Product not found.';
        } elseif ($product['quantity'] < $qty) {
            $errors[] = 'Insufficient stock for ' . $product['name'] . '.';
        } else {
            try {
                $pdo->beginTransaction();

                $line_total = $product['cost_price'] * $qty;

                // Insert part link
                $stmt = $pdo->prepare("INSERT INTO repair_parts (repair_id, product_id, quantity, unit_cost, line_total) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $product_id, $qty, $product['cost_price'], $line_total]);

                // Deduct stock
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$qty, $product_id]);

                // Update repair cost
                $stmt = $pdo->prepare("UPDATE repairs SET repair_cost = repair_cost + ? WHERE id = ?");
                $stmt->execute([$line_total, $id]);

                $pdo->commit();
                set_flash('success', 'Part added and stock deducted.');
                redirect('repair_view.php?id=' . $id);
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Re-fetch repair after possible updates
$stmt = $pdo->prepare("SELECT r.*, u.full_name as created_by FROM repairs r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch();

// Get parts
$stmt = $pdo->prepare("SELECT rp.*, p.name as product_name FROM repair_parts rp LEFT JOIN products p ON rp.product_id = p.id WHERE rp.repair_id = ?");
$stmt->execute([$id]);
$parts = $stmt->fetchAll();

$page_title = 'Repair #' . $id;
$current_page = 'repair_view.php';
$extra_css = [OTIKA_ASSETS . 'bundles/select2/dist/css/select2.min.css'];
$extra_js  = [OTIKA_ASSETS . 'bundles/select2/dist/js/select2.full.min.js'];
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';

$statuses = ['pending', 'repairing', 'ready', 'collected'];
?>

<div class="section-header">
  <h1>Repair Ticket #<?= (int)$repair['id'] ?></h1>
  <div class="section-header-breadcrumb">
    <div class="breadcrumb-item"><a href="repairs.php">Repairs</a></div>
    <div class="breadcrumb-item active">#<?= (int)$repair['id'] ?></div>
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
    <!-- Repair Info -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header"><h4>Repair Details</h4></div>
        <div class="card-body">
          <table class="table table-sm">
            <tr><th width="35%">Customer</th><td><?= e($repair['customer_name']) ?></td></tr>
            <tr><th>Phone</th><td><?= e($repair['customer_phone'] ?? '-') ?></td></tr>
            <tr><th>Device</th><td><?= e($repair['device_model']) ?></td></tr>
            <tr><th>Passcode</th><td><?= e($repair['device_passcode'] ?? '-') ?></td></tr>
            <tr><th>Fault</th><td><?= e($repair['fault_description']) ?></td></tr>
            <tr><th>Cost</th><td class="font-weight-bold"><?= format_naira($repair['repair_cost']) ?></td></tr>
            <tr><th>Status</th><td><?= status_badge($repair['status']) ?></td></tr>
            <tr><th>Created By</th><td><?= e($repair['created_by'] ?? 'N/A') ?></td></tr>
            <tr><th>Created</th><td><?= e(date('M d, Y h:i A', strtotime($repair['created_at']))) ?></td></tr>
            <tr><th>Last Updated</th><td><?= e(date('M d, Y h:i A', strtotime($repair['updated_at']))) ?></td></tr>
          </table>
        </div>
      </div>

      <!-- Parts Used -->
      <div class="card">
        <div class="card-header"><h4>Parts Used</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>Part</th><th>Qty</th><th>Unit Cost</th><th>Total</th></tr></thead>
              <tbody>
                <?php if (empty($parts)): ?>
                  <tr><td colspan="4" class="text-center text-muted">No parts added yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($parts as $part): ?>
                  <tr>
                    <td><?= e($part['product_name'] ?? 'Unknown') ?></td>
                    <td><?= (int)$part['quantity'] ?></td>
                    <td><?= format_naira($part['unit_cost']) ?></td>
                    <td><?= format_naira($part['line_total']) ?></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php if ($repair['status'] !== 'collected'): ?>
        <div class="card-footer">
          <form method="POST" class="form-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_part">
            <select name="product_id" id="part-search" class="form-control mr-2" style="width:300px" required></select>
            <input type="number" name="part_qty" class="form-control mr-2" value="1" min="1" style="width:80px">
            <button type="submit" class="btn btn-info"><i class="fas fa-plus"></i> Add Part</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status Workflow -->
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header"><h4>Update Status</h4></div>
        <div class="card-body">
          <!-- Status Timeline -->
          <div class="mb-4">
            <?php foreach ($statuses as $s): ?>
            <div class="d-flex align-items-center mb-2">
              <span class="badge badge-<?= $repair['status'] === $s ? (status_badge($s) ? 'primary' : 'dark') : 'light' ?> mr-2" style="width:20px;height:20px;border-radius:50%;padding:0;display:flex;align-items:center;justify-content:center;">
                <?php if (array_search($s, $statuses) < array_search($repair['status'], $statuses)): ?>
                  <i class="fas fa-check" style="font-size:10px;color:#28a745"></i>
                <?php elseif ($repair['status'] === $s): ?>
                  <i class="fas fa-circle" style="font-size:8px;color:#6777ef"></i>
                <?php endif; ?>
              </span>
              <span class="<?= $repair['status'] === $s ? 'font-weight-bold' : 'text-muted' ?>"><?= ucfirst($s) ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if ($repair['status'] !== 'collected'): ?>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_status">
            <div class="form-group">
              <label>Change Status To</label>
              <select name="status" class="form-control">
                <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $repair['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-sync-alt"></i> Update Status</button>
          </form>
          <?php else: ?>
          <div class="alert alert-success mb-0"><i class="fas fa-check-circle"></i> This repair has been collected.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>

<script>
$(document).ready(function() {
  $('#part-search').select2({
    placeholder: 'Search for a part...',
    allowClear: true,
    minimumInputLength: 1,
    ajax: {
      url: '<?= BASE_URL ?>api/product_search.php',
      dataType: 'json',
      delay: 300,
      data: function(params) { return { q: params.term }; },
      processResults: function(data) { return { results: data }; }
    },
    templateResult: function(item) {
      if (item.loading) return item.text;
      return $('<span>').text(item.text + ' — ₦' + parseFloat(item.price).toLocaleString() + ' (Stock: ' + item.stock + ')');
    }
  });
});
</script>
