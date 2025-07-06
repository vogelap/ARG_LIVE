@echo off
cls
echo =================================================================
echo  ARG Framework Installer for Windows + XAMPP
echo =================================================================
echo.
echo This script will guide you through the setup process.
echo It assumes you have XAMPP installed at C:\xampp
echo and Composer installed globally.
echo.

setlocal

REM --- Dependency Check ---
echo Checking for dependencies...
if not exist "C:\xampp\php\php.exe" (
    echo [ERROR] PHP not found at C:\xampp\php\php.exe. Is XAMPP installed?
    goto :end
)
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Composer not found. Please install it from getcomposer.org and ensure it's in your system's PATH.
    goto :end
)
if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo [ERROR] MySQL client not found at C:\xampp\mysql\bin\mysql.exe.
    goto :end
)
echo [SUCCESS] Dependencies found.
echo.

REM --- Run Composer ---
echo Installing PHP dependencies with Composer...
composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo [ERROR] Composer install failed. Please check for errors above.
    goto :end
)
echo [SUCCESS] Composer dependencies installed.
echo.


REM --- Database Configuration ---
echo --- Database Configuration ---
set /p DB_HOST="Enter Database Host [localhost]: " || set DB_HOST=localhost
set /p DB_USER="Enter Database User [root]: " || set DB_USER=root
set /p DB_PASS="Enter Database Password (leave blank for default XAMPP): "
set /p DB_NAME="Enter Database Name [arg_game]: " || set DB_NAME=arg_game
set /p SITE_URL="Enter Full Site URL [http://localhost/arg_game]: " || set SITE_URL=http://localhost/arg_game
echo.

echo Creating config.php...
(
    echo ^<?php
    echo // Composer Autoloader for external libraries like PHPMailer
    echo require_once __DIR__ . '/vendor/autoload.php';
    echo require_once __DIR__ . '/includes/helpers.php';
    echo.
    echo // Database Configuration
    echo define('DB_SERVER', '%DB_HOST%');
    echo define('DB_USERNAME', '%DB_USER%');
    echo define('DB_PASSWORD', '%DB_PASS%');
    echo define('DB_NAME', '%DB_NAME%');
    echo.
    echo // Site Configuration - IMPORTANT: No trailing slash here
    echo define('SITE_URL', '%SITE_URL%');
    echo define('ROOT_PATH', __DIR__);
    echo.
    echo // Timezone
    echo date_default_timezone_set('America/New_York');
    echo ?^>
) > config.php
echo [SUCCESS] config.php created.
echo.

REM --- Database Creation & Schema Import ---
echo Importing database schema...
echo CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; | "C:\xampp\mysql\bin\mysql.exe" -u"%DB_USER%" -p"%DB_PASS%" -h"%DB_HOST%"
if %errorlevel% neq 0 (
    echo [ERROR] Could not create database. Check your credentials.
    goto :end
)
"C:\xampp\mysql\bin\mysql.exe" -u"%DB_USER%" -p"%DB_PASS%" -h"%DB_HOST%" "%DB_NAME%" < complete_sql_schema.sql
if %errorlevel% neq 0 (
    echo [ERROR] Failed to import database schema.
    goto :end
)
echo [SUCCESS] Database schema imported.
echo.

REM --- Admin User Creation ---
echo --- Administrator Account Setup ---
:getAdminEmail
set /p ADMIN_EMAIL="Enter Admin Email: "
if "%ADMIN_EMAIL%"=="" goto getAdminEmail

:getAdminPass
set /p ADMIN_PASS="Enter Admin Password (will be visible): "
if "%ADMIN_PASS%"=="" goto getAdminPass
echo.

echo Creating admin user...
"C:\xampp\php\php.exe" create_admin.php "%DB_HOST%" "%DB_USER%" "%DB_PASS%" "%DB_NAME%" "%ADMIN_EMAIL%" "%ADMIN_PASS%"
if %errorlevel% neq 0 (
    echo [ERROR] Failed to create admin user. The email may already be in use.
    goto :end
)
echo [SUCCESS] Admin user created!
echo.

REM --- Final Instructions ---
echo =================================================================
echo  Installation Complete!
echo =================================================================
echo.
echo You can now access your game at:
echo   Player Site: %SITE_URL%/public/
echo   Admin Panel: %SITE_URL%/admin/
echo.
echo Login to the admin panel with:
echo   Email:    %ADMIN_EMAIL%
echo   Password: [the password you entered]
echo.
echo IMPORTANT: For production, configure your web server to
echo serve from the 'public' directory for security.
echo.

:end
pause
