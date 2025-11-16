<?php

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

requireStudent();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    redirectWithMessage(
        BASE_URL . 'student/my-courses.php',
        'danger',
        'Invalid request method'
    );
}

$student_id = $_SESSION['user_id'];
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

error_log("=== UNENROLL ATTEMPT ===");
error_log("Student ID: " . $student_id);
error_log("Class ID: " . $class_id);
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("POST data: " . json_encode($_POST));

// Validate input
if ($class_id <= 0) {
    error_log("Invalid class ID: " . $class_id);
    redirectWithMessage(
        BASE_URL . 'student/my-courses.php',
        'danger',
        'Invalid class ID provided'
    );
}

try {
    $conn->beginTransaction();
    error_log("Transaction started");
    
    // STEP 1: Verify enrollment exists and is active
    $query = "SELECT 
            e.enrollment_id,
            e.status,
            e.enrolled_at,
            c.class_name, 
            c.subject, 
            c.section,
            c.class_code
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.class_id
        WHERE e.student_id = ? 
        AND e.class_id = ? 
        AND e.status = 'active'
        LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$student_id, $class_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        $conn->rollBack();
        error_log("Enrollment not found or already inactive");
        
        $checkQuery = "SELECT enrollment_id, status 
            FROM enrollments 
            WHERE student_id = ? AND class_id = ?
            ORDER BY enrollment_id DESC
            LIMIT 1";
        
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$student_id, $class_id]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($checkResult) {
            error_log("Found enrollment with status: " . $checkResult['status']);
            
            if ($checkResult['status'] === 'dropped') {
                redirectWithMessage(
                    BASE_URL . 'student/my-courses.php',
                    'info',
                    'You have already unenrolled from this class'
                );
            }
        } else {
            error_log("No enrollment record found");
        }
        
        redirectWithMessage(
            BASE_URL . 'student/my-courses.php',
            'warning',
            'Enrollment not found or already inactive'
        );
    }
    
    error_log("Enrollment found: ID=" . $enrollment['enrollment_id']);
    error_log("Current status: " . $enrollment['status']);
    error_log("Class: " . $enrollment['class_name']);
    
    // STEP 2: Check for duplicate enrollments
    $dupQuery = "SELECT COUNT(*) as count 
        FROM enrollments 
        WHERE student_id = ? AND class_id = ? AND status = 'active'";
    
    $dupStmt = $conn->prepare($dupQuery);
    $dupStmt->execute([$student_id, $class_id]);
    $dupCheck = $dupStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dupCheck['count'] > 1) {
        error_log("WARNING: " . $dupCheck['count'] . " active enrollments found for this class!");
        error_log("This indicates a database integrity issue");
    }
    
    // STEP 3: Update enrollment status to 'dropped'
    $updateQuery = "UPDATE enrollments 
        SET status = 'dropped',
            updated_at = NOW()
        WHERE enrollment_id = ? 
        AND student_id = ? 
        AND status = 'active'";
    
    $updateStmt = $conn->prepare($updateQuery);
    $result = $updateStmt->execute([$enrollment['enrollment_id'], $student_id]);
    $rowsAffected = $updateStmt->rowCount();
    
    error_log("Update result: " . ($result ? 'true' : 'false'));
    error_log("Rows affected: " . $rowsAffected);
    
    if (!$result) {
        $conn->rollBack();
        error_log("Update failed - database error");
        throw new Exception("Database update failed");
    }
    
    if ($rowsAffected === 0) {
        $conn->rollBack();
        error_log("Update failed - no rows affected");
        
        $verifyQuery = "SELECT status 
            FROM enrollments 
            WHERE enrollment_id = ?";
        
        $verifyStmt = $conn->prepare($verifyQuery);
        $verifyStmt->execute([$enrollment['enrollment_id']]);
        $currentStatus = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Current status in DB: " . ($currentStatus['status'] ?? 'NULL'));
        
        throw new Exception("No rows were updated - enrollment may have changed");
    }
    
    error_log($rowsAffected . " row(s) updated");
    
    // STEP 4: Verify the update was successful
    $verifyQuery = "SELECT status, updated_at 
        FROM enrollments 
        WHERE enrollment_id = ?";
    
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->execute([$enrollment['enrollment_id']]);
    $verify = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Verification - New status: " . $verify['status']);
    error_log("Verification - Updated at: " . $verify['updated_at']);
    
    if ($verify['status'] !== 'dropped') {
        $conn->rollBack();
        error_log("Status verification failed - still: " . $verify['status']);
        throw new Exception("Status update verification failed");
    }
    
    error_log("Enrollment successfully updated to 'dropped'");
    
    // STEP 5: If there were duplicate active enrollments, drop them all
    if ($dupCheck['count'] > 1) {
        error_log("Cleaning up duplicate enrollments...");
        
        $cleanupQuery = "UPDATE enrollments 
            SET status = 'dropped',
                updated_at = NOW()
            WHERE student_id = ? 
            AND class_id = ? 
            AND status = 'active'";
        
        $cleanupStmt = $conn->prepare($cleanupQuery);
        $cleanupStmt->execute([$student_id, $class_id]);
        $cleanedCount = $cleanupStmt->rowCount();
        
        error_log("Cleaned up " . $cleanedCount . " additional duplicate enrollment(s)");
    }
    
    // STEP 6: Log audit trail
    if (function_exists('logAudit')) {
        $log_details = json_encode([
            'enrollment_id' => $enrollment['enrollment_id'],
            'class_id' => $class_id,
            'class_name' => $enrollment['class_name'],
            'subject' => $enrollment['subject'],
            'section' => $enrollment['section'],
            'class_code' => $enrollment['class_code'],
            'old_status' => 'active',
            'new_status' => 'dropped',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
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
        error_log("Audit log created");
    }
    
    $conn->commit();
    error_log("Transaction committed successfully");
    
    error_log("=== UNENROLL SUCCESS ===");
    error_log("Enrollment ID: " . $enrollment['enrollment_id']);
    error_log("Class: " . $enrollment['class_name']);
    error_log("Final status: dropped");
    
    // STEP 7: Redirect with success message
    redirectWithMessage(
        BASE_URL . 'student/my-courses.php',
        'success',
        'Successfully unenrolled from ' . htmlspecialchars($enrollment['class_name']) . '. You can rejoin anytime using the class code: ' . htmlspecialchars($enrollment['class_code'])
    );
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }
    
    error_log("=== UNENROLL ERROR (PDO) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("SQL State: " . ($e->errorInfo[0] ?? 'Unknown'));
    error_log("Stack trace: " . $e->getTraceAsString());
    
    redirectWithMessage(
        BASE_URL . 'student/my-courses.php',
        'danger',
        'Database error occurred while unenrolling. Please try again or contact support if the issue persists.'
    );
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }
    
    error_log("=== UNENROLL ERROR (General) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    redirectWithMessage(
        BASE_URL . 'student/my-courses.php',
        'danger',
        'An unexpected error occurred while unenrolling. Please try again.'
    );
}

error_log("WARNING: Reached end of unenroll-handler.php without redirect");
redirectWithMessage(
    BASE_URL . 'student/my-courses.php',
    'danger',
    'An unexpected error occurred'
);
?>