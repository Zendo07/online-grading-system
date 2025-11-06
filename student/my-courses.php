<?php
/**
 * Student My Courses - Updated Layout (Matching Teacher Version)
 * File: student/my-courses.php
 */

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = $_SESSION['user_id'];

// Get student's enrolled classes
try {
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.full_name as teacher_name,
            e.enrollment_id,
            (SELECT COUNT(*) FROM schedules WHERE class_id = c.class_id) as schedule_count
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.class_id
        INNER JOIN users u ON c.teacher_id = u.user_id
        WHERE e.student_id = ? AND e.status = 'active' AND c.status = 'active'
        ORDER BY c.subject ASC, c.class_name ASC
    ");
    $stmt->execute([$student_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get schedules for each course
    foreach ($courses as &$course) {
        $scheduleStmt = $conn->prepare("
            SELECT * FROM schedules 
            WHERE class_id = ? 
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                     start_time ASC
        ");
        $scheduleStmt->execute([$course['class_id']]);
        $course['schedules'] = $scheduleStmt->fetchAll();
    }
    
} catch (PDOException $e) {
    error_log("Student My Courses Error: " . $e->getMessage());
    $courses = [];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - indEx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-pages/my-courses.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                    <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                </div>
            <?php endif; ?>

            <section class="courses-section">
                <div class="courses-header">
                    <div>
                        <h3 class="section-title">My Courses</h3>
                        <p class="section-subtitle">View your enrolled courses and grades</p>
                    </div>
                    <a href="join-class.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Join New Class
                    </a>
                </div>

                <?php if (count($courses) > 0): ?>
                    <div class="courses-grid">
                        <?php foreach ($courses as $course): ?>
                            <div class="course-card" data-course-id="<?php echo $course['class_id']; ?>">
                                <!-- 3-Dot Menu -->
                                <button class="course-menu-toggle" data-course-id="<?php echo $course['class_id']; ?>">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div class="course-dropdown-menu" id="menu-<?php echo $course['class_id']; ?>">
                                    <div class="dropdown-menu-item danger" onclick="confirmUnenroll(<?php echo $course['class_id']; ?>, '<?php echo htmlspecialchars($course['class_name'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Unenroll</span>
                                    </div>
                                </div>
                                
                                <div class="course-header">
                                    <div class="course-code"><?php echo htmlspecialchars($course['subject']); ?></div>
                                    <h3 class="course-name"><?php echo htmlspecialchars($course['class_name']); ?></h3>
                                    <?php if (!empty($course['section'])): ?>
                                        <div class="course-section">Section: <?php echo htmlspecialchars($course['section']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="course-body">
                                    <div class="course-info">
                                        <div class="course-info-item">
                                            <i class="fas fa-user-tie"></i>
                                            <span><?php echo htmlspecialchars($course['teacher_name']); ?></span>
                                        </div>
                                        <div class="course-info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><?php echo $course['schedule_count']; ?> Schedule<?php echo $course['schedule_count'] != 1 ? 's' : ''; ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Schedules Display -->
                                    <?php if (!empty($course['schedules'])): ?>
                                        <div class="section-schedules">
                                            <h4 class="schedules-title">
                                                <i class="fas fa-clock"></i> Class Schedule
                                            </h4>
                                            <div class="schedule-list">
                                                <?php foreach (array_slice($course['schedules'], 0, 2) as $schedule): ?>
                                                    <div class="schedule-item-display">
                                                        <span class="schedule-day"><?php echo htmlspecialchars($schedule['day_of_week']); ?></span>
                                                        <span class="schedule-time">
                                                            <?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?>
                                                        </span>
                                                        <?php if (!empty($schedule['room'])): ?>
                                                            <span class="schedule-room">
                                                                <i class="fas fa-map-marker-alt"></i>
                                                                <?php echo htmlspecialchars($schedule['room']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($course['schedules']) > 2): ?>
                                                    <div class="schedule-more">
                                                        +<?php echo count($course['schedules']) - 2; ?> more schedule<?php echo (count($course['schedules']) - 2) > 1 ? 's' : ''; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="course-footer">
                                    <a href="view-grades.php?class_id=<?php echo $course['class_id']; ?>" class="course-btn">
                                        <i class="fas fa-chart-bar"></i> View Grades & Records
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h3>No Courses Yet</h3>
                        <p>Join your first class to get started</p>
                        <a href="join-class.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Join Class
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- Overlay for dropdown -->
    <div class="dropdown-overlay" id="dropdownOverlay"></div>

    <!-- Unenroll Confirmation Modal -->
    <div class="modal-overlay" id="unenrollModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <div class="success-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2>Confirm Unenrollment</h2>
            </div>
            <div class="modal-body">
                <p class="class-info">
                    Are you sure you want to unenroll from <strong id="unenrollClassName"></strong>?
                </p>
                <div class="code-instructions">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>
                        <strong>Warning:</strong> You will lose access to all course materials, grades, and attendance records. You can rejoin later using the class code.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal-close" onclick="closeUnenrollModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn-modal-close" onclick="processUnenroll()">
                    <i class="fas fa-sign-out-alt"></i> Unenroll
                </button>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 Pampanga State University. All rights reserved. | <a href="#">FAQs</a></p>
    </div>

    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        let currentUnenrollId = null;
        
        // Toggle dropdown menu
        document.addEventListener('click', function(e) {
            const toggle = e.target.closest('.course-menu-toggle');
            
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                const courseId = toggle.dataset.courseId;
                const menu = document.getElementById('menu-' + courseId);
                const overlay = document.getElementById('dropdownOverlay');
                
                // Close all other menus
                document.querySelectorAll('.course-dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.remove('show');
                });
                
                // Toggle current menu
                menu.classList.toggle('show');
                overlay.classList.toggle('show', menu.classList.contains('show'));
            }
        });
        
        // Close dropdown when clicking overlay
        document.getElementById('dropdownOverlay').addEventListener('click', function() {
            document.querySelectorAll('.course-dropdown-menu').forEach(m => m.classList.remove('show'));
            this.classList.remove('show');
        });
        
        function confirmUnenroll(classId, className) {
            event.preventDefault();
            event.stopPropagation();
            
            currentUnenrollId = classId;
            document.getElementById('unenrollClassName').textContent = className;
            document.getElementById('unenrollModal').style.display = 'flex';
            
            // Close dropdown
            document.querySelectorAll('.course-dropdown-menu').forEach(m => m.classList.remove('show'));
            document.getElementById('dropdownOverlay').classList.remove('show');
        }
        
        function closeUnenrollModal() {
            document.getElementById('unenrollModal').style.display = 'none';
            currentUnenrollId = null;
        }
        
        function processUnenroll() {
            if (!currentUnenrollId) return;
            
            // Show loading state
            const modal = document.getElementById('unenrollModal');
            modal.style.opacity = '0.7';
            modal.style.pointerEvents = 'none';
            
            // Submit unenroll request
            fetch('<?php echo BASE_URL; ?>api/student/unenroll-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'class_id=' + currentUnenrollId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show updated courses
                    window.location.href = 'my-courses.php';
                } else {
                    alert('Error: ' + (data.message || 'Failed to unenroll'));
                    modal.style.opacity = '1';
                    modal.style.pointerEvents = 'auto';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
            });
        }
        
        // Close modal on outside click
        document.getElementById('unenrollModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUnenrollModal();
            }
        });
    </script>
</body>
</html>