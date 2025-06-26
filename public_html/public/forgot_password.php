<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Reset Password</h2>
        <p>Enter your email address and we will send you a link to reset your password.</p>
        <?php
        if (isset($_SESSION['reset_error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['reset_error']) . '</div>';
            unset($_SESSION['reset_error']);
        }
        ?>
        <form action="process_password_reset_request.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="login-btn">Send Reset Link</button>
        </form>
        <div class="extra-links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>