<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';

$user_id = $_SESSION['user_id'];
$user_manager = new User($mysqli);
$user = $user_manager->find($user_id);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($password) && $password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } else {
        // We pass `null` for is_admin to prevent changing our own status
        if ($user_manager->updateProfile($user_id, $username, $user['email'], $password, null)) {
            $success_message = "Profile updated successfully!";
            // Update session username if it changed
            if ($_SESSION['username'] !== $username) {
                $_SESSION['username'] = $username;
            }
            $user = $user_manager->find($user_id); // Refresh data
        } else {
            $error_message = "Error updating profile. Username may already be taken.";
        }
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>
<div class="container">
    <h2>My Profile</h2>
    <?php if ($success_message): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>
    <?php if ($error_message): ?><p class="error"><?php echo $error_message; ?></p><?php endif; ?>

    <form action="profile.php" method="post">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>
         <div class="form-group">
            <label for="email">Email (Cannot be changed)</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
        </div>
        
        <div class="danger-zone" style="margin-bottom: 1.5rem; background-color: transparent; border-color: var(--admin-border);">
            <h3 style="color: var(--admin-text-muted);">Change Password</h3>
            <p style="color: var(--admin-text-muted);">Only fill out the fields below if you wish to change your password.</p>
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password">
            </div>
             <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password">
            </div>
        </div>
        
        <button type="submit" class="btn">Update Profile</button>
    </form>
</div>
<?php include __DIR__ . '/../templates/admin_footer.php'; ?>