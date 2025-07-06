<?php
require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';

// Logic to handle Dropbox URLs for streaming
$video_url = defined('CONGRATS_VIDEO_URL') ? CONGRATS_VIDEO_URL : '';
if (strpos($video_url, 'dropbox.com') !== false) {
    $video_url = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $video_url);
    $video_url = strtok($video_url, '?');
}

// Get the download URL
$download_url = defined('CONGRATS_DOWNLOAD_URL') ? CONGRATS_DOWNLOAD_URL : '';

include __DIR__ . '/../templates/header.php';
?>

<style>
    .victory-container { text-align: center; }
    .victory-video { max-width: 100%; width: 800px; margin: 2rem auto; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
    .victory-title { font-size: 3rem; font-weight: 700; color: var(--primary-color); }
    .victory-text { font-size: 1.2rem; max-width: 700px; margin: 1rem auto 2rem; }
    .action-buttons { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; }
</style>

<div class="container victory-container">
    <h1 class="victory-title"><?php echo htmlspecialchars(defined('CONGRATS_TITLE') ? CONGRATS_TITLE : 'Congratulations!'); ?></h1>
    <p class="victory-text"><?php echo nl2br(htmlspecialchars(defined('CONGRATS_TEXT') ? CONGRATS_TEXT : 'You have completed the game.')); ?></p>

    <?php if (!empty($video_url)): ?>
        <video class="victory-video" src="<?php echo htmlspecialchars($video_url); ?>" controls autoplay muted loop playsinline>
            Your browser does not support the video tag.
        </video>
    <?php endif; ?>
    
    <div class="action-buttons">
        <a href="index.php" class="btn btn-secondary"><?php echo get_text('congrats_back_to_dashboard'); ?></a>
        <?php if (!empty($download_url)): ?>
            <a href="<?php echo htmlspecialchars($download_url); ?>" class="btn" download><?php echo get_text('congrats_download_button'); ?></a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>