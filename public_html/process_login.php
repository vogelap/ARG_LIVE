<?php
// Start the session to store login state
session_start();

// Include configuration and bootstrapper files.
// This path assumes config.php is one level above the current directory.
require_once __DIR__ . '/../config.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // If not a POST request, redirect to login page
    header("Location: login.php");
    exit();
}

// Get form data
$username = $_POST['username'];
$password = $_POST['password'];

// Validate input
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Username and password are required.";
    header("Location: login.php");
    exit();
}

try {
    // Find the user by username or email
    // Using prepared statements to prevent SQL injection
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :username OR email = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify user exists and password is correct
    // This uses modern password_verify() function. Your passwords MUST be stored using password_hash().
    if ($user && password_verify($password, $user['password'])) {
        // Password is correct, so create session variables
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect to the dashboard or a role-specific page
        header("Location: dashboard.php");
        exit();
    } else {
        // Invalid credentials
        $_SESSION['login_error'] = "Invalid username or password.";
        header("Location: login.php");
        exit();
    }

} catch (PDOException $e) {
    // Database error
    // For production, you would log this error instead of displaying it.
    // error_log("Login failed: " . $e->getMessage());
    $_SESSION['login_error'] = "A server error occurred. Please try again later.";
    header("Location: login.php");
    exit();
}
?>