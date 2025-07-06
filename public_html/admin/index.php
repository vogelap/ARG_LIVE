<?php
// File: arg_game/admin/index.php

require_once __DIR__ . '/../includes/session.php';
require_admin(); // Ensures only admins can access this page
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';

// Check for incomplete configurations to alert the admin
$congrats_incomplete = empty(CONGRATS_TITLE) || empty(CONGRATS_TEXT);
$is_game_locked = defined('GAME_LIVE_DATETIME') && time() < strtotime(GAME_LIVE_DATETIME);

// Fetch dashboard statistics
$user_manager = new User($mysqli);
$all_users = $user_manager->getAll();

// Filter for players only for the count
$players = array_filter($all_users, function($user) {
    return !$user['is_admin'];
});
$player_count = count($players);

$puzzle_manager = new Puzzle($mysqli);
$puzzles = $puzzle_manager->getAll();
$puzzle_count = count($puzzles);

// Fetch top player from the leaderboard
$leaderboard_query = $mysqli->query("
    SELECT u.id, u.username, COUNT(pp.id) as score
    FROM users u
    JOIN player_progress pp ON u.id = pp.player_id AND pp.status = 'solved'
    WHERE u.is_admin = 0
    GROUP BY u.id, u.username ORDER BY score DESC, u.username ASC LIMIT 1
");
$top_player = $leaderboard_query ? $leaderboard_query->fetch_assoc() : null;

$page_title = 'Admin Dashboard';
include __DIR__ . '/../templates/admin_header.php';
?>

<div class="container">
    <?php if ($is_game_locked): ?>
        <div class="alert alert-info">
            <h4>Site is Currently Locked</h4>
            <p>The public site is displaying the countdown page. It will go live on: <strong><?php echo date('F j, Y \a\t g:i A', strtotime(GAME_LIVE_DATETIME)); ?></strong></p>
            <a href="game_state_config.php" class="btn btn-secondary">Adjust Game State</a>
        </div>
    <?php endif; ?>

    <div class="stat-card-container">
        <a href="manage_users.php" class="stat-card">
            <h4>Total Players</h4>
            <div class="stat-number"><?php echo $player_count; ?></div>
        </a>
        <a href="manage_puzzles.php" class="stat-card">
            <h4>Total Puzzles</h4>
            <div class="stat-number"><?php echo $puzzle_count; ?></div>
        </a>
        <div class="stat-card">
             <h4>Top Player</h4>
             <div class="stat-number" style="font-size: 1.8rem;"><?php echo $top_player ? htmlspecialchars($top_player['username']) : 'N/A'; ?></div>
        </div>
    </div>

    <h3>Quick Actions</h3>
    <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
        <a href="edit_puzzle.php" class="btn">Create New Puzzle</a>
        <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
        <a href="player_view_login.php" target="_blank" class="btn btn-secondary">View Player Site</a>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>