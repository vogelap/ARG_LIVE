<?php
// File: arg_game/admin/player_view_login.php

require_once __DIR__ . '/../includes/session.php';
require_admin(); // Make sure only an admin can run this
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';

// Get the current admin's user ID from their session
$admin_user_id = $_SESSION['user_id'];

// Instantiate the User class
$user_manager = new User($mysqli);

// Generate the single-use login token for the admin's user ID
$token = $user_manager->generateLoginToken($admin_user_id);

if ($token) {
    // If token generation is successful, redirect to the public login page 
    // and pass the token as a GET parameter.
    $login_url = SITE_URL . '/public/login.php?login_token=' . $token;
    header("Location: " . $login_url);
    exit;
} else {
    // If token generation fails, redirect back to the admin dashboard with an error.
    header("Location: index.php?error=token_generation_failed");
    exit;
}