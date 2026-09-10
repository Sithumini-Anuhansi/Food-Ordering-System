<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';
include '../includes/pricing.php';
include '../includes/stripe.php';

$cart = $_SESSION['pending_cart'] ?? null;
$page_error = null; // set below; rendered further down if non-null

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($cart)) {
    $page_error = "Your checkout session expired. Please choose your items again.";
} else {
    csrf_verify();

    if (!isset($_SESSION['user_id'])) {
        $page_error = "Session expired. Please log in again.";
    } else {
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
            $page_error = "Some items changed while you were checking out: " . implode(', ', $insufficient) . ". Please review your order again.";
        } else {
            $totals = calculate_order_totals($subtotal);
            $delivery_fee = $totals['delivery_fee'];
            $tax = $totals['tax'];
            $total = $totals['total'];

            $delivery_address = trim($_POST['delivery_address'] ?? '');
            $delivery_time_choice = ($_POST['delivery_time_choice'] ?? 'ASAP') === 'Scheduled' ? 'Scheduled' : 'ASAP';
            $scheduled_time = trim($_POST['scheduled_time'] ?? '');
            $delivery_time = $delivery_time_choice === 'Scheduled' && $scheduled_time !== '' ? $scheduled_time : 'ASAP';
            $special_instructions = trim($_POST['special_instructions'] ?? '');

            // Only actually offer Card if Stripe is configured — otherwise
            // silently fall back to Cash on Delivery rather than trying
            // (and failing) to create a Stripe session with no keys set.
            $payment_method = ($_POST['payment_method'] ?? '') === 'Card' && stripe_is_configured() ? 'Card' : 'Cash on Delivery';

            if ($delivery_address === '') {
                $page_error = "Please provide a delivery address.";
            } else {
                $status = "Pending";
                $payment_status = "Unpaid";

                $stmt = $conn->prepare(
                    "INSERT INTO orders (User_ID, Total, Status, Delivery_Fee, Tax, Delivery_Address, Delivery_Time, Special_Instructions, Payment_Method, Payment_Status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "idsddsssss",
                    $user_id, $total, $status, $delivery_fee, $tax,
                    $delivery_address, $delivery_time, $special_instructions, $payment_method, $payment_status
                );

                if ($stmt->execute()) {
                    $order_id = $stmt->insert_id;
                    $stmt->close();

                    $item_stmt = $conn->prepare("INSERT INTO order_items (Order_ID, Item_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
                    $stock_stmt = $conn->prepare("UPDATE food_items SET Stock_Quantity = Stock_Quantity - ? WHERE Item_ID = ? AND Stock_Quantity IS NOT NULL");
                    $out_of_stock_stmt = $conn->prepare("UPDATE food_items SET available = 0 WHERE Item_ID = ? AND Stock_Quantity IS NOT NULL AND Stock_Quantity <= 0");

                    foreach ($cart as $item_id => $item) {
                        $item_stmt->bind_param("iiid", $order_id, $item_id, $item['qty'], $item['price']);
                        $item_stmt->execute();

                        $stock_stmt->bind_param("ii", $item['qty'], $item_id);
                        $stock_stmt->execute();
                        $out_of_stock_stmt->bind_param("i", $item_id);
                        $out_of_stock_stmt->execute();
                    }
                    $item_stmt->close();
                    $stock_stmt->close();
                    $out_of_stock_stmt->close();

                    unset($_SESSION['pending_cart'], $_SESSION['pending_subtotal']);

                    // Fetch the customer's info once, upfront — used for both
                    // the confirmation email and (if applicable) Stripe, so
                    // Stripe checkout never silently gets an empty email just
                    // because the email-sending step below happened to fail.
                    $customer = null;
                    $email_stmt = $conn->prepare("SELECT Email, Name FROM users WHERE User_ID = ?");
                    $email_stmt->bind_param("i", $user_id);
                    $email_stmt->execute();
                    $customer = $email_stmt->get_result()->fetch_assoc();
                    $email_stmt->close();

                    // Send an order-confirmation email — never let a mail
                    // failure break order placement itself.
                    try {
                        include_once '../includes/mailer.php';
                        if ($customer) {
                            $items_list = implode('', array_map(function ($it) {
                                return "<li>" . htmlspecialchars($it['name']) . " x" . (int)$it['qty'] . " — $" . number_format($it['price'] * $it['qty'], 2) . "</li>";
                            }, $cart));
                            $body = "<p>Hi " . htmlspecialchars($customer['Name']) . ",</p>"
                                . "<p>Your order #$order_id has been placed.</p>"
                                . "<ul>$items_list</ul>"
                                . "<p><strong>Total: $" . number_format($total, 2) . "</strong> (incl. $" . number_format($delivery_fee, 2) . " delivery, $" . number_format($tax, 2) . " tax)</p>"
                                . "<p>Delivery: " . htmlspecialchars($delivery_time) . " to " . htmlspecialchars($delivery_address) . "</p>";
                            send_email($customer['Email'], "Order #$order_id confirmed", $body);
                        }
                    } catch (\Throwable $e) {
                        error_log("Order confirmation email failed: " . $e->getMessage());
                    }

                    if ($payment_method === 'Card') {
                        // Redirect to Stripe's hosted checkout to actually collect payment.
                        // This must happen before any HTML is echoed.
                        try {
                            $base_url = rtrim(env('APP_BASE_URL', ''), '/');
                            $session = stripe_create_checkout_session(
                                $order_id,
                                $total,
                                'usd',
                                $customer['Email'] ?? '',
                                "$base_url/customer/payment_success.php?order_id=$order_id",
                                "$base_url/customer/order_history.php"
                            );
                            $save = $conn->prepare("UPDATE orders SET Stripe_Session_ID = ? WHERE ID = ?");
                            $save->bind_param("si", $session['id'], $order_id);
                            $save->execute();
                            $save->close();

                            header("Location: " . $session['url']);
                            exit();
                        } catch (\Throwable $e) {
                            error_log("Stripe session creation failed: " . $e->getMessage());
                            // Order already exists as Cash-equivalent Unpaid — let the
                            // customer know payment didn't go through and to try again
                            // or pay on delivery, rather than losing the order entirely.
                            $page_error = "Order #$order_id was placed, but we couldn't start the card payment. You can pay on delivery, or contact support to try card payment again.";
                        }
                    } else {
                        $order_placed = $order_id;
                    }
                } else {
                    error_log("Order insert failed: " . $stmt->error);
                    $page_error = "Something went wrong while placing your order. Please try again.";
                    $stmt->close();
                }
            }
        }
    }
}
?>
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

<?php include '../includes/customer_header.php'; ?>

<?php if (isset($order_placed)): ?>
    <div class="order-message"><div class="order-spinner"></div>Order placed successfully! Redirecting to your order history...</div>
    <script>setTimeout(function(){ window.location.href = 'order_history.php'; }, 1800);</script>
<?php else: ?>
    <div class="order-message order-error"><?= htmlspecialchars($page_error ?? 'Something went wrong.') ?></div>
    <p style="text-align:center;"><a class="action-link" href="browse_menu.php">&larr; Back to Menu</a></p>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
</body>
</html>
