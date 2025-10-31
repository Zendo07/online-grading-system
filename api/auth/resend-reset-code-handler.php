<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

session_start();

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if reset session exists
if (!isset($_SESSION['reset_email'])) {
    echo json_encode(['success' => false, 'message' => 'No reset session found']);
    exit();
}

$email = $_SESSION['reset_email'];

try {
    // Rate limiting - max 3 requests in 5 minutes
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM password_resets 
        WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute([$email]);
    $recent_requests = $stmt->fetch()['count'];

    if ($recent_requests >= 3) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait 5 minutes.']);
        exit();
    }

    // Get user
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Generate new reset code
    $reset_code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . PASSWORD_RESET_EXPIRY . ' minutes'));
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Store new reset code
    $stmt = $conn->prepare("
        INSERT INTO password_resets (email, reset_code, expires_at, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$email, $reset_code, $expires_at, $ip_address]);

    // Send reset email
    $email_sent = sendPasswordResetEmail($email, $user['full_name'], $reset_code);

    if (!$email_sent) {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Reset code resent successfully!']);

} catch (PDOException $e) {
    error_log("Resend Reset Code Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}