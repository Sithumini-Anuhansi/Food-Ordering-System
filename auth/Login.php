<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../configure.php';

const MAX_FAILED_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;

if (isset($_POST['login'])) {
    csrf_verify();

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Locked out?
        if (!empty($row['Lockout_Until']) && strtotime($row['Lockout_Until']) > time()) {
            $minutes_left = ceil((strtotime($row['Lockout_Until']) - time()) / 60);
            $_SESSION['error'] = "Too many failed attempts. Please try again in $minutes_left minute(s), or reset your password.";
            header("Location: Login.php");
            exit();
        }

        if (password_verify($password, $row['Password'])) {
            // Successful login — clear any lockout state
            $reset = $conn->prepare("UPDATE users SET Failed_Login_Attempts = 0, Lockout_Until = NULL WHERE User_ID = ?");
            $reset->bind_param("i", $row['User_ID']);
            $reset->execute();
            $reset->close();

            // Regenerate the session ID on privilege change to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $row['User_ID'];
            $_SESSION['role'] = strtolower($row['Role']);
            $_SESSION['name'] = $row['Name'];
            switch ($_SESSION['role']) {
                case 'admin': header("Location: ../pages/admin.php"); break;
                case 'customer': header("Location: ../pages/customer.php"); break;
                case 'kitchen': header("Location: ../pages/kitchen.php"); break;
                case 'delivery': header("Location: ../pages/delivery.php"); break;
                default:
                    $_SESSION['error'] = "Invalid role.";
                    header("Location: Login.php");
                    break;
            }
            exit();
        } else {
            $attempts = (int)$row['Failed_Login_Attempts'] + 1;
            if ($attempts >= MAX_FAILED_ATTEMPTS) {
                $lockout_until = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
                $upd = $conn->prepare("UPDATE users SET Failed_Login_Attempts = 0, Lockout_Until = ? WHERE User_ID = ?");
                $upd->bind_param("si", $lockout_until, $row['User_ID']);
                $upd->execute();
                $upd->close();
                $_SESSION['error'] = "Too many failed attempts. Your account is locked for " . LOCKOUT_MINUTES . " minutes.";
            } else {
                $upd = $conn->prepare("UPDATE users SET Failed_Login_Attempts = ? WHERE User_ID = ?");
                $upd->bind_param("ii", $attempts, $row['User_ID']);
                $upd->execute();
                $upd->close();
                // Deliberately vague — don't reveal whether the email exists.
                $_SESSION['error'] = "Incorrect email or password.";
            }
            header("Location: Login.php");
            exit();
        }
    } else {
        // Deliberately the same message as a wrong password — don't let this
        // form be used to check which emails are registered.
        $_SESSION['error'] = "Incorrect email or password.";
        header("Location: Login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Food Ordering System</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="login-body">
    <div class="login-card">
        <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
        ?>
        <form name="LoginForm" onsubmit="return validateFields()" action="Login.php" method="post">
            <?= csrf_field() ?>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" required placeholder="Enter your email">

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required placeholder="Enter your password">

            <input type="submit" name="login" value="LOGIN">
        </form>
        <a class="login-link" href="forgot_password.php">Forgot password?</a>
        <a class="login-link" href="Register.php">Don't have an account? Register</a>
    </div>
    <script>
        function validateFields() {
            let txtEmail = document.getElementById("email").value;
            let txtPassword = document.getElementById("password").value;
            if(txtEmail == "" || txtPassword == "") {
                alert("Please fill all the fields.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
