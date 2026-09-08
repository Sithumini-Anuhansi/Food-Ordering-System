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

<div class="admin-users-container">
    <h2>Manage Users</h2>
    <?php
    if (isset($_SESSION['success'])) {
        echo "<div class='success-message'>" . htmlspecialchars($_SESSION['success']) . "</div>";
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo "<div class='error-message'>" . htmlspecialchars($_SESSION['error']) . "</div>";
        unset($_SESSION['error']);
    }

    // Fetch all users
    $result = mysqli_query($conn, "SELECT User_ID, Name, Email, Role, Phone_Number, Address FROM users");

    if (!$result) {
        echo "<p style='color:red;'>Error fetching users: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
        include '../includes/footer.php';
        exit;
    }

    echo "<table class='admin-users-table'>";
    echo "<thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
          </thead>
          <tbody>";

    while ($row = mysqli_fetch_assoc($result)) {
        $isSelf = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$row['User_ID'];

        echo "<tr>";
        echo "<td>" . (int)$row['User_ID'] . "</td>";
        echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Phone_Number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Address']) . "</td>";
        echo "<td class='action-links'>
                <a href='edit_user.php?id=" . (int)$row['User_ID'] . "'>Edit</a>";
        if (!$isSelf) {
            echo "
                <form method='POST' action='delete_user.php' style='display:inline;' onsubmit=\"return confirm('Delete this user? This cannot be undone.')\">
                    " . csrf_field() . "
                    <input type='hidden' name='id' value='" . (int)$row['User_ID'] . "'>
                    <button type='submit' style='border:none;background:none;cursor:pointer;color:#e74c3c;padding:0;font:inherit;'>Delete</button>
                </form>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    ?>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
