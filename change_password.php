<?php
/**
 * MarvelStore v2.0 — Change Password (Self-Service)
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
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $current  = input_str('current_password');
        $new      = input_str('new_password');
        $confirm  = input_str('confirm_password');

        // Validate
        if (empty($current) || empty($new) || empty($confirm)) {
            $errors[] = 'All fields are required.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([current_user('id')]);
            $hash = $stmt->fetchColumn();

            if (!password_verify($current, $hash)) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$new_hash, current_user('id')]);
                log_activity('password_change', 'user', current_user('id'), "Changed own password");
                $success = true;
            }
        }
    }
}

$page_title = 'Change Password';
$current_page = 'change_password.php';
$extra_css = [];
$extra_js = [];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1><i class="fas fa-key"></i> Change Password</h1>
</div>

<div class="section-body">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
      <div class="card">
        <div class="card-header"><h4>Update Your Password</h4></div>
        <div class="card-body">
          <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Password changed successfully!</div>
          <?php endif; ?>
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>

          <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
              <label>Current Password <span class="text-danger">*</span></label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
              <label>New Password <span class="text-danger">*</span></label>
              <input type="password" name="new_password" class="form-control" required minlength="6">
              <small class="text-muted">Minimum 6 characters</small>
            </div>
            <div class="form-group">
              <label>Confirm New Password <span class="text-danger">*</span></label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Change Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
