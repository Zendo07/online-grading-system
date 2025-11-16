<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../api/teacher/dashboard-handler.php'; // Backend logic

requireTeacher();

// Get data from handler
$data = getDashboardData($_SESSION['user_id']);
$teacher_name = $_SESSION['full_name'];
$profile_picture = $_SESSION['profile_picture'] ?? null;
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - indEx</title>
    
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>teacher-pages/teacher-dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                    <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-container">
                
                
                <div class="welcome-header">
                    <div class="welcome-decoration">
                        <div class="decoration-circle"></div>
                        <div class="decoration-circle"></div>
                        <div class="decoration-circle"></div>
                    </div>
                    
                    <div class="welcome-content">
                        <div class="profile-section">
                            <div class="profile-avatar-container">
                                <img src="<?php echo getProfilePicture($profile_picture, $teacher_name); ?>" 
                                     alt="<?php echo htmlspecialchars($teacher_name); ?>" 
                                     class="profile-avatar"
                                     onerror="this.src='<?php echo BASE_URL; ?>assets/images/default-avatar.png'">
                                <div class="profile-status"></div>
                                <div class="profile-badge">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="welcome-text">
                            <div class="welcome-greeting">
                                <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $teacher_name)[0]); ?>!</h1>
                                <span class="greeting-wave">👋</span>
                            </div>
                            <div class="welcome-meta">
                                <div class="meta-date">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('l, F j, Y'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Stats -->
                <div class="summary-stats">
                    <div class="stat-mini">
                        <div class="stat-mini-icon">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div class="stat-mini-info">
                            <div class="stat-mini-value"><?php echo $data['stats']['total_classes']; ?></div>
                            <div class="stat-mini-label">Total Classes</div>
                        </div>
                    </div>
                    
                    <div class="stat-mini stat-success">
                        <div class="stat-mini-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-mini-info">
                            <div class="stat-mini-value"><?php echo $data['stats']['total_students']; ?></div>
                            <div class="stat-mini-label">Total Students</div>
                        </div>
                    </div>
                    
                    <div class="stat-mini">
                        <div class="stat-mini-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-mini-info">
                            <div class="stat-mini-value"><?php echo $data['stats']['passing_rate']; ?>%</div>
                            <div class="stat-mini-label">Passing Rate</div>
                        </div>
                    </div>
                    
                    <div class="stat-mini stat-danger">
                        <div class="stat-mini-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-mini-info">
                            <div class="stat-mini-value"><?php echo $data['stats']['students_at_risk']; ?></div>
                            <div class="stat-mini-label">Students at Risk</div>
                        </div>
                    </div>
                </div>
                
                <div class="content-grid">
                    
                    <!-- Today's Schedule -->
                    <div class="schedule-section">
                        <div class="section-header">
                            <h2>
                                <i class="fas fa-calendar-day"></i>
                                Today's Schedule
                            </h2>
                            <span class="section-badge"><?php echo count($data['today_schedule']); ?> Classes</span>
                        </div>
                        <div class="schedule-content">
                            <?php if (count($data['today_schedule']) > 0): ?>
                                <?php foreach ($data['today_schedule'] as $sched): ?>
                                    <div class="schedule-card">
                                        <div class="schedule-time">
                                            <?php echo date('g:i A', strtotime($sched['start_time'])); ?>
                                        </div>
                                        <div class="schedule-info">
                                            <div class="schedule-title">
                                                <?php echo htmlspecialchars($sched['class_name']); ?>
                                            </div>
                                            <div class="schedule-details">
                                                <span>
                                                    <i class="fas fa-book"></i>
                                                    <?php echo htmlspecialchars($sched['subject']); ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-users"></i>
                                                    <?php echo htmlspecialchars($sched['section']); ?>
                                                </span>
                                                <?php if (!empty($sched['room'])): ?>
                                                    <span>
                                                        <i class="fas fa-door-open"></i>
                                                        <?php echo htmlspecialchars($sched['room']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>No classes scheduled for today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Performance Chart -->
                    <div class="chart-section">
                        <div class="section-header">
                            <h2>
                                <i class="fas fa-chart-bar"></i>
                                Class Performance
                            </h2>
                        </div>
                        <div class="chart-content">
                            <?php if (count($data['class_analytics']) > 0): ?>
                                <canvas id="performanceChart"></canvas>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-bar"></i>
                                    <p>No performance data available yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.dashboardData = {
            classAnalytics: <?php echo json_encode($data['class_analytics']); ?>,
            subjectAnalytics: <?php echo json_encode($data['subject_analytics']); ?>
        };
    </script>
    <script src="<?php echo JS_PATH; ?>teacher-dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>