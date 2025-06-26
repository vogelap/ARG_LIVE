<?php
/**
 * This file now only contains session-related helper functions.
 * All application setup is handled by init.php.
 */

// Load the master initializer.
require_once __DIR__ . '/init.php';

// --- Session Helper Functions ---

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: " . SITE_URL . "/public/login.php");
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        header("Location: " . SITE_URL . "/public/login.php?error=access_denied");
        exit;
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}