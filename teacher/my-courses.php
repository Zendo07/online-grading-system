<?php
/**
 * My Courses - COMPLETE FIXED VERSION
 * File: teacher/my-courses.php
 */

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require teacher access
requireTeacher();

$teacher_id = $_SESSION['user_id'];

// Check if we should show the class code modal
$show_code_modal = isset($_GET['show_code']) && isset($_SESSION['new_class_code']);
$new_class_code = $_SESSION['new_class_code'] ?? null;
$new_class_name = $_SESSION['new_class_name'] ?? null;
$new_class_section = $_SESSION['new_class_section'] ?? null;

// Clear the session variables after retrieving
if ($show_code_modal) {
    unset($_SESSION['new_class_code']);
    unset($_SESSION['new_class_name']);
    unset($_SESSION['new_class_section']);
}

// Get teacher's courses GROUPED by subject and class name (NO schedules)
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
        ORDER BY c.subject ASC, c.class_name ASC
    ");
    $stmt->execute([$teacher_id]);
    $courses = $stmt->fetchAll();
    
    // If specific course is selected - SHOW SECTIONS
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
        
        // FIXED: Get schedules for each section using 'schedules' table
        foreach ($sections as &$section) {
            $scheduleStmt = $conn->prepare("
                SELECT * FROM schedules 
                WHERE class_id = ? 
                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                         start_time ASC
            ");
            $scheduleStmt->execute([$section['class_id']]);
            $section['schedules'] = $scheduleStmt->fetchAll();
            
            // Debug log
            error_log("My Courses - Class ID {$section['class_id']} has " . count($section['schedules']) . " schedules");
        }
        
        $selected_course = [
            'subject' => $course_subject,
            'name' => $course_name
        ];
    }
    
} catch (PDOException $e) {
    error_log("My Courses Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
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
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>teacher-pages/my-courses.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/teacher-nav.php'; ?>
        
        <div class="main-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                    <i class="fas fa-<?php echo $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo $flash['message']; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($selected_course): ?>
                <!-- ============ SECTIONS VIEW ============ -->
                <section class="courses-section">
                    <div class="courses-header">
                        <div>
                            <h3 class="section-title">
                                <?php echo htmlspecialchars($selected_course['subject']); ?> - 
                                <?php echo htmlspecialchars($selected_course['name']); ?>
                            </h3>
                            <p class="section-subtitle">All sections for this course</p>
                        </div>
                        <div class="courses-header-actions">
                            <a href="my-courses.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Courses
                            </a>
                            <a href="create-class.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Section
                            </a>
                        </div>
                    </div>

                    <?php if (count($sections) > 0): ?>
                        <div class="sections-grid">
                            <?php foreach ($sections as $section): ?>
                                <div class="section-card">
                                    <div class="section-header">
                                        <h3 class="section-name">Section: <?php echo htmlspecialchars($section['section']); ?></h3>
                                        <p class="section-students">
                                            <i class="fas fa-users"></i> 
                                            <?php echo $section['student_count']; ?> Student<?php echo $section['student_count'] != 1 ? 's' : ''; ?>
                                        </p>
                                    </div>
                                    <div class="section-body">
                                        <!-- CLASS CODE DISPLAY - FIXED: Using data attribute -->
                                        <div class="section-code-display">
                                            <div class="code-label">Class Code:</div>
                                            <div class="code-value"><?php echo htmlspecialchars($section['class_code']); ?></div>
                                            <button class="btn-copy-code" data-code="<?php echo htmlspecialchars($section['class_code']); ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- SCHEDULES DISPLAY -->
                                        <?php if (!empty($section['schedules'])): ?>
                                            <div class="section-schedules">
                                                <h4 class="schedules-title">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    Class Schedule
                                                </h4>
                                                <div class="schedule-list">
                                                    <?php foreach ($section['schedules'] as $schedule): ?>
                                                        <div class="schedule-item-display">
                                                            <span class="schedule-day"><?php echo htmlspecialchars($schedule['day_of_week']); ?></span>
                                                            <span class="schedule-time">
                                                                <?php 
                                                                echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                                     date('g:i A', strtotime($schedule['end_time'])); 
                                                                ?>
                                                            </span>
                                                            <?php if (!empty($schedule['room'])): ?>
                                                                <span class="schedule-room">
                                                                    <i class="fas fa-map-marker-alt"></i>
                                                                    <?php echo htmlspecialchars($schedule['room']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-schedules">
                                                <i class="fas fa-calendar-times"></i>
                                                No schedule set
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- ACTION BUTTON -->
                                        <div class="section-actions">
                                            <a href="view-class.php?class_id=<?php echo $section['class_id']; ?>" class="course-btn">
                                                <i class="fas fa-eye"></i> View Class Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <h3>No Sections Yet</h3>
                            <p>Create your first section for this course</p>
                            <a href="create-class.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Section
                            </a>
                        </div>
                    <?php endif; ?>
                </section>

            <?php else: ?>
                <!-- ============ COURSES VIEW (GROUPED - NO SCHEDULES, NO CODES) ============ -->
                <section class="courses-section">
                    <div class="courses-header">
                        <div>
                            <h3 class="section-title">My Courses</h3>
                            <p class="section-subtitle">Click on a course to view sections</p>
                        </div>
                        <a href="create-class.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create New Course
                        </a>
                    </div>

                    <?php if (count($courses) > 0): ?>
                        <div class="courses-grid">
                            <?php foreach ($courses as $course): ?>
                                <a href="my-courses.php?course=<?php echo urlencode($course['subject']); ?>&name=<?php echo urlencode($course['class_name']); ?>" class="course-card">
                                    <div class="course-header">
                                        <div class="course-code"><?php echo htmlspecialchars($course['subject']); ?></div>
                                        <h3 class="course-name"><?php echo htmlspecialchars($course['class_name']); ?></h3>
                                    </div>
                                    <div class="course-body">
                                        <div class="course-info">
                                            <div class="course-info-item">
                                                <i class="fas fa-book"></i>
                                                <span><?php echo $course['section_count']; ?> Section<?php echo $course['section_count'] != 1 ? 's' : ''; ?></span>
                                            </div>
                                            <div class="course-info-item">
                                                <i class="fas fa-users"></i>
                                                <span><?php echo $course['total_students']; ?> Student<?php echo $course['total_students'] != 1 ? 's' : ''; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="course-footer">
                                            <div class="view-sections-btn">
                                                <i class="fas fa-arrow-right"></i> View Sections
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <h3>No Courses Yet</h3>
                            <p>Create your first course to get started</p>
                            <a href="create-class.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Course
                            </a>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <!-- Class Code Success Modal (Shows after creating new class) -->
    <?php if ($show_code_modal && $new_class_code): ?>
    <div class="modal-overlay show" id="codeModal">
        <div class="modal-content class-code-modal">
            <div class="modal-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Class Created Successfully! 🎉</h2>
            </div>
            <div class="modal-body">
                <p class="class-info">
                    <strong><?php echo htmlspecialchars($new_class_name); ?></strong>
                    <?php if ($new_class_section): ?>
                        - Section <?php echo htmlspecialchars($new_class_section); ?>
                    <?php endif; ?>
                </p>
                <div class="code-display-large">
                    <div class="code-label">📋 Share this code with your students:</div>
                    <div class="code-box">
                        <span class="code-text" id="classCode"><?php echo htmlspecialchars($new_class_code); ?></span>
                        <button class="btn-copy-large" id="copyModalBtn">
                            <i class="fas fa-copy"></i>
                            <span id="copyBtnText">Copy</span>
                        </button>
                    </div>
                </div>
                <div class="code-instructions">
                    <i class="fas fa-info-circle"></i>
                    <p>Students can use this code to join your class instantly. You can always find this code in the section details.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal-close" id="closeModalBtn">
                    <i class="fas fa-check"></i> Got it!
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Pampanga State University. All rights reserved. | <a href="#">FAQs</a></p>
    </div>

    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script>
        // FIXED: All event handlers wrapped in DOMContentLoaded to prevent double execution
        document.addEventListener('DOMContentLoaded', function() {
            
            // Handle copy code buttons in section cards using event delegation
            document.addEventListener('click', function(e) {
                const copyBtn = e.target.closest('.btn-copy-code');
                if (copyBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const code = copyBtn.dataset.code;
                    if (!code) {
                        console.error('No code found');
                        return;
                    }
                    
                    navigator.clipboard.writeText(code).then(() => {
                        const originalHTML = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                        copyBtn.style.background = '#10b981';
                        
                        setTimeout(() => {
                            copyBtn.innerHTML = originalHTML;
                            copyBtn.style.background = '';
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy code:', err);
                        alert('Failed to copy code');
                    });
                }
            });

            // Handle modal copy button
            const copyModalBtn = document.getElementById('copyModalBtn');
            if (copyModalBtn) {
                copyModalBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const code = document.getElementById('classCode').textContent;
                    const btnText = document.getElementById('copyBtnText');
                    
                    navigator.clipboard.writeText(code).then(() => {
                        btnText.textContent = 'Copied!';
                        setTimeout(() => {
                            btnText.textContent = 'Copy';
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy code:', err);
                        alert('Failed to copy code');
                    });
                });
            }

            // Handle modal close button
            const closeModalBtn = document.getElementById('closeModalBtn');
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeCodeModal();
                });
            }

            // Close modal when clicking outside
            const modal = document.getElementById('codeModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeCodeModal();
                    }
                });
            }
        });

        // Close modal function
        function closeCodeModal() {
            const modal = document.getElementById('codeModal');
            if (modal) {
                modal.classList.remove('show');
                // Remove the show_code parameter from URL
                const url = new URL(window.location);
                url.searchParams.delete('show_code');
                window.history.replaceState({}, '', url);
            }
        }
    </script>
</body>
</html>