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
    
    // Total grades
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM grades WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_grades = $stmt->fetch()['total'];
    
    // Total attendance records
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_attendance = $stmt->fetch()['total'];
    
    // Present attendance count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ? AND status = 'present'");
    $stmt->execute([$student_id]);
    $present_count = $stmt->fetch()['total'];
    
    // Calculate attendance percentage
    $attendance_percentage = $total_attendance > 0 ? round(($present_count / $total_attendance) * 100, 2) : 0;
    
    // Get enrolled classes
    $stmt = $conn->prepare("
        SELECT c.*, u.full_name as teacher_name
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.class_id
        INNER JOIN users u ON c.teacher_id = u.user_id
        WHERE e.student_id = ? AND e.status = 'active'
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([$student_id]);
    $classes = $stmt->fetchAll();
    
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
    <title>Student Dashboard - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="page-title-section">
                    <h1>Dashboard</h1>
                    <p class="breadcrumb">Home / Dashboard</p>
                </div>
                <div class="header-actions">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                </div>
            </header>
            
            <div class="dashboard-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">📚</div>
                        <div class="stat-info">
                            <h3>Enrolled Classes</h3>
                            <div class="stat-value"><?php echo $total_classes; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">📝</div>
                        <div class="stat-info">
                            <h3>Total Grades</h3>
                            <div class="stat-value"><?php echo $total_grades; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon yellow">📋</div>
                        <div class="stat-info">
                            <h3>Attendance Records</h3>
                            <div class="stat-value"><?php echo $total_attendance; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon purple">✅</div>
                        <div class="stat-info">
                            <h3>Attendance Rate</h3>
                            <div class="stat-value"><?php echo $attendance_percentage; ?>%</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <a href="join-class.php" class="btn btn-primary">➕ Join New Class</a>
                            <a href="my-grades.php" class="btn btn-success">📝 View My Grades</a>
                            <a href="my-attendance.php" class="btn btn-warning">📋 Check Attendance</a>
                        </div>
                    </div>
                </div>
                
                <!-- My Classes -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">My Classes</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($classes) > 0): ?>
                            <div class="data-table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Class Name</th>
                                            <th>Subject</th>
                                            <th>Section</th>
                                            <th>Teacher</th>
                                            <th>Enrolled Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['subject']); ?></td>
                                                <td><?php echo htmlspecialchars($class['section']); ?></td>
                                                <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                                                <td><?php echo formatDate($class['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📚</div>
                                <h3>No Classes Yet</h3>
                                <p>Join a class to get started</p>
                                <a href="join-class.php" class="btn btn-primary">Join Class</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
</body>
</html>