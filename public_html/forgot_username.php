<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/../includes/classes/MailManager.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $user_manager = new User($mysqli);
    
    $user_data = $user_manager->findByEmail($email);

    if ($user_data) {
        try {
            $mail_manager = new MailManager();
            $mail_manager->sendUsernameReminder($email, $user_data['username']);
        } catch (Exception $e) {
            $error = "Could not send email. Please contact the administrator. Mailer Error: " . $e->getMessage();
        }
    }
    
    if(empty($error)){
        $message = get_text('forgot_username_success_message');
    }
}
$page_title = get_text('forgot_username_title');
include __DIR__ . '/../templates/header.php';
?>
<div class="container login-container">
    <div class="card">
        <h2><?php echo get_text('forgot_username_title'); ?></h2>
        <p><?php echo get_text('forgot_username_instructions'); ?></p>
        <?php if ($message): ?><p class="success"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        <form action="forgot_username.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;"><?php echo get_text('forgot_username_button'); ?></button>
        </form>
        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9em;">
            <p><a href="login.php">&laquo; Back to Login</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>