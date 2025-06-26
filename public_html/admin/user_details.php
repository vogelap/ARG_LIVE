<?php
// File: arg_game/admin/user_details.php

require_once __DIR__ . '/../includes/session.php';
require_admin(); // FIXED: Changed from require_admin_login()
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$user_id = (int)$_GET['id'];
$user_manager = new User($mysqli);
$user = $user_manager->find($user_id);

if (!$user) {
    header("Location: manage_users.php?error=not_found");
    exit;
}

// Fetch the user's progress on all puzzles
$progress_query = $mysqli->prepare("
    SELECT p.id as puzzle_id, p.title, pp.status, pp.unlocked_at, pp.solved_at
    FROM puzzles p
    LEFT JOIN player_progress pp ON p.id = pp.puzzle_id AND pp.player_id = ?
    ORDER BY p.display_order ASC
");
$progress_query->bind_param("i", $user_id);
$progress_query->execute();
$progress_result = $progress_query->get_result()->fetch_all(MYSQLI_ASSOC);


$page_title = 'Details for ' . htmlspecialchars($user['username']);
include __DIR__ . '/../templates/admin_header.php';
?>

<div class="container">
    <h2>Progress for <?php echo htmlspecialchars($user['username']); ?></h2>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <a href="manage_users.php">&laquo; Back to All Users</a>
    
    <h3 style="margin-top: 2rem;">Puzzle Status</h3>
    <p>You can manually change a puzzle's status for this user using the dropdowns below. Changes are saved automatically.</p>
    <div id="update-feedback" style="display: none; padding: 10px; border-radius: 5px; margin-bottom: 1rem;"></div>

    <table>
        <thead>
            <tr>
                <th>Puzzle Title</th>
                <th>Status</th>
                <th>Unlocked At</th>
                <th>Solved At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($progress_result)): ?>
                <tr><td colspan="4">No puzzles exist in the game yet.</td></tr>
            <?php else: ?>
                <?php foreach ($progress_result as $progress): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($progress['title']); ?></td>
                        <td>
                            <select class="status-select" data-user-id="<?php echo $user_id; ?>" data-puzzle-id="<?php echo $progress['puzzle_id']; ?>" <?php echo $user['is_admin'] ? 'disabled' : ''; ?>>
                                <option value="locked" <?php echo ($progress['status'] == 'locked' || $progress['status'] == null) ? 'selected' : ''; ?>>Locked</option>
                                <option value="unlocked" <?php echo $progress['status'] == 'unlocked' ? 'selected' : ''; ?>>Unlocked</option>
                                <option value="solved" <?php echo $progress['status'] == 'solved' ? 'selected' : ''; ?>>Solved</option>
                            </select>
                        </td>
                        <td><?php echo $progress['unlocked_at'] ? date('Y-m-d H:i:s', strtotime($progress['unlocked_at'])) : 'N/A'; ?></td>
                        <td><?php echo $progress['solved_at'] ? date('Y-m-d H:i:s', strtotime($progress['solved_at'])) : 'N/A'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!$user['is_admin']): ?>
    <div class="danger-zone" style="margin-top: 2rem;">
        <h3>Reset Player Progress</h3>
        <p>This will permanently delete all of this player's puzzle progress and reset them to the beginning of the game. This action cannot be undone.</p>
        <form action="reset_user_progress.php" method="post" onsubmit="return confirm('Are you absolutely sure you want to reset all progress for this user?');">
            <input type="hidden" name="player_id" value="<?php echo $user_id; ?>">
            <button type="submit" class="btn btn-danger">Reset All Progress for <?php echo htmlspecialchars($user['username']); ?></button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelects = document.querySelectorAll('.status-select');
    const feedbackDiv = document.getElementById('update-feedback');

    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const payload = {
                user_id: this.dataset.userId,
                puzzle_id: this.dataset.puzzleId,
                status: this.value
            };

            feedbackDiv.style.display = 'none';

            fetch('ajax_update_player_progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    feedbackDiv.textContent = 'Status updated successfully!';
                    feedbackDiv.className = 'success';
                    feedbackDiv.style.display = 'block';
                    // The page can be reloaded to show updated timestamps
                    setTimeout(() => location.reload(), 1000);
                } else {
                    feedbackDiv.textContent = data.message || 'An unknown error occurred.';
                    feedbackDiv.className = 'error';
                    feedbackDiv.style.display = 'block';
                }
            })
            .catch(error => {
                feedbackDiv.textContent = 'A network error occurred.';
                feedbackDiv.className = 'error';
                feedbackDiv.style.display = 'block';
                console.error('Error:', error);
            });
        });
    });
});
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>