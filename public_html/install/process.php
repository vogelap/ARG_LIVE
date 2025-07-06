<?php
// File: install/process.php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function redirect_with_error($message, $step) {
    $_SESSION['error'] = $message;
    header("Location: index.php?step=$step");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$step = $_POST['step'] ?? '1';

if ($step == '2') {
    // --- Step 2: Database Connection & Schema Import ---
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];

    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        $sql_schema_path = __DIR__ . '/../complete_sql_schema.sql';
        if (!file_exists($sql_schema_path)) {
            redirect_with_error('ERROR: `complete_sql_schema.sql` not found in the project root.', '2');
        }
        $sql = file_get_contents($sql_schema_path);
        $pdo->exec($sql);

    } catch (PDOException $e) {
        redirect_with_error("Database Error: " . $e->getMessage(), '2');
    }

    $_SESSION['db_config'] = [
        'host' => $db_host,
        'name' => $db_name,
        'user' => $db_user,
        'pass' => $db_pass
    ];

    header('Location: index.php?step=3');
    exit;

} elseif ($step == '3') {
    // --- Step 3: Create Config, Admin User, and Finalize ---

    if (!isset($_SESSION['db_config'])) {
        redirect_with_error('Database configuration not found. Please start over.', '2');
    }

    $db_config = $_SESSION['db_config'];
    $site_url = rtrim($_POST['site_url'], '/');
    $admin_email = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];

    $config_template_path = __DIR__ . '/../config.php.template';
    if (!file_exists($config_template_path)) {
        redirect_with_error('ERROR: `config.php.template` not found in the project root.', '3');
    }

    $config_template = file_get_contents($config_template_path);
    $config_content = str_replace(
        ['%%DB_HOST%%', '%%DB_NAME%%', '%%DB_USER%%', '%%DB_PASS%%', '%%SITE_URL%%'],
        [$db_config['host'], $db_config['name'], $db_config['user'], $db_config['pass'], $site_url],
        $config_template
    );

    if (file_put_contents(__DIR__ . '/../config.php', $config_content) === false) {
        redirect_with_error('Could not write to config.php. Please check file permissions.', '3');
    }

    try {
        $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['name']}", $db_config['user'], $db_config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->execute([$admin_email]);
        if ($stmt_check->fetch()) {
             redirect_with_error("An admin user with this email already exists. Please start over or use a different email.", '3');
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES ('admin', ?, ?, 1)");
        $stmt->execute([$admin_email, $hashed_password]);

    } catch (PDOException $e) {
        redirect_with_error("Failed to create admin user: " . $e->getMessage(), '3');
    }

    $_SESSION['install_complete'] = true;
    header('Location: index.php?step=complete');
    exit;
}