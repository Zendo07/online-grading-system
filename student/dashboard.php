<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

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
    
    // Quiz Average
    $stmt = $conn->prepare("
        SELECT AVG(percentage) as avg_quiz
        FROM grades
        WHERE student_id = ? AND activity_type = 'quiz'
    ");
    $stmt->execute([$student_id]);
    $quiz_result = $stmt->fetch();
    $quiz_average = $quiz_result['avg_quiz'] !== null ? (float)$quiz_result['avg_quiz'] : 0;
    
    // Activity Average
    $stmt = $conn->prepare("
        SELECT AVG(percentage) as avg_activity
        FROM grades
        WHERE student_id = ? AND activity_type IN ('assignment', 'project')
    ");
    $stmt->execute([$student_id]);
    $activity_result = $stmt->fetch();
    $activity_average = $activity_result['avg_activity'] !== null ? (float)$activity_result['avg_activity'] : 0;
    
    // Attendance
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_attendance = (int)($stmt->fetch()['total'] ?? 0);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ? AND status = 'present'");
    $stmt->execute([$student_id]);
    $present_count = (int)($stmt->fetch()['total'] ?? 0);
    
    $attendance_percentage = $total_attendance > 0 ? round(($present_count / $total_attendance) * 100, 1) : 0;
    
    // Missing Submissions - Activities assigned but no grade submitted
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
    
    // Grade history for chart
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
    
    // Subject performance
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
        $subject_performance = $stmt->fetchAll();
    }
    
    // Recent Academic Updates - Grades, Activities, Attendance
    $stmt = $conn->prepare("
        SELECT 
            'grade' as type,
            u.full_name as teacher_name,
            c.subject,
            g.activity_name,
            g.activity_type,
            g.score,
            g.max_score,
            g.percentage,
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
    $recent_activities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
    $active_courses = 0;
    $gpa = 0;
    $quiz_average = 0;
    $activity_average = 0;
    $attendance_percentage = 0;
    $missing_submissions = 0;
    $subject_performance = [];
    $grade_history = [0, 0, 0, 0, 0, 0];
    $recent_activities = [];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo htmlspecialchars($student_id); ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="description" content="Student Dashboard - indEx Online Grading System">
    <title>Student Dashboard - indEx</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css?v=<?php echo time(); ?>">
    
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-pages/student-dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-content-v5">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Section -->
                <section class="welcome-section-v5">
                    <div class="welcome-text-v5">
                        <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
                        <p>Your academic journey continues here. Check your grades, attendance, and manage your coursework all in one place.</p>
                        <div class="cta-buttons-v5">
                            <a href="my-courses.php" class="btn-v5 btn-primary-v5">
                                <i class="fas fa-arrow-right"></i> View Courses
                            </a>
                            <a href="join-class.php" class="btn-v5 btn-secondary-v5">
                                <i class="fas fa-info-circle"></i> Join Class
                            </a>
                        </div>
                    </div>
                    <img src="<?php echo getProfilePicture($_SESSION['profile_picture'] ?? null, $_SESSION['full_name']); ?>" 
                                alt="Profile Picture" 
                                class="welcome-graphic-v5" 
                                id="dashboardProfilePic">
                </section>

                <!-- Stats Section -->
                <section class="stats-section-v5">
                    <h3 class="section-title-v5">Quick Overview</h3>
                    <div class="stats-grid-v5">
                        <div class="stat-card-v5">
                            <div class="stat-icon-v5"><i class="fas fa-book"></i></div>
                            <div class="stat-label-v5">Active Courses</div>
                            <div class="stat-value-v5" id="activeCourses"><?php echo $active_courses; ?></div>
                        </div>

                        <div class="stat-card-v5">
                            <div class="stat-icon-v5"><i class="fas fa-star"></i></div>
                            <div class="stat-label-v5">Overall GPA</div>
                            <div class="stat-value-v5" id="gpaValue"><?php echo number_format($gpa, 1); ?>%</div>
                        </div>

                        <div class="stat-card-v5">
                            <div class="stat-icon-v5"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-label-v5">Attendance</div>
                            <div class="stat-value-v5" id="attendancePercent"><?php echo $attendance_percentage; ?>%</div>
                        </div>

                        <div class="stat-card-v5">
                            <div class="stat-icon-v5"><i class="fas fa-file-alt"></i></div>
                            <div class="stat-label-v5">Missing Submissions</div>
                            <div class="stat-value-v5" id="pendingTasks"><?php echo $missing_submissions; ?></div>
                        </div>
                    </div>
                </section>

                <!-- Charts Section -->
                <section class="charts-section-v5">
                    <div class="chart-card-v5">
                        <div class="chart-header-v5">
                            <h3 class="chart-title-v5">Grade Performance</h3>
                        </div>
                        <div class="chart-container-v5">
                            <canvas id="gradeChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card-v5">
                        <div class="chart-header-v5">
                            <h3 class="chart-title-v5">Subject Performance</h3>
                        </div>
                        <div class="progress-list-v5">
                            <?php if (!empty($subject_performance)): ?>
                                <?php foreach ($subject_performance as $subject): ?>
                                    <div class="progress-item-v5">
                                        <div class="progress-header-v5">
                                            <span class="progress-label-v5"><?php echo htmlspecialchars($subject['subject']); ?></span>
                                            <span class="progress-percentage-v5"><?php echo number_format($subject['avg_percentage'], 1); ?>%</span>
                                        </div>
                                        <div class="progress-bar-container-v5">
                                            <div class="progress-bar-v5" data-progress="<?php echo number_format($subject['avg_percentage'], 1); ?>"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px 20px;">
                                    <div style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;">📚</div>
                                    <p style="color: var(--gray-medium); margin: 0;">No performance data available yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Recent Academic Updates -->
                <section class="activity-section-v5">
                    <h3 class="section-title-v5">Recent Academic Updates</h3>
                    <div class="activity-list-v5">
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item-v5">
                                    <?php if ($activity['type'] === 'grade'): ?>
                                        <div class="activity-icon-v5 blue">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="activity-content-v5">
                                            <div class="activity-title-v5">
                                                <?php echo htmlspecialchars($activity['activity_name']); ?> 
                                                <span style="font-size: 0.85rem; color: var(--gray-medium);">
                                                    (<?php echo ucfirst($activity['activity_type']); ?>)
                                                </span>
                                            </div>
                                            <div class="activity-details-v5">
                                                <strong><?php echo htmlspecialchars($activity['teacher_name']); ?></strong> • 
                                                <?php echo htmlspecialchars($activity['subject']); ?><br>
                                                Score: <strong><?php echo $activity['score']; ?>/<?php echo $activity['max_score']; ?></strong> 
                                                (<?php echo number_format($activity['percentage'], 1); ?>%)
                                            </div>
                                            <div class="activity-time-v5"><?php echo formatDateTime($activity['event_date']); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="activity-icon-v5 <?php echo $activity['attendance_status'] === 'present' ? 'green' : 'red'; ?>">
                                            <i class="fas fa-<?php echo $activity['attendance_status'] === 'present' ? 'check-circle' : 'times-circle'; ?>"></i>
                                        </div>
                                        <div class="activity-content-v5">
                                            <div class="activity-title-v5">
                                                Attendance Mark - 
                                                <span style="text-transform: capitalize;">
                                                    <?php echo $activity['attendance_status']; ?>
                                                </span>
                                            </div>
                                            <div class="activity-details-v5">
                                                <strong><?php echo htmlspecialchars($activity['teacher_name']); ?></strong> • 
                                                <?php echo htmlspecialchars($activity['subject']); ?>
                                            </div>
                                            <div class="activity-time-v5"><?php echo formatDateTime($activity['event_date']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px 20px;">
                                <div style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;">📋</div>
                                <p style="color: var(--gray-medium); margin: 0;">No recent academic updates yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
        window.CURRENT_USER_ID = '<?php echo $student_id; ?>';
        const gradeHistoryData = <?php echo json_encode($grade_history); ?>;
        const hasGrades = <?php echo $gpa > 0 ? 'true' : 'false'; ?>;
    </script>
    <script src="<?php echo JS_PATH; ?>main.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo JS_PATH; ?>student-dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>