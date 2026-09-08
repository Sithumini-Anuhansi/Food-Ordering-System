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
include '../includes/customer_header.php';

function show_message($msg, $is_error = false) {
    $class = $is_error ? 'order-message order-error' : 'order-message';
    echo "<div class='$class'>" . htmlspecialchars($msg) . "</div>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['Quantity']))
{
    csrf_verify();

    if (!isset($_SESSION['user_id']))
    {
        show_message("Session expired. Please log in again.", true);
        include '../includes/footer.php';
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $quantities = $_POST['Quantity'];

    // Filter only items with quantity > 0
    $order_items = [];
    foreach ($quantities as $food_id => $qty)
    {
        $qty = intval($qty);
        if ($qty > 0)
        {
            $order_items[$food_id] = $qty;
        }
    }

    if (empty($order_items))
    {
        show_message("Please select at least one item with quantity greater than zero.", true);
        include '../includes/footer.php';
        exit();
    }

    // Get item details and prepare order summary string
    $items_detail = [];
    $total_price = 0.0;

    foreach ($order_items as $food_id => $qty)
    {
        $stmt = $conn->prepare("SELECT Name, Price FROM food_items WHERE Item_ID = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $food_id);
        $stmt->execute();
        $stmt->bind_result($name, $price);
        if ($stmt->fetch())
        {
            $items_detail[] = htmlspecialchars($name) . " (x" . $qty . ")";
            $total_price += $price * $qty;
        }
        $stmt->close();
    }

    if (empty($items_detail))
    {
        show_message("Selected items are invalid.", true);
        include '../includes/footer.php';
        exit();
    }

    $items_string = implode(", ", $items_detail);
    $status = "Pending";

    // Insert order record into the orders table
    $stmt = $conn->prepare("INSERT INTO orders (User_ID, Total, Status) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ids", $user_id, $total_price, $status);

    if ($stmt->execute())
    {
        $order_id = $stmt->insert_id; // get inserted order id
        $stmt->close();

        // Save each item + quantity + price so order history can show line items later
        $item_stmt = $conn->prepare("INSERT INTO order_items (Order_ID, Item_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
        if ($item_stmt) {
            foreach ($order_items as $food_id => $qty) {
                // Re-look up price so what we store matches what was actually charged
                $price_stmt = $conn->prepare("SELECT Price FROM food_items WHERE Item_ID = ?");
                $price_stmt->bind_param("i", $food_id);
                $price_stmt->execute();
                $price_stmt->bind_result($item_price);
                if ($price_stmt->fetch()) {
                    $item_stmt->bind_param("iiid", $order_id, $food_id, $qty, $item_price);
                    $item_stmt->execute();
                }
                $price_stmt->close();
            }
            $item_stmt->close();
        }

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
}
else
{
    show_message("Invalid request.", true);
}

include '../includes/footer.php';
?>
