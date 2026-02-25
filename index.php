<?php
/**
 * MarvelStore v1.0 — Dashboard
 * KPI cards, revenue chart (ApexCharts), low-stock alerts, recent sales.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo = get_db();

// ── KPI Data ──────────────────────────────────────────────────────
$today = date('Y-m-d');

// Today's Revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$today_revenue = $stmt->fetchColumn();

// Today's Profit
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(si.line_total - (si.cost_price * si.quantity)), 0)
    FROM sale_items si
    JOIN sales s ON s.id = si.sale_id
    WHERE DATE(s.created_at) = ?
");
$stmt->execute([$today]);
$today_profit = $stmt->fetchColumn();

// Total Products
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Pending Repairs
$stmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE status IN ('pending', 'repairing')");
$stmt->execute();
$pending_repairs = $stmt->fetchColumn();

// Low Stock Items
$low_stock = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.quantity <= p.low_stock_threshold ORDER BY p.quantity ASC LIMIT 10")->fetchAll();

// Recent Sales
$recent_sales = $pdo->query("SELECT s.*, u.full_name as cashier FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 5")->fetchAll();

// ── Page Setup ────────────────────────────────────────────────────
$page_title = 'Dashboard';
$current_page = 'index.php';
$extra_css = [];
$extra_js = [OTIKA_ASSETS . 'bundles/apexcharts/apexcharts.min.js'];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<!-- Section Header -->
<div class="section-header">
  <h1>Dashboard</h1>
</div>

<div class="section-body">

  <!-- KPI Cards -->
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

  <!-- Revenue Chart -->
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h4>Revenue — Last 7 Days</h4>
        </div>
        <div class="card-body">
          <div id="revenue-chart"></div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">
          <h4><i class="fas fa-exclamation-triangle text-warning"></i> Low Stock Alerts</h4>
        </div>
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

  <!-- Recent Sales -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4>Recent Sales</h4>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Date</th>
                  <th>Cashier</th>
                  <th>Payment</th>
                  <th>Total</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recent_sales)): ?>
                  <tr><td colspan="6" class="text-center text-muted">No sales recorded yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($recent_sales as $sale): ?>
                  <tr>
                    <td><?= (int)$sale['id'] ?></td>
                    <td><?= e(date('M d, Y h:ia', strtotime($sale['created_at']))) ?></td>
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
  </div>

</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>

<!-- Page-specific JS: ApexCharts init (AFTER footer since base libs loaded there) -->
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
