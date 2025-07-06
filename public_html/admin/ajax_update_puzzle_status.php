<?php
// File: arg_game/admin/ajax_update_puzzle_status.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && isset($data['field']) && isset($data['value'])) {
    $id = (int)$data['id'];
    $field = $data['field'];
    $value = (int)$data['value'];

    // Whitelist the field to prevent arbitrary column updates
    $allowed_fields = ['is_visible'];
    if (in_array($field, $allowed_fields)) {
        $stmt = $mysqli->prepare("UPDATE puzzles SET `$field` = ? WHERE id = ?");
        $stmt->bind_param("ii", $value, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid field specified.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
}