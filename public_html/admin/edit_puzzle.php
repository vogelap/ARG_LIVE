<?php
// File: arg_game/admin/edit_puzzle.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/classes/Puzzle.php';

$puzzle_manager = new Puzzle($mysqli);

// Default structure with ALL keys to prevent "undefined array key" warnings
$puzzle = [
    'id' => '', 'title' => '', 'description' => '', 'story_text' => '', 'puzzle_type' => 'text',
    'puzzle_data' => '[]', 'solution' => '', 'solution_hint' => '', 'success_media_url' => '', 'failure_media_url' => '', 
    'prerequisites_enabled' => 1, 'media_url' => '', 'media_type' => null, 'media_pos' => 'above', 'link_url' => '',
    'release_time' => '', 'is_visible' => 1, 'display_order' => 0
];
$prerequisites = [];
$hints = [];
$page_title = 'Add New Puzzle';
$is_edit = false;

if (isset($_GET['id'])) {
    $is_edit = true;
    $puzzle_id = (int)$_GET['id'];
    $puzzle_data = $puzzle_manager->find($puzzle_id);
    if ($puzzle_data) {
        $puzzle = array_merge($puzzle, $puzzle_data); // Merge to ensure all keys exist
        $hints = $puzzle_manager->getHints($puzzle_id);
        $page_title = 'Edit Puzzle';
        $stmt_prereq = $mysqli->prepare("SELECT prerequisite_puzzle_id FROM puzzle_prerequisites WHERE puzzle_id = ?");
        $stmt_prereq->bind_param("i", $puzzle_id);
        $stmt_prereq->execute();
        $result = $stmt_prereq->get_result();
        while($row = $result->fetch_assoc()) {
            $prerequisites[] = $row['prerequisite_puzzle_id'];
        }
    } else {
        header("Location: manage_puzzles.php?error=Puzzle_not_found");
        exit;
    }
} else {
    $puzzle['display_order'] = $puzzle_manager->getNextDisplayOrder();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and process form data
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $story_text = trim($_POST['story_text']);
    $puzzle_type = $_POST['puzzle_type'];
    $solution = trim($_POST['solution']);
    $solution_hint = trim($_POST['solution_hint']);
    $success_media_url = trim($_POST['success_media_url']);
    $failure_media_url = trim($_POST['failure_media_url']);
    $prerequisites_enabled = isset($_POST['prerequisites_enabled']) ? 1 : 0;
    $media_url = trim($_POST['media_url']);
    $media_type = empty($media_url) ? null : $_POST['media_type'];
    $media_pos = $_POST['media_pos'];
    $link_url = trim($_POST['link_url']);
    $release_time = !empty($_POST['release_time']) ? date('Y-m-d H:i:s', strtotime($_POST['release_time'])) : null;
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    $selected_prereqs = isset($_POST['prerequisites']) ? $_POST['prerequisites'] : [];
    $posted_hints = isset($_POST['hints']) ? array_filter(array_map('trim', $_POST['hints'])) : [];

    $puzzle_data_json = '[]';
    if ($puzzle_type === 'multiple_choice' && isset($_POST['puzzle_data_mc'])) {
        $mc_options = array_values(array_filter(array_map('trim', $_POST['puzzle_data_mc'])));
        $puzzle_data_json = json_encode($mc_options);
    } elseif ($puzzle_type === 'location_gps') {
        $gps_data = ['latitude' => (float)($_POST['puzzle_data_gps_lat'] ?? 0), 'longitude' => (float)($_POST['puzzle_data_gps_lon'] ?? 0), 'radius' => (int)($_POST['puzzle_data_gps_radius'] ?? 50)];
        $puzzle_data_json = json_encode($gps_data);
    }

    if ($is_edit) {
        $stmt = $mysqli->prepare("UPDATE puzzles SET title=?, description=?, story_text=?, puzzle_type=?, puzzle_data=?, solution=?, solution_hint=?, success_media_url=?, failure_media_url=?, prerequisites_enabled=?, media_url=?, media_type=?, media_pos=?, link_url=?, release_time=?, is_visible=?, display_order=? WHERE id=?");
        $stmt->bind_param("sssssssssisssssiii", $title, $description, $story_text, $puzzle_type, $puzzle_data_json, $solution, $solution_hint, $success_media_url, $failure_media_url, $prerequisites_enabled, $media_url, $media_type, $media_pos, $link_url, $release_time, $is_visible, $display_order, $id);
    } else {
        $stmt = $mysqli->prepare("INSERT INTO puzzles (title, description, story_text, puzzle_type, puzzle_data, solution, solution_hint, success_media_url, failure_media_url, prerequisites_enabled, media_url, media_type, media_pos, link_url, release_time, is_visible, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssisssssii", $title, $description, $story_text, $puzzle_type, $puzzle_data_json, $solution, $solution_hint, $success_media_url, $failure_media_url, $prerequisites_enabled, $media_url, $media_type, $media_pos, $link_url, $release_time, $is_visible, $display_order);
    }
    
    $stmt->execute();
    $current_puzzle_id = $is_edit ? $id : $mysqli->insert_id;
    $puzzle_manager->updateHints($current_puzzle_id, $posted_hints);
    $mysqli->query("DELETE FROM puzzle_prerequisites WHERE puzzle_id = $current_puzzle_id");
    if ($prerequisites_enabled && !empty($selected_prereqs)) {
        $stmt_prereq_insert = $mysqli->prepare("INSERT INTO puzzle_prerequisites (puzzle_id, prerequisite_puzzle_id) VALUES (?, ?)");
        foreach ($selected_prereqs as $prereq_id) {
            $pid = (int)$prereq_id;
            $stmt_prereq_insert->bind_param("ii", $current_puzzle_id, $pid);
            $stmt_prereq_insert->execute();
        }
    }
    header("Location: manage_puzzles.php?success=Puzzle saved successfully!");
    exit;
}

