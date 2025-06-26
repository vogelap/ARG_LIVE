<?php
require_once __DIR__ . '/../includes/session.php';
// FIXED: Use the new unified require_login() function
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';

header('Content-Type: application/json');

$puzzle_id = $_GET['puzzle_id'] ?? 0;

if ($puzzle_id > 0) {
    $puzzle_manager = new Puzzle($mysqli);
    $hints = $puzzle_manager->getHints($puzzle_id);
    
    // We only send the text, not the full DB record
    $hint_texts = array_column($hints, 'hint_text');
    
    echo json_encode(['success' => true, 'hints' => $hint_texts]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Puzzle ID.']);
}