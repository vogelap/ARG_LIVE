<?php
// File: install/index.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$step = $_GET['step'] ?? '1';
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

function is_writable_in_path($file, $path) {
    $full_path = $path . '/' . $file;
    if (file_exists($full_path)) {
        return is_writable($full_path);
    }
    return is_writable(dirname($full_path));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ARG Framework Installer</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
    <div class="installer-container">
        <h1>ARG Framework Installer</h1>

        <?php if ($error): ?>
            <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($step == '1'): ?>
            <h2>Step 1: Prerequisites Check</h2>
            <p>This wizard will guide you through the installation. First, let's check your server environment.</p>
            <ul class="prereq-list">
                <?php
                $php_version_ok = version_compare(PHP_VERSION, '7.4', '>=');
                $pdo_ok = extension_loaded('pdo_mysql');
                $mbstring_ok = extension_loaded('mbstring');
                $composer_ok = file_exists(__DIR__ . '/../vendor/autoload.php');
                $config_writable = is_writable_in_path('config.php', __DIR__ . '/..');

                $all_ok = $php_version_ok && $pdo_ok && $mbstring_ok && $composer_ok && $config_writable;
                ?>
                <li class="<?php echo $php_version_ok ? 'ok' : 'err'; ?>">PHP Version >= 7.4 (You have <?php echo PHP_VERSION; ?>)</li>
                <li class="<?php echo $pdo_ok ? 'ok' : 'err'; ?>">PDO MySQL Extension Loaded</li>
                <li class="<?php echo $mbstring_ok ? 'ok' : 'err'; ?>">Multibyte String Extension Loaded</li>
                <li class="<?php echo $composer_ok ? 'ok' : 'err'; ?>">Composer Dependencies Installed (vendor/autoload.php exists)</li>
                <li class="<?php echo $config_writable ? 'ok' : 'err'; ?>">Root Directory is Writable (for config.php)</li>
            </ul>
            <?php if ($all_ok): ?>
                <a href="?step=2" class="btn">Next Step</a>
            <?php else: ?>
                <p class="error-text">Please resolve the issues above before proceeding.</p>
                <?php if (!$composer_ok) { echo "<p class='error-text'><strong>Hint:</strong> It looks like you need to run `composer install` in your project's root directory.</p>"; } ?>
            <?php endif; ?>
        <?php elseif ($step == '2'): ?>
            <h2>Step 2: Database Configuration</h2>
            <p>Please provide your database connection details.</p>
            <form action="process.php" method="post">
                <input type="hidden" name="step" value="2">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" name="db_host" id="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" name="db_name" id="db_name" value="arg_game" required>
                </div>
                <div class="form-group">
                    <label for="db_user">Database User</label>
                    <input type="text" name="db_user" id="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" name="db_pass" id="db_pass">
                </div>
                <button type="submit" class="btn">Test Connection & Create Tables</button>
            </form>
        <?php elseif ($step == '3'): ?>
            <h2>Step 3: Site & Admin Setup</h2>
            <p>Configure your site URL and create the primary administrator account.</p>
            <form action="process.php" method="post">
                <input type="hidden" name="step" value="3">
                <div class="form-group">
                    <label for="site_url">Site URL</label>
                    <input type="url" name="site_url" id="site_url" value="<?php echo htmlspecialchars(rtrim('http://' . $_SERVER['HTTP_HOST'] . str_replace('/install/index.php', '', $_SERVER['PHP_SELF']), '/')); ?>" required>
                    <small>The full URL to your project root, without a trailing slash. Avoid using "localhost" for production.</small>
                </div>
                <hr>
                <div class="form-group">
                    <label for="admin_email">Admin Email</label>
                    <input type="email" name="admin_email" id="admin_email" required>
                </div>
                <div class="form-group">
                    <label for="admin_password">Admin Password</label>
                    <input type="password" name="admin_password" id="admin_password" required>
                </div>
                <button type="submit" class="btn">Create Admin & Finish Installation</button>
            </form>
        <?php elseif ($step == 'complete'): ?>
            <h2>Installation Complete!</h2>
            <div class="success-box">
                <p>Congratulations! The ARG Framework has been successfully installed.</p>
                <p><strong>For site security, please DELETE the entire `install` directory and INSTALL.bat & INSTALL.sh from the directory now.</strong></p>
            </div>
            <h3>Next Steps:</h3>
            <a href="../public/" class="btn">Go to Public Site</a>
            <a href="../admin/" class="btn">Go to Admin Panel</a>
        <?php endif; ?>
    </div>
</body>
</html>