<?php
session_start();
// Validate token from URL
$token = $_GET['token'] ?? '';
if (!ctype_xdigit($token) || strlen($token) !== 64) {
    die("Invalid token format.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter New Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Create New Password</h2>
        <?php
        if (isset($_SESSION['update_error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['update_error']) . '</div>';
            unset($_SESSION['update_error']);
        }
        ?>
        <form action="process_password_update.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="login-btn">Update Password</button>
        </form>
    </div>
</body>
</html>