<?php
// api/student/get-dashboard-updates.php
// Endpoint for realtime dashboard updates

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Require student access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user_id'];

try {
    // Active Courses
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM enrollments 
        WHERE student_id = ? AND status = 'active'
    ");
    $stmt->execute([$student_id]);
    $active_courses = (int)($stmt->fetch()['total'] ?? 0);
    
    // Overall GPA
    $stmt = $conn->prepare("
        SELECT AVG(percentage) as avg_grade
        FROM grades
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $gpa_result = $stmt->fetch();
    $gpa = $gpa_result['avg_grade'] !== null ? (float)$gpa_result['avg_grade'] : 0;
    
    // Attendance Percentage
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_attendance = (int)($stmt->fetch()['total'] ?? 0);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ? AND status = 'present'");
    $stmt->execute([$student_id]);
    $present_count = (int)($stmt->fetch()['total'] ?? 0);
    
    $attendance_percentage = $total_attendance > 0 ? round(($present_count / $total_attendance) * 100, 1) : 0;
    
    // Missing Submissions
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT CONCAT(g1.class_id, '_', g1.activity_name)) as missing_count
        FROM (
            SELECT DISTINCT activity_name, class_id
            FROM grades
            WHERE class_id IN (
                SELECT class_id FROM enrollments WHERE student_id = ? AND status = 'active'
            )
        ) g1
        LEFT JOIN grades g2 ON g1.activity_name = g2.activity_name 
            AND g1.class_id = g2.class_id 
            AND g2.student_id = ?
        WHERE g2.grade_id IS NULL
    ");
    $stmt->execute([$student_id, $student_id]);
    $missing_submissions = (int)($stmt->fetch()['missing_count'] ?? 0);
    
    // Grade History for Chart
    $stmt = $conn->prepare("
        SELECT 
            WEEK(created_at) as week_num,
            AVG(percentage) as avg_grade
        FROM grades
        WHERE student_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 WEEK)
        GROUP BY WEEK(created_at)
        ORDER BY created_at ASC
        LIMIT 6
    ");
    $stmt->execute([$student_id]);
    $grade_history_data = $stmt->fetchAll();
    
    $grade_history = [];
    if (count($grade_history_data) > 0) {
        $grade_history = array_map(function($row) {
            return round($row['avg_grade'], 2);
        }, $grade_history_data);
    } else {
        $grade_history = [0, 0, 0, 0, 0, 0];
    }
    
    // Subject Performance
    $subject_performance = [];
    if ($gpa > 0) {
        $stmt = $conn->prepare("
            SELECT 
                c.subject,
                AVG(g.percentage) as avg_percentage
            FROM grades g
            INNER JOIN classes c ON g.class_id = c.class_id
            WHERE g.student_id = ?
            GROUP BY c.subject
            ORDER BY avg_percentage DESC
            LIMIT 5
        ");
        $stmt->execute([$student_id]);
        $subject_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Recent Academic Updates (Last 10)
    $stmt = $conn->prepare("
        SELECT 
            'grade' as type,
            u.full_name as teacher_name,
            c.subject,
            g.activity_name,
            g.activity_type,
            ROUND(g.score, 2) as score,
            ROUND(g.max_score, 2) as max_score,
            ROUND(g.percentage, 2) as percentage,
            g.created_at as event_date,
            NULL as attendance_status
        FROM grades g
        INNER JOIN classes c ON g.class_id = c.class_id
        INNER JOIN users u ON c.teacher_id = u.user_id
        WHERE g.student_id = ?
        
        UNION ALL
        
        SELECT 
            'attendance' as type,
            u.full_name as teacher_name,
            c.subject,
            NULL as activity_name,
            a.status as activity_type,
            NULL as score,
            NULL as max_score,
            NULL as percentage,
            a.created_at as event_date,
            a.status as attendance_status
        FROM attendance a
        INNER JOIN classes c ON a.class_id = c.class_id
        INNER JOIN users u ON c.teacher_id = u.user_id
        WHERE a.student_id = ?
        
        ORDER BY event_date DESC
        LIMIT 10
    ");
    $stmt->execute([$student_id, $student_id]);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'active_courses' => $active_courses,
        'gpa' => $gpa,
        'attendance_percentage' => $attendance_percentage,
        'missing_submissions' => $missing_submissions,
        'grade_history' => $grade_history,
        'subject_performance' => $subject_performance,
        'recent_activities' => $recent_activities,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("Dashboard Updates Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}