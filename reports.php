<?php
/**
 * MarvelStore v2.0 — Enhanced Reports (Admin Only)
 * Date range filtering, summary cards, multiple charts, staff performance table.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_role('admin');

$pdo = get_db();

// Date range
$from = input_str('from') ?: date('Y-m-01');
$to   = input_str('to')   ?: date('Y-m-d');

// Summary stats
$stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(discount),0) as discounts FROM sales WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$sales_summary = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(si.line_total - (si.cost_price * si.quantity)), 0)
    FROM sale_items si JOIN sales s ON s.id = si.sale_id WHERE DATE(s.created_at) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$total_profit = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM repairs WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$repair_count = $stmt->fetchColumn();

// Inventory value
$inv = $pdo->query("SELECT COALESCE(SUM(quantity * cost_price),0) as cost_val, COALESCE(SUM(quantity * selling_price),0) as retail_val, COALESCE(SUM(quantity),0) as units FROM products")->fetch();

// Daily revenue for chart
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as day, COALESCE(SUM(total),0) as total
    FROM sales WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at) ORDER BY day
");
$stmt->execute([$from, $to]);
$daily = $stmt->fetchAll();

$page_title = 'Reports';
$current_page = 'reports.php';
$extra_css = [];
$extra_js = [OTIKA_ASSETS . 'bundles/apexcharts/apexcharts.min.js'];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1><i class="fas fa-chart-pie"></i> Reports</h1>
</div>

<div class="section-body">

  <!-- Date Range Filter -->
  <div class="card">
    <div class="card-body">
      <form method="GET" class="form-inline">
        <div class="form-group mr-2">
          <label class="mr-1">From</label>
          <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
        </div>
        <div class="form-group mr-2">
          <label class="mr-1">To</label>
          <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
      </form>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row">
    <div class="col-lg-3 col-md-6">
      <div class="card card-statistic-1">
        <div class="card-icon bg-primary"><i class="fas fa-money-bill-wave"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Revenue</h4></div>
          <div class="card-body"><?= format_naira($sales_summary['revenue']) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success"><i class="fas fa-chart-line"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Profit</h4></div>
          <div class="card-body"><?= format_naira($total_profit) ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card card-statistic-1">
        <div class="card-icon bg-info"><i class="fas fa-shopping-bag"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Total Sales</h4></div>
          <div class="card-body"><?= (int)$sales_summary['count'] ?></div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card card-statistic-1">
        <div class="card-icon bg-warning"><i class="fas fa-tools"></i></div>
        <div class="card-wrap">
          <div class="card-header"><h4>Repairs</h4></div>
          <div class="card-body"><?= (int)$repair_count ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Inventory Value Cards -->
  <div class="row">
    <div class="col-lg-4 col-md-6">
      <div class="card">
        <div class="card-body text-center">
          <h6 class="text-muted mb-1">Inventory Cost Value</h6>
          <h3 class="text-primary"><?= format_naira($inv['cost_val']) ?></h3>
          <small class="text-muted"><?= number_format($inv['units']) ?> units in stock</small>
        </div>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="card">
        <div class="card-body text-center">
          <h6 class="text-muted mb-1">Inventory Retail Value</h6>
          <h3 class="text-success"><?= format_naira($inv['retail_val']) ?></h3>
          <small class="text-muted">Potential revenue if all sold</small>
        </div>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="card">
        <div class="card-body text-center">
          <h6 class="text-muted mb-1">Avg Repair Turnaround</h6>
          <h3 class="text-info" id="turnaround-val">—</h3>
          <small class="text-muted">Days from pending to collected</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Row 1 -->
  <div class="row">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h4>Daily Revenue</h4></div>
        <div class="card-body"><div id="daily-chart"></div></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h4>Sales by Payment Method</h4></div>
        <div class="card-body"><div id="payment-chart"></div></div>
      </div>
    </div>
  </div>

  <!-- Charts Row 2 -->
  <div class="row">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h4>Sales by Category</h4></div>
        <div class="card-body"><div id="category-chart"></div></div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header"><h4>Top 10 Products (by Qty Sold)</h4></div>
        <div class="card-body"><div id="top-products-chart"></div></div>
      </div>
    </div>
  </div>

  <!-- Staff Performance Table -->
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header"><h4><i class="fas fa-trophy text-warning"></i> Staff Performance</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="staff-table">
              <thead><tr><th>#</th><th>Staff</th><th>Sales Count</th><th>Revenue</th><th>Avg Sale Value</th></tr></thead>
              <tbody id="staff-tbody">
                <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
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
  var from = '<?= e($from) ?>';
  var to = '<?= e($to) ?>';

  // Daily Revenue (server-rendered data)
  var dailyLabels = <?= json_encode(array_column($daily, 'day')) ?>;
  var dailyValues = <?= json_encode(array_map('floatval', array_column($daily, 'total'))) ?>;

  new ApexCharts(document.querySelector("#daily-chart"), {
    chart: { type: 'area', height: 300, toolbar: { show: false } },
    series: [{ name: 'Revenue', data: dailyValues }],
    xaxis: { categories: dailyLabels, labels: { rotate: -45 } },
    colors: ['#6777ef'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
    tooltip: { y: { formatter: function(v) { return '₦' + v.toLocaleString(); } } },
    dataLabels: { enabled: false }
  }).render();

  // Fetch report data from API
  fetch('<?= BASE_URL ?>api/report_data.php?from=' + from + '&to=' + to)
    .then(function(r) { return r.json(); })
    .then(function(data) {
      // Payment Method Pie
      if (data.payment_methods.labels.length > 0) {
        new ApexCharts(document.querySelector("#payment-chart"), {
          chart: { type: 'donut', height: 280 },
          labels: data.payment_methods.labels,
          series: data.payment_methods.values,
          colors: ['#6777ef', '#63ed7a', '#ffa426'],
          legend: { position: 'bottom' },
          tooltip: { y: { formatter: function(v) { return '₦' + v.toLocaleString(); } } }
        }).render();
      } else {
        document.querySelector("#payment-chart").innerHTML = '<p class="text-muted text-center">No data</p>';
      }

      // Category Pie
      if (data.categories.labels.length > 0) {
        new ApexCharts(document.querySelector("#category-chart"), {
          chart: { type: 'donut', height: 300 },
          labels: data.categories.labels,
          series: data.categories.values,
          colors: ['#6777ef', '#63ed7a', '#ffa426', '#fc544b', '#3abaf4', '#e83e8c', '#6c757d'],
          legend: { position: 'bottom' },
          tooltip: { y: { formatter: function(v) { return '₦' + v.toLocaleString(); } } }
        }).render();
      } else {
        document.querySelector("#category-chart").innerHTML = '<p class="text-muted text-center">No data</p>';
      }

      // Top Products Bar
      if (data.top_products.labels.length > 0) {
        new ApexCharts(document.querySelector("#top-products-chart"), {
          chart: { type: 'bar', height: 300, toolbar: { show: false } },
          series: [{ name: 'Qty Sold', data: data.top_products.qty }],
          xaxis: { categories: data.top_products.labels, labels: { rotate: -45, style: { fontSize: '11px' } } },
          colors: ['#63ed7a'],
          plotOptions: { bar: { borderRadius: 4 } },
          dataLabels: { enabled: false }
        }).render();
      } else {
        document.querySelector("#top-products-chart").innerHTML = '<p class="text-muted text-center">No data</p>';
      }

      // Staff Table
      var tbody = document.getElementById('staff-tbody');
      if (data.staff_performance.length > 0) {
        var html = '';
        data.staff_performance.forEach(function(s, i) {
          var medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : (i + 1)));
          html += '<tr><td>' + medal + '</td><td>' + s.full_name + '</td><td>' + s.sale_count + '</td>';
          html += '<td>₦' + parseFloat(s.revenue).toLocaleString() + '</td>';
          html += '<td>₦' + parseFloat(s.avg_sale).toLocaleString(undefined, {minimumFractionDigits:2}) + '</td></tr>';
        });
        tbody.innerHTML = html;
      } else {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No data</td></tr>';
      }

      // Turnaround
      document.getElementById('turnaround-val').textContent = data.repair_turnaround + ' days';
    })
    .catch(function() {
      console.error('Failed to load report data');
    });
});
</script>
