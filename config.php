<?php
// Composer Autoloader for external libraries like PHPMailer
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';
/**
 * =================================================================
 * DATABASE CONFIGURATION
 * =================================================================
 */

// -----------------------------------------------------------------
// STEP 1: Enter your database credentials below.
// -----------------------------------------------------------------

$db_host = 'localhost';                  // This is correct.
$db_name = 'dbma3ztscdzkgc';         // REPLACE THIS with your real database name.
$db_user = 'u8rakddhc8bp0';     // REPLACE THIS with your real database username.
$db_pass = 'pdrx263xi2rd';     // REPLACE THIS with the new password you just set.
$db_charset = 'utf8mb4';

// Database Configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u8rakddhc8bp0');
define('DB_PASSWORD', 'pdrx263xi2rd');
define('DB_NAME', 'dbma3ztscdzkgc');

// Site Configuration - IMPORTANT: No trailing slash here
define('SITE_URL', 'https://arg.agalacticevent.com');
define('ROOT_PATH', __DIR__);

// Timezone
date_default_timezone_set('America/New_York');

/**
 * =================================================================
 * PDO CONNECTION LOGIC (No edits needed below this line)
 * =================================================================
 */

// Data Source Name (DSN) - specifies how to connect.
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

// Connection options for security, error handling, and data format.
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    error_log('Database Connection Failed: ' . $e->getMessage());
    die("HTTP 500 - Internal Server Error: Could not connect to the database.");
}
?>