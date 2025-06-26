<?php
// File: arg_game/admin/manage_puzzles.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';

$puzzle_manager = new Puzzle($mysqli);
$puzzles = $puzzle_manager->getAll();

$page_title = 'Manage Puzzles';
include __DIR__ . '/../templates/admin_header.php';
?>

<div class="container">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
        <h2>Manage Puzzles</h2>
        <div>
            <a href="visualizer.php" class="btn btn-secondary">View Flowchart</a>
            <a href="edit_puzzle.php" class="btn">Add New Puzzle</a>
        </div>
    </div>
    <p>Click the toggles to change puzzle visibility or prerequisite status. Drag and drop rows to reorder.</p>

    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Title</th>
                <th>Type</th>
                <th>Visible</th>
                <th>Prereqs On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="puzzle-list">
            <?php foreach ($puzzles as $puzzle): ?>
                <tr data-id="<?php echo $puzzle['id']; ?>">
                    <td class="drag-handle">#<?php echo htmlspecialchars($puzzle['display_order']); ?></td>
                    <td><?php echo htmlspecialchars($puzzle['title']); ?></td>
                    <td><?php echo htmlspecialchars($puzzle['puzzle_type']); ?></td>
                    <td>
                        <label class="switch">
                          <input type="checkbox" class="visibility-toggle" data-id="<?php echo $puzzle['id']; ?>" <?php echo $puzzle['is_visible'] ? 'checked' : ''; ?>>
                          <span class="slider"></span>
                        </label>
                    </td>
                    <td>
                        <label class="switch">
                          <input type="checkbox" class="prereq-toggle" data-id="<?php echo $puzzle['id']; ?>" <?php echo $puzzle['prerequisites_enabled'] ? 'checked' : ''; ?>>
                          <span class="slider"></span>
                        </label>
                    </td>
                    <td class="action-links">
                        <a href="edit_puzzle.php?id=<?php echo $puzzle['id']; ?>">Edit</a> |
                        <a href="view_puzzle_as_player.php?id=<?php echo $puzzle['id']; ?>" target="_blank">View as Player</a> |
                        <a href="delete_puzzle.php?id=<?php echo $puzzle['id']; ?>" onclick="return confirm('Are you sure you want to delete this puzzle? This cannot be undone.');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const puzzleList = document.getElementById('puzzle-list');
    
    // Drag-and-drop sorting functionality
    $(puzzleList).sortable({
        handle: '.drag-handle',
        update: function (event, ui) {
            let order = [];
            $('#puzzle-list tr').each(function(index) {
                $(this).find('.drag-handle').text('#' + (index + 1));
                order.push($(this).data('id'));
            });

            fetch('ajax_reorder_puzzles.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: order })
            }).then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error: Failed to save new puzzle order.');
                }
            });
        }
    }).disableSelection();

    // Event delegation for toggle switches
    puzzleList.addEventListener('change', function(e) {
        const target = e.target;
        if (target.matches('.visibility-toggle, .prereq-toggle')) {
            const id = target.dataset.id;
            const value = target.checked ? 1 : 0;
            let field, url;

            if (target.classList.contains('visibility-toggle')) {
                field = 'is_visible';
                url = 'ajax_update_puzzle_status.php';
            } else {
                field = 'prerequisites_enabled';
                url = 'ajax_toggle_prereqs.php';
            }

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, field: field, value: value })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error saving change: ' + (data.message || 'Unknown error'));
                    target.checked = !target.checked; // Revert toggle on failure
                }
            })
            .catch(error => {
                 alert('A network error occurred. Could not save change.');
                 target.checked = !target.checked;
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>