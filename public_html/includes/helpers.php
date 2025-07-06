<?php
// File: arg_game/includes/helpers.php

// This function will hold all our site text, loaded once.
global $site_text;
$site_text = [];

/**
 * Loads all text from the `site_text` table into a global variable.
 * This is efficient as it only queries the database once per page load.
 * @param mysqli $mysqli The database connection object.
 */
function load_site_text($mysqli) {
    global $site_text;
    if (empty($site_text)) {
        // Check if the table exists to prevent errors during initial setup
        $table_check = $mysqli->query("SHOW TABLES LIKE 'site_text'");
        if($table_check && $table_check->num_rows > 0) {
            $result = $mysqli->query("SELECT text_key, text_value FROM site_text");
            if ($result) {
                while($row = $result->fetch_assoc()) {
                    $site_text[$row['text_key']] = $row['text_value'];
                }
            }
        }
    }
}

/**
 * Retrieves a text value by its key.
 *
 * @param string $key The key for the desired text.
 * @param array $replacements An associative array of placeholders to replace in the string.
 * e.g., ['{count}' => 5]
 * @return string The text value, or the key itself if not found.
 */
function get_text($key, $replacements = []) {
    global $site_text;
    $value = $site_text[$key] ?? $key; // Return the key itself as a fallback

    // Perform replacements
    if (!empty($replacements) && is_array($replacements)) {
        foreach ($replacements as $placeholder => $replacement) {
            $value = str_replace($placeholder, $replacement, $value);
        }
    }
    
    return $value;
}