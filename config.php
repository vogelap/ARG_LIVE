<?php
// File: config.php (Modified)

// Composer Autoloader for external libraries like PHPMailer
require_once __DIR__ . '/vendor/autoload.php';

// --- Configuration Loading ---
$config_path = __DIR__ . '/config.ini';
if (!file_exists($config_path)) {
    die("CRITICAL ERROR: Configuration file 'config.ini' not found. Please create it from the template.");
}
$config = parse_ini_file($config_path, true);

if ($config === false) {
    die("CRITICAL ERROR: Could not parse config.ini. Please check its format.");
}

// --- Database Constants ---
define('DB_SERVER', $config['database']['DB_HOST']);
define('DB_USERNAME', $config['database']['DB_USER']);
define('DB_PASSWORD', $config['database']['DB_PASS']);
define('DB_NAME', $config['database']['DB_NAME']);

// --- Site Constants ---
// Remove trailing slashes to prevent issues with URL construction
define('SITE_URL', rtrim($config['site']['SITE_URL'], '/'));
define('ROOT_PATH', __DIR__);

// --- Timezone ---
date_default_timezone_set($config['site']['TIMEZONE'] ?? 'UTC');

// This file no longer creates the PDO object.
// The connection is now handled in includes/db.php, which is loaded by init.php.
?>