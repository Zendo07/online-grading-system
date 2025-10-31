<?php
// includes/teacher-nav.php - ENHANCED VERSION
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Top Navigation Bar -->
<header class="top-navbar">
    <div class="navbar-left">
        <button class="hamburger-menu" id="hamburgerMenu" aria-label="Toggle navigation">
            <div class="hamburger-icon">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </div>
        </button>
        <img src="<?php echo BASE_URL; ?>assets/images/psu-logo.png" alt="" class="navbar-logo">
    </div>
    <div class="navbar-brand">indEx</div>
    <div class="navbar-right">
        <div class="profile-dropdown">
            <a href="<?php echo BASE_URL; ?>teacher/profile.php" class="profile-button">
                <?php
                $profile_pic = $_SESSION['profile_picture'] ?? '';
                $full_name = $_SESSION['full_name'] ?? 'Teacher';
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
        
        <!-- Main Navigation -->
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item nav-dropdown">
                    <div class="nav-dropdown-toggle">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                            </svg>
                        </span>
                        <span class="nav-text">My Courses</span>
                        <span class="dropdown-arrow">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </span>
                    </div>
                    <ul class="nav-dropdown-menu">
                        <li><a href="<?php echo BASE_URL; ?>teacher/my-courses.php">All Courses</a></li>
                        <li><a href="<?php echo BASE_URL; ?>teacher/create-class.php">Create New Course</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>teacher/archive.php" class="<?php echo $current_page == 'archive.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="21 8 21 21 3 21 3 8"/>
                                <rect x="1" y="3" width="22" height="5"/>
                                <line x1="10" y1="12" x2="14" y2="12"/>
                            </svg>
                        </span>
                        <span class="nav-text">Archive</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>teacher/audit-trail.php" class="<?php echo $current_page == 'audit-trail.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </span>
                        <span class="nav-text">Audit Trail</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Bottom Navigation Section -->
        <div class="sidebar-bottom">
            <div class="nav-divider"></div>
            <ul>
                <li class="nav-item profile">
                    <a href="<?php echo BASE_URL; ?>teacher/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <span class="nav-text">My Profile</span>
                    </a>
                </li>
                
                <li class="nav-item password">
                    <a href="<?php echo BASE_URL; ?>teacher/change-password.php" class="<?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <span class="nav-text">Change Password</span>
                    </a>
                </li>
                
                <li class="nav-item logout">
                    <a href="<?php echo BASE_URL; ?>auth/logout.php" onclick="return confirm('Are you sure you want to logout?');">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                        </span>
                        <span class="nav-text">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Load Enhanced Styles and Scripts -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/navigation.css?v=<?php echo time(); ?>">
<script>
window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>assets/js/navigation.js?v=<?php echo time(); ?>"></script>