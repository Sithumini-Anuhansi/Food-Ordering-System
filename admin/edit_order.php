<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';

$valid_statuses = ['Pending', 'Preparing', 'Ready for Delivery', 'Delivered', 'Cancelled'];
$error = '';

if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    header("Location: manage_orders.php");
    exit();
}
$id = intval($_GET['id']);

// Fetch existing order + customer name
$stmt = $conn->prepare(
    "SELECT o.ID, o.Status, o.Total, o.Order_Time, u.Name AS Customer_Name
     FROM orders o JOIN users u ON o.User_ID = u.User_ID
     WHERE o.ID = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "<div class='error-message'>Order not found.</div>";
    include '../includes/footer.php';
    exit();
}
$order = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $new_status = $_POST['status'] ?? '';
    if (in_array($new_status, $valid_statuses, true)) {
        $upd = $conn->prepare("UPDATE orders SET Status = ? WHERE ID = ?");
        $upd->bind_param("si", $new_status, $id);
        if ($upd->execute()) {
            $_SESSION['success'] = "Order #$id updated to \"$new_status\".";
            header("Location: manage_orders.php");
            exit();
        } else {
            $error = "Error updating order: " . $upd->error;
        }
        $upd->close();
    } else {
        $error = "Invalid status selected.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Order - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include '../includes/admin_header.php'; ?>

<div class="admin-form-container">
    <h2>Edit Order #<?= (int)$order['ID'] ?></h2>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <p><strong>Customer:</strong> <?= htmlspecialchars($order['Customer_Name']) ?></p>
    <p><strong>Total:</strong> <?= number_format($order['Total'], 2) ?></p>
    <p><strong>Placed:</strong> <?= htmlspecialchars($order['Order_Time']) ?></p>

    <form class="admin-form" method="POST" action="edit_order.php?id=<?= (int)$order['ID'] ?>">
        <?= csrf_field() ?>
        <label>
            Status:
            <select name="status" required>
                <?php foreach ($valid_statuses as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= $order['Status'] === $status ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <input type="submit" value="Update Status">
    </form>
    <p><a class="action-link" href="manage_orders.php">&larr; Back to Orders</a></p>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
