<?php

header('Content-Type: text/html; charset=UTF-8');

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../api/teacher/view-grades-handler.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];

if (!isset($_GET['class_id'])) {
    header('Location: my-courses.php');
    exit();
}

$class_id = intval($_GET['class_id']);

$class_info = getClassInfo($class_id, $teacher_id);

if (!$class_info) {
    header('Location: my-courses.php');
    exit();
}

// Get enrolled students
$students = getEnrolledStudents($class_id);
$actual_student_count = count($students);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class_info['subject'] . ' - ' . $class_info['class_name']); ?> - Grade Spreadsheet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>teacher-pages/view-grades.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <div class="content-wrapper">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                        <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    </div>
                <?php endif; ?>

                <div class="container">
                    <div class="header">
                        <div class="header-left">
                            <a href="view-sections.php?course=<?php echo urlencode($class_info['subject']); ?>&name=<?php echo urlencode($class_info['class_name']); ?>" class="back-link">
                                <i class="fas fa-arrow-left"></i> Back to Sections
                            </a>
                            <div class="class-info-header">
                                <h1><?php echo htmlspecialchars($class_info['subject']); ?> - <?php echo htmlspecialchars($class_info['class_name']); ?></h1>
                                <p class="class-section">Section: <?php echo htmlspecialchars($class_info['section']); ?> | Code: <?php echo htmlspecialchars($class_info['class_code']); ?></p>
                            </div>
                        </div>
                        <div class="header-controls">
                            <div class="student-count">Total Students: <span id="studentCount"><?php echo $actual_student_count; ?></span></div>
                            
                            <div class="control-group">
                                <div class="grading-period-buttons">
                                    <button class="btn-period active" data-period="prelim" id="btnPrelim">Prelim</button>
                                    <button class="btn-period" data-period="midterm" id="btnMidterm">Midterm</button>
                                    <button class="btn-period" data-period="finals" id="btnFinals">Finals</button>
                                </div>
                                
                                <select id="sortSelect">
                                    <option value="name-az">Sort: A–Z</option>
                                    <option value="name-za">Sort: Z–A</option>
                                    <option value="grade-high">Sort: Highest–Lowest</option>
                                    <option value="grade-low">Sort: Lowest–Highest</option>
                                </select>
                            </div>

                            <button class="btn btn-refresh" id="refreshBtn" title="Refresh student list">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <div class="toolbar">
                        <div class="date-selector">
                            <label>Add Attendance for:</label>
                            <input type="date" id="attendanceDate">
                            <button class="btn btn-attendance" id="addAttendanceBtn">+ Add Attendance Column</button>
                        </div>

                        <div class="add-column-group">
                            <button class="btn btn-add" data-type="quiz">+ Quiz</button>
                            <button class="btn btn-add" data-type="assignment">+ Activity</button>
                            <button class="btn btn-add" data-type="project">+ Project</button>
                        </div>
                    </div>

                    <?php if ($actual_student_count === 0): ?>
                        <!-- Empty State when no students -->
                        <div class="empty-state-container">
                            <div class="empty-state">
                                <i class="fas fa-user-graduate"></i>
                                <h3>No Students Enrolled Yet</h3>
                                <p>Students can join this class using the class code:</p>
                                <div class="empty-class-code">
                                    <span class="code-display"><?php echo htmlspecialchars($class_info['class_code']); ?></span>
                                    <button class="btn-copy" onclick="copyClassCode('<?php echo htmlspecialchars($class_info['class_code']); ?>')">
                                        <i class="fas fa-copy"></i> Copy Code
                                    </button>
                                </div>
                                <p class="empty-hint">Once students join, they will automatically appear in the grade table below with default columns.</p>
                                <button class="btn btn-primary" id="refreshEmptyBtn">
                                    <i class="fas fa-sync-alt"></i> Check for New Students
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Grade Table - Always show with default columns -->
                    <div class="table-wrapper" id="gradeTableWrapper">
                        <table id="gradeTable">
                            <thead id="tableHeader">
                                <tr>
                                    <th style="min-width: 50px;">#</th>
                                    <th class="student-name-header" style="min-width: 250px;">
                                        Student Name
                                        <span class="date-label">Surname, Firstname</span>
                                    </th>

                                    <th style="min-width: 100px;">Total</th>
                                    <th style="min-width: 100px;">Average</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php if ($actual_student_count === 0): ?>
                                    <tr class="no-data-row">
                                        <td colspan="4" style="text-align: center; padding: 2rem; color: #999;">
                                            <i class="fas fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                            Waiting for students to enroll...
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="info-panel">
                        <div class="info-row">
                            <strong><i class="fas fa-calendar-check"></i> Attendance Columns:</strong> 
                            <span id="attendanceInfo">No attendance sessions added yet</span>
                        </div>
                        <div class="info-row">
                            <strong><i class="fas fa-chart-line"></i> Grade Columns:</strong> 
                            <span id="gradeInfo">Default columns loaded (Recitation, Quiz 1, Activity 1, Project 1, Exam)</span>
                        </div>
                        <div class="info-row">
                            <strong><i class="fas fa-info-circle"></i> Tip:</strong> 
                            <span>Default columns are automatically created for each student. Add more columns as needed. Changes are saved automatically.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <!-- Pass PHP data to JavaScript -->
    <script>
        window.classData = {
            class_id: <?php echo $class_id; ?>,
            teacher_id: <?php echo $teacher_id; ?>,
            class_info: <?php echo json_encode($class_info); ?>
        };
        
        // Function to copy class code
        function copyClassCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                const btn = event.target.closest('.btn-copy');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = '#28a745';
                
                setTimeout(function() {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy class code');
            });
        }
        
        let autoRefreshInterval = null;
        
        function checkForNewStudents() {
            const refreshBtn = document.getElementById('refreshBtn');
            if (refreshBtn) {
                refreshBtn.click();
            }
        }
        
        <?php if ($actual_student_count === 0): ?>
        autoRefreshInterval = setInterval(checkForNewStudents, 30000);
        <?php endif; ?>
        
        document.getElementById('refreshEmptyBtn')?.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
            checkForNewStudents();
        });
    </script>
    <script src="<?php echo JS_PATH; ?>teacher-pages/view-grades.js?v=<?php echo time(); ?>"></script>
</body>
</html>