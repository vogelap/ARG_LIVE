<?php
// File: arg_game/admin/ajax_toggle_prereqs.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && isset($data['value'])) {
    $id = (int)$data['id'];
    $value = (int)$data['value'];

    // The field is hardcoded here for security
    $stmt = $mysqli->prepare("UPDATE puzzles SET `prerequisites_enabled` = ? WHERE id = ?");
    $stmt->bind_param("ii", $value, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
}