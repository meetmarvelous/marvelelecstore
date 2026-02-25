<?php
/**
 * MarvelStore v1.0 — Sidebar Partial
 * Decomposed from new_dashboard.php (Gold Standard)
 * 
 * Required variables before include:
 *   $current_page (string) — basename of current page for active highlighting
 */
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
$user_role = current_user('role');

// Helper to check if menu item is active
function is_active(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}
function is_menu_open(array $pages, string $current): string {
    return in_array($current, $pages) ? 'active' : '';
}
?>
      <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
          <div class="sidebar-brand">
            <a href="<?= BASE_URL ?>index.php"> <span class="logo-name">MARVELSTORE</span></a>
          </div>
          <ul class="sidebar-menu">

            <!-- ── Main ─────────────────────────────────────────── -->
            <li class="menu-header">Main</li>
            <li class="<?= is_active('index.php', $current_page) ?>">
              <a href="<?= BASE_URL ?>index.php" class="nav-link"><i data-feather="monitor"></i><span>Dashboard</span></a>
            </li>

            <!-- ── Inventory ────────────────────────────────────── -->
            <li class="menu-header">Inventory</li>
            <li class="dropdown <?= is_menu_open(['products.php','product_add.php','product_edit.php','categories.php'], $current_page) ?>">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="package"></i><span>Inventory</span></a>
              <ul class="dropdown-menu">
                <li class="<?= is_active('products.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>products.php">All Products</a></li>
                <li class="<?= is_active('product_add.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>product_add.php">Add Product</a></li>
                <li class="<?= is_active('categories.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>categories.php">Categories</a></li>
              </ul>
            </li>

            <!-- ── Sales / POS ──────────────────────────────────── -->
            <li class="menu-header">Sales</li>
            <li class="dropdown <?= is_menu_open(['sale_new.php','sales.php','sale_receipt.php'], $current_page) ?>">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="shopping-bag"></i><span>Sales</span></a>
              <ul class="dropdown-menu">
                <li class="<?= is_active('sale_new.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>sale_new.php">New Sale</a></li>
                <li class="<?= is_active('sales.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>sales.php">Sale History</a></li>
              </ul>
            </li>

            <!-- ── Repairs ──────────────────────────────────────── -->
            <li class="menu-header">Repairs</li>
            <li class="dropdown <?= is_menu_open(['repair_add.php','repairs.php','repair_view.php'], $current_page) ?>">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="briefcase"></i><span>Repairs</span></a>
              <ul class="dropdown-menu">
                <li class="<?= is_active('repair_add.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>repair_add.php">New Repair</a></li>
                <li class="<?= is_active('repairs.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>repairs.php">All Repairs</a></li>
              </ul>
            </li>

            <?php if ($user_role === 'admin'): ?>
            <!-- ── Reports (Admin) ──────────────────────────────── -->
            <li class="menu-header">Reports</li>
            <li class="<?= is_active('reports.php', $current_page) ?>">
              <a href="<?= BASE_URL ?>reports.php" class="nav-link"><i data-feather="bar-chart"></i><span>Reports</span></a>
            </li>

            <!-- ── User Management (Admin) ──────────────────────── -->
            <li class="menu-header">Management</li>
            <li class="dropdown <?= is_menu_open(['users.php','user_add.php','user_edit.php'], $current_page) ?>">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="user"></i><span>Users</span></a>
              <ul class="dropdown-menu">
                <li class="<?= is_active('users.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>users.php">All Users</a></li>
                <li class="<?= is_active('user_add.php', $current_page) ?>"><a class="nav-link" href="<?= BASE_URL ?>user_add.php">Add User</a></li>
              </ul>
            </li>
            <?php endif; ?>

          </ul>
        </aside>
      </div>

      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
