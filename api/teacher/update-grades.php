<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

// Require teacher access
requireTeacher();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'teacher/grades.php');
    exit();
}

$class_id = (int)$_POST['class_id'];
$student_id = (int)$_POST['student_id'];
$activity_name = sanitize($_POST['activity_name']);
$activity_type = sanitize($_POST['activity_type']);
$score = (float)$_POST['score'];
$max_score = (float)$_POST['max_score'];
$grading_period = sanitize($_POST['grading_period']);
$teacher_id = $_SESSION['user_id'];

// Validate input
if (empty($class_id) || empty($student_id) || empty($activity_name) || empty($activity_type) || empty($grading_period)) {
    redirectWithMessage(BASE_URL . 'teacher/grades.php?class_id=' . $class_id, 'danger', 'Please fill in all required fields.');
}

// Validate activity type
if (!in_array($activity_type, ['quiz', 'exam', 'assignment', 'project', 'recitation', 'other'])) {
    redirectWithMessage(BASE_URL . 'teacher/grades.php?class_id=' . $class_id, 'danger', 'Invalid activity type.');
}

// Validate grading period
if (!in_array($grading_period, ['midterm', 'finals'])) {
    redirectWithMessage(BASE_URL . 'teacher/grades.php?class_id=' . $class_id, 'danger', 'Invalid grading period.');
}

// Validate scores
if ($score < 0 || $max_score <= 0 || $score > $max_score) {
    redirectWithMessage(BASE_URL . 'teacher/grades.php?class_id=' . $class_id, 'danger', 'Invalid score values.');
}

try {
    // Verify teacher owns this class
    $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    
    if ($stmt->rowCount() === 0) {
        redirectWithMessage(BASE_URL . 'teacher/grades.php', 'danger', 'Unauthorized access.');
    }
    
    // Verify student is enrolled in this class
    $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE student_id = ? AND class_id = ? AND status = 'active'");
    $stmt->execute([$student_id, $class_id]);
    
    if ($stmt->rowCount() === 0) {
        redirectWithMessage(BASE_URL . 'teacher/grades.php?class_id=' . $class_id, 'danger', 'Student is not enrolled in this class.');
    }
    
    // Insert grade
    $stmt = $conn->prepare("
        INSERT INTO grades (student_id, class_id, activity_name, activity_type, score, max_score, grading_period, recorded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$student_id, $class_id, $activity_name, $activity_type, $score, $max_score, $grading_period, $teacher_id]);
    
    $grade_id = $conn->lastInsertId();
    
    // Get student name for log
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $student_name = $stmt->fetch()['full_name'];
    
    // Log the action
    logAudit(
        $conn,
        $teacher_id,
        'Added grade entry',
        'create',
        'grades',
        $grade_id,
        "Added $activity_name for $student_name: $score/$max_score"
    );
    
    redirectWithMessage(
        BASE_URL . 'teacher/grades.php?class_id=' . $class_id,
        'success',
        'Grade added successfully!'
    );
    
} catch (PDOException $e) {
    error_log("Update Grades Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'teacher/grades.php?class_id=' . $class_id, 'danger', 'An error occurred while saving the grade. Please try again.');
}
?>