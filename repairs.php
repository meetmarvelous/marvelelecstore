<?php
/**
 * MarvelStore v1.0 — All Repairs
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo = get_db();

// Status filter
$status_filter = input_str('status');
$allowed_statuses = ['pending', 'repairing', 'ready', 'collected'];
if ($status_filter && !in_array($status_filter, $allowed_statuses)) {
    $status_filter = null;
}

if ($status_filter) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name as created_by
        FROM repairs r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.status = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$status_filter]);
    $repairs = $stmt->fetchAll();
} else {
    $repairs = $pdo->query("
        SELECT r.*, u.full_name as created_by
        FROM repairs r
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ")->fetchAll();
}

$page_title = $status_filter ? ucfirst($status_filter) . ' Repairs' : 'All Repairs';
$current_page = 'repairs.php';
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
  <h1><?= $status_filter ? ucfirst(e($status_filter)) . ' Repairs' : 'All Repairs' ?></h1>
  <div class="section-header-button">
    <?php if ($status_filter): ?>
    <a href="repairs.php" class="btn btn-outline-secondary mr-2"><i class="fas fa-times"></i> Clear Filter</a>
    <?php endif; ?>
    <a href="repair_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Repair</a>
  </div>
</div>

<div class="section-body">
  <?= render_flash() ?>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped" id="repairs-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Code</th>
              <th>Customer</th>
              <th>Device</th>
              <th>Status</th>
              <th>Cost</th>
              <th>Created</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($repairs as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><span class="badge badge-primary" style="letter-spacing:1px"><?= e($r['repair_code'] ?? '—') ?></span></td>
              <td><?= e($r['customer_name']) ?><br><small class="text-muted"><?= e($r['customer_phone'] ?? '') ?></small></td>
              <td><?= e($r['device_model']) ?></td>
              <td><?= status_badge($r['status']) ?></td>
              <td><?= format_naira($r['repair_cost']) ?></td>
              <td><?= time_ago($r['created_at']) ?></td>
              <td>
                <a href="repair_view.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                <a href="repair_receipt.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-receipt"></i></a>
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
  $('#repairs-table').DataTable({ "order": [[0, "desc"]] });
});
</script>
