<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

requireTeacher();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$student_id = (int)$data['student_id'];
$class_id = (int)$data['class_id'];
$activity_type = sanitize($data['activity_type']); // quizzes, activities, projects, etc.
$score = (float)$data['score'];
$grading_period = sanitize($data['grading_period']);
$teacher_id = $_SESSION['user_id'];

try {
    // Verify teacher owns this class
    $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Map activity_type to database type
    $type_mapping = [
        'quizzes' => 'quiz',
        'activities' => 'assignment',
        'projects' => 'project',
        'recitation' => 'recitation',
        'exam' => 'exam'
    ];
    
    $db_type = $type_mapping[$activity_type] ?? 'other';
    $activity_name = ucfirst($activity_type);
    
    // Check if grade exists
    $stmt = $conn->prepare("
        SELECT grade_id 
        FROM grades 
        WHERE student_id = ? AND class_id = ? AND activity_type = ? AND grading_period = ?
        LIMIT 1
    ");
    $stmt->execute([$student_id, $class_id, $db_type, $grading_period]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing
        $stmt = $conn->prepare("
            UPDATE grades 
            SET score = ?, max_score = 100, recorded_by = ?
            WHERE grade_id = ?
        ");
        $stmt->execute([$score, $teacher_id, $existing['grade_id']]);
    } else {
        // Insert new
        $stmt = $conn->prepare("
            INSERT INTO grades (student_id, class_id, activity_name, activity_type, score, max_score, grading_period, recorded_by)
            VALUES (?, ?, ?, ?, ?, 100, ?, ?)
        ");
        $stmt->execute([$student_id, $class_id, $activity_name, $db_type, $score, $grading_period, $teacher_id]);
    }
    
    logAudit($conn, $teacher_id, 'Updated grade', 'update', 'grades', $class_id, "Score: $score for $activity_name");
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Save Grade Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>