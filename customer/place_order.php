<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Food Ordering System - Place Order</title>
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';
include '../includes/pricing.php';
include '../includes/customer_header.php';

function show_message($msg, $is_error = false) {
    $class = $is_error ? 'order-message order-error' : 'order-message';
    echo "<div class='$class'>" . htmlspecialchars($msg) . "</div>";
}

$cart = $_SESSION['pending_cart'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($cart)) {
    show_message("Your checkout session expired. Please choose your items again.", true);
    echo "<p style='text-align:center;'><a class='action-link' href='browse_menu.php'>&larr; Back to Menu</a></p>";
    include '../includes/footer.php';
    exit();
}

csrf_verify();

if (!isset($_SESSION['user_id']))
{
    show_message("Session expired. Please log in again.", true);
    include '../includes/footer.php';
    exit();
}

$user_id = $_SESSION['user_id'];

// Re-validate stock right before committing — it may have changed since checkout.php ran.
$subtotal = 0.0;
$insufficient = [];
foreach ($cart as $item_id => $item) {
    $stmt = $conn->prepare("SELECT Stock_Quantity FROM food_items WHERE Item_ID = ? AND available = 1");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $food = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$food) {
        $insufficient[] = $item['name'] . " (no longer available)";
        continue;
    }
    if ($food['Stock_Quantity'] !== null && $item['qty'] > (int)$food['Stock_Quantity']) {
        $insufficient[] = $item['name'] . " (only " . (int)$food['Stock_Quantity'] . " left)";
        continue;
    }
    $subtotal += $item['price'] * $item['qty'];
}

if (!empty($insufficient)) {
    show_message("Some items changed while you were checking out: " . implode(', ', $insufficient) . ". Please review your order again.", true);
    echo "<p style='text-align:center;'><a class='action-link' href='browse_menu.php'>&larr; Back to Menu</a></p>";
    include '../includes/footer.php';
    exit();
}

$totals = calculate_order_totals($subtotal);
$delivery_fee = $totals['delivery_fee'];
$tax = $totals['tax'];
$total = $totals['total'];

$delivery_address = trim($_POST['delivery_address'] ?? '');
$delivery_time_choice = ($_POST['delivery_time_choice'] ?? 'ASAP') === 'Scheduled' ? 'Scheduled' : 'ASAP';
$scheduled_time = trim($_POST['scheduled_time'] ?? '');
$delivery_time = $delivery_time_choice === 'Scheduled' && $scheduled_time !== ''
    ? $scheduled_time
    : 'ASAP';
$special_instructions = trim($_POST['special_instructions'] ?? '');
$payment_method = ($_POST['payment_method'] ?? '') === 'Cash on Delivery' ? 'Cash on Delivery' : 'Cash on Delivery'; // only option live right now

if ($delivery_address === '') {
    show_message("Please provide a delivery address.", true);
    echo "<p style='text-align:center;'><a class='action-link' href='browse_menu.php'>&larr; Back to Menu</a></p>";
    include '../includes/footer.php';
    exit();
}

$status = "Pending";
$payment_status = "Unpaid"; // Cash on Delivery is settled at the door

$stmt = $conn->prepare(
    "INSERT INTO orders (User_ID, Total, Status, Delivery_Fee, Tax, Delivery_Address, Delivery_Time, Special_Instructions, Payment_Method, Payment_Status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "idsddsssss",
    $user_id, $total, $status, $delivery_fee, $tax,
    $delivery_address, $delivery_time, $special_instructions, $payment_method, $payment_status
);

if ($stmt->execute())
{
    $order_id = $stmt->insert_id;
    $stmt->close();

    $item_stmt = $conn->prepare("INSERT INTO order_items (Order_ID, Item_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
    $stock_stmt = $conn->prepare("UPDATE food_items SET Stock_Quantity = Stock_Quantity - ? WHERE Item_ID = ? AND Stock_Quantity IS NOT NULL");
    $out_of_stock_stmt = $conn->prepare("UPDATE food_items SET available = 0 WHERE Item_ID = ? AND Stock_Quantity IS NOT NULL AND Stock_Quantity <= 0");

    foreach ($cart as $item_id => $item) {
        $item_stmt->bind_param("iiid", $order_id, $item_id, $item['qty'], $item['price']);
        $item_stmt->execute();

        // Decrement stock for tracked items, then auto-hide if it just hit zero.
        $stock_stmt->bind_param("ii", $item['qty'], $item_id);
        $stock_stmt->execute();
        $out_of_stock_stmt->bind_param("i", $item_id);
        $out_of_stock_stmt->execute();
    }
    $item_stmt->close();
    $stock_stmt->close();
    $out_of_stock_stmt->close();

    unset($_SESSION['pending_cart'], $_SESSION['pending_subtotal']);

    echo "<div class='order-message'><div class='order-spinner'></div>Order placed successfully! Redirecting to your order history...</div>";
    echo "<script>setTimeout(function(){ window.location.href = 'order_history.php'; }, 1800);</script>";
    include '../includes/footer.php';
    exit();
}
else
{
    error_log("Order insert failed: " . $stmt->error);
    show_message("Something went wrong while placing your order. Please try again.", true);
    $stmt->close();
}

include '../includes/footer.php';
?>
