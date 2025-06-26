<?php
// File: arg_game/admin/ajax_update_player_progress.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/GameManager.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['puzzle_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

$user_id = (int)$data['user_id'];
$puzzle_id = (int)$data['puzzle_id'];
$status = $data['status'];

$allowed_statuses = ['locked', 'unlocked', 'solved'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// We need to either update an existing record or insert a new one.
$now = date("Y-m-d H:i:s");
$unlocked_at = null;
$solved_at = null;

if ($status === 'unlocked') {
    $unlocked_at = $now;
} elseif ($status === 'solved') {
    $solved_at = $now;
    // If a puzzle is being marked as solved, it must also have been unlocked.
    // We set unlocked_at to now() if it's not already set.
    $check_stmt = $mysqli->prepare("SELECT unlocked_at FROM player_progress WHERE player_id = ? AND puzzle_id = ?");
    $check_stmt->bind_param("ii", $user_id, $puzzle_id);
    $check_stmt->execute();
    $res = $check_stmt->get_result()->fetch_assoc();
    if (empty($res['unlocked_at'])) {
        $unlocked_at = $now;
    } else {
        $unlocked_at = $res['unlocked_at'];
    }
}

// Use INSERT ... ON DUPLICATE KEY UPDATE to handle both new and existing progress records.
$stmt = $mysqli->prepare("
    INSERT INTO player_progress (player_id, puzzle_id, status, unlocked_at, solved_at)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status), unlocked_at = VALUES(unlocked_at), solved_at = VALUES(solved_at)
");

$stmt->bind_param("iisss", $user_id, $puzzle_id, $status, $unlocked_at, $solved_at);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}

$stmt->close();