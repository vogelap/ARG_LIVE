<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? (defined('ADMIN_DASHBOARD_NAME') ? ADMIN_DASHBOARD_NAME : 'Admin Dashboard')); ?></title>
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <?php if (!empty($THEME_SETTINGS)): ?>
    <style>:root { <?php foreach ($THEME_SETTINGS as $key => $value) echo "$key: " . htmlspecialchars($value) . ";"; ?> }</style>
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <h3><?php echo htmlspecialchars(defined('ADMIN_DASHBOARD_NAME') ? ADMIN_DASHBOARD_NAME : 'Admin Panel'); ?></h3>
            <ul>
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_puzzles.php"><i class="fas fa-puzzle-piece"></i> Puzzles</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="manage_media.php"><i class="fas fa-photo-video"></i> Media Library</a></li>
                <li>
                    <a href="#" class="submenu-toggle"><i class="fas fa-cogs"></i> Settings</a>
                    <ul class="submenu">
                        <li><a href="site_config.php">Site Config</a></li>
                        <li><a href="game_state_config.php">Game State</a></li>
                        <li><a href="intro_config.php">Intro Page</a></li>
                        <li><a href="congrats_config.php">Congrats Page</a></li>
                        <li><a href="gmail_config.php">Email (SMTP)</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#" class="submenu-toggle"><i class="fas fa-user-shield"></i> Administration</a>
                    <ul class="submenu">
                        <li><a href="manage_themes.php">Theme Manager</a></li>
                        <li><a href="manage_text.php">Site Text</a></li>
                        <li><a href="visualizer.php">Game Visualizer</a></li>
                    </ul>
                </li>
                <li><a href="profile.php"><i class="fas fa-user-edit"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
            <header>
                <h2><?php echo htmlspecialchars($page_title ?? ''); ?></h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                </div>
            </header>
            <main>