<?php
/**
 * MarvelStore v1.0 — New Sale (POS)
 * Select2 product search, dynamic cart, stock deduction.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . 'db.php';
require_once INCLUDES_PATH . 'auth.php';
require_once INCLUDES_PATH . 'csrf.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'logger.php';
require_login();

$pdo = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(input_str('csrf_token'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $payment_method = input_str('payment_method');
        $customer_name  = input_str('customer_name') ?: null;
        $serial_number  = input_str('serial_number') ?: null;
        $discount       = max(0, (float)input_str('discount'));
        $items_json     = input_str('cart_items');
        $items          = json_decode($items_json, true);

        if (empty($items) || !is_array($items)) {
            $errors[] = 'Cart is empty. Add at least one product.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Calculate subtotal
                $subtotal = 0;
                foreach ($items as &$item) {
                    $item['line_total'] = $item['price'] * $item['qty'];
                    $subtotal += $item['line_total'];
                }
                unset($item);
                $total = max(0, $subtotal - $discount);

                // Insert sale
                $stmt = $pdo->prepare("INSERT INTO sales (user_id, customer_name, serial_number, payment_method, subtotal, discount, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([current_user('id'), $customer_name, $serial_number, $payment_method, $subtotal, $discount, $total]);
                $sale_id = $pdo->lastInsertId();

                // Insert sale items & deduct stock
                $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, cost_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_stock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");

                foreach ($items as $item) {
                    // Get cost price
                    $stmt_p = $pdo->prepare("SELECT cost_price FROM products WHERE id = ?");
                    $stmt_p->execute([$item['id']]);
                    $cost = $stmt_p->fetchColumn() ?: 0;

                    $stmt_item->execute([$sale_id, $item['id'], $item['qty'], $item['price'], $cost, $item['line_total']]);

                    // Deduct stock
                    $stmt_stock->execute([$item['qty'], $item['id'], $item['qty']]);
                    if ($stmt_stock->rowCount() === 0) {
                        throw new Exception("Insufficient stock for product ID {$item['id']}.");
                    }
                }

                $pdo->commit();
                log_activity('sale_create', 'sale', (int)$sale_id, "Sale #{$sale_id} — " . format_naira($total) . " ({$payment_method})");
                set_flash('success', 'Sale completed! Total: ' . format_naira($total));
                redirect('sale_receipt.php?id=' . $sale_id);
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = $e->getMessage();
            }
        }
    }
}

$page_title = 'New Sale';
$current_page = 'sale_new.php';
$extra_css = [OTIKA_ASSETS . 'bundles/select2/dist/css/select2.min.css'];
$extra_js  = [OTIKA_ASSETS . 'bundles/select2/dist/js/select2.full.min.js'];
require_once INCLUDES_PATH . 'header.php';
require_once INCLUDES_PATH . 'sidebar.php';
?>

<div class="section-header">
  <h1>New Sale</h1>
</div>

<div class="section-body">
  <?= render_flash() ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- Product Search -->
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header"><h4>Search Product</h4></div>
        <div class="card-body">
          <div class="form-group">
            <label>Product</label>
            <select id="product-search" class="form-control" style="width:100%"></select>
          </div>
          <div class="form-group">
            <label>Quantity</label>
            <input type="number" id="add-qty" class="form-control" value="1" min="1">
          </div>
          <button type="button" id="add-to-cart" class="btn btn-primary btn-block"><i class="fas fa-cart-plus"></i> Add to Cart</button>
        </div>
      </div>
    </div>

    <!-- Cart -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header"><h4>Cart</h4></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0" id="cart-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="cart-body">
                <tr id="cart-empty"><td colspan="5" class="text-center text-muted">Cart is empty</td></tr>
              </tbody>
              <tfoot>
                <tr><td colspan="3" class="text-right font-weight-bold">Subtotal:</td><td id="cart-subtotal" class="font-weight-bold">₦0.00</td><td></td></tr>
                <tr><td colspan="3" class="text-right">Discount:</td><td><input type="number" id="discount-input" class="form-control form-control-sm" value="0" min="0" step="0.01"></td><td></td></tr>
                <tr><td colspan="3" class="text-right font-weight-bold">Total:</td><td id="cart-total" class="font-weight-bold text-black" style="font-size:1.2rem">₦0.00</td><td></td></tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div class="card-footer">
          <form method="POST" id="sale-form">
            <?= csrf_field() ?>
            <input type="hidden" name="cart_items" id="cart-items-input">
            <input type="hidden" name="discount" id="discount-hidden" value="0">

            <!-- Row 1: Optional fields -->
            <div class="row">
              <div class="col-sm-6 col-12 mb-2">
                <label>Customer Name <small class="text-muted">(optional)</small></label>
                <input type="text" name="customer_name" class="form-control" placeholder="Walk-in customer">
              </div>
              <div class="col-sm-6 col-12 mb-2">
                <label>IMEI / Serial No. <small class="text-muted">(optional)</small></label>
                <input type="text" name="serial_number" class="form-control" placeholder="e.g. 356938035643809">
              </div>
            </div>

            <!-- Row 2: Payment + Submit -->
            <div class="row align-items-end">
              <div class="col-sm-4 col-12 mb-2">
                <label>Payment Method</label>
                <select name="payment_method" class="form-control">
                  <option value="cash">Cash</option>
                  <option value="transfer">Transfer</option>
                  <option value="pos">POS</option>
                </select>
              </div>
              <div class="col-sm-8 col-12 mb-2">
                <button type="submit" class="btn btn-success btn-lg btn-block"><i class="fas fa-check-circle"></i> Complete Sale</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>

<script>
$(document).ready(function() {
  var cart = [];

  // Select2 AJAX product search
  $('#product-search').select2({
    placeholder: 'Type to search products...',
    allowClear: true,
    minimumInputLength: 1,
    ajax: {
      url: '<?= BASE_URL ?>api/product_search.php',
      dataType: 'json',
      delay: 300,
      data: function(params) { return { q: params.term }; },
      processResults: function(data) { return { results: data }; }
    },
    templateResult: function(item) {
      if (item.loading) return item.text;
      return $('<span>').text(item.text + ' — ₦' + parseFloat(item.price).toLocaleString() + ' (Stock: ' + item.stock + ')');
    }
  });

  // Add to cart
  $('#add-to-cart').on('click', function() {
    var sel = $('#product-search').select2('data')[0];
    if (!sel) { alert('Please select a product.'); return; }
    var qty = parseInt($('#add-qty').val()) || 1;
    if (qty < 1) qty = 1;
    if (qty > parseInt(sel.stock)) { alert('Not enough stock! Available: ' + sel.stock); return; }

    // Check if already in cart
    var existing = cart.find(function(i) { return i.id == sel.id; });
    if (existing) {
      existing.qty += qty;
    } else {
      cart.push({ id: sel.id, name: sel.text, price: parseFloat(sel.price), qty: qty, stock: parseInt(sel.stock) });
    }
    renderCart();
    $('#product-search').val(null).trigger('change');
    $('#add-qty').val(1);
  });

  // Discount change
  $('#discount-input').on('input', function() { renderCart(); });

  // Render cart table
  function renderCart() {
    var $body = $('#cart-body');
    $body.empty();
    if (cart.length === 0) {
      $body.append('<tr id="cart-empty"><td colspan="5" class="text-center text-muted">Cart is empty</td></tr>');
    }
    var subtotal = 0;
    cart.forEach(function(item, idx) {
      var lt = item.price * item.qty;
      subtotal += lt;
      $body.append(
        '<tr>' +
        '<td>' + $('<span>').text(item.name).html() + '</td>' +
        '<td>₦' + item.price.toLocaleString(undefined, {minimumFractionDigits:2}) + '</td>' +
        '<td><input type="number" class="form-control form-control-sm cart-qty" data-idx="' + idx + '" value="' + item.qty + '" min="1" max="' + item.stock + '" style="width:70px"></td>' +
        '<td>₦' + lt.toLocaleString(undefined, {minimumFractionDigits:2}) + '</td>' +
        '<td><button class="btn btn-sm btn-danger cart-remove" data-idx="' + idx + '"><i class="fas fa-times"></i></button></td>' +
        '</tr>'
      );
    });
    var discount = parseFloat($('#discount-input').val()) || 0;
    var total = Math.max(0, subtotal - discount);
    $('#cart-subtotal').text('₦' + subtotal.toLocaleString(undefined, {minimumFractionDigits:2}));
    $('#cart-total').text('₦' + total.toLocaleString(undefined, {minimumFractionDigits:2}));
    $('#discount-hidden').val(discount);
  }

  // Qty change in cart
  $(document).on('change', '.cart-qty', function() {
    var idx = $(this).data('idx');
    var newQty = parseInt($(this).val()) || 1;
    if (newQty > cart[idx].stock) { newQty = cart[idx].stock; $(this).val(newQty); }
    cart[idx].qty = newQty;
    renderCart();
  });

  // Remove from cart
  $(document).on('click', '.cart-remove', function() {
    cart.splice($(this).data('idx'), 1);
    renderCart();
  });

  // Submit
  $('#sale-form').on('submit', function(e) {
    if (cart.length === 0) { e.preventDefault(); alert('Cart is empty!'); return; }
    $('#cart-items-input').val(JSON.stringify(cart));
  });
});
</script>
