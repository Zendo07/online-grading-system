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
$full_name = sanitize($_POST['full_name']);
$email = sanitize($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$invitation_code = isset($_POST['invitation_code']) ? sanitize($_POST['invitation_code']) : '';

// Validate input
if (empty($role) || empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
    redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Please fill in all required fields.');
}

// Validate role
if (!in_array($role, ['teacher', 'student'])) {
    redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Invalid role selected.');
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

// Validate teacher invitation code
if ($role === 'teacher') {
    if (empty($invitation_code)) {
        redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Teacher invitation code is required.');
    }
    
    // Check if invitation code is valid and not used
    $stmt = $conn->prepare("SELECT code_id, is_used FROM teacher_codes WHERE invitation_code = ?");
    $stmt->execute([$invitation_code]);
    $code = $stmt->fetch();
    
    if (!$code) {
        redirectWithMessage(BASE_URL . 'auth/register.php', 'danger', 'Invalid invitation code.');
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
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->execute([$email, $hashed_password, $full_name, $role]);
    
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