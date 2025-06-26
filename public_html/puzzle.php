<?php
// File: arg_game/public/puzzle.php

require_once __DIR__ . '/gatekeeper.php';
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/GameManager.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$puzzle_id = (int)$_GET['id'];
$player_id = $_SESSION['user_id'];

$gameManager = new GameManager($mysqli);
$puzzle_manager = new Puzzle($mysqli);

// Check if the puzzle is actually unlocked for this player
if (!$gameManager->isPuzzleUnlocked($player_id, $puzzle_id)) {
    header("Location: index.php?error=locked");
    exit;
}

$puzzle = $puzzle_manager->find($puzzle_id);

if (!$puzzle) {
    header("Location: index.php?error=not_found");
    exit;
}

$puzzle_status = $gameManager->getPuzzleStatusForPlayer($player_id, $puzzle_id);

$hints = $puzzle_manager->getHints($puzzle_id);
$solution_hint_available = !empty($puzzle['solution_hint']);
$hint_data_for_js = [
    'hints' => array_column($hints, 'hint_text'),
    'solutionHint' => $puzzle['solution_hint'] ?? null,
    'confirmText' => get_text('puzzle_hint_confirm_solution')
];

// Logic to prepare media if it exists
$media_html = '';
if (!empty($puzzle['media_url'])) {
    ob_start();
    ?>
    <div class="puzzle-media">
        <?php if ($puzzle['media_type'] == 'image'): ?>
            <img src="<?php echo htmlspecialchars($puzzle['media_url']); ?>" alt="Puzzle Media">
        <?php elseif ($puzzle['media_type'] == 'video'): ?>
            <video src="<?php echo htmlspecialchars($puzzle['media_url']); ?>" controls></video>
        <?php elseif ($puzzle['media_type'] == 'audio'): ?>
            <audio src="<?php echo htmlspecialchars($puzzle['media_url']); ?>" controls></audio>
        <?php endif; ?>
    </div>
    <?php
    $media_html = ob_get_clean();
}

$page_title = $puzzle['title'];
include __DIR__ . '/../templates/header.php';
?>

<div class="container" 
    data-text-story-header="<?php echo htmlspecialchars(get_text('puzzle_story_update_header')); ?>"
    data-text-ajax-error="<?php echo htmlspecialchars(get_text('ajax_error_unexpected')); ?>"
    data-text-final-solve="<?php echo htmlspecialchars(get_text('puzzle_final_solve_redirect')); ?>"
>
    <div style="margin-bottom: 1.5rem;">
        <a href="index.php" class="btn btn-secondary"><?php echo get_text('puzzle_back_to_list'); ?></a>
    </div>

    <div class="card">
        <h2><?php echo htmlspecialchars($puzzle['title']); ?></h2>
        <?php if (($puzzle['media_pos'] ?? 'above') === 'above') echo $media_html; ?>
        
        <div class="puzzle-description">
            <?php echo nl2br(htmlspecialchars($puzzle['description'])); ?>
        </div>
        
        <?php if (($puzzle['media_pos'] ?? 'above') === 'below') echo $media_html; ?>

        <?php
        // ADDED: Display the link URL as a button if it exists
        if (!empty($puzzle['link_url'])):
        ?>
        <div class="puzzle-link-container" style="margin-top: 1.5rem; text-align: center;">
            <a href="<?php echo htmlspecialchars($puzzle['link_url']); ?>" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-external-link-alt"></i> Open Associated Link
            </a>
        </div>
        <?php endif; ?>

        <hr style="border-color: var(--border-color); margin: 2rem 0;">
        
        <div id="feedback-media-player" style="margin-bottom: 1rem; text-align: center;"></div>
        <div id="puzzle-feedback" class="feedback-area" style="display: none;"></div>

        <div class="puzzle-content">
            <?php if ($puzzle_status === 'solved'): ?>
                <div class="success"><?php echo get_text('puzzle_status_already_solved'); ?></div>
            <?php else: ?>
                <?php
                $puzzle_template = __DIR__ . "/../templates/puzzle_templates/{$puzzle['puzzle_type']}.php";
                if (file_exists($puzzle_template)) {
                    $csrf_token = generate_csrf_token();
                    include $puzzle_template;
                } else {
                    echo "<p class='error'>" . get_text('puzzle_error_loading') . "</p>";
                }
                ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($puzzle_status !== 'solved' && ($hints || $solution_hint_available)): ?>
    <div class="card hint-container" data-hints='<?php echo htmlspecialchars(json_encode($hint_data_for_js), ENT_QUOTES, 'UTF-8'); ?>'>
        <h3><i class="fas fa-lightbulb"></i> <?php echo get_text('puzzle_hint_container_header'); ?></h3>
        <p><?php echo get_text('puzzle_hint_container_instructions'); ?></p>
        <button id="request-hint-btn" class="btn btn-secondary"><?php echo get_text('puzzle_hint_button_initial'); ?></button>
        <div id="hint-display-area" style="margin-top: 1rem;"></div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hintContainer = document.querySelector('.hint-container');
    if (hintContainer) {
        const hintButton = document.getElementById('request-hint-btn');
        const hintDisplayArea = document.getElementById('hint-display-area');
        
        const hintData = JSON.parse(hintContainer.dataset.hints);
        const regularHints = hintData.hints || [];
        const solutionHint = hintData.solutionHint;
        const confirmText = hintData.confirmText;
        let revealedHintCount = 0;
        let solutionHintRevealed = false;

        function updateHintButton() {
            const remainingHints = regularHints.length - revealedHintCount;
            if (remainingHints > 0) {
                hintButton.textContent = "<?php echo get_text('puzzle_hint_button_remaining'); ?>".replace('{count}', remainingHints);
            } else if (solutionHint && !solutionHintRevealed) {
                hintButton.textContent = "<?php echo get_text('puzzle_hint_button_reveal_solution'); ?>";
                hintButton.classList.remove('btn-secondary');
                hintButton.classList.add('btn-danger');
            } else {
                hintButton.textContent = "<?php echo get_text('puzzle_hint_button_all_revealed'); ?>";
                hintButton.disabled = true;
            }
        }
        
        updateHintButton();

        hintButton.addEventListener('click', function() {
            if (revealedHintCount < regularHints.length) {
                const hintText = regularHints[revealedHintCount];
                const hintElement = document.createElement('div');
                hintElement.className = 'hint-item';
                hintElement.innerHTML = `<i class="fas fa-key"></i> <strong>Hint ${revealedHintCount + 1}:</strong> ${hintText}`;
                hintDisplayArea.appendChild(hintElement);
                revealedHintCount++;
            } else if (solutionHint && !this.disabled && !solutionHintRevealed) {
                if (confirm(confirmText)) {
                    const solutionElement = document.createElement('div');
                    solutionElement.className = 'hint-item success';
                    solutionElement.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <strong>Solution Hint:</strong> ${solutionHint}`;
                    hintDisplayArea.appendChild(solutionElement);
                    solutionHintRevealed = true;
                }
            }
            updateHintButton();
        });
    }
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>