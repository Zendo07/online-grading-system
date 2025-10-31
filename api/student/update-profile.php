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
    // Update profile
    $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
    $stmt->execute([$full_name, $user_id]);
    
    // Update session
    $_SESSION['full_name'] = $full_name;
    
    // Log the action
    logAudit($conn, $user_id, 'Updated profile', 'update', 'users', $user_id, 'Updated full name to: ' . $full_name);
    
    redirectWithMessage(BASE_URL . 'student/profile.php', 'success', 'Profile updated successfully!');
    
} catch (PDOException $e) {
    error_log("Update Profile Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'student/profile.php', 'danger', 'An error occurred. Please try again.');
}
?>