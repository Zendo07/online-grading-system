<?php

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'student/join-class.php');
    exit();
}

$class_code = strtoupper(trim(sanitize($_POST['class_code'])));
$student_id = $_SESSION['user_id'];

error_log("=== JOIN CLASS ATTEMPT ===");
error_log("Student ID: " . $student_id);
error_log("Class Code: " . $class_code);
error_log("Timestamp: " . date('Y-m-d H:i:s'));

if (empty($class_code)) {
    error_log("✗ Empty class code");
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'Please enter a class code.'
    );
}

if (!preg_match('/^[A-Z0-9-]+$/', $class_code)) {
    error_log("✗ Invalid format: " . $class_code);
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'Invalid class code format.'
    );
}

try {
    $conn->beginTransaction();
    error_log("✓ Transaction started");
    
    //Find the class
    $stmt = $conn->prepare("
        SELECT 
            c.class_id,
            c.class_name,
            c.subject,
            c.section,
            c.status,
            u.full_name as teacher_name
        FROM classes c
        JOIN users u ON c.teacher_id = u.user_id
        WHERE c.class_code = ? AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$class_code]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        $conn->rollBack();
        error_log("✗ Class not found or inactive: " . $class_code);
        redirectWithMessage(
            BASE_URL . 'student/join-class.php', 
            'danger', 
            'Invalid class code or class is not active.'
        );
    }
    
    error_log("✓ Class found: ID=" . $class['class_id'] . ", Name=" . $class['class_name']);
    
    $stmt = $conn->prepare("
        SELECT 
            enrollment_id, 
            status,
            enrolled_at,
            updated_at
        FROM enrollments 
        WHERE student_id = ? AND class_id = ?
        ORDER BY enrollment_id DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id, $class['class_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        error_log("📋 Found existing enrollment: ID=" . $existing['enrollment_id'] . ", Status=" . $existing['status']);
        
        if ($existing['status'] === 'active') {
            $conn->rollBack();
            error_log("⚠️ Already enrolled - Status: active");
            redirectWithMessage(
                BASE_URL . 'student/my-courses.php', 
                'warning', 
                'You are already enrolled in ' . htmlspecialchars($class['class_name']) . '.'
            );
        } else {
            // Reactivate dropped/archived enrollment
            error_log("🔄 Reactivating enrollment: " . $existing['enrollment_id']);
            
            $stmt = $conn->prepare("
                UPDATE enrollments 
                SET status = 'active', 
                    enrolled_at = NOW(),
                    updated_at = NOW()
                WHERE enrollment_id = ? AND student_id = ?
            ");
            $result = $stmt->execute([$existing['enrollment_id'], $student_id]);
            
            if (!$result) {
                throw new Exception("Failed to reactivate enrollment");
            }
            
            // Verify update
            $stmt = $conn->prepare("SELECT status FROM enrollments WHERE enrollment_id = ?");
            $stmt->execute([$existing['enrollment_id']]);
            $verify = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verify['status'] !== 'active') {
                throw new Exception("Enrollment reactivation verification failed");
            }
            
            error_log("✓ Enrollment reactivated and verified");
            $enrollment_id = $existing['enrollment_id'];
            $action_type = 'rejoined';
        }
    } else {
        error_log("➕ Creating new enrollment");
        
        // Double-check for race conditions
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM enrollments 
            WHERE student_id = ? AND class_id = ?
        ");
        $stmt->execute([$student_id, $class['class_id']]);
        $doubleCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doubleCheck['count'] > 0) {
            $conn->rollBack();
            error_log("⚠️ Race condition detected - enrollment exists");
            redirectWithMessage(
                BASE_URL . 'student/my-courses.php', 
                'warning', 
                'You are already enrolled in this class.'
            );
        }
        
        $stmt = $conn->prepare("
            INSERT INTO enrollments (student_id, class_id, status, enrolled_at) 
            VALUES (?, ?, 'active', NOW())
        ");
        
        $result = $stmt->execute([$student_id, $class['class_id']]);
        
        if (!$result) {
            throw new Exception("Failed to create enrollment");
        }
        
        $enrollment_id = $conn->lastInsertId();
        
        if (!$enrollment_id) {
            throw new Exception("Failed to get enrollment ID");
        }
        
        // Verify insertion
        $stmt = $conn->prepare("
            SELECT enrollment_id, status 
            FROM enrollments 
            WHERE enrollment_id = ?
        ");
        $stmt->execute([$enrollment_id]);
        $verify = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verify || $verify['status'] !== 'active') {
            throw new Exception("Enrollment creation verification failed");
        }
        
        error_log("✓ New enrollment created and verified: ID=" . $enrollment_id);
        $action_type = 'joined';
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM enrollments 
        WHERE student_id = ? AND class_id = ?
    ");
    $stmt->execute([$student_id, $class['class_id']]);
    $finalCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($finalCheck['count'] > 1) {
        $conn->rollBack();
        error_log("✗ CRITICAL: Multiple enrollments detected (" . $finalCheck['count'] . ")");
        throw new Exception("Database integrity error: duplicate enrollments detected");
    }
    
    error_log("✓ Final verification passed - exactly 1 enrollment exists");
    
    if (function_exists('logAudit')) {
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
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        logAudit(
            $conn,
            $student_id,
            ucfirst($action_type) . ' class: ' . $class['class_name'],
            $action_type === 'joined' ? 'create' : 'update',
            'enrollments',
            $enrollment_id,
            $log_details
        );
        
        error_log("✓ Audit log created");
    }
    
    // Commit transaction
    $conn->commit();
    error_log("✓ Transaction committed successfully");
    
    $success_message = $action_type === 'joined' 
        ? '🎉 Successfully joined ' . htmlspecialchars($class['class_name']) . 
          ' (' . htmlspecialchars($class['section']) . ')!' 
        : '👋 Welcome back! You have been re-enrolled in ' . 
          htmlspecialchars($class['class_name']) . '.';
    
    error_log("=== JOIN CLASS SUCCESS ===");
    error_log("Action: " . $action_type);
    error_log("Enrollment ID: " . $enrollment_id);
    error_log("Class ID: " . $class['class_id']);
    
    redirectWithMessage(
        BASE_URL . 'student/my-courses.php',
        'success',
        $success_message
    );
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
        error_log("✓ Transaction rolled back");
    }
    
    error_log("=== JOIN CLASS ERROR (PDO) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("SQL State: " . ($e->errorInfo[0] ?? 'Unknown'));
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Handle duplicate entry errors
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        error_log("⚠️ Duplicate entry detected by database");
        redirectWithMessage(
            BASE_URL . 'student/my-courses.php', 
            'warning', 
            'You are already enrolled in this class.'
        );
    }
    
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'Database error occurred. Please try again.'
    );
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
        error_log("✓ Transaction rolled back");
    }
    
    error_log("=== JOIN CLASS ERROR (General) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    redirectWithMessage(
        BASE_URL . 'student/join-class.php', 
        'danger', 
        'An unexpected error occurred. Please try again.'
    );
}
?>