<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require teacher access
requireTeacher();

$teacher_id = $_SESSION['user_id'];

// Get teacher's classes
try {
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(e.enrollment_id) as student_count
        FROM classes c
        LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
        WHERE c.teacher_id = ? AND c.status = 'active'
        GROUP BY c.class_id
        ORDER BY c.class_name ASC
    ");
    $stmt->execute([$teacher_id]);
    $classes = $stmt->fetchAll();
    
    // Get selected class students if class_id is provided
    $selected_class = null;
    $students = [];
    $attendance_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    if (isset($_GET['class_id'])) {
        $class_id = (int)$_GET['class_id'];
        
        // Verify teacher owns this class
        $stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $teacher_id]);
        $selected_class = $stmt->fetch();
        
        if ($selected_class) {
            // Get students in this class with their attendance for the selected date
            $stmt = $conn->prepare("
                SELECT 
                    u.user_id, 
                    u.full_name,
                    a.attendance_id,
                    a.status as attendance_status,
                    a.remarks
                FROM enrollments e
                INNER JOIN users u ON e.student_id = u.user_id
                LEFT JOIN attendance a ON a.student_id = u.user_id 
                    AND a.class_id = ? 
                    AND DATE(a.attendance_date) = ?
                WHERE e.class_id = ? AND e.status = 'active'
                ORDER BY u.full_name ASC
            ");
            $stmt->execute([$class_id, $attendance_date, $class_id]);
            $students = $stmt->fetchAll();
        }
    }
    
} catch (PDOException $e) {
    error_log("Attendance Error: " . $e->getMessage());
}

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Online Grading System</title>
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
                    <h1>Attendance Management</h1>
                    <p class="breadcrumb">Home / Attendance</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back</a>
                </div>
            </header>
            
            <div class="dashboard-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Class and Date Selection -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Select Class & Date</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($classes) > 0): ?>
                            <form method="GET" action="attendance.php">
                                <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="classSelect" class="form-label">Select Class:</label>
                                        <select id="classSelect" name="class_id" class="form-select" required>
                                            <option value="">-- Choose a class --</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?php echo $class['class_id']; ?>" <?php echo (isset($_GET['class_id']) && $_GET['class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo htmlspecialchars($class['section']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="dateSelect" class="form-label">Date:</label>
                                        <input 
                                            type="date" 
                                            id="dateSelect" 
                                            name="date" 
                                            class="form-control" 
                                            value="<?php echo $attendance_date; ?>"
                                            max="<?php echo date('Y-m-d'); ?>"
                                            required
                                        >
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Load Attendance</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📚</div>
                                <h3>No Classes Yet</h3>
                                <p>Create a class first to mark attendance</p>
                                <a href="create-class.php" class="btn btn-primary">Create Class</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($selected_class && count($students) > 0): ?>
                    <!-- Attendance Form -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                Mark Attendance - <?php echo htmlspecialchars($selected_class['class_name']); ?>
                            </h2>
                            <small class="text-muted">Date: <?php echo formatDate($attendance_date); ?></small>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>api/teacher/update-attendance.php" method="POST" id="attendanceForm">
                                <input type="hidden" name="class_id" value="<?php echo $selected_class['class_id']; ?>">
                                <input type="hidden" name="attendance_date" value="<?php echo $attendance_date; ?>">
                                
                                <div class="data-table-wrapper">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Student Name</th>
                                                <th>Status</th>
                                                <th>Remarks (Optional)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $index => $student): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                    <td>
                                                        <input type="hidden" name="students[<?php echo $student['user_id']; ?>][student_id]" value="<?php echo $student['user_id']; ?>">
                                                        <select name="students[<?php echo $student['user_id']; ?>][status]" class="form-select" style="width: 150px;" required>
                                                            <option value="present" <?php echo ($student['attendance_status'] == 'present') ? 'selected' : ''; ?>>✅ Present</option>
                                                            <option value="absent" <?php echo ($student['attendance_status'] == 'absent') ? 'selected' : ''; ?>>❌ Absent</option>
                                                            <option value="late" <?php echo ($student['attendance_status'] == 'late') ? 'selected' : ''; ?>>⏰ Late</option>
                                                            <option value="excused" <?php echo ($student['attendance_status'] == 'excused') ? 'selected' : ''; ?>>📝 Excused</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input 
                                                            type="text" 
                                                            name="students[<?php echo $student['user_id']; ?>][remarks]" 
                                                            class="form-control" 
                                                            placeholder="Add remarks..."
                                                            value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>"
                                                        >
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                                    <button type="submit" class="btn btn-success btn-lg">💾 Save Attendance</button>
                                    <button type="button" class="btn btn-secondary" onclick="markAllPresent()">✅ Mark All Present</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif ($selected_class && count($students) === 0): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <div class="empty-state-icon">👥</div>
                                <h3>No Students Enrolled</h3>
                                <p>There are no students in this class yet.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        // Mark all students as present
        function markAllPresent() {
            const selects = document.querySelectorAll('select[name*="[status]"]');
            selects.forEach(select => {
                select.value = 'present';
            });
        }
        
        // Form submission
        document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to save this attendance record?')) {
                e.preventDefault();
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>