$all_puzzles = $puzzle_manager->getAll();
$decoded_puzzle_data = json_decode($puzzle['puzzle_data'] ?: '[]', true);
include __DIR__ . '/../templates/admin_header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <h2><?php echo $page_title; ?></h2>
        <?php if ($is_edit): ?>
            <a href="<?php echo SITE_URL . '/public/puzzle.php?id=' . htmlspecialchars($puzzle['id']); ?>" target="_blank" class="btn btn-secondary">View Live Puzzle</a>
        <?php endif; ?>
    </div>

    <form action="edit_puzzle.php<?php echo $is_edit ? '?id='.htmlspecialchars($puzzle['id']) : ''; ?>" method="post">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($puzzle['id']); ?>">

        <fieldset><legend>Core Details</legend>
            <div class="form-group"><label for="title">Title</label><input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($puzzle['title']); ?>" required></div>
            <div class="form-group"><label for="description">Puzzle Description / Instructions</label><textarea name="description" id="description" rows="5" class="form-control"><?php echo htmlspecialchars($puzzle['description']); ?></textarea></div>
            <div class="form-group"><label for="story_text">Story Log Text (Optional)</label><textarea name="story_text" id="story_text" rows="3" class="form-control"><?php echo htmlspecialchars($puzzle['story_text']); ?></textarea></div>
        </fieldset>

        <fieldset><legend>Puzzle Logic</legend>
            <div class="form-group"><label for="puzzle_type">Puzzle Type</label><select name="puzzle_type" id="puzzle_type" class="form-control"><option value="text" <?php echo $puzzle['puzzle_type'] == 'text' ? 'selected' : ''; ?>>Simple Text</option><option value="multiple_choice" <?php echo $puzzle['puzzle_type'] == 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option><option value="location_gps" <?php echo $puzzle['puzzle_type'] == 'location_gps' ? 'selected' : ''; ?>>Location (GPS)</option><option value="location_qr" <?php echo $puzzle['puzzle_type'] == 'location_qr' ? 'selected' : ''; ?>>Location (QR Code)</option></select></div>
            <div id="puzzle-data-multiple_choice" class="puzzle-data-fields" style="display:none;"><div id="mc-options-container"><?php if ($puzzle['puzzle_type'] === 'multiple_choice' && is_array($decoded_puzzle_data)): foreach ($decoded_puzzle_data as $option): ?><div class="dynamic-row"><input type="text" name="puzzle_data_mc[]" placeholder="Choice Text" value="<?php echo htmlspecialchars($option); ?>" class="form-control"><button type="button" class="btn btn-danger btn-remove">Remove</button></div><?php endforeach; endif; ?></div><button type="button" id="btn-add-mc" class="btn btn-secondary">Add Choice</button></div>
            <div id="puzzle-data-location_gps" class="puzzle-data-fields" style="display:none;"><div class="form-group"><label>Latitude</label><input type="number" step="any" name="puzzle_data_gps_lat" class="form-control" value="<?php echo $decoded_puzzle_data['latitude'] ?? ''; ?>"></div><div class="form-group"><label>Longitude</label><input type="number" step="any" name="puzzle_data_gps_lon" class="form-control" value="<?php echo $decoded_puzzle_data['longitude'] ?? ''; ?>"></div><div class="form-group"><label>Radius (meters)</label><input type="number" step="1" name="puzzle_data_gps_radius" class="form-control" value="<?php echo $decoded_puzzle_data['radius'] ?? 50; ?>"></div></div>
            <div class="form-group"><label for="solution">Solution (Case-insensitive)</label><input type="text" name="solution" id="solution" class="form-control" value="<?php echo htmlspecialchars($puzzle['solution']); ?>" required></div>
        </fieldset>
        
        <fieldset><legend>Feedback Media</legend>
            <div class="form-group">
                <label for="success_media_url">Success Media URL (Optional)</label>
                <div class="input-group">
                    <input type="text" name="success_media_url" id="success_media_url" class="form-control" value="<?php echo htmlspecialchars($puzzle['success_media_url']); ?>" placeholder="URL for audio/video on correct answer">
                    <button type="button" class="btn btn-secondary select-media-btn" data-target="success_media_url">Library</button>
                </div>
            </div>
            <div class="form-group">
                <label for="failure_media_url">Failure Media URL (Optional)</label>
                <div class="input-group">
                    <input type="text" name="failure_media_url" id="failure_media_url" class="form-control" value="<?php echo htmlspecialchars($puzzle['failure_media_url']); ?>" placeholder="URL for audio/video on incorrect answer">
                    <button type="button" class="btn btn-secondary select-media-btn" data-target="failure_media_url">Library</button>
                </div>
            </div>
        </fieldset>

        <fieldset><legend>Hints</legend>
            <div id="hints-container"><label>Regular Hints</label><?php if (!empty($hints)): foreach ($hints as $hint): ?><div class="dynamic-row"><input type="text" name="hints[]" placeholder="Hint Text" value="<?php echo htmlspecialchars($hint['hint_text']); ?>" class="form-control"><button type="button" class="btn btn-danger btn-remove">Remove</button></div><?php endforeach; else: ?><div class="dynamic-row"><input type="text" name="hints[]" placeholder="Hint Text" class="form-control"><button type="button" class="btn btn-danger btn-remove">Remove</button></div><?php endif; ?></div><button type="button" id="btn-add-hint" class="btn btn-secondary">Add Hint</button><hr><div class="form-group"><label for="solution_hint">Final Solution Hint (Optional)</label><textarea name="solution_hint" id="solution_hint" rows="2" class="form-control"><?php echo htmlspecialchars($puzzle['solution_hint']); ?></textarea></div>
        </fieldset>

        <fieldset><legend>Media & Links</legend>
            <div class="form-group"><label for="media_url">Media URL</label><div class="input-group"><input type="text" name="media_url" id="media_url" class="form-control" value="<?php echo htmlspecialchars($puzzle['media_url']); ?>"><button type="button" class="btn btn-secondary select-media-btn" data-target="media_url">Library</button></div></div>
            <div class="form-group"><label for="media_file_upload">Or Upload New Media</label><div class="input-group"><input type="file" id="media_file_upload" class="form-control"><button type="button" class="btn btn-secondary" id="upload-media-btn">Upload</button></div><div id="upload-status" style="margin-top: 10px;"></div></div>
            <div id="media-preview-container"><div id="preview-wrapper"></div><div id="preview-message" style="color: #888;"></div></div>
            <div class="form-group"><label for="media_type">Media Type</label><select name="media_type" id="media_type" class="form-control"><option value="">None</option><option value="image" <?php echo $puzzle['media_type'] == 'image' ? 'selected' : ''; ?>>Image</option><option value="video" <?php echo $puzzle['media_type'] == 'video' ? 'selected' : ''; ?>>Video</option><option value="audio" <?php echo $puzzle['media_type'] == 'audio' ? 'selected' : ''; ?>>Audio</option></select></div>
            <div class="form-group"><label for="media_pos">Media Position</label><select name="media_pos" id="media_pos" class="form-control"><option value="above" <?php echo ($puzzle['media_pos'] ?? 'above') == 'above' ? 'selected' : ''; ?>>Above Description</option><option value="below" <?php echo ($puzzle['media_pos'] ?? 'above') == 'below' ? 'selected' : ''; ?>>Below Description</option></select></div>
            <div class="form-group"><label for="link_url">Associated Link URL (Optional)</label><input type="text" name="link_url" id="link_url" class="form-control" value="<?php echo htmlspecialchars($puzzle['link_url']); ?>"></div>
        </fieldset>
        
        <fieldset><legend>Visibility, Order & Prerequisites</legend>
            <div class="form-group"><label><input type="checkbox" name="is_visible" value="1" <?php echo $puzzle['is_visible'] ? 'checked' : ''; ?>> Visible to Players</label></div>
            <div class="form-group"><label for="release_time">Release Time (Optional)</label><input type="datetime-local" name="release_time" id="release_time" class="form-control" value="<?php echo !empty($puzzle['release_time']) ? date('Y-m-d\TH:i', strtotime($puzzle['release_time'])) : ''; ?>"></div>
            <div class="form-group"><label for="display_order">Display Order</label><input type="number" name="display_order" id="display_order" class="form-control" value="<?php echo $puzzle['display_order']; ?>"></div>
            <div class="form-group"><label><input type="checkbox" name="prerequisites_enabled" id="prerequisites_enabled" value="1" <?php echo $puzzle['prerequisites_enabled'] ? 'checked' : ''; ?>> Enable Prerequisites</label></div>
            <div class="form-group"><label for="prerequisites">Puzzles that must be solved first</label><select name="prerequisites[]" id="prerequisites" class="form-control" multiple style="height: 150px;"><?php foreach ($all_puzzles as $p): if (!$is_edit || $p['id'] != $puzzle['id']): ?><option value="<?php echo $p['id']; ?>" <?php echo in_array($p['id'], $prerequisites) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['title']); ?></option><?php endif; endforeach; ?></select></div>
        </fieldset>

        <button type="submit" class="btn btn-primary">Save Puzzle</button>
    </form>
