<?php
/**
 * Unenroll Handler
 * File: api/student/unenroll-handler.php
 */

header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

// Require student access
requireStudent();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$student_id = $_SESSION['user_id'];
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

error_log("=== UNENROLL ATTEMPT ===");
error_log("Student ID: " . $student_id);
error_log("Class ID: " . $class_id);

// Validate input
if (empty($class_id)) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit();
}

try {
    // Check if student is enrolled in this class
    $stmt = $conn->prepare("
        SELECT e.*, c.class_name, c.subject, c.section
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.class_id
        WHERE e.student_id = ? AND e.class_id = ? AND e.status = 'active'
    ");
    $stmt->execute([$student_id, $class_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        error_log("Unenroll failed: Not enrolled or already inactive");
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this class']);
        exit();
    }
    
    error_log("Enrollment found: ID=" . $enrollment['enrollment_id']);
    
    // Update enrollment status to 'dropped'
    $stmt = $conn->prepare("
        UPDATE enrollments 
        SET status = 'dropped', 
            updated_at = NOW() 
        WHERE enrollment_id = ?
    ");
    
    $result = $stmt->execute([$enrollment['enrollment_id']]);
    
    if (!$result) {
        throw new Exception("Failed to update enrollment status");
    }
    
    error_log("Enrollment updated to dropped");
    
    // Log audit trail
    if (function_exists('logAudit')) {
        $log_details = json_encode([
            'enrollment_id' => $enrollment['enrollment_id'],
            'class_id' => $class_id,
            'class_name' => $enrollment['class_name'],
            'subject' => $enrollment['subject'],
            'section' => $enrollment['section'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        logAudit(
            $conn,
            $student_id,
            "Unenrolled from class: {$enrollment['class_name']}",
            'update',
            'enrollments',
            $enrollment['enrollment_id'],
            $log_details
        );
    }
    
    error_log("=== UNENROLL SUCCESS ===");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully unenrolled from ' . $enrollment['class_name']
    ]);
    
} catch (PDOException $e) {
    error_log("=== UNENROLL ERROR (PDO) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
    
} catch (Exception $e) {
    error_log("=== UNENROLL ERROR (General) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred'
    ]);
}
?>