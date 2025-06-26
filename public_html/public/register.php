<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/../includes/classes/GameManager.php';

$is_game_live = true;
if (defined('GAME_LIVE_DATETIME')) {
    if (time() < strtotime(GAME_LIVE_DATETIME)) {
        $is_game_live = false;
    }
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_game_live) {
        if (!validate_csrf_token($_POST['csrf_token'])) {
             $error_message = get_text('ajax_error_security_token');
        } else {
            $user_manager = new User($mysqli);
            $user_id = $user_manager->register($_POST['username'], $_POST['email'], $_POST['password']);

            if ($user_id) {
                $game_manager = new GameManager($mysqli);
                $game_manager->unlockInitialPuzzles($user_id);
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $_POST['username'];
                $_SESSION['is_admin'] = false;
                header("Location: index.php");
                exit;
            } else {
                $error_message = get_text('register_error_taken');
            }
        }
    }
}
$page_title = get_text('register_title');
include __DIR__ . '/../templates/header.php';
?>
<div class="container" style="max-width: 500px;">
    <div class="card">
        <h2 style="text-align: center;"><?php echo get_text('register_title'); ?></h2>
        <p style="text-align: center; color: var(--text-muted-color);"><?php echo htmlspecialchars(defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : ''); ?></p>
        <hr style="border-color: var(--border-color);">
        
        <?php if (!empty($error_message)): ?><p class="error"><?php echo $error_message; ?></p><?php endif; ?>

        <?php if ($is_game_live): ?>
            <form action="register.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <button type="submit" class="btn" style="width: 100%;"><?php echo get_text('register_button'); ?></button>
            </form>
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9em;">
                <p><?php echo get_text('register_link_to_login'); ?></p>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 20px;">
                <h3 style="color: var(--primary-color);"><?php echo get_text('register_status_header'); ?></h3>
                <p><?php echo get_text('register_status_closed'); ?></p>
                <p><strong><?php echo defined('GAME_LIVE_DATETIME') ? date('F j, Y \a\t g:i A', strtotime(GAME_LIVE_DATETIME)) : 'Soon'; ?></strong></p>
                <p style="margin-top: 2rem;"><a href="login.php" class="btn btn-secondary">Back to Login</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>