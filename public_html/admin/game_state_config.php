<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$success_message = '';

// Handle saving the settings
if (isset($_POST['save_game_state'])) {
    $settings_to_save = [
        'GAME_LIVE_DATETIME'    => $_POST['game_live_datetime'],
        'GAME_LOCKED_TITLE'     => $_POST['game_locked_title'],
        'GAME_LOCKED_MESSAGE'   => $_POST['game_locked_message'],
        'GAME_LOCKED_IMAGE_URL' => $_POST['game_locked_image_url'],
        'GAME_LOCKED_IMAGE_POS' => $_POST['game_locked_image_pos']
    ];

    $stmt = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    foreach ($settings_to_save as $key => $value) {
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $stmt->close();
    $success_message = "Game state settings saved successfully!";
    // Reload settings constants with new values
    require_once __DIR__ . '/../includes/settings.php';
}

// Fetch current settings to display in the form
$settings = [];
$result = $mysqli->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'GAME_%'");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include __DIR__ . '/../templates/admin_header.php';
?>
<style>
/* Styles for the media selection modal */
#media-modal-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.7); z-index: 1050;
    justify-content: center; align-items: center;
}
#media-modal-content {
    background: var(--admin-bg); padding: 20px; border-radius: 8px;
    width: 90%; max-width: 1000px; height: 80%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5); display: flex; flex-direction: column;
}
#media-modal-iframe {
    width: 100%; height: 100%; border: none;
}
</style>
<div class="container">
    <h2>Game State Configuration</h2>
    <p>Use these settings to control when the public site becomes available to players.</p>
    <?php if ($success_message): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>

    <form action="game_state_config.php" method="post">
        <div class="form-group">
            <label for="game_live_datetime">Game Start Date & Time</label>
            <input type="datetime-local" name="game_live_datetime" id="game_live_datetime" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($settings['GAME_LIVE_DATETIME']))); ?>" required>
            <small>Players will see a countdown page until this time is reached.</small>
        </div>
        <hr style="border-color: var(--admin-border); margin: 2rem 0;">
        <h3>Countdown Page Content</h3>
        <div class="form-group">
            <label for="game_locked_title">Page Title</label>
            <input type="text" name="game_locked_title" id="game_locked_title" value="<?php echo htmlspecialchars($settings['GAME_LOCKED_TITLE']); ?>" required>
        </div>
        <div class="form-group">
            <label for="game_locked_message">Message to Players</label>
            <textarea name="game_locked_message" id="game_locked_message" rows="4" required><?php echo htmlspecialchars($settings['GAME_LOCKED_MESSAGE']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="game_locked_image_url">Media URL (Optional)</label>
             <div style="display: flex; gap: 10px;">
                <input type="text" name="game_locked_image_url" id="game_locked_image_url" value="<?php echo htmlspecialchars($settings['GAME_LOCKED_IMAGE_URL'] ?? ''); ?>" placeholder="https://example.com/image.jpg" style="flex-grow: 1;">
                 <button type="button" class="btn btn-secondary" id="select-media-btn">Select from Library</button>
            </div>
            <small>An image, video, or audio file to display on the locked screen.</small>
        </div>
        <div id="media-preview-container" style="text-align: center; border: 1px dashed var(--admin-border); padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; min-height: 100px; display: flex; justify-content: center; align-items: center; flex-direction: column;">
            <div id="preview-wrapper"></div>
            <div id="preview-message-error" style="color: var(--admin-danger-text); display: none;">
                <strong>Unable to load preview.</strong><br>
                <small>Please check the URL is correct and the file type is supported (.png, .jpg, .mp4, .mp3, etc).</small>
            </div>
            <div id="preview-message-empty" style="color: var(--admin-text-muted);">
                Ready to receive media
            </div>
        </div>
        <div class="form-group">
            <label for="game_locked_image_pos">Media Position</label>
            <select name="game_locked_image_pos" id="game_locked_image_pos">
                <option value="above" <?php echo ($settings['GAME_LOCKED_IMAGE_POS'] ?? 'above') == 'above' ? 'selected' : ''; ?>>Above Message</option>
                <option value="below" <?php echo ($settings['GAME_LOCKED_IMAGE_POS'] ?? 'above') == 'below' ? 'selected' : ''; ?>>Below Message</option>
            </select>
        </div>


        <button type="submit" name="save_game_state" class="btn">Save Settings</button>
    </form>
</div>

<div id="media-modal-overlay">
    <div id="media-modal-content">
        <button type="button" onclick="closeMediaModal()" style="align-self: flex-end; cursor:pointer;">&times;</button>
        <iframe id="media-modal-iframe" src="about:blank"></iframe>
    </div>
</div>

<script>
// Media Modal Functions
function openMediaModal() {
    document.getElementById('media-modal-iframe').src = 'select_media.php';
    document.getElementById('media-modal-overlay').style.display = 'flex';
}
function closeMediaModal() {
    document.getElementById('media-modal-iframe').src = 'about:blank';
    document.getElementById('media-modal-overlay').style.display = 'none';
}
// This function is called by the modal window
function setMediaUrlAndType(url, type) { // We receive both arguments but only need the URL here
    const mediaUrlInput = document.getElementById('game_locked_image_url');
    mediaUrlInput.value = url;
    // Manually trigger the input event to update the preview
    const event = new Event('input', { bubbles: true, cancelable: true });
    mediaUrlInput.dispatchEvent(event);
    closeMediaModal();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('select-media-btn').addEventListener('click', openMediaModal);

    const mediaUrlInput = document.getElementById('game_locked_image_url');
    const previewWrapper = document.getElementById('preview-wrapper');
    const emptyMessage = document.getElementById('preview-message-empty');
    const errorMessage = document.getElementById('preview-message-error');

    function updatePreview() {
        const url = mediaUrlInput.value.trim();
        previewWrapper.innerHTML = '';
        errorMessage.style.display = 'none';

        if (!url) {
            emptyMessage.style.display = 'block';
            return;
        }
        emptyMessage.style.display = 'none';

        const ext = url.split('.').pop().toLowerCase();
        let element;

        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
            element = document.createElement('img');
        } else if (['mp4', 'webm', 'ogg', 'mov'].includes(ext)) { // FIXED: Added 'mov'
            element = document.createElement('video');
            element.controls = true;
        } else if (['mp3', 'wav'].includes(ext)) {
            element = document.createElement('audio');
            element.controls = true;
        } else {
            errorMessage.style.display = 'block';
            return;
        }
        
        element.style.maxWidth = '100%';
        element.style.maxHeight = '250px';
        element.addEventListener('error', () => {
            previewWrapper.innerHTML = '';
            errorMessage.style.display = 'block';
        });

        element.src = url;
        previewWrapper.appendChild(element);
    }
    
    mediaUrlInput.addEventListener('input', updatePreview);

    // Initial call to set up preview on page load
    updatePreview();
});
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>