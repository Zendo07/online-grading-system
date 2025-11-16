<?php
header('Content-Type: text/html; charset=UTF-8');

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../api/teacher/view-sections-handler.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];

if (!isset($_GET['course']) || !isset($_GET['name'])) {
    header('Location: my-courses.php');
    exit();
}

$course_subject = $_GET['course'];
$course_name = $_GET['name'];

$course_info = getCourseInfo($teacher_id, $course_subject, $course_name);
$sections = getCourseSections($teacher_id, $course_subject, $course_name);

if (!$course_info) {
    header('Location: my-courses.php');
    exit();
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course_subject . ' - ' . $course_name); ?> - indEx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>teacher-pages/view-section.css?v=<?php echo time(); ?>">
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

                <section class="sections-page">
                    <div class="sections-header">
                        <div class="header-content">
                            <a href="my-courses.php" class="back-link">
                                <i class="fas fa-arrow-left"></i> Back to Courses
                            </a>
                            <h3 class="section-title">
                                <?php echo htmlspecialchars($course_subject); ?> - 
                                <?php echo htmlspecialchars($course_name); ?>
                            </h3>
                        </div>
                        <a href="create-class.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Section
                        </a>
                    </div>

                    <?php if (count($sections) > 0): ?>
                        <div class="sections-scroll-container">
                            <button class="scroll-btn scroll-left" id="scrollLeft" style="display: none;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            
                            <div class="sections-wrapper" id="sectionsWrapper">
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
                                            <div class="section-code-display">
                                                <div class="code-label">Class Code:</div>
                                                <div class="code-value"><?php echo htmlspecialchars($section['class_code']); ?></div>
                                                <button class="btn-copy-code" data-code="<?php echo htmlspecialchars($section['class_code']); ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            
                                            <?php if (!empty($section['schedules'])): ?>
                                                <div class="section-schedules">
                                                    <h4 class="schedules-title">
                                                        <span class="schedules-title-left">
                                                            <i class="fas fa-calendar-alt"></i>
                                                            Class Schedule
                                                        </span>
                                                        <?php 
                                                        // Get unique rooms from all schedules
                                                        $rooms = array_filter(array_unique(array_column($section['schedules'], 'room')));
                                                        if (!empty($rooms)): 
                                                        ?>
                                                            <span class="schedule-room">
                                                                <i class="fas fa-map-marker-alt"></i>
                                                                <?php echo htmlspecialchars(implode(', ', $rooms)); ?>
                                                            </span>
                                                        <?php endif; ?>
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
                                            
                                            <div class="section-actions">
                                                <a href="view-grades.php?class_id=<?php echo $section['class_id']; ?>" class="course-btn">
                                                    <i class="fas fa-eye"></i> View Class Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button class="scroll-btn scroll-right" id="scrollRight" style="display: none;">
                                <i class="fas fa-chevron-right"></i>
                            </button>
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
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="<?php echo JS_PATH; ?>teacher-pages/view-sections.js?v=<?php echo time(); ?>"></script>
</body>
</html>