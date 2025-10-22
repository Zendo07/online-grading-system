<?php
// Student Navigation - Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="sidebar-brand">
            📚 Grading System
        </a>
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="sidebar-user-role">Student</div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>student/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>student/join-class.php" class="<?php echo $current_page == 'join-class.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">➕</span>
                    Join Class
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>student/my-grades.php" class="<?php echo $current_page == 'my-grades.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📝</span>
                    My Grades
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>student/my-attendance.php" class="<?php echo $current_page == 'my-attendance.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📋</span>
                    My Attendance
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>student/audit-trail.php" class="<?php echo $current_page == 'audit-trail.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📜</span>
                    Audit Trail
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>student/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👤</span>
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>student/change-password.php" class="<?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🔑</span>
                    Change Password
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>auth/logout.php" onclick="return confirm('Are you sure you want to logout?');">
                    <span class="nav-icon">🚪</span>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>