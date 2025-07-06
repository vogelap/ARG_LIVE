<?php
// File: arg_game/public/profile.php

require_once __DIR__ . '/gatekeeper.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/../includes/classes/GameManager.php';

$player_id = $_SESSION['user_id'];
$user_manager = new User($mysqli);
$player = $user_manager->find($player_id);

$success_message = '';
$error_message = '';

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $error_message = get_text('ajax_error_security_token');
    } else {
        if ($user_manager->updateProfile($player_id, $_POST['username'], $_POST['email'], $_POST['password'])) {
            $success_message = get_text('profile_success_update');
            $_SESSION['username'] = $_POST['username']; // Update session username
            $player = $user_manager->find($player_id); // Refresh player data
        } else {
            $error_message = get_text('profile_error_update');
        }
    }
}

// Handle Progress Reset
if (isset($_POST['reset_progress'])) {
     if (!validate_csrf_token($_POST['csrf_token'])) {
        $error_message = get_text('ajax_error_security_token');
    } else {
        $game_manager = new GameManager($mysqli);
        if ($game_manager->resetPlayerProgress($player_id)) {
            $success_message = get_text('profile_success_reset');
        } else {
            $error_message = get_text('profile_error_reset');
        }
    }
}

$page_title = get_text('profile_title');
include __DIR__ . '/../templates/header.php';
?>
<div class="container">
    <h2><?php echo get_text('profile_title'); ?></h2>
    <?php if ($success_message): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>
    <?php if ($error_message): ?><p class="error"><?php echo $error_message; ?></p><?php endif; ?>

    <div class="card">
        <h3><?php echo get_text('profile_header_edit_details'); ?></h3>
        <form action="profile.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="form-group">
                <label for="username"><?php echo get_text('profile_label_username'); ?></label>
                <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($player['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email"><?php echo get_text('profile_label_email'); ?></label>
                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($player['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="password"><?php echo get_text('profile_label_new_password'); ?></label>
                <input type="password" name="password" id="password" class="form-control">
            </div>
            <button type="submit" name="update_profile" class="btn"><?php echo get_text('profile_button_update'); ?></button>
        </form>
    </div>

    <div class="card danger-zone">
        <h3><?php echo get_text('profile_danger_zone_header'); ?></h3>
        <p><?php echo get_text('profile_danger_zone_warning'); ?></p>
        <form action="profile.php" method="post" onsubmit="return confirm('Are you absolutely sure you want to reset all your progress? This cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" name="reset_progress" class="btn btn-danger"><?php echo get_text('profile_danger_zone_button'); ?></button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>