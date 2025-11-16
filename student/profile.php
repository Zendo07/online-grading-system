<?php

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$student_info = null;
$flash = getFlashMessage();

try {
    // Get student information from database
    $stmt = $conn->prepare("
        SELECT 
            user_id,
            email,
            full_name,
            middle_name,
            student_number,
            program,
            year_section,
            contact_number,
            profile_picture,
            created_at,
            role,
            status
        FROM users 
        WHERE user_id = ? AND role = 'student'
    ");
    $stmt->execute([$student_id]);
    $student_info = $stmt->fetch();
    
    if (!$student_info) {
        redirectWithMessage(BASE_URL . 'student/dashboard.php', 'danger', 'Student information not found.');
    }
    
    $name_parts = explode(' ', $student_info['full_name']);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[count($name_parts) - 1] ?? '';
    
    $middle_initial = '';
    if (!empty($student_info['middle_name'])) {
        $middle_initial = strtoupper(substr(trim($student_info['middle_name']), 0, 1)) . '.';
    } else {
        if (count($name_parts) > 2) {
            $middle_initial = strtoupper(substr($name_parts[1], 0, 1)) . '.';
        }
    }
    
    $full_name_display = $last_name . ', ' . $first_name;
    if ($middle_initial) {
        $full_name_display .= ' ' . $middle_initial;
    }
    
    $program = $student_info['program'] ?? 'Not Specified';
    $year_section = $student_info['year_section'] ?? 'Not Assigned';
    $course_display = $program . ' ' . $year_section;
    
    $profile_pic_url = getProfilePicture($student_info['profile_picture'] ?? null, $student_info['full_name']);
    
} catch (PDOException $e) {
    error_log("Profile Error: " . $e->getMessage());
    redirectWithMessage(BASE_URL . 'student/dashboard.php', 'danger', 'Error loading profile.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - indEx</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-pages/student-profile.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="profile-container">
                <div class="profile-wrapper">
                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo $flash['type']; ?>">
                            <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                            <span><?php echo htmlspecialchars($flash['message']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="profile-main-card">
                        <div class="profile-content">
                            <div class="profile-image-container">
                                <div class="profile-image-wrapper">
                                    <img 
                                        src="<?php echo $profile_pic_url; ?>" 
                                        alt="<?php echo htmlspecialchars($student_info['full_name']); ?>" 
                                        class="profile-image"
                                        id="profileImage">
                                </div>
                            </div>

                            <div class="profile-details-container">
                                <div class="profile-name-section">
                                    <h1 class="profile-name"><?php echo htmlspecialchars($full_name_display); ?></h1>
                                    <p class="profile-course">
                                        <i class="fas fa-graduation-cap"></i>
                                        <?php echo htmlspecialchars($course_display); ?>
                                    </p>
                                    <p class="profile-student-number">
                                        <i class="fas fa-id-card"></i>
                                        <?php echo htmlspecialchars($student_info['student_number'] ?? 'No Student Number'); ?>
                                    </p>
                                </div>

                                <!-- Contact Section -->
                                <div class="profile-contact-section">
                                    <div class="profile-contact-item">
                                        <div class="profile-contact-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <span class="profile-contact-text">
                                            <?php echo htmlspecialchars($student_info['contact_number'] ?: 'No Contact Number'); ?>
                                        </span>
                                    </div>

                                    <div class="profile-contact-item">
                                        <div class="profile-contact-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <span class="profile-contact-text">
                                            <?php echo htmlspecialchars($student_info['email']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-actions-compact">
                        <a href="<?php echo BASE_URL; ?>student/profile-overview.php" class="action-button-compact">
                            <div class="action-button-icon">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <span class="action-button-label">Overview</span>
                        </a>

                        <a href="<?php echo BASE_URL; ?>student/help-support.php" class="action-button-compact">
                            <div class="action-button-icon">
                                <i class="fas fa-life-ring"></i>
                            </div>
                            <span class="action-button-label">Help</span>
                        </a>

                        <a href="<?php echo BASE_URL; ?>student/account-settings.php" class="action-button-compact">
                            <div class="action-button-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <span class="action-button-label">Settings</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>

    <script src="<?php echo JS_PATH; ?>main.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo JS_PATH; ?>student-profile.js?v=<?php echo time(); ?>"></script>
</body>
</html>