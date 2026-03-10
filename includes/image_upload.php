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
    if ($filename && file_exists($target_dir . $filename)) {
        unlink($target_dir . $filename);
    }
}

function getProfileImageUrl($filename, $default = 'default-avatar.png') {
    if ($filename && file_exists("uploads/profiles/" . $filename)) {
        return "uploads/profiles/" . $filename;
    }
    return "uploads/profiles/" . $default;
}

// Create default avatar if it doesn't exist
function createDefaultAvatar($target_dir = "uploads/profiles/") {
    $default_file = $target_dir . 'default-avatar.png';
    
    if (!file_exists($default_file)) {
        // Create a simple default avatar using GD
        if (extension_loaded('gd')) {
            $image = imagecreate(200, 200);
            $bg = imagecolorallocate($image, 102, 126, 234); // #667eea
            $text_color = imagecolorallocate($image, 255, 255, 255);
            
            // Draw a user icon or just a letter
            imagestring($image, 5, 80, 90, 'User', $text_color);
            
            // Save the image
            imagepng($image, $default_file);
            imagedestroy($image);
        }
    }
}

// Call this function to ensure default avatar exists
createDefaultAvatar();
?>
