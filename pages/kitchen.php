<?php
include '../includes/session_check.php';
checkAnyRole(['kitchen', 'admin']);
include '../configure.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    csrf_verify();

    $order_id = intval($_POST['order_id']);
    $allowed_transitions = [
        'Pending'   => 'Preparing',
        'Preparing' => 'Ready for Delivery',
    ];
    $new_status = $_POST['new_status'];

    if (in_array($new_status, $allowed_transitions, true)) {
        $stmt = $conn->prepare("UPDATE orders SET Status = ? WHERE ID = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            $_SESSION['flash'] = "Order #$order_id updated to \"$new_status\".";
        } else {
            $_SESSION['flash'] = "Could not update order #$order_id.";
        }
        $stmt->close();
    }
    header("Location: kitchen.php");
    exit();
}

$query = "SELECT o.ID, u.Name AS Customer_Name, o.Total, o.Status, o.Order_Time
          FROM orders o
          JOIN users u ON o.User_ID = u.User_ID
          WHERE o.Status IN ('Pending', 'Preparing')
          ORDER BY o.Order_Time ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kitchen Dashboard - Food Ordering System</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php
$staff_role_label = 'Kitchen';
$staff_home = 'kitchen.php';
include '../includes/staff_header.php';
?>

<div class="admin-orders-container">
    <h2>Kitchen Queue</h2>
    <p style="text-align:center; color:#666;">Orders waiting to be prepared, in the order they came in.</p>

    <?php if ($flash): ?>
        <div class="success-message"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <?php if (!$result): ?>
        <p style="color:red;">Error fetching orders: <?= htmlspecialchars(mysqli_error($conn)) ?></p>
    <?php elseif (mysqli_num_rows($result) === 0): ?>
        <div class="order-history-message">No pending orders right now. Nice and quiet!</div>
    <?php else: ?>
        <table class="admin-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Order Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)):
                    $next_status = $row['Status'] === 'Pending' ? 'Preparing' : 'Ready for Delivery';
                    $button_label = $row['Status'] === 'Pending' ? 'Start Preparing' : 'Mark Ready for Delivery';
                ?>
                <tr>
                    <td><?= (int)$row['ID'] ?></td>
                    <td><?= htmlspecialchars($row['Customer_Name']) ?></td>
                    <td><?= number_format($row['Total'], 2) ?></td>
                    <td><?= htmlspecialchars($row['Status']) ?></td>
                    <td><?= htmlspecialchars($row['Order_Time']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$row['ID'] ?>">
                            <input type="hidden" name="new_status" value="<?= htmlspecialchars($next_status) ?>">
                            <button type="submit" class="action-link" style="border:none; background:none; cursor:pointer; color:#3498db; font-weight:bold;">
                                <?= htmlspecialchars($button_label) ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
