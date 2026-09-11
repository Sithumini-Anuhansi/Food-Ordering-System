<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['action'])) {
    csrf_verify();

    $review_id = intval($_POST['review_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE reviews SET Approved = 1 WHERE Review_ID = ?");
        $stmt->bind_param("i", $review_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Review approved.";
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE Review_ID = ? AND Order_ID IS NOT NULL");
        $stmt->bind_param("i", $review_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Review rejected and removed.";
    }
    header("Location: manage_reviews.php");
    exit();
}

$pending = mysqli_query($conn,
    "SELECT r.Review_ID, r.Rating, r.Review_Text, r.Order_ID, u.Name AS Customer_Name
     FROM reviews r
     LEFT JOIN users u ON r.User_ID = u.User_ID
     WHERE r.Approved = 0
     ORDER BY r.Review_ID DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reviews - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include '../includes/admin_header.php'; ?>

<div class="admin-container">
    <h2>Pending Reviews</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!$pending || mysqli_num_rows($pending) === 0): ?>
        <p style="text-align:center; color:#666;">No reviews waiting for approval.</p>
    <?php else: ?>
        <table class="admin-orders-table">
            <thead>
                <tr><th>Customer</th><th>Order</th><th>Rating</th><th>Review</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($pending)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['Customer_Name'] ?? 'Unknown') ?></td>
                    <td>#<?= (int)$row['Order_ID'] ?></td>
                    <td><?= str_repeat('★', (int)$row['Rating']) . str_repeat('☆', 5 - (int)$row['Rating']) ?></td>
                    <td><?= htmlspecialchars($row['Review_Text']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="review_id" value="<?= (int)$row['Review_ID'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" style="border:none;background:none;cursor:pointer;color:#2ecc71;font-weight:bold;">Approve</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Reject and delete this review?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="review_id" value="<?= (int)$row['Review_ID'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" style="border:none;background:none;cursor:pointer;color:#e74c3c;font-weight:bold;">Reject</button>
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
