<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$success_message = '';

// Handle saving the settings
if (isset($_POST['save_site_settings'])) {
    $settings_to_save = [
        'SITE_NAME'             => $_POST['site_name'],
        'SITE_DESCRIPTION'      => $_POST['site_description'],
        'ADMIN_DASHBOARD_NAME'  => $_POST['admin_dashboard_name'] // Added new setting
    ];

    $stmt = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    foreach ($settings_to_save as $key => $value) {
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $stmt->close();
    $success_message = "Site settings saved successfully!";
    // Reload settings constants with new values
    require_once __DIR__ . '/../includes/settings.php';
}

// Fetch current settings to display in the form
$settings = [];
$result = $mysqli->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('SITE_NAME', 'SITE_DESCRIPTION', 'ADMIN_DASHBOARD_NAME')");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include __DIR__ . '/../templates/admin_header.php';
?>
<div class="container">
    <h2>Site Settings</h2>
    <p>Configure the public name and branding for your game.</p>
    <?php if ($success_message): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>

    <form action="site_config.php" method="post">
        <h3>Public Site Settings</h3>
        <div class="form-group">
            <label for="site_name">Public Site Name</label>
            <input type="text" name="site_name" id="site_name" value="<?php echo htmlspecialchars($settings['SITE_NAME']); ?>" required>
            <small>This will appear in the public navigation bar and page titles.</small>
        </div>
        <div class="form-group">
            <label for="site_description">Public Welcome Description</label>
            <textarea name="site_description" id="site_description" rows="4" required><?php echo htmlspecialchars($settings['SITE_DESCRIPTION']); ?></textarea>
            <small>This message is shown to new users on the login and registration pages.</small>
        </div>

        <hr style="border-color: var(--admin-border); margin: 2rem 0;">

        <h3>Admin Settings</h3>
        <!-- ADDED: New field for the admin dashboard name -->
        <div class="form-group">
            <label for="admin_dashboard_name">Admin Dashboard Name</label>
            <input type="text" name="admin_dashboard_name" id="admin_dashboard_name" value="<?php echo htmlspecialchars($settings['ADMIN_DASHBOARD_NAME'] ?? 'ARG Dashboard'); ?>" required>
            <small>This name appears at the top of the admin navigation bar.</small>
        </div>

        <button type="submit" name="save_site_settings" class="btn">Save Settings</button>
    </form>
</div>
<?php include __DIR__ . '/../templates/admin_footer.php'; ?>