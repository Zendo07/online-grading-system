<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/verify-email.php');
    exit();
}

// Check if user has pending registration
if (!isset($_SESSION['pending_registration'])) {
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Invalid verification session.');
}

$registration_data = $_SESSION['pending_registration'];

try {
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
        redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Failed to send verification email. Please try again.');
    }

    // Update session with new code and expiry
    $_SESSION['pending_registration']['verification_code'] = $verification_code;
    $_SESSION['pending_registration']['expires_at'] = $expires_at;

    redirectWithMessage(
        BASE_URL . 'auth/verify-email.php',
        'success',
        'A new verification code has been sent to your email.'
    );

} catch (Exception $e) {
    error_log("Resend Verification Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'An error occurred. Please try again.');
}