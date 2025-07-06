<?php
// Define site constants if not already defined
if (!defined('SITE_URL')) {
    // Determine the base URL dynamically
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    
    // A more robust way to find the base path, works from any subdirectory
    $base_path = rtrim(substr($script_path, 0, strpos($script_path, 'public/') ?: (strpos($script_path, 'admin/') ?: strlen($script_path))), '/');
    
    define('SITE_URL', $protocol . $host . $base_path);
}

// Global settings that are not theme-related
$global_settings_result = $mysqli->query("SELECT setting_key, setting_value FROM settings");
if ($global_settings_result) {
    while($row = $global_settings_result->fetch_assoc()) {
        if (!defined($row['setting_key'])) {
            define($row['setting_key'], $row['setting_value']);
        }
    }
}

// --- NEW: Theme Loading System ---
$THEME_SETTINGS = [];

// FIXED: Wrap the function declaration in a function_exists() check
if (!function_exists('load_active_theme')) {
    function load_active_theme($mysqli, $is_admin_area) {
        global $THEME_SETTINGS;
        
        // Check if the themes table exists to prevent errors on first install
        $table_check = $mysqli->query("SHOW TABLES LIKE 'themes'");
        if($table_check->num_rows == 0) {
            // Table doesn't exist, maybe do nothing or load fallback
            return;
        }
        
        $is_admin_flag = $is_admin_area ? 1 : 0;
        $stmt = $mysqli->prepare("SELECT settings_json FROM themes WHERE is_active = 1 AND is_admin_theme = ?");
        $stmt->bind_param("i", $is_admin_flag);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $theme = $result->fetch_assoc();
            $THEME_SETTINGS = json_decode($theme['settings_json'], true);
        } else {
            // Fallback: If no theme is active, try to load a default
            $default_stmt = $mysqli->prepare("SELECT settings_json FROM themes WHERE name LIKE 'Default%' AND is_admin_theme = ?");
            $default_stmt->bind_param("i", $is_admin_flag);
            $default_stmt->execute();
            $default_result = $default_stmt->get_result();
            if($default_result->num_rows > 0) {
                $default_theme = $default_result->fetch_assoc();
                $THEME_SETTINGS = json_decode($default_theme['settings_json'], true);
            }
        }
    }
}

// Determine if we are in the admin area to load the correct theme
$is_admin_area = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false);
load_active_theme($mysqli, $is_admin_area);