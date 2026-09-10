<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    header("Location: order_history.php");
    exit();
}

csrf_verify();

$order_id = intval($_POST['order_id']);
$user_id = $_SESSION['user_id'];

// Only the order's own owner can cancel it, and only while it's still Pending
// (once the kitchen has started on it, cancelling would waste real food/work).
$stmt = $conn->prepare("SELECT Status FROM orders WHERE ID = ? AND User_ID = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header("Location: order_history.php");
    exit();
}

if ($order['Status'] !== 'Pending') {
    $_SESSION['error'] = "This order can no longer be cancelled — it's already \"" . $order['Status'] . "\".";
    header("Location: order_history.php");
    exit();
}

$upd = $conn->prepare("UPDATE orders SET Status = 'Cancelled' WHERE ID = ? AND User_ID = ? AND Status = 'Pending'");
$upd->bind_param("ii", $order_id, $user_id);

if ($upd->execute() && $upd->affected_rows > 0) {
    // Give back any stock that was reserved for this order.
    $items_stmt = $conn->prepare("SELECT Item_ID, Quantity FROM order_items WHERE Order_ID = ?");
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();

    $restock_stmt = $conn->prepare("UPDATE food_items SET Stock_Quantity = Stock_Quantity + ?, available = 1 WHERE Item_ID = ? AND Stock_Quantity IS NOT NULL");
    while ($item = $items_result->fetch_assoc()) {
        $restock_stmt->bind_param("ii", $item['Quantity'], $item['Item_ID']);
        $restock_stmt->execute();
    }
    $items_stmt->close();
    $restock_stmt->close();

    $_SESSION['success'] = "Order #$order_id cancelled.";
    require_once '../includes/notifications.php';
    notify_order_status_change($conn, $order_id, 'Cancelled');
} else {
    $_SESSION['error'] = "Could not cancel this order — it may have already moved to preparation.";
}
$upd->close();

header("Location: order_history.php");
exit();
