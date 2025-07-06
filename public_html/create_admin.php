<?php
// File: create_admin.php
// THIS SCRIPT IS CALLED BY INSTALL.BAT TO SECURELY CREATE THE FIRST ADMIN USER.

// Suppress warnings for a cleaner command-line experience
error_reporting(E_ERROR | E_PARSE);

// --- Argument Validation ---
if ($argc !== 7) {
    echo "[PHP SCRIPT ERROR] Invalid number of arguments.\n";
    exit(1);
}

$db_host = $argv[1];
$db_user = $argv[2];
$db_pass = $argv[3];
$db_name = $argv[4];
$admin_email = $argv[5];
$admin_pass = $argv[6];

if (empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    echo "[PHP SCRIPT ERROR] Invalid admin email address provided.\n";
    exit(1);
}
if (empty($admin_pass)) {
    echo "[PHP SCRIPT ERROR] Admin password cannot be empty.\n";
    exit(1);
}

// --- Database Connection ---
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    echo "[PHP SCRIPT ERROR] Database Connection Failed: " . $mysqli->connect_error . "\n";
    exit(1);
}

// --- User Creation Logic ---

// Check if the user or email already exists
$stmt_check = $mysqli->prepare("SELECT id FROM users WHERE email = ? OR username = 'admin'");
if ($stmt_check === false) {
    echo "[PHP SCRIPT ERROR] Failed to prepare statement: " . $mysqli->error . "\n";
    exit(1);
}
$stmt_check->bind_param("s", $admin_email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    echo "[PHP SCRIPT ERROR] An admin user with the username 'admin' or email '$admin_email' already exists.\n";
    $stmt_check->close();
    $mysqli->close();
    exit(1);
}
$stmt_check->close();

// Hash the password
$hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
if ($hashed_password === false) {
    echo "[PHP SCRIPT ERROR] Failed to hash password.\n";
    exit(1);
}

// Insert the new admin user
$stmt = $mysqli->prepare("INSERT INTO users (username, email, password, is_admin) VALUES ('admin', ?, ?, 1)");
if ($stmt === false) {
    echo "[PHP SCRIPT ERROR] Failed to prepare insert statement: " . $mysqli->error . "\n";
    exit(1);
}
$stmt->bind_param("ss", $admin_email, $hashed_password);

if (!$stmt->execute()) {
    echo "[PHP SCRIPT ERROR] Failed to execute query to create admin user: " . $stmt->error . "\n";
    $stmt->close();
    $mysqli->close();
    exit(1);
}

$stmt->close();
$mysqli->close();

// Exit with success code
exit(0);

?>
