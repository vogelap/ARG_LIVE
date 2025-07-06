<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_GET['id'])) {
    die("No theme ID specified.");
}

$id = (int)$_GET['id'];
$stmt = $mysqli->prepare("SELECT name, settings_json, is_admin_theme FROM themes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$theme = $result->fetch_assoc();

if (!$theme) {
    die("Theme not found.");
}

$file_name = preg_replace('/[^a-z0-9_]/i', '_', $theme['name']) . '.json';

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $file_name . '"');

// Re-structure for export
$export_data = [
    'name' => $theme['name'],
    'is_admin_theme' => (bool)$theme['is_admin_theme'],
    'settings_json' => json_decode($theme['settings_json'])
];

echo json_encode($export_data, JSON_PRETTY_PRINT);
exit;