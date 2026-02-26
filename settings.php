<?php
/**
 * MarvelStore v2.0 — Store Settings (Admin Only)
 * Configure store name, address, phone, receipt footer.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_role('admin');

$pdo = get_db();
$success = false;

// Load current settings
function get_settings(PDO $pdo): array {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

$settings = get_settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(input_str('csrf_token'))) {
        set_flash('danger', 'Invalid CSRF token.');
    } else {
        $keys = ['store_name', 'store_address', 'store_phone', 'receipt_footer'];
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        foreach ($keys as $k) {
            $val = input_str($k);
            $stmt->execute([$k, $val]);
        }
        
        log_activity('settings_update', 'settings', null, "Updated store settings");
        $settings = get_settings($pdo);
        $success = true;
    }
}

$page_title = 'Store Settings';
$current_page = 'settings.php';
$extra_css = [];
$extra_js = [];

require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1><i class="fas fa-cog"></i> Store Settings</h1>
</div>

<div class="section-body">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Settings saved successfully!</div>
      <?php endif; ?>
      <?= render_flash() ?>

      <div class="card">
        <div class="card-header"><h4>Store Information</h4></div>
        <div class="card-body">
          <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
              <label>Store Name</label>
              <input type="text" name="store_name" class="form-control" value="<?= e($settings['store_name'] ?? 'MarvelStore') ?>">
            </div>
            <div class="form-group">
              <label>Store Address</label>
              <textarea name="store_address" class="form-control" rows="2"><?= e($settings['store_address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label>Store Phone</label>
              <input type="text" name="store_phone" class="form-control" value="<?= e($settings['store_phone'] ?? '') ?>" placeholder="08012345678">
            </div>
            <hr>
            <div class="form-group">
              <label>Receipt Footer Message</label>
              <textarea name="receipt_footer" class="form-control" rows="2"><?= e($settings['receipt_footer'] ?? 'Thank you for your patronage!') ?></textarea>
              <small class="text-muted">Shown at the bottom of every receipt</small>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fas fa-save"></i> Save Settings</button>
          </form>
        </div>
      </div>

      <!-- Receipt Preview -->
      <div class="card">
        <div class="card-header"><h4><i class="fas fa-receipt"></i> Receipt Preview</h4></div>
        <div class="card-body text-center" style="font-family: monospace; border: 1px dashed #ccc; padding: 20px;">
          <h5><strong><?= e($settings['store_name'] ?? 'MarvelStore') ?></strong></h5>
          <?php if (!empty($settings['store_address'])): ?>
            <p class="mb-0"><?= e($settings['store_address']) ?></p>
          <?php endif; ?>
          <?php if (!empty($settings['store_phone'])): ?>
            <p class="mb-1">Tel: <?= e($settings['store_phone']) ?></p>
          <?php endif; ?>
          <hr style="border-style: dashed;">
          <p class="text-muted">[ Receipt items would appear here ]</p>
          <hr style="border-style: dashed;">
          <p class="mb-0"><em><?= e($settings['receipt_footer'] ?? '') ?></em></p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>
