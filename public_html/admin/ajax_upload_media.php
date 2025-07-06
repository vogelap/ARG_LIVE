<?php
// File: arg_game/admin/ajax_upload_media.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => 'An unknown error occurred.'];

$upload_dir = __DIR__ . '/../public/uploads/';
$upload_url = SITE_URL . '/public/uploads/';

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $response['error'] = 'Failed to create uploads directory.';
        echo json_encode($response);
        exit;
    }
}

if (isset($_FILES['media_file'])) {
    if ($_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid() . '-' . basename($_FILES['media_file']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['media_file']['tmp_name'], $target_path)) {
            $file_path = $upload_url . $file_name;
            $alt_text = pathinfo($file_name, PATHINFO_FILENAME);

            $stmt = $mysqli->prepare("INSERT INTO media_library (file_name, file_path, alt_text) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $file_name, $file_path, $alt_text);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['url'] = $file_path;
                
                // Auto-detect media type
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $media_type = 'link'; // Default
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) $media_type = 'image';
                // *** ADDED .mov to the list of recognized video extensions ***
                if (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'])) $media_type = 'video';
                if (in_array($ext, ['mp3', 'wav'])) $media_type = 'audio';
                
                $response['type'] = $media_type;
                unset($response['error']);
            } else {
                $response['error'] = 'Failed to save file info to database.';
                unlink($target_path);
            }
        } else {
            $response['error'] = 'Failed to move uploaded file.';
        }
    } else {
        $response['error'] = 'File upload error. Code: ' . $_FILES['media_file']['error'];
    }
} else {
    $response['error'] = 'No file was uploaded.';
}

echo json_encode($response);
exit;