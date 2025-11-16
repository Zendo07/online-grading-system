<?php

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

require_once '../api/teacher/my-courses-handler.php';
requireTeacher();

$teacher_id = $_SESSION['user_id'];

$show_code_modal = isset($_GET['show_code']);
$modal_data = $show_code_modal ? getNewClassModalData() : null;

// Get all courses
$courses = getTeacherCourses($teacher_id);

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
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>teacher-pages/my-courses.css?v=<?php echo time(); ?>">
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

                <section class="courses-section">
                    <div class="courses-header">
                        <div>
                            <h3 class="section-title">My Courses</h3>
                        </div>
                        <a href="create-class.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create New Course
                        </a>
                    </div>

                    <?php if (count($courses) > 0): ?>
                        <div class="courses-grid">
                            <?php foreach ($courses as $course): ?>
                                <a href="view-sections.php?course=<?php echo urlencode($course['subject']); ?>&name=<?php echo urlencode($course['class_name']); ?>" class="course-card">
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
                                                <span>View Sections</span>
                                                <i class="fas fa-arrow-right"></i>
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
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <?php if ($show_code_modal && $modal_data): ?>
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
                    <strong><?php echo htmlspecialchars($modal_data['name']); ?></strong>
                    <?php if ($modal_data['section']): ?>
                        - Section <?php echo htmlspecialchars($modal_data['section']); ?>
                    <?php endif; ?>
                </p>
                <div class="code-display-large">
                    <div class="code-label">📋 Share this code with your students:</div>
                    <div class="code-box">
                        <span class="code-text" id="classCode"><?php echo htmlspecialchars($modal_data['code']); ?></span>
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

    <script src="<?php echo JS_PATH; ?>teacher-pages/my-courses.js?v=<?php echo time(); ?>"></script>
</body>
</html>