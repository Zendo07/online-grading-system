<?php
/**
 * Create Class Handler - COMPLETE FIXED VERSION
 * File: api/teacher/create-class-handler.php
 */

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

// Get teacher ID from session
$teacher_id = $_SESSION['user_id'];

// Get and sanitize input
$class_name = trim(sanitize($_POST['class_name']));
$subject = trim(sanitize($_POST['subject']));
$section = trim(sanitize($_POST['section']));

// Get schedules array if provided
$schedules = isset($_POST['schedules']) ? $_POST['schedules'] : [];

// Validation
$errors = [];

if (empty($class_name)) {
    $errors[] = 'Class name is required.';
}

if (empty($subject)) {
    $errors[] = 'Subject is required.';
}

if (empty($section)) {
    $errors[] = 'Section is required.';
}

// Validate schedules if provided
if (!empty($schedules)) {
    foreach ($schedules as $index => $schedule) {
        // Check if any field in this schedule is filled
        if (!empty($schedule['day']) || !empty($schedule['start_time']) || !empty($schedule['end_time'])) {
            if (empty($schedule['day'])) {
                $errors[] = "Schedule #" . ($index + 1) . ": Day is required.";
            }
            if (empty($schedule['start_time'])) {
                $errors[] = "Schedule #" . ($index + 1) . ": Start time is required.";
            }
            if (empty($schedule['end_time'])) {
                $errors[] = "Schedule #" . ($index + 1) . ": End time is required.";
            }
            
            // Validate time logic
            if (!empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                if ($schedule['start_time'] >= $schedule['end_time']) {
                    $errors[] = "Schedule #" . ($index + 1) . ": End time must be after start time.";
                }
            }
        }
    }
}

// If there are validation errors, return to form
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . 'teacher/create-class.php');
    exit();
}

try {
    // Start database transaction
    $conn->beginTransaction();
    
    // Generate unique class code
    $class_code = generateUniqueClassCode($conn, 'PSU');
    
    // Log for debugging
    error_log("=== CREATE CLASS START ===");
    error_log("Teacher ID: " . $teacher_id);
    error_log("Class Name: " . $class_name);
    error_log("Subject: " . $subject);
    error_log("Section: " . $section);
    error_log("Generated Class Code: " . $class_code);
    
    // Double-check the code is unique
    $check_stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_code = ?");
    $check_stmt->execute([$class_code]);
    if ($check_stmt->rowCount() > 0) {
        throw new Exception("Generated class code is not unique: {$class_code}");
    }
    
    // Insert class into database
    $stmt = $conn->prepare("
        INSERT INTO classes (
            teacher_id, 
            class_name, 
            subject, 
            section, 
            class_code, 
            status, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    
    $result = $stmt->execute([
        $teacher_id,
        $class_name,
        $subject,
        $section,
        $class_code
    ]);
    
    if (!$result) {
        throw new Exception("Failed to insert class into database");
    }
    
    // Get the newly created class ID
    $class_id = $conn->lastInsertId();
    
    if (!$class_id) {
        throw new Exception("Failed to retrieve class ID after insertion");
    }
    
    error_log("Class inserted with ID: " . $class_id);
    
    // Verify the class code was actually saved
    $verify_stmt = $conn->prepare("SELECT class_code, class_name FROM classes WHERE class_id = ?");
    $verify_stmt->execute([$class_id]);
    $verified = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$verified || $verified['class_code'] !== $class_code) {
        throw new Exception("Class code verification failed. Expected: {$class_code}, Got: " . ($verified['class_code'] ?? 'NULL'));
    }
    
    error_log("Class code verified: " . $verified['class_code']);
    
    // Insert schedules if provided (using 'schedules' table from your database)
    $schedule_count = 0;
    if (!empty($schedules)) {
        foreach ($schedules as $schedule) {
            // Only insert if all required fields are present
            if (!empty($schedule['day']) && !empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                $schedule_stmt = $conn->prepare("
                    INSERT INTO schedules (
                        class_id, 
                        day_of_week, 
                        start_time, 
                        end_time, 
                        room, 
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $schedule_result = $schedule_stmt->execute([
                    $class_id,
                    $schedule['day'],
                    $schedule['start_time'],
                    $schedule['end_time'],
                    isset($schedule['room']) ? trim($schedule['room']) : null
                ]);
                
                if ($schedule_result) {
                    $schedule_count++;
                    error_log("Schedule inserted: {$schedule['day']} {$schedule['start_time']}-{$schedule['end_time']}");
                }
            }
        }
    }
    
    error_log("Total schedules inserted: " . $schedule_count);
    
    // Log audit trail
    if (function_exists('logAudit')) {
        $log_details = json_encode([
            'class_id' => $class_id,
            'class_code' => $class_code,
            'class_name' => $class_name,
            'subject' => $subject,
            'section' => $section,
            'schedule_count' => $schedule_count,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        logAudit(
            $conn,
            $teacher_id,
            "Created class: {$class_name} - {$section}",
            'create',
            'classes',
            $class_id,
            $log_details
        );
    }
    
    // Commit the transaction
    $conn->commit();
    
    error_log("Transaction committed successfully");
    
    // Store success data in session for the success modal
    $_SESSION['new_class_code'] = $class_code;
    $_SESSION['new_class_name'] = $class_name;
    $_SESSION['new_class_section'] = $section;
    
    error_log("=== CREATE CLASS SUCCESS ===");
    error_log("Redirecting to my-courses.php with code: " . $class_code);
    
    // Redirect with success message
    setFlashMessage('success', 'Class created successfully!');
    header('Location: ' . BASE_URL . 'teacher/my-courses.php?show_code=1');
    exit();
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log detailed error for debugging
    error_log("=== CREATE CLASS ERROR (PDO) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // User-friendly error message
    setFlashMessage('error', 'Database error: Unable to create class. Please try again or contact support.');
    header('Location: ' . BASE_URL . 'teacher/create-class.php');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error
    error_log("=== CREATE CLASS ERROR (General) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Show specific error to help debugging
    setFlashMessage('error', 'Error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'teacher/create-class.php');
    exit();
}
?>