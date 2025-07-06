<?php
// File: arg_game/public/intro.php

require_once __DIR__ . '/../includes/session.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/classes/User.php';

// MODIFIED: Mark intro as seen in the database for the current user.
$user_manager = new User($mysqli);
$user_manager->markIntroAsSeen();

$video_url = defined('INTRO_VIDEO_URL') ? INTRO_VIDEO_URL : '';
if (strpos($video_url, 'dropbox.com') !== false) {
    $video_url = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $video_url);
    $video_url = strtok($video_url, '?');
}

$page_title = 'Mission Briefing';
include __DIR__ . '/../templates/header.php';
?>
<div class="container intro-container">
    <div class="card">
        <h2>Mission Briefing</h2>
        <p class="intro-text"><?php echo nl2br(htmlspecialchars(defined('INTRO_TEXT') ? INTRO_TEXT : 'Welcome. Your mission begins now.')); ?></p>
        <?php if (!empty($video_url)): ?>
            <video class="intro-video" src="<?php echo htmlspecialchars($video_url); ?>" controls autoplay muted playsinline>
                Your browser does not support the video tag.
            </video>
        <?php endif; ?>
        <br><a href="index.php" class="btn">Proceed to Puzzles</a>
    </div>
</div>
<?php include __DIR__ . '/../templates/footer.php'; ?>