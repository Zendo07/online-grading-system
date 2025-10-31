<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

// Require student access
requireStudent();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'student/profile.php');
    exit();
}

$full_name = sanitize($_POST['full_name']);
$user_id = $_SESSION['user_id'];

// Validate input
if (empty($full_name)) {
    redirectWithMessage(BASE_URL . 'student/profile.php', 'danger', 'Full name is required.');
}

try {
    // Handle profile picture upload
    $profile_picture_path = null;
    $update_picture = false;
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($file_type, $allowed_types)) {
            redirectWithMessage(BASE_URL . 'student/profile.php', 'danger', 'Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            redirectWithMessage(BASE_URL . 'student/profile.php', 'danger', 'File size must be less than 5MB.');
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../../uploads/profiles/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                redirectWithMessage(BASE_URL . 'student/profile.php', 'danger', 'Failed to create upload directory.');
            }
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $profile_picture_path = 'uploads/profiles/' . $new_filename;
            $update_picture = true;
            
            // Delete old profile picture if exists
            $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if ($result && $result['profile_picture']) {
                $old_picture = $result['profile_picture'];
                $old_file_path = '../../' . $old_picture;
                if (file_exists($old_file_path) && is_file($old_file_path)) {
                    @unlink($old_file_path);
                }
            }
        } else {
            $error_msg = 'Failed to upload profile picture. ';
            if (!is_writable($upload_dir)) {
                $error_msg .= 'Upload directory is not writable.';
            }
            redirectWithMessage(BASE_URL . 'student/profile.php', 'danger', $error_msg);
        }
    }
    
    // Update profile in database
    if ($update_picture) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $profile_picture_path, $user_id]);
        
        // Update session with new profile picture
        $_SESSION['profile_picture'] = $profile_picture_path;
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $user_id]);
    }
    
    // Update session with new name
    $_SESSION['full_name'] = $full_name;
    
    // Log the action
    $description = 'Updated full name';
    if ($update_picture) {
        $description .= ' and profile picture';
    }
    logAudit($conn, $user_id, 'Updated profile', 'update', 'users', $user_id, $description);
    
    redirectWithMessage(BASE_URL . 'student/profile.php', 'success', 'Profile updated successfully!');
    
} catch (PDOException $e) {
    error_log("Update Profile Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'student/profile.php', 'danger', 'An error occurred. Please try again.');
} catch (Exception $e) {
    error_log("Update Profile Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'student/profile.php', 'danger', 'An error occurred. Please try again.');
}
?>