<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/verify-email.php');
    exit();
}

// Check if user has pending registration
if (!isset($_SESSION['pending_registration'])) {
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Invalid verification session.');
}

$registration_data = $_SESSION['pending_registration'];

// Get verification code from input
$code = '';
for ($i = 1; $i <= 6; $i++) {
    $code .= isset($_POST["digit$i"]) ? sanitize($_POST["digit$i"]) : '';
}

if (strlen($code) !== 6) {
    redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Please enter the complete 6-digit code.');
}

try {
    // Check if code expired
    if (strtotime($registration_data['expires_at']) < time()) {
        redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Verification code has expired. Please request a new one.');
    }

    // Verify code
    if ($registration_data['verification_code'] !== $code) {
        redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Invalid verification code. Please try again.');
    }

    // Code is valid - NOW save to database
    $conn->beginTransaction();

    // Insert user into database
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, full_name, role, contact_number, student_number, program, status, email_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', TRUE)
    ");
    $stmt->execute([
        $registration_data['email'],
        $registration_data['password'],
        $registration_data['full_name'],
        $registration_data['role'],
        $registration_data['contact_number'],
        $registration_data['student_number'],
        $registration_data['program']
    ]);

    $user_id = $conn->lastInsertId();

    // Update teacher code usage if teacher
    if ($registration_data['role'] === 'teacher' && $registration_data['code_id']) {
        $stmt = $conn->prepare("UPDATE teacher_codes SET use_count = use_count + 1 WHERE code_id = ?");
        $stmt->execute([$registration_data['code_id']]);
    }

    // Save verification record
    $stmt = $conn->prepare("
        INSERT INTO email_verifications (user_id, email, verification_code, expires_at, is_verified, verified_at)
        VALUES (?, ?, ?, ?, TRUE, NOW())
    ");
    $stmt->execute([
        $user_id,
        $registration_data['email'],
        $registration_data['verification_code'],
        $registration_data['expires_at']
    ]);

    // Log the registration
    logAudit($conn, $user_id, 'User registered', 'create', 'users', $user_id, 'Email verified - account created');

    $conn->commit();

    // Auto-login the user
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $registration_data['email'];
    $_SESSION['full_name'] = $registration_data['full_name'];
    $_SESSION['role'] = $registration_data['role'];
    $_SESSION['last_activity'] = time();

    // Clear pending registration data
    unset($_SESSION['pending_registration']);

    // Redirect to appropriate dashboard
    if ($registration_data['role'] === 'teacher') {
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