<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$role = sanitize($_POST['role']);
$first_name = sanitize($_POST['first_name']);
$last_name = sanitize($_POST['last_name']);
$full_name = trim($first_name . ' ' . $last_name);
$email = sanitize($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

$invitation_code = isset($_POST['invitation_code']) ? sanitize($_POST['invitation_code']) : '';
$contact_number = isset($_POST['contact_number']) ? sanitize($_POST['contact_number']) : '';
$student_number = isset($_POST['student_number']) ? sanitize($_POST['student_number']) : '';
$program = isset($_POST['program']) ? sanitize($_POST['program']) : '';

$redirect_register = ($role === 'teacher')
    ? BASE_URL . 'auth/teacher-register.php'
    : BASE_URL . 'auth/student-register.php';

// Basic validation
if (empty($role) || empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
    redirectWithMessage($redirect_register, 'danger', 'Please fill in all required fields.');
}

if (!in_array($role, ['teacher', 'student'])) {
    redirectWithMessage($redirect_register, 'danger', 'Invalid role selected.');
}

if (!isValidEmail($email)) {
    redirectWithMessage($redirect_register, 'danger', 'Invalid email address.');
}

if (strlen($password) < PASSWORD_MIN_LENGTH) {
    redirectWithMessage($redirect_register, 'danger', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
}

if ($password !== $confirm_password) {
    redirectWithMessage($redirect_register, 'danger', 'Passwords do not match.');
}

// Teacher validation
if ($role === 'teacher') {
    if (empty($invitation_code)) {
        redirectWithMessage($redirect_register, 'danger', 'Teacher invitation code is required.');
    }

    $stmt = $conn->prepare("SELECT code_id FROM teacher_codes WHERE invitation_code = ?");
    $stmt->execute([$invitation_code]);
    $code = $stmt->fetch();

    if (!$code) {
        redirectWithMessage($redirect_register, 'danger', 'Invalid invitation code.');
    }
}

// Student validation
if ($role === 'student') {
    if (empty($student_number)) {
        redirectWithMessage($redirect_register, 'danger', 'Student number is required.');
    }

    // Check if student number already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE student_number = ?");
    $stmt->execute([$student_number]);
    if ($stmt->rowCount() > 0) {
        redirectWithMessage($redirect_register, 'danger', 'This student number is already registered.');
    }
}

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        redirectWithMessage($redirect_register, 'danger', 'Email is already registered.');
    }

    // Generate verification code
    $verification_code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_CODE_EXPIRY . ' minutes'));

    // Send verification email FIRST
    $email_sent = sendVerificationEmail($email, $full_name, $verification_code);

    if (!$email_sent) {
        error_log("Failed to send verification email to: $email");
        redirectWithMessage($redirect_register, 'danger', 'Failed to send verification email. Please check your email address or contact support.');
    }

    // Store registration data in SESSION
    $_SESSION['pending_registration'] = [
        'email' => $email,
        'password' => hashPassword($password),
        'full_name' => $full_name,
        'role' => $role,
        'contact_number' => $contact_number,
        'student_number' => $student_number,
        'program' => $program,
        'invitation_code' => $invitation_code,
        'code_id' => isset($code['code_id']) ? $code['code_id'] : null,
        'verification_code' => $verification_code,
        'expires_at' => $expires_at
    ];

    redirectWithMessage(
        BASE_URL . 'auth/verify-email.php',
        'success',
        'Registration successful! Please check your email for the verification code.'
    );

} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    redirectWithMessage($redirect_register, 'danger', 'An error occurred during registration. Please try again.');
} catch (Exception $e) {
    error_log("Email Error: " . $e->getMessage());
    redirectWithMessage($redirect_register, 'danger', 'Failed to send verification email. Please try again.');
}