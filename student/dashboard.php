<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$student_id = $_SESSION['user_id'];

// Get statistics
try {
    // Total enrolled classes
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM enrollments 
        WHERE student_id = ? AND status = 'active'
    ");
    $stmt->execute([$student_id]);
    $total_classes = $stmt->fetch()['total'];
    
    // Overall Grade Average
    $stmt = $conn->prepare("
        SELECT AVG((score / max_score) * 100) as average 
        FROM grades 
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $overall_average = $stmt->fetch()['average'];
    $overall_average = $overall_average ? round($overall_average, 1) : 0;
    
    // Total attendance records
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM attendance 
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $total_attendance = $stmt->fetch()['total'];
    
    // Present attendance count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM attendance 
        WHERE student_id = ? AND status = 'present'
    ");
    $stmt->execute([$student_id]);
    $present_count = $stmt->fetch()['total'];
    
    // Calculate attendance percentage
    $attendance_percentage = $total_attendance > 0 ? round(($present_count / $total_attendance) * 100, 1) : 0;
    
    // Missing/Overdue Tasks (grades with score 0 or NULL in last 30 days)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM grades 
        WHERE student_id = ? 
        AND score = 0 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$student_id]);
    $missing_tasks = $stmt->fetch()['total'];
    
    // Quiz Score Average
    $stmt = $conn->prepare("
        SELECT AVG((score / max_score) * 100) as average 
        FROM grades 
        WHERE student_id = ? AND activity_type = 'quiz'
    ");
    $stmt->execute([$student_id]);
    $quiz_average = $stmt->fetch()['average'];
    $quiz_average = $quiz_average ? round($quiz_average, 1) : 0;
    
    // Activity Score Average (assignments, projects, recitation)
    $stmt = $conn->prepare("
        SELECT AVG((score / max_score) * 100) as average 
        FROM grades 
        WHERE student_id = ? 
        AND activity_type IN ('assignment', 'project', 'recitation', 'other')
    ");
    $stmt->execute([$student_id]);
    $activity_average = $stmt->fetch()['average'];
    $activity_average = $activity_average ? round($activity_average, 1) : 0;
    
    // Rank in Class (calculate based on average grades)
    // First, get student's average
    $stmt = $conn->prepare("
        SELECT student_id, AVG((score / max_score) * 100) as avg_grade
        FROM grades
        WHERE class_id IN (
            SELECT class_id FROM enrollments WHERE student_id = ? AND status = 'active'
        )
        GROUP BY student_id
        ORDER BY avg_grade DESC
    ");
    $stmt->execute([$student_id]);
    $rankings = $stmt->fetchAll();
    
    $rank = 0;
    $total_students = count($rankings);
    foreach ($rankings as $index => $student) {
        if ($student['student_id'] == $student_id) {
            $rank = $index + 1;
            break;
        }
    }
    
    $rank_suffix = 'th';
    if ($rank % 10 == 1 && $rank % 100 != 11) $rank_suffix = 'st';
    elseif ($rank % 10 == 2 && $rank % 100 != 12) $rank_suffix = 'nd';
    elseif ($rank % 10 == 3 && $rank % 100 != 13) $rank_suffix = 'rd';
    
    $rank_display = $rank > 0 ? $rank . $rank_suffix : 'N/A';
    $total_students_display = $total_students > 0 ? $total_students : 0;
    
} catch (PDOException $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - indEx</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-dashboard.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-content-wrapper">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Section -->
                <section class="welcome-section">
                    <div class="welcome-text">
                        <h2>Welcome Back, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?>!</h2>
                        <p>Your academic journey continues here. Check your grades, attendance, and manage your coursework all in one place.</p>
                        <div class="cta-buttons">
                            <a href="my-grades.php" class="btn btn-primary">
                                <i class="fas fa-arrow-right"></i> View Grades
                            </a>
                            <a href="join-class.php" class="btn btn-secondary">
                                <i class="fas fa-plus"></i> Join Class
                            </a>
                        </div>
                    </div>
                    <img src="<?php echo BASE_URL; ?>assets/images/welcome-graphic.png" 
                         alt="Welcome" 
                         class="welcome-graphic"
                         onerror="this.src='https://via.placeholder.com/300/7b2d26/ffffff?text=indEx'">
                </section>

                <!-- Stats Section -->
                <section class="stats-section">
                    <h3 class="section-title">Your Statistics</h3>
                    <div class="cards-grid">
                        <div class="card">
                            <div class="card-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Overall Grade Average</h3>
                            <div class="card-number"><?php echo $overall_average; ?></div>
                            <p>Cumulative GPA</p>
                        </div>

                        <div class="card">
                            <div class="card-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <h3>Total Subjects Enrolled</h3>
                            <div class="card-number"><?php echo $total_classes; ?></div>
                            <p>This Semester</p>
                        </div>

                        <div class="card">
                            <div class="card-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h3>Attendance Percentage</h3>
                            <div class="card-number"><?php echo $attendance_percentage; ?>%</div>
                            <p>Present This Semester</p>
                        </div>

                        <div class="card">
                            <div class="card-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3>Missing / Overdue Tasks</h3>
                            <div class="card-number"><?php echo $missing_tasks; ?></div>
                            <p>Need Attention</p>
                        </div>

                        <div class="card">
                            <div class="card-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <h3>Quiz Score Average</h3>
                            <div class="card-number"><?php echo $quiz_average; ?></div>
                            <p>Average Score</p>
                        </div>

                        <div class="card">
                            <div class="card-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h3>Activity Score Average</h3>
                            <div class="card-number"><?php echo $activity_average; ?></div>
                            <p>Overall Performance</p>
                        </div>

                        <div class="card">
                            <div class="card-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h3>Rank in Class</h3>
                            <div class="card-number"><?php echo $rank_display; ?></div>
                            <p>Out of <?php echo $total_students_display; ?> Students</p>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Footer -->
            <div class="dashboard-footer">
                <p>&copy; 2025 Pampanga State University. All rights reserved. | <a href="#">FAQs</a></p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script src="<?php echo JS_PATH; ?>student-dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>