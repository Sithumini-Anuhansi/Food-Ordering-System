<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';

$error = '';
$success = '';

$allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxUploadBytes = 5 * 1024 * 1024; // 5 MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    $rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0.0;

    // Images live alongside the rest of the site's food photos in /image,
    // same folder the seeded menu items use, so the storefront can find them
    // without needing a second "uploads" convention.
    $imagePath = 'Food-Plate.png'; // sensible default if no image is uploaded

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        if ($_FILES['image']['size'] > $maxUploadBytes) {
            $error = "Image is too large (max 5MB).";
        } else {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes, true)) {
                $error = "Invalid image format. Only JPG, JPEG, PNG, GIF, WEBP allowed.";
            } else {
                // Generate a unique, safe filename — never trust the client's filename directly.
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

    if (!$error && $name && $price > 0 && $category) {
        $stmt = $conn->prepare("INSERT INTO food_items (Name, Description, Price, Image, Category, Rating) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssd", $name, $description, $price, $imagePath, $category, $rating);
        if ($stmt->execute()) {
            $success = "Food item added successfully.";
            $stmt->close();
        } else {
            $error = "Database Error: " . $stmt->error;
            $stmt->close();
        }
    } elseif (!$error) {
        $error = "Please fill all required fields correctly.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Food Item</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<?php include '../includes/admin_header.php'; ?>
<div class="admin-form-container">
    <h2>Add New Food Item</h2>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form class="admin-form" action="create_food.php" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>
            Name:
            <input type="text" name="name" required>
        </label>

        <label>
            Description:
            <textarea name="description" rows="3" required></textarea>
        </label>

        <label>
            Price:
            <input type="number" name="price" step="0.01" min="0.01" required>
        </label>

        <label>
            Image:
            <input type="file" name="image" accept="image/*">
        </label>
        <br>

        <label>
            Category:
            <select name="category" required>
                <option value="" disabled selected>Select Category</option>
                <option value="Hot Picks">Hot Picks</option>
                <option value="Fast Food">Fast Food</option>
                <option value="Pizza">Pizza</option>
                <option value="Main Dishes">Main Dishes</option>
                <option value="Salads">Salads</option>
                <option value="Desserts">Desserts</option>
                <option value="Beverages">Beverages</option>
            </select>
        </label>
        <br>

        <label>
            Rating (optional):
            <input type="number" name="rating" step="0.1" min="0" max="5" placeholder="0.0 to 5.0">
        </label>

        <input type="submit" value="Add Item">
    </form>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
