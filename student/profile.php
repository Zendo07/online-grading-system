<?php
/**
 * Student Profile Page - FIXED
 * Clean display: No labels, just data next to profile picture
 * Proper profile picture fetching per unique user
 */

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$student_id = $_SESSION['user_id'];
$student_info = null;
$flash = getFlashMessage();

try {
    // Get student information from database - FIXED QUERY
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
    
    // Parse full name into components
    $name_parts = explode(' ', $student_info['full_name']);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[count($name_parts) - 1] ?? '';
    
    // Get middle initial from middle_name field
    $middle_initial = '';
    if (!empty($student_info['middle_name'])) {
        $middle_initial = strtoupper(substr(trim($student_info['middle_name']), 0, 1)) . '.';
    } else {
        // Fallback: try to extract from full_name if middle_name is empty
        if (count($name_parts) > 2) {
            $middle_initial = strtoupper(substr($name_parts[1], 0, 1)) . '.';
        }
    }
    
    // Get program and year_section
    $program = $student_info['program'] ?? 'Not Specified';
    $year_section = $student_info['year_section'] ?? 'Not Assigned';
    
    // Format display: "PROGRAM YEAR-SECTION" (e.g., "BSCS 3-A")
    $course_display = $program . ' ' . $year_section;
    
    // Get profile picture - FIXED: Returns default-avatar.jpg for NULL/empty, uploaded pic if exists
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
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-profile.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="profile-container">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Header Card - CLEAN UI -->
                <div class="profile-header-card">
                    <div class="profile-header-content">
                        <!-- Left: Profile Image -->
                        <div class="profile-image-section">
                            <div class="profile-image-wrapper">
                                <img 
                                src="<?php echo $profile_pic_url; ?>" 
                                alt="<?php echo htmlspecialchars($student_info['full_name']); ?>" 
                                class="profile-image"
                                id="profileImage">
                                <div class="profile-image-overlay">
                                    <span class="profile-status-badge">
                                        <?php echo ucfirst($student_info['status'] ?? 'active'); ?>
                                    </span>
                                </div>
                            </div>
                            <button class="btn-change-photo" disabled title="Coming soon">
                                <i class="fas fa-camera"></i> Change Photo
                            </button>
                        </div>

                        <!-- Right: Student Information - NO LABELS, JUST DATA -->
                        <div class="profile-info-section">
                            <!-- Name Display -->
                            <div style="margin-bottom: 32px;">
                                <div style="font-size: 36px; font-weight: 700; color: var(--maroon); margin-bottom: 8px; line-height: 1.2;">
                                    <?php echo htmlspecialchars($last_name); ?>, 
                                    <?php echo htmlspecialchars($first_name); ?>
                                    <?php if ($middle_initial): ?>
                                        <?php echo htmlspecialchars($middle_initial); ?>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 20px; font-weight: 600; color: var(--gray-medium); margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($course_display); ?>
                                </div>
                                <div style="font-size: 16px; color: var(--gray-medium); margin-bottom: 8px;">
                                    <?php echo htmlspecialchars($student_info['student_number'] ?? 'No Student Number'); ?>
                                </div>
                                <div style="font-size: 15px; color: var(--gray-medium); margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($student_info['contact_number'] ?: 'No Contact Number'); ?>
                                </div>
                                <div style="font-size: 15px; color: var(--gray-medium);">
                                    <?php echo htmlspecialchars($student_info['email']); ?>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div style="display: flex; gap: 24px; flex-wrap: wrap; padding-top: 24px; border-top: 2px solid var(--cream);">
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-medium); margin-bottom: 4px;">
                                        Member Since
                                    </div>
                                    <div style="font-size: 16px; font-weight: 600; color: var(--maroon);">
                                        <?php echo date('F Y', strtotime($student_info['created_at'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-medium); margin-bottom: 4px;">
                                        Role
                                    </div>
                                    <div style="font-size: 16px; font-weight: 600; color: var(--maroon);">
                                        Student
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs Section -->
                <div class="profile-tabs-section">
                    <div class="profile-tabs">
                        <button class="tab-button active" data-tab="overview">
                            <i class="fas fa-user"></i> Overview
                        </button>
                        <button class="tab-button" data-tab="help">
                            <i class="fas fa-question-circle"></i> Help
                        </button>
                        <button class="tab-button" data-tab="settings">
                            <i class="fas fa-cog"></i> Account Settings
                        </button>
                    </div>

                    <!-- Tab Content: Overview -->
                    <div class="tab-content active" id="tab-overview">
                        <div class="tab-content-card">
                            <h3 class="tab-title">Profile Overview</h3>
                            
                            <div class="overview-grid">
                                <div class="overview-item">
                                    <div class="overview-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="overview-text">
                                        <h4>Active Courses</h4>
                                        <p>View and manage your enrolled courses</p>
                                        <a href="<?php echo BASE_URL; ?>student/my-grades.php" class="overview-link">
                                            Go to Courses <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="overview-item">
                                    <div class="overview-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="overview-text">
                                        <h4>Academic Progress</h4>
                                        <p>Check your grades and performance</p>
                                        <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="overview-link">
                                            View Dashboard <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="overview-item">
                                    <div class="overview-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="overview-text">
                                        <h4>Attendance</h4>
                                        <p>Monitor your attendance records</p>
                                        <a href="<?php echo BASE_URL; ?>student/my-attendance.php" class="overview-link">
                                            View Attendance <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>

                                <div class="overview-item">
                                    <div class="overview-icon">
                                        <i class="fas fa-history"></i>
                                    </div>
                                    <div class="overview-text">
                                        <h4>Activity Log</h4>
                                        <p>Review your account activity history</p>
                                        <a href="<?php echo BASE_URL; ?>student/audit-trail.php" class="overview-link">
                                            View Log <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content: Help -->
                    <div class="tab-content" id="tab-help">
                        <div class="tab-content-card">
                            <h3 class="tab-title">Help & Support</h3>
                            
                            <div class="help-section">
                                <div class="help-item">
                                    <h4><i class="fas fa-question-circle"></i> How do I join a class?</h4>
                                    <p>To join a class, navigate to "Join Class" from the sidebar menu and enter the class code provided by your instructor. You'll be automatically enrolled!</p>
                                </div>

                                <div class="help-item">
                                    <h4><i class="fas fa-question-circle"></i> How can I view my grades?</h4>
                                    <p>Go to "My Grades" from the sidebar. Select a class to see your detailed grades for quizzes, assignments, projects, and exams.</p>
                                </div>

                                <div class="help-item">
                                    <h4><i class="fas fa-question-circle"></i> How do I check my attendance?</h4>
                                    <p>Visit "My Attendance" to view your attendance records for all classes. You can see your attendance percentage and status for each date.</p>
                                </div>

                                <div class="help-item">
                                    <h4><i class="fas fa-question-circle"></i> How do I update my contact information?</h4>
                                    <p>Go to "Account Settings" tab to update your contact number and other account details. Contact support if you need to update core information.</p>
                                </div>

                                <div class="help-item">
                                    <h4><i class="fas fa-question-circle"></i> Can I change my password?</h4>
                                    <p>Yes! Visit "Change Password" from the sidebar menu. Enter your current password and your new password to update your account security.</p>
                                </div>

                                <div class="help-cta">
                                    <p>Need more help? Contact your instructor or visit our support center.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content: Settings -->
                    <div class="tab-content" id="tab-settings">
                        <div class="tab-content-card">
                            <h3 class="tab-title">Account Settings</h3>
                            
                            <div class="settings-section">
                                <div class="settings-group">
                                    <h4>Account Information</h4>
                                    <div class="setting-item">
                                        <div class="setting-label">
                                            <label>Account Status</label>
                                            <p class="setting-description">Your current account status</p>
                                        </div>
                                        <div class="setting-value">
                                            <span class="badge-active">✓ Active</span>
                                        </div>
                                    </div>

                                    <div class="setting-item">
                                        <div class="setting-label">
                                            <label>Member Since</label>
                                            <p class="setting-description">Date you created your account</p>
                                        </div>
                                        <div class="setting-value">
                                            <span><?php echo date('F j, Y', strtotime($student_info['created_at'])); ?></span>
                                        </div>
                                    </div>

                                    <div class="setting-item">
                                        <div class="setting-label">
                                            <label>Role</label>
                                            <p class="setting-description">Your role in the system</p>
                                        </div>
                                        <div class="setting-value">
                                            <span class="role-badge">Student</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="settings-group">
                                    <h4>Security Settings</h4>
                                    <div class="setting-item">
                                        <div class="setting-label">
                                            <label>Password</label>
                                            <p class="setting-description">Manage your password security</p>
                                        </div>
                                        <div class="setting-value">
                                            <a href="<?php echo BASE_URL; ?>student/change-password.php" class="btn-settings-action">
                                                Change Password
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="settings-group">
                                    <h4>Danger Zone</h4>
                                    <p class="settings-warning">These actions cannot be undone. Proceed with caution.</p>
                                    <div class="setting-item danger">
                                        <div class="setting-label">
                                            <label>Deactivate Account</label>
                                            <p class="setting-description">Temporarily disable your account</p>
                                        </div>
                                        <div class="setting-value">
                                            <button class="btn-settings-danger" disabled title="Contact administrator">
                                                Deactivate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo JS_PATH; ?>main.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo JS_PATH; ?>student-profile.js?v=<?php echo time(); ?>"></script>
</body>
</html>