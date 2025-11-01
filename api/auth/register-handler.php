<?php
/**
 * Registration Handler with Email Verification - FIXED
 * Updated: Year & Section validation, proper field order, profile picture fix
 */

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';
require_once '../../includes/email-config.php';

// Prevent access if already logged in
if (isLoggedIn()) {
    redirectToDashboard();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Invalid request method.');
}

// Sanitize and validate common fields
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = strtolower(trim($_POST['role'] ?? ''));

// Validate role
if (!in_array($role, ['student', 'teacher', 'professor'])) {
    redirectWithMessage(BASE_URL . 'auth/login.php', 'danger', 'Invalid role specified.');
}

// Set redirect page based on role
$redirect_page = ($role === 'student') ? 'student-register.php' : 'teacher-register.php';

// Validate required common fields
if (empty($email) || empty($password) || empty($confirm_password)) {
    redirectWithMessage(BASE_URL . 'auth/' . $redirect_page, 'danger', 'All required fields must be filled.');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithMessage(BASE_URL . 'auth/' . $redirect_page, 'danger', 'Invalid email format.');
}

// Validate password match
if ($password !== $confirm_password) {
    redirectWithMessage(BASE_URL . 'auth/' . $redirect_page, 'danger', 'Passwords do not match.');
}

// Validate password length
if (strlen($password) < 8) {
    redirectWithMessage(BASE_URL . 'auth/' . $redirect_page, 'danger', 'Password must be at least 8 characters long.');
}

