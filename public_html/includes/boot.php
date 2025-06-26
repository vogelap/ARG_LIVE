<?php
/**
 * This file handles global application booting, primarily session configuration.
 * It MUST be included before any session logic is started.
 */

// Load the main configuration to get SITE_URL if needed.
require_once __DIR__ . '/../config.php';

// --- CRITICAL FIX: Set a global cookie path for the entire application ---
// This ensures the session persists across all subdirectories (e.g., /public/ and /admin/).
// The most reliable path is simply '/', making the cookie available to the entire domain.
$cookie_path = '/';

// Set session cookie parameters BEFORE the session starts.
session_set_cookie_params([
    'lifetime' => 0, // 0 = until browser is closed
    'path' => $cookie_path,
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Now, start the session if it hasn't been started already.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}