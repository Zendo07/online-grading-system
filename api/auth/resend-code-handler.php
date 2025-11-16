<?php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['pending_registration'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification session. Please register again.']);
    exit();
}

$registration_data = $_SESSION['pending_registration'];

try {
    $last_resend = $_SESSION['last_resend_time'] ?? 0;
    $cooldown = 60; 
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