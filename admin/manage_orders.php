<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering System - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include '../includes/admin_header.php'; ?>

<div class="admin-orders-container">
    <h2>Manage Orders</h2>
    <?php
    if (isset($_SESSION['success'])) {
        echo "<div class='success-message'>" . htmlspecialchars($_SESSION['success']) . "</div>";
        unset($_SESSION['success']);
    }

    $query = "SELECT o.ID, u.Name AS Customer_Name, o.Total, o.Status, o.Order_Time, o.Delivery_Address, o.Delivery_Time, o.Payment_Method, o.Payment_Status
              FROM orders o
              JOIN users u ON o.User_ID = u.User_ID
              ORDER BY o.Order_Time DESC";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo "<p style='color:red;'>Error fetching orders: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
        include '../includes/footer.php';
        exit;
    }

    echo "<table class='admin-orders-table'>";
    echo "<thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Delivery</th>
                <th>Payment</th>
                <th>Total</th>
                <th>Status</th>
                <th>Order Time</th>
                <th>Actions</th>
            </tr>
          </thead>
          <tbody>";

    while ($row = mysqli_fetch_assoc($result)) {
        $delivery_text = htmlspecialchars($row['Delivery_Time'] ?: 'ASAP');
        if (!empty($row['Delivery_Address'])) {
            $delivery_text .= "<br><span style='color:#666;font-size:0.85em;'>" . htmlspecialchars($row['Delivery_Address']) . "</span>";
        }
        $payment_text = htmlspecialchars($row['Payment_Method']) . "<br><span style='color:#666;font-size:0.85em;'>" . htmlspecialchars($row['Payment_Status']) . "</span>";

        echo "<tr>";
        echo "<td>" . (int)$row['ID'] . "</td>";
        echo "<td>" . htmlspecialchars($row['Customer_Name']) . "</td>";
        echo "<td>" . $delivery_text . "</td>";
        echo "<td>" . $payment_text . "</td>";
        echo "<td>" . number_format($row['Total'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Order_Time']) . "</td>";
        echo "<td><a class='action-link' href='edit_order.php?id=" . (int)$row['ID'] . "'>Edit Status</a></td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
