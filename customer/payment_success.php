<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';

$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$order = null;

if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT ID, Payment_Status, Total FROM orders WHERE ID = ? AND User_ID = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - Food Ordering System</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include '../includes/customer_header.php'; ?>

<div class="order-message">
    <?php if (!$order): ?>
        We couldn't find that order.
    <?php elseif ($order['Payment_Status'] === 'Paid'): ?>
        <div class="order-spinner"></div>
        Payment received for order #<?= (int)$order['ID'] ?> — thank you!
    <?php else: ?>
        <div class="order-spinner"></div>
        Thanks — we're confirming your payment for order #<?= (int)$order['ID'] ?> now.
        This page will update automatically once it's confirmed.
    <?php endif; ?>
</div>
<p style="text-align:center;"><a class="action-link" href="order_history.php">View Order History</a></p>

<?php if ($order && $order['Payment_Status'] !== 'Paid'): ?>
<script>
    // Poll briefly for the webhook to land, then just send them to order
    // history either way — the order itself was already placed.
    let attempts = 0;
    const check = setInterval(function () {
        attempts++;
        if (attempts > 6) { clearInterval(check); window.location.href = 'order_history.php'; return; }
        fetch('order_status_check.php?order_id=<?= (int)$order['ID'] ?>')
            .then(r => r.json())
            .then(data => {
                if (data.payment_status === 'Paid') {
                    clearInterval(check);
                    window.location.reload();
                }
            })
            .catch(() => {});
    }, 3000);
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
</body>
</html>
