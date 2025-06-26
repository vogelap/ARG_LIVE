<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/GameManager.php';

if (!isset($_POST['player_id'])) {
    header("Location: manage_users.php");
    exit;
}

$player_id = (int)$_POST['player_id'];

$game_manager = new GameManager($mysqli);
$game_manager->resetPlayerProgress($player_id);

// Redirect back to the player's detail page
header("Location: user_details.php?id=" . $player_id . "&success=reset");
exit;
?>