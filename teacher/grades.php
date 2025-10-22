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
    
    if (isset($_GET['class_id'])) {
        $class_id = (int)$_GET['class_id'];
        
        // Verify teacher owns this class
        $stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $teacher_id]);
        $selected_class = $stmt->fetch();
        
        if ($selected_class) {
            // Get students in this class
            $stmt = $conn->prepare("
                SELECT u.user_id, u.full_name
                FROM enrollments e
                INNER JOIN users u ON e.student_id = u.user_id
                WHERE e.class_id = ? AND e.status = 'active'
                ORDER BY u.full_name ASC
            ");
            $stmt->execute([$class_id]);
            $students = $stmt->fetchAll();
        }
    }
    
} catch (PDOException $e) {
    error_log("Grades Error: " . $e->getMessage());
}

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades - Online Grading System</title>
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
                    <h1>Grades Management</h1>
                    <p class="breadcrumb">Home / Grades</p>
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
                                <select id="classSelect" class="form-select" onchange="window.location.href='grades.php?class_id=' + this.value">
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
                                <p>Create a class first to manage grades</p>
                                <a href="create-class.php" class="btn btn-primary">Create Class</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($selected_class): ?>
                    <!-- Add Grade Form -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Add New Grade</h2>
                        </div>
                        <div class="card-body">
                            <button onclick="openModal('addGradeModal')" class="btn btn-primary">
                                ➕ Add Grade Entry
                            </button>
                        </div>
                    </div>
                    
                    <!-- Students List with Grades -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                Students & Grades - <?php echo htmlspecialchars($selected_class['class_name']); ?>
                            </h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($students) > 0): ?>
                                <div class="data-table-wrapper">
                                    <table class="data-table" id="gradesTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th class="sortable" onclick="sortTableAdvanced('gradesTable', 1, 'text')">Student Name</th>
                                                <th class="sortable" onclick="sortTableAdvanced('gradesTable', 2, 'number')">Total Grades</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $index => $student): 
                                                // Count grades for this student
                                                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM grades WHERE student_id = ? AND class_id = ?");
                                                $stmt->execute([$student['user_id'], $selected_class['class_id']]);
                                                $grade_count = $stmt->fetch()['total'];
                                            ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                    <td><?php echo $grade_count; ?> entries</td>
                                                    <td>
                                                        <a href="student-records.php?student_id=<?php echo $student['user_id']; ?>&class_id=<?php echo $selected_class['class_id']; ?>" class="btn btn-sm btn-primary">
                                                            View All Grades
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">👥</div>
                                    <h3>No Students Enrolled</h3>
                                    <p>There are no students in this class yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Grade Modal -->
    <div id="addGradeModal" class="modal" style="overflow-y: auto;">
        <div class="modal-content" style="margin: 2rem auto;">
            <div class="modal-header">
                <h3 class="modal-title">Add Grade Entry</h3>
                <button class="modal-close" onclick="closeModal('addGradeModal')" type="button">×</button>
            </div>
            <form action="<?php echo BASE_URL; ?>api/teacher/update-grades.php" method="POST">
                <div class="modal-body" style="max-height: none;">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class['class_id'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label for="studentSelect" class="form-label">Select Student <span style="color: red;">*</span></label>
                        <select id="studentSelect" name="student_id" class="form-select" required>
                            <option value="">-- Choose student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['user_id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="activityName" class="form-label">Activity Name <span style="color: red;">*</span></label>
                        <input type="text" id="activityName" name="activity_name" class="form-control" placeholder="e.g., Quiz 1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="activityType" class="form-label">Activity Type <span style="color: red;">*</span></label>
                        <select id="activityType" name="activity_type" class="form-select" required>
                            <option value="quiz">Quiz</option>
                            <option value="exam">Exam</option>
                            <option value="assignment">Assignment</option>
                            <option value="project">Project</option>
                            <option value="recitation">Recitation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="score" class="form-label">Score <span style="color: red;">*</span></label>
                            <input type="number" id="score" name="score" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="maxScore" class="form-label">Max Score <span style="color: red;">*</span></label>
                            <input type="number" id="maxScore" name="max_score" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="gradingPeriod" class="form-label">Grading Period <span style="color: red;">*</span></label>
                        <select id="gradingPeriod" name="grading_period" class="form-select" required>
                            <option value="midterm">Midterm</option>
                            <option value="finals">Finals</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addGradeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Grade</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
</body>
</html>