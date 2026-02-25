<?php
/**
 * MarvelStore v1.0 — Header Partial
 * Decomposed from new_dashboard.php (Gold Standard)
 * 
 * Required variables before include:
 *   $page_title (string) — Page title
 *   $extra_css  (array, optional) — Additional CSS file paths
 */
$extra_css = $extra_css ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title><?= e($page_title ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
  <!-- General CSS Files -->
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/app.min.css">
  <!-- Template CSS -->
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/style.css">
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/components.css">
  <?php foreach ($extra_css as $css): ?>
  <link rel="stylesheet" href="<?= $css ?>">
  <?php endforeach; ?>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/custom.css">
  <link rel='shortcut icon' type='image/x-icon' href='<?= OTIKA_ASSETS ?>img/favicon.ico' />
</head>

<body class="light light-sidebar theme-white">
  <div class="loader"></div>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <div class="navbar-bg"></div>
      <nav class="navbar navbar-expand-lg main-navbar sticky">
        <div class="form-inline mr-auto">
          <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg collapse-btn"> <i data-feather="align-justify"></i></a></li>
            <li><a href="#" class="nav-link nav-link-lg fullscreen-btn">
                <i data-feather="maximize"></i>
              </a></li>
          </ul>
        </div>
        <ul class="navbar-nav navbar-right">
          <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
              <img alt="image" src="<?= OTIKA_ASSETS ?>img/user.png" class="user-img-radious-style">
              <span class="d-sm-none d-lg-inline-block"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right pullDown">
              <div class="dropdown-title"><?= e(current_user('full_name')) ?><br><small class="text-muted"><?= ucfirst(e(current_user('role'))) ?></small></div>
              <div class="dropdown-divider"></div>
              <a href="<?= BASE_URL ?>logout.php" class="dropdown-item has-icon text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
              </a>
            </div>
          </li>
        </ul>
      </nav>
