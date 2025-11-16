<?php

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

requireStudent();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get and sanitize input
$first_name = sanitize($_POST['first_name'] ?? '');
$middle_name = sanitize($_POST['middle_name'] ?? '');
$last_name = sanitize($_POST['last_name'] ?? '');
$program = sanitize($_POST['program'] ?? '');
$year_section = sanitize($_POST['year_section'] ?? '');
$contact_number = sanitize($_POST['contact_number'] ?? '');
$email = sanitize($_POST['email'] ?? '');

// Validation
$errors = [];

if (empty($first_name)) {
    $errors[] = 'First name is required';
}

if (empty($last_name)) {
    $errors[] = 'Last name is required';
}

if (empty($program)) {
    $errors[] = 'Course/Program is required';
}

if (empty($year_section)) {
    $errors[] = 'Year & Section is required';
}

if (empty($contact_number)) {
    $errors[] = 'Contact number is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit();
}

try {
    // Construct full name
    $full_name = $first_name;
    if (!empty($middle_name)) {
        $full_name .= ' ' . $middle_name;
    }
    $full_name .= ' ' . $last_name;

    // Check if email is already used by another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Email is already in use by another account'
        ]);
        exit();
    }

    // Update user information
    $stmt = $conn->prepare("
        UPDATE users 
        SET 
            full_name = ?,
            middle_name = ?,
            program = ?,
            year_section = ?,
            contact_number = ?,
            email = ?
        WHERE user_id = ? AND role = 'student'
    ");
    
    $stmt->execute([
        $full_name,
        $middle_name,
        $program,
        $year_section,
        $contact_number,
        $email,
        $user_id
    ]);

    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;

    // Log the action
    logAudit(
        $conn, 
        $user_id, 
        'Updated profile information', 
        'update', 
        'users', 
        $user_id, 
        'Updated profile: ' . $full_name
    );

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'full_name' => $full_name
    ]);

} catch (PDOException $e) {
    error_log("Update Profile Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
}
?>