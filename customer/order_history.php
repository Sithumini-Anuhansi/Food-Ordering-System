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

echo "<div class='order-history-container'>";
echo "<h2>Your Order History</h2>";

if (!isset($_SESSION['user_id'])) 
{
    echo "<div class='order-history-message'>Session expired. Please log in again.</div>";
    echo "</div>";
    include '../includes/footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];

// Select orders for the user
$query = "SELECT ID, Total, Status, Order_Time FROM orders WHERE User_ID = ? ORDER BY Order_Time DESC";
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

if ($result->num_rows > 0) 
{
    echo "<table class='order-history-table'>";
    echo "<tr><th>Order ID</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr>";

    while ($row = $result->fetch_assoc()) 
    {
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

        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['ID']) . "</td>";
        echo "<td>" . $items_text . "</td>";
        echo "<td>" . htmlspecialchars($row['Total']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Order_Time']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} 
else 
{
    echo "<div class='order-history-message'>You have not placed any orders yet.</div>";
}

echo "</div>";

include '../includes/footer.php';
?>
