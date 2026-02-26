<?php
/**
 * MarvelStore v2.0 — Role-Based Dashboard
 * Admin: KPI cards, revenue chart, low-stock alerts, recent sales
 * Staff: My sales stats, my recent sales, ranking
 * Technician: My repair stats, my active repairs
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo  = get_db();
$role = current_user('role');
$uid  = current_user('id');
$today = date('Y-m-d');
$month_start = date('Y-m-01');

// ═══════════════════════════════════════════════════════════════
// Common data for ALL roles
// ═══════════════════════════════════════════════════════════════
$total_products  = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$pending_repairs = $pdo->query("SELECT COUNT(*) FROM repairs WHERE status IN ('pending','repairing')")->fetchColumn();

// ═══════════════════════════════════════════════════════════════
// Admin KPI
// ═══════════════════════════════════════════════════════════════
if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $today_revenue = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(si.line_total - (si.cost_price * si.quantity)), 0)
        FROM sale_items si JOIN sales s ON s.id = si.sale_id WHERE DATE(s.created_at) = ?
    ");
    $stmt->execute([$today]);
    $today_profit = $stmt->fetchColumn();

    $low_stock     = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.quantity <= p.low_stock_threshold ORDER BY p.quantity ASC LIMIT 10")->fetchAll();
    $recent_sales  = $pdo->query("SELECT s.*, u.full_name as cashier FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 5")->fetchAll();

    // Staff leaderboard
    $leaderboard = $pdo->query("
        SELECT u.full_name, COUNT(s.id) as sale_count, COALESCE(SUM(s.total), 0) as total_revenue
        FROM users u LEFT JOIN sales s ON u.id = s.user_id AND DATE(s.created_at) >= '{$month_start}'
        WHERE u.role IN ('admin','staff') AND u.is_active = 1
        GROUP BY u.id ORDER BY total_revenue DESC LIMIT 5
    ")->fetchAll();
}

// ═══════════════════════════════════════════════════════════════
// Staff KPI
// ═══════════════════════════════════════════════════════════════
if ($role === 'staff') {
    $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total), 0) FROM sales WHERE user_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$uid, $today]);
    [$my_sales_today, $my_revenue_today] = $stmt->fetch(PDO::FETCH_NUM);

    $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total), 0) FROM sales WHERE user_id = ? AND DATE(created_at) >= ?");
    $stmt->execute([$uid, $month_start]);
    [$my_sales_month, $my_revenue_month] = $stmt->fetch(PDO::FETCH_NUM);

    $my_recent_sales = $pdo->prepare("SELECT s.* FROM sales s WHERE s.user_id = ? ORDER BY s.created_at DESC LIMIT 10");
    $my_recent_sales->execute([$uid]);
    $my_recent_sales = $my_recent_sales->fetchAll();

    // My ranking
    $rank_query = $pdo->query("
        SELECT u.id, u.full_name, COALESCE(SUM(s.total), 0) as total
        FROM users u LEFT JOIN sales s ON u.id = s.user_id AND DATE(s.created_at) >= '{$month_start}'
        WHERE u.role IN ('admin','staff') AND u.is_active = 1
        GROUP BY u.id ORDER BY total DESC
    ")->fetchAll();
    $my_rank = 0;
    foreach ($rank_query as $i => $r) {
        if ((int)$r['id'] === (int)$uid) { $my_rank = $i + 1; break; }
    }
}

// ═══════════════════════════════════════════════════════════════
// Technician KPI
// ═══════════════════════════════════════════════════════════════
if ($role === 'technician') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE user_id = ? AND status IN ('pending','repairing')");
    $stmt->execute([$uid]);
    $my_active_repairs = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE user_id = ? AND status IN ('ready','collected') AND DATE(updated_at) >= ?");
    $stmt->execute([$uid, $month_start]);
    $my_completed_month = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE user_id = ?");
    $stmt->execute([$uid]);
    $my_total_repairs = $stmt->fetchColumn();

    $my_repairs = $pdo->prepare("SELECT r.* FROM repairs r WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 10");
    $my_repairs->execute([$uid]);
    $my_repairs = $my_repairs->fetchAll();
}

// ── Page Setup ────────────────────────────────────────────────────
$page_title = 'Dashboard';
$current_page = 'index.php';
$extra_css = [];
$extra_js = ($role === 'admin') ? [OTIKA_ASSETS . 'bundles/apexcharts/apexcharts.min.js'] : [];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>Dashboard</h1>
  <div class="section-header-breadcrumb">
    <div class="breadcrumb-item">Welcome, <?= e(current_user('full_name')) ?> <span class="badge badge-primary"><?= ucfirst(e($role)) ?></span></div>
  </div>
</div>

<div class="section-body">
  <?= render_flash() ?>

<?php if ($role === 'admin'): ?>
  <!-- ══════════ ADMIN DASHBOARD ══════════ -->
  <div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-primary"><i class="fas fa-money-bill-wave"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Today's Revenue</h4></div>
          <div class="card-body"><?= format_naira($today_revenue) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success"><i class="fas fa-chart-line"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Today's Profit</h4></div>
          <div class="card-body"><?= format_naira($today_profit) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-warning"><i class="fas fa-boxes"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Total Products</h4></div>
          <div class="card-body"><?= (int)$total_products ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-danger"><i class="fas fa-tools"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Pending Repairs</h4></div>
          <div class="card-body"><?= (int)$pending_repairs ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h4>Revenue — Last 7 Days</h4></div>
        <div class="card-body"><div id="revenue-chart"></div></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h4><i class="fas fa-exclamation-triangle text-warning"></i> Low Stock</h4></div>
        <div class="card-body p-0">
          <?php if (empty($low_stock)): ?>
            <div class="p-3 text-muted text-center">All stock levels are healthy.</div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>Product</th><th>Stock</th></tr></thead>
              <tbody>
                <?php foreach ($low_stock as $item): ?>
                <tr>
                  <td><?= e($item['name']) ?></td>
                  <td><span class="badge badge-<?= $item['quantity'] <= 0 ? 'danger' : 'warning' ?>"><?= (int)$item['quantity'] ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h4>Recent Sales</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>#</th><th>Date</th><th>Cashier</th><th>Payment</th><th>Total</th><th></th></tr></thead>
              <tbody>
                <?php if (empty($recent_sales)): ?>
                  <tr><td colspan="6" class="text-center text-muted">No sales yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($recent_sales as $sale): ?>
                  <tr>
                    <td><?= (int)$sale['id'] ?></td>
                    <td><?= e(date('M d, h:ia', strtotime($sale['created_at']))) ?></td>
                    <td><?= e($sale['cashier'] ?? 'N/A') ?></td>
                    <td><span class="badge badge-info"><?= ucfirst(e($sale['payment_method'])) ?></span></td>
                    <td><?= format_naira($sale['total']) ?></td>
                    <td><a href="sale_receipt.php?id=<?= (int)$sale['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-receipt"></i></a></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h4><i class="fas fa-trophy text-warning"></i> Staff Leaderboard (This Month)</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>#</th><th>Name</th><th>Sales</th><th>Revenue</th></tr></thead>
              <tbody>
                <?php foreach ($leaderboard as $i => $lb): ?>
                <tr>
                  <td><?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ($i + 1))) ?></td>
                  <td><?= e($lb['full_name']) ?></td>
                  <td><?= (int)$lb['sale_count'] ?></td>
                  <td><?= format_naira($lb['total_revenue']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($role === 'staff'): ?>
  <!-- ══════════ STAFF DASHBOARD ══════════ -->
  <div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-primary"><i class="fas fa-shopping-cart"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>My Sales Today</h4></div>
          <div class="card-body"><?= (int)$my_sales_today ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success"><i class="fas fa-money-bill-wave"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>My Revenue Today</h4></div>
          <div class="card-body"><?= format_naira($my_revenue_today) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-info"><i class="fas fa-chart-bar"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>My Sales This Month</h4></div>
          <div class="card-body"><?= (int)$my_sales_month ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-warning"><i class="fas fa-trophy"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>My Ranking</h4></div>
          <div class="card-body">#<?= $my_rank ?: '-' ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body text-center">
          <h5 class="text-muted">Month Revenue</h5>
          <h2 class="font-weight-bold text-primary"><?= format_naira($my_revenue_month) ?></h2>
          <p class="text-muted"><?= date('F Y') ?></p>
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h4>My Recent Sales</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>#</th><th>Date</th><th>Payment</th><th>Total</th><th></th></tr></thead>
              <tbody>
                <?php if (empty($my_recent_sales)): ?>
                  <tr><td colspan="5" class="text-center text-muted">No sales yet. Start selling!</td></tr>
                <?php else: ?>
                  <?php foreach ($my_recent_sales as $sale): ?>
                  <tr>
                    <td><?= (int)$sale['id'] ?></td>
                    <td><?= e(date('M d, h:ia', strtotime($sale['created_at']))) ?></td>
                    <td><span class="badge badge-info"><?= ucfirst(e($sale['payment_method'])) ?></span></td>
                    <td><?= format_naira($sale['total']) ?></td>
                    <td><a href="sale_receipt.php?id=<?= (int)$sale['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-receipt"></i></a></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($role === 'technician'): ?>
  <!-- ══════════ TECHNICIAN DASHBOARD ══════════ -->
  <div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-danger"><i class="fas fa-wrench"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>My Active Repairs</h4></div>
          <div class="card-body"><?= (int)$my_active_repairs ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Completed (Month)</h4></div>
          <div class="card-body"><?= (int)$my_completed_month ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-info"><i class="fas fa-tools"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Total Repairs</h4></div>
          <div class="card-body"><?= (int)$my_total_repairs ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-warning"><i class="fas fa-boxes"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Total Products</h4></div>
          <div class="card-body"><?= (int)$total_products ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header"><h4>My Repairs</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>#</th><th>Date</th><th>Customer</th><th>Device</th><th>Status</th><th>Cost</th><th></th></tr></thead>
              <tbody>
                <?php if (empty($my_repairs)): ?>
                  <tr><td colspan="7" class="text-center text-muted">No repairs assigned.</td></tr>
                <?php else: ?>
                  <?php foreach ($my_repairs as $r): ?>
                  <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= e(date('M d', strtotime($r['created_at']))) ?></td>
                    <td><?= e($r['customer_name']) ?></td>
                    <td><?= e($r['device_model']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td><?= format_naira($r['repair_cost']) ?></td>
                    <td><a href="repair_view.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>

<?php if ($role === 'admin'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  fetch('<?= BASE_URL ?>api/dashboard_data.php')
    .then(r => r.json())
    .then(data => {
      new ApexCharts(document.querySelector("#revenue-chart"), {
        chart: { type: 'area', height: 300, toolbar: { show: false } },
        series: [{ name: 'Revenue', data: data.revenue }],
        xaxis: { categories: data.labels },
        colors: ['#6777ef'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
        tooltip: { y: { formatter: v => '₦' + v.toLocaleString() } },
        dataLabels: { enabled: false }
      }).render();
    })
    .catch(() => {
      document.querySelector("#revenue-chart").innerHTML = '<p class="text-muted text-center">Could not load chart data.</p>';
    });
});
</script>
<?php endif; ?>
