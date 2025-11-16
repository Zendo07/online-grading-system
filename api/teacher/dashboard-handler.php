<?php

function getDashboardData($teacher_id) {
    global $conn;
    
    $data = [
        'stats' => [
            'total_classes' => 0,
            'total_students' => 0,
            'passing_rate' => 0,
            'students_at_risk' => 0
        ],
        'today_schedule' => [],
        'class_analytics' => [],
        'subject_analytics' => []
    ];
    
    try {
        // Get total active classes
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM classes 
            WHERE teacher_id = ? AND status = 'active'
        ");
        $stmt->execute([$teacher_id]);
        $data['stats']['total_classes'] = (int)$stmt->fetchColumn();
        
        // Get total enrolled students
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.student_id) as total 
            FROM enrollments e
            INNER JOIN classes c ON e.class_id = c.class_id
            WHERE c.teacher_id = ? AND e.status = 'active' AND c.status = 'active'
        ");
        $stmt->execute([$teacher_id]);
        $data['stats']['total_students'] = (int)$stmt->fetchColumn();
        
        // Calculate passing rate (students with >= 75% average)
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT CASE WHEN avg_percentage >= 75 THEN g.student_id END) as passing,
                COUNT(DISTINCT g.student_id) as total
            FROM (
                SELECT student_id, class_id, AVG(percentage) as avg_percentage
                FROM grades
                GROUP BY student_id, class_id
            ) g
            INNER JOIN classes c ON g.class_id = c.class_id
            WHERE c.teacher_id = ? AND c.status = 'active'
        ");
        $stmt->execute([$teacher_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['total'] > 0) {
            $data['stats']['passing_rate'] = round(($result['passing'] / $result['total']) * 100, 1);
        }
        
        // Students at risk (average < 75%)
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT g.student_id) as total
            FROM (
                SELECT student_id, class_id, AVG(percentage) as avg_percentage
                FROM grades
                GROUP BY student_id, class_id
            ) g
            INNER JOIN classes c ON g.class_id = c.class_id
            WHERE c.teacher_id = ? AND c.status = 'active' AND g.avg_percentage < 75
        ");
        $stmt->execute([$teacher_id]);
        $data['stats']['students_at_risk'] = (int)$stmt->fetchColumn();
        
        $today = date('l'); // Get full day name: Monday, Tuesday, etc.
        
        $stmt = $conn->prepare("
            SELECT 
                c.class_name,
                c.subject,
                c.section,
                s.day_of_week,
                s.start_time,
                s.end_time,
                s.room
            FROM class_schedules s
            INNER JOIN classes c ON s.class_id = c.class_id
            WHERE c.teacher_id = ? 
            AND c.status = 'active' 
            AND LOWER(TRIM(s.day_of_week)) = LOWER(?)
            ORDER BY s.start_time ASC
        ");
        $stmt->execute([$teacher_id, $today]);
        $data['today_schedule'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("
            SELECT 
                c.class_name,
                c.section,
                c.subject,
                COUNT(DISTINCT e.student_id) as student_count,
                COALESCE(AVG(g.percentage), 0) as avg_grade
            FROM classes c
            LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
            LEFT JOIN grades g ON c.class_id = g.class_id
            WHERE c.teacher_id = ? AND c.status = 'active'
            GROUP BY c.class_id
            HAVING student_count > 0
            ORDER BY c.class_name
        ");
        $stmt->execute([$teacher_id]);
        $data['class_analytics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get subject analytics (average grade per subject)
        $stmt = $conn->prepare("
            SELECT 
                c.subject,
                COUNT(DISTINCT e.student_id) as student_count,
                COALESCE(AVG(g.percentage), 0) as avg_grade
            FROM classes c
            LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
            LEFT JOIN grades g ON c.class_id = g.class_id
            WHERE c.teacher_id = ? AND c.status = 'active'
            GROUP BY c.subject
            HAVING student_count > 0
            ORDER BY c.subject
        ");
        $stmt->execute([$teacher_id]);
        $data['subject_analytics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Dashboard Data Error: " . $e->getMessage());
    }
    
    return $data;
}

function getQuickStats($teacher_id) {
    global $conn;
    
    $stats = [
        'total_classes' => 0,
        'total_students' => 0,
        'passing_rate' => 0,
        'students_at_risk' => 0
    ];
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = ? AND status = 'active'");
        $stmt->execute([$teacher_id]);
        $stats['total_classes'] = (int)$stmt->fetchColumn();
        
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.student_id) 
            FROM enrollments e
            INNER JOIN classes c ON e.class_id = c.class_id
            WHERE c.teacher_id = ? AND e.status = 'active' AND c.status = 'active'
        ");
        $stmt->execute([$teacher_id]);
        $stats['total_students'] = (int)$stmt->fetchColumn();
        
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT CASE WHEN avg_percentage >= 75 THEN g.student_id END) as passing,
                COUNT(DISTINCT g.student_id) as total
            FROM (
                SELECT student_id, class_id, AVG(percentage) as avg_percentage
                FROM grades
                GROUP BY student_id, class_id
            ) g
            INNER JOIN classes c ON g.class_id = c.class_id
            WHERE c.teacher_id = ? AND c.status = 'active'
        ");
        $stmt->execute([$teacher_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['total'] > 0) {
            $stats['passing_rate'] = round(($result['passing'] / $result['total']) * 100, 1);
        }
        
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT g.student_id)
            FROM (
                SELECT student_id, class_id, AVG(percentage) as avg_percentage
                FROM grades
                GROUP BY student_id, class_id
            ) g
            INNER JOIN classes c ON g.class_id = c.class_id
            WHERE c.teacher_id = ? AND c.status = 'active' AND g.avg_percentage < 75
        ");
        $stmt->execute([$teacher_id]);
        $stats['students_at_risk'] = (int)$stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log("Quick Stats Error: " . $e->getMessage());
    }
    
    return $stats;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === 'refresh_stats') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $stats = getQuickStats($_SESSION['user_id']);
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}