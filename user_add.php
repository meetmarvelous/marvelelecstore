<?php
/**
 * MarvelStore v1.0 — Add User (Admin Only)
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_role('admin');

$pdo = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $username  = input_str('username');
        $full_name = input_str('full_name');
        $password  = input_str('password');
        $role      = input_str('role');

        if (empty($username))  $errors[] = 'Username is required.';
        if (empty($full_name)) $errors[] = 'Full name is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if (!in_array($role, ['admin','staff','technician'])) $errors[] = 'Invalid role.';

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $full_name, $hash, $role]);
                log_activity('user_create', 'user', (int)$pdo->lastInsertId(), "Created user '{$username}' ({$role})");
                set_flash('success', 'User created successfully.');
                redirect('users.php');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = 'Username already exists.';
                } else {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'Add User';
$current_page = 'user_add.php';
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>Add User</h1>
  <div class="section-header-breadcrumb">
    <div class="breadcrumb-item"><a href="users.php">Users</a></div>
    <div class="breadcrumb-item active">Add New</div>
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
              <label>Username <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control" value="<?= e(input_str('username')) ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Full Name <span class="text-danger">*</span></label>
              <input type="text" name="full_name" class="form-control" value="<?= e(input_str('full_name')) ?>" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Password <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control" required minlength="6">
              <small class="form-text text-muted">Minimum 6 characters.</small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Role <span class="text-danger">*</span></label>
              <select name="role" class="form-control" required>
                <option value="staff" <?= input_str('role') === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="technician" <?= input_str('role') === 'technician' ? 'selected' : '' ?>>Technician</option>
                <option value="admin" <?= input_str('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer text-right">
        <a href="users.php" class="btn btn-secondary mr-1">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User</button>
      </div>
    </form>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
