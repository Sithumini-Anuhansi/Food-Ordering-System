<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';
include '../includes/pricing.php';
include '../includes/stripe.php';

$user_id = $_SESSION['user_id'];

// --- Build & validate the cart from what was submitted ---
$submitted_qty = $_POST['Quantity'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($submitted_qty)) {
    $_SESSION['order_error'] = "Please select at least one item before ordering.";
    header("Location: browse_menu.php");
    exit();
}
csrf_verify();

$cart = [];       // Item_ID => ['name'=>, 'price'=>, 'qty'=>, 'image'=>]
$subtotal = 0.0;

foreach ($submitted_qty as $item_id => $qty) {
    $item_id = (int)$item_id;
    $qty = (int)$qty;
    if ($qty <= 0) continue;

    $stmt = $conn->prepare("SELECT Item_ID, Name, Price, Image, Stock_Quantity FROM food_items WHERE Item_ID = ? AND available = 1");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $food = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$food) continue; // item no longer available — silently skip

    if ($food['Stock_Quantity'] !== null && $qty > (int)$food['Stock_Quantity']) {
        $qty = (int)$food['Stock_Quantity']; // clamp to what's actually left
    }
    if ($qty <= 0) continue;

    $cart[$item_id] = [
        'name'  => $food['Name'],
        'price' => (float)$food['Price'],
        'qty'   => $qty,
        'image' => $food['Image'],
    ];
    $subtotal += $food['Price'] * $qty;
}

if (empty($cart)) {
    $_SESSION['order_error'] = "Those items are no longer available. Please choose something else.";
    header("Location: browse_menu.php");
    exit();
}

// Stash the *validated* cart in the session — the delivery-details form below
// only carries non-pricing fields, so nothing about totals can be tampered
// with client-side.
$_SESSION['pending_cart'] = $cart;
$_SESSION['pending_subtotal'] = $subtotal;

// --- Delivery fee & tax (shared helper — keeps this preview and the final order in sync) ---
$totals = calculate_order_totals($subtotal);
$delivery_fee = $totals['delivery_fee'];
$tax = $totals['tax'];
$total = $totals['total'];

// Prefill delivery address from the customer's profile
$stmt = $conn->prepare("SELECT Address FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
$default_address = $profile['Address'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Food Ordering System</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include '../includes/customer_header.php'; ?>

<div class="admin-form-container" style="max-width:700px;">
    <h2>Review Your Order</h2>

    <table class="admin-orders-table">
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Line Total</th></tr>
        </thead>
        <tbody>
            <?php foreach ($cart as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= (int)$item['qty'] ?></td>
                <td>$<?= number_format($item['price'] * $item['qty'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p style="text-align:right; margin-top:10px;">
        Subtotal: $<?= number_format($subtotal, 2) ?><br>
        Delivery Fee: $<?= number_format($delivery_fee, 2) ?><br>
        Tax (8%): $<?= number_format($tax, 2) ?><br>
        <strong style="font-size:1.2em;">Total: $<?= number_format($total, 2) ?></strong>
    </p>

    <h2>Delivery Details</h2>
    <form class="admin-form" method="POST" action="place_order.php">
        <?= csrf_field() ?>

        <label>
            Delivery Address:
            <textarea name="delivery_address" rows="2" required><?= htmlspecialchars($default_address) ?></textarea>
        </label>

        <label>
            Delivery Time:
            <select name="delivery_time_choice" id="deliveryTimeChoice" required>
                <option value="ASAP">As soon as possible</option>
                <option value="Scheduled">Schedule for later</option>
            </select>
        </label>

        <label id="scheduledTimeWrap" style="display:none;">
            Scheduled Time:
            <input type="datetime-local" name="scheduled_time">
        </label>

        <label>
            Special Instructions (optional):
            <textarea name="special_instructions" rows="2" placeholder="e.g. no onions, leave at the door..."></textarea>
        </label>

        <label>
            Payment Method:
            <select name="payment_method" required>
                <option value="Cash on Delivery">Cash on Delivery</option>
                <?php if (stripe_is_configured()): ?>
                    <option value="Card">Card Payment (via Stripe)</option>
                <?php else: ?>
                    <option value="Card" disabled>Card Payment (not configured)</option>
                <?php endif; ?>
            </select>
        </label>

        <input type="submit" value="Confirm &amp; Place Order">
    </form>
    <p><a class="action-link" href="browse_menu.php">&larr; Back to Menu</a></p>
</div>

<script>
    const choice = document.getElementById('deliveryTimeChoice');
    const scheduledWrap = document.getElementById('scheduledTimeWrap');
    choice.addEventListener('change', function () {
        scheduledWrap.style.display = (choice.value === 'Scheduled') ? 'block' : 'none';
    });
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
