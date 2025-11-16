<?php

function getClassInfo($class_id, $teacher_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                c.class_id,
                c.class_name,
                c.subject,
                c.section,
                c.class_code,
                c.created_at,
                COUNT(DISTINCT e.student_id) as student_count
            FROM classes c
            LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
            WHERE c.class_id = :class_id AND c.teacher_id = :teacher_id AND c.status = 'active'
            GROUP BY c.class_id
        ");
        
        $stmt->execute([
            ':class_id' => $class_id,
            ':teacher_id' => $teacher_id
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getClassInfo error: " . $e->getMessage());
        return null;
    }
}

function getEnrolledStudents($class_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                u.user_id,
                u.full_name,
                u.middle_name,
                u.email,
                u.program,
                u.year_section,
                e.enrolled_at,
                e.enrollment_id
            FROM enrollments e
            JOIN users u ON e.student_id = u.user_id
            WHERE e.class_id = :class_id AND e.status = 'active'
            ORDER BY u.full_name ASC
        ");
        
        $stmt->execute([':class_id' => $class_id]);
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format names as Surname, Firstname
        foreach ($students as &$student) {
            $nameParts = explode(' ', trim($student['full_name']));
            if (count($nameParts) >= 2) {
                $surname = array_pop($nameParts);
                $firstname = implode(' ', $nameParts);
                $middleInitial = !empty($student['middle_name']) ? ' ' . substr($student['middle_name'], 0, 1) . '.' : '';
                $student['formatted_name'] = $surname . ', ' . $firstname . $middleInitial;
            } else {
                $student['formatted_name'] = $student['full_name'];
            }
        }
        
        return $students;
    } catch (PDOException $e) {
        error_log("getEnrolledStudents error: " . $e->getMessage());
        return [];
    }
}

