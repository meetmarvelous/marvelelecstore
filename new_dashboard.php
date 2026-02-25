<?php
/**
 * MarvelStore — WORKING PROTOTYPE (New Dashboard)
 * This file contains the verified structure for UI interactivity.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>PROTOTYPE — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/app.min.css">
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/style.css">
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/components.css">
  <link rel="stylesheet" href="<?= OTIKA_ASSETS ?>css/custom.css">
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
      </nav>
      <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
          <div class="sidebar-brand">
            <a href="#"> <span class="logo-name">MARVELSTORE</span></a>
          </div>
          <ul class="sidebar-menu">
            <li class="menu-header">Main</li>
            <li class="dropdown active">
              <a href="#" class="nav-link"><i data-feather="monitor"></i><span>Dashboard</span></a>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="package"></i><span>Inventory</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="#">All Products</a></li>
                <li><a class="nav-link" href="#">Add Product</a></li>
              </ul>
            </li>
          </ul>
        </aside>
      </div>
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>PROTOTYPE DASHBOARD</h1>
          </div>
          <div class="section-body">
            <div class="card">
              <div class="card-body">
                <h5>UI Verification State</h5>
                <p>Hamburger: Verified Working</p>
                <p>Sidebar Dropdowns: Verified Working</p>
                <p>White Aesthetic: Verified Working</p>
                <hr>
                <p>Use <code>prompt.md</code> to rebuild the rest of the system modules based on this structure.</p>
              </div>
            </div>
          </div>
        </section>
      </div>
      <footer class="main-footer">
        <div class="footer-left">&copy; <?= date('Y') ?> <?= APP_NAME ?></div>
      </footer>
    </div>
  </div>
  <!-- General JS Scripts -->
  <script src="<?= OTIKA_ASSETS ?>js/app.min.js"></script>
  <!-- Template JS File -->
  <script src="<?= OTIKA_ASSETS ?>js/scripts.js"></script>
  <!-- Custom JS File -->
  <script src="<?= OTIKA_ASSETS ?>js/custom.js"></script>
</body>
</html>
