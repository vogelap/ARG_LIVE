<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php'; // For usage checking

$error_message = '';
$success_message = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    $upload_dir = __DIR__ . '/../public/uploads/';
    $upload_url = SITE_URL . '/public/uploads/';

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $error_message = 'Failed to create uploads directory.';
        }
    }

    if (empty($error_message) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid() . '-' . basename($_FILES['media_file']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['media_file']['tmp_name'], $target_path)) {
            $file_path = $upload_url . $file_name;
            $alt_text = pathinfo($file_name, PATHINFO_FILENAME);

            $stmt = $mysqli->prepare("INSERT INTO media_library (file_name, file_path, alt_text) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $file_name, $file_path, $alt_text);

            if ($stmt->execute()) {
                $success_message = "File uploaded successfully!";
            } else {
                $error_message = 'Failed to save file info to database.';
                unlink($target_path);
            }
        } else {
            $error_message = 'Failed to move uploaded file.';
        }
    } elseif ($_FILES['media_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error_message = 'File upload error. Code: ' . $_FILES['media_file']['error'];
    }
}


// Fetch all media
$media_items_result = $mysqli->query("SELECT * FROM media_library ORDER BY uploaded_at DESC");
$media_items = $media_items_result ? $media_items_result->fetch_all(MYSQLI_ASSOC) : [];

