<?php
/**
 * Resend Password Reset Code Handler - FIXED
 * Generates and sends a new password reset code
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if reset session exists
if (!isset($_SESSION['reset_email'])) {
    echo json_encode(['success' => false, 'message' => 'No reset session found. Please start the password reset process again.']);
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
        echo json_encode([
            'success' => false, 
            'message' => 'Too many requests. Please wait 5 minutes before trying again.'
        ]);
        exit();
    }

    // Get user information
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found. Please try again.']);
        exit();
    }

    // Generate new reset code
    $reset_code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '::1';

    // Begin transaction
    $conn->beginTransaction();

    // Mark old codes as used
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
    $stmt->execute([$email]);

    // Insert new reset code
    $stmt = $conn->prepare("
        INSERT INTO password_resets (user_id, email, reset_code, expires_at, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user['user_id'], $email, $reset_code, $expires_at, $ip_address]);

    // Send reset email
    $email_sent = sendPasswordResetEmail($email, $user['full_name'], $reset_code);

    if (!$email_sent) {
        $conn->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send email. Please check your email address and try again.'
        ]);
        exit();
    }

    // Commit transaction
    $conn->commit();

    // SUCCESS response
    echo json_encode([
        'success' => true, 
        'message' => 'A new reset code has been sent to your email successfully!'
    ]);
    exit();

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Resend Reset Code Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request. Please try again.'
    ]);
    exit();
}
?>