<?php
include '../includes/session_check.php';
checkAnyRole(['delivery', 'admin']);
include '../configure.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    csrf_verify();

    $order_id = intval($_POST['order_id']);
    $new_status = 'Delivered';

    $stmt = $conn->prepare("UPDATE orders SET Status = ? WHERE ID = ? AND Status = 'Ready for Delivery'");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute()) {
        $_SESSION['flash'] = "Order #$order_id marked as delivered.";
    } else {
        $_SESSION['flash'] = "Could not update order #$order_id.";
    }
    $stmt->close();

    header("Location: delivery.php");
    exit();
}

$query = "SELECT o.ID, u.Name AS Customer_Name, u.Address, u.Phone_Number, o.Total, o.Order_Time
          FROM orders o
          JOIN users u ON o.User_ID = u.User_ID
          WHERE o.Status = 'Ready for Delivery'
          ORDER BY o.Order_Time ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Dashboard - Food Ordering System</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php
$staff_role_label = 'Delivery';
$staff_home = 'delivery.php';
include '../includes/staff_header.php';
?>

<div class="admin-orders-container">
    <h2>Ready for Delivery</h2>
    <p style="text-align:center; color:#666;">Orders the kitchen has finished, waiting to go out.</p>

    <?php if ($flash): ?>
        <div class="success-message"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <?php if (!$result): ?>
        <p style="color:red;">Error fetching orders: <?= htmlspecialchars(mysqli_error($conn)) ?></p>
    <?php elseif (mysqli_num_rows($result) === 0): ?>
        <div class="order-history-message">Nothing waiting to be delivered right now.</div>
    <?php else: ?>
        <table class="admin-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Total</th>
                    <th>Ready Since</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= (int)$row['ID'] ?></td>
                    <td><?= htmlspecialchars($row['Customer_Name']) ?></td>
                    <td><?= htmlspecialchars($row['Address']) ?></td>
                    <td><?= htmlspecialchars($row['Phone_Number']) ?></td>
                    <td><?= number_format($row['Total'], 2) ?></td>
                    <td><?= htmlspecialchars($row['Order_Time']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$row['ID'] ?>">
                            <button type="submit" class="action-link" style="border:none; background:none; cursor:pointer; color:#3498db; font-weight:bold;">
                                Mark Delivered
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    setTimeout(function () { window.location.reload(); }, 30000);
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