$puzzle_manager = new Puzzle($mysqli);
$page_title = 'Manage Media Library';
include __DIR__ . '/../templates/admin_header.php';
?>
<style>
.media-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.media-filter-group {
    display: flex;
    gap: 1rem;
}
.media-library-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.5rem;
}
.media-item {
    background: var(--admin-bg);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: all 0.2s ease-in-out;
}
.media-item.is-missing {
    border-color: var(--admin-danger);
}
.media-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}
.media-item-preview {
    height: 150px;
    background-color: var(--admin-surface); /* Fallback for broken images */
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    position: relative;
}
.media-item-preview img, .media-item-preview video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.media-item-preview .file-icon {
    font-size: 5rem;
    color: var(--admin-text-muted);
}
.missing-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: var(--admin-danger);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}
.media-item-info {
    padding: 1rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.media-item-name {
    font-weight: 600;
    word-wrap: break-word;
    margin-bottom: 0.5rem;
}
.media-item-details {
    font-size: 0.8em;
    color: var(--admin-text-muted);
    margin-bottom: 1rem;
}
.media-item-actions {
    display: flex;
    gap: 0.5rem;
    border-top: 1px solid var(--admin-border);
    padding-top: 1rem;
    margin-top: auto;
}
.media-item-actions .btn {
    flex-grow: 1;
    padding: 8px 10px;
}
.copy-feedback {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background-color: var(--admin-success);
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.5s;
    pointer-events: none;
}
.usage-report {
    background-color: var(--admin-danger-bg);
    border-top: 1px solid var(--admin-danger);
    padding: 10px;
    font-size: 0.8em;
    text-align: left;
}
.usage-report ul {
    padding-left: 15px;
    margin: 5px 0 0 0;
}
.usage-report a { color: var(--admin-danger-text); }
</style>

<div class="container">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Media Library</h2>
        <button class="btn" onclick="document.getElementById('upload-section').style.display='block'">Upload New Media</button>
    </div>
    
    <?php if ($success_message): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>
    <?php if ($error_message): ?><p class="error"><?php echo $error_message; ?></p><?php endif; ?>

    <div id="upload-section" style="display:none; border: 1px solid var(--admin-border); padding: 1.5rem; border-radius: 8px; margin-top: 1rem; margin-bottom: 2rem;">
        <h3>Upload New Media</h3>
        <form action="manage_media.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="media_file">Select File</label>
                <input type="file" name="media_file" id="media_file" required>
            </div>
            <button type="submit" class="btn">Upload</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('upload-section').style.display='none'">Cancel</button>
        </form>
    </div>

    <div class="media-controls">
        <div class="media-filter-group">
            <input type="text" id="media-search" placeholder="Search by name..." class="form-control" style="width: 250px;">
            <select id="media-filter" class="form-control">
                <option value="all">All Types</option>
                <option value="image">Image</option>
                <option value="video">Video</option>
                <option value="audio">Audio</option>
                <option value="other">Other</option>
            </select>
        </div>
    </div>
    
    <?php if (empty($media_items)): ?>
        <p>No media has been uploaded yet.</p>
    <?php else: ?>
        <div class="media-library-grid" id="media-grid">
            <?php foreach ($media_items as $item): ?>
                <?php
                    // RE-INTEGRATED: Check if the physical file is missing from the server.
                    $local_path = str_replace(SITE_URL, rtrim(ROOT_PATH, '/'), $item['file_path']);
                    $is_missing = !file_exists($local_path);
                    $file_size = !$is_missing ? filesize($local_path) : 0;
                    
                    // RE-INTEGRATED: If missing, check which puzzles are using this media URL.
                    $mismatched_puzzles = $is_missing ? $puzzle_manager->getMismatchedPuzzles($item['file_path']) : [];

                    $ext = strtolower(pathinfo($item['file_name'], PATHINFO_EXTENSION));
                    $media_type = 'other';
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) $media_type = 'image';
                    elseif (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'])) $media_type = 'video';
                    elseif (in_array($ext, ['mp3', 'wav', 'm4a'])) $media_type = 'audio';
                ?>
                <div class="media-item <?php if ($is_missing) echo 'is-missing'; ?>" data-name="<?php echo htmlspecialchars(strtolower($item['file_name'])); ?>" data-type="<?php echo $media_type; ?>">
                    <div class="media-item-preview">
                        <?php if ($is_missing): ?>
                            <i class="fas fa-exclamation-triangle file-icon"></i>
                            <span class="missing-badge">Missing</span>
                        <?php else: ?>
                             <?php if ($media_type === 'image'): ?>
                                <img src="<?php echo htmlspecialchars($item['file_path']); ?>" alt="<?php echo htmlspecialchars($item['alt_text']); ?>" loading="lazy">
                            <?php elseif ($media_type === 'video'): ?>
                                <video muted loop onmouseover="this.play()" onmouseout="this.pause();this.currentTime=0;"><source src="<?php echo htmlspecialchars($item['file_path']); ?>" type="video/<?php echo $ext; ?>"></video>
                            <?php elseif ($media_type === 'audio'): ?>
                                <i class="fas fa-music file-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-file file-icon"></i>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="media-item-info">
                        <div>
                            <p class="media-item-name" title="<?php echo htmlspecialchars($item['file_name']); ?>"><?php echo htmlspecialchars(strlen($item['file_name']) > 25 ? substr($item['file_name'], 0, 25).'...' : $item['file_name']); ?></p>
                            <p class="media-item-details">
                                <?php echo date('M j, Y', strtotime($item['uploaded_at'])); ?> &bull; <?php echo $is_missing ? 'N/A' : round($file_size / 1024, 2) . ' KB'; ?>
                            </p>
                        </div>
                        <div class="media-item-actions">
                            <button class="btn btn-secondary copy-url-btn" data-url="<?php echo htmlspecialchars($item['file_path']); ?>">Copy URL</button>
                            <a href="delete_media.php?id=<?php echo $item['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure? This will delete the database record. If the file still exists, you must remove it manually.');">Delete</a>
                        </div>
                    </div>
                     <?php if (!empty($mismatched_puzzles)): ?>
                        <div class="usage-report">
                            <strong>This missing file is used in:</strong>
                            <ul>
                                <?php foreach ($mismatched_puzzles as $puzzle): ?>
                                    <li><a href="edit_puzzle.php?id=<?php echo $puzzle['id']; ?>"><?php echo htmlspecialchars($puzzle['title']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<div id="copy-feedback" class="copy-feedback">URL Copied!</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('media-search');
    const filterSelect = document.getElementById('media-filter');
    const mediaGrid = document.getElementById('media-grid');
    if (!mediaGrid) return; // Exit if no grid exists

    const mediaItems = mediaGrid.querySelectorAll('.media-item');
    const copyFeedback = document.getElementById('copy-feedback');

    function filterAndSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterType = filterSelect.value;

        mediaItems.forEach(item => {
            const name = item.dataset.name;
            const type = item.dataset.type;

            const nameMatch = name.includes(searchTerm);
            const typeMatch = filterType === 'all' || type === filterType;

            if (nameMatch && typeMatch) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterAndSearch);
    filterSelect.addEventListener('change', filterAndSearch);

    mediaGrid.addEventListener('click', function(e) {
        let target = e.target;
        // Handle clicks on elements inside the button
        if (!target.classList.contains('copy-url-btn')) {
            target = target.closest('.copy-url-btn');
        }
        
        if (target) {
            const url = target.dataset.url;
            navigator.clipboard.writeText(url).then(() => {
                copyFeedback.style.opacity = 1;
                setTimeout(() => {
                    copyFeedback.style.opacity = 0;
                }, 1500);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
                alert('Failed to copy URL.');
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>