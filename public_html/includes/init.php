<?php
/**
 * This is the new, unified initialization file for the entire application.
 * It handles loading all core components in the correct order.
 */

// Define the project root directory if it's not already defined.
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

// 1. Load the main configuration file (DB credentials, SITE_URL, etc.)
require_once PROJECT_ROOT . '/config.php';

// 2. Load core helper functions.
require_once PROJECT_ROOT . '/includes/helpers.php';

// 3. Establish the database connection.
require_once PROJECT_ROOT . '/includes/db.php';

// 4. Configure session handling and start the session.
require_once PROJECT_ROOT . '/includes/boot.php';

// 5. Load site-wide text data from the database.
// This is safe to run now that all other components are loaded.
if (isset($mysqli) && $mysqli->connect_errno === 0) {
    load_site_text($mysqli);
}