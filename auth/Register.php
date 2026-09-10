<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../configure.php';
require_once __DIR__ . '/../includes/mailer.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    csrf_verify();

    $allowed_roles = ['Customer', 'Kitchen', 'Delivery'];
    $role = in_array($_POST['role'], $allowed_roles, true) ? $_POST['role'] : 'Customer';
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header("Location: Register.php");
        exit();
    }

    if (strlen($_POST['password']) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters.";
        header("Location: Register.php");
        exit();
    }

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password

    // Check for existing email
    $checkQuery = "SELECT * FROM users WHERE Email = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already registered.";
    } else {
        $verify_token = bin2hex(random_bytes(32));
        $verify_expiry = date('Y-m-d H:i:s', time() + 86400); // 24 hours

        // Insert new user
        $insertQuery = "INSERT INTO users (Role, Name, Email, Password, Phone_Number, Address, Verification_Token, Verification_Token_Expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssssssss", $role, $name, $email, $password, $phone, $address, $verify_token, $verify_expiry);

        if ($stmt->execute()) {
            $base_url = rtrim(env('APP_BASE_URL', ''), '/');
            $verify_link = "$base_url/auth/verify_email.php?token=$verify_token";
            try {
                send_email($email, "Verify your email", "<p>Hi " . htmlspecialchars($name) . ",</p><p>Welcome! Please verify your email address to finish setting up your account:</p><p><a href=\"$verify_link\">$verify_link</a></p>");
            } catch (\Throwable $e) {
                error_log("Verification email failed: " . $e->getMessage());
            }
            $_SESSION['success'] = "Registration successful! Check your email to verify your account, then log in.";
            header("Location: Login.php");
        } else {
            $_SESSION['error'] = "Registration failed. Please try again.";
            header("Location: Register.php");
        }
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="/style.css">
</head>

<body class="register-body">
    <div class="register-card">
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">'.htmlspecialchars($_SESSION['error']).'</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="success-message">'.htmlspecialchars($_SESSION['success']).'</div>';
            unset($_SESSION['success']);
        }
        ?>
        <form name="RegisterForm" method="post" onsubmit="return validateFields()">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role" required>
                    <option value="" disabled selected>Select Role</option>
                    <option value="Customer">Customer</option>
                    <option value="Kitchen">Kitchen</option>
                    <option value="Delivery">Delivery</option>
                </select>
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" name="name" id="name" required placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required placeholder="Enter your password">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" name="phone" id="phone" required placeholder="Enter your phone number">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" name="address" id="address" required placeholder="Enter your address">
            </div>

            <input type="submit" name="register" value="REGISTER">
        </form>

        <a class="login-link" href="Login.php">Already have an account? Login</a>
    </div>

    <script>
        function validateFields() {
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                alert("Password must be at least 6 characters.");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
