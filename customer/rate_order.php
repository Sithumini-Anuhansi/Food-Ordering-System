<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$error = '';

// Only your own Delivered orders can be rated, and only once.
$stmt = $conn->prepare("SELECT o.ID, o.Status, u.Name FROM orders o JOIN users u ON o.User_ID = u.User_ID WHERE o.ID = ? AND o.User_ID = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || $order['Status'] !== 'Delivered') {
    header("Location: order_history.php");
    exit();
}

$existing_stmt = $conn->prepare("SELECT Review_ID FROM reviews WHERE Order_ID = ? AND User_ID = ?");
$existing_stmt->bind_param("ii", $order_id, $user_id);
$existing_stmt->execute();
$already_rated = $existing_stmt->get_result()->num_rows > 0;
$existing_stmt->close();

if ($already_rated) {
    header("Location: order_history.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = "Please choose a star rating.";
    } elseif ($review_text === '') {
        $error = "Please write a short review.";
    } else {
        // Starts unapproved — an admin reviews it before it shows on the public Reviews page.
        $stmt = $conn->prepare(
            "INSERT INTO reviews (Name, Rating, Review_Text, Display_Order, Order_ID, User_ID, Approved)
             VALUES (?, ?, ?, 0, ?, ?, 0)"
        );
        $stmt->bind_param("sdsii", $order['Name'], $rating, $review_text, $order_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Thanks for the feedback! Your review will appear once approved.";
            header("Location: order_history.php");
            exit();
        } else {
            $error = "Could not save your review: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Your Order</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include '../includes/customer_header.php'; ?>

<div class="admin-form-container" style="max-width:600px;">
    <h2>Rate Order #<?= (int)$order['ID'] ?></h2>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="admin-form" method="POST" action="rate_order.php?order_id=<?= (int)$order['ID'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int)$order['ID'] ?>">

        <label>
            Rating:
            <select name="rating" required>
                <option value="" disabled selected>Select a rating</option>
                <option value="5">★★★★★ — Excellent</option>
                <option value="4">★★★★☆ — Good</option>
                <option value="3">★★★☆☆ — Okay</option>
                <option value="2">★★☆☆☆ — Not great</option>
                <option value="1">★☆☆☆☆ — Poor</option>
            </select>
        </label>

        <label>
            Your Review:
            <textarea name="review_text" rows="4" placeholder="How was the food and delivery?" required></textarea>
        </label>

        <input type="submit" value="Submit Review">
    </form>
    <p><a class="action-link" href="order_history.php">&larr; Back to Order History</a></p>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
