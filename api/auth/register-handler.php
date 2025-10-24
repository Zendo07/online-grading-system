<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

session_start(); // ✅ ensure session starts for auto-login

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

$redirect_success = ($role === 'teacher')
    ? BASE_URL . 'teacher/dashboard.php'
    : BASE_URL . 'student/dashboard.php';

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

    // Check if code exists
    $stmt = $conn->prepare("SELECT code_id FROM teacher_codes WHERE invitation_code = ?");
    $stmt->execute([$invitation_code]);
    $code = $stmt->fetch();

    if (!$code) {
        redirectWithMessage($redirect_register, 'danger', 'Invalid invitation code.');
    }

    // Check for username
    if (empty($username)) {
        redirectWithMessage($redirect_register, 'danger', 'Username is required for teachers.');
    }

    // Check if username already exists
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

    // Prevent duplicate student number
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE student_number = ?");
    $stmt->execute([$student_number]);
    if ($stmt->rowCount() > 0) {
        redirectWithMessage($redirect_register, 'danger', 'This student number is already registered.');
    }
}

// =====================================
// ✅ Database Insertion
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

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, full_name, role, username, contact_number, student_number, program, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$email, $hashed_password, $full_name, $role, $username, $contact_number, $student_number, $program]);

    $user_id = $conn->lastInsertId();

    // Track code usage (for teachers only)
    if ($role === 'teacher' && isset($code['code_id'])) {
        $stmt = $conn->prepare("UPDATE teacher_codes SET use_count = use_count + 1 WHERE code_id = ?");
        $stmt->execute([$code['code_id']]);
    }

    // Log registration
    logAudit($conn, $user_id, 'User registered', 'create', 'users', $user_id, "New $role account created");

    $conn->commit();

    // Auto-login
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();

    // ✅ Redirect to dashboard
    redirectWithMessage($redirect_success, 'success', "Account created successfully! Welcome, $full_name!");

} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Registration Error: " . $e->getMessage());
    redirectWithMessage($redirect_register, 'danger', 'An error occurred during registration. Please try again.');
}
?>
