<?php
// File: arg_game/admin/select_media.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

// Fetch all media items to display in the grid
$media_items = $mysqli->query("SELECT * FROM media_library ORDER BY uploaded_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select or Upload Media</title>
    <link rel="stylesheet" href="assets/css/admin_style.css">
    <style>
        body { background: var(--admin-bg); color: var(--admin-text); padding: 1rem; font-family: sans-serif; }
        .upload-section {
            background-color: var(--admin-surface);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid var(--admin-border);
        }
        .media-library-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        .media-item {
            border: 2px solid var(--admin-border);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .media-item:hover {
            border-color: var(--admin-primary);
            background: var(--admin-surface);
        }
        .media-item img, .media-item video, .media-item .file-icon {
            max-width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            color: var(--admin-text-muted);
        }
        .media-item .file-icon {
            font-size: 5em;
            line-height: 100px;
        }
        .media-item-name {
            font-size: 0.8em;
            word-wrap: break-word;
            margin-top: 0.5rem;
        }
        #upload-status {
            margin-top: 1rem;
            font-weight: bold;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

    <div class="upload-section">
        <h3>Upload New Media</h3>
        <form id="upload-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="media_file">Select File</label>
                <input type="file" name="media_file" id="media_file" class="form-control" required>
            </div>
            <button type="submit" class="btn">Upload File</button>
            <div id="upload-status"></div>
        </form>
    </div>

    <h3>Or Select Existing Media</h3>
    <div class="media-library-grid">
        <?php if (empty($media_items)): ?>
            <p>No media found. Upload a file above to get started.</p>
        <?php else: ?>
            <?php foreach ($media_items as $item): ?>
                <?php
                    $ext = strtolower(pathinfo($item['file_name'], PATHINFO_EXTENSION));
                    $media_type = ''; // Default to no type
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) $media_type = 'image';
                    elseif (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'])) $media_type = 'video';
                    elseif (in_array($ext, ['mp3', 'wav'])) $media_type = 'audio';
                ?>
                <div class="media-item" onclick="selectMedia('<?php echo htmlspecialchars($item['file_path']); ?>', '<?php echo $media_type; ?>')">
                    <?php if ($media_type === 'image'): ?>
                        <img src="<?php echo htmlspecialchars($item['file_path']); ?>" alt="<?php echo htmlspecialchars($item['alt_text']); ?>" loading="lazy">
                    <?php elseif ($media_type === 'video'): ?>
                        <i class="fas fa-film file-icon"></i>
                    <?php elseif ($media_type === 'audio'): ?>
                        <i class="fas fa-music file-icon"></i>
                    <?php else: ?>
                        <i class="fas fa-file file-icon"></i>
                    <?php endif; ?>
                    <p class="media-item-name" title="<?php echo htmlspecialchars($item['file_name']); ?>"><?php echo htmlspecialchars(strlen($item['file_name']) > 20 ? substr($item['file_name'], 0, 20).'...' : $item['file_name']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<script>
    function selectMedia(url, type) {
        if (window.parent && typeof window.parent.setMediaUrlAndType === 'function') {
            window.parent.setMediaUrlAndType(url, type);
        } else {
            alert('Error: Could not communicate with the parent page.');
        }
    }

    document.getElementById('upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const fileInput = document.getElementById('media_file');
        const statusDiv = document.getElementById('upload-status');
        const formData = new FormData(form);

        if (fileInput.files.length === 0) {
            statusDiv.textContent = 'Please select a file to upload.';
            statusDiv.style.color = 'orange';
            return;
        }

        statusDiv.textContent = 'Uploading...';
        statusDiv.style.color = 'inherit';

        fetch('ajax_upload_media.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusDiv.textContent = 'Upload successful! Reloading library...';
                statusDiv.style.color = 'var(--admin-success)';
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                statusDiv.textContent = 'Upload failed: ' + (data.error || 'Unknown error');
                statusDiv.style.color = 'var(--admin-danger)';
            }
        })
        .catch(error => {
            statusDiv.textContent = 'An error occurred during upload.';
            statusDiv.style.color = 'var(--admin-danger)';
            console.error('Error:', error);
        });
    });
</script>
</body>
</html>