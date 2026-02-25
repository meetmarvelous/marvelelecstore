<?php
/**
 * MarvelStore v1.0 — Login Page
 * Rebuilt Afresh from Otika/auth-login.html
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'db.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $error = 'Invalid CSRF token.';
    } else {
        $username = input_str('username');
        $password = input_str('password');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $pdo = get_db();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Success!
                login_user($user);
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                redirect('index.php');
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Login — <?= APP_NAME ?></title>
  <!-- General CSS Files -->
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/app.min.css">
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>bundles/bootstrap-social/bootstrap-social.css">
  <!-- Template CSS -->
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/style.css">
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/components.css">
  <!-- Custom style CSS -->
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/custom.css">
  <link rel='shortcut icon' type='image/x-icon' href='<?= OTIKA_ASSETS ?>img/favicon.ico' />
</head>

<body>
  <div class="loader"></div>
  <div id="app">
    <section class="section">
      <div class="container mt-5">
        <div class="row">
          <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
            <div class="login-brand">
               <img src="<?= OTIKA_ASSETS ?>img/logo.png" alt="logo" width="100" class="shadow-light rounded-circle">
            </div>
            <div class="card card-primary">
              <div class="card-header">
                <h4>Login</h4>
              </div>
              <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <?= render_flash() ?>

                <form method="POST" action="login.php" class="needs-validation" novalidate="">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <div class="form-group">
                    <label for="username">Username</label>
                    <input id="username" type="text" class="form-control" name="username" tabindex="1" required autofocus>
                    <div class="invalid-feedback">
                      Please fill in your username
                    </div>
                  </div>
                  <div class="form-group">
                    <div class="d-block">
                      <label for="password" class="control-label">Password</label>
                    </div>
                    <input id="password" type="password" class="form-control" name="password" tabindex="2" required>
                    <div class="invalid-feedback">
                      please fill in your password
                    </div>
                  </div>
                  <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                      Login
                    </button>
                  </div>
                </form>
              </div>
            </div>
            <div class="mt-5 text-muted text-center">
              MarvelStore v<?= APP_VERSION ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
  <!-- General JS Scripts -->
  <script src="<?= OTIKA_ASSETS ?>js/app.min.js"></script>
  <!-- Template JS File -->
  <script src="<?= OTIKA_ASSETS ?>js/scripts.js"></script>
  <!-- Custom JS File -->
  <script src="<?= OTIKA_ASSETS ?>js/custom.js"></script>
</body>
</html>
