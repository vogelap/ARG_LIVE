<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: manage_media.php");
    exit;
}

$id = (int)$_GET['id'];

// Get file path from DB to delete the physical file
$stmt = $mysqli->prepare("SELECT file_path FROM media_library WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if ($item) {
    // Construct local server path from URL
    $local_path = str_replace(SITE_URL, rtrim(__DIR__, '/admin'), $item['file_path']);
    if (file_exists($local_path)) {
        unlink($local_path);
    }
}

// Delete from database
$delete_stmt = $mysqli->prepare("DELETE FROM media_library WHERE id = ?");
$delete_stmt->bind_param("i", $id);
$delete_stmt->execute();

header("Location: manage_media.php");
exit;
?>