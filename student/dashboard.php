<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$student_id = $_SESSION['user_id'];

// Get statistics with proper null handling for new users
try {
    // Active Courses (Total enrolled classes)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM enrollments 
        WHERE student_id = ? AND status = 'active'
    ");
    $stmt->execute([$student_id]);
    $active_courses = (int)($stmt->fetch()['total'] ?? 0);
    
    // GPA / Overall Grade Average
    $stmt = $conn->prepare("
        SELECT AVG(percentage) as avg_grade
        FROM grades
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $gpa_result = $stmt->fetch();
    $gpa = $gpa_result['avg_grade'] !== null ? (float)$gpa_result['avg_grade'] : 0;
    
    // Quizzes Average
    $stmt = $conn->prepare("
        SELECT AVG(percentage) as avg_quiz
        FROM grades
        WHERE student_id = ? AND activity_type = 'quiz'
    ");
    $stmt->execute([$student_id]);
    $quiz_result = $stmt->fetch();
    $quiz_average = $quiz_result['avg_quiz'] !== null ? (float)$quiz_result['avg_quiz'] : 0;
    
    // Activities Average
    $stmt = $conn->prepare("
        SELECT AVG(percentage) as avg_activity
        FROM grades
        WHERE student_id = ? AND activity_type IN ('assignment', 'project')
    ");
    $stmt->execute([$student_id]);
    $activity_result = $stmt->fetch();
    $activity_average = $activity_result['avg_activity'] !== null ? (float)$activity_result['avg_activity'] : 0;
    
    // Attendance Percentage
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_attendance = (int)($stmt->fetch()['total'] ?? 0);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ? AND status = 'present'");
    $stmt->execute([$student_id]);
    $present_count = (int)($stmt->fetch()['total'] ?? 0);
    
    $attendance_percentage = $total_attendance > 0 ? round(($present_count / $total_attendance) * 100, 1) : 0;
    
    // Missing/Pending Tasks
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT g1.activity_name) as missing_count
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
    $missing_tasks = (int)($stmt->fetch()['missing_count'] ?? 0);
    
    // Get subject performance for chart (only if student has grades)
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
    
    // Get grade history for chart (last 6 weeks)
    $grade_history = [];
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
    
    // Prepare grade history for JavaScript (fill with zeros if no data)
    if (count($grade_history_data) > 0) {
        $grade_history = array_map(function($row) {
            return round($row['avg_grade'], 2);
        }, $grade_history_data);
    } else {
        $grade_history = [0, 0, 0, 0, 0, 0]; // Default zeros for new users
    }
    
} catch (PDOException $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
    // Set default values if error occurs
    $active_courses = 0;
    $gpa = 0;
    $quiz_average = 0;
    $activity_average = 0;
    $attendance_percentage = 0;
    $missing_tasks = 0;
    $subject_performance = [];
    $grade_history = [0, 0, 0, 0, 0, 0];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - indEx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/student-dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-content-v5">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Section -->
                <section class="welcome-section-v5">
                    <div class="welcome-text-v5">
                        <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
                        <p>Your academic journey continues here. Check your grades, attendance, and manage your coursework all in one place.</p>
                        <div class="cta-buttons-v5">
                            <a href="my-grades.php" class="btn-v5 btn-primary-v5">
                                <i class="fas fa-arrow-right"></i> View Courses
                            </a>
                            <a href="join-class.php" class="btn-v5 btn-secondary-v5">
                                <i class="fas fa-info-circle"></i> Join Class
                            </a>
                        </div>
                    </div>
                    <img src="<?php echo getProfilePicture($_SESSION['profile_picture'] ?? '', $_SESSION['full_name']); ?>" 
                         alt="Welcome" 
                         class="welcome-graphic-v5" 
                         id="dashboardProfilePic">
                </section>

                <!-- Stats Section -->
                <section class="stats-section-v5">
                    <h3 class="section-title-v5">Quick Overview</h3>
                    <div class="stats-grid-v5">
                        <div class="stat-card-v5">
                            <div class="stat-icon-v5">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-label-v5">Active Courses</div>
                            <div class="stat-value-v5" id="activeCourses"><?php echo $active_courses; ?></div>
                        </div>

                        <div class="stat-card-v5">
                            <div class="stat-icon-v5">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-label-v5">Overall GPA</div>
                            <div class="stat-value-v5" id="gpaValue"><?php echo number_format($gpa, 1); ?>%</div>
                        </div>

                        <div class="stat-card-v5">
                            <div class="stat-icon-v5">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-label-v5">Attendance</div>
                            <div class="stat-value-v5" id="attendancePercent"><?php echo $attendance_percentage; ?>%</div>
                        </div>

                        <div class="stat-card-v5">
                            <div class="stat-icon-v5">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-label-v5">Pending Tasks</div>
                            <div class="stat-value-v5" id="pendingTasks"><?php echo $missing_tasks; ?></div>
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
                            <h3 class="chart-title-v5">High-Performance Subjects</h3>
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
                                    <p style="color: var(--gray-medium); font-size: 14px; margin-top: 8px;">Join a class and complete assignments to see your performance!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Recent Activity -->
                <section class="activity-section-v5">
                    <h3 class="section-title-v5">Recent Activity</h3>
                    <div class="activity-list-v5">
                        <div class="activity-item-v5">
                            <div class="activity-icon-v5 blue">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="activity-content-v5">
                                <div class="activity-title-v5">Dashboard accessed</div>
                                <div class="activity-time-v5">Just now</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-v5">
            <p>&copy; 2025 Pampanga State University. All rights reserved. | <a href="#">FAQs</a> | <a href="#">Support</a></p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const gradeHistoryData = <?php echo json_encode($grade_history); ?>;
        const hasGrades = <?php echo $gpa > 0 ? 'true' : 'false'; ?>;
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/student-dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>