function initializeDefaultGrades($student_id, $class_id, $grading_period, $teacher_id) {
    global $conn;
    
    $defaultColumns = [
        ['name' => 'Recitation', 'type' => 'recitation', 'max' => 100],
        ['name' => 'Quiz 1', 'type' => 'quiz', 'max' => 100],
        ['name' => 'Activity 1', 'type' => 'assignment', 'max' => 100],
        ['name' => 'Project 1', 'type' => 'project', 'max' => 100],
        ['name' => ($grading_period === 'finals' ? 'Final Exam' : 'Midterm Exam'), 'type' => 'exam', 'max' => 100]
    ];
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO grades (student_id, class_id, activity_name, activity_type, score, max_score, percentage, grading_period, recorded_by)
            VALUES (:student_id, :class_id, :activity_name, :activity_type, 0, :max_score, 0, :grading_period, :recorded_by)
            ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
        ");
        
        foreach ($defaultColumns as $col) {
            // Check if already exists
            $checkStmt = $conn->prepare("
                SELECT grade_id FROM grades 
                WHERE student_id = :student_id 
                AND class_id = :class_id 
                AND activity_name = :activity_name
                AND grading_period = :grading_period
            ");
            $checkStmt->execute([
                ':student_id' => $student_id,
                ':class_id' => $class_id,
                ':activity_name' => $col['name'],
                ':grading_period' => $grading_period
            ]);
            
            if ($checkStmt->rowCount() === 0) {
                $stmt->execute([
                    ':student_id' => $student_id,
                    ':class_id' => $class_id,
                    ':activity_name' => $col['name'],
                    ':activity_type' => $col['type'],
                    ':max_score' => $col['max'],
                    ':grading_period' => $grading_period,
                    ':recorded_by' => $teacher_id
                ]);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("initializeDefaultGrades error: " . $e->getMessage());
        return false;
    }
}

function getClassGrades($class_id, $grading_period = 'prelim') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                g.grade_id,
                g.student_id,
                g.activity_name,
                g.activity_type,
                g.score,
                g.max_score,
                g.percentage,
                g.remarks,
                g.grading_period,
                g.created_at,
                g.updated_at
            FROM grades g
            WHERE g.class_id = :class_id AND g.grading_period = :grading_period
            ORDER BY g.created_at
        ");
        
        $stmt->execute([
            ':class_id' => $class_id,
            ':grading_period' => $grading_period
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getClassGrades error: " . $e->getMessage());
        return [];
    }
}

function getClassAttendance($class_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                a.attendance_id,
                a.student_id,
                a.attendance_date,
                a.status,
                a.remarks,
                a.created_at
            FROM attendance a
            WHERE a.class_id = :class_id
            ORDER BY a.attendance_date DESC, a.student_id
        ");
        
        $stmt->execute([':class_id' => $class_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getClassAttendance error: " . $e->getMessage());
        return [];
    }
}

function getAttendanceDates($class_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT attendance_date
            FROM attendance
            WHERE class_id = :class_id
            ORDER BY attendance_date ASC
        ");
        
        $stmt->execute([':class_id' => $class_id]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("getAttendanceDates error: " . $e->getMessage());
        return [];
    }
}

function getActivityColumns($class_id, $grading_period = 'prelim') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT activity_name, activity_type, MAX(max_score) as max_score
            FROM grades
            WHERE class_id = :class_id AND grading_period = :grading_period
            GROUP BY activity_name, activity_type
            ORDER BY 
                CASE activity_type
                    WHEN 'recitation' THEN 1
                    WHEN 'quiz' THEN 2
                    WHEN 'assignment' THEN 3
                    WHEN 'project' THEN 4
                    WHEN 'exam' THEN 5
                    ELSE 6
                END,
                activity_name ASC
        ");
        
        $stmt->execute([
            ':class_id' => $class_id,
            ':grading_period' => $grading_period
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getActivityColumns error: " . $e->getMessage());
        return [];
    }
}

function addAttendanceDate($class_id, $attendance_date, $teacher_id) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        // Check if date already exists
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM attendance
            WHERE class_id = :class_id AND attendance_date = :attendance_date
        ");
        $stmt->execute([
            ':class_id' => $class_id,
            ':attendance_date' => $attendance_date
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            $conn->rollBack();
            return ['success' => false, 'message' => 'Attendance for this date already exists'];
        }
        
        $students = getEnrolledStudents($class_id);
        
        if (empty($students)) {
            $conn->rollBack();
            return ['success' => false, 'message' => 'No students enrolled in this class'];
        }
        
        $stmt = $conn->prepare("
            INSERT INTO attendance (student_id, class_id, attendance_date, status, recorded_by)
            VALUES (:student_id, :class_id, :attendance_date, 'present', :recorded_by)
        ");
        
        foreach ($students as $student) {
            $stmt->execute([
                ':student_id' => $student['user_id'],
                ':class_id' => $class_id,
                ':attendance_date' => $attendance_date,
                ':recorded_by' => $teacher_id
            ]);
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Attendance date added successfully'];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("addAttendanceDate error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add attendance date'];
    }
}

//Add a new grade column for all students
 
function addGradeColumn($class_id, $activity_type, $max_score, $grading_period, $teacher_id) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        // Get count of existing columns of this type
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT activity_name) as count
            FROM grades
            WHERE class_id = :class_id 
            AND activity_type = :activity_type
            AND grading_period = :grading_period
            AND activity_name LIKE :pattern
        ");
        
        $typeLabel = ucfirst($activity_type);
        $pattern = $typeLabel . ' %';
        
        $stmt->execute([
            ':class_id' => $class_id,
            ':activity_type' => $activity_type,
            ':grading_period' => $grading_period,
            ':pattern' => $pattern
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNumber = ($result['count'] ?? 0) + 1;
        
        $activity_name = $typeLabel . ' ' . $nextNumber;
        
        // Get all enrolled students
        $students = getEnrolledStudents($class_id);
        
        if (empty($students)) {
            $conn->rollBack();
            return ['success' => false, 'message' => 'No students enrolled in this class'];
        }
        
        // Add grade record for each student
        $stmt = $conn->prepare("
            INSERT INTO grades (student_id, class_id, activity_name, activity_type, score, max_score, percentage, grading_period, recorded_by)
            VALUES (:student_id, :class_id, :activity_name, :activity_type, 0, :max_score, 0, :grading_period, :recorded_by)
        ");
        
        foreach ($students as $student) {
            $stmt->execute([
                ':student_id' => $student['user_id'],
                ':class_id' => $class_id,
                ':activity_name' => $activity_name,
                ':activity_type' => $activity_type,
                ':max_score' => $max_score,
                ':grading_period' => $grading_period,
                ':recorded_by' => $teacher_id
            ]);
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Grade column added successfully', 'activity_name' => $activity_name];
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("addGradeColumn error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add grade column'];
    }
}

//Delete attendance column
function deleteAttendanceDate($class_id, $attendance_date, $teacher_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT class_id FROM classes 
            WHERE class_id = :class_id AND teacher_id = :teacher_id
        ");
        $stmt->execute([
            ':class_id' => $class_id,
            ':teacher_id' => $teacher_id
        ]);
        
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $stmt = $conn->prepare("
            DELETE FROM attendance
            WHERE class_id = :class_id AND attendance_date = :attendance_date
        ");
        
        $stmt->execute([
            ':class_id' => $class_id,
            ':attendance_date' => $attendance_date
        ]);
        
        return ['success' => true, 'message' => 'Attendance column deleted successfully'];
    } catch (PDOException $e) {
        error_log("deleteAttendanceDate error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete attendance column'];
    }
}

// Delete grade column (prevent deletion of default columns)
 
function deleteGradeColumn($class_id, $activity_name, $teacher_id) {
    global $conn;
    
    // Prevent deletion of default columns
    $defaultColumns = ['Recitation', 'Midterm Exam', 'Final Exam'];
    if (in_array($activity_name, $defaultColumns)) {
        return ['success' => false, 'message' => 'Cannot delete default columns'];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT class_id FROM classes 
            WHERE class_id = :class_id AND teacher_id = :teacher_id
        ");
        $stmt->execute([
            ':class_id' => $class_id,
            ':teacher_id' => $teacher_id
        ]);
        
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $stmt = $conn->prepare("
            DELETE FROM grades
            WHERE class_id = :class_id AND activity_name = :activity_name
        ");
        
        $stmt->execute([
            ':class_id' => $class_id,
            ':activity_name' => $activity_name
        ]);
        
        return ['success' => true, 'message' => 'Grade column deleted successfully'];
    } catch (PDOException $e) {
        error_log("deleteGradeColumn error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete grade column'];
    }
}

// Save or update a grade
 
function saveGrade($data) {
    global $conn;
    
    $required = ['student_id', 'class_id', 'activity_name', 'score', 'grading_period'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            return ['success' => false, 'message' => "Missing required field: $field"];
        }
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE grades 
            SET score = :score,
                percentage = CASE WHEN max_score > 0 THEN (:score / max_score * 100) ELSE 0 END,
                updated_at = CURRENT_TIMESTAMP
            WHERE student_id = :student_id 
            AND class_id = :class_id 
            AND activity_name = :activity_name
            AND grading_period = :grading_period
        ");
        
        $stmt->execute([
            ':score' => $data['score'],
            ':student_id' => $data['student_id'],
            ':class_id' => $data['class_id'],
            ':activity_name' => $data['activity_name'],
            ':grading_period' => $data['grading_period']
        ]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Grade updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Grade record not found'];
        }
    } catch (PDOException $e) {
        error_log("saveGrade error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to save grade'];
    }
}

// Save or update attendance
 
function saveAttendance($data) {
    global $conn;
    
    $required = ['student_id', 'class_id', 'attendance_date', 'status'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            return ['success' => false, 'message' => "Missing required field: $field"];
        }
    }
    
    $valid_statuses = ['present', 'absent', 'late', 'excused'];
    if (!in_array($data['status'], $valid_statuses)) {
        return ['success' => false, 'message' => 'Invalid attendance status'];
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE attendance 
            SET status = :status,
                remarks = :remarks,
                updated_at = CURRENT_TIMESTAMP
            WHERE student_id = :student_id 
            AND class_id = :class_id 
            AND attendance_date = :attendance_date
        ");
        
        $stmt->execute([
            ':status' => $data['status'],
            ':remarks' => $data['remarks'] ?? null,
            ':student_id' => $data['student_id'],
            ':class_id' => $data['class_id'],
            ':attendance_date' => $data['attendance_date']
        ]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Attendance updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Attendance record not found'];
        }
    } catch (PDOException $e) {
        error_log("saveAttendance error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to save attendance'];
    }
}

// Handle AJAX requests
if (basename($_SERVER['PHP_SELF']) === 'view-grades-handler.php') {
    require_once '../../includes/config.php';
    require_once '../../includes/session.php';
    require_once '../../includes/functions.php';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $teacher_id = $_SESSION['user_id'];
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_attendance_date':
                $class_id = $_POST['class_id'] ?? null;
                $attendance_date = $_POST['attendance_date'] ?? null;
                
                if (!$class_id || !$attendance_date) {
                    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                    exit;
                }
                
                $result = addAttendanceDate($class_id, $attendance_date, $teacher_id);
                echo json_encode($result);
                break;
                
            case 'add_grade_column':
                $class_id = $_POST['class_id'] ?? null;
                $activity_type = $_POST['activity_type'] ?? null;
                $max_score = $_POST['max_score'] ?? 100;
                $grading_period = $_POST['grading_period'] ?? 'prelim';
                
                if (!$class_id || !$activity_type) {
                    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                    exit;
                }
                
                $result = addGradeColumn($class_id, $activity_type, $max_score, $grading_period, $teacher_id);
                echo json_encode($result);
                break;
                
            case 'delete_attendance_date':
                $result = deleteAttendanceDate($_POST['class_id'], $_POST['attendance_date'], $teacher_id);
                echo json_encode($result);
                break;
                
            case 'delete_grade_column':
                $result = deleteGradeColumn($_POST['class_id'], $_POST['activity_name'], $teacher_id);
                echo json_encode($result);
                break;
                
            case 'save_grade':
                $data = [
                    'student_id' => $_POST['student_id'] ?? null,
                    'class_id' => $_POST['class_id'] ?? null,
                    'activity_name' => $_POST['activity_name'] ?? null,
                    'score' => $_POST['score'] ?? null,
                    'grading_period' => $_POST['grading_period'] ?? 'prelim'
                ];
                
                $result = saveGrade($data);
                echo json_encode($result);
                break;
                
            case 'save_attendance':
                $data = [
                    'student_id' => $_POST['student_id'] ?? null,
                    'class_id' => $_POST['class_id'] ?? null,
                    'attendance_date' => $_POST['attendance_date'] ?? null,
                    'status' => $_POST['status'] ?? null,
                    'remarks' => $_POST['remarks'] ?? null
                ];
                
                $result = saveAttendance($data);
                echo json_encode($result);
                break;
                
            case 'initialize_student':
                $student_id = $_POST['student_id'] ?? null;
                $class_id = $_POST['class_id'] ?? null;
                $grading_period = $_POST['grading_period'] ?? 'prelim';
                
                if (!$student_id || !$class_id) {
                    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
                    exit;
                }
                
                $success = initializeDefaultGrades($student_id, $class_id, $grading_period, $teacher_id);
                echo json_encode(['success' => $success]);
                break;
                
            case 'get_data':
                $class_id = $_POST['class_id'] ?? null;
                $grading_period = $_POST['grading_period'] ?? 'prelim';
                
                if (!$class_id) {
                    echo json_encode(['success' => false, 'message' => 'Missing class ID']);
                    exit;
                }
                
                $students = getEnrolledStudents($class_id);
                
                // Initialize default grades for all students if needed
                foreach ($students as $student) {
                    initializeDefaultGrades($student['user_id'], $class_id, $grading_period, $teacher_id);
                }
                
                $grades = getClassGrades($class_id, $grading_period);
                $attendance = getClassAttendance($class_id);
                $attendanceDates = getAttendanceDates($class_id);
                $activityColumns = getActivityColumns($class_id, $grading_period);
                
                echo json_encode([
                    'success' => true,
                    'students' => $students,
                    'grades' => $grades,
                    'attendance' => $attendance,
                    'attendanceDates' => $attendanceDates,
                    'activityColumns' => $activityColumns
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
        exit;
    }
}
?>