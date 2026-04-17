<?php
function uploadProfileImage($file, $target_dir = "uploads/profiles/") {
    $response = ['success' => false, 'message' => '', 'filename' => ''];
    
    // Check if directory exists, if not create it
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Check if file was uploaded without errors
    if (isset($file) && $file['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB max
        
        // Check file type
        if (!in_array($file['type'], $allowed_types)) {
            $response['message'] = 'Only JPG, JPEG, PNG, and GIF files are allowed.';
            return $response;
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            $response['message'] = 'File size must be less than 5MB.';
            return $response;
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $target_file = $target_dir . $filename;
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $response['success'] = true;
            $response['message'] = 'File uploaded successfully.';
            $response['filename'] = $filename;
        } else {
            $response['message'] = 'Failed to upload file.';
        }
    } else {
        $response['message'] = 'No file uploaded or upload error occurred.';
    }
    
    return $response;
}

function deleteProfileImage($filename, $target_dir = "uploads/profiles/") {
    // Don't delete the default image
    if ($filename && $filename != 'default.jpg' && $filename != 'default-avatar.png' && file_exists($target_dir . $filename)) {
        unlink($target_dir . $filename);
    }
}

function getProfileImageUrl($filename, $default = 'default.jpg') {
    // If a custom filename exists and file exists, use it
    if ($filename && !empty($filename) && file_exists("uploads/profiles/" . $filename)) {
        return "uploads/profiles/" . $filename;
    }
    
    // Check for default.jpg
    if (file_exists("uploads/profiles/default.jpg")) {
        return "uploads/profiles/default.jpg";
    }
    
    // Fallback to default-avatar.png if exists
    if (file_exists("uploads/profiles/default-avatar.png")) {
        return "uploads/profiles/default-avatar.png";
    }
    
    // Ultimate fallback - return empty
    return "uploads/profiles/default.jpg";
}

// No need to create default avatar anymore since we have default.jpg
?>