<?php
// This page is only ever included by gatekeeper.php, which already has the settings loaded.
// It doesn't need to require any files itself.

// --- UPDATED: Prepare Media HTML with detection for video, audio, and images ---
$media_html = '';
if (defined('GAME_LOCKED_IMAGE_URL') && !empty(GAME_LOCKED_IMAGE_URL)) {
    $media_url = GAME_LOCKED_IMAGE_URL;
    $ext = strtolower(pathinfo($media_url, PATHINFO_EXTENSION));

    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $video_extensions = ['mp4', 'webm', 'ogg'];
    $audio_extensions = ['mp3', 'wav'];

    ob_start();
    ?>
    <div class="locked-media" style="margin-bottom: 2rem; margin-top: 1rem;">
        <?php if (in_array($ext, $image_extensions)): ?>
            <img src="<?php echo htmlspecialchars($media_url); ?>" alt="<?php echo htmlspecialchars(GAME_LOCKED_TITLE); ?>" style="max-width: 100%; max-height: 300px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <?php elseif (in_array($ext, $video_extensions)): ?>
            <video src="<?php echo htmlspecialchars($media_url); ?>" autoplay muted loop controls style="max-width: 100%; max-height: 400px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">Your browser does not support the video tag.</video>
        <?php elseif (in_array($ext, $audio_extensions)): ?>
            <audio src="<?php echo htmlspecialchars($media_url); ?>" controls style="width: 100%;">Your browser does not support the audio element.</audio>
        <?php endif; ?>
    </div>
    <?php
    $media_html = ob_get_clean();
}


include __DIR__ . '/../templates/header.php';
?>
<style>
    .locked-container { text-align: center; }
    #countdown-timer {
        font-size: 3rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 2rem 0;
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .timer-box {
        background: var(--surface-color);
        padding: 1rem 1.5rem;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        min-width: 100px;
    }
    .timer-box span {
        display: block;
        font-size: 1rem;
        font-weight: 400;
        color: var(--text-muted-color);
    }
</style>
<div class="container locked-container">
    <div class="card">
        <?php // Display media above message if configured ?>
        <?php if (defined('GAME_LOCKED_IMAGE_POS') && GAME_LOCKED_IMAGE_POS === 'above') echo $media_html; ?>
        
        <h1><?php echo htmlspecialchars(GAME_LOCKED_TITLE); ?></h1>
        <p><?php echo nl2br(htmlspecialchars(GAME_LOCKED_MESSAGE)); ?></p>
        
        <?php // Display media below message if configured ?>
        <?php if (defined('GAME_LOCKED_IMAGE_POS') && GAME_LOCKED_IMAGE_POS === 'below') echo $media_html; ?>
        
        <div id="countdown-timer"></div>
    </div>
</div>

<script>
    const countdownElement = document.getElementById('countdown-timer');
    const targetDate = new Date("<?php echo GAME_LIVE_DATETIME; ?>").getTime();

    const timerInterval = setInterval(function() {
        const now = new Date().getTime();
        const distance = targetDate - now;

        if (distance < 0) {
            clearInterval(timerInterval);
            countdownElement.innerHTML = "<h2>The experience is now live! Refreshing...</h2>";
            setTimeout(() => window.location.reload(), 2000);
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        countdownElement.innerHTML = `
            <div class="timer-box">${days}<span>Days</span></div>
            <div class="timer-box">${hours}<span>Hours</span></div>
            <div class="timer-box">${minutes}<span>Mins</span></div>
            <div class="timer-box">${seconds}<span>Secs</span></div>
        `;
    }, 1000);
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>