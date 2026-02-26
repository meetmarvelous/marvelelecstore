<?php
/**
 * MarvelStore v1.0 — Sales History
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo = get_db();

$sales = $pdo->query("
    SELECT s.*, u.full_name as cashier,
        (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.created_at DESC
")->fetchAll();

$page_title = 'Sales History';
$current_page = 'sales.php';
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
  <h1>Sales History</h1>
  <div class="section-header-button">
    <a href="sale_new.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Sale</a>
  </div>
</div>

<div class="section-body">
  <?= render_flash() ?>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped" id="sales-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Cashier</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Payment</th>
              <th>Discount</th>
              <th>Total</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sales as $s): ?>
            <tr>
              <td><?= (int)$s['id'] ?></td>
              <td><?= e(date('M d, Y h:ia', strtotime($s['created_at']))) ?></td>
              <td><?= e($s['cashier'] ?? 'N/A') ?></td>
              <td><?= !empty($s['customer_name']) ? e($s['customer_name']) : '<span class="text-muted">—</span>' ?></td>
              <td><span class="badge badge-light"><?= (int)$s['item_count'] ?></span></td>
              <td><span class="badge badge-info"><?= ucfirst(e($s['payment_method'])) ?></span></td>
              <td><?= format_naira($s['discount']) ?></td>
              <td class="font-weight-bold"><?= format_naira($s['total']) ?></td>
              <td><a href="sale_receipt.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-receipt"></i> Receipt</a></td>
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
  $('#sales-table').DataTable({ "order": [[0, "desc"]] });
});
</script>
