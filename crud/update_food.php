<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';

if (!isset($conn) || !$conn) {
    echo "<div class='error-message'>Database connection failed.</div>";
    include '../includes/footer.php';
    exit();
}

if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    header("Location: ../admin/manage_food_items.php");
    exit();
}

$id = intval($_GET['id']);
$error = '';

// Fetch existing data
$stmt = $conn->prepare("SELECT Name, Description, Price, Image, Category, Rating, Stock_Quantity FROM food_items WHERE Item_ID=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $description, $price, $image, $category, $rating, $stock);
if (!$stmt->fetch()) {
    echo "<div class='error-message'>Item not found.</div>";
    include '../includes/footer.php';
    exit();
}
$stmt->close();

$allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxUploadBytes = 5 * 1024 * 1024; // 5 MB

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_verify();

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    $rating = floatval($_POST['rating']);
    $imagePath = $image; // default to existing

    $stockInput = trim($_POST['stock'] ?? '');
    $stockValue = ($stockInput === '') ? null : max(0, intval($stockInput));

    // Image upload if provided — stored in /image alongside the rest of the
    // site's food photos (same convention the seeded menu items use).
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['image']['size'] > $maxUploadBytes) {
            $error = "Image is too large (max 5MB).";
        } else {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes, true)) {
                $error = "Invalid image format. Only JPG, JPEG, PNG, GIF, WEBP allowed.";
            } else {
                $imageName = 'food_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetFile = '../image/' . $imageName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = $imageName;
                } else {
                    $error = "Image upload failed.";
                }
            }
        }
    }

    if (empty($error) && $name && $price > 0 && $category) {
        $stmt = $conn->prepare("UPDATE food_items SET Name=?, Description=?, Price=?, Image=?, Category=?, Rating=?, Stock_Quantity=? WHERE Item_ID=?");
        $stmt->bind_param("ssdssdii", $name, $description, $price, $imagePath, $category, $rating, $stockValue, $id);

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['success'] = "Food item updated successfully!";
            header("Location: ../admin/manage_food_items.php");
            exit();
        } else {
            $error = "Error updating item: " . $stmt->error;
            $stmt->close();
        }
    } else {
        if (empty($error)) {
            $error = "Please fill all fields correctly.";
        }
    }

    // Keep the form showing what the admin just typed, including the new image if one was set.
    $image = $imagePath;
    $stock = $stockValue;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Food Item - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<?php include '../includes/admin_header.php'; ?>
<div class="admin-form-container">
    <h2>Edit Food Item</h2>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form class="admin-form" action="update_food.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>
            Name:
            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
        </label>
        <label>
            Description:
            <textarea name="description" rows="3"><?= htmlspecialchars($description) ?></textarea>
        </label>
        <label>
            Price:
            <input type="number" step="0.01" min="0.01" name="price" value="<?= htmlspecialchars($price) ?>" required>
        </label>
        <label>
            Category:
            <select name="category" required>
                <?php
                $categories = ['Hot Picks', 'Fast Food', 'Pizza', 'Main Dishes', 'Salads', 'Desserts', 'Beverages'];
                foreach ($categories as $cat) {
                    $selected = ($category === $cat) ? 'selected' : '';
                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }
                ?>
            </select>
        </label>
        <br>

        <label>
            Rating:
            <input type="number" step="0.1" max="5" min="0" name="rating" value="<?= htmlspecialchars($rating) ?>" required>
        </label>
        <label>
            Stock Quantity (optional — leave blank for unlimited):
            <input type="number" step="1" min="0" name="stock" value="<?= htmlspecialchars($stock ?? '') ?>" placeholder="Leave blank for unlimited">
        </label>
        <label>
            Image: <br>
            <?php if (!empty($image)): ?>
                <img src="../image/<?= htmlspecialchars($image) ?>" alt="Current Image" style="max-width: 120px; display:block; margin: 8px 0;">
            <?php endif; ?>
            <input type="file" name="image" accept="image/*">
        </label>
        <input type="submit" value="Update Item">
    </form>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>
