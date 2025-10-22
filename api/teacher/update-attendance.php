<?php
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

// Require teacher access
requireTeacher();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'teacher/attendance.php');
    exit();
}

$class_id = (int)$_POST['class_id'];
$attendance_date = sanitize($_POST['attendance_date']);
$students = $_POST['students'];
$teacher_id = $_SESSION['user_id'];

// Validate input
if (empty($class_id) || empty($attendance_date) || empty($students)) {
    redirectWithMessage(BASE_URL . 'teacher/attendance.php', 'danger', 'Invalid data submitted.');
}

try {
    // Verify teacher owns this class
    $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    
    if ($stmt->rowCount() === 0) {
        redirectWithMessage(BASE_URL . 'teacher/attendance.php', 'danger', 'Unauthorized access.');
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    $success_count = 0;
    
    foreach ($students as $student_id => $data) {
        $student_id = (int)$student_id;
        $status = sanitize($data['status']);
        $remarks = isset($data['remarks']) ? sanitize($data['remarks']) : null;
        
        // Validate status
        if (!in_array($status, ['present', 'absent', 'late', 'excused'])) {
            continue;
        }
        
        // Check if attendance already exists for this date
        $stmt = $conn->prepare("
            SELECT attendance_id 
            FROM attendance 
            WHERE student_id = ? AND class_id = ? AND DATE(attendance_date) = ?
        ");
        $stmt->execute([$student_id, $class_id, $attendance_date]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing attendance
            $stmt = $conn->prepare("
                UPDATE attendance 
                SET status = ?, remarks = ?, recorded_by = ?
                WHERE attendance_id = ?
            ");
            $stmt->execute([$status, $remarks, $teacher_id, $existing['attendance_id']]);
        } else {
            // Insert new attendance
            $stmt = $conn->prepare("
                INSERT INTO attendance (student_id, class_id, attendance_date, status, remarks, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $class_id, $attendance_date, $status, $remarks, $teacher_id]);
        }
        
        $success_count++;
    }
    
    // Log the action
    logAudit(
        $conn,
        $teacher_id,
        'Updated attendance records',
        'update',
        'attendance',
        $class_id,
        "Updated attendance for $success_count students on $attendance_date"
    );
    
    // Commit transaction
    $conn->commit();
    
    redirectWithMessage(
        BASE_URL . 'teacher/attendance.php?class_id=' . $class_id . '&date=' . $attendance_date,
        'success',
        "Attendance saved successfully for $success_count students!"
    );
    
} catch (PDOException $e) {
    // Rollback on error
    $conn->rollBack();
    error_log("Update Attendance Error: " . $e->getMessage());
    redirectWithMessage(
        BASE_URL . 'teacher/attendance.php?class_id=' . $class_id . '&date=' . $attendance_date,
        'danger',
        'An error occurred while saving attendance. Please try again.'
    );
}
?>