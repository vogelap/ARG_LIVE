#!/bin/bash

# ==============================================================================
#
# ARG Framework Installation Script (Linux)
#
# This script automates the setup of the ARG Framework, including dependency
# checks, file setup, database configuration, and admin user creation.
#
# ==============================================================================

# --- Color Definitions ---
C_RESET='\033[0m'
C_RED='\033[0;31m'
C_GREEN='\033[0;32m'
C_YELLOW='\033[0;33m'
C_BLUE='\033[0;34m'
C_CYAN='\033[0;36m'

# --- Helper Functions ---
function print_step() {
    echo -e "\n${C_CYAN}>>> $1${C_RESET}"
}

function print_success() {
    echo -e "${C_GREEN}? $1${C_RESET}"
}

function print_error() {
    echo -e "${C_RED}? $1${C_RESET}"
}

function print_warning() {
    echo -e "${C_YELLOW}? $1${C_RESET}"
}

function check_command() {
    if ! command -v "$1" &> /dev/null; then
        print_error "Command not found: $1. Please install it and run this script again."
        exit 1
    fi
}

# --- Main Script ---

# 1. Welcome and Dependency Check
clear
print_step "Starting ARG Framework Installation"
echo "This script will guide you through the setup process."

print_step "Checking for required dependencies..."
check_command "php"
check_command "composer"
check_command "mysql"
print_success "All dependencies (PHP, Composer, MySQL client) are installed."

# 2. Project Setup
print_step "Setting up project files..."
if [ ! -f "composer.json" ]; then
    print_error "composer.json not found. Please run this script from the root of the arg_game project directory."
    exit 1
fi

echo "Installing PHP dependencies with Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader
if [ $? -ne 0 ]; then
    print_error "Composer install failed. Please check for errors above."
    exit 1
fi
print_success "Composer dependencies installed successfully."

# Create config template if it doesn't exist
if [ ! -f "config.php.template" ]; then
    print_warning "config.php.template not found. Creating one..."
    cat > config.php.template << 'EOF'
<?php
// Composer Autoloader for external libraries like PHPMailer
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';

// Database Configuration
define('DB_SERVER', '%%DB_HOST%%');
define('DB_USERNAME', '%%DB_USER%%');
define('DB_PASSWORD', '%%DB_PASS%%');
define('DB_NAME', '%%DB_NAME%%');

// Site Configuration - IMPORTANT: No trailing slash here
define('SITE_URL', '%%SITE_URL%%');
define('ROOT_PATH', __DIR__);

// Timezone
date_default_timezone_set('America/New_York');
EOF
    print_success "config.php.template created."
fi

# 3. Database Configuration
print_step "Configuring the database..."
if [ -f "config.php" ]; then
    print_warning "config.php already exists. Skipping creation."
    source config.php
    DB_HOST=${DB_SERVER}
    DB_USER=${DB_USERNAME}
    DB_PASS=${DB_PASSWORD}
    DB_NAME=${DB_NAME}
else
    DB_HOST_DEFAULT="localhost"
    DB_USER_DEFAULT="root"
    DB_NAME_DEFAULT="arg_game"

    echo "Please enter your MySQL database details."
    read -rp "Database Host [${DB_HOST_DEFAULT}]: " DB_HOST
    DB_HOST=${DB_HOST:-$DB_HOST_DEFAULT}

    read -rp "Database User [${DB_USER_DEFAULT}]: " DB_USER
    DB_USER=${DB_USER:-$DB_USER_DEFAULT}

    read -rsp "Database Password: " DB_PASS
    echo
    
    read -rp "Database Name [${DB_NAME_DEFAULT}]: " DB_NAME
    DB_NAME=${DB_NAME:-$DB_NAME_DEFAULT}
    
    # Prompt for Site URL
    SITE_URL_DEFAULT="http://localhost/arg_game"
    read -rp "Full Site URL [${SITE_URL_DEFAULT}]: " SITE_URL
    SITE_URL=${SITE_URL:-$SITE_URL_DEFAULT}

    # Create config.php from template
    sed -e "s/%%DB_HOST%%/$DB_HOST/" \
        -e "s/%%DB_USER%%/$DB_USER/" \
        -e "s/%%DB_PASS%%/$DB_PASS/" \
        -e "s/%%DB_NAME%%/$DB_NAME/" \
        -e "s/%%SITE_URL%%/$SITE_URL/" \
        config.php.template > config.php
    print_success "config.php created successfully."
fi

# Test database connection and import schema
print_step "Connecting to the database and importing schema..."
mysql -u"${DB_USER}" -p"${DB_PASS}" -h"${DB_HOST}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
if [ $? -ne 0 ]; then
    print_error "Could not create database. Please check your credentials."
    exit 1
fi
print_success "Database '$DB_NAME' created or already exists."

mysql -u"${DB_USER}" -p"${DB_PASS}" -h"${DB_HOST}" "${DB_NAME}" < complete_sql_schema.sql
if [ $? -ne 0 ]; then
    print_error "Failed to import database schema from complete_sql_schema.sql."
    exit 1
fi
print_success "Database schema imported successfully."

# 4. Create First Admin User
print_step "Creating the first administrator account..."
echo "Please enter the details for the first admin user."
while true; do
    read -rp "Admin Email: " ADMIN_EMAIL
    if [[ "$ADMIN_EMAIL" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$ ]]; then
        break
    else
        print_warning "Please enter a valid email address."
    fi
done

while true; do
    read -rsp "Admin Password (min 8 chars): " ADMIN_PASS
    echo
    if [ ${#ADMIN_PASS} -ge 8 ]; then
        read -rsp "Confirm Password: " ADMIN_PASS_CONFIRM
        echo
        if [ "$ADMIN_PASS" == "$ADMIN_PASS_CONFIRM" ]; then
            break
        else
            print_warning "Passwords do not match. Please try again."
        fi
    else
        print_warning "Password must be at least 8 characters long."
    fi
done

# Insert the admin user into the database via PHP for secure password hashing
php create_admin.php "$DB_HOST" "$DB_USER" "$DB_PASS" "$DB_NAME" "$ADMIN_EMAIL" "$ADMIN_PASS"
if [ $? -ne 0 ]; then
    print_error "Failed to create the admin user. Please check the error from the PHP script."
    exit 1
fi
print_success "Admin user created successfully!"

# 5. Final Instructions
print_step "Installation Complete!"
echo -e "${C_GREEN}The ARG Framework has been successfully installed!${C_RESET}"
echo -e "You can now access your game at the following URLs:"
echo -e "  ${C_BLUE}Player Site:${C_RESET} ${SITE_URL}/public/"
echo -e "  ${C_BLUE}Admin Panel:${C_RESET} ${SITE_URL}/admin/"
echo
echo -e "Login to the admin panel with the credentials you just created:"
echo -e "  ${C_YELLOW}Email:${C_RESET} $ADMIN_EMAIL"
echo -e "  ${C_YELLOW}Password:${C_RESET} [the password you entered]"
echo
echo -e "${C_YELLOW}IMPORTANT: For security, ensure your web server is configured to serve from the 'public' directory and that the project root is not publicly accessible in a production environment.${C_RESET}"
