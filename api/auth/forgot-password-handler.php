<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

session_start();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/forgot-password.php');
    exit();
}

$email = sanitize($_POST['email']);

// Validate email
if (empty($email) || !isValidEmail($email)) {
    redirectWithMessage(BASE_URL . 'auth/forgot-password.php', 'danger', 'Please enter a valid email address.');
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, full_name, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show success message (security best practice - don't reveal if email exists)
    if (!$user) {
        redirectWithMessage(
            BASE_URL . 'auth/forgot-password.php', 
            'success', 
            'If an account exists with this email, a password reset code has been sent.'
        );
    }

    // Check if email is verified
    if (!$user['email_verified']) {
        redirectWithMessage(
            BASE_URL . 'auth/forgot-password.php', 
            'warning', 
            'Please verify your email address first before resetting your password.'
        );
    }

    // Rate limiting - check recent reset requests
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM password_resets 
        WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$email]);
    $recent_requests = $stmt->fetch()['count'];

    if ($recent_requests >= 3) {
        redirectWithMessage(
            BASE_URL . 'auth/forgot-password.php', 
            'warning', 
            'Too many reset requests. Please try again in 15 minutes.'
        );
    }

    // Generate reset code
    $reset_code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . PASSWORD_RESET_EXPIRY . ' minutes'));
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Store reset code
    $stmt = $conn->prepare("
        INSERT INTO password_resets (email, reset_code, expires_at, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$email, $reset_code, $expires_at, $ip_address]);

    // Send reset email
    $email_sent = sendPasswordResetEmail($email, $user['full_name'], $reset_code);

    if (!$email_sent) {
        error_log("Failed to send password reset email to: $email");
        redirectWithMessage(
            BASE_URL . 'auth/forgot-password.php', 
            'danger', 
            'Failed to send reset email. Please try again or contact support.'
        );
    }

    // Store email in session for reset page
    $_SESSION['reset_email'] = $email;

    // Redirect to reset code verification page
    redirectWithMessage(
        BASE_URL . 'auth/reset-password.php', 
        'success', 
        'A password reset code has been sent to your email.'
    );

} catch (PDOException $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    redirectWithMessage(
        BASE_URL . 'auth/forgot-password.php', 
        'danger', 
        'An error occurred. Please try again.'
    );
}