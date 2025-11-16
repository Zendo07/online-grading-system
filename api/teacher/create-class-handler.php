<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

requireTeacher();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ Not a POST request");
    header('Location: ' . BASE_URL . 'teacher/create-class.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get and sanitize input
$class_name = trim(sanitize($_POST['class_name']));
$subject = trim(sanitize($_POST['subject']));
$section = trim(sanitize($_POST['section']));
$schedules = isset($_POST['schedules']) ? $_POST['schedules'] : [];

error_log("=== CREATE CLASS ATTEMPT ===");
error_log("Teacher ID: " . $teacher_id);
error_log("Class Name: " . $class_name);
error_log("Subject: " . $subject);
error_log("Section: " . $section);
error_log("Schedules Received: " . count($schedules));
error_log("Schedules Data: " . print_r($schedules, true));

// Validation
$errors = [];

if (empty($class_name)) {
    $errors[] = 'Class name is required.';
    error_log("❌ Class name empty");
}

if (empty($subject)) {
    $errors[] = 'Subject is required.';
    error_log("❌ Subject empty");
}

if (empty($section)) {
    $errors[] = 'Section is required.';
    error_log("❌ Section empty");
}

// Validate schedules if provided
if (!empty($schedules)) {
    foreach ($schedules as $index => $schedule) {
        error_log("Validating schedule #" . ($index + 1) . ": " . print_r($schedule, true));
        
        if (!empty($schedule['day']) || !empty($schedule['start_time']) || !empty($schedule['end_time'])) {
            if (empty($schedule['day'])) {
                $errors[] = "Schedule #" . ($index + 1) . ": Day is required.";
                error_log("❌ Schedule #" . ($index + 1) . ": Day missing");
            }
            if (empty($schedule['start_time'])) {
                $errors[] = "Schedule #" . ($index + 1) . ": Start time is required.";
                error_log("❌ Schedule #" . ($index + 1) . ": Start time missing");
            }
            if (empty($schedule['end_time'])) {
                $errors[] = "Schedule #" . ($index + 1) . ": End time is required.";
                error_log("❌ Schedule #" . ($index + 1) . ": End time missing");
            }
            
            if (!empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                if ($schedule['start_time'] >= $schedule['end_time']) {
                    $errors[] = "Schedule #" . ($index + 1) . ": End time must be after start time.";
                    error_log("❌ Schedule #" . ($index + 1) . ": Invalid time range");
                }
            }
        }
    }
}

// Return errors if validation fails
if (!empty($errors)) {
    error_log("❌ Validation failed: " . implode(', ', $errors));
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: ' . BASE_URL . 'teacher/create-class.php');
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();
    error_log("✓ Transaction started");
    
    // Generate unique class code
    $class_code = generateUniqueClassCode($conn, 'PSU');
    error_log("✓ Generated code: " . $class_code);
    
    // Verify code is unique
    $checkStmt = $conn->prepare("SELECT class_id FROM classes WHERE class_code = ?");
    $checkStmt->execute([$class_code]);
    if ($checkStmt->rowCount() > 0) {
        throw new Exception("Generated class code is not unique: {$class_code}");
    }
    error_log("✓ Code uniqueness verified");
    
    // Insert class
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
        throw new Exception("Failed to insert class");
    }
    
    // Get class ID
    $class_id = $conn->lastInsertId();
    
    if (!$class_id) {
        throw new Exception("Failed to retrieve class ID");
    }
    
    error_log("✓ Class created with ID: " . $class_id);
    
    // Verify class was inserted
    $verifyStmt = $conn->prepare("SELECT class_code FROM classes WHERE class_id = ?");
    $verifyStmt->execute([$class_id]);
    $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$verified || $verified['class_code'] !== $class_code) {
        throw new Exception("Class insertion verification failed");
    }
    
    error_log("✓ Class insertion verified");
    
    // Insert schedules into class_schedules table
    $schedule_count = 0;
    if (!empty($schedules)) {
        error_log("📅 Processing " . count($schedules) . " schedule(s)");
        
        foreach ($schedules as $index => $schedule) {
            error_log("Processing schedule #" . ($index + 1) . ": " . print_r($schedule, true));
            
            if (!empty($schedule['day']) && !empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                
                $scheduleStmt = $conn->prepare("
                    INSERT INTO class_schedules (
                        class_id, 
                        day_of_week, 
                        start_time, 
                        end_time, 
                        room, 
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $room = isset($schedule['room']) ? trim($schedule['room']) : null;
                
                $scheduleResult = $scheduleStmt->execute([
                    $class_id,
                    $schedule['day'],
                    $schedule['start_time'],
                    $schedule['end_time'],
                    $room
                ]);
                
                if ($scheduleResult) {
                    $schedule_count++;
                    error_log("✓ Schedule #" . ($index + 1) . " inserted: {$schedule['day']} {$schedule['start_time']}-{$schedule['end_time']}");
                } else {
                    error_log("❌ Failed to insert schedule #" . ($index + 1));
                    $errorInfo = $scheduleStmt->errorInfo();
                    error_log("SQL Error: " . print_r($errorInfo, true));
                }
            } else {
                error_log("⚠️ Schedule #" . ($index + 1) . " skipped - incomplete data");
            }
        }
    } else {
        error_log("ℹ️ No schedules to process");
    }
    
    error_log("✓ Total schedules inserted: " . $schedule_count);
    
    // Verify schedules were inserted
    $verifySchedulesStmt = $conn->prepare("SELECT COUNT(*) as count FROM class_schedules WHERE class_id = ?");
    $verifySchedulesStmt->execute([$class_id]);
    $scheduleVerify = $verifySchedulesStmt->fetch(PDO::FETCH_ASSOC);
    error_log("✓ Schedules in database: " . $scheduleVerify['count']);
    
    if (function_exists('logAudit')) {
        logAudit(
            $conn,
            $teacher_id,
            "Created class: {$class_name} - {$section}",
            'create',
            'classes',
            $class_id,
            json_encode([
                'class_id' => $class_id,
                'class_code' => $class_code,
                'class_name' => $class_name,
                'subject' => $subject,
                'section' => $section,
                'schedule_count' => $schedule_count,
                'timestamp' => date('Y-m-d H:i:s')
            ])
        );
        error_log("✓ Audit log created");
    }
    
    $conn->commit();
    error_log("✓ Transaction committed");
    
    // Store data in session for modal
    $_SESSION['new_class_code'] = $class_code;
    $_SESSION['new_class_name'] = $class_name;
    $_SESSION['new_class_section'] = $section;
    
    error_log("=== CREATE CLASS SUCCESS ===");
    error_log("Class ID: " . $class_id);
    error_log("Class Code: " . $class_code);
    error_log("Schedules: " . $schedule_count);
    
    // Redirect with success
    setFlashMessage('success', 'Class created successfully!');
    header('Location: ' . BASE_URL . 'teacher/my-courses.php?show_code=1');
    exit();
    
} catch (PDOException $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
        error_log("✓ Transaction rolled back");
    }
    
    error_log("=== CREATE CLASS ERROR (PDO) ===");
    error_log("Error Code: " . $e->getCode());
    error_log("Error Message: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    setFlashMessage('error', 'Database error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'teacher/create-class.php');
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
        error_log("✓ Transaction rolled back");
    }
    
    error_log("=== CREATE CLASS ERROR (General) ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    setFlashMessage('error', 'Error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'teacher/create-class.php');
    exit();
}
?>