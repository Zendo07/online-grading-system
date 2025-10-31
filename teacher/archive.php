<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require teacher access
requireTeacher();

$teacher_id = $_SESSION['user_id'];

// Initialize variables
$archived_classes = [];
$dropped_students = [];

// Get archived classes
try {
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(e.enrollment_id) as student_count
        FROM classes c
        LEFT JOIN enrollments e ON c.class_id = e.class_id
        WHERE c.teacher_id = ? AND c.status = 'archived'
        GROUP BY c.class_id
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$teacher_id]);
    $archived_classes = $stmt->fetchAll();
    
    // Get dropped students
    $stmt = $conn->prepare("
        SELECT e.*, u.full_name, u.email, c.class_name, c.section
        FROM enrollments e
        INNER JOIN users u ON e.student_id = u.user_id
        INNER JOIN classes c ON e.class_id = c.class_id
        WHERE c.teacher_id = ? AND e.status = 'dropped'
        ORDER BY e.updated_at DESC
    ");
    $stmt->execute([$teacher_id]);
    $dropped_students = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Archive Error: " . $e->getMessage());
    $archived_classes = [];
    $dropped_students = [];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="page-title-section">
                    <h1>Archive</h1>
                    <p class="breadcrumb">Home / Archive</p>
                </div>
            </header>
            
            <div class="dashboard-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Archived Classes -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Archived Classes</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($archived_classes) > 0): ?>
                            <div class="data-table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Class Code</th>
                                            <th>Class Name</th>
                                            <th>Subject</th>
                                            <th>Section</th>
                                            <th>Students</th>
                                            <th>Archived Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($archived_classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['class_code']); ?></td>
                                                <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['subject']); ?></td>
                                                <td><?php echo htmlspecialchars($class['section']); ?></td>
                                                <td><?php echo $class['student_count']; ?></td>
                                                <td><?php echo formatDate($class['updated_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">🗄️</div>
                                <h3>No Archived Classes</h3>
                                <p>You don't have any archived classes yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Dropped Students -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Dropped Students</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($dropped_students) > 0): ?>
                            <div class="data-table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Email</th>
                                            <th>Class</th>
                                            <th>Section</th>
                                            <th>Dropped Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dropped_students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['section']); ?></td>
                                                <td><?php echo formatDate($student['updated_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">👥</div>
                                <h3>No Dropped Students</h3>
                                <p>No students have been removed from classes.</p>
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