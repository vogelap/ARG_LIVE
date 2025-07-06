<?php
// File: arg_game/admin/intro_config.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

$success_message = '';

if (isset($_POST['save_intro_settings'])) {
    $settings_to_save = [
        'INTRO_ENABLED'     => isset($_POST['intro_enabled']) ? '1' : '0',
        'INTRO_VIDEO_URL'   => trim($_POST['intro_video_url']),
        'INTRO_TEXT'        => trim($_POST['intro_text'])
    ];

    $stmt = $mysqli->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    foreach ($settings_to_save as $key => $value) {
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    $stmt->close();
    $success_message = "Intro video settings saved successfully!";
    // Reload settings constants so the page reflects the new values immediately
    require_once __DIR__ . '/../includes/settings.php';
}

// Fetch current settings to display in the form
$settings = [];
$result = $mysqli->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'INTRO_%'");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = 'Intro Video Configuration';
include __DIR__ . '/../templates/admin_header.php';
?>

<div class="container">
    <h2>Intro Video Configuration</h2>
    <p>This video will be shown to players once per session immediately after they log in, before they can see the puzzle dashboard.</p>
    <?php if ($success_message): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>

    <form action="intro_config.php" method="post">
        <div class="form-group">
            <label>
                <input type="checkbox" name="intro_enabled" value="1" <?php echo (defined('INTRO_ENABLED') && INTRO_ENABLED) ? 'checked' : ''; ?>>
                <strong>Enable Intro Video</strong>
            </label>
            <small>If unchecked, players will go directly to the puzzle dashboard after logging in.</small>
        </div>
        <div class="form-group">
            <label for="intro_video_url">Intro Video URL</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="intro_video_url" id="intro_video_url" class="form-control" value="<?php echo htmlspecialchars($settings['INTRO_VIDEO_URL'] ?? ''); ?>" placeholder="e.g., https://example.com/intro.mp4" style="flex-grow: 1;">
                <button type="button" class="btn btn-secondary" id="select-media-btn">Select from Library</button>
            </div>
            <small><b>Recommended format: .MP4</b>. For Dropbox links, use the standard sharing link.</small>
        </div>
        
        <div id="media-preview-container" style="text-align: center; border: 1px dashed var(--admin-border); padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; min-height: 100px; display: flex; justify-content: center; align-items: center; flex-direction: column;">
            <div id="preview-wrapper"></div>
            <div id="preview-message" style="color: var(--admin-text-muted);">Ready to receive media</div>
        </div>
        
        <div class="form-group">
            <label for="intro_text">Message to Display with Video</label>
            <textarea name="intro_text" id="intro_text" rows="3" class="form-control"><?php echo htmlspecialchars($settings['INTRO_TEXT'] ?? ''); ?></textarea>
        </div>
        <button type="submit" name="save_intro_settings" class="btn">Save Settings</button>
    </form>
</div>

<div id="media-modal-overlay">
    <div id="media-modal-content">
        <button type="button" onclick="closeMediaModal()" class="modal-close-btn">&times;</button>
        <iframe id="media-modal-iframe" src="about:blank"></iframe>
    </div>
</div>

<style>
    /* Basic styling for the modal pop-up */
    #media-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1050; justify-content: center; align-items: center; }
    #media-modal-content { background: var(--admin-surface); padding: 20px; border-radius: 8px; width: 90%; max-width: 1000px; height: 80%; box-shadow: 0 5px 15px rgba(0,0,0,0.5); display: flex; flex-direction: column; position: relative; }
    #media-modal-iframe { width: 100%; height: 100%; border: none; }
    .modal-close-btn { position: absolute; top: -10px; right: -10px; background: red; color: white; border-radius: 50%; width: 30px; height: 30px; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer; }
    #preview-wrapper video { max-width: 100%; max-height: 250px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mediaUrlInput = document.getElementById('intro_video_url');
    const previewWrapper = document.getElementById('preview-wrapper');
    const previewMessage = document.getElementById('preview-message');

    // --- Media Modal Functions ---
    window.openMediaModal = () => {
        document.getElementById('media-modal-iframe').src = 'select_media.php';
        document.getElementById('media-modal-overlay').style.display = 'flex';
    };
    window.closeMediaModal = () => {
        document.getElementById('media-modal-iframe').src = 'about:blank';
        document.getElementById('media-modal-overlay').style.display = 'none';
    };
    // This function is called by the modal window when a file is clicked
    window.setMediaUrlAndType = (url, type) => {
        mediaUrlInput.value = url;
        // Manually trigger the input event to update the preview
        mediaUrlInput.dispatchEvent(new Event('input', { bubbles: true, cancelable: true }));
        closeMediaModal();
    };

    // --- Live Preview Logic ---
    function updatePreview() {
        const url = mediaUrlInput.value.trim();
        previewWrapper.innerHTML = '';
        previewMessage.textContent = '';

        if (!url) {
            previewMessage.textContent = 'No media selected.';
            return;
        }
        
        // FIXED: Added 'mov' and simplified the regex test
        if (/\.(mp4|webm|ogg|mov)$/i.test(url)) {
            const video = document.createElement('video');
            video.controls = true;
            video.autoplay = true;
            video.muted = true;
            video.src = url;
            video.addEventListener('error', () => {
                previewMessage.textContent = 'Could not load video preview. Check URL.';
            });
            previewWrapper.appendChild(video);
            previewMessage.textContent = '';
        } else {
            previewMessage.textContent = 'Preview is only available for video files (.mp4, .webm, .ogg, .mov).';
        }
    }
    
    // --- Event Listeners ---
    document.getElementById('select-media-btn').addEventListener('click', openMediaModal);
    mediaUrlInput.addEventListener('input', updatePreview);

    // Initial call to set up preview on page load
    updatePreview();
});
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>