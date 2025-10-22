<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$student_id = $_SESSION['user_id'];

// Get student's enrolled classes
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
    
    // Get selected class grades
    $selected_class = null;
    $grades = [];
    $grade_summary = [];
    
    if (isset($_GET['class_id'])) {
        $class_id = (int)$_GET['class_id'];
        
        // Verify student is enrolled
        if (isStudentEnrolled($conn, $student_id, $class_id)) {
            $stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ?");
            $stmt->execute([$class_id]);
            $selected_class = $stmt->fetch();
            
            // Get all grades
            $stmt = $conn->prepare("
                SELECT * FROM grades 
                WHERE student_id = ? AND class_id = ?
                ORDER BY grading_period, created_at DESC
            ");
            $stmt->execute([$student_id, $class_id]);
            $grades = $stmt->fetchAll();
            
            // Calculate averages per period
            foreach (['prelim', 'midterm', 'finals'] as $period) {
                $period_grades = array_filter($grades, fn($g) => $g['grading_period'] == $period);
                if (count($period_grades) > 0) {
                    $total_percentage = array_sum(array_column($period_grades, 'percentage'));
                    $average = $total_percentage / count($period_grades);
                    $grade_summary[$period] = [
                        'average' => $average,
                        'letter' => getLetterGrade($average),
                        'count' => count($period_grades)
                    ];
                }
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("My Grades Error: " . $e->getMessage());
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - Online Grading System</title>
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
                    <h1>My Grades</h1>
                    <p class="breadcrumb">Home / My Grades</p>
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
                                <select id="classSelect" class="form-select" onchange="window.location.href='my-grades.php?class_id=' + this.value">
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
                                <p>Join a class to view your grades</p>
                                <a href="join-class.php" class="btn btn-primary">Join Class</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($selected_class): ?>
                    <!-- Grade Summary -->
                    <?php if (count($grade_summary) > 0): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Grade Summary</h2>
                            </div>
                            <div class="card-body">
                                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                                    <?php foreach ($grade_summary as $period => $summary): ?>
                                        <div class="stat-card">
                                            <div class="stat-info" style="text-align: center; width: 100%;">
                                                <h3 style="text-transform: capitalize;"><?php echo $period; ?></h3>
                                                <div class="stat-value"><?php echo number_format($summary['average'], 2); ?>%</div>
                                                <span class="badge badge-success" style="font-size: 1rem; margin-top: 0.5rem;">
                                                    <?php echo $summary['letter']; ?>
                                                </span>
                                                <p style="margin-top: 0.5rem; color: var(--text-light); font-size: 0.875rem;">
                                                    <?php echo $summary['count']; ?> activities
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Detailed Grades -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                Detailed Grades - <?php echo htmlspecialchars($selected_class['class_name']); ?>
                            </h2>
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
                                        <h3 style="text-transform: capitalize; margin-top: 1.5rem; margin-bottom: 1rem;">
                                            <?php echo $period; ?> Period
                                        </h3>
                                        <div class="data-table-wrapper">
                                            <table class="data-table" id="myGrades<?php echo ucfirst($period); ?>Table">
                                                <thead>
                                                    <tr>
                                                        <th class="sortable" onclick="sortTableAdvanced('myGrades<?php echo ucfirst($period); ?>Table', 0, 'text')">Activity</th>
                                                        <th>Type</th>
                                                        <th class="sortable" onclick="sortTableAdvanced('myGrades<?php echo ucfirst($period); ?>Table', 2, 'number')">Score</th>
                                                        <th class="sortable" onclick="sortTableAdvanced('myGrades<?php echo ucfirst($period); ?>Table', 3, 'number')">Percentage</th>
                                                        <th class="sortable" onclick="sortTableAdvanced('myGrades<?php echo ucfirst($period); ?>Table', 4, 'date')">Date</th>
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
                                    <p>Your teacher hasn't posted any grades for this class yet.</p>
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