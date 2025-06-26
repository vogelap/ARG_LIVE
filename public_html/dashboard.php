<?php
// Start the session to check for login status
session_start();

// If the user is not logged in, redirect them to the login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// If you want to restrict this page to admins only
// if ($_SESSION['role'] !== 'admin') {
//     die("Access Denied: You do not have permission to view this page.");
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body { font-family: sans-serif; padding: 40px; }
        .welcome { font-size: 24px; }
        .role { font-style: italic; color: #555; }
    </style>
</head>
<body>
    <div class="welcome">
        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
    </div>
    <p class="role">Your role is: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
    <br>
    <p>You have successfully logged in.</p>
    <br>
    <a href="logout.php">Logout</a>
</body>
</html>