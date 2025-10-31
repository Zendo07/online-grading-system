<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/email-config.php';

session_start();

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if user is pending verification
if (!isset($_SESSION['pending_verification_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification session']);
    exit();
}

$user_id = $_SESSION['pending_verification_user_id'];
$email = $_SESSION['pending_verification_email'];

try {
    // Rate limiting - check recent requests (max 3 in 5 minutes)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM email_verifications 
        WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute([$user_id]);
    $recent_requests = $stmt->fetch()['count'];

    if ($recent_requests >= 3) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait 5 minutes.']);
        exit();
    }

    // Generate new verification code
    $verification_code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_CODE_EXPIRY . ' minutes'));

    // Store new verification code
    $stmt = $conn->prepare("
        INSERT INTO email_verifications (user_id, email, verification_code, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $email, $verification_code, $expires_at]);

    // Get user full name
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Send verification email
    $email_sent = sendVerificationEmail($email, $user['full_name'], $verification_code);

    if (!$email_sent) {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Verification code resent successfully!']);

} catch (PDOException $e) {
    error_log("Resend Verification Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}