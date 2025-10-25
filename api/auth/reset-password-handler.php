<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

session_start();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/reset-password.php');
    exit();
}

// Check if email is in session
if (!isset($_SESSION['reset_email'])) {
    redirectWithMessage(BASE_URL . 'auth/forgot-password.php', 'danger', 'Invalid reset session.');
}

$email = $_SESSION['reset_email'];

// Get verification code from input
$code = '';
for ($i = 1; $i <= 6; $i++) {
    $code .= isset($_POST["digit$i"]) ? sanitize($_POST["digit$i"]) : '';
}

$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Validate inputs
if (strlen($code) !== 6) {
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Please enter the complete 6-digit code.');
}

if (empty($new_password) || empty($confirm_password)) {
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Please enter and confirm your new password.');
}

if ($new_password !== $confirm_password) {
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Passwords do not match.');
}

if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
}

try {
    // Get reset record
    $stmt = $conn->prepare("
        SELECT * FROM password_resets 
        WHERE email = ? AND is_used = FALSE 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $reset = $stmt->fetch();

    if (!$reset) {
        redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'No valid reset request found. Please request a new code.');
    }

    // Check if code expired
    if (strtotime($reset['expires_at']) < time()) {
        redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Reset code has expired. Please request a new one.');
    }

    // Verify code
    if ($reset['reset_code'] !== $code) {
        redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'Invalid reset code. Please try again.');
    }

    // Get user
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'User not found.');
    }

    // Code is valid - update password
    $conn->beginTransaction();

    // Hash new password
    $hashed_password = hashPassword($new_password);

    // Update user password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$hashed_password, $user['user_id']]);

    // Mark reset as used
    $stmt = $conn->prepare("
        UPDATE password_resets 
        SET is_used = TRUE, used_at = NOW() 
        WHERE reset_id = ?
    ");
    $stmt->execute([$reset['reset_id']]);

    // Log the action
    logAudit($conn, $user['user_id'], 'Password reset', 'update', 'users', $user['user_id'], 'Password reset successful');

    $conn->commit();

    // Clear session
    unset($_SESSION['reset_email']);

    // Redirect to login with success message
    redirectWithMessage(
        BASE_URL . 'auth/login.php', 
        'success', 
        'Password reset successful! You can now login with your new password.'
    );

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Reset Password Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'auth/reset-password.php', 'danger', 'An error occurred. Please try again.');
}