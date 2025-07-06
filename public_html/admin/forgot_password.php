<?php
require_once __DIR__ . '/../includes/db.php';
// UPDATED: Now uses the unified User class
require_once __DIR__ . '/../includes/classes/User.php'; 
require_once __DIR__ . '/../includes/classes/MailManager.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $user_manager = new User($mysqli);

    // Find a user by email from the unified users table
    $user = $user_manager->findByEmail($email);

    // UPDATED: Check if the user exists AND has the is_admin flag set
    if ($user && $user['is_admin']) {
        $token = $user_manager->generatePasswordResetToken($email);
        if ($token) {
            try {
                $mail_manager = new MailManager();
                // The `is_admin` flag is no longer needed here as the reset system is unified
                $mail_manager->sendPasswordResetLink($email, $token); 
            } catch (Exception $e) {
                // In a production environment, you would log the full error: error_log($e->getMessage());
                $error = "Could not send email. Please contact the server administrator to check the mail settings.";
            }
        }
    }
    
    // Always show a generic message to prevent leaking information about which emails are registered.
    if(empty($error)){
        $message = "If an admin account with that email exists, a password reset link has been sent.";
    }
}
include __DIR__ . '/../templates/admin_header.php';
?>
<div class="container" style="max-width: 400px;">
    <h2>Forgot Admin Password</h2>
    <p>Enter your administrator email address and we will send you a link to reset your password.</p>
    <?php if ($message): ?><p class="success"><?php echo $message; ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
    <form action="forgot_password.php" method="post">
        <div class="form-group">
            <label for="email">Admin Email Address</label>
            <input type="email" name="email" id="email" required>
        </div>
        <button type="submit" class="btn">Send Reset Link</button>
    </form>
    <p style="margin-top:1rem;"><a href="login.php">&laquo; Back to Login</a></p>
</div>
<?php include __DIR__ . '/../templates/admin_footer.php';?>