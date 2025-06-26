<?php
require_once __DIR__ . '/gatekeeper.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/GameManager.php';

$player_id = $_SESSION['user_id'];
$game_manager = new GameManager($mysqli);
$story_entries = $game_manager->getStorySoFar($player_id);

include __DIR__ . '/../templates/header.php';
?>

<style>
    .story-log-container {
        max-width: 800px;
        margin: 0 auto;
    }
    .story-entry {
        background-color: var(--surface-color);
        border: 1px solid var(--border-color);
        border-left: 5px solid var(--primary-color);
        border-radius: 8px;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .story-entry-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
    }
    .story-entry-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--primary-color);
        margin: 0;
    }
    .story-entry-date {
        font-size: 0.9rem;
        color: var(--text-muted-color);
        font-style: italic;
    }
    .story-entry-content {
        line-height: 1.7;
    }
</style>

<div class="container story-log-container">
    <h2 style="font-weight: 300; margin-bottom: 2.5rem; text-align: center;">Mission Narrative Log</h2>

    <?php if (empty($story_entries)): ?>
        <div class="card" style="text-align: center;">
            <p>Your story is just beginning. Solve your first puzzle to start compiling your narrative log.</p>
            <a href="index.php" class="btn">Return to Dashboard</a>
        </div>
    <?php else: ?>
        <?php foreach ($story_entries as $entry): ?>
            <div class="story-entry">
                <div class="story-entry-header">
                    <h3 class="story-entry-title"><?php echo htmlspecialchars($entry['title']); ?></h3>
                    <span class="story-entry-date">
                        Unlocked: <?php echo date('M j, Y @ g:i A', strtotime($entry['solved_at'])); ?>
                    </span>
                </div>
                <div class="story-entry-content">
                    <p><?php echo nl2br(htmlspecialchars($entry['story_text'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>