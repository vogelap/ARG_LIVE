<?php
session_start();
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$token = $_POST['token'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// --- Input Validation ---
if (!ctype_xdigit($token) || strlen($token) !== 64) {
    $_SESSION['update_error'] = 'Invalid or missing token.';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit();
}
if (strlen($new_password) < 8) {
    $_SESSION['update_error'] = 'Password must be at least 8 characters long.';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit();
}
if ($new_password !== $confirm_password) {
    $_SESSION['update_error'] = 'Passwords do not match.';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Token Validation ---
    $stmt = $pdo->prepare("SELECT id, password_reset_expires FROM users WHERE password_reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['login_error'] = 'Invalid or expired token. Please request a new password reset.';
        header('Location: login.php');
        exit();
    }

    $expires = new DateTime($user['password_reset_expires']);
    $now = new DateTime();

    if ($now > $expires) {
        $_SESSION['login_error'] = 'Your password reset token has expired. Please request a new one.';
        header('Location: login.php');
        exit();
    }

    // --- Update Password ---
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Set token to NULL so it cannot be used again
    $update_stmt = $pdo->prepare(
        "UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?"
    );
    $update_stmt->execute([$new_password_hash, $user['id']]);

    $_SESSION['success_message'] = 'Your password has been updated successfully. You can now log in.';
    header('Location: login.php');
    exit();

} catch (PDOException $e) {
    error_log("Password Update Error: " . $e->getMessage());
    $_SESSION['update_error'] = 'A server error occurred. Please try again later.';
    header('Location: reset_password.php?token=' . urlencode($token));
    exit();
}
?>