// Hash password securely
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        redirectWithMessage(BASE_URL . 'auth/' . $redirect_page, 'danger', 'This email is already registered. Please use a different email or login.');
    }
    
    // ==================== STUDENT REGISTRATION ====================
    if ($role === 'student') {
        
        // Sanitize student-specific fields
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $year_section = strtoupper(trim($_POST['year_section'] ?? '')); // Convert to uppercase
        $student_number = trim($_POST['student_number'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($program) || empty($year_section) || empty($student_number)) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'All required fields must be filled.');
        }
        
        // Validate name fields (letters and spaces only)
        if (!preg_match('/^[a-zA-Z\s]+$/', $first_name)) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'First name must contain only letters.');
        }
        
        if (!preg_match('/^[a-zA-Z\s]+$/', $last_name)) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'Last name must contain only letters.');
        }
        
        // Validate middle name if provided
        if (!empty($middle_name) && !preg_match('/^[a-zA-Z\s]+$/', $middle_name)) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'Middle name must contain only letters.');
        }
        
        // Validate program
        $valid_programs = ['BSIT', 'BSCS', 'ACT', 'BSIS'];
        if (!in_array($program, $valid_programs)) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'Invalid program selected.');
        }
        
        // Validate Year & Section format: 1-4 (year) + dash + A-Z (section)
        if (!preg_match('/^[1-4]-[A-Z]$/', $year_section)) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'Year & Section must be in format: 1-A, 2-B, 3-C, or 4-D (year 1-4, section A-Z).');
        }
        
        // Validate student number format (exactly 10 digits)
        if (!preg_match('/^\d{10}$/', $student_number)) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'Student number must be exactly 10 digits.');
        }
        
        // Validate contact number if provided (exactly 11 digits, Philippine format)
        if (!empty($contact_number)) {
            $contact_number = preg_replace('/[^0-9]/', '', $contact_number);
            
            if (strlen($contact_number) !== 11) {
                redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'Contact number must be exactly 11 digits.');
            }
            
            if (!preg_match('/^09\d{9}$/', $contact_number)) {
                redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'Contact number must start with 09 (Philippine format).');
            }
        }
        
        // Build full name with middle name if provided
        if (!empty($middle_name)) {
            $full_name = $first_name . ' ' . $middle_name . ' ' . $last_name;
        } else {
            $full_name = $first_name . ' ' . $last_name;
        }
        
        // Check if student number already exists
        $stmt = $conn->prepare("
            SELECT user_id 
            FROM users 
            WHERE email LIKE CONCAT('%', ?, '%') AND role = 'student'
        ");
        $stmt->execute([$student_number]);
        
        if ($stmt->fetch()) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'This student number is already registered.');
        }
        
        // Generate verification code
        $verification_code = generateVerificationCode();
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_CODE_EXPIRY . ' minutes'));
        
        // Send verification email
        $email_sent = sendVerificationEmail($email, $full_name, $verification_code);
        
        if (!$email_sent) {
            redirectWithMessage(BASE_URL . 'auth/student-register.php', 'danger', 'Failed to send verification email. Please check your email address and try again.');
        }
        
        // Store registration data in session (NOT in database yet - only after verification)
        $_SESSION['pending_registration'] = [
            'email' => $email,
            'password' => $hashed_password,
            'full_name' => $full_name,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'role' => 'student',
            'contact_number' => $contact_number ?: null,
            'student_number' => $student_number,
            'program' => $program,
            'year_section' => $year_section, // Store in new format (1-A, 2-B, etc.)
            'verification_code' => $verification_code,
            'expires_at' => $expires_at,
            'code_id' => null,
            'profile_picture' => null // IMPORTANT: Set to null for new accounts
        ];
        
        // Redirect to verification page
        redirectWithMessage(
            BASE_URL . 'auth/verify-email.php',
            'success',
            'Registration successful! Please check your email for the verification code.'
        );
    }
    
    // ==================== TEACHER REGISTRATION ====================
    elseif ($role === 'teacher' || $role === 'professor') {
        
        // Sanitize teacher-specific fields
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $invitation_code = trim($_POST['invitation_code'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($invitation_code)) {
            redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'All fields are required for teacher registration.');
        }
        
        // Validate name fields (letters and spaces only)
        if (!preg_match('/^[a-zA-Z\s]+$/', $first_name)) {
            redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'First name must contain only letters.');
        }
        
        if (!preg_match('/^[a-zA-Z\s]+$/', $last_name)) {
            redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'Last name must contain only letters.');
        }
        
        // Build full name
        $full_name = $first_name . ' ' . $last_name;
        
        // Validate contact number if provided
        if (!empty($contact_number)) {
            $contact_number = preg_replace('/[^0-9]/', '', $contact_number);
            
            if (strlen($contact_number) !== 11 || !preg_match('/^09\d{9}$/', $contact_number)) {
                redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'Contact number must be 11 digits starting with 09.');
            }
        }
        
        // Verify invitation code
        $stmt = $conn->prepare("
            SELECT code_id, is_used 
            FROM teacher_codes 
            WHERE invitation_code = ?
        ");
        $stmt->execute([$invitation_code]);
        $code_info = $stmt->fetch();
        
        if (!$code_info) {
            redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'Invalid invitation code. Please check and try again.');
        }
        
        if ($code_info['is_used']) {
            redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'This invitation code has already been used.');
        }
        
        // Generate verification code
        $verification_code = generateVerificationCode();
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_CODE_EXPIRY . ' minutes'));
        
        // Send verification email
        $email_sent = sendVerificationEmail($email, $full_name, $verification_code);
        
        if (!$email_sent) {
            redirectWithMessage(BASE_URL . 'auth/teacher-register.php', 'danger', 'Failed to send verification email. Please check your email address and try again.');
        }
        
        // Store registration data in session (NOT in database yet)
        $_SESSION['pending_registration'] = [
            'email' => $email,
            'password' => $hashed_password,
            'full_name' => $full_name,
            'first_name' => $first_name,
            'middle_name' => null,
            'last_name' => $last_name,
            'role' => 'teacher',
            'contact_number' => $contact_number ?: null,
            'student_number' => null,
            'program' => null,
            'year_section' => null,
            'verification_code' => $verification_code,
            'expires_at' => $expires_at,
            'code_id' => $code_info['code_id'],
            'profile_picture' => null // IMPORTANT: Set to null for new accounts
        ];
        
        // Redirect to verification page
        redirectWithMessage(
            BASE_URL . 'auth/verify-email.php',
            'success',
            'Registration successful! Please check your email for the verification code.'
        );
    }
    
} catch (PDOException $e) {
    // Database error
    error_log("Database Error in Registration: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    redirectWithMessage(
        BASE_URL . 'auth/' . $redirect_page, 
        'danger', 
        'A database error occurred. Please try again later. If the problem persists, contact support.'
    );
    
} catch (Exception $e) {
    // General error
    error_log("Registration Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    redirectWithMessage(
        BASE_URL . 'auth/' . $redirect_page, 
        'danger', 
        'An unexpected error occurred during registration. Please try again.'
    );
}
?>