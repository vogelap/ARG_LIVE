<?php
/**
 * This file handles global application booting, primarily session configuration.
 * It MUST be included before any session logic is started.
 */

// Load the main configuration to get SITE_URL
require_once __DIR__ . '/../config.php';

// --- CRITICAL FIX: Set a global cookie path for the entire application ---
// This ensures the session persists across all subdirectories (e.g., /public/ and /admin/).
// We will set the path to '/' which makes the cookie available to the entire domain.
// If your application lives in a subdirectory (like /arg_game), this is the most robust solution.
$base_path = '/';
$url_parts = parse_url(SITE_URL);
if (isset($url_parts['path'])) {
    $base_path = $url_parts['path'] . '/';
}


// Set session cookie parameters BEFORE the session starts.
session_set_cookie_params([
    'lifetime' => 0, // 0 = until browser is closed
    'path' => $base_path,
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Now, start the session if it hasn't been started already.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}