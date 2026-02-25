<?php
/**
 * MarvelStore v1.0 — Reports (Admin Only)
 * Date-range revenue/profit analysis, staff leaderboard.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_role('admin');

$pdo = get_db();

// Date range defaults
$from = input_str('from') ?: date('Y-m-01');
$to   = input_str('to')   ?: date('Y-m-d');

// Revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$revenue = $stmt->fetchColumn();

// Total Cost (from sale_items)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(si.cost_price * si.quantity), 0)
    FROM sale_items si
    JOIN sales s ON s.id = si.sale_id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$total_cost = $stmt->fetchColumn();
$profit = $revenue - $total_cost;

// Total Sales count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$sale_count = $stmt->fetchColumn();

// Repairs Revenue
$stmt = $pdo->prepare("SELECT COALESCE(SUM(repair_cost), 0) FROM repairs WHERE status = 'collected' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$repair_revenue = $stmt->fetchColumn();

// Staff Leaderboard (Sales)
$stmt = $pdo->prepare("
    SELECT u.full_name, COUNT(s.id) as sale_count, COALESCE(SUM(s.total), 0) as total_sales
    FROM sales s
    JOIN users u ON s.user_id = u.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY s.user_id
    ORDER BY total_sales DESC
    LIMIT 10
");
$stmt->execute([$from, $to]);
$leaderboard = $stmt->fetchAll();

// Daily revenue for chart (within range)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as day, SUM(total) as rev
    FROM sales
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY day
");
$stmt->execute([$from, $to]);
$daily = $stmt->fetchAll();
$chart_labels = array_column($daily, 'day');
$chart_data   = array_column($daily, 'rev');

$page_title = 'Reports';
$current_page = 'reports.php';
$extra_css = [
    OTIKA_ASSETS . 'bundles/bootstrap-daterangepicker/daterangepicker.css',
];
$extra_js = [
    OTIKA_ASSETS . 'bundles/bootstrap-daterangepicker/daterangepicker.js',
    OTIKA_ASSETS . 'bundles/apexcharts/apexcharts.min.js',
];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>Reports</h1>
</div>

<div class="section-body">
  <!-- Date Range Filter -->
  <div class="card">
    <div class="card-body">
      <form method="GET" class="form-inline">
        <div class="form-group mr-2">
          <label class="mr-2">From</label>
          <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
        </div>
        <div class="form-group mr-2">
          <label class="mr-2">To</label>
          <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      </form>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row">
    <div class="col-lg-3 col-md-6">
      <div class="card card-statistic-1">
        <div class="card-icon bg-primary"><i class="fas fa-money-bill-wave"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Revenue (Sales)</h4></div>
          <div class="card-body"><?= format_naira($revenue) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success"><i class="fas fa-chart-line"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Profit</h4></div>
          <div class="card-body"><?= format_naira($profit) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card card-statistic-1">
        <div class="card-icon bg-info"><i class="fas fa-receipt"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Total Sales</h4></div>
          <div class="card-body"><?= (int)$sale_count ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card card-statistic-1">
        <div class="card-icon bg-warning"><i class="fas fa-tools"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Repair Revenue</h4></div>
          <div class="card-body"><?= format_naira($repair_revenue) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <!-- Chart -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h4>Daily Revenue</h4></div>
        <div class="card-body">
          <div id="report-chart"></div>
        </div>
      </div>
    </div>
    <!-- Leaderboard -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h4><i class="fas fa-trophy text-warning"></i> Staff Leaderboard</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0">
              <thead><tr><th>Staff</th><th>Sales</th><th>Revenue</th></tr></thead>
              <tbody>
                <?php if (empty($leaderboard)): ?>
                  <tr><td colspan="3" class="text-center text-muted">No data for this period.</td></tr>
                <?php else: ?>
                  <?php foreach ($leaderboard as $l): ?>
                  <tr>
                    <td><?= e($l['full_name']) ?></td>
                    <td><?= (int)$l['sale_count'] ?></td>
                    <td><?= format_naira($l['total_sales']) ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  var labels = <?= json_encode($chart_labels) ?>;
  var data   = <?= json_encode(array_map('floatval', $chart_data)) ?>;

  if (labels.length > 0) {
    new ApexCharts(document.querySelector("#report-chart"), {
      chart: { type: 'bar', height: 300, toolbar: { show: false } },
      series: [{ name: 'Revenue', data: data }],
      xaxis: { categories: labels },
      colors: ['#6777ef'],
      plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
      tooltip: { y: { formatter: function(v) { return '₦' + v.toLocaleString(); } } },
      dataLabels: { enabled: false }
    }).render();
  } else {
    document.querySelector("#report-chart").innerHTML = '<p class="text-muted text-center">No data for this period.</p>';
  }
});
</script>
