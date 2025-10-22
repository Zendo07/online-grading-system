<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$student_id = $_SESSION['user_id'];

// Get students enrolled classes
try {
    $stmt = $conn->prepare("
        SELECT c.*
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.class_id
        WHERE e.student_id = ? AND e.status = 'active'
        ORDER BY c.class_name ASC
    ");
    $stmt->execute([$student_id]);
    $classes = $stmt->fetchAll();
    
    // Get selected class attendance
    $selected_class = null;
    $attendance_records = [];
    $attendance_summary = [];
    
    if (isset($_GET['class_id'])) {
        $class_id = (int)$_GET['class_id'];
        
        // Verify student is enrolled
        if (isStudentEnrolled($conn, $student_id, $class_id)) {
            $stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ?");
            $stmt->execute([$class_id]);
            $selected_class = $stmt->fetch();
            
            // Get attendance records
            $stmt = $conn->prepare("
                SELECT * FROM attendance 
                WHERE student_id = ? AND class_id = ?
                ORDER BY attendance_date DESC
            ");
            $stmt->execute([$student_id, $class_id]);
            $attendance_records = $stmt->fetchAll();
            
            // Calculate summary
            $stmt = $conn->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM attendance
                WHERE student_id = ? AND class_id = ?
                GROUP BY status
            ");
            $stmt->execute([$student_id, $class_id]);
            $summary_data = $stmt->fetchAll();
            
            foreach ($summary_data as $row) {
                $attendance_summary[$row['status']] = $row['count'];
            }
            
            // Calculate percentage
            $total = array_sum($attendance_summary);
            $present = $attendance_summary['present'] ?? 0;
            $attendance_percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;
        }
    }
    
} catch (PDOException $e) {
    error_log("My Attendance Error: " . $e->getMessage());
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="page-title-section">
                    <h1>My Attendance</h1>
                    <p class="breadcrumb">Home / My Attendance</p>
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
                
                <!-- Class Selection -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Select a Class</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($classes) > 0): ?>
                            <div class="form-group">
                                <label for="classSelect" class="form-label">Choose Class:</label>
                                <select id="classSelect" class="form-select" onchange="window.location.href='my-attendance.php?class_id=' + this.value">
                                    <option value="">-- Select a class --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>" <?php echo (isset($_GET['class_id']) && $_GET['class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo htmlspecialchars($class['section']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📚</div>
                                <h3>No Classes Yet</h3>
                                <p>Join a class to view your attendance</p>
                                <a href="join-class.php" class="btn btn-primary">Join Class</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($selected_class): ?>
                    <!-- Attendance Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Attendance Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon green">✅</div>
                                    <div class="stat-info">
                                        <h3>Present</h3>
                                        <div class="stat-value"><?php echo $attendance_summary['present'] ?? 0; ?></div>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon" style="background-color: #fee2e2; color: #ef4444;">❌</div>
                                    <div class="stat-info">
                                        <h3>Absent</h3>
                                        <div class="stat-value"><?php echo $attendance_summary['absent'] ?? 0; ?></div>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon yellow">⏰</div>
                                    <div class="stat-info">
                                        <h3>Late</h3>
                                        <div class="stat-value"><?php echo $attendance_summary['late'] ?? 0; ?></div>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon purple">📊</div>
                                    <div class="stat-info">
                                        <h3>Attendance Rate</h3>
                                        <div class="stat-value"><?php echo $attendance_percentage ?? 0; ?>%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Records -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                Attendance Records - <?php echo htmlspecialchars($selected_class['class_name']); ?>
                            </h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($attendance_records) > 0): ?>
                                <div class="data-table-wrapper">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendance_records as $record): ?>
                                                <tr>
                                                    <td><?php echo formatDate($record['attendance_date']); ?></td>
                                                    <td>
                                                        <?php
                                                        $badge_class = match($record['status']) {
                                                            'present' => 'badge-success',
                                                            'absent' => 'badge-danger',
                                                            'late' => 'badge-warning',
                                                            'excused' => 'badge-info',
                                                            default => 'badge-secondary'
                                                        };
                                                        $icon = match($record['status']) {
                                                            'present' => '✅',
                                                            'absent' => '❌',
                                                            'late' => '⏰',
                                                            'excused' => '📝',
                                                            default => '•'
                                                        };
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>">
                                                            <?php echo $icon . ' ' . ucfirst($record['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📋</div>
                                    <h3>No Attendance Records</h3>
                                    <p>Your teacher hasn't marked attendance for this class yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
</body>
</html>