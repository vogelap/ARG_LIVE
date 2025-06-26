<?php
/**
 * This file handles redirecting all traffic from the project root
 * to the /public/ directory, which is the main entry point for users.
 */

// Load the main configuration file to get the SITE_URL constant
require_once __DIR__ . '/config.php';

// Perform a permanent redirect (HTTP 301) to the public directory
header("Location: " . SITE_URL . "/public/", true, 301);

// Ensure no other code is executed after the redirect
exit();