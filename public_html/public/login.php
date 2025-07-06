<?php
// File: arg_game/public/login.php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';

$error = '';
$user_manager = new User($mysqli);

// This function handles the final redirect after a successful login
function handle_login_redirect() {
    if (isset($_SESSION['player_view_redirect'])) {
        $redirect_url = $_SESSION['player_view_redirect'];
        unset($_SESSION['player_view_redirect']); // Clear the session variable
        header("Location: " . $redirect_url);
    } else {
        header("Location: index.php");
    }
    exit;
}

// Function to set session variables after a successful login
function set_login_session($user_data) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['is_admin'] = (bool)$user_data['is_admin'];
    // MODIFIED: Set the intro seen status in the session
    $_SESSION['has_seen_intro'] = (bool)$user_data['has_seen_intro'];
}

if (isset($_GET['login_token'])) {
    $user_id = $user_manager->verifyLoginToken($_GET['login_token']);
    if ($user_id) {
        // MODIFIED: login method now returns the has_seen_intro flag
        $user_data = $user_manager->find($user_id); 
        if ($user_data) {
            $user_data_full = User::login($mysqli, $user_data['email'], null); // We need the full user record
            set_login_session($user_data_full);
            $user_manager->updateLastLogin($user_data['id']);
            
            handle_login_redirect();
        }
    }
    $error = get_text('login_error_expired_token');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $user_data = User::login($mysqli, $_POST['email'], $_POST['password']);
        if ($user_data) {
            set_login_session($user_data);
            $user_manager->updateLastLogin($user_data['id']);

            handle_login_redirect();
        } else {
            $error = get_text('login_error_credentials');
        }
    } else {
        $error = get_text('login_error_missing_fields');
    }
}

if (is_logged_in() && !isset($_GET['login_token'])) {
    handle_login_redirect();
}

$page_title = get_text('login_title');
include __DIR__ . '/../templates/header.php';
?>
<div class="container" style="max-width: 500px;">
    <div class="card">
        <h2 style="text-align: center;"><?php echo get_text('login_title'); ?></h2>
        <?php if (!empty($error)): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group"><label for="email">Email</label><input type="email" name="email" id="email" class="form-control" required></div>
            <div class="form-group"><label for="password">Password</label><input type="password" name="password" id="password" class="form-control" required></div>
            <button type="submit" class="btn" style="width: 100%;"><?php echo get_text('login_button'); ?></button>
        </form>
        
        <div style="display: flex; justify-content: space-between; margin-top: 1.5rem; font-size: 0.9em;">
            <a href="forgot_username.php">Forgot Username?</a>
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
        
        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <p>Don't have an account? <a href="register.php">Register here</a>.</p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>