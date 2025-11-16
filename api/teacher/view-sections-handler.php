<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/functions.php';

function getCourseSections($teacher_id, $subject, $class_name) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                c.*,
                COUNT(DISTINCT e.student_id) as student_count
            FROM classes c
            LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
            WHERE c.teacher_id = ? AND c.subject = ? AND c.class_name = ? AND c.status = 'active'
            GROUP BY c.class_id
            ORDER BY c.section ASC
        ");
        $stmt->execute([$teacher_id, $subject, $class_name]);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get schedules for each section from class_schedules table
        foreach ($sections as &$section) {
            $scheduleStmt = $conn->prepare("
                SELECT * FROM class_schedules 
                WHERE class_id = ? 
                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                         start_time ASC
            ");
            $scheduleStmt->execute([$section['class_id']]);
            $section['schedules'] = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $sections;
    } catch (PDOException $e) {
        error_log("Get Course Sections Error: " . $e->getMessage());
        return [];
    }
}

function getCourseInfo($teacher_id, $subject, $class_name) {
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
            WHERE c.teacher_id = ? AND c.subject = ? AND c.class_name = ? AND c.status = 'active'
            GROUP BY c.subject, c.class_name
        ");
        $stmt->execute([$teacher_id, $subject, $class_name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get Course Info Error: " . $e->getMessage());
        return null;
    }
}
?>