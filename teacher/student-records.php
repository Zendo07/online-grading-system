<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require teacher access
requireTeacher();

$teacher_id = $_SESSION['user_id'];

// Get specific student record if student_id and class_id provided
$student_record = null;
$grades = [];
$attendance_summary = [];

if (isset($_GET['student_id']) && isset($_GET['class_id'])) {
    $student_id = (int)$_GET['student_id'];
    $class_id = (int)$_GET['class_id'];
    
    try {
        // Verify teacher owns this class
        $stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $teacher_id]);
        $class_info = $stmt->fetch();
        
        if ($class_info) {
            // Get student info
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$student_id]);
            $student_info = $stmt->fetch();
            
            if ($student_info) {
                $student_record = [
                    'student' => $student_info,
                    'class' => $class_info
                ];
                
                // Get all grades for this student in this class
                $stmt = $conn->prepare("
                    SELECT * FROM grades 
                    WHERE student_id = ? AND class_id = ?
                    ORDER BY grading_period, created_at DESC
                ");
                $stmt->execute([$student_id, $class_id]);
                $grades = $stmt->fetchAll();
                
                // Calculate overall grade
                $overall_grade = calculateOverallGrade($conn, $student_id, $class_id);
                
                // Get missing activities
                $missing_activities = getMissingActivities($conn, $student_id, $class_id);
                
                // Get attendance summary
                $stmt = $conn->prepare("
                    SELECT 
                        status,
                        COUNT(*) as count
                    FROM attendance
                    WHERE student_id = ? AND class_id = ?
                    GROUP BY status
                ");
                $stmt->execute([$student_id, $class_id]);
                $attendance_data = $stmt->fetchAll();
                
                foreach ($attendance_data as $row) {
                    $attendance_summary[$row['status']] = $row['count'];
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Student Records Error: " . $e->getMessage());
    }
}

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - Online Grading System</title>
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
                    <h1>Student Records</h1>
                    <p class="breadcrumb">Home / Student Records</p>
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
                
                <?php if ($student_record): ?>
                    <!-- Student Information -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Student Information</h2>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                                <img 
                                    src="<?php echo getProfilePicture($student_record['student']['profile_picture'], $student_record['student']['full_name']); ?>" 
                                    alt="Profile" 
                                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color);"
                                >
                                <div style="flex: 1; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div>
                                        <strong>Name:</strong><br>
                                        <?php echo htmlspecialchars($student_record['student']['full_name']); ?>
                                    </div>
                                    <div>
                                        <strong>Email:</strong><br>
                                        <?php echo htmlspecialchars($student_record['student']['email']); ?>
                                    </div>
                                    <div>
                                        <strong>Class:</strong><br>
                                        <?php echo htmlspecialchars($student_record['class']['class_name']); ?>
                                    </div>
                                    <div>
                                        <strong>Section:</strong><br>
                                        <?php echo htmlspecialchars($student_record['class']['section']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overall Grade Summary -->
                    <?php if ($overall_grade): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Overall Grade Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                                <div class="stat-card">
                                    <div class="stat-info" style="text-align: center; width: 100%;">
                                        <h3>Midterm (50%)</h3>
                                        <div class="stat-value"><?php echo number_format($overall_grade['midterm'], 2); ?>%</div>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-info" style="text-align: center; width: 100%;">
                                        <h3>Finals (50%)</h3>
                                        <div class="stat-value"><?php echo number_format($overall_grade['finals'], 2); ?>%</div>
                                    </div>
                                </div>
                                <div class="stat-card" style="background: linear-gradient(135deg, #8B4049 0%, #6B3039 100%); color: white;">
                                    <div class="stat-info" style="text-align: center; width: 100%;">
                                        <h3 style="color: white;">Overall Grade</h3>
                                        <div class="stat-value" style="color: white; font-size: 2.5rem;"><?php echo number_format($overall_grade['overall'], 2); ?>%</div>
                                        <span class="badge" style="background: white; color: var(--primary-color); font-size: 1.125rem; margin-top: 0.5rem;">
                                            <?php echo $overall_grade['letter_grade']; ?>
                                        </span>
                                        <p style="margin-top: 0.5rem; color: white;">
                                            <strong><?php echo $overall_grade['status']; ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Missing Activities Alert -->
                    <?php if (count($missing_activities) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">⚠️ Missing Activities (<?php echo count($missing_activities); ?>)</h2>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <strong>Warning:</strong> This student has missing activities that need to be submitted.
                            </div>
                            <div class="data-table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Activity Name</th>
                                            <th>Type</th>
                                            <th>Period</th>
                                            <th>Max Score</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($missing_activities as $missing): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($missing['activity_name']); ?></td>
                                                <td><span class="badge badge-secondary"><?php echo ucfirst($missing['activity_type']); ?></span></td>
                                                <td><span class="badge badge-info"><?php echo ucfirst($missing['grading_period']); ?></span></td>
                                                <td><?php echo $missing['max_score']; ?> pts</td>
                                                <td><span class="badge badge-danger">❌ Not Submitted</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Attendance Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Attendance Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
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
                                    <div class="stat-icon" style="background-color: #e0e7ff; color: #6366f1;">📝</div>
                                    <div class="stat-info">
                                        <h3>Excused</h3>
                                        <div class="stat-value"><?php echo $attendance_summary['excused'] ?? 0; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Grades by Period -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Grades</h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($grades) > 0): ?>
                                <?php 
                                $grades_by_period = [];
                                foreach ($grades as $grade) {
                                    $grades_by_period[$grade['grading_period']][] = $grade;
                                }
                                ?>
                                
                                <?php foreach (['prelim', 'midterm', 'finals'] as $period): ?>
                                    <?php if (isset($grades_by_period[$period])): ?>
                                        <h3 style="text-transform: capitalize; margin-top: 1.5rem;"><?php echo $period; ?> Period</h3>
                                        <div class="data-table-wrapper">
                                            <table class="data-table" id="grades<?php echo ucfirst($period); ?>Table">
                                                <thead>
                                                    <tr>
                                                        <th class="sortable" onclick="sortTableAdvanced('grades<?php echo ucfirst($period); ?>Table', 0, 'text')">Activity</th>
                                                        <th>Type</th>
                                                        <th class="sortable" onclick="sortTableAdvanced('grades<?php echo ucfirst($period); ?>Table', 2, 'number')">Score</th>
                                                        <th class="sortable" onclick="sortTableAdvanced('grades<?php echo ucfirst($period); ?>Table', 3, 'number')">Percentage</th>
                                                        <th class="sortable" onclick="sortTableAdvanced('grades<?php echo ucfirst($period); ?>Table', 4, 'date')">Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($grades_by_period[$period] as $grade): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($grade['activity_name']); ?></td>
                                                            <td><span class="badge badge-info"><?php echo ucfirst($grade['activity_type']); ?></span></td>
                                                            <td><?php echo $grade['score']; ?> / <?php echo $grade['max_score']; ?></td>
                                                            <td><?php echo number_format($grade['percentage'], 2); ?>%</td>
                                                            <td><?php echo formatDate($grade['created_at']); ?></td>
                                                            <td>
                                                                <a href="#" class="btn btn-sm btn-danger" onclick="return confirm('Delete this grade?')">Delete</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📝</div>
                                    <h3>No Grades Yet</h3>
                                    <p>No grades have been recorded for this student.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <div class="empty-state-icon">📊</div>
                                <h3>Select a Student</h3>
                                <p>Go to "Manage Students" to view individual student records.</p>
                                <a href="manage-students.php" class="btn btn-primary">Go to Manage Students</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
</body>
</html>