<?php
// --- Server & Path Diagnostic Script ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "==========================================\n";
echo "==      ARG Framework Diagnostics v2    ==\n";
echo "==========================================\n\n";

// --- 1. PHP & Server Information ---
echo "---[ Server Information ]---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "\n";

// --- 2. Current Script Path ---
echo "---[ Path Information ]---\n";
$script_path = __FILE__;
echo "This diagnostic script's absolute path (__FILE__):\n" . $script_path . "\n\n";

$project_root_guess = dirname($script_path);
echo "Guessed Project Root (the directory this script is in):\n" . $project_root_guess . "\n\n";

// --- 3. Critical File Checks ---
echo "---[ Critical File Existence Check ]---\n";
$files_to_check = [
    'config.php',
    'includes/boot.php',
    'includes/session.php',
    'public/login.php'
];

foreach ($files_to_check as $file) {
    $full_path = $project_root_guess . '/' . $file;
    $status = file_exists($full_path) ? "FOUND" : "NOT FOUND";
    echo "Checking for: " . $full_path . "\n";
    echo "      Status: " . $status . "\n\n";
}

// --- 4. Directory Permissions ---
echo "---[ Directory Permissions Check ]---\n";
$dirs_to_check = [
    '', // The root directory itself (public_html)
    'includes',
    'public',
    'vendor'
];

foreach ($dirs_to_check as $dir) {
    $full_path = $project_root_guess . '/' . $dir;
    echo "Checking directory: " . $full_path . "\n";
    if (is_dir($full_path)) {
        echo "      Readable:  " . (is_readable($full_path) ? "Yes" : "NO") . "\n";
        echo "      Writable:  " . (is_writable($full_path) ? "Yes" : "NO") . "\n\n";
    } else {
        echo "      Status: NOT A DIRECTORY\n\n";
    }
}

// --- 5. Attempt to load config.php directly ---
echo "---[ Direct Config Load Test ]---\n";
$config_path = $project_root_guess . '/config.php';
if (file_exists($config_path)) {
    echo "Attempting to include: " . $config_path . "\n";
    try {
        // Use a variable to prevent it from affecting the rest of the script
        $test_include = include($config_path);
        if ($test_include) {
            echo "      Result: SUCCESS\n";
            // Check if a constant was defined
            if (defined('SITE_URL')) {
                echo "      SITE_URL constant is defined as: " . SITE_URL . "\n";
            } else {
                 echo "      WARNING: config.php was included, but SITE_URL constant was NOT defined.\n";
            }
        } else {
            echo "      Result: FAILED (include returned false)\n";
        }
    } catch (Throwable $e) {
        echo "      Result: FATAL ERROR\n";
        echo "      Error Message: " . $e->getMessage() . "\n";
    }
} else {
    echo "      Result: SKIPPED (config.php not found at the guessed path)\n";
}

echo "\n---[ End of Report ]---";

?>