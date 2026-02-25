<?php
/**
 * MarvelStore v1.0 — Edit User (Admin Only)
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_role('admin');

$pdo = get_db();
$id = input_int('id');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'User not found.');
    redirect('users.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $full_name = input_str('full_name');
        $role      = input_str('role');
        $password  = input_str('password');

        if (empty($full_name)) $errors[] = 'Full name is required.';
        if (!in_array($role, ['admin','staff','technician'])) $errors[] = 'Invalid role.';
        if (!empty($password) && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

        if (empty($errors)) {
            try {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name=?, role=?, password_hash=? WHERE id=?");
                    $stmt->execute([$full_name, $role, $hash, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name=?, role=? WHERE id=?");
                    $stmt->execute([$full_name, $role, $id]);
                }
                set_flash('success', 'User updated successfully.');
                redirect('users.php');
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
        $user['full_name'] = $full_name;
        $user['role'] = $role;
    }
}

$page_title = 'Edit User';
$current_page = 'user_edit.php';
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>Edit User</h1>
  <div class="section-header-breadcrumb">
    <div class="breadcrumb-item"><a href="users.php">Users</a></div>
    <div class="breadcrumb-item active">Edit</div>
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
              <label>Username</label>
              <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
              <small class="form-text text-muted">Username cannot be changed.</small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Full Name <span class="text-danger">*</span></label>
              <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>New Password</label>
              <input type="password" name="password" class="form-control" minlength="6">
              <small class="form-text text-muted">Leave blank to keep current password.</small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Role <span class="text-danger">*</span></label>
              <select name="role" class="form-control" required>
                <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="technician" <?= $user['role'] === 'technician' ? 'selected' : '' ?>>Technician</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer text-right">
        <a href="users.php" class="btn btn-secondary mr-1">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
      </div>
    </form>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
