<?php
/**
 * MarvelStore v1.0 — Sale Receipt (Print View)
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'helpers.php';
require_login();

$pdo = get_db();
$id = input_int('id');

$stmt = $pdo->prepare("SELECT s.*, u.full_name as cashier FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    set_flash('danger', 'Sale not found.');
    redirect('sales.php');
}

$stmt = $pdo->prepare("SELECT si.*, p.name as product_name FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt #<?= (int)$sale['id'] ?> — <?= APP_NAME ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Courier New', monospace; font-size: 14px; padding: 20px; max-width: 400px; margin: 0 auto; color: #333; }
    .receipt-header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 10px; margin-bottom: 10px; }
    .receipt-header h2 { font-size: 20px; margin-bottom: 5px; }
    .receipt-meta { margin-bottom: 10px; font-size: 12px; }
    .receipt-meta div { display: flex; justify-content: space-between; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { text-align: left; padding: 4px 0; font-size: 13px; }
    th { border-bottom: 1px solid #333; }
    .text-right { text-align: right; }
    .totals { border-top: 1px dashed #333; padding-top: 8px; }
    .totals div { display: flex; justify-content: space-between; padding: 2px 0; }
    .totals .grand-total { font-size: 18px; font-weight: bold; border-top: 2px solid #333; padding-top: 5px; margin-top: 5px; }
    .receipt-footer { text-align: center; border-top: 2px dashed #333; padding-top: 10px; margin-top: 10px; font-size: 12px; }
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
    <a href="sales.php" style="display:inline-block; margin-left:10px; padding:10px 20px; font-size:14px; text-decoration:none; color:#6777ef;">← Back to Sales</a>
  </div>

  <div class="receipt-header">
    <h2><?= APP_NAME ?></h2>
    <div>Electronics Retail & Repair</div>
  </div>

  <div class="receipt-meta">
    <div><span>Receipt #:</span><span><?= (int)$sale['id'] ?></span></div>
    <div><span>Date:</span><span><?= date('M d, Y h:i A', strtotime($sale['created_at'])) ?></span></div>
    <div><span>Cashier:</span><span><?= e($sale['cashier'] ?? 'N/A') ?></span></div>
    <?php if (!empty($sale['customer_name'])): ?>
    <div><span>Customer:</span><span><?= e($sale['customer_name']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($sale['serial_number'])): ?>
    <div><span>IMEI/S.N:</span><span><?= e($sale['serial_number']) ?></span></div>
    <?php endif; ?>
    <div><span>Payment:</span><span><?= ucfirst(e($sale['payment_method'])) ?></span></div>
  </div>

  <table>
    <thead>
      <tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td><?= e($item['product_name'] ?? 'Unknown') ?></td>
        <td class="text-right"><?= (int)$item['quantity'] ?></td>
        <td class="text-right"><?= format_naira($item['unit_price']) ?></td>
        <td class="text-right"><?= format_naira($item['line_total']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals">
    <div><span>Subtotal:</span><span><?= format_naira($sale['subtotal']) ?></span></div>
    <?php if ($sale['discount'] > 0): ?>
    <div><span>Discount:</span><span>-<?= format_naira($sale['discount']) ?></span></div>
    <?php endif; ?>
    <div class="grand-total"><span>TOTAL:</span><span><?= format_naira($sale['total']) ?></span></div>
  </div>

  <div class="receipt-footer">
    <p>Thank you for your patronage!</p>
    <p><?= APP_NAME ?> v<?= APP_VERSION ?></p>
  </div>
</body>
</html>
