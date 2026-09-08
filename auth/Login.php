<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../configure.php';

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
        if (password_verify($password, $row['Password'])) {
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
            $_SESSION['error'] = "Incorrect Password.";
            header("Location: Login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "No user found with this email.";
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
