<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require teacher access
requireTeacher();

$teacher_id = $_SESSION['user_id'];

// Get teacher's courses grouped by subject
try {
    $stmt = $conn->prepare("
        SELECT 
            c.subject,
            c.class_name,
            COUNT(DISTINCT c.class_id) as section_count,
            COUNT(DISTINCT e.student_id) as total_students
        FROM classes c
        LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
        WHERE c.teacher_id = ? AND c.status = 'active'
        GROUP BY c.subject, c.class_name
        ORDER BY c.subject ASC
    ");
    $stmt->execute([$teacher_id]);
    $courses = $stmt->fetchAll();
    
    // If specific course is selected
    $selected_course = null;
    $sections = [];
    
    if (isset($_GET['course'])) {
        $course_subject = $_GET['course'];
        $course_name = $_GET['name'] ?? '';
        
        // Get sections for this course
        $stmt = $conn->prepare("
            SELECT 
                c.*,
                COUNT(DISTINCT e.student_id) as student_count
            FROM classes c
            LEFT JOIN enrollments e ON c.class_id = e.class_id AND e.status = 'active'
            WHERE c.teacher_id = ? AND c.subject = ? AND c.class_name = ? AND c.status = 'active'
            GROUP BY c.class_id
            ORDER BY c.section ASC
        ");
        $stmt->execute([$teacher_id, $course_subject, $course_name]);
        $sections = $stmt->fetchAll();
        
        $selected_course = [
            'subject' => $course_subject,
            'name' => $course_name
        ];
    }
    
    // If specific section is selected - show spreadsheet
    $selected_section = null;
    $students = [];
    $grading_period = isset($_GET['period']) ? $_GET['period'] : 'midterm';
    
   if (isset($_GET['section_id'])) {
    $section_id = (int)$_GET['section_id'];
    
    // Verify teacher owns this section
    $stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ? AND teacher_id = ?");
    $stmt->execute([$section_id, $teacher_id]);
    $selected_section = $stmt->fetch();
    
    if ($selected_section) {
        // Get students with their grades and attendance
        $stmt = $conn->prepare("
            SELECT DISTINCT
                u.user_id,
                u.full_name,
                u.email,
                u.profile_picture,
                e.enrollment_id
            FROM enrollments e
            INNER JOIN users u ON e.student_id = u.user_id
            WHERE e.class_id = ? AND e.status = 'active'
            ORDER BY u.full_name ASC
        ");
        $stmt->execute([$section_id]);
        $students = $stmt->fetchAll();
        
        // Get grades and attendance for each student
        $processed = [];
        foreach ($students as $key => $student) {
            // Skip duplicates
            if (isset($processed[$student['user_id']])) {
                unset($students[$key]);
                continue;
            }
            $processed[$student['user_id']] = true;
            
            // Attendance
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                FROM attendance
                WHERE student_id = ? AND class_id = ?
            ");
            $stmt->execute([$student['user_id'], $section_id]);
            $attendance = $stmt->fetch();
            $students[$key]['attendance'] = $attendance['total'] > 0 
                ? round(($attendance['present'] / $attendance['total']) * 100, 2) 
                : 0;
            
            // Grades by type
            $types = ['quiz' => 'quizzes', 'assignment' => 'activities', 'project' => 'projects', 
                     'recitation' => 'recitation', 'exam' => 'exam'];
            
            foreach ($types as $db_type => $type_key) {
                $stmt = $conn->prepare("
                    SELECT AVG(percentage) as avg_grade
                    FROM grades
                    WHERE student_id = ? AND class_id = ? 
                    AND activity_type = ? AND grading_period = ?
                ");
                $stmt->execute([$student['user_id'], $section_id, $db_type, $grading_period]);
                $grade = $stmt->fetch();
                $students[$key][$type_key] = $grade['avg_grade'] ?? 0;
            }
            
            // Overall
            $students[$key]['overall'] = ($students[$key]['quizzes'] + $students[$key]['activities'] + 
                                  $students[$key]['projects'] + $students[$key]['recitation'] + 
                                  $students[$key]['exam']) / 5;
        }
        $students = array_values($students); // Reindex array
    }
}
    
} catch (PDOException $e) {
    error_log("My Courses Error: " . $e->getMessage());
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>my-courses.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="page-title-section">
                    <h1>
                        <?php if ($selected_section): ?>
                            <?php echo htmlspecialchars($selected_section['class_name']); ?> - <?php echo htmlspecialchars($selected_section['section']); ?>
                        <?php elseif ($selected_course): ?>
                            <?php echo htmlspecialchars($selected_course['name']); ?>
                        <?php else: ?>
                            My Courses
                        <?php endif; ?>
                    </h1>
                    <p class="breadcrumb">
                        <a href="my-courses.php">My Courses</a>
                        <?php if ($selected_course): ?>
                            / <a href="my-courses.php?course=<?php echo urlencode($selected_course['subject']); ?>&name=<?php echo urlencode($selected_course['name']); ?>">
                                <?php echo htmlspecialchars($selected_course['name']); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($selected_section): ?>
                            / <?php echo htmlspecialchars($selected_section['section']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="header-actions">
                    <?php if ($selected_section): ?>
                        <a href="my-courses.php?course=<?php echo urlencode($selected_course['subject']); ?>&name=<?php echo urlencode($selected_course['name']); ?>" class="btn btn-secondary btn-sm">← Back to Sections</a>
                    <?php elseif ($selected_course): ?>
                        <a href="my-courses.php" class="btn btn-secondary btn-sm">← Back to Courses</a>
                    <?php endif; ?>
                </div>
            </header>
            
            <div class="dashboard-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($selected_section): ?>
                    <!-- SPREADSHEET VIEW -->
                    <div class="spreadsheet-container">
                        <div class="spreadsheet-header">
                            <h2 class="spreadsheet-title">Student Records</h2>
                            <div class="spreadsheet-controls">
                                <button class="period-toggle" id="periodToggle" onclick="togglePeriod()">
                                    <?php echo ucfirst($grading_period); ?>
                                </button>
                                
                                <button class="btn btn-success btn-sm" onclick="markAllPresent()">
                                    ✅ Mark All Present
                                </button>
                                
                                <div class="sort-dropdown">
                                    <button class="sort-btn">Sort <span>▼</span></button>
                                    <div class="sort-menu">
                                        <a href="#" onclick="sortStudents('az')">A - Z</a>
                                        <a href="#" onclick="sortStudents('za')">Z - A</a>
                                        <a href="#" onclick="sortStudents('high')">Highest → Lowest</a>
                                        <a href="#" onclick="sortStudents('low')">Lowest → Highest</a>
                                    </div>
                                </div>
                                
                                <button class="btn btn-warning btn-sm">
                                    🗄️ Archive Section
                                </button>
                            </div>
                        </div>
                        
                        <?php if (count($students) > 0): ?>
                            <table class="spreadsheet-table" id="spreadsheetTable">
                                <thead>
                                    <tr>
                                        <th>Surname</th>
                                        <th>Firstname</th>
                                        <th>M.I.</th>
                                        <th>Attendance</th>
                                        <th>Quizzes</th>
                                        <th>Activities</th>
                                        <th>Projects</th>
                                        <th>Recitation</th>
                                        <th><?php echo ucfirst($grading_period); ?> Exam</th>
                                        <th>Overall</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): 
                                        $name_parts = explode(' ', $student['full_name']);
                                        $firstname = $name_parts[0] ?? '';
                                        $surname = $name_parts[count($name_parts) - 1] ?? '';
                                        $mi = isset($name_parts[1]) && count($name_parts) > 2 
                                            ? substr($name_parts[1], 0, 1) . '.' 
                                            : '';
                                    ?>
                                        <tr>
                                            <td class="student-name-cell" onclick="showStudentCard(<?php echo $student['user_id']; ?>)">
                                                <?php echo htmlspecialchars($surname); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($firstname); ?></td>
                                            <td><?php echo htmlspecialchars($mi); ?></td>
                                            <td><?php echo number_format($student['attendance'], 2); ?>%</td>
                                            <td class="editable-cell">
                                                <input type="number" value="<?php echo number_format($student['quizzes'], 2); ?>" 
                                                       data-student="<?php echo $student['user_id']; ?>" 
                                                       data-type="quizzes">
                                            </td>
                                            <td class="editable-cell">
                                                <input type="number" value="<?php echo number_format($student['activities'], 2); ?>" 
                                                       data-student="<?php echo $student['user_id']; ?>" 
                                                       data-type="activities">
                                            </td>
                                            <td class="editable-cell">
                                                <input type="number" value="<?php echo number_format($student['projects'], 2); ?>" 
                                                       data-student="<?php echo $student['user_id']; ?>" 
                                                       data-type="projects">
                                            </td>
                                            <td class="editable-cell">
                                                <input type="number" value="<?php echo number_format($student['recitation'], 2); ?>" 
                                                       data-student="<?php echo $student['user_id']; ?>" 
                                                       data-type="recitation">
                                            </td>
                                            <td class="editable-cell">
                                                <input type="number" value="<?php echo number_format($student['exam'], 2); ?>" 
                                                       data-student="<?php echo $student['user_id']; ?>" 
                                                       data-type="exam">
                                            </td>
                                            <td><strong><?php echo number_format($student['overall'], 2); ?>%</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">👥</div>
                                <h3>No Students Enrolled</h3>
                                <p>Share the class code with students to enroll them.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($selected_course): ?>
                    <!-- SECTIONS VIEW -->
                    <div class="courses-header">
                        <h2>Sections</h2>
                        <div class="courses-controls">
                            <div class="sort-dropdown">
                                <button class="sort-btn">Sort <span>▼</span></button>
                                <div class="sort-menu">
                                    <a href="#">By Department</a>
                                    <a href="#">Year (Ascending)</a>
                                    <a href="#">Year (Descending)</a>
                                </div>
                            </div>
                            <a href="create-class.php" class="btn btn-primary">+ Add Section</a>
                        </div>
                    </div>
                    
                    <div class="sections-grid">
                        <?php foreach ($sections as $section): ?>
                            <div class="section-card">
                                <div class="section-card-header">
                                    <h3 class="section-card-title"><?php echo htmlspecialchars($section['section']); ?></h3>
                                    <p class="section-card-students">Students Enrolled: <?php echo $section['student_count']; ?></p>
                                </div>
                                <div class="section-card-actions">
                                    <button class="btn btn-warning btn-sm">🗄️ Archive</button>
                                    <a href="my-courses.php?course=<?php echo urlencode($selected_course['subject']); ?>&name=<?php echo urlencode($selected_course['name']); ?>&section_id=<?php echo $section['class_id']; ?>" 
                                       class="btn btn-primary btn-sm">👁️ View</a>
                                    <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this section?')">🗑️ Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- COURSES VIEW (Google Classroom Style) -->
                    <div class="courses-header">
                        <h2>All Courses</h2>
                        <div class="courses-controls">
                            <div class="sort-dropdown">
                                <button class="sort-btn">Sort <span>▼</span></button>
                                <div class="sort-menu">
                                    <a href="#">By Department</a>
                                    <a href="#">Year (Ascending)</a>
                                    <a href="#">Year (Descending)</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (count($courses) > 0): ?>
                        <div class="courses-grid">
                            <?php foreach ($courses as $course): ?>
                                <a href="my-courses.php?course=<?php echo urlencode($course['subject']); ?>&name=<?php echo urlencode($course['class_name']); ?>" class="course-card">
                                    <div class="course-card-header">
                                        <h3 class="course-card-title"><?php echo htmlspecialchars($course['class_name']); ?></h3>
                                        <p class="course-card-description"><?php echo htmlspecialchars($course['subject']); ?></p>
                                    </div>
                                    <div class="course-card-body">
                                        <div class="course-card-info">
                                            <div class="course-card-stats">
                                                <div class="course-card-stat">
                                                    <span>📚</span>
                                                    <span><?php echo $course['section_count']; ?> Section(s)</span>
                                                </div>
                                                <div class="course-card-stat">
                                                    <span>👥</span>
                                                    <span><?php echo $course['total_students']; ?> Student(s)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📚</div>
                            <h3>No Courses Yet</h3>
                            <p>Create your first course to get started</p>
                            <a href="create-class.php" class="btn btn-primary">Create Course</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Student Card Modal -->
    <div class="student-card-modal" id="studentCardModal">
        <div class="student-index-card">
            <div class="student-card-header">
                <div class="student-card-info">
                    <h2 id="studentName">Loading...</h2>
                    <p class="student-card-section" id="studentSection">Section</p>
                </div>
                <div class="student-card-avatar">
                    <img src="" alt="Profile" class="student-avatar-img" id="studentAvatar">
                </div>
            </div>
            <div class="student-card-body">
                <div class="student-contact-info">
                    <div class="contact-item">
                        <span class="contact-icon">📧</span>
                        <div>
                            <small>Email</small><br>
                            <span id="studentEmail">-</span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">📱</span>
                        <div>
                            <small>Contact</small><br>
                            <span id="studentPhone">N/A</span>
                        </div>
                    </div>
                </div>
                
                <h3 style="margin: 1.5rem 0 1rem;">Performance</h3>
                <div class="student-performance">
                    <div class="performance-item">
                        <div class="performance-label">Attendance</div>
                        <div class="performance-value" id="perfAttendance">0%</div>
                    </div>
                    <div class="performance-item">
                        <div class="performance-label">Quizzes</div>
                        <div class="performance-value" id="perfQuizzes">0%</div>
                    </div>
                    <div class="performance-item">
                        <div class="performance-label">Activities</div>
                        <div class="performance-value" id="perfActivities">0%</div>
                    </div>
                    <div class="performance-item">
                        <div class="performance-label">Overall</div>
                        <div class="performance-value" id="perfOverall">0%</div>
                    </div>
                </div>
            </div>
            <div class="student-card-footer">
                <button class="btn btn-secondary" onclick="closeStudentCard()">Close</button>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        function togglePeriod() {
            const btn = document.getElementById('periodToggle');
            const currentPeriod = btn.textContent.trim().toLowerCase();
            const newPeriod = currentPeriod === 'midterm' ? 'finals' : 'midterm';
            
            btn.classList.add('flipping');
            
            setTimeout(() => {
                btn.textContent = newPeriod.charAt(0).toUpperCase() + newPeriod.slice(1);
                btn.classList.remove('flipping');
                
                // Reload page with new period
                const url = new URL(window.location.href);
                url.searchParams.set('period', newPeriod);
                window.location.href = url.toString();
            }, 300);
        }
        
        function showStudentCard(studentId) {
            // Get student data from table row
            const table = document.getElementById('spreadsheetTable');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const nameCell = row.querySelector('.student-name-cell');
                if (nameCell && nameCell.onclick.toString().includes(studentId)) {
                    const cells = row.cells;
                    const surname = cells[0].textContent;
                    const firstname = cells[1].textContent;
                    const attendance = cells[3].textContent;
                    const quizzes = cells[4].querySelector('input').value;
                    const activities = cells[5].querySelector('input').value;
                    const overall = cells[9].textContent;
                    
                    // Populate modal
                    document.getElementById('studentName').textContent = `${firstname} ${surname}`;
                    document.getElementById('studentSection').textContent = '<?php echo htmlspecialchars($selected_section['section'] ?? ''); ?>';
                    document.getElementById('perfAttendance').textContent = attendance;
                    document.getElementById('perfQuizzes').textContent = quizzes + '%';
                    document.getElementById('perfActivities').textContent = activities + '%';
                    document.getElementById('perfOverall').textContent = overall;
                    
                    // Show modal
                    document.getElementById('studentCardModal').classList.add('active');
                }
            });
        }
        
        function closeStudentCard() {
            document.getElementById('studentCardModal').classList.remove('active');
        }
        
        function markAllPresent() {
            alert('Mark all students as present for today');
        }
        
        function sortStudents(type) {
            const table = document.getElementById('spreadsheetTable');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aName = a.cells[0].textContent + ' ' + a.cells[1].textContent;
                const bName = b.cells[0].textContent + ' ' + b.cells[1].textContent;
                const aOverall = parseFloat(a.cells[9].textContent);
                const bOverall = parseFloat(b.cells[9].textContent);
                
                switch(type) {
                    case 'az': return aName.localeCompare(bName);
                    case 'za': return bName.localeCompare(aName);
                    case 'high': return bOverall - aOverall;
                    case 'low': return aOverall - bOverall;
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Close modal when clicking outside
        document.getElementById('studentCardModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStudentCard();
            }
        });
        // Close modal when clicking outside
        document.getElementById('studentCardModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStudentCard();
            }
        });
        
        // ========== ADD THESE NEW FUNCTIONS BELOW ========== //
        
        // Auto-save grade when input changes
        document.addEventListener('DOMContentLoaded', function() {
            const gradeInputs = document.querySelectorAll('.editable-cell input');
            
            gradeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    saveGradeInline(this);
                });
                
                input.addEventListener('blur', function() {
                    saveGradeInline(this);
                });
            });
        });

        function saveGradeInline(input) {
            const studentId = input.dataset.student;
            const activityType = input.dataset.type;
            const score = parseFloat(input.value) || 0;
            const classId = <?php echo $selected_section['class_id'] ?? 0; ?>;
            const gradingPeriod = '<?php echo $grading_period; ?>';
            
            input.style.borderColor = '#f59e0b';
            
            fetch('<?php echo BASE_URL; ?>api/teacher/save-grade-inline.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: studentId,
                    class_id: classId,
                    activity_type: activityType,
                    score: score,
                    grading_period: gradingPeriod
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.style.borderColor = '#10b981';
                    setTimeout(() => {
                        input.style.borderColor = 'transparent';
                    }, 2000);
                    updateOverallGrade(input.closest('tr'));
                } else {
                    input.style.borderColor = '#ef4444';
                    alert('Error saving: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                input.style.borderColor = '#ef4444';
            });
        }

        function updateOverallGrade(row) {
            const inputs = row.querySelectorAll('.editable-cell input');
            let sum = 0;
            let count = 0;
            
            inputs.forEach(input => {
                const val = parseFloat(input.value) || 0;
                sum += val;
                count++;
            });
            
            const overall = count > 0 ? (sum / count) : 0;
            const overallCell = row.querySelector('td:last-child strong');
            if (overallCell) {
                overallCell.textContent = overall.toFixed(2) + '%';
            }
        }
    </script>
</body>
</html>
    