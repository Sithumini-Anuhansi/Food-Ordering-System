<?php
include '../includes/session_check.php';
checkRole('customer');
include '../configure.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering System - Browse Menu</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include '../includes/customer_header.php'; ?>

<?php
echo "<div class='menu-container'>";
echo "<h2>Browse Menu</h2>";

$query = "SELECT * FROM food_items WHERE available = 1 ORDER BY Name ASC";
$result = mysqli_query($conn, $query);

if (!$result)
{
    echo "<div class='menu-message'>Error fetching menu: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
    echo "</div>";
    include '../includes/footer.php';
    exit;
}

if (mysqli_num_rows($result) > 0)
{
    echo "<form action='place_order.php' method='POST'>";
    echo csrf_field();
    echo "<table class='menu-table'>";
    echo "<tr><th>Name</th><th>Description</th><th>Price</th><th>Quantity</th></tr>";

    while ($row = mysqli_fetch_assoc($result))
    {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
        echo "<td>Rs. " . number_format($row['Price'], 2) . "</td>";
        echo "<td><input type='number' name='Quantity[" . (int)$row['Item_ID'] . "]' min='0' value='0' step='1' style='width:60px;'></td>";
        echo "</tr>";
    }

    echo "</table><br>";
    echo "<input type='submit' value='Place Order' class='menu-submit-btn'>";
    echo "</form>";
}
else
{
    echo "<div class='menu-message'>No food items available.</div>";
}

echo "</div>";

include '../includes/footer.php';
?>
