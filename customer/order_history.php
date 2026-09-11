<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Food Ordering System - Order History</title>
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';
include '../includes/customer_header.php';

echo "<div class='order-history-container'>";
echo "<h2>Your Order History</h2>";

if (isset($_SESSION['success'])) {
    echo "<div class='success-message'>" . htmlspecialchars($_SESSION['success']) . "</div>";
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo "<div class='error-message'>" . htmlspecialchars($_SESSION['error']) . "</div>";
    unset($_SESSION['error']);
}

if (!isset($_SESSION['user_id'])) 
{
    echo "<div class='order-history-message'>Session expired. Please log in again.</div>";
    echo "</div>";
    include '../includes/footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];

// Select orders for the user
$query = "SELECT ID, Total, Status, Order_Time, Delivery_Fee, Tax, Delivery_Address, Delivery_Time, Special_Instructions, Payment_Method, Payment_Status, Estimated_Ready_Time
          FROM orders WHERE User_ID = ? ORDER BY Order_Time DESC";
$stmt = $conn->prepare($query);

if (!$stmt) 
{
    echo "<div class='order-history-message'>Database error: " . htmlspecialchars($conn->error) . "</div>";
    echo "</div>";
    include '../includes/footer.php';
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) 
{
    echo "<div class='order-history-message'>Database error: " . htmlspecialchars($stmt->error) . "</div>";
    echo "</div>";
    include '../includes/footer.php';
    exit;
}

$has_active_order = false;
$active_statuses = ['Pending', 'Preparing', 'Ready for Delivery'];

if ($result->num_rows > 0) 
{
    echo "<table class='order-history-table'>";
    echo "<tr><th>Order ID</th><th>Items</th><th>Delivery</th><th>Payment</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>";

    while ($row = $result->fetch_assoc()) 
    {
        if (in_array($row['Status'], $active_statuses, true)) {
            $has_active_order = true;
        }

        // Look up the items belonging to this order
        $items_text = "—";
        $items_stmt = $conn->prepare(
            "SELECT f.Name, oi.Quantity FROM order_items oi
             JOIN food_items f ON oi.Item_ID = f.Item_ID
             WHERE oi.Order_ID = ?"
        );
        if ($items_stmt) {
            $items_stmt->bind_param("i", $row['ID']);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            $items_parts = [];
            while ($item_row = $items_result->fetch_assoc()) {
                $items_parts[] = htmlspecialchars($item_row['Name']) . " (x" . htmlspecialchars($item_row['Quantity']) . ")";
            }
            $items_stmt->close();
            if (!empty($items_parts)) {
                $items_text = implode(", ", $items_parts);
            }
        }

        $delivery_text = htmlspecialchars($row['Delivery_Time'] ?: 'ASAP');
        if (!empty($row['Delivery_Address'])) {
            $delivery_text .= "<br><span style='color:#666;font-size:0.9em;'>" . htmlspecialchars($row['Delivery_Address']) . "</span>";
        }
        if (!empty($row['Special_Instructions'])) {
            $delivery_text .= "<br><span style='color:#888;font-size:0.85em;'><em>" . htmlspecialchars($row['Special_Instructions']) . "</em></span>";
        }

        $payment_text = htmlspecialchars($row['Payment_Method']) . "<br><span style='color:#666;font-size:0.9em;'>" . htmlspecialchars($row['Payment_Status']) . "</span>";

        $total_text = "$" . number_format($row['Total'], 2)
            . "<br><span style='color:#666;font-size:0.85em;'>incl. $" . number_format($row['Delivery_Fee'], 2) . " delivery, $" . number_format($row['Tax'], 2) . " tax</span>";

        $status_text = htmlspecialchars($row['Status']);
        if ($row['Status'] === 'Preparing' && $row['Estimated_Ready_Time']) {
            $status_text .= "<br><span style='color:#666;font-size:0.85em;'>ETA " . htmlspecialchars(date('g:i A', strtotime($row['Estimated_Ready_Time']))) . "</span>";
        }

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
        echo "<td>" . $items_text . "</td>";
        echo "<td>" . $delivery_text . "</td>";
        echo "<td>" . $payment_text . "</td>";
        echo "<td>" . $total_text . "</td>";
        echo "<td>" . $status_text . "</td>";
        echo "<td>" . htmlspecialchars($row['Order_Time']) . "</td>";
        echo "<td>";
        if ($row['Status'] === 'Pending') {
            echo "<form method='POST' action='cancel_order.php' onsubmit=\"return confirm('Cancel this order?')\">"
               . csrf_field()
               . "<input type='hidden' name='order_id' value='" . (int)$row['ID'] . "'>"
               . "<button type='submit' style='border:none;background:none;cursor:pointer;color:#e74c3c;font:inherit;'>Cancel</button>"
               . "</form>";
        }
        if ($row['Status'] === 'Delivered') {
            $rating_stmt = $conn->prepare("SELECT Rating FROM reviews WHERE Order_ID = ? AND User_ID = ?");
            $rating_stmt->bind_param("ii", $row['ID'], $user_id);
            $rating_stmt->execute();
            $existing_rating = $rating_stmt->get_result()->fetch_assoc();
            $rating_stmt->close();

            if ($existing_rating) {
                echo "<div style='color:#f39c12;'>" . str_repeat('★', (int)round($existing_rating['Rating'])) . "</div>";
            } else {
                echo "<a class='action-link' href='rate_order.php?order_id=" . (int)$row['ID'] . "'>Rate Order</a><br>";
            }
        }
        if (in_array($row['Status'], ['Delivered', 'Cancelled'], true)) {
            echo "<a class='action-link' href='checkout.php?reorder_from=" . (int)$row['ID'] . "'>Order Again</a>";
        }
        if (!in_array($row['Status'], array_merge(['Pending'], ['Delivered', 'Cancelled']), true)) {
            echo "—";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} 
else 
{
    echo "<div class='order-history-message'>You have not placed any orders yet.</div>";
}

echo "</div>";

if ($has_active_order) {
    echo "<script>setTimeout(function () { window.location.reload(); }, 20000);</script>";
}

include '../includes/footer.php';
?>
