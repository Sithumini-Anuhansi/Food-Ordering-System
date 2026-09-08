<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering System - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include '../includes/admin_header.php'; ?>

<div class="admin-container">
    <h2>Manage Food Items</h2>
    <?php
    if (isset($_SESSION['success'])) {
        echo "<div class='success-message'>" . htmlspecialchars($_SESSION['success']) . "</div>";
        unset($_SESSION['success']);
    }
    ?>
    <a class="add-btn" href="../crud/create_food.php"><i class="fa fa-plus"></i> Add New Food Item</a>
    <?php
    $query = "SELECT Item_ID, Name, Description, Price, Image, Category, Rating FROM food_items ORDER BY Name ASC";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo "<p>Error fetching food items: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
        include '../includes/footer.php';
        exit;
    }

    echo "<table>";
    echo "<thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Description</th>
                <th>Category</th>
                <th>Price (₹)</th>
                <th>Rating</th>
                <th>Actions</th>
            </tr>
          </thead>
          <tbody>";

    while ($row = mysqli_fetch_assoc($result)) {
        $id = (int)$row['Item_ID'];
        $name = htmlspecialchars($row['Name']);
        $description = htmlspecialchars($row['Description']);
        $price = number_format($row['Price'], 2);
        $image = htmlspecialchars($row['Image']);
        $category = htmlspecialchars($row['Category']);
        $rating = htmlspecialchars($row['Rating']);

        // Show stars for rating
        $stars = str_repeat('★', (int)$rating) . str_repeat('☆', 5 - (int)$rating);

        // Food images (seeded and admin-uploaded) all live in /image now.
        if ($image && file_exists("../image/{$image}")) {
            $imgSrc = "../image/{$image}";
        } else {
            $imgSrc = "../image/Food-Plate.png";
        }

        echo "<tr>";
        echo "<td>{$id}</td>";
        echo "<td><img src='{$imgSrc}' alt='{$name}' class='food-img'></td>";
        echo "<td>{$name}</td>";
        echo "<td>{$description}</td>";
        echo "<td><span class='category-badge'>{$category}</span></td>";
        echo "<td>{$price}</td>";
        echo "<td class='rating'>{$stars}</td>";
        echo "<td class='action-links'>
                <a href='../crud/update_food.php?id={$id}'><i class='fa fa-edit'></i> Edit</a>
                <form method='POST' action='../crud/delete_food.php' style='display:inline;' onsubmit=\"return confirm('Delete this item?')\">
                    " . csrf_field() . "
                    <input type='hidden' name='id' value='{$id}'>
                    <button type='submit' class='action-link' style='border:none;background:none;cursor:pointer;color:#e74c3c;padding:0;font:inherit;'><i class='fa fa-trash'></i> Delete</button>
                </form>
              </td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
