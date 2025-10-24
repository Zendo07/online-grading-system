<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/register.php');
    exit();
}

// Get and sanitize input
$role = sanitize($_POST['role']);
$first_name = sanitize($_POST['first_name']);
$last_name = sanitize($_POST['last_name']);
$full_name = $first_name . ' ' . $last_name;
$email = sanitize($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$invitation_code = isset($_POST['invitation_code']) ? sanitize($_POST['invitation_code']) : '';
$username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
$contact_number = isset($_POST['contact_number']) ? sanitize($_POST['contact_number']) : '';
$student_number = isset($_POST['student_number']) ? sanitize($_POST['student_number']) : '';
$program = isset($_POST['program']) ? sanitize($_POST['program']) : '';

// Validate input
if (empty($role) || empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
    redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'Please fill in all required fields.');
}

// Validate role
if (!in_array($role, ['teacher', 'student'])) {
    redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'Invalid role selected.');
}

// Validate email
if (!isValidEmail($email)) {
    redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Invalid email address.');
}

// Validate password length
if (strlen($password) < PASSWORD_MIN_LENGTH) {
    redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
}

// Check if passwords match
if ($password !== $confirm_password) {
    redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Passwords do not match.');
}

// ================================
// ✅ TEACHER-SPECIFIC VALIDATION
// ================================
if ($role === 'teacher') {

    // Invitation code check (existing)
    if (empty($invitation_code)) {
        redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Teacher invitation code is required.');
    }

    $stmt = $conn->prepare("SELECT code_id, is_used FROM teacher_codes WHERE invitation_code = ?");
    $stmt->execute([$invitation_code]);
    $code = $stmt->fetch();

    if (!$code) {
        redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Invalid invitation code.');
    }

    if ($code['is_used']) {
        redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'This invitation code has already been used.');
    }

    // ✅ Additional validation for teacher fields
    if (empty($username)) {
        redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Username is required for teachers.');
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Username is already taken.');
    }
}

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Email address is already registered.');
    }

    // Hash password
    $hashed_password = hashPassword($password);

    // Begin transaction
    $conn->beginTransaction();

    // ================================
    // ✅ INSERT USER (common logic)
    // ================================
    $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role, status, username, contact_number) VALUES (?, ?, ?, ?, 'active', ?, ?)");
    $stmt->execute([$email, $hashed_password, $full_name, $role, $username, $contact_number]);

    $user_id = $conn->lastInsertId();

    // If teacher, mark invitation code as used
    if ($role === 'teacher' && isset($code['code_id'])) {
        $stmt = $conn->prepare("UPDATE teacher_codes SET is_used = TRUE WHERE code_id = ?");
        $stmt->execute([$code['code_id']]);
    }

    // Log the registration
    logAudit($conn, $user_id, 'User registered', 'create', 'users', $user_id, "New $role account created");

    // Commit transaction
    $conn->commit();

    // Redirect to login with success message
    redirectWithMessage(BASE_URL . 'auth/login.php', 'success', 'Account created successfully! Please login.');

} catch (PDOException $e) {
    // Rollback on error
    $conn->rollBack();
    error_log("Registration Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'An error occurred during registration. Please try again.');
}
?>
