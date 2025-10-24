<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require teacher access
requireTeacher();

// Get teacher data
$teacher_id = $_SESSION['user_id'];

// Get statistics (same as before)
try {
    // Total classes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM classes WHERE teacher_id = ? AND status = 'active'");
    $stmt->execute([$teacher_id]);
    $total_classes = $stmt->fetch()['total'];
    
    // Total students
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT e.student_id) as total 
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.class_id
        WHERE c.teacher_id = ? AND e.status = 'active' AND c.status = 'active'
    ");
    $stmt->execute([$teacher_id]);
    $total_students = $stmt->fetch()['total'];
    
    // Recent attendance
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM attendance a
        INNER JOIN classes c ON a.class_id = c.class_id
        WHERE c.teacher_id = ? AND DATE(a.attendance_date) = CURDATE()
    ");
    $stmt->execute([$teacher_id]);
    $today_attendance = $stmt->fetch()['total'];
    
    // Total grades
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM grades g
        INNER JOIN classes c ON g.class_id = c.class_id
        WHERE c.teacher_id = ?
    ");
    $stmt->execute([$teacher_id]);
    $total_grades = $stmt->fetch()['total'];
    
    // Get recent classes
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(e.enrollment_id) as student_count
        FROM classes c
        LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
        WHERE c.teacher_id = ? AND c.status = 'active'
        GROUP BY c.class_id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$teacher_id]);
    $recent_classes = $stmt->fetchAll();
    
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
    <title>Teacher Dashboard - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <div class="dashboard-content">
                <div style="margin-bottom: 24px;">
                    <h1 style="font-size: 2rem; margin: 0 0 8px 0; color: #202124;">Dashboard</h1>
                    <p style="color: #5f6368; margin: 0;">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                </div>
                
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 24px;">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">📚</div>
                        <div class="stat-info">
                            <h3>Total Classes</h3>
                            <div class="stat-value"><?php echo $total_classes; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">👥</div>
                        <div class="stat-info">
                            <h3>Total Students</h3>
                            <div class="stat-value"><?php echo $total_students; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon yellow">📋</div>
                        <div class="stat-info">
                            <h3>Today's Attendance</h3>
                            <div class="stat-value"><?php echo $today_attendance; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon purple">📝</div>
                        <div class="stat-info">
                            <h3>Grade Entries</h3>
                            <div class="stat-value"><?php echo $total_grades; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <a href="create-class.php" class="btn btn-primary">➕ Create New Class</a>
                            <a href="attendance.php" class="btn btn-success">📋 Mark Attendance</a>
                            <a href="grades.php" class="btn btn-warning">📝 Input Grades</a>
                            <a href="my-courses.php" class="btn btn-info">📊 View Courses</a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Classes -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Your Classes</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_classes) > 0): ?>
                            <div class="data-table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Class Code</th>
                                            <th>Class Name</th>
                                            <th>Subject</th>
                                            <th>Section</th>
                                            <th>Students</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_classes as $class): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($class['class_code']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['subject']); ?></td>
                                                <td><?php echo htmlspecialchars($class['section']); ?></td>
                                                <td><?php echo $class['student_count']; ?> students</td>
                                                <td><?php echo formatDate($class['created_at']); ?></td>
                                                <td>
                                                    <a href="manage-students.php?class_id=<?php echo $class['class_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📚</div>
                                <h3>No Classes Yet</h3>
                                <p>Create your first class to get started</p>
                                <a href="create-class.php" class="btn btn-primary">Create Class</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script src="<?php echo JS_PATH; ?>dashboard-nav.js?v=<?php echo time(); ?>"></script>
</body>
</html>