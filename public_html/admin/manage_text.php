<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

// Handle saving the text content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_text'])) {
    if (!validate_csrf_token($_POST['csrf_token'])) {
        // A more graceful error handling could be implemented here
        die('Invalid CSRF token. Please refresh and try again.');
    }

    $stmt = $mysqli->prepare("UPDATE site_text SET text_value = ? WHERE text_key = ?");
    if ($stmt) {
        foreach ($_POST['text_values'] as $key => $value) {
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
        }
        $stmt->close();
        $success_message = "Site text updated successfully!";
        // Reload the text after saving
        load_site_text($mysqli);
    } else {
        $error_message = "Failed to prepare the database statement.";
    }
}

// Fetch all text content to display in the form
$text_content = [];
$result = $mysqli->query("SELECT * FROM site_text ORDER BY text_key ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $text_content[] = $row;
    }
}

$page_title = 'Manage Site Text';
include __DIR__ . '/../templates/admin_header.php';
?>

<div class="container">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Manage Site Text</h2>
    </div>
    <p>Use this page to edit the text content that appears across the public-facing site. Use placeholders like <code>{count}</code> where noted, as they will be replaced with dynamic data automatically.</p>
    
    <?php if (isset($success_message)): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>
    <?php if (isset($error_message)): ?><p class="error"><?php echo $error_message; ?></p><?php endif; ?>

    <form action="manage_text.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <?php if (empty($text_content)): ?>
            <p class="error">The `site_text` table is empty or does not exist. Please run the SQL update script.</p>
        <?php else: ?>
            <?php foreach ($text_content as $text): ?>
                <div class="form-group">
                    <label for="<?php echo htmlspecialchars($text['text_key']); ?>"><?php echo htmlspecialchars($text['description']); ?> (Key: <code><?php echo htmlspecialchars($text['text_key']); ?></code>)</label>
                    
                    <?php if (strlen($text['text_value']) > 100): ?>
                        <textarea name="text_values[<?php echo htmlspecialchars($text['text_key']); ?>]" id="<?php echo htmlspecialchars($text['text_key']); ?>" rows="3" class="form-control"><?php echo htmlspecialchars($text['text_value']); ?></textarea>
                    <?php else: ?>
                        <input type="text" name="text_values[<?php echo htmlspecialchars($text['text_key']); ?>]" id="<?php echo htmlspecialchars($text['text_key']); ?>" class="form-control" value="<?php echo htmlspecialchars($text['text_value']); ?>">
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <button type="submit" name="save_text" class="btn">Save All Changes</button>
    </form>
</div>

<?php
include __DIR__ . '/../templates/admin_footer.php';
?>