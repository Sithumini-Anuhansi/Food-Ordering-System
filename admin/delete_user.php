<?php
include '../includes/session_check.php';
checkRole('admin');
include '../configure.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: manage_users.php");
    exit();
}

csrf_verify();

$id = intval($_POST['id']);

if ($id <= 0) {
    header("Location: manage_users.php");
    exit();
}

// Don't allow an admin to delete their own account from this screen.
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
    $_SESSION['error'] = "You can't delete your own account while logged in as it.";
    header("Location: manage_users.php");
    exit();
}

// Look up the target user first so we can protect the last remaining admin.
$stmt = $conn->prepare("SELECT Role FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: manage_users.php");
    exit();
}
$target = $result->fetch_assoc();
$stmt->close();

if (strtolower($target['Role']) === 'admin') {
    $countResult = mysqli_query($conn, "SELECT COUNT(*) AS admin_count FROM users WHERE Role = 'admin'");
    $countRow = mysqli_fetch_assoc($countResult);
    if ((int)$countRow['admin_count'] <= 1) {
        $_SESSION['error'] = "Can't delete the last remaining admin account.";
        header("Location: manage_users.php");
        exit();
    }
}

// A user's past orders should stick around for records/reporting, so we
// don't delete their order history here — only the account itself.
$del = $conn->prepare("DELETE FROM users WHERE User_ID = ?");
$del->bind_param("i", $id);

if ($del->execute()) {
    $_SESSION['success'] = "User deleted successfully.";
} elseif ($conn->errno === 1451) {
    // Foreign key constraint: this user still has order history.
    $_SESSION['error'] = "Can't delete this user — they still have order history on file.";
} else {
    $_SESSION['error'] = "Could not delete user: " . $del->error;
}
$del->close();

header("Location: manage_users.php");
exit();
