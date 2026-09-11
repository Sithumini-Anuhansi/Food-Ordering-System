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
        if ($new_status === 'Preparing') {
            // Kitchen is accepting the order — record how long they expect it to take.
            $eta_minutes = max(1, intval($_POST['eta_minutes'] ?? 15));
            $stmt = $conn->prepare("UPDATE orders SET Status = ?, Estimated_Ready_Time = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE ID = ?");
            $stmt->bind_param("sii", $new_status, $eta_minutes, $order_id);
        } else {
            $stmt = $conn->prepare("UPDATE orders SET Status = ? WHERE ID = ?");
            $stmt->bind_param("si", $new_status, $order_id);
        }
        if ($stmt->execute()) {
            $_SESSION['flash'] = "Order #$order_id updated to \"$new_status\".";
            require_once '../includes/notifications.php';
            notify_order_status_change($conn, $order_id, $new_status);
        } else {
            $_SESSION['flash'] = "Could not update order #$order_id.";
        }
        $stmt->close();
    }
    header("Location: kitchen.php");
    exit();
}

$query = "SELECT o.ID, u.Name AS Customer_Name, o.Total, o.Status, o.Order_Time, o.Estimated_Ready_Time
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
<?php include '../includes/verification_banner.php'; ?>

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
                    $minutes_old = (time() - strtotime($row['Order_Time'])) / 60;
                    $is_stale = $minutes_old > 15;
                    $row_style = $is_stale ? 'background:#fdecea;' : '';
                ?>
                <tr style="<?= $row_style ?>">
                    <td><?= (int)$row['ID'] ?></td>
                    <td><?= htmlspecialchars($row['Customer_Name']) ?></td>
                    <td><?= number_format($row['Total'], 2) ?></td>
                    <td>
                        <?= htmlspecialchars($row['Status']) ?>
                        <?php if ($is_stale): ?>
                            <br><span style="color:#e74c3c; font-size:0.85em; font-weight:bold;">
                                <i class="fa fa-triangle-exclamation"></i> waiting <?= (int)$minutes_old ?> min
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['Order_Time']) ?></td>
                    <td>
                        <?php if ($row['Status'] === 'Preparing' && $row['Estimated_Ready_Time']): ?>
                            <div style="margin-bottom:6px; color:#666; font-size:0.85em;">
                                ETA: <?= htmlspecialchars(date('g:i A', strtotime($row['Estimated_Ready_Time']))) ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$row['ID'] ?>">
                            <input type="hidden" name="new_status" value="<?= htmlspecialchars($next_status) ?>">
                            <?php if ($row['Status'] === 'Pending'): ?>
                                <select name="eta_minutes" style="margin-right:6px;">
                                    <option value="10">10 min</option>
                                    <option value="15" selected>15 min</option>
                                    <option value="20">20 min</option>
                                    <option value="30">30 min</option>
                                    <option value="45">45 min</option>
                                </select>
                            <?php endif; ?>
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

<script>
    // Auto-refresh so new orders show up without a manual reload.
    setTimeout(function () { window.location.reload(); }, 30000);
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
