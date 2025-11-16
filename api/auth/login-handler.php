<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$email = sanitize($_POST['email']);
$password = $_POST['password'];
$remember = isset($_POST['remember']);

// Validate input
if (empty($email) || empty($password)) {
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Please fill in all fields.');
}

if (!isValidEmail($email)) {
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Invalid email address.');
}

try {
    
    $stmt = $conn->prepare("
        SELECT 
            user_id, 
            email, 
            password, 
            full_name, 
            middle_name,
            role, 
            status, 
            email_verified, 
            profile_picture,
            created_at 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Invalid email or password.');
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Your account has been deactivated. Please contact administrator.');
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Invalid email or password.');
    }
    
    // Auto-verify legacy users (created before verification system)
    if (!$user['email_verified']) {
        if (strtotime($user['created_at']) < strtotime('2025-01-30')) {
            $stmt = $conn->prepare("UPDATE users SET email_verified = TRUE WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            $user['email_verified'] = true;
            
            logAudit($conn, $user['user_id'], 'Auto-verified legacy user', 'update', 'users', $user['user_id'], 'Email auto-verified for legacy account');
        }
    }
    
    // Check if email is verified (for new users)
    if (!$user['email_verified']) {
        $_SESSION['pending_verification_user_id'] = $user['user_id'];
        $_SESSION['pending_verification_email'] = $user['email'];
        
        redirectWithMessage(
            BASE_URL . 'auth/verify-email.php', 
            'warning', 
            'Please verify your email address before logging in. A verification code has been sent to your email.'
        );
    }
    
    // Password is correct and email verified - Create session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['middle_name'] = $user['middle_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_picture'] = $user['profile_picture']; 
    $_SESSION['last_activity'] = time();
    
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
    }
    
    logAudit($conn, $user['user_id'], 'User logged in', 'login', 'users', $user['user_id'], 'Successful login');
    
    // Redirect based on role to their respective dashboard
    if ($user['role'] === 'teacher') {
        redirectWithMessage(BASE_URL . 'teacher/dashboard.php', 'success', 'Welcome back, ' . $user['full_name'] . '!');
    } else {
        redirectWithMessage(BASE_URL . 'student/dashboard.php', 'success', 'Welcome back, ' . $user['full_name'] . '!');
    }
    
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'An error occurred. Please try again.');
}
?>