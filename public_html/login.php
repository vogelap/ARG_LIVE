<?php
// Start the session to check for login status and display errors
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - A Galactic Event</title>
    
    <link rel="stylesheet" href="css/style.css">

</head>
<body>
    <div class="login-container">
        <h2>A Galactic Event Login</h2>

        <?php
        // Display login error messages if they exist
        if (isset($_SESSION['login_error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
            // Unset the error message so it doesn't show again on refresh
            unset($_SESSION['login_error']);
        }
        ?>

        <form action="process_login.php" method="POST">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
    </div>
</body>
</html>