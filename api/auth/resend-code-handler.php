<?php
/**
 * Resend Verification Code Handler - FIXED (JSON Response)
 * Generates and sends a new verification code during registration
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if user has pending registration
if (!isset($_SESSION['pending_registration'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification session. Please register again.']);
    exit();
}

$registration_data = $_SESSION['pending_registration'];

try {
    // Rate limiting - check if too many recent requests
    $last_resend = $_SESSION['last_resend_time'] ?? 0;
    $cooldown = 60; // 60 seconds between resends
    
    if (time() - $last_resend < $cooldown) {
        $remaining = $cooldown - (time() - $last_resend);
        echo json_encode([
            'success' => false, 
            'message' => "Please wait {$remaining} seconds before requesting a new code."
        ]);
        exit();
    }
    
    // Generate new verification code
    $verification_code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_CODE_EXPIRY . ' minutes'));

    // Send new verification email
    $email_sent = sendVerificationEmail(
        $registration_data['email'], 
        $registration_data['full_name'], 
        $verification_code
    );

    if (!$email_sent) {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send verification email. Please check your email address and try again.'
        ]);
        exit();
    }

    // Update session with new code and expiry
    $_SESSION['pending_registration']['verification_code'] = $verification_code;
    $_SESSION['pending_registration']['expires_at'] = $expires_at;
    $_SESSION['last_resend_time'] = time(); // Track last resend time

    // SUCCESS: Return JSON response
    echo json_encode([
        'success' => true, 
        'message' => 'A new verification code has been sent to your email successfully!'
    ]);
    exit();

} catch (Exception $e) {
    error_log("Resend Verification Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while sending the code. Please try again.'
    ]);
    exit();
}
?>