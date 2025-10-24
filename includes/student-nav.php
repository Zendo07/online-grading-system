<?php
// Student Navigation - Modern Google Classroom Style
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
        <a href="dashboard.php" class="navbar-brand">IndEX</a>
    </div>
    <div class="navbar-right">
        <div class="profile-dropdown">
            <a href="profile.php" class="profile-button">
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
        
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/join-class.php" class="<?php echo $current_page == 'join-class.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">➕</span>
                        <span class="nav-text">Join Class</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/my-grades.php" class="<?php echo $current_page == 'my-grades.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📝</span>
                        <span class="nav-text">My Grades</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/my-attendance.php" class="<?php echo $current_page == 'my-attendance.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📋</span>
                        <span class="nav-text">My Attendance</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/audit-trail.php" class="<?php echo $current_page == 'audit-trail.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">📜</span>
                        <span class="nav-text">Audit Trail</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">Profile</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>student/change-password.php" class="<?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
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