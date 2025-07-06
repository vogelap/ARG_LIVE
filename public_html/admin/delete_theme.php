<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // UPDATED: This now prevents the deletion of an active theme by checking the is_active flag.
    $stmt = $mysqli->prepare("DELETE FROM themes WHERE id = ? AND is_active = 0");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: manage_themes.php");
exit;
?>