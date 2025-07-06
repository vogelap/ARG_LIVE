<?php
// This file is included at the top of all public-facing pages.
// It requires the session to be started to check login status.
require_once __DIR__ . '/../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? (defined('SITE_NAME') ? SITE_NAME : 'ARG Game')); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            <?php if (!empty($THEME_SETTINGS)) { foreach ($THEME_SETTINGS as $key => $value) {
                echo $key . ': ' . htmlspecialchars($value) . ';';
            } } ?>
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="sidebar">
            <h2 class="site-title"><?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : 'ARG Game'); ?></h2>
            <nav class="nav-menu">
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo SITE_URL; ?>/public/index.php" class="nav-item"><i class="fas fa-th-large"></i> <?php echo get_text('sidebar_nav_puzzle_grid'); ?></a>
                    <a href="<?php echo SITE_URL; ?>/public/story.php" class="nav-item"><i class="fas fa-book-open"></i> Story Log</a>
                    <a href="<?php echo SITE_URL; ?>/public/profile.php" class="nav-item"><i class="fas fa-user-cog"></i> <?php echo get_text('sidebar_nav_my_profile'); ?></a>
                    
                    <?php if (is_admin()): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/index.php" class="nav-item" style="color: var(--primary-color); font-weight: bold;">
                        <i class="fas fa-user-shield"></i> <?php echo get_text('sidebar_nav_admin_panel'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>/public/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <?php echo get_text('sidebar_nav_logout'); ?></a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/public/login.php" class="nav-item"><i class="fas fa-sign-in-alt"></i> <?php echo get_text('sidebar_nav_login'); ?></a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="main-content">