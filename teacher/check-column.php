<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .section { background: #2a2a2a; padding: 20px; margin: 20px 0; border: 2px solid #0f0; }
        .title { color: #0ff; font-size: 1.5rem; margin-bottom: 15px; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; border: 1px solid #0f0; text-align: left; }
        th { background: #333; }
        pre { background: #000; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1 style="color: #0ff;">🔧 DETAILED DASHBOARD DEBUGGER</h1>
    <p>Teacher ID: <span class="success"><?php echo $teacher_id; ?></span></p>
    <p>Teacher Name: <span class="success"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span></p>

    <?php
    echo '<div class="section">';
    echo '<div class="title">TEST 1: Your Classes</div>';
    try {
        $stmt = $conn->prepare("SELECT * FROM classes WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($classes)) {
            echo '<p class="error">❌ NO CLASSES FOUND for teacher_id = ' . $teacher_id . '</p>';
            echo '<p class="warning">⚠️ You need to create a class first!</p>';
        } else {
            echo '<p class="success">✅ Found ' . count($classes) . ' classes</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Class Name</th><th>Subject</th><th>Section</th><th>Code</th><th>Status</th></tr>';
            foreach ($classes as $class) {
                echo '<tr>';
                echo '<td>' . $class['class_id'] . '</td>';
                echo '<td>' . htmlspecialchars($class['class_name']) . '</td>';
                echo '<td>' . htmlspecialchars($class['subject']) . '</td>';
                echo '<td>' . htmlspecialchars($class['section']) . '</td>';
                echo '<td>' . htmlspecialchars($class['class_code']) . '</td>';
                echo '<td>' . $class['status'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ ERROR: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';

    echo '<div class="section">';
    echo '<div class="title">TEST 2: All Enrollments in Your Classes</div>';
    try {
        $stmt = $conn->prepare("
            SELECT 
                e.enrollment_id,
                e.student_id,
                e.class_id,
                e.status as enrollment_status,
                e.enrolled_at,
                c.subject,
                c.section,
                u.full_name as student_name
            FROM enrollments e
            INNER JOIN classes c ON e.class_id = c.class_id
            LEFT JOIN users u ON e.student_id = u.user_id
            WHERE c.teacher_id = ?
            ORDER BY e.enrolled_at DESC
        ");
        $stmt->execute([$teacher_id]);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($enrollments)) {
            echo '<p class="error">❌ NO ENROLLMENTS FOUND</p>';
            echo '<p class="warning">⚠️ Students need to join your classes!</p>';
        } else {
            echo '<p class="success">✅ Found ' . count($enrollments) . ' total enrollments</p>';
            
            $status_counts = [];
            foreach ($enrollments as $e) {
                $status = $e['enrollment_status'];
                $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
            }
            
            echo '<p>Status breakdown:</p><ul>';
            foreach ($status_counts as $status => $count) {
                $color = $status === 'active' ? 'success' : 'warning';
                echo '<li class="' . $color . '">' . $status . ': ' . $count . '</li>';
            }
            echo '</ul>';
            
            echo '<table>';
            echo '<tr><th>Student</th><th>Class</th><th>Status</th><th>Enrolled At</th></tr>';
            foreach ($enrollments as $e) {
                $statusColor = $e['enrollment_status'] === 'active' ? 'success' : 'warning';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($e['student_name'] ?? 'Unknown') . '</td>';
                echo '<td>' . htmlspecialchars($e['subject']) . ' - ' . htmlspecialchars($e['section']) . '</td>';
                echo '<td class="' . $statusColor . '">' . $e['enrollment_status'] . '</td>';
                echo '<td>' . $e['enrolled_at'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ ERROR: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';

    echo '<div class="section">';
    echo '<div class="title">TEST 3: Active Students Count</div>';
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.student_id) as total 
            FROM enrollments e 
            INNER JOIN classes c ON e.class_id = c.class_id 
            WHERE c.teacher_id = ? AND e.status = 'active'
        ");
        $stmt->execute([$teacher_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_students = $result['total'];
        
        if ($total_students == 0) {
            echo '<p class="error">❌ TOTAL ACTIVE STUDENTS: 0</p>';
            echo '<p class="warning">⚠️ No students with status = "active" found!</p>';
        } else {
            echo '<p class="success">✅ TOTAL ACTIVE STUDENTS: ' . $total_students . '</p>';
        }
        
        // Show the students
        $stmt = $conn->prepare("
            SELECT DISTINCT 
                u.user_id,
                u.full_name,
                u.student_number,
                COUNT(e.enrollment_id) as class_count
            FROM enrollments e 
            INNER JOIN classes c ON e.class_id = c.class_id 
            INNER JOIN users u ON e.student_id = u.user_id
            WHERE c.teacher_id = ? AND e.status = 'active'
            GROUP BY u.user_id, u.full_name, u.student_number
        ");
        $stmt->execute([$teacher_id]);
        $active_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($active_students)) {
            echo '<table>';
            echo '<tr><th>Student Name</th><th>Student Number</th><th>Classes Enrolled</th></tr>';
            foreach ($active_students as $student) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($student['full_name']) . '</td>';
                echo '<td>' . htmlspecialchars($student['student_number'] ?? 'N/A') . '</td>';
                echo '<td>' . $student['class_count'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ ERROR: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';

    echo '<div class="section">';
    echo '<div class="title">TEST 4: Grades in Your Classes</div>';
    try {
        $stmt = $conn->prepare("
            SELECT 
                g.*,
                c.subject,
                c.section,
                u.full_name as student_name
            FROM grades g
            INNER JOIN classes c ON g.class_id = c.class_id
            LEFT JOIN users u ON g.student_id = u.user_id
            WHERE c.teacher_id = ?
            ORDER BY g.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$teacher_id]);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($grades)) {
            echo '<p class="error">❌ NO GRADES FOUND</p>';
            echo '<p class="warning">⚠️ You need to record grades for activities!</p>';
        } else {
            echo '<p class="success">✅ Found grades (showing last 10)</p>';
            echo '<table>';
            echo '<tr><th>Student</th><th>Class</th><th>Activity</th><th>Type</th><th>Score</th><th>Max</th><th>%</th><th>Period</th></tr>';
            foreach ($grades as $grade) {
                $pct = $grade['percentage'];
                $color = $pct >= 75 ? 'success' : 'error';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($grade['student_name'] ?? 'Unknown') . '</td>';
                echo '<td>' . htmlspecialchars($grade['subject']) . ' - ' . htmlspecialchars($grade['section']) . '</td>';
                echo '<td>' . htmlspecialchars($grade['activity_name']) . '</td>';
                echo '<td>' . $grade['activity_type'] . '</td>';
                echo '<td>' . $grade['score'] . '</td>';
                echo '<td>' . $grade['max_score'] . '</td>';
                echo '<td class="' . $color . '">' . number_format($pct, 2) . '%</td>';
                echo '<td>' . $grade['grading_period'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ ERROR: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    echo '<div class="section">';
    echo '<div class="title">TEST 5: Passed/Failed Calculation</div>';
    try {
        // Get student averages
        $stmt = $conn->prepare("
            SELECT 
                g.student_id,
                g.class_id,
                u.full_name as student_name,
                c.subject,
                c.section,
                AVG(g.percentage) as avg_percentage,
                COUNT(g.grade_id) as activity_count
            FROM grades g
            INNER JOIN classes c ON g.class_id = c.class_id
            LEFT JOIN users u ON g.student_id = u.user_id
            WHERE c.teacher_id = ? AND g.percentage IS NOT NULL
            GROUP BY g.student_id, g.class_id, u.full_name, c.subject, c.section
            ORDER BY avg_percentage DESC
        ");
        $stmt->execute([$teacher_id]);
        $student_averages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($student_averages)) {
            echo '<p class="error">❌ NO STUDENT AVERAGES TO CALCULATE</p>';
            echo '<p class="warning">⚠️ Need grades to calculate pass/fail!</p>';
        } else {
            $passed = 0;
            $failed = 0;
            
            echo '<table>';
            echo '<tr><th>Student</th><th>Class</th><th>Avg %</th><th>Activities</th><th>Status</th></tr>';
            foreach ($student_averages as $avg) {
                $percentage = $avg['avg_percentage'];
                if ($percentage >= 75) {
                    $passed++;
                    $status = 'PASSED';
                    $color = 'success';
                } else {
                    $failed++;
                    $status = 'FAILED';
                    $color = 'error';
                }
                
                echo '<tr>';
                echo '<td>' . htmlspecialchars($avg['student_name'] ?? 'Unknown') . '</td>';
                echo '<td>' . htmlspecialchars($avg['subject']) . ' - ' . htmlspecialchars($avg['section']) . '</td>';
                echo '<td>' . number_format($percentage, 2) . '%</td>';
                echo '<td>' . $avg['activity_count'] . '</td>';
                echo '<td class="' . $color . '"><strong>' . $status . '</strong></td>';
                echo '</tr>';
            }
            echo '</table>';
            
            echo '<p class="success">✅ PASSED: ' . $passed . '</p>';
            echo '<p class="error">✅ FAILED: ' . $failed . '</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ ERROR: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';

    echo '<div class="section">';
    echo '<div class="title">TEST 6: Dashboard Stats (What Should Show)</div>';
    
    try {
        // Total classes
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM classes WHERE teacher_id = ? AND status = 'active'");
        $stmt->execute([$teacher_id]);
        $total_classes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total students
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.student_id) as total 
            FROM enrollments e 
            INNER JOIN classes c ON e.class_id = c.class_id 
            WHERE c.teacher_id = ? AND e.status = 'active'
        ");
        $stmt->execute([$teacher_id]);
        $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Passed students
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT CONCAT(g.student_id, '-', g.class_id)) as total
            FROM (
                SELECT 
                    g.student_id,
                    g.class_id,
                    AVG(g.percentage) as avg_percentage
                FROM grades g
                INNER JOIN classes c ON g.class_id = c.class_id
                WHERE c.teacher_id = ? AND g.percentage IS NOT NULL
                GROUP BY g.student_id, g.class_id
                HAVING avg_percentage >= 75
            ) as student_averages
        ");
        $stmt->execute([$teacher_id]);
        $passed_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Failed students
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT CONCAT(g.student_id, '-', g.class_id)) as total
            FROM (
                SELECT 
                    g.student_id,
                    g.class_id,
                    AVG(g.percentage) as avg_percentage
                FROM grades g
                INNER JOIN classes c ON g.class_id = c.class_id
                WHERE c.teacher_id = ? AND g.percentage IS NOT NULL
                GROUP BY g.student_id, g.class_id
                HAVING avg_percentage < 75
            ) as student_averages
        ");
        $stmt->execute([$teacher_id]);
        $failed_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo '<table>';
        echo '<tr><th>Metric</th><th>Value</th></tr>';
        echo '<tr><td>Total Active Classes</td><td class="success"><strong>' . $total_classes . '</strong></td></tr>';
        echo '<tr><td>Total Active Students</td><td class="success"><strong>' . $total_students . '</strong></td></tr>';
        echo '<tr><td>Passed Students (≥75%)</td><td class="success"><strong>' . $passed_students . '</strong></td></tr>';
        echo '<tr><td>Failed Students (<75%)</td><td class="error"><strong>' . $failed_students . '</strong></td></tr>';
        echo '</table>';
        
        echo '<h3 style="color: #0ff; margin-top: 30px;">👆 THESE NUMBERS SHOULD SHOW ON YOUR DASHBOARD</h3>';
        
    } catch (Exception $e) {
        echo '<p class="error">❌ ERROR: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';

    // FINAL DIAGNOSIS
    echo '<div class="section">';
    echo '<div class="title">🎯 DIAGNOSIS & ACTION ITEMS</div>';
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $has_classes = $stmt->fetch(PDO::FETCH_ASSOC)['COUNT(*)'] > 0;
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM enrollments e 
            INNER JOIN classes c ON e.class_id = c.class_id 
            WHERE c.teacher_id = ? AND e.status = 'active'
        ");
        $stmt->execute([$teacher_id]);
        $has_enrollments = $stmt->fetch(PDO::FETCH_ASSOC)['COUNT(*)'] > 0;
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM grades g 
            INNER JOIN classes c ON g.class_id = c.class_id 
            WHERE c.teacher_id = ?
        ");
        $stmt->execute([$teacher_id]);
        $has_grades = $stmt->fetch(PDO::FETCH_ASSOC)['COUNT(*)'] > 0;
        
        echo '<ol style="font-size: 1.2rem; line-height: 2;">';
        
        if (!$has_classes) {
            echo '<li class="error">❌ CREATE A CLASS FIRST!</li>';
        } else {
            echo '<li class="success">✅ You have classes</li>';
        }
        
        if (!$has_enrollments) {
            echo '<li class="error">❌ GET STUDENTS TO ENROLL! (Share class code)</li>';
        } else {
            echo '<li class="success">✅ You have students enrolled</li>';
        }
        
        if (!$has_grades) {
            echo '<li class="warning">⚠️ RECORD SOME GRADES for activities</li>';
        } else {
            echo '<li class="success">✅ You have grades recorded</li>';
        }
        
        echo '</ol>';
        
        echo '<p style="font-size: 1.3rem; margin-top: 20px;">';
        if ($has_classes && $has_enrollments) {
            echo '<span class="success">🎉 YOUR DASHBOARD SHOULD BE WORKING NOW!</span>';
        } else {
            echo '<span class="warning">⚠️ Complete the steps above first!</span>';
        }
        echo '</p>';
        
    } catch (Exception $e) {
        echo '<p class="error">ERROR: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    ?>

    <div style="text-align: center; margin: 40px 0;">
        <a href="dashboard.php" style="background: #0f0; color: #000; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 5px; font-size: 1.2rem;">
            ← BACK TO DASHBOARD
        </a>
    </div>
</body>
</html>