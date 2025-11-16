<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

requireStudent();

header('Content-Type: application/json');

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$user_id = $_SESSION['user_id'];
$file = $_FILES['profile_picture'];

// Upload profile picture
$result = uploadProfilePicture($file, $user_id);

if ($result['success']) {
    try {
        // Delete old profile picture if exists
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $old_pic = $stmt->fetchColumn();
        
        if ($old_pic && $old_pic !== $result['filename']) {
            $old_path = __DIR__ . '/../../uploads/profiles/' . $old_pic;
            if (file_exists($old_path)) {
                @unlink($old_path);
            }
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$result['filename'], $user_id]);
        
        // UPDATE SESSION IMMEDIATELY
        $_SESSION['profile_picture'] = $result['filename'];
        
        // Log action
        logAudit($conn, $user_id, 'Updated profile picture', 'update', 'users', $user_id, 'Changed profile picture');
        
        // Return full URL with cache buster
        $picture_url = BASE_URL . 'uploads/profiles/' . $result['filename'] . '?t=' . time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile picture updated successfully',
            'filename' => $result['filename'],
            'picture_url' => $picture_url
        ]);
    } catch (PDOException $e) {
        error_log("Upload Profile Picture Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode($result);
}
?>