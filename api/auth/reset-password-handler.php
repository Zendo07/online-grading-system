<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/forgot-password.php');
    exit();
}

if (!isset($_SESSION['reset_email'])) {
    redirectWithMessage(BASE_URL . 'auth/forgot-password.php', 'danger', 'Invalid reset session. Please request a new password reset.');
}

$code_digits = [
    sanitize($_POST['digit1'] ?? ''),
    sanitize($_POST['digit2'] ?? ''),
    sanitize($_POST['digit3'] ?? ''),
    sanitize($_POST['digit4'] ?? ''),
    sanitize($_POST['digit5'] ?? ''),
    sanitize($_POST['digit6'] ?? '')
];

$verification_code = implode('', $code_digits);
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$email = $_SESSION['reset_email'];
$reset_id = $_SESSION['reset_id'] ?? null;
$user_id = $_SESSION['reset_user_id'] ?? null;

if (empty($verification_code) || strlen($verification_code) !== 6) {
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Invalid verification code');
}

if (!$reset_id || !$user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT pr.reset_id, pr.user_id, pr.expires_at, pr.used
            FROM password_resets pr
            WHERE pr.email = ? 
            AND pr.reset_code = ?
            AND pr.used = FALSE
            ORDER BY pr.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$email, $verification_code]);
        $reset_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset_data) {
            redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Invalid verification code');
        }

        if (strtotime($reset_data['expires_at']) < time()) {
            redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Verification code has expired');
        }

        $_SESSION['reset_id'] = $reset_data['reset_id'];
        $_SESSION['reset_user_id'] = $reset_data['user_id'];
        
        $reset_id = $reset_data['reset_id'];
        $user_id = $reset_data['user_id'];
        
    } catch (PDOException $e) {
        error_log("Code Verification Error: " . $e->getMessage());
        redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'An error occurred. Please try again.');
    }
}

if (empty($new_password)) {
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Password is required');
}

if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
}

if ($new_password !== $confirm_password) {
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Passwords do not match');
}

try {
    $stmt = $conn->prepare("
        SELECT reset_id, user_id, expires_at, used
        FROM password_resets
        WHERE reset_id = ? AND user_id = ? AND email = ? AND reset_code = ?
    ");
    $stmt->execute([$reset_id, $user_id, $email, $verification_code]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Invalid reset request');
    }

    if ($reset['used']) {
        redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'This reset code has already been used. Please request a new one.');
    }

    if (strtotime($reset['expires_at']) < time()) {
        redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Reset code has expired. Please request a new one.');
    }

    $conn->beginTransaction();

    $hashed_password = hashPassword($new_password);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$hashed_password, $user_id]);

    $stmt = $conn->prepare("UPDATE password_resets SET used = TRUE, used_at = NOW() WHERE reset_id = ?");
    $stmt->execute([$reset_id]);

    logAudit($conn, $user_id, 'Password reset successfully', 'update', 'users', $user_id, 'Password was reset via email verification');

    $conn->commit();

    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_id']);
    unset($_SESSION['reset_user_id']);

    redirectWithMessage(
        BASE_URL . 'auth/login.php',
        'success',
        'Password reset successful! You can now login with your new password.'
    );

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Password Reset Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'An error occurred. Please try again.');
}