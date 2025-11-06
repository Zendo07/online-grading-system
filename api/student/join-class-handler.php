<?php
/**
 * Join Class Handler - COMPLETE FIXED VERSION
 * File: api/student/join-class-handler.php
 */

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

// Get and sanitize input
$class_code = strtoupper(trim(sanitize($_POST['class_code'])));
$student_id = $_SESSION['user_id'];

error_log("=== JOIN CLASS ATTEMPT ===");
error_log("Student ID: " . $student_id);
error_log("Class Code: " . $class_code);

// Validate input
if (empty($class_code)) {
    error_log("Join failed: Empty class code");
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'Please enter a class code.'
    );
}

// Validate class code format (alphanumeric and dashes only)
if (!preg_match('/^[A-Z0-9-]+$/', $class_code)) {
    error_log("Join failed: Invalid format - " . $class_code);
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'Invalid class code format. Please use only letters, numbers, and dashes.'
    );
}

try {
    // Check if class code exists and get class details
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.full_name as teacher_name
        FROM classes c
        JOIN users u ON c.teacher_id = u.user_id
        WHERE c.class_code = ? AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$class_code]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        // Log detailed error for debugging
        error_log("Join failed: Class not found or inactive - Code: " . $class_code);
        
        // Check if class exists but is archived
        $stmt = $conn->prepare("SELECT class_id, status FROM classes WHERE class_code = ?");
        $stmt->execute([$class_code]);
        $archived = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($archived) {
            error_log("Class exists but status is: " . $archived['status']);
            redirectWithMessage(
                BASE_URL . 'student/join-class.php', 
                'danger', 
                'This class is no longer active. Please contact your teacher.'
            );
        }
        
        redirectWithMessage(
            BASE_URL . 'student/join-class.php', 
            'danger', 
            'Invalid class code. Please check and try again.'
        );
    }
    
    error_log("Class found: ID=" . $class['class_id'] . ", Name=" . $class['class_name']);
    
    // Check if student is already enrolled with active status
    $stmt = $conn->prepare("
        SELECT enrollment_id, status 
        FROM enrollments 
        WHERE student_id = ? AND class_id = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$student_id, $class['class_id']]);
    $active_enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($active_enrollment) {
        error_log("Join failed: Already enrolled - Enrollment ID: " . $active_enrollment['enrollment_id']);
        redirectWithMessage(
            BASE_URL . 'student/join-class.php', 
            'warning', 
            'You are already enrolled in this class.'
        );
    }
    
    // Check if student was previously dropped from this class
    $stmt = $conn->prepare("
        SELECT enrollment_id, status 
        FROM enrollments 
        WHERE student_id = ? AND class_id = ?
        ORDER BY enrolled_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id, $class['class_id']]);
    $existing_enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_enrollment && $existing_enrollment['status'] === 'dropped') {
        // Reactivate the dropped enrollment
        $stmt = $conn->prepare("
            UPDATE enrollments 
            SET status = 'active', 
                enrolled_at = NOW() 
            WHERE enrollment_id = ?
        ");
        $stmt->execute([$existing_enrollment['enrollment_id']]);
        $enrollment_id = $existing_enrollment['enrollment_id'];
        $action_type = 'reactivated';
        
        error_log("Enrollment reactivated: ID=" . $enrollment_id);
    } else {
        // Create new enrollment
        $stmt = $conn->prepare("
            INSERT INTO enrollments (student_id, class_id, status, enrolled_at) 
            VALUES (?, ?, 'active', NOW())
        ");
        $stmt->execute([$student_id, $class['class_id']]);
        $enrollment_id = $conn->lastInsertId();
        $action_type = 'joined';
        
        error_log("New enrollment created: ID=" . $enrollment_id);
    }
    
    // Log the action with detailed information
    $log_details = json_encode([
        'class_code' => $class_code,
        'class_id' => $class['class_id'],
        'class_name' => $class['class_name'],
        'subject' => $class['subject'],
        'section' => $class['section'],
        'teacher_name' => $class['teacher_name'],
        'enrollment_id' => $enrollment_id,
        'action_type' => $action_type,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log audit if function exists
    if (function_exists('logAudit')) {
        logAudit(
            $conn,
            $student_id,
            ucfirst($action_type) . ' class: ' . $class['class_name'],
            $action_type === 'joined' ? 'join' : 'update',
            'enrollments',
            $enrollment_id,
            $log_details
        );
    }
    
    // Prepare success message with more details
    $success_message = $action_type === 'joined' 
        ? 'Successfully joined ' . htmlspecialchars($class['subject']) . ' - ' . 
          htmlspecialchars($class['class_name']) . ' (Section: ' . htmlspecialchars($class['section']) . ')!' 
        : 'Welcome back! You have been re-enrolled in ' . htmlspecialchars($class['class_name']) . '.';
    
    // Store success data in session for better UX
    $_SESSION['last_joined_class'] = [
        'class_id' => $class['class_id'],
        'class_name' => $class['class_name'],
        'subject' => $class['subject'],
        'section' => $class['section'],
        'teacher_name' => $class['teacher_name'],
        'enrolled_at' => date('Y-m-d H:i:s')
    ];
    
    error_log("=== JOIN CLASS SUCCESS ===");
    error_log("Redirecting to dashboard");
    
    // Redirect to dashboard with success message
    redirectWithMessage(
        BASE_URL . 'student/dashboard.php',
        'success',
        $success_message
    );
    
} catch (PDOException $e) {
    // Log the detailed error for debugging
    error_log("=== JOIN CLASS ERROR (PDO) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("Class Code Attempted: " . $class_code);
    error_log("Student ID: " . $student_id);
    
    // User-friendly error message
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'An error occurred while joining the class. Please try again or contact support if the problem persists.'
    );
} catch (Exception $e) {
    // Catch any other exceptions
    error_log("=== JOIN CLASS ERROR (General) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'An unexpected error occurred. Please try again later.'
    );
}
?>