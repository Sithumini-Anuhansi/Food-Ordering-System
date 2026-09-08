<?php
include '../includes/session_check.php';
requireLogin();
include '../configure.php';

$user_id = $_SESSION['user_id'];
$role = strtolower($_SESSION['role']);

$profile_error = '';
$profile_success = '';
$password_error = '';
$password_success = '';

// Fetch current user record
$stmt = $conn->prepare("SELECT * FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    include '../includes/footer.php';
    exit();
}

// --- Handle profile info update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    csrf_verify();

    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($name === '') {
        $profile_error = "Name is required.";
    } else {
        $upd = $conn->prepare("UPDATE users SET Name = ?, Phone_Number = ?, Address = ? WHERE User_ID = ?");
        $upd->bind_param("sssi", $name, $phone, $address, $user_id);
        if ($upd->execute()) {
            $_SESSION['name'] = $name; // keep header greeting in sync
            $user['Name'] = $name;
            $user['Phone_Number'] = $phone;
            $user['Address'] = $address;
            $profile_success = "Profile updated successfully.";
        } else {
            $profile_error = "Error updating profile: " . $upd->error;
        }
        $upd->close();
    }
}

// --- Handle password change ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    csrf_verify();

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['Password'])) {
        $password_error = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $password_error = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $password_error = "New password and confirmation don't match.";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET Password = ? WHERE User_ID = ?");
        $upd->bind_param("si", $newHash, $user_id);
        if ($upd->execute()) {
            $password_success = "Password updated successfully.";
        } else {
            $password_error = "Could not update password: " . $upd->error;
        }
        $upd->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php
switch ($role) {
    case 'admin':
        include '../includes/admin_header.php';
        break;
    case 'customer':
        include '../includes/customer_header.php';
        break;
    case 'kitchen':
        $staff_role_label = 'Kitchen';
        $staff_home = 'kitchen.php';
        include '../includes/staff_header.php';
        break;
    case 'delivery':
        $staff_role_label = 'Delivery';
        $staff_home = 'delivery.php';
        include '../includes/staff_header.php';
        break;
}
?>

<div class="profile-container">
    <h2>My Profile</h2>

    <?php if ($profile_error): ?>
        <div class="error-message"><?= htmlspecialchars($profile_error) ?></div>
    <?php endif; ?>
    <?php if ($profile_success): ?>
        <div class="success-message"><?= htmlspecialchars($profile_success) ?></div>
    <?php endif; ?>

    <p><strong>User ID:</strong> <?= htmlspecialchars($user['User_ID']) ?></p>
    <p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($role)) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['Email']) ?> <span style="color:#888;">(contact support to change)</span></p>

    <form class="admin-form" method="POST" action="profile.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_profile">
        <label>
            Name:
            <input type="text" name="name" value="<?= htmlspecialchars($user['Name']) ?>" required>
        </label>
        <label>
            Phone Number:
            <input type="tel" name="phone" value="<?= htmlspecialchars($user['Phone_Number']) ?>">
        </label>
        <label>
            Address:
            <input type="text" name="address" value="<?= htmlspecialchars($user['Address']) ?>">
        </label>
        <input type="submit" value="Save Changes">
    </form>
</div>

<div class="profile-container" id="change-password">
    <h2>Change Password</h2>

    <?php if ($password_error): ?>
        <div class="error-message"><?= htmlspecialchars($password_error) ?></div>
    <?php endif; ?>
    <?php if ($password_success): ?>
        <div class="success-message"><?= htmlspecialchars($password_success) ?></div>
    <?php endif; ?>

    <form class="admin-form" method="POST" action="profile.php#change-password">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <label>
            Current Password:
            <input type="password" name="current_password" required>
        </label>
        <label>
            New Password:
            <input type="password" name="new_password" required minlength="6">
        </label>
        <label>
            Confirm New Password:
            <input type="password" name="confirm_password" required minlength="6">
        </label>
        <input type="submit" value="Update Password">
    </form>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
