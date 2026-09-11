<?php
include '../includes/session_check.php';
checkAnyRole(['delivery', 'admin']);
include '../configure.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$driver_id = $_SESSION['user_id'];
$is_admin_viewing = strtolower($_SESSION['role']) === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    csrf_verify();

    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];

    if ($action === 'claim') {
        // Only succeeds if nobody else has already claimed it — the WHERE
        // clause is what actually prevents two drivers grabbing the same
        // order at the same moment (a race between two claim clicks).
        $stmt = $conn->prepare("UPDATE orders SET Assigned_Driver_ID = ? WHERE ID = ? AND Status = 'Ready for Delivery' AND Assigned_Driver_ID IS NULL");
        $stmt->bind_param("ii", $driver_id, $order_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['flash'] = "Order #$order_id claimed — it's yours to deliver.";
        } else {
            $_SESSION['flash'] = "Couldn't claim order #$order_id — someone else may have just taken it.";
        }
        $stmt->close();
    } elseif ($action === 'release') {
        $stmt = $conn->prepare("UPDATE orders SET Assigned_Driver_ID = NULL WHERE ID = ? AND Assigned_Driver_ID = ?");
        $stmt->bind_param("ii", $order_id, $driver_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['flash'] = "Order #$order_id released back to the pool.";
        }
        $stmt->close();
    } elseif ($action === 'deliver') {
        $new_status = 'Delivered';
        // Admin can override and mark any order delivered; a driver can
        // only complete deliveries they've actually claimed.
        if ($is_admin_viewing) {
            $stmt = $conn->prepare("UPDATE orders SET Status = ? WHERE ID = ? AND Status = 'Ready for Delivery'");
            $stmt->bind_param("si", $new_status, $order_id);
        } else {
            $stmt = $conn->prepare("UPDATE orders SET Status = ? WHERE ID = ? AND Status = 'Ready for Delivery' AND Assigned_Driver_ID = ?");
            $stmt->bind_param("sii", $new_status, $order_id, $driver_id);
        }
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['flash'] = "Order #$order_id marked as delivered.";
            require_once '../includes/notifications.php';
            notify_order_status_change($conn, $order_id, $new_status);
        } else {
            $_SESSION['flash'] = "Could not update order #$order_id.";
        }
        $stmt->close();
    }

    header("Location: delivery.php");
    exit();
}

// Available to claim: nobody's picked it up yet.
$available_query = "SELECT o.ID, u.Name AS Customer_Name, u.Address, u.Phone_Number, o.Total, o.Order_Time
          FROM orders o
          JOIN users u ON o.User_ID = u.User_ID
          WHERE o.Status = 'Ready for Delivery' AND o.Assigned_Driver_ID IS NULL
          ORDER BY o.Order_Time ASC";
$available_result = mysqli_query($conn, $available_query);

// This driver's own active deliveries (or, for admin, everyone's claimed orders).
if ($is_admin_viewing) {
    $mine_query = "SELECT o.ID, u.Name AS Customer_Name, u.Address, u.Phone_Number, o.Total, o.Order_Time, d.Name AS Driver_Name
              FROM orders o
              JOIN users u ON o.User_ID = u.User_ID
              LEFT JOIN users d ON o.Assigned_Driver_ID = d.User_ID
              WHERE o.Status = 'Ready for Delivery' AND o.Assigned_Driver_ID IS NOT NULL
              ORDER BY o.Order_Time ASC";
    $mine_result = mysqli_query($conn, $mine_query);
} else {
    $mine_stmt = $conn->prepare(
        "SELECT o.ID, u.Name AS Customer_Name, u.Address, u.Phone_Number, o.Total, o.Order_Time
         FROM orders o
         JOIN users u ON o.User_ID = u.User_ID
         WHERE o.Status = 'Ready for Delivery' AND o.Assigned_Driver_ID = ?
         ORDER BY o.Order_Time ASC"
    );
    $mine_stmt->bind_param("i", $driver_id);
    $mine_stmt->execute();
    $mine_result = $mine_stmt->get_result();
}
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
<?php include '../includes/verification_banner.php'; ?>

<div class="admin-orders-container">
    <?php if ($flash): ?>
        <div class="success-message"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <h2><?= $is_admin_viewing ? 'Claimed Deliveries' : 'My Deliveries' ?></h2>
    <?php if (!$is_admin_viewing): ?>
        <p style="text-align:center; color:#666;">Orders you've claimed — mark them delivered once dropped off.</p>
    <?php endif; ?>

    <?php if (!$mine_result || mysqli_num_rows($mine_result) === 0): ?>
        <div class="order-history-message"><?= $is_admin_viewing ? 'Nobody has claimed a delivery yet.' : "You haven't claimed any deliveries yet — grab one below." ?></div>
    <?php else: ?>
        <table class="admin-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <?php if ($is_admin_viewing): ?><th>Driver</th><?php endif; ?>
                    <th>Total</th>
                    <th>Ready Since</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($mine_result)): ?>
                <tr>
                    <td><?= (int)$row['ID'] ?></td>
                    <td><?= htmlspecialchars($row['Customer_Name']) ?></td>
                    <td><?= htmlspecialchars($row['Address']) ?></td>
                    <td><?= htmlspecialchars($row['Phone_Number']) ?></td>
                    <?php if ($is_admin_viewing): ?><td><?= htmlspecialchars($row['Driver_Name'] ?? '—') ?></td><?php endif; ?>
                    <td><?= number_format($row['Total'], 2) ?></td>
                    <td><?= htmlspecialchars($row['Order_Time']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$row['ID'] ?>">
                            <input type="hidden" name="action" value="deliver">
                            <button type="submit" class="action-link" style="border:none; background:none; cursor:pointer; color:#3498db; font-weight:bold;">
                                Mark Delivered
                            </button>
                        </form>
                        <?php if (!$is_admin_viewing): ?>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= (int)$row['ID'] ?>">
                            <input type="hidden" name="action" value="release">
                            <button type="submit" class="action-link" style="border:none; background:none; cursor:pointer; color:#888;">
                                Release
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2 style="margin-top:32px;">Available to Claim</h2>
    <?php if (!$available_result || mysqli_num_rows($available_result) === 0): ?>
        <div class="order-history-message">Nothing waiting to be claimed right now.</div>
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
                <?php while ($row = mysqli_fetch_assoc($available_result)): ?>
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
                            <input type="hidden" name="action" value="claim">
                            <button type="submit" class="action-link" style="border:none; background:none; cursor:pointer; color:#2ecc71; font-weight:bold;">
                                Claim Delivery
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
