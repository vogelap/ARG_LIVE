<?php
// --- FINAL Server & Database Diagnostic Script ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "============================================\n";
echo "==      ARG Framework Diagnostics v3      ==\n";
echo "==     (Includes Database Connection Test)    ==\n";
echo "============================================\n\n";

// ---[ Path Information ]---
$project_root = __DIR__;
echo "Project Root Path: " . $project_root . "\n\n";

// ---[ File Existence & Load Test ]---
echo "---[ Critical File Load Test ]---\n";
$config_path = $project_root . '/config.php';
if (!file_exists($config_path)) {
    echo "CRITICAL ERROR: config.php NOT FOUND at " . $config_path . "\n";
    exit;
}

try {
    require_once($config_path);
    echo "SUCCESS: config.php loaded successfully.\n";
    if (defined('SITE_URL')) {
        echo "   -> SITE_URL is defined as: " . SITE_URL . "\n";
    } else {
        echo "   -> WARNING: SITE_URL constant was NOT defined in config.php.\n";
    }
} catch (Throwable $e) {
    echo "CRITICAL ERROR: A fatal error occurred while loading config.php.\n";
    echo "   -> Error: " . $e->getMessage() . "\n";
    exit;
}
echo "\n";


// ---[ Database Connection Test ]---
echo "---[ Database Connection Test ]---\n";
echo "Attempting to connect to the database with credentials from config.php...\n";

// Check if all DB constants are defined
if (!defined('DB_SERVER') || !defined('DB_USERNAME') || !defined('DB_PASSWORD') || !defined('DB_NAME')) {
    echo "CRITICAL ERROR: Not all database constants (DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME) are defined in config.php.\n";
    exit;
}

echo "   -> Host: " . DB_SERVER . "\n";
echo "   -> Database: " . DB_NAME . "\n";
echo "   -> User: " . DB_USERNAME . "\n";

// Attempt the connection
try {
    $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check for connection errors
    if ($mysqli->connect_error) {
        echo "\nCONNECTION FAILED!\n";
        echo "   -> MySQLi Error Code: " . $mysqli->connect_errno . "\n";
        echo "   -> MySQLi Error Message: " . $mysqli->connect_error . "\n";
        echo "\n   -> This error means your PHP script could not connect to the database server. Please double-check your DB_SERVER, DB_USERNAME, and DB_PASSWORD constants in config.php.\n";
    } else {
        echo "\nSUCCESS: Database connection was successful!\n";
        echo "   -> The credentials in config.php are correct and the database is accessible.\n";
        $mysqli->close();
    }

} catch (Throwable $e) {
    echo "\nCRITICAL ERROR: A fatal error occurred during the database connection attempt.\n";
    echo "   -> Error: " . $e->getMessage() . "\n";
}


echo "\n---[ End of Report ]---";

?>