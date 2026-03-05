<?php
/**
 * MarvelStore v2.0 — Repair Receipt (Print View)
 * Shows repair code, status, customer/device info, parts used.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo = get_db();
$id = input_int('id');

$stmt = $pdo->prepare("SELECT r.*, u.full_name as technician FROM repairs r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch();

if (!$repair) {
    set_flash('danger', 'Repair not found.');
    redirect('repairs.php');
}

// Get parts used
$stmt = $pdo->prepare("SELECT rp.*, p.name as product_name FROM repair_parts rp LEFT JOIN products p ON rp.product_id = p.id WHERE rp.repair_id = ?");
$stmt->execute([$id]);
$parts = $stmt->fetchAll();
$parts_total = array_sum(array_column($parts, 'line_total'));

// Status display
$status_map = [
    'pending'   => ['⏳', 'Pending — Awaiting repair'],
    'repairing' => ['🔧', 'In Progress — Being repaired'],
    'ready'     => ['✅', 'Ready — Available for pickup'],
    'collected' => ['📦', 'Collected — Delivered to customer'],
];
$status_info = $status_map[$repair['status']] ?? ['❓', ucfirst($repair['status'])];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Repair Receipt — <?= e($repair['repair_code'] ?? '') ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Courier New', monospace; font-size: 14px; padding: 20px; max-width: 450px; margin: 0 auto; color: #333; }
    .receipt-header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 12px; margin-bottom: 12px; }
    .receipt-header h2 { font-size: 20px; margin-bottom: 3px; }
    .repair-code-box { text-align: center; margin: 15px 0; padding: 15px; border: 3px solid #333; }
    .repair-code-box .code { font-size: 32px; font-weight: bold; letter-spacing: 6px; margin: 5px 0; }
    .repair-code-box .label { font-size: 11px; text-transform: uppercase; color: #666; }
    .status-box { text-align: center; margin: 12px 0; padding: 10px; background: #f0f0f0; font-size: 14px; }
    .info-table { width: 100%; margin-bottom: 12px; }
    .info-table td { padding: 4px 0; font-size: 13px; vertical-align: top; }
    .info-table td:first-child { font-weight: bold; width: 40%; }
    .section-title { font-weight: bold; font-size: 13px; border-bottom: 1px solid #333; padding-bottom: 3px; margin: 12px 0 6px; text-transform: uppercase; }
    table.parts { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.parts th, table.parts td { text-align: left; padding: 4px 0; font-size: 12px; }
    table.parts th { border-bottom: 1px solid #333; }
    .text-right { text-align: right; }
    .totals { border-top: 1px dashed #333; padding-top: 8px; }
    .totals div { display: flex; justify-content: space-between; padding: 2px 0; font-size: 13px; }
    .totals .grand-total { font-size: 18px; font-weight: bold; border-top: 2px solid #333; padding-top: 5px; margin-top: 5px; }
    .receipt-footer { text-align: center; border-top: 2px dashed #333; padding-top: 10px; margin-top: 15px; font-size: 11px; }
    .receipt-footer .important { font-weight: bold; font-size: 12px; margin-bottom: 5px; }
    .no-print { text-align: center; margin: 20px 0; }
    @media print {
      .no-print { display: none; }
      body { padding: 0; }
    }
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()" style="padding:10px 30px; font-size:16px; cursor:pointer; background:#6777ef; color:#fff; border:none; border-radius:4px;">🖨️ Print Receipt</button>
    <a href="repair_view.php?id=<?= (int)$repair['id'] ?>" style="display:inline-block; margin-left:10px; padding:10px 20px; font-size:14px; text-decoration:none; color:#6777ef;">← Back to Repair</a>
  </div>

  <div class="receipt-header">
    <h2><?= APP_NAME ?></h2>
    <div>Electronics Retail & Repair</div>
  </div>

  <!-- REPAIR CODE (hidden once collected) -->
  <?php if ($repair['status'] !== 'collected'): ?>
  <div class="repair-code-box">
    <div class="label">Repair Verification Code</div>
    <div class="code"><?= e($repair['repair_code'] ?? 'N/A') ?></div>
    <div class="label">Present this code when collecting your device</div>
  </div>
  <?php endif; ?>

  <!-- STATUS -->
  <div class="status-box">
    <?= $status_info[0] ?> <?= $status_info[1] ?>
  </div>

  <!-- REPAIR INFO -->
  <div class="section-title">Repair Details</div>
  <table class="info-table">
    <tr><td>Ticket #:</td><td><?= (int)$repair['id'] ?></td></tr>
    <tr><td>Date In:</td><td><?= date('M d, Y h:i A', strtotime($repair['created_at'])) ?></td></tr>
    <tr><td>Customer:</td><td><?= e($repair['customer_name']) ?></td></tr>
    <?php if (!empty($repair['customer_phone'])): ?>
    <tr><td>Phone:</td><td><?= e($repair['customer_phone']) ?></td></tr>
    <?php endif; ?>
    <tr><td>Device:</td><td><?= e($repair['device_model']) ?></td></tr>
    <tr><td>Fault:</td><td><?= e($repair['fault_description']) ?></td></tr>
    <tr><td>Booked By:</td><td><?= e($repair['technician'] ?? 'N/A') ?></td></tr>
  </table>

  <?php if (!empty($parts)): ?>
  <div class="section-title">Parts Used</div>
  <table class="parts">
    <thead>
      <tr><th>Part</th><th class="text-right">Qty</th><th class="text-right">Cost</th><th class="text-right">Total</th></tr>
    </thead>
    <tbody>
      <?php foreach ($parts as $part): ?>
      <tr>
        <td><?= e($part['product_name'] ?? 'Unknown') ?></td>
        <td class="text-right"><?= (int)$part['quantity'] ?></td>
        <td class="text-right"><?= format_naira($part['unit_cost']) ?></td>
        <td class="text-right"><?= format_naira($part['line_total']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="totals">
    <?php if (!empty($parts)): ?>
    <div><span>Parts Total:</span><span><?= format_naira($parts_total) ?></span></div>
    <?php endif; ?>
    <div class="grand-total"><span>REPAIR COST:</span><span><?= format_naira($repair['repair_cost']) ?></span></div>
  </div>

  <div class="receipt-footer">
    <div class="important">⚠️ Please keep this receipt safe.</div>
    <div class="important">Your verification code is required for device collection.</div>
    <p style="margin-top:8px">Thank you for choosing <?= APP_NAME ?>!</p>
    <p><?= APP_NAME ?> v<?= APP_VERSION ?></p>
  </div>
</body>
</html>
