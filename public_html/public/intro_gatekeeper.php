<?php
// File: arg_game/public/intro_gatekeeper.php

// This gatekeeper checks if the intro video should be shown.
// It must be placed after the session has been started.

// Load necessary files if they haven't been already
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/settings.php';
}

// Ensure the User class is available for the static call
if (!class_exists('User')) {
    require_once __DIR__ . '/../includes/classes/User.php';
}

// Check if the intro feature is enabled AND the user has NOT seen it this session
if ((defined('INTRO_ENABLED') && INTRO_ENABLED) && !User::hasSeenIntro()) {
    // Redirect to the intro page, which will then mark the intro as seen
    header("Location: " . SITE_URL . "/public/intro.php");
    exit();
}