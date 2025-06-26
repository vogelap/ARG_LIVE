<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';

if (!isset($_GET['id'])) {
    header("Location: manage_puzzles.php");
    exit;
}

$id = (int)$_GET['id'];
// UPDATED: Deletes the puzzle using the new OOP method
$puzzle_manager = new Puzzle($mysqli);
$puzzle_manager->delete($id);

header("Location: manage_puzzles.php");
exit;
?>