<?php
// File: arg_game/public/index.php

require_once __DIR__ . '/gatekeeper.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/intro_gatekeeper.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';
require_once __DIR__ . '/../includes/classes/GameManager.php';

$player_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$puzzle_manager = new Puzzle($mysqli);
$all_dashboard_puzzles = $puzzle_manager->getDashboardPuzzlesForPlayer($player_id);

$available_puzzles = [];
$solved_puzzles = [];
foreach ($all_dashboard_puzzles as $puzzle) {
    if ($puzzle['status'] === 'solved') {
        $solved_puzzles[] = $puzzle;
    } else {
        $available_puzzles[] = $puzzle;
    }
}

$game_manager = new GameManager($mysqli);
$is_game_complete = $game_manager->hasPlayerCompletedGame($player_id);

$page_title = 'Player Dashboard';
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <h2 style="font-weight: 300; margin-bottom: 2.5rem;"><?php echo get_text('dashboard_welcome'); ?> <strong style="font-weight: 600;"><?php echo htmlspecialchars($username); ?></strong>!</h2>
    
    <?php if (defined('INTRO_ENABLED') && INTRO_ENABLED): ?>
        <a href="intro.php" class="mission-briefing-link">
            <div>
                <strong><?php echo get_text('dashboard_mission_briefing_link'); ?></strong><br>
                <span><?php echo get_text('dashboard_mission_briefing_tagline'); ?></span>
            </div>
            <i class="fas fa-video"></i>
        </a>
    <?php endif; ?>

    <?php if ($is_game_complete): ?>
        <div class="completion-banner">
            <h3><?php echo get_text('dashboard_mission_complete_banner_header'); ?></h3>
            <p><?php echo get_text('dashboard_mission_complete_banner_text'); ?></p>
            <a href="congratulations.php" class="btn"><?php echo get_text('dashboard_mission_complete_banner_button'); ?></a>
        </div>
    <?php endif; ?>

    <div class="puzzle-section">
        <h3><i class="fas fa-unlock-alt icon"></i> <?php echo get_text('dashboard_available_puzzles'); ?></h3>
        <div class="puzzle-list">
            <?php if (empty($available_puzzles)): ?>
                <?php if (!$is_game_complete): ?>
                    <p><?php echo get_text('dashboard_no_available_puzzles'); ?></p>
                <?php else: ?>
                     <p><?php echo get_text('dashboard_all_puzzles_solved'); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($available_puzzles as $puzzle): ?>
                    <a href="puzzle.php?id=<?php echo $puzzle['id']; ?>" class="puzzle-item">
                        <span><?php echo htmlspecialchars($puzzle['title']); ?></span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="puzzle-section">
        <h3><i class="fas fa-check-circle icon"></i> <?php echo get_text('dashboard_solved_puzzles'); ?></h3>
        <div class="puzzle-list">
            <?php if (empty($solved_puzzles)): ?>
                <p><?php echo get_text('dashboard_no_solved_puzzles'); ?></p>
            <?php else: ?>
                <?php foreach (array_reverse($solved_puzzles) as $puzzle): ?>
                    <div class="puzzle-item solved">
                        <span class="puzzle-title"><?php echo htmlspecialchars($puzzle['title']); ?></span>
                        <span class="solved-date">
                            <?php echo get_text('dashboard_solved_timestamp_prefix'); ?> <?php echo date('M j, Y @ g:i A', strtotime($puzzle['solved_at'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>