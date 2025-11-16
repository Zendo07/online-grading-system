<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

requireTeacher();

if (!isset($_GET['enrollment_id']) || !isset($_GET['class_id'])) {
    redirectWithMessage(BASE_URL . 'teacher/manage-students.php', 'danger', 'Invalid request.');
}

$enrollment_id = (int)$_GET['enrollment_id'];
$class_id = (int)$_GET['class_id'];
$teacher_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    
    if ($stmt->rowCount() === 0) {
        redirectWithMessage(BASE_URL . 'teacher/manage-students.php', 'danger', 'Unauthorized access.');
    }
    
    // Get student info before removing
    $stmt = $conn->prepare("
        SELECT u.full_name, u.user_id
        FROM enrollments e
        INNER JOIN users u ON e.student_id = u.user_id
        WHERE e.enrollment_id = ?
    ");
    $stmt->execute([$enrollment_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        redirectWithMessage(BASE_URL . 'teacher/manage-students.php?class_id=' . $class_id, 'danger', 'Student not found.');
    }
    
    // Update enrollment status to 'dropped'
    $stmt = $conn->prepare("UPDATE enrollments SET status = 'dropped' WHERE enrollment_id = ?");
    $stmt->execute([$enrollment_id]);
    
    // Log the action
    logAudit(
        $conn,
        $teacher_id,
        'Removed student from class',
        'delete',
        'enrollments',
        $enrollment_id,
        'Removed student: ' . $student['full_name']
    );
    
    redirectWithMessage(
        BASE_URL . 'teacher/manage-students.php?class_id=' . $class_id,
        'success',
        'Student removed from class successfully.'
    );
    
} catch (PDOException $e) {
    error_log("Remove Student Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'teacher/manage-students.php?class_id=' . $class_id, 'danger', 'An error occurred. Please try again.');
}
?>