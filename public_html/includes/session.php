<?php
// This single file now handles all session setup and helper functions.
require_once __DIR__ . '/boot.php';

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

// Load site text after session has started and db connection is available
require_once __DIR__ . '/db.php';
load_site_text($mysqli);
?>