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

// Get and sanitize input
$class_code = strtoupper(trim(sanitize($_POST['class_code'])));
$student_id = $_SESSION['user_id'];

// Validate input
if (empty($class_code)) {
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'Please enter a class code.'
    );
}

// Validate class code format (alphanumeric only)
if (!preg_match('/^[A-Z0-9]+$/', $class_code)) {
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'Invalid class code format. Please use only letters and numbers.'
    );
}

try {
    // Check if class code exists and is active
    $class = getClassByCode($conn, $class_code);
    
    if (!$class) {
        redirectWithMessage(
            BASE_URL . 'student/join-class.php', 
            'danger', 
            'Invalid class code. Please check and try again.'
        );
    }
    
    // Verify class is active
    if ($class['status'] !== 'active') {
        redirectWithMessage(
            BASE_URL . 'student/join-class.php', 
            'warning', 
            'This class is not currently active. Please contact your teacher.'
        );
    }
    
    // Check if student is already enrolled with active status
    if (isStudentEnrolled($conn, $student_id, $class['class_id'])) {
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
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id, $class['class_id']]);
    $existing_enrollment = $stmt->fetch();
    
    if ($existing_enrollment && $existing_enrollment['status'] === 'dropped') {
        // Reactivate the dropped enrollment
        $stmt = $conn->prepare("
            UPDATE enrollments 
            SET status = 'active', 
                updated_at = NOW() 
            WHERE enrollment_id = ?
        ");
        $stmt->execute([$existing_enrollment['enrollment_id']]);
        $enrollment_id = $existing_enrollment['enrollment_id'];
        $action_type = 'reactivated';
    } else {
        // Create new enrollment
        $stmt = $conn->prepare("
            INSERT INTO enrollments (student_id, class_id, status, created_at) 
            VALUES (?, ?, 'active', NOW())
        ");
        $stmt->execute([$student_id, $class['class_id']]);
        $enrollment_id = $conn->lastInsertId();
        $action_type = 'joined';
    }
    
    // Get teacher name for better notification
    $stmt = $conn->prepare("
        SELECT CONCAT(first_name, ' ', last_name) as teacher_name 
        FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$class['teacher_id']]);
    $teacher = $stmt->fetch();
    
    // Log the action with detailed information
    $log_details = json_encode([
        'class_code' => $class_code,
        'class_name' => $class['class_name'],
        'teacher_name' => $teacher['teacher_name'] ?? 'Unknown',
        'enrollment_id' => $enrollment_id,
        'action_type' => $action_type,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    logAudit(
        $conn,
        $student_id,
        ucfirst($action_type) . ' class: ' . $class['class_name'],
        $action_type === 'joined' ? 'join' : 'reactivate',
        'enrollments',
        $enrollment_id,
        $log_details
    );
    
    // Prepare success message
    $success_message = $action_type === 'joined' 
        ? 'Successfully joined ' . htmlspecialchars($class['class_name']) . '!' 
        : 'Welcome back! You have been re-enrolled in ' . htmlspecialchars($class['class_name']) . '.';
    
    // Redirect to dashboard with success message
    redirectWithMessage(
        BASE_URL . 'student/dashboard.php',
        'success',
        $success_message
    );
    
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Join Class Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // User-friendly error message
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'An error occurred while joining the class. Please try again or contact support if the problem persists.'
    );
} catch (Exception $e) {
    // Catch any other exceptions
    error_log("Unexpected error in join-class-handler: " . $e->getMessage());
    
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'An unexpected error occurred. Please try again later.'
    );
}
?>