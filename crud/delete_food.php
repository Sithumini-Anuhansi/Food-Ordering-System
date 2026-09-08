<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';

if (!isset($conn) || !$conn) {
    header("Location: ../admin/manage_food_items.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: ../admin/manage_food_items.php");
    exit();
}

csrf_verify();

$itemId = intval($_POST['id']);

if ($itemId <= 0) {
    header("Location: ../admin/manage_food_items.php");
    exit();
}

$stmt = $conn->prepare("DELETE FROM food_items WHERE Item_ID = ?");
$error_message = '';

if ($stmt) {
    $stmt->bind_param("i", $itemId);

    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['success'] = "Food item deleted successfully.";
        header("Location: ../admin/manage_food_items.php");
        exit();
    } else {
        $error_message = "Error deleting item: " . $stmt->error;
        $stmt->close();
    }
} else {
    $error_message = "Error preparing delete statement: " . $conn->error;
}

// Only reached if something went wrong (the happy path already redirected).
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Food Item</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include '../includes/admin_header.php'; ?>
<div class="admin-form-container">
    <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
    <p><a class="action-link" href="../admin/manage_food_items.php">&larr; Back to Food Items</a></p>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>
