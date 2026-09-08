<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';

$valid_roles = ['admin', 'customer', 'kitchen', 'delivery'];
$error = '';

if (!isset($_GET['id']) || intval($_GET['id']) <= 0) {
    header("Location: manage_users.php");
    exit();
}
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT User_ID, Name, Email, Role, Phone_Number, Address FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "<div class='error-message'>User not found.</div>";
    include '../includes/footer.php';
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = strtolower($_POST['role']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!in_array($role, $valid_roles, true)) {
        $error = "Invalid role selected.";
    } elseif ($name === '') {
        $error = "Name is required.";
    } else {
        // Make sure the email isn't already used by a different account
        $check = $conn->prepare("SELECT User_ID FROM users WHERE Email = ? AND User_ID != ?");
        $check->bind_param("si", $email, $id);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "That email is already used by another account.";
        } else {
            $upd = $conn->prepare("UPDATE users SET Name=?, Email=?, Role=?, Phone_Number=?, Address=? WHERE User_ID=?");
            $upd->bind_param("sssssi", $name, $email, $role, $phone, $address, $id);
            if ($upd->execute()) {
                $_SESSION['success'] = "User updated successfully.";
                header("Location: manage_users.php");
                exit();
            } else {
                $error = "Error updating user: " . $upd->error;
            }
            $upd->close();
        }
        $check->close();
    }

    // Keep the form showing what the admin just typed
    $user = ['User_ID' => $id, 'Name' => $name, 'Email' => $email, 'Role' => $role, 'Phone_Number' => $phone, 'Address' => $address];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include '../includes/admin_header.php'; ?>

<div class="admin-form-container">
    <h2>Edit User #<?= (int)$user['User_ID'] ?></h2>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="admin-form" method="POST" action="edit_user.php?id=<?= (int)$user['User_ID'] ?>">
        <?= csrf_field() ?>
        <label>
            Name:
            <input type="text" name="name" value="<?= htmlspecialchars($user['Name']) ?>" required>
        </label>
        <label>
            Email:
            <input type="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required>
        </label>
        <label>
            Role:
            <select name="role" required>
                <?php foreach ($valid_roles as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>" <?= strtolower($user['Role']) === $r ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($r)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Phone Number:
            <input type="tel" name="phone" value="<?= htmlspecialchars($user['Phone_Number']) ?>">
        </label>
        <label>
            Address:
            <input type="text" name="address" value="<?= htmlspecialchars($user['Address']) ?>">
        </label>
        <input type="submit" value="Update User">
    </form>
    <p><a class="action-link" href="manage_users.php">&larr; Back to Users</a></p>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
