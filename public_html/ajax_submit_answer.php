<?php
// File: arg_game/public/ajax_submit_answer.php

ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../includes/session.php';
header('Content-Type: application/json');

try {
    require_login(); 
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        throw new Exception(get_text('ajax_error_security_token'));
    }

    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/classes/GameManager.php';
    require_once __DIR__ . '/../includes/classes/Puzzle.php';

    if (!isset($_POST['id']) || !isset($_POST['answer'])) {
        throw new Exception(get_text('ajax_error_missing_data'));
    }

    $puzzle_id = (int)$_POST['id'];
    $player_id = $_SESSION['user_id'];
    $answer = $_POST['answer'];

    $gameManager = new GameManager($mysqli);
    $puzzle_manager = new Puzzle($mysqli);
    $puzzle = $puzzle_manager->find($puzzle_id);

    if (!$puzzle) {
        throw new Exception('Puzzle not found.');
    }

    if ($gameManager->checkSolution($puzzle_id, $answer)) {
        $next_puzzles = $gameManager->recordSolve($player_id, $puzzle_id);
        $is_game_complete = $gameManager->hasPlayerCompletedGame($player_id);

        // MODIFIED: Added story_text to the success response
        echo json_encode([
            'success' => true,
            'message' => get_text('ajax_answer_correct'),
            'next_puzzles' => $next_puzzles,
            'game_complete' => $is_game_complete,
            'success_media_url' => $puzzle['success_media_url'],
            'story_text' => $puzzle['story_text']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => get_text('ajax_answer_incorrect'),
            'failure_media_url' => $puzzle['failure_media_url']
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;