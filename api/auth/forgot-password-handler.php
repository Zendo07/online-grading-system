<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/forgot-password.php');
    exit();
}

$email = sanitize($_POST['email'] ?? '');

if (empty($email)) {
    redirectWithMessage(BASE_URL . 'auth/forgot-password.php', 'danger', 'Email address is required');
}

if (!isValidEmail($email)) {
    redirectWithMessage(BASE_URL . 'auth/forgot-password.php', 'danger', 'Invalid email address');
}

try {
    $stmt = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        redirectWithMessage(BASE_URL . 'auth/forgot-password.php', 'danger', 'This email is not registered yet. Please sign up first.');
    }

    $reset_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $conn->beginTransaction();

    $stmt = $conn->prepare("UPDATE password_resets SET used = TRUE WHERE email = ? AND used = FALSE");
    $stmt->execute([$email]);

    $stmt = $conn->prepare("
        INSERT INTO password_resets (user_id, email, reset_code, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user['user_id'], $email, $reset_code, $expires_at]);

    $email_sent = sendPasswordResetEmail($email, $user['full_name'], $reset_code);

    if (!$email_sent) {
        $conn->rollBack();
        error_log("Failed to send password reset email to: $email");
        redirectWithMessage(BASE_URL . 'auth/forgot-password.php', 'danger', 'Failed to send email. Please try again.');
    }

    logAudit($conn, $user['user_id'], 'Password reset requested', 'create', 'password_resets', null, 'User requested password reset');

    $conn->commit();

    $_SESSION['reset_email'] = $email;

    redirectWithMessage(
        BASE_URL . 'auth/reset-password.php',
        'success',
        'Verification code sent! Please check your email.'
    );

} catch (PDOException $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Forgot Password Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'auth/forgot-password.php', 'danger', 'An error occurred. Please try again.');
}