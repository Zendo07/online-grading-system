<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

session_start();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/verify-email.php');
    exit();
}

// Check if user is pending verification
if (!isset($_SESSION['pending_verification_user_id'])) {
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Invalid verification session.');
}

$user_id = $_SESSION['pending_verification_user_id'];

// Get verification code from input
$code = '';
for ($i = 1; $i <= 6; $i++) {
    $code .= isset($_POST["digit$i"]) ? sanitize($_POST["digit$i"]) : '';
}

if (strlen($code) !== 6) {
    redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Please enter the complete 6-digit code.');
}

try {
    // Get verification record
    $stmt = $conn->prepare("
        SELECT * FROM email_verifications 
        WHERE user_id = ? AND is_verified = FALSE 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $verification = $stmt->fetch();

    if (!$verification) {
        redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'No verification code found. Please request a new one.');
    }

    // Check if code expired
    if (strtotime($verification['expires_at']) < time()) {
        redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Verification code has expired. Please request a new one.');
    }

    // Verify code
    if ($verification['verification_code'] !== $code) {
        redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Invalid verification code. Please try again.');
    }

    // Code is valid - update user and verification records
    $conn->beginTransaction();

    // Mark email as verified
    $stmt = $conn->prepare("UPDATE users SET email_verified = TRUE WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Mark verification as complete
    $stmt = $conn->prepare("
        UPDATE email_verifications 
        SET is_verified = TRUE, verified_at = NOW() 
        WHERE verification_id = ?
    ");
    $stmt->execute([$verification['verification_id']]);

    // Log the verification
    logAudit($conn, $user_id, 'Email verified', 'update', 'users', $user_id, 'Email verification successful');

    $conn->commit();

    // Get user info for auto-login
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Auto-login the user
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    // Clear pending verification session
    unset($_SESSION['pending_verification_user_id']);
    unset($_SESSION['pending_verification_email']);

    // Redirect to appropriate dashboard
    if ($user['role'] === 'teacher') {
        redirectWithMessage(BASE_URL . 'teacher/dashboard.php', 'success', 'Email verified successfully! Welcome to IndEX!');
    } else {
        redirectWithMessage(BASE_URL . 'student/dashboard.php', 'success', 'Email verified successfully! Welcome to IndEX!');
    }

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Verification Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'An error occurred. Please try again.');
}