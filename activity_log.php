<?php
/**
 * MarvelStore v2.0 — Activity Log Viewer (Admin Only)
 * Non-deletable audit trail with filters by user, action type, and date range.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_role('admin');

$pdo = get_db();

// Filters
$filter_user   = input_int('user_id');
$filter_action = input_str('action');
$filter_from   = input_str('from') ?: date('Y-m-d', strtotime('-7 days'));
$filter_to     = input_str('to')   ?: date('Y-m-d');

// Build query
$where = ["DATE(al.created_at) BETWEEN ? AND ?"];
$params = [$filter_from, $filter_to];

if ($filter_user) {
    $where[] = "al.user_id = ?";
    $params[] = $filter_user;
}
if ($filter_action) {
    $where[] = "al.action = ?";
    $params[] = $filter_action;
}

$where_sql = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT al.*, u.full_name, u.username
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE {$where_sql}
    ORDER BY al.created_at DESC
    LIMIT 500
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get users for filter dropdown
$users = $pdo->query("SELECT id, full_name, username FROM users ORDER BY full_name")->fetchAll();

// Get distinct actions for filter
$actions = $pdo->query("SELECT DISTINCT action FROM activity_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Activity Log';
$current_page = 'activity_log.php';
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
  <h1><i class="fas fa-history"></i> Activity Log</h1>
</div>

<div class="section-body">
  <!-- Filters -->
  <div class="card">
    <div class="card-body">
      <form method="GET" class="form-inline flex-wrap">
        <div class="form-group mr-2 mb-2">
          <label class="mr-1">User</label>
          <select name="user_id" class="form-control">
            <option value="">All Users</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?> (<?= e($u['username']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group mr-2 mb-2">
          <label class="mr-1">Action</label>
          <select name="action" class="form-control">
            <option value="">All Actions</option>
            <?php foreach ($actions as $a): ?>
            <option value="<?= e($a) ?>" <?= $filter_action === $a ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $a)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group mr-2 mb-2">
          <label class="mr-1">From</label>
          <input type="date" name="from" class="form-control" value="<?= e($filter_from) ?>">
        </div>
        <div class="form-group mr-2 mb-2">
          <label class="mr-1">To</label>
          <input type="date" name="to" class="form-control" value="<?= e($filter_to) ?>">
        </div>
        <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-filter"></i> Filter</button>
      </form>
    </div>
  </div>

  <!-- Log Table -->
  <div class="card">
    <div class="card-header">
      <h4>Showing <?= count($logs) ?> entries</h4>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0" id="log-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Date & Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Description</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr><td colspan="6" class="text-center text-muted">No activity found for this period.</td></tr>
            <?php else: ?>
              <?php foreach ($logs as $log): ?>
              <tr>
                <td><?= (int)$log['id'] ?></td>
                <td><small><?= e(date('M d, Y h:i:s A', strtotime($log['created_at']))) ?></small></td>
                <td><?= e($log['full_name'] ?? 'System') ?><br><small class="text-muted"><?= e($log['username'] ?? '') ?></small></td>
                <td><?= action_label($log['action']) ?></td>
                <td><?= e($log['description']) ?></td>
                <td><small class="text-muted"><?= e($log['ip_address']) ?></small></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>

<script>
$(document).ready(function() {
  $('#log-table').DataTable({ "order": [[0, "desc"]], "pageLength": 50 });
});
</script>
