<?php
/**
 * My Courses Handler - Backend Logic
 * File: api/teacher/my-courses-handler.php
 */

// Use __DIR__ to get absolute paths relative to THIS file's location
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

/**
 * Get all courses for a teacher (grouped by subject and class name)
 * @param int $teacher_id
 * @return array
 */
function getTeacherCourses($teacher_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                c.subject,
                c.class_name,
                COUNT(DISTINCT c.class_id) as section_count,
                COUNT(DISTINCT e.student_id) as total_students
            FROM classes c
            LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
            WHERE c.teacher_id = ? AND c.status = 'active'
            GROUP BY c.subject, c.class_name
            ORDER BY c.subject ASC, c.class_name ASC
        ");
        $stmt->execute([$teacher_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get Teacher Courses Error: " . $e->getMessage());
        return [];
    }
}

function getNewClassModalData() {
    if (isset($_SESSION['new_class_code'])) {
        $data = [
            'code' => $_SESSION['new_class_code'],
            'name' => $_SESSION['new_class_name'] ?? null,
            'section' => $_SESSION['new_class_section'] ?? null
        ];
        
        // Clear session data
        unset($_SESSION['new_class_code']);
        unset($_SESSION['new_class_name']);
        unset($_SESSION['new_class_section']);
        
        return $data;
    }
    
    return null;
}
?>