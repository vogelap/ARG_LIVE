<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$user_manager = new User($mysqli);

$email = $user_manager->verifyPasswordResetToken($token);

if (!$email) {
    $error = get_text('reset_password_error_token');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password)) {
        $error = get_text('reset_password_error_empty');
    } elseif ($password !== $confirm_password) {
        $error = get_text('reset_password_error_mismatch');
    } else {
        if ($user_manager->resetPassword($email, $password)) {
            $success = get_text('reset_password_success');
        } else {
            $error = get_text('reset_password_error_server');
        }
    }
}

$page_title = get_text('reset_password_title');
include __DIR__ . '/../templates/header.php';
?>
<div class="container login-container">
    <div class="card">
        <h2><?php echo get_text('reset_password_title'); ?></h2>
        <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        <?php if ($success): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>

        <?php if ($email && !$success): ?>
        <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
            <div class="form-group">
                <label for="password"><?php echo get_text('reset_password_label_new'); ?></label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="confirm_password"><?php echo get_text('reset_password_label_confirm'); ?></label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;"><?php echo get_text('reset_password_button'); ?></button>
        </form>
        <?php elseif(!$success): ?>
            <p><a href="forgot_password.php"><?php echo get_text('reset_password_link_new_request'); ?></a></p>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>