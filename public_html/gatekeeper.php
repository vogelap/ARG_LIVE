<?php
/**
 * This file acts as a gatekeeper for all public pages.
 * It checks if the game is live. If not, it shows the locked page and stops execution.
 */

require_once __DIR__ . '/../includes/session.php';

// The login and password reset pages should always be accessible.
$current_page = basename($_SERVER['SCRIPT_NAME']);
// --- MODIFIED: Added 'register.php' to the array of unlocked pages ---
$unlocked_pages = ['login.php', 'register.php', 'forgot_username.php', 'forgot_password.php', 'reset_password.php'];

// The lock will be bypassed if an admin is logged in.
if (!is_admin() && !in_array($current_page, $unlocked_pages)) {
    
    // For non-admins, we check the game's live time.
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/settings.php';

    // Proceed with the time check only if the setting is defined.
    if (defined('GAME_LIVE_DATETIME')) {
        $game_live_time = strtotime(GAME_LIVE_DATETIME);
        $current_time = time();

        // If the game start time is in the future...
        if ($current_time < $game_live_time) {
            // ...show the locked page and stop the original script from running.
            include __DIR__ . '/locked.php';
            exit();
        }
    }
}