<?php
// Teacher Navigation - Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="sidebar-brand">
            📚 Grading System
        </a>
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="sidebar-user-role">Teacher</div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span>
                    Dashboard
                </a>
            </li>
            
            <!-- My Courses Dropdown -->
            <li class="nav-item nav-dropdown">
                <a href="#" class="nav-dropdown-toggle" onclick="return false;">
                    <span class="nav-icon">📚</span>
                    My Courses
                    <span class="dropdown-arrow">▼</span>
                </a>
                <ul class="nav-dropdown-menu">
                    <li><a href="<?php echo BASE_URL; ?>teacher/my-courses.php">All Courses</a></li>
                    <li><a href="<?php echo BASE_URL; ?>teacher/create-class.php">Create New Course</a></li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/archive.php" class="<?php echo $current_page == 'archive.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🗄️</span>
                    Archive
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/audit-trail.php" class="<?php echo $current_page == 'audit-trail.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📜</span>
                    Audit Trail
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👤</span>
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/change-password.php" class="<?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
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