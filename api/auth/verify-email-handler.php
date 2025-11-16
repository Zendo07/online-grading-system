<?php

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Invalid request method.');
}

$entered_code = '';
for ($i = 1; $i <= 6; $i++) {
    $entered_code .= trim($_POST["digit$i"] ?? '');
}

error_log("DEBUG - Entered code: " . $entered_code);

if (empty($entered_code) || strlen($entered_code) !== 6 || !ctype_digit($entered_code)) {
    redirectWithMessage(BASE_URL . 'auth/verify-email.php', 'danger', 'Please enter all 6 digits of the verification code.');
}

if (!isset($_SESSION['pending_registration'])) {
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'No pending registration found. Please register again.');
}

$registration_data = $_SESSION['pending_registration'];

error_log("DEBUG - Expected code: " . $registration_data['verification_code']);
error_log("DEBUG - Code expires at: " . $registration_data['expires_at']);
error_log("DEBUG - Current time: " . date('Y-m-d H:i:s'));

// Verify the code matches
if ($entered_code !== $registration_data['verification_code']) {
    
    redirectWithMessage(
        BASE_URL . 'auth/verify-email.php', 
        'danger', 
        'Invalid verification code. You entered: ' . $entered_code . ' but expected: ' . $registration_data['verification_code']
    );
}

// Check if code has expired
$expires_timestamp = strtotime($registration_data['expires_at']);
$current_timestamp = time();

if ($expires_timestamp < $current_timestamp) {
    $time_diff = $current_timestamp - $expires_timestamp;
    redirectWithMessage(
        BASE_URL . 'auth/verify-email.php', 
        'danger', 
        'Verification code expired ' . round($time_diff / 60) . ' minutes ago. Please request a new code.'
    );
}

try {
    $conn->beginTransaction();
    
    // Insert user into database
    $stmt = $conn->prepare("
        INSERT INTO users (
            email, 
            email_verified, 
            password, 
            full_name, 
            middle_name,
            role, 
            contact_number, 
            student_number, 
            program, 
            year_section,
            profile_picture,
            status,
            created_at
        ) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 'active', NOW())
    ");
    
    $stmt->execute([
        $registration_data['email'],
        $registration_data['password'],
        $registration_data['full_name'],
        $registration_data['middle_name'],
        $registration_data['role'],
        $registration_data['contact_number'],
        $registration_data['student_number'],
        $registration_data['program'],
        $registration_data['year_section']
    ]);
    
    $user_id = $conn->lastInsertId();
    
    // If teacher registration, increment the use_count for the invitation code
    if ($registration_data['role'] === 'teacher' && $registration_data['code_id']) {
        $stmt = $conn->prepare("
            UPDATE teacher_codes 
            SET use_count = use_count + 1 
            WHERE code_id = ?
        ");
        $stmt->execute([$registration_data['code_id']]);
    }
    
    // Log the registration in audit_logs
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (
            user_id, 
            action, 
            action_type, 
            table_affected, 
            record_id, 
            description, 
            ip_address, 
            user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        'User registered',
        'create',
        'users',
        $user_id,
        'Email verified - account created: ' . $registration_data['full_name'],
        $_SERVER['REMOTE_ADDR'] ?? '::1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $stmt = $conn->prepare("
        INSERT INTO email_verifications (
            user_id, 
            email, 
            verification_code, 
            expires_at, 
            is_verified, 
            verified_at
        ) VALUES (?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $registration_data['email'],
        $registration_data['verification_code'],
        $registration_data['expires_at']
    ]);
    
    // Commit transaction
    $conn->commit();
    
    error_log("DEBUG - User created successfully with ID: " . $user_id);
    
    // Clear pending registration data
    unset($_SESSION['pending_registration']);
    
    // Create login session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $registration_data['email'];
    $_SESSION['full_name'] = $registration_data['full_name'];
    $_SESSION['middle_name'] = $registration_data['middle_name'];
    $_SESSION['role'] = $registration_data['role'];
    $_SESSION['profile_picture'] = null;
    $_SESSION['last_activity'] = time();
    
    // Redirect to appropriate dashboard
    if ($registration_data['role'] === 'teacher') {
        redirectWithMessage(
            BASE_URL . 'teacher/dashboard.php',
            'success',
            'Welcome to indEx, ' . $registration_data['full_name'] . '! Your account has been created successfully.'
        );
    } else {
        redirectWithMessage(
            BASE_URL . 'student/dashboard.php',
            'success',
            'Welcome to indEx, ' . $registration_data['full_name'] . '! Your account has been created successfully.'
        );
    }
    
} catch (PDOException $e) {

    $conn->rollBack();
    
    error_log("Verification Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    redirectWithMessage(
        BASE_URL . 'auth/verify-email.php',
        'danger',
        'An error occurred during verification. Please try again or contact support.'
    );
}
?>