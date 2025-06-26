<?php
// File: arg_game/admin/delete_user.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';

// Ensure a user ID is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$id_to_delete = (int)$_GET['id'];
$current_user_id = $_SESSION['user_id'];

// Prevent an admin from deleting their own account
if ($id_to_delete === $current_user_id) {
    header("Location: manage_users.php?error=" . urlencode("You cannot delete your own account."));
    exit;
}

// Instantiate the User manager and call the now-robust delete method
$user_manager = new User($mysqli);
if ($user_manager->delete($id_to_delete)) {
    header("Location: manage_users.php?success=" . urlencode("User and all related data deleted successfully."));
    exit;
} else {
    header("Location: manage_users.php?error=" . urlencode("Failed to delete user. The user may not exist or a database error occurred."));
    exit;
}