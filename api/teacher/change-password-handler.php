<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

// Require teacher access
requireTeacher();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'teacher/change-password.php');
    exit();
}

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];
$user_id = $_SESSION['user_id'];

// Validate input
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    redirectWithMessage(BASE_URL . 'teacher/change-password.php', 'danger', 'All fields are required.');
}

if ($new_password !== $confirm_password) {
    redirectWithMessage(BASE_URL . 'teacher/change-password.php', 'danger', 'New passwords do not match.');
}

if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
    redirectWithMessage(BASE_URL . 'teacher/change-password.php', 'danger', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
}

try {
    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Verify current password
    if (!verifyPassword($current_password, $user['password'])) {
        redirectWithMessage(BASE_URL . 'teacher/change-password.php', 'danger', 'Current password is incorrect.');
    }
    
    // Hash new password
    $new_password_hash = hashPassword($new_password);
    
    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$new_password_hash, $user_id]);
    
    // Log the action
    logAudit($conn, $user_id, 'Changed password', 'update', 'users', $user_id, 'Password updated');
    
    redirectWithMessage(BASE_URL . 'teacher/change-password.php', 'success', 'Password changed successfully!');
    
} catch (PDOException $e) {
    error_log("Change Password Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'teacher/change-password.php', 'danger', 'An error occurred. Please try again.');
}
?>