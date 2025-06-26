<?php

/**
 * --- DEBUGGING SCRIPT ---
 * This script will help identify the exact point of failure on your server.
 * Please copy the output of this page and send it back to me.
 */

// Force error reporting to be visible
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Script starting...<br>";

// Test each required file to find the failure point
try {
    echo "DEBUG: Loading session and core config...<br>";
    // This file includes config.php, db.php, and helpers.php
    require_once __DIR__ . '/../includes/session.php';
    echo "DEBUG: Core files loaded successfully.<br>";

    echo "DEBUG: Loading User class...<br>";
    require_once __DIR__ . '/../includes/classes/User.php';
    echo "DEBUG: User class loaded successfully.<br>";

} catch (Throwable $e) {
    // If any of the above 'require_once' calls fail, this will catch it.
    echo "<h1>A Fatal Error Occurred During File Inclusion</h1>";
    echo "<p>The application could not load a critical file, which is causing the white screen.</p>";
    echo "<p><strong>Error Message:</strong> <pre>" . htmlspecialchars($e->getMessage()) . "</pre></p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
    exit;
}

echo "DEBUG: All files included. Checking database connection object...<br>";

// Check if the database connection object exists and is valid
if (!isset($mysqli)) {
    echo "<h1>Database Error</h1>";
    echo "<p>The database connection object (\$mysqli) was not created. This usually means there is an issue in your `includes/db.php` or `config.php` file.</p>";
    exit;
}

if ($mysqli->connect_error) {
    echo "<h1>Database Connection Failed</h1>";
    echo "<p>PHP was able to load all files, but could not connect to the database. Please double-check your credentials in `config.php`.</p>";
    echo "<p><strong>MySQL Error:</strong> " . htmlspecialchars($mysqli->connect_error) . "</p>";
    exit;
}

echo "<h1>All Checks Passed</h1>";
echo "<p>The script was able to load all files and connect to the database successfully. If you are seeing this page, the problem lies within the user authentication logic itself, not the server setup.</p>";

// The rest of the login script is intentionally disabled for this test.
exit;

?>