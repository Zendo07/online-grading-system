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
        ORDER BY c.created_at DESC
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
                SELECT u.user_id, u.full_name, u.email, e.enrolled_at, e.enrollment_id
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
    error_log("Manage Students Error: " . $e->getMessage());
}

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Online Grading System</title>
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
                    <h1>Manage Students</h1>
                    <p class="breadcrumb">Home / Manage Students</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back</a>
                </div>
            </header>
            
            <div class="dashboard-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo $flash['message']; ?>
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
                                <select id="classSelect" class="form-select" onchange="window.location.href='manage-students.php?class_id=' + this.value">
                                    <option value="">-- Select a class --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['class_id']; ?>" <?php echo (isset($_GET['class_id']) && $_GET['class_id'] == $class['class_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo htmlspecialchars($class['section']); ?> (<?php echo $class['student_count']; ?> students)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📚</div>
                                <h3>No Classes Yet</h3>
                                <p>Create a class first to manage students</p>
                                <a href="create-class.php" class="btn btn-primary">Create Class</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($selected_class): ?>
                    <!-- Class Info -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Class Information</h2>
                        </div>
                        <div class="card-body">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <div>
                                    <strong>Class Name:</strong><br>
                                    <?php echo htmlspecialchars($selected_class['class_name']); ?>
                                </div>
                                <div>
                                    <strong>Subject:</strong><br>
                                    <?php echo htmlspecialchars($selected_class['subject']); ?>
                                </div>
                                <div>
                                    <strong>Section:</strong><br>
                                    <?php echo htmlspecialchars($selected_class['section']); ?>
                                </div>
                                <div>
                                    <strong>Class Code:</strong><br>
                                    <span style="font-size: 1.25rem; font-weight: bold; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($selected_class['class_code']); ?>
                                    </span>
                                    <button onclick="copyToClipboard('<?php echo $selected_class['class_code']; ?>')" class="btn btn-sm btn-secondary ml-1">
                                        📋 Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Students List -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Enrolled Students (<?php echo count($students); ?>)</h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($students) > 0): ?>
                                <div class="data-table-wrapper">
                                    <table class="data-table" id="studentsTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Student Name</th>
                                                <th>Email</th>
                                                <th>Enrolled Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $index => $student): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                    <td><?php echo formatDate($student['enrolled_at']); ?></td>
                                                    <td>
                                                        <a href="student-records.php?student_id=<?php echo $student['user_id']; ?>&class_id=<?php echo $selected_class['class_id']; ?>" class="btn btn-sm btn-primary">View Records</a>
                                                        <a href="<?php echo BASE_URL; ?>api/teacher/remove-student.php?enrollment_id=<?php echo $student['enrollment_id']; ?>&class_id=<?php echo $selected_class['class_id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Are you sure you want to remove this student from the class?')">
                                                            Remove
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
                                    <h3>No Students Enrolled Yet</h3>
                                    <p>Share the class code <strong><?php echo htmlspecialchars($selected_class['class_code']); ?></strong> with your students</p>
                                    <button onclick="copyToClipboard('<?php echo $selected_class['class_code']; ?>')" class="btn btn-primary">
                                        📋 Copy Class Code
                                    </button>
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