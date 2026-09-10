<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';

header('Content-Type: application/json');

$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT Payment_Status FROM orders WHERE ID = ? AND User_ID = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode(['payment_status' => $order['Payment_Status'] ?? null]);
