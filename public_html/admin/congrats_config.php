<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$success_message = '';

// Handle saving the settings
if (isset($_POST['save_congrats'])) {
    $settings_to_save = [
        'CONGRATS_TITLE'        => $_POST['congrats_title'],
        'CONGRATS_TEXT'         => $_POST['congrats_text'],
        'CONGRATS_VIDEO_URL'    => $_POST['congrats_video_url'],
        'CONGRATS_DOWNLOAD_URL' => $_POST['congrats_download_url'] // Added new setting
    ];

    $stmt = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    foreach ($settings_to_save as $key => $value) {
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $stmt->close();
    $success_message = "Victory page settings saved successfully!";
    // Reload settings constants with new values
    require_once __DIR__ . '/../includes/settings.php';
}

// Fetch current settings to display in the form
$settings = [];
$result = $mysqli->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'CONGRATS_%'");
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
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <h2>Victory Page Configuration</h2>
        <a href="<?php echo SITE_URL; ?>/public/congratulations.php" target="_blank" class="btn btn-secondary">View Live Page (Player View)</a>
    </div>
    <p>This is the page players will see after they solve the final puzzle in the game.</p>
    <?php if ($success_message): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>

    <form action="congrats_config.php" method="post">
        <div class="form-group">
            <label for="congrats_title">Congratulations Title</label>
            <input type="text" name="congrats_title" id="congrats_title" value="<?php echo htmlspecialchars($settings['CONGRATS_TITLE']); ?>" required>
        </div>
        <div class="form-group">
            <label for="congrats_text">Congratulations Message</label>
            <textarea name="congrats_text" id="congrats_text" rows="4" required><?php echo htmlspecialchars($settings['CONGRATS_TEXT']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="congrats_video_url">Victory Video URL (for streaming)</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="congrats_video_url" id="congrats_video_url" value="<?php echo htmlspecialchars($settings['CONGRATS_VIDEO_URL']); ?>" placeholder="e.g., https://example.com/video.mp4" style="flex-grow: 1;">
                <button type="button" class="btn btn-secondary" id="select-media-btn">Select from Library</button>
            </div>
            <small>
                <b>Recommended format: .MP4</b>. For Dropbox links, use the standard sharing link.
            </small>
        </div>
        <div id="media-preview-container" style="text-align: center; border: 1px dashed var(--admin-border); padding: 1rem; margin-top: 1.5rem; border-radius: 8px; min-height: 100px; display: flex; justify-content: center; align-items: center; flex-direction: column;">
            <div id="preview-wrapper"></div>
            <div id="preview-message-error" style="color: var(--admin-danger-text); display: none;">
                <strong>Unable to load preview.</strong><br>
                <small>Please check that the URL is correct, publicly accessible, and points directly to a media file (e.g., .mp4, .mp3, .jpg).</small>
            </div>
            <div id="preview-message-empty" style="color: var(--admin-text-muted);">Ready to receive media</div>
        </div>
        <div class="form-group">
            <label for="congrats_download_url">Video Download URL (Optional)</label>
            <input type="text" name="congrats_download_url" id="congrats_download_url" value="<?php echo htmlspecialchars($settings['CONGRATS_DOWNLOAD_URL'] ?? ''); ?>" placeholder="Leave blank to hide download button">
            <small>Provide a direct link to the video file for players to download.</small>
        </div>
        <button type="submit" name="save_congrats" class="btn">Save Settings</button>
    </form>
</div>

<div id="media-modal-overlay">
    <div id="media-modal-content">
        <button type="button" onclick="closeMediaModal()" style="position: absolute; top: 10px; right: 20px; font-size: 1.5rem; background: none; border: none; color: white; cursor: pointer;">&times;</button>
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
    const mediaUrlInput = document.getElementById('congrats_video_url');
    mediaUrlInput.value = url;
    // Manually trigger the input event to update the preview
    const event = new Event('input', { bubbles: true, cancelable: true });
    mediaUrlInput.dispatchEvent(event);
    closeMediaModal();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('select-media-btn').addEventListener('click', openMediaModal);

    const mediaUrlInput = document.getElementById('congrats_video_url');
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
            errorMessage.querySelector('small').textContent = "This file type is not supported for preview (.png, .jpg, .mp4, .mp3, etc).";
            return;
        }
        
        element.style.maxWidth = '100%';
        element.style.maxHeight = '250px';
        element.addEventListener('error', () => {
            previewWrapper.innerHTML = '';
            errorMessage.style.display = 'block';
            errorMessage.querySelector('small').textContent = "Please check that the URL is correct, publicly accessible, and points directly to a media file.";
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