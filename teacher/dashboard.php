<?php
// teacher/dashboard.php - UPDATED FOR YOUR DATABASE SCHEMA
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['full_name'];
$profile_picture = $_SESSION['profile_picture'] ?? null;

// Initialize stats
$stats = [
    'total_classes' => 0,
    'total_students' => 0,
    'passing_rate' => 0,
    'students_at_risk' => 0
];

$class_analytics = [];
$subject_analytics = [];
$today_schedule = [];

try {
    // Get total active classes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM classes WHERE teacher_id = ? AND status = 'active'");
    $stmt->execute([$teacher_id]);
    $stats['total_classes'] = (int)$stmt->fetchColumn();
    
    // Get total enrolled students
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT e.student_id) as total 
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.class_id
        WHERE c.teacher_id = ? AND e.status = 'active' AND c.status = 'active'
    ");
    $stmt->execute([$teacher_id]);
    $stats['total_students'] = (int)$stmt->fetchColumn();
    
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
        $stats['passing_rate'] = round(($result['passing'] / $result['total']) * 100, 1);
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
    $stats['students_at_risk'] = (int)$stmt->fetchColumn();
    
    // Get today's schedule
    $today = date('l'); // Monday, Tuesday, etc.
    $stmt = $conn->prepare("
        SELECT 
            c.class_name,
            c.subject,
            c.section,
            s.day_of_week,
            s.start_time,
            s.end_time,
            s.room
        FROM schedules s
        INNER JOIN classes c ON s.class_id = c.class_id
        WHERE c.teacher_id = ? AND c.status = 'active' AND s.day_of_week = ?
        ORDER BY s.start_time ASC
    ");
    $stmt->execute([$teacher_id, $today]);
    $today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get class analytics (average grade per class)
    $stmt = $conn->prepare("
        SELECT 
            c.class_name,
            c.section,
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
    $class_analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    $subject_analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - indEx</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>teacher-pages/teacher-dashboard.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <div class="modern-dashboard">
                
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                        <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Modern Hero Welcome Section -->
                <section class="hero-section">
                    <div class="hero-gradient"></div>
                    <div class="hero-container">
                        <div class="hero-left">
                            <div class="greeting-chip">
                                <i class="fas fa-sparkles"></i>
                                <span><?php echo date('A') == 'AM' ? 'Good Morning' : (date('H') < 18 ? 'Good Afternoon' : 'Good Evening'); ?></span>
                            </div>
                            <h1 class="hero-heading">Welcome back, <span class="highlight"><?php echo htmlspecialchars(explode(' ', $teacher_name)[0]); ?></span></h1>
                            <p class="hero-text">Track your classes, monitor student progress, and manage your teaching activities all in one place.</p>
                            <div class="hero-actions">
                                <a href="create-class.php" class="btn-modern btn-primary">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Create New Class</span>
                                </a>
                                <a href="my-courses.php" class="btn-modern btn-secondary">
                                    <i class="fas fa-chalkboard"></i>
                                    <span>My Courses</span>
                                </a>
                            </div>
                        </div>
                        <div class="hero-right">
                            <div class="profile-card">
                                <div class="profile-avatar">
                                    <div class="avatar-ring"></div>
                                    <img src="<?php echo getProfilePicture($profile_picture, $teacher_name); ?>" 
                                         alt="<?php echo htmlspecialchars($teacher_name); ?>" 
                                         class="avatar-img"
                                         onerror="this.src='<?php echo BASE_URL; ?>assets/images/default-avatar.png'">
                                    <div class="status-indicator"></div>
                                </div>
                                <div class="profile-details">
                                    <h3 class="profile-name"><?php echo htmlspecialchars($teacher_name); ?></h3>
                                    <p class="profile-role">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span>Teacher</span>
                                    </p>
                                    <div class="profile-stats-mini">
                                        <div class="mini-stat">
                                            <i class="fas fa-book"></i>
                                            <span><?php echo $stats['total_classes']; ?> Classes</span>
                                        </div>
                                        <div class="mini-stat">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $stats['total_students']; ?> Students</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Stats Grid -->
                <section class="stats-section">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-target="<?php echo $stats['total_classes']; ?>">0</div>
                            <div class="stat-label">Total Classes</div>
                            <div class="stat-progress">
                                <div class="progress-bar" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card stat-success">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-target="<?php echo $stats['total_students']; ?>">0</div>
                            <div class="stat-label">Total Students</div>
                            <div class="stat-progress">
                                <div class="progress-bar" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card stat-info">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-target="<?php echo $stats['passing_rate']; ?>">0</div>
                            <div class="stat-label">Passing Rate</div>
                            <div class="stat-progress">
                                <div class="progress-bar" style="width: <?php echo $stats['passing_rate']; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card stat-warning">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" data-target="<?php echo $stats['students_at_risk']; ?>">0</div>
                            <div class="stat-label">Students at Risk</div>
                            <div class="stat-progress">
                                <div class="progress-bar" style="width: 50%"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Today's Schedule -->
                <section class="schedule-section">
                    <div class="section-header">
                        <div class="header-left">
                            <h2 class="section-title">
                                <i class="fas fa-calendar-day"></i>
                                Today's Schedule
                            </h2>
                            <p class="section-subtitle"><?php echo date('l, F j, Y'); ?></p>
                        </div>
                        <div class="header-right">
                            <span class="schedule-badge"><?php echo count($today_schedule); ?> Classes</span>
                        </div>
                    </div>
                    <div class="schedule-content">
                        <?php if (count($today_schedule) > 0): ?>
                            <div class="schedule-timeline">
                                <?php foreach ($today_schedule as $index => $sched): ?>
                                    <div class="schedule-item" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                        <div class="schedule-time">
                                            <div class="time-badge">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('g:i A', strtotime($sched['start_time'])); ?></span>
                                            </div>
                                            <div class="time-duration">
                                                <?php 
                                                $start = strtotime($sched['start_time']);
                                                $end = strtotime($sched['end_time']);
                                                $duration = round(($end - $start) / 60);
                                                echo $duration . ' min';
                                                ?>
                                            </div>
                                        </div>
                                        <div class="schedule-details">
                                            <div class="schedule-header-item">
                                                <h3 class="schedule-title"><?php echo htmlspecialchars($sched['class_name']); ?></h3>
                                                <span class="schedule-section"><?php echo htmlspecialchars($sched['section']); ?></span>
                                            </div>
                                            <div class="schedule-meta">
                                                <span class="meta-item">
                                                    <i class="fas fa-book"></i>
                                                    <?php echo htmlspecialchars($sched['subject']); ?>
                                                </span>
                                                <?php if (!empty($sched['room'])): ?>
                                                    <span class="meta-item">
                                                        <i class="fas fa-door-open"></i>
                                                        <?php echo htmlspecialchars($sched['room']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="meta-item">
                                                    <i class="fas fa-arrow-right"></i>
                                                    <?php echo date('g:i A', strtotime($sched['end_time'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h3 class="empty-title">No classes scheduled today</h3>
                                <p class="empty-text">Enjoy your day off or use this time to prepare for upcoming classes!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Analytics Charts -->
                <section class="charts-section">
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="header-left">
                                <h2 class="chart-title">
                                    <i class="fas fa-chart-bar"></i>
                                    Class Performance
                                </h2>
                                <p class="chart-subtitle">Average grades across all classes</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <?php if (count($class_analytics) > 0): ?>
                                <canvas id="classPerformanceChart"></canvas>
                            <?php else: ?>
                                <div class="empty-chart">
                                    <i class="fas fa-chart-bar"></i>
                                    <h3>No Data Available</h3>
                                    <p>Start adding grades to see class performance analytics</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="header-left">
                                <h2 class="chart-title">
                                    <i class="fas fa-book-open"></i>
                                    Subject Performance
                                </h2>
                                <p class="chart-subtitle">Average grades by subject area</p>
                            </div>
                        </div>
                        <div class="chart-body">
                            <?php if (count($subject_analytics) > 0): ?>
                                <canvas id="subjectPerformanceChart"></canvas>
                            <?php else: ?>
                                <div class="empty-chart">
                                    <i class="fas fa-book-open"></i>
                                    <h3>No Data Available</h3>
                                    <p>Grade distribution will appear once grades are recorded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        const classData = <?php echo json_encode($class_analytics); ?>;
        const subjectData = <?php echo json_encode($subject_analytics); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat values
            document.querySelectorAll('.stat-value').forEach(stat => {
                const target = parseFloat(stat.dataset.target);
                animateValue(stat, 0, target, 1500);
            });
            
            // Auto dismiss alerts
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
            
            // Initialize charts
            initializeCharts();
        });
        
        function animateValue(element, start, end, duration) {
            const isDecimal = end % 1 !== 0;
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    element.textContent = isDecimal ? end.toFixed(1) : Math.round(end);
                    clearInterval(timer);
                } else {
                    element.textContent = isDecimal ? current.toFixed(1) : Math.floor(current);
                }
            }, 16);
        }
        
        function initializeCharts() {
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#64748b';
            
            // Class Performance Chart
            const classCtx = document.getElementById('classPerformanceChart');
            if (classCtx && classData.length > 0) {
                const labels = classData.map(c => c.class_name + ' - ' + c.section);
                const data = classData.map(c => parseFloat(c.avg_grade));
                
                new Chart(classCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Average Grade',
                            data: data,
                            backgroundColor: 'rgba(127, 29, 29, 0.8)',
                            borderRadius: 8,
                            barThickness: 40
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: function(context) {
                                        return 'Average: ' + context.parsed.y.toFixed(1) + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) { return value + '%'; }
                                },
                                grid: { color: 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
            
            // Subject Performance Chart
            const subjectCtx = document.getElementById('subjectPerformanceChart');
            if (subjectCtx && subjectData.length > 0) {
                const labels = subjectData.map(s => s.subject);
                const data = subjectData.map(s => parseFloat(s.avg_grade));
                
                new Chart(subjectCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Average Grade',
                            data: data,
                            backgroundColor: 'rgba(212, 175, 55, 0.8)',
                            borderRadius: 8,
                            barThickness: 40
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: function(context) {
                                        return 'Average: ' + context.parsed.y.toFixed(1) + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) { return value + '%'; }
                                },
                                grid: { color: 'rgba(0, 0, 0, 0.05)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>