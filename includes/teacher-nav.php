<?php
// includes/teacher-nav.php - FIXED VERSION
// This file should be included on ALL teacher pages

// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Top Navigation Bar -->
<header class="top-navbar">
    <div class="navbar-left">
        <div class="hamburger-menu" id="hamburgerMenu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </div>
        <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="navbar-brand">IndEX</a>
    </div>
    <div class="navbar-right">
        <div class="profile-dropdown">
            <a href="<?php echo BASE_URL; ?>teacher/profile.php" class="profile-button">
                <?php
                $profile_pic = $_SESSION['profile_picture'] ?? '';
                $full_name = $_SESSION['full_name'] ?? 'User';
                ?>
                <img src="<?php echo getProfilePicture($profile_pic, $full_name); ?>" alt="Profile">
            </a>
        </div>
    </div>
</header>

<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <div class="sidebar-header">
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Teacher'); ?></div>
                <div class="sidebar-user-role">Teacher</div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item nav-dropdown">
                    <div class="nav-dropdown-toggle">
                        <span class="nav-icon">📚</span>
                        <span class="nav-text">My Courses</span>
                        <span class="dropdown-arrow">▼</span>
                    </div>
                    <ul class="nav-dropdown-menu">
                        <li><a href="<?php echo BASE_URL; ?>teacher/my-courses.php">All Courses</a></li>
                        <li><a href="<?php echo BASE_URL; ?>teacher/create-class.php">Create New Course</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>teacher/archive.php" class="<?php echo $current_page == 'archive.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🗄️</span>
                        <span class="nav-text">Archive</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>teacher/audit-trail.php" class="<?php echo $current_page == 'audit-trail.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📜</span>
                        <span class="nav-text">Audit Trail</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>teacher/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">Profile</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>teacher/change-password.php" class="<?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🔑</span>
                        <span class="nav-text">Change Password</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>auth/logout.php" onclick="return confirm('Are you sure you want to logout?');">
                        <span class="nav-icon">🚪</span>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Load Navigation JavaScript -->
<script>
// Define BASE_URL for JavaScript
window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>assets/js/dashboard-nav.js?v=<?php echo time(); ?>"></script>