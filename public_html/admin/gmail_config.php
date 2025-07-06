<?php
require_once __DIR__ . '/../includes/session.php';
require_admin(); // Uses the new, correct session check
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
// FIXED: Now uses the unified User class
require_once __DIR__ . '/../includes/classes/User.php'; 
require_once __DIR__ . '/../includes/classes/MailManager.php';

// FIXED: Instantiates the User class and finds the current user by their session ID
$user_manager = new User($mysqli);
$current_user = $user_manager->find($_SESSION['user_id']);

$success_message = '';
$error_message = '';

// --- Handle Saving New Settings ---
if (isset($_POST['save_settings'])) {
    $settings_to_save = [
        'SMTP_HOST'       => $_POST['smtp_host'],
        'SMTP_USERNAME'   => $_POST['smtp_username'],
        'SMTP_PORT'       => $_POST['smtp_port'],
        'SMTP_SECURE'     => $_POST['smtp_secure'],
        'EMAIL_FROM'      => $_POST['email_from'],
        'EMAIL_FROM_NAME' => $_POST['email_from_name']
    ];
    if (!empty($_POST['smtp_password'])) {
        $settings_to_save['SMTP_PASSWORD'] = $_POST['smtp_password'];
    }

    $stmt = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    foreach ($settings_to_save as $key => $value) {
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $stmt->close();
    $success_message = "Settings saved successfully!";
    // Reload settings constants with new values
    require __DIR__ . '/../includes/settings.php';
}

// --- Handle Sending a Test Email ---
if (isset($_POST['send_test'])) {
    // FIXED: Uses the email from the fetched user data
    $test_email_address = $current_user['email'];

    try {
        $mail_manager = new MailManager();
        $mail_manager->sendTestEmail($test_email_address);
        $success_message = "Test email sent successfully to " . htmlspecialchars($test_email_address) . ". Please check your inbox.";
    } catch (Exception $e) {
        $error_message = "Failed to send test email. Please check your settings. <br><br><strong>Mailer Error:</strong> " . $e->getMessage();
    }
}

// Fetch current settings to display in the form
$settings = [];
$result = $mysqli->query("SELECT setting_key, setting_value FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include __DIR__ . '/../templates/admin_header.php';
?>
<div class="container">
    <h2>Gmail (SMTP) Configuration</h2>
    <p>These settings are used to send all emails from the system, such as password resets.</p>
    <?php if ($success_message): ?><p class="success" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px;"><?php echo $success_message; ?></p><?php endif; ?>
    <?php if ($error_message): ?><p class="error"><?php echo $error_message; ?></p><?php endif; ?>

    <form action="gmail_config.php" method="post">
        <h3>Edit SMTP Settings</h3>
        <div class="form-group">
            <label for="smtp_host">SMTP Host</label>
            <input type="text" name="smtp_host" id="smtp_host" value="<?php echo htmlspecialchars($settings['SMTP_HOST']); ?>" required>
        </div>
        <div class="form-group">
            <label for="smtp_username">SMTP Username (Your Gmail Address)</label>
            <input type="email" name="smtp_username" id="smtp_username" value="<?php echo htmlspecialchars($settings['SMTP_USERNAME']); ?>" required>
        </div>
        <div class="form-group">
            <label for="smtp_password">SMTP Password (Your Gmail App Password)</label>
            <input type="password" name="smtp_password" id="smtp_password" placeholder="Leave blank to keep current password">
        </div>
        <div class="form-group">
            <label for="smtp_port">SMTP Port</label>
            <input type="number" name="smtp_port" id="smtp_port" value="<?php echo htmlspecialchars($settings['SMTP_PORT']); ?>" required>
        </div>
        <div class="form-group">
            <label for="smtp_secure">SMTP Security</label>
            <select name="smtp_secure" id="smtp_secure">
                <option value="tls" <?php echo ($settings['SMTP_SECURE'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                <option value="ssl" <?php echo ($settings['SMTP_SECURE'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
            </select>
        </div>
        <hr style="margin: 2rem 0;">
        <div class="form-group">
            <label for="email_from">From Email</label>
            <input type="email" name="email_from" id="email_from" value="<?php echo htmlspecialchars($settings['EMAIL_FROM']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email_from_name">From Name</label>
            <input type="text" name="email_from_name" id="email_from_name" value="<?php echo htmlspecialchars($settings['EMAIL_FROM_NAME']); ?>" required>
        </div>
        <button type="submit" name="save_settings" class="btn">Save Settings</button>
    </form>
    
    <hr style="margin: 2rem 0;">
    <h3>Test Configuration</h3>
    <p>Click the button below to send a test email to your own admin address (<strong><?php echo htmlspecialchars($current_user['email']); ?></strong>) using the currently saved settings.</p>
    <form action="gmail_config.php" method="post">
        <button type="submit" name="send_test" class="btn btn-secondary">Send Test Email</button>
    </form>
</div>
<?php include __DIR__ . '/../templates/admin_footer.php'; ?>