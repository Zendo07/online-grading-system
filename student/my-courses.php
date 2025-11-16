<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = $_SESSION['user_id'];

// Enable comprehensive error logging
error_log("=== MY COURSES PAGE LOADED ===");
error_log("Student ID: " . $student_id);
error_log("Timestamp: " . date('Y-m-d H:i:s'));

try {
    // Fetch ONLY the latest active enrollment for each class
    $stmt = $conn->prepare("
        SELECT 
            e.enrollment_id,
            e.student_id,
            e.class_id,
            e.enrolled_at,
            e.status,
            c.class_name,
            c.subject,
            c.section,
            c.class_code,
            c.status as class_status,
            u.full_name as teacher_name,
            u.email as teacher_email
        FROM enrollments e
        INNER JOIN classes c ON e.class_id = c.class_id
        INNER JOIN users u ON c.teacher_id = u.user_id
        WHERE e.student_id = ? 
        AND e.status = 'active' 
        AND c.status = 'active'
        GROUP BY e.class_id
        HAVING e.enrollment_id = MAX(e.enrollment_id)
        ORDER BY e.enrolled_at DESC
    ");
    
    $stmt->execute([$student_id]);
    $rawCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("✓ Raw query returned " . count($rawCourses) . " rows");
    
    // Remove any duplicates by class_id
    $courses = [];
    $seenClassIds = [];
    
    foreach ($rawCourses as $course) {
        if (!in_array($course['class_id'], $seenClassIds)) {
            $courses[] = $course;
            $seenClassIds[] = $course['class_id'];
            error_log("  ✓ Added course: " . $course['class_name'] . " (Class ID: " . $course['class_id'] . ")");
        } else {
            error_log("  ⚠ Skipped duplicate: " . $course['class_name'] . " (Class ID: " . $course['class_id'] . ")");
        }
    }
    
    error_log("✓ After deduplication: " . count($courses) . " unique courses");
    
    // Get additional data for each unique course
    foreach ($courses as &$course) {
        $classmateStmt = $conn->prepare("
            SELECT COUNT(DISTINCT student_id) as count
            FROM enrollments
            WHERE class_id = ? 
            AND status = 'active' 
            AND student_id != ?
        ");
        $classmateStmt->execute([$course['class_id'], $student_id]);
        $classmateResult = $classmateStmt->fetch(PDO::FETCH_ASSOC);
        $course['classmate_count'] = $classmateResult['count'] ?? 0;
        
        // Get schedules
        $scheduleStmt = $conn->prepare("
            SELECT 
                schedule_id,
                day_of_week,
                start_time,
                end_time,
                room
            FROM class_schedules 
            WHERE class_id = ? 
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                     start_time ASC
        ");
        $scheduleStmt->execute([$course['class_id']]);
        $course['schedules'] = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($course);
    
    error_log("=== DATA PREPARATION COMPLETE ===");
    
} catch (PDOException $e) {
    error_log("=== MY COURSES DATABASE ERROR ===");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $courses = [];
}

$flash = getFlashMessage();
$hasCourses = count($courses) > 0;

error_log("Has courses: " . ($hasCourses ? 'YES (' . count($courses) . ')' : 'NO'));
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
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-pages/my-courses.css?v=<?php echo time(); ?>">
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
                <div class="courses-wrapper">
                    <?php if ($hasCourses): ?>
                        <div class="courses-header">
                            <a href="join-class.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Join New Class
                            </a>
                        </div>

                        <div class="courses-grid" id="coursesGrid">
                            <?php 
                            $renderedClassIds = [];
                            foreach ($courses as $course): 
                                if (in_array($course['class_id'], $renderedClassIds)) {
                                    error_log("⚠ Skipping duplicate render: Class ID " . $course['class_id']);
                                    continue;
                                }
                                $renderedClassIds[] = $course['class_id'];
                            ?>
                                <div class="course-card" 
                                     data-course-id="<?php echo $course['class_id']; ?>" 
                                     data-enrollment-id="<?php echo $course['enrollment_id']; ?>"
                                     data-class-name="<?php echo htmlspecialchars($course['class_name']); ?>">
                                    
                                    <div class="course-header">
                                        <div class="course-info-header">
                                            <div class="course-code"><?php echo htmlspecialchars($course['subject']); ?></div>
                                            <h3 class="course-name"><?php echo htmlspecialchars($course['class_name']); ?></h3>
                                            <div class="course-professor-header">
                                                <i class="fas fa-user-tie"></i>
                                                <span><?php echo htmlspecialchars($course['teacher_name']); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="course-menu-wrapper">
                                        <button 
                                            class="course-menu-toggle" 
                                            data-course-id="<?php echo $course['class_id']; ?>" 
                                            type="button"
                                            aria-label="Course options"
                                            aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="course-menu" data-course-id="<?php echo $course['class_id']; ?>" aria-hidden="true">
                                            <form method="POST" action="<?php echo BASE_URL; ?>api/student/unenroll-handler.php" class="unenroll-form">
                                                <input type="hidden" name="class_id" value="<?php echo $course['class_id']; ?>">
                                                <button 
                                                    type="submit" 
                                                    class="course-menu-item"
                                                    data-class-name="<?php echo htmlspecialchars($course['class_name']); ?>">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                    Unenroll from Class
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="course-body">
                                        <?php if (!empty($course['schedules'])): ?>
                                            <div class="course-schedules">
                                                <h4 class="schedules-title">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <?php if (count($course['schedules']) > 0): ?>
                                                        <span class="schedule-day"><?php echo htmlspecialchars($course['schedules'][0]['day_of_week']); ?></span>
                                                    <?php endif; ?>
                                                </h4>
                                                <div class="schedule-list">
                                                    <?php foreach ($course['schedules'] as $schedule): ?>
                                                        <div class="schedule-item">
                                                            <span class="schedule-time">
                                                                <i class="fas fa-clock"></i>
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
                                        <?php endif; ?>

                                        <div class="course-info">
                                            <div class="course-info-item">
                                                <i class="fas fa-users"></i>
                                                <span><?php echo $course['classmate_count']; ?> Classmate<?php echo $course['classmate_count'] != 1 ? 's' : ''; ?></span>
                                            </div>
                                            <div class="course-info-item">
                                                <i class="fas fa-calendar"></i>
                                                <span>Joined <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="course-footer">
                                        <a href="view-class.php?class_id=<?php echo $course['class_id']; ?>" class="course-btn">
                                            <i class="fas fa-eye"></i> View Grades
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-modern">
                            <div class="empty-state-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            
                            <h2 class="empty-state-title">No Classes Yet</h2>
                            <p class="empty-state-description">
                                You haven't enrolled in any classes. Start your learning journey by joining a class with your instructor's code.
                            </p>
                            
                            <a href="join-class.php" class="btn-enroll-now">
                                <i class="fas fa-plus-circle"></i>
                                <span>Enroll Now</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>

    <script>
        console.log('%c=== MY COURSES DEBUG INFO ===', 'color: #00ff00; font-weight: bold; font-size: 16px;');
        console.log('Student ID:', '<?php echo $student_id; ?>');
        console.log('Total courses from PHP:', <?php echo count($courses); ?>);
        console.log('Has courses:', <?php echo $hasCourses ? 'true' : 'false'; ?>);
        console.log('Timestamp:', '<?php echo date('Y-m-d H:i:s'); ?>');
        
        <?php if ($hasCourses): ?>
        console.log('%cCourses List:', 'color: #ffff00; font-weight: bold;');
        console.table([
            <?php foreach ($courses as $index => $course): ?>
            {
                '#': <?php echo ($index + 1); ?>,
                'Class ID': <?php echo $course['class_id']; ?>,
                'Enrollment ID': <?php echo $course['enrollment_id']; ?>,
                'Class Name': '<?php echo addslashes($course['class_name']); ?>',
                'Subject': '<?php echo addslashes($course['subject']); ?>',
                'Professor': '<?php echo addslashes($course['teacher_name']); ?>'
            }<?php echo ($index < count($courses) - 1) ? ',' : ''; ?>
            <?php endforeach; ?>
        ]);
        <?php endif; ?>
        
        console.log('%c--- DOM VERIFICATION ---', 'color: yellow; font-weight: bold;');
        
        // Verify no duplicates after DOM loads
        document.addEventListener('DOMContentLoaded', function() {
            const courseCards = document.querySelectorAll('.course-card');
            console.log('Total course cards in DOM:', courseCards.length);
            
            const courseIdsInDOM = Array.from(courseCards).map(card => card.dataset.courseId);
            const uniqueIdsInDOM = [...new Set(courseIdsInDOM)];
            
            console.log('Unique course IDs in DOM:', uniqueIdsInDOM.length);
            
            if (courseIdsInDOM.length !== uniqueIdsInDOM.length) {
                console.error('%c✗ DUPLICATES DETECTED IN DOM!', 'color: red; font-weight: bold; font-size: 18px;');
            } else {
                console.log('%c✓ No duplicates - All courses unique!', 'color: #00ff00; font-weight: bold;');
            }
            
            console.log('%c================================', 'color: #00ff00; font-weight: bold;');
        });
    </script>

    <script src="<?php echo JS_PATH; ?>main.js"></script>
    <script src="<?php echo JS_PATH; ?>student-pages/my-courses.js?v=<?php echo time(); ?>"></script>
</body>
</html>