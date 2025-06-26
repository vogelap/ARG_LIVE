<?php
// File: arg_game/admin/manage_users.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';

$user_manager = new User($mysqli);
$users = $user_manager->getAll(); // This now includes the last_login field

$puzzle_manager = new Puzzle($mysqli);
$total_puzzles = count($puzzle_manager->getAll());

$page_title = 'Manage Users';
include __DIR__ . '/../templates/admin_header.php';
?>
<div class="container">
    <div style="display:flex; justify-content: space-between; align-items: center;">
        <h2>Manage Users</h2>
        <a href="edit_user.php" class="btn">Add New User</a>
    </div>
    <p>This table shows all registered users and their progress.</p>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Progress</th>
                <th>Last Login</th> <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="6" style="text-align: center;">No users found.</td></tr> <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['is_admin']): ?><span class="badge admin-badge">Admin</span>
                            <?php else: ?><span class="badge player-badge">Player</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$user['is_admin']): ?>
                                <?php $progress_percent = $total_puzzles > 0 ? ($user['solved_count'] / $total_puzzles) * 100 : 0; ?>
                                <div class="progress-bar" title="<?php echo $user['solved_count'] . ' of ' . $total_puzzles . ' solved'; ?>">
                                    <div class="progress-bar-inner" style="width: <?php echo $progress_percent; ?>%;"><?php echo round($progress_percent); ?>%</div>
                                </div>
                            <?php else: ?>N/A<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['last_login']): ?>
                                <?php echo date('Y-m-d H:i', strtotime($user['last_login'])); ?>
                            <?php else: ?>
                                <span style="color: var(--admin-text-muted);">Never</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-links">
                            <a href="user_details.php?id=<?php echo $user['id']; ?>">Details</a> | 
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>">Edit</a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                | <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../templates/admin_footer.php'; ?>