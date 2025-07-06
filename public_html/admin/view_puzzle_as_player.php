<?php
// File: arg_game/admin/view_puzzle_as_player.php

require_once __DIR__ . '/../includes/session.php';
require_admin(); // Ensure only an admin can run this
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';

// Ensure a puzzle ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_puzzles.php?error=puzzle_id_missing");
    exit;
}

$puzzle_id = (int)$_GET['id'];
$admin_user_id = $_SESSION['user_id'];

// Set the final destination URL in the session.
// The login script will use this after authenticating.
$_SESSION['player_view_redirect'] = SITE_URL . '/public/puzzle.php?id=' . $puzzle_id;

// Now, generate the single-use login token for the admin
$user_manager = new User($mysqli);
$token = $user_manager->generateLoginToken($admin_user_id);

if ($token) {
    // Redirect to the public login page with the token.
    // The login page will handle authentication and the final redirect.
    $login_url = SITE_URL . '/public/login.php?login_token=' . $token;
    header("Location: " . $login_url);
    exit;
} else {
    // If token generation fails, redirect back with an error.
    unset($_SESSION['player_view_redirect']); // Clear the redirect session variable
    header("Location: manage_puzzles.php?error=token_generation_failed");
    exit;
}