<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

// Require student access
requireStudent();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'student/join-class.php');
    exit();
}

$class_code = strtoupper(sanitize($_POST['class_code']));
$student_id = $_SESSION['user_id'];

// Validate input
if (empty($class_code)) {
    redirectWithMessage(BASE_URL . 'student/join-class.php', 'danger', 'Please enter a class code.');
}

try {
    // Check if class code exists and is active
    $class = getClassByCode($conn, $class_code);
    
    if (!$class) {
        redirectWithMessage(BASE_URL . 'student/join-class.php', 'danger', 'Invalid class code. Please check and try again.');
    }
    
    // Check if student is already enrolled
    if (isStudentEnrolled($conn, $student_id, $class['class_id'])) {
        redirectWithMessage(BASE_URL . 'student/join-class.php', 'warning', 'You are already enrolled in this class.');
    }
    
    // Check if student was previously dropped from this class
    $stmt = $conn->prepare("
        SELECT enrollment_id 
        FROM enrollments 
        WHERE student_id = ? AND class_id = ? AND status = 'dropped'
    ");
    $stmt->execute([$student_id, $class['class_id']]);
    $dropped_enrollment = $stmt->fetch();
    
    if ($dropped_enrollment) {
        // Reactivate enrollment
        $stmt = $conn->prepare("UPDATE enrollments SET status = 'active' WHERE enrollment_id = ?");
        $stmt->execute([$dropped_enrollment['enrollment_id']]);
        $enrollment_id = $dropped_enrollment['enrollment_id'];
    } else {
        // Create new enrollment
        $stmt = $conn->prepare("
            INSERT INTO enrollments (student_id, class_id, status) 
            VALUES (?, ?, 'active')
        ");
        $stmt->execute([$student_id, $class['class_id']]);
        $enrollment_id = $conn->lastInsertId();
    }
    
    // Log the action
    logAudit(
        $conn,
        $student_id,
        'Joined class: ' . $class['class_name'],
        'join',
        'enrollments',
        $enrollment_id,
        'Class Code: ' . $class_code
    );
    
    redirectWithMessage(
        BASE_URL . 'student/dashboard.php',
        'success',
        'Successfully joined ' . htmlspecialchars($class['class_name']) . '!'
    );
    
} catch (PDOException $e) {
    error_log("Join Class Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'student/join-class.php', 'danger', 'An error occurred. Please try again.');
}
?>