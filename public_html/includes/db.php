<?php
// File: arg_game/includes/db.php

require_once __DIR__ . '/../config.php';

$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($mysqli->connect_error) {
    // Using a more user-friendly error page for database connection issues
    header('HTTP/1.1 503 Service Unavailable');
    echo "Error: Unable to connect to the database. Please try again later.";
    // In a real production environment, you would log the detailed error for the admin
    // error_log("Database Connection Failed: " . $mysqli->connect_error);
    exit;
}