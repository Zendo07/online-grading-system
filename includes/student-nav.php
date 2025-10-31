<?php
// includes/student-nav.php - ENHANCED VERSION
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
            <a href="<?php echo BASE_URL; ?>student/profile.php" class="profile-button">
                <img src="<?php echo getProfilePicture($_SESSION['profile_picture'] ?? '', $_SESSION['full_name']); ?>" alt="Profile">
            </a>
        </div>
    </div>
</header>

<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <div class="sidebar-header">
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="sidebar-user-role">Student</div>
            </div>
        </div>
        
        <!-- Main Navigation -->
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/join-class.php" class="<?php echo $current_page == 'join-class.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="16"/>
                                <line x1="8" y1="12" x2="16" y2="12"/>
                            </svg>
                        </span>
                        <span class="nav-text">Join Class</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/my-grades.php" class="<?php echo $current_page == 'my-grades.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </span>
                        <span class="nav-text">My Grades</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/my-attendance.php" class="<?php echo $current_page == 'my-attendance.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </span>
                        <span class="nav-text">My Attendance</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/audit-trail.php" class="<?php echo $current_page == 'audit-trail.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
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
                    <a href="<?php echo BASE_URL; ?>student/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
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
                    <a href="<?php echo BASE_URL; ?>student/change-password.php" class="<?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
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