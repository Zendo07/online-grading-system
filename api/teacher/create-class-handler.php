<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

// Require teacher access
requireTeacher();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'teacher/create-class.php');
    exit();
}

// Get and sanitize input
$class_name = sanitize($_POST['class_name']);
$subject = sanitize($_POST['subject']);
$section = sanitize($_POST['section']);
$teacher_id = $_SESSION['user_id'];

// Validate input
if (empty($class_name) || empty($subject) || empty($section)) {
    redirectWithMessage(BASE_URL . 'teacher/create-class.php', 'danger', 'Please fill in all required fields.');
}

try {
    // Generate unique class code
    $class_code = generateUniqueClassCode($conn);
    
    // Insert class
    $stmt = $conn->prepare("
        INSERT INTO classes (teacher_id, class_name, subject, section, class_code, status) 
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$teacher_id, $class_name, $subject, $section, $class_code]);
    
    $class_id = $conn->lastInsertId();
    
    // Log the action
    logAudit(
        $conn, 
        $teacher_id, 
        'Created new class: ' . $class_name, 
        'create', 
        'classes', 
        $class_id, 
        "Class Code: $class_code"
    );
    
    // Redirect with success message
    redirectWithMessage(
        BASE_URL . 'teacher/manage-students.php?class_id=' . $class_id, 
        'success', 
        "Class created successfully! Class Code: <strong>$class_code</strong>. Share this code with your students."
    );
    
} catch (PDOException $e) {
    error_log("Create Class Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'teacher/create-class.php', 'danger', 'An error occurred while creating the class. Please try again.');
}
?>