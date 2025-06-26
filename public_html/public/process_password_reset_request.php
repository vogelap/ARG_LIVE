<?php
session_start();
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit();
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
    $_SESSION['reset_error'] = 'Invalid email address provided.';
    header('Location: forgot_password.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate a secure, random token
        $token = bin2hex(random_bytes(32));
        $expires = new DateTime('now + 1 hour');
        $expires_formatted = $expires->format('Y-m-d H:i:s');

        // Store the token and its expiration in the database
        $update_stmt = $pdo->prepare(
            "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?"
        );
        $update_stmt->execute([$token, $expires_formatted, $user['id']]);

        // Send the password reset email
        $reset_link = "https://arg.agalacticevent.com/public/reset_password.php?token=" . $token;
        $subject = "Password Reset Request for A Galactic Event";
        $body = "Hi,\n\nA password reset was requested for your account.\n";
        $body .= "If you did not request this, you can safely ignore this email.\n\n";
        $body .= "To reset your password, please click the following link:\n";
        $body .= $reset_link . "\n\n";
        $body .= "This link will expire in 1 hour.\n\nThank you.";
        $headers = "From: no-reply@arg.agalacticevent.com";

        // Note: For production, use a robust email library like PHPMailer
        // The built-in mail() function can be unreliable.
        mail($email, $subject, $body, $headers);
    }

    // Always show a success message to prevent user enumeration
    $_SESSION['success_message'] = 'If an account with that email exists, a password reset link has been sent.';
    header('Location: login.php');
    exit();

} catch (PDOException $e) {
    error_log("Password Reset Request Error: " . $e->getMessage());
    // Do not expose detailed errors to the user
    $_SESSION['reset_error'] = 'A server error occurred. Please try again later.';
    header('Location: forgot_password.php');
    exit();
}
?>