<?php
require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

$error_message = '';
$success_message = '';

// Handle theme import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['theme_import_file'])) {
    if ($_FILES['theme_import_file']['error'] === UPLOAD_ERR_OK && is_uploaded_file($_FILES['theme_import_file']['tmp_name'])) {
        $json_content = file_get_contents($_FILES['theme_import_file']['tmp_name']);
        $theme_data = json_decode($json_content, true);

        if (json_last_error() === JSON_ERROR_NONE && !empty($theme_data['name']) && !empty($theme_data['settings_json'])) {
            $name = $theme_data['name'];
            $settings_json = json_encode($theme_data['settings_json']); // Re-encode to ensure valid JSON
            $is_admin_theme = isset($theme_data['is_admin_theme']) && $theme_data['is_admin_theme'] ? 1 : 0;

            $stmt = $mysqli->prepare("INSERT INTO themes (name, settings_json, is_admin_theme) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $settings_json, $is_admin_theme);
            if ($stmt->execute()) {
                $success_message = "Theme '{$name}' imported successfully!";
            } else {
                $error_message = "Database error: Could not import theme.";
            }
        } else {
            $error_message = "Invalid or corrupted theme file. Please check the file and try again.";
        }
    } else {
        $error_message = "File upload error. Please try again.";
    }
}


// Handle theme activation
if (isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    $is_admin = (int)($_GET['is_admin'] ?? 0);

    // Deactivate all themes of the same type first
    $mysqli->query("UPDATE themes SET is_active = 0 WHERE is_admin_theme = $is_admin");

    // Activate the selected theme
    $stmt = $mysqli->prepare("UPDATE themes SET is_active = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $success_message = "Theme activated successfully!";
}


// Fetch all themes
$public_themes = $mysqli->query("SELECT * FROM themes WHERE is_admin_theme = 0 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$admin_themes = $mysqli->query("SELECT * FROM themes WHERE is_admin_theme = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../templates/admin_header.php';
?>
<style>
.theme-library { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
.theme-card {
    border: 1px solid var(--admin-border); border-radius: 8px;
    background: var(--admin-bg); display: flex; flex-direction: column;
}
.theme-preview {
    height: 150px; border-top-left-radius: 8px; border-top-right-radius: 8px;
    padding: 1rem; color: white; display: flex; justify-content: center; align-items: center;
    text-align: center; font-size: 1.2rem; font-weight: bold;
}
.theme-info { padding: 1rem; flex-grow: 1; }
.theme-actions { padding: 1rem; border-top: 1px solid var(--admin-border); display: flex; gap: 0.5rem; flex-wrap: wrap; }
.theme-card.active { border-color: var(--admin-success); box-shadow: 0 0 10px var(--admin-success); }
</style>

<div class="container">
    <div style="display:flex; justify-content: space-between; align-items: center;">
        <h2>Theme Manager</h2>
    </div>
    
    <?php if ($success_message): ?><p class="success"><?php echo $success_message; ?></p><?php endif; ?>
    <?php if ($error_message): ?><p class="error"><?php echo $error_message; ?></p><?php endif; ?>

    <div style="border: 1px solid var(--admin-border); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <h3>Import & Create</h3>
         <form action="manage_themes.php" method="post" enctype="multipart/form-data" style="display:inline-block; margin-right: 1rem;">
            <input type="file" name="theme_import_file" id="theme_import_file" required>
            <button type="submit" class="btn">Import Theme</button>
        </form>
        <a href="edit_theme.php" class="btn">Create New Theme</a>
    </div>

    <h3>Public Site Themes</h3>
    <div class="theme-library">
        <?php foreach($public_themes as $theme): ?>
            <div class="theme-card <?php echo $theme['is_active'] ? 'active' : ''; ?>">
                <?php $settings = json_decode($theme['settings_json'], true); ?>
                <div class="theme-preview" style="background-color: <?php echo $settings['--bg-color'] ?? '#ffffff'; ?>; color: <?php echo $settings['--text-color'] ?? '#000000'; ?>">
                    <span style="background: <?php echo $settings['--primary-color'] ?? '#0000ff'; ?>; padding: 10px 20px; border-radius: 8px;">Aa</span>
                </div>
                <div class="theme-info">
                    <h4><?php echo htmlspecialchars($theme['name']); ?> <?php echo $theme['is_active'] ? '(Active)' : ''; ?></h4>
                </div>
                <div class="theme-actions">
                    <?php if(!$theme['is_active']): ?>
                        <a href="manage_themes.php?activate=<?php echo $theme['id']; ?>&is_admin=0" class="btn">Activate</a>
                    <?php endif; ?>
                    <a href="edit_theme.php?id=<?php echo $theme['id']; ?>" class="btn btn-secondary">Edit</a>
                    <a href="export_theme.php?id=<?php echo $theme['id']; ?>" class="btn btn-secondary">Export</a>
                    <a href="delete_theme.php?id=<?php echo $theme['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <hr style="border-color: var(--admin-border); margin: 3rem 0;">

    <h3>Admin Panel Themes</h3>
     <div class="theme-library">
        <?php foreach($admin_themes as $theme): ?>
            <div class="theme-card <?php echo $theme['is_active'] ? 'active' : ''; ?>">
                <?php $settings = json_decode($theme['settings_json'], true); ?>
                <div class="theme-preview" style="background-color: <?php echo $settings['--admin-bg'] ?? '#2c3e50'; ?>; color: <?php echo $settings['--admin-text'] ?? '#ecf0f1'; ?>">
                    <span style="background: <?php echo $settings['--admin-primary'] ?? '#8e44ad'; ?>; padding: 10px 20px; border-radius: 8px;">Aa</span>
                </div>
                <div class="theme-info">
                    <h4><?php echo htmlspecialchars($theme['name']); ?> <?php echo $theme['is_active'] ? '(Active)' : ''; ?></h4>
                </div>
                <div class="theme-actions">
                    <?php if(!$theme['is_active']): ?>
                        <a href="manage_themes.php?activate=<?php echo $theme['id']; ?>&is_admin=1" class="btn">Activate</a>
                    <?php endif; ?>
                    <a href="edit_theme.php?id=<?php echo $theme['id']; ?>" class="btn btn-secondary">Edit</a>
                    <a href="export_theme.php?id=<?php echo $theme['id']; ?>" class="btn btn-secondary">Export</a>
                    <a href="delete_theme.php?id=<?php echo $theme['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/../templates/admin_footer.php'; ?>