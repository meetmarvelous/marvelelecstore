<?php
/**
 * MarvelStore v1.0 — User Management (Admin Only)
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_role('admin');

$pdo = get_db();

// Handle toggle active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && input_str('action') === 'toggle') {
    if (validate_csrf(input_str('csrf_token'))) {
        $uid = input_int('id');
        if ($uid != current_user('id')) { // Don't deactivate self
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$uid]);
            log_activity('user_toggle', 'user', $uid, "Toggled user status (id={$uid})");
            set_flash('success', 'User status updated.');
        } else {
            set_flash('danger', 'You cannot deactivate your own account.');
        }
    }
    redirect('users.php');
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

$page_title = 'Users';
$current_page = 'users.php';
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
  <h1>User Management</h1>
  <div class="section-header-button">
    <a href="user_add.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</a>
  </div>
</div>

<div class="section-body">
  <?= render_flash() ?>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped" id="users-table">
          <thead>
            <tr><th>#</th><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= e($u['username']) ?></td>
              <td><?= e($u['full_name']) ?></td>
              <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'technician' ? 'info' : 'primary') ?>"><?= ucfirst(e($u['role'])) ?></span></td>
              <td>
                <?php if ($u['is_active']): ?>
                  <span class="badge badge-success">Active</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Inactive</span>
                <?php endif; ?>
              </td>
              <td><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
              <td>
                <a href="user_edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                <?php if ($u['id'] != current_user('id')): ?>
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-<?= $u['is_active'] ? 'warning' : 'success' ?>" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                    <i class="fas fa-<?= $u['is_active'] ? 'user-slash' : 'user-check' ?>"></i>
                  </button>
                </form>
                <?php endif; ?>
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
$(document).ready(function() { $('#users-table').DataTable(); });
</script>
