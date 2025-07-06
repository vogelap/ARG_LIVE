<?php
// File: arg_game/admin/edit_user.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/User.php';

$user_manager = new User($mysqli);

$user = ['id' => '', 'username' => '', 'email' => '', 'is_admin' => 0];
$page_title = 'Add New User';
$is_edit = false;
$error = '';
$success = '';

if (isset($_GET['id'])) {
    $is_edit = true;
    $user_id = (int)$_GET['id'];
    $user_data = $user_manager->find($user_id);
    if (!$user_data) {
        header("Location: manage_users.php?error=" . urlencode("User not found."));
        exit;
    }
    $user = $user_data;
    $page_title = 'Edit User';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Admins cannot revoke their own admin status
    $is_admin = (isset($_POST['is_admin']) && $id != $_SESSION['user_id']) ? 1 : ($id == $_SESSION['user_id'] ? 1 : 0);

    if (!empty($password) && $password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        if ($is_edit) {
            // Update existing user
            if (!$user_manager->updateProfile($id, $username, $email, $password, $is_admin)) {
                $error = "Error updating user. Email or username may already be in use.";
            } else {
                $success = "User updated successfully!";
                $user = $user_manager->find($id); // Refresh data
            }
        } else {
            // Create new user
            if (empty($password)) {
                $error = "Password is required for new users.";
            } else {
                $new_user_id = $user_manager->register($username, $email, $password, $is_admin);
                if (!$new_user_id) {
                    $error = "Error creating user. Email or username may already be taken.";
                } else {
                    header("Location: manage_users.php?success=" . urlencode("User created successfully!"));
                    exit;
                }
            }
        }
    }
}

include __DIR__ . '/../templates/admin_header.php';
?>
<div class="container">
    <h2><?php echo $page_title; ?></h2>
    <?php if ($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>

    <form action="edit_user.php<?php echo $is_edit ? '?id='.htmlspecialchars($user['id']) : ''; ?>" method="post">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" <?php echo !$is_edit ? 'required' : ''; ?>>
            <?php if ($is_edit): ?>
                <small>Leave blank to keep the current password.</small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control">
        </div>

        <hr style="border-color: var(--admin-border); margin: 2rem 0;">

        <div class="form-group">
            <label>
                <input type="checkbox" name="is_admin" value="1" <?php echo ($user['is_admin'] ?? 0) ? 'checked' : ''; ?>
                <?php if ($is_edit && $user['id'] == $_SESSION['user_id']) echo 'disabled'; ?>>
                <strong>Is Administrator</strong>
            </label>
            <small>Administrators have full access to this control panel.</small>
            <?php if ($is_edit && $user['id'] == $_SESSION['user_id']): ?>
                <p class="error" style="margin-top: 1rem;">You cannot revoke your own admin status.</p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn"><?php echo $is_edit ? 'Update' : 'Create'; ?> User</button>
    </form>
</div>
<?php include __DIR__ . '/../templates/admin_footer.php'; ?>