<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

session_start();

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

// Get and sanitize input
$role = sanitize($_POST['role']);
$first_name = sanitize($_POST['first_name']);
$last_name = sanitize($_POST['last_name']);
$full_name = trim($first_name . ' ' . $last_name);
$email = sanitize($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

$invitation_code = isset($_POST['invitation_code']) ? sanitize($_POST['invitation_code']) : '';
$username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
$contact_number = isset($_POST['contact_number']) ? sanitize($_POST['contact_number']) : '';
$student_number = isset($_POST['student_number']) ? sanitize($_POST['student_number']) : '';
$program = isset($_POST['program']) ? sanitize($_POST['program']) : '';

// Determine redirect URLs
$redirect_register = ($role === 'teacher')
    ? BASE_URL . 'auth/teacher-register.php'
    : BASE_URL . 'auth/student-register.php';

// =====================================
// 🧩 Basic validation
// =====================================
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

// =====================================
// 🧩 Teacher-specific validation
// =====================================
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

    if (empty($username)) {
        redirectWithMessage($redirect_register, 'danger', 'Username is required for teachers.');
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        redirectWithMessage($redirect_register, 'danger', 'Username is already taken.');
    }
}

// =====================================
// 🧩 Student-specific validation
// =====================================
if ($role === 'student') {
    if (empty($student_number)) {
        redirectWithMessage($redirect_register, 'danger', 'Student number is required.');
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE student_number = ?");
    $stmt->execute([$student_number]);
    if ($stmt->rowCount() > 0) {
        redirectWithMessage($redirect_register, 'danger', 'This student number is already registered.');
    }
}

// =====================================
// ✅ Database Insertion with Email Verification
// =====================================
try {
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        redirectWithMessage($redirect_register, 'danger', 'Email is already registered.');
    }

    // Hash password
    $hashed_password = hashPassword($password);

    $conn->beginTransaction();

    // Insert user (email_verified = FALSE by default)
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, full_name, role, username, contact_number, student_number, program, status, email_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', FALSE)
    ");
    $stmt->execute([$email, $hashed_password, $full_name, $role, $username, $contact_number, $student_number, $program]);

    $user_id = $conn->lastInsertId();

    // Track code usage (for teachers only)
    if ($role === 'teacher' && isset($code['code_id'])) {
        $stmt = $conn->prepare("UPDATE teacher_codes SET use_count = use_count + 1 WHERE code_id = ?");
        $stmt->execute([$code['code_id']]);
    }

    // Generate verification code
    $verification_code = generateVerificationCode();
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_CODE_EXPIRY . ' minutes'));

    // Store verification code
    $stmt = $conn->prepare("
        INSERT INTO email_verifications (user_id, email, verification_code, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $email, $verification_code, $expires_at]);

    // Send verification email
    $email_sent = sendVerificationEmail($email, $full_name, $verification_code);

    if (!$email_sent) {
        $conn->rollBack();
        error_log("Failed to send verification email to: $email");
        redirectWithMessage($redirect_register, 'danger', 'Failed to send verification email. Please try again or contact support.');
    }

    // Log registration
    logAudit($conn, $user_id, 'User registered', 'create', 'users', $user_id, "New $role account created - awaiting email verification");

    $conn->commit();

    // Store user_id in session for verification page
    $_SESSION['pending_verification_user_id'] = $user_id;
    $_SESSION['pending_verification_email'] = $email;

    // Redirect to verification page
    redirectWithMessage(
        BASE_URL . 'auth/verify-email.php',
        'success',
        'Registration successful! Please check your email for the verification code.'
    );

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Registration Error: " . $e->getMessage());
    redirectWithMessage($redirect_register, 'danger', 'An error occurred during registration. Please try again.');
}