</div>

<div id="media-modal-overlay">
    <div id="media-modal-content">
        <button type="button" onclick="closeMediaModal()" class="modal-close-btn">&times;</button>
        <iframe id="media-modal-iframe" src="about:blank"></iframe>
    </div>
</div>

<style>
    fieldset { border: 1px solid var(--admin-border); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
    legend { padding: 0 0.5rem; font-weight: bold; color: var(--admin-primary); }
    .dynamic-row, .input-group { display: flex; align-items: center; margin-bottom: 10px; }
    .dynamic-row input, .input-group input { flex-grow: 1; }
    .dynamic-row .btn-remove, .input-group .btn { margin-left: 10px; flex-shrink: 0; }
    #media-preview-container { text-align: center; border: 1px dashed var(--admin-border); padding: 1rem; margin-top: 1.5rem; border-radius: 8px; min-height: 100px; display: flex; justify-content: center; align-items: center; }
    #preview-wrapper img, #preview-wrapper video, #preview-wrapper audio { max-width: 100%; max-height: 250px; }
    #media-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1050; justify-content: center; align-items: center; }
    #media-modal-content { background: var(--admin-surface); padding: 20px; border-radius: 8px; width: 90%; max-width: 1000px; height: 80%; box-shadow: 0 5px 15px rgba(0,0,0,0.5); display: flex; flex-direction: column; position: relative; }
    #media-modal-iframe { width: 100%; height: 100%; border: none; }
    .modal-close-btn { position: absolute; top: -10px; right: -10px; background: red; color: white; border-radius: 50%; width: 30px; height: 30px; border: none; font-size: 1.5rem; line-height: 1; cursor: pointer; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentMediaTarget = null;
    document.addEventListener('click', e => {
        if (e.target && e.target.matches('button.btn-remove')) { e.target.closest('.dynamic-row').remove(); }
        if (e.target && e.target.matches('.select-media-btn')) {
            currentMediaTarget = e.target.dataset.target;
            openMediaModal();
        }
    });
    document.getElementById('btn-add-hint').addEventListener('click', () => {
        const container = document.getElementById('hints-container');
        const newRow = document.createElement('div');
        newRow.className = 'dynamic-row';
        newRow.innerHTML = `<input type="text" name="hints[]" placeholder="Hint Text" class="form-control"><button type="button" class="btn btn-danger btn-remove">Remove</button>`;
        container.appendChild(newRow);
    });
    document.getElementById('btn-add-mc').addEventListener('click', () => {
        const container = document.getElementById('mc-options-container');
        const newRow = document.createElement('div');
        newRow.className = 'dynamic-row';
        newRow.innerHTML = `<input type="text" name="puzzle_data_mc[]" placeholder="Choice Text" class="form-control"><button type="button" class="btn btn-danger btn-remove">Remove</button>`;
        container.appendChild(newRow);
    });
    const puzzleTypeSelect = document.getElementById('puzzle_type');
    const allDataFields = document.querySelectorAll('.puzzle-data-fields');
    const togglePuzzleFields = () => {
        allDataFields.forEach(f => f.style.display = 'none');
        const selectedField = document.getElementById('puzzle-data-' + puzzleTypeSelect.value);
        if (selectedField) selectedField.style.display = 'block';
    };
    puzzleTypeSelect.addEventListener('change', togglePuzzleFields);
    togglePuzzleFields();
    const prereqToggle = document.getElementById('prerequisites_enabled');
    const prereqSelect = document.getElementById('prerequisites');
    const togglePrereqState = () => { prereqSelect.disabled = !prereqToggle.checked; };
    prereqToggle.addEventListener('change', togglePrereqState);
    togglePrereqState();
    const mediaUrlInput = document.getElementById('media_url');
    const mediaTypeSelect = document.getElementById('media_type');
    const previewWrapper = document.getElementById('preview-wrapper');
    const previewMessage = document.getElementById('preview-message');
    const updatePreview = () => {
        const url = mediaUrlInput.value.trim();
        const type = mediaTypeSelect.value;
        previewWrapper.innerHTML = '';
        previewMessage.textContent = '';
        if (!url) { previewMessage.textContent = 'No media selected.'; return; }
        let element;
        if (type === 'image') { element = document.createElement('img'); }
        else if (type === 'video') { element = document.createElement('video'); element.controls = true; }
        else if (type === 'audio') { element = document.createElement('audio'); element.controls = true; }
        else { previewMessage.textContent = 'Select a media type for preview.'; return; }
        element.addEventListener('error', () => previewMessage.textContent = 'Could not load preview.');
        element.src = url;
        previewWrapper.appendChild(element);
    };
    mediaUrlInput.addEventListener('input', updatePreview);
    mediaTypeSelect.addEventListener('change', updatePreview);
    updatePreview();
    window.openMediaModal = () => { document.getElementById('media-modal-iframe').src = 'select_media.php'; document.getElementById('media-modal-overlay').style.display = 'flex'; };
    window.closeMediaModal = () => document.getElementById('media-modal-overlay').style.display = 'none';
    window.setMediaUrlAndType = (url, type) => {
        if (currentMediaTarget) {
            const targetInput = document.getElementById(currentMediaTarget);
            if (targetInput) {
                targetInput.value = url;
                if (currentMediaTarget === 'media_url') {
                    mediaTypeSelect.value = type;
                    updatePreview();
                }
            }
        }
        closeMediaModal();
    };
    
    document.getElementById('upload-media-btn').addEventListener('click', () => {
        const fileInput = document.getElementById('media_file_upload');
        const uploadStatus = document.getElementById('upload-status');
        if (fileInput.files.length === 0) { uploadStatus.textContent = 'Please select a file first.'; return; }
        const formData = new FormData();
        formData.append('media_file', fileInput.files[0]);
        uploadStatus.textContent = 'Uploading...';
        fetch('ajax_upload_media.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    uploadStatus.textContent = 'Upload successful!';
                    currentMediaTarget = 'media_url'; // Assume upload is for main media
                    setMediaUrlAndType(data.url, data.type);
                } else {
                    uploadStatus.textContent = 'Upload failed: ' + data.error;
                }
            }).catch(() => uploadStatus.textContent = 'An error occurred during upload.');
    });
});
</script>

<?php
include __DIR__ . '/../templates/admin_footer.php';
?>