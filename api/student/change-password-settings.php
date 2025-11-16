<?php

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

requireStudent();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];

if (empty($current_password)) {
    $errors[] = 'Current password is required';
}

if (empty($new_password)) {
    $errors[] = 'New password is required';
} elseif (strlen($new_password) < 8) {
    $errors[] = 'New password must be at least 8 characters long';
}

if (empty($confirm_password)) {
    $errors[] = 'Please confirm your new password';
} elseif ($new_password !== $confirm_password) {
    $errors[] = 'New passwords do not match';
}

if ($current_password === $new_password) {
    $errors[] = 'New password must be different from current password';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ? AND role = 'student'");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    if (!password_verify($current_password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
        exit();
    }

    // Hash new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$new_password_hash, $user_id]);

    // Log the action
    logAudit(
        $conn, 
        $user_id, 
        'Changed password', 
        'update', 
        'users', 
        $user_id, 
        'Password changed successfully'
    );

    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully. Please login again with your new password.'
    ]);

} catch (PDOException $e) {
    error_log("Change Password Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
}
?>