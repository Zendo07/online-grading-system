<?php
// Teacher Navigation - Sidebar with Dropdown
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="sidebar-brand">
        📚 indEx
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
            
            <!-- My Courses with Hover Dropdown -->
            <li class="nav-item nav-dropdown">
                <a href="<?php echo BASE_URL; ?>teacher/my-courses.php" class="<?php echo $current_page == 'my-courses.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📚</span>
                    My Courses
                    <span class="dropdown-arrow">▼</span>
                </a>
                <ul class="nav-dropdown-menu">
                    <li><a href="<?php echo BASE_URL; ?>teacher/my-courses.php">View All Courses</a></li>
                    <li><a href="<?php echo BASE_URL; ?>teacher/create-class.php">Create New Course</a></li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/manage-students.php" class="<?php echo $current_page == 'manage-students.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👥</span>
                    Manage Students
                </a>
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

<style>
/* Dropdown Menu Styles */
.nav-dropdown {
    position: relative;
}

.nav-dropdown > a {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dropdown-arrow {
    font-size: 0.75rem;
    transition: transform 0.3s ease;
}

.nav-dropdown:hover .dropdown-arrow {
    transform: rotate(180deg);
}

.nav-dropdown-menu {
    list-style: none;
    padding-left: 0;
    margin: 0;
    max-height: 0;
    overflow: hidden;
    background: rgba(139, 64, 73, 0.05);
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.nav-dropdown:hover .nav-dropdown-menu {
    max-height: 200px;
    padding: 0.5rem 0;
}

.nav-dropdown-menu li {
    list-style: none;
}

.nav-dropdown-menu a {
    display: block;
    padding: 0.625rem 1.5rem 0.625rem 3.5rem;
    color: var(--text-color);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.nav-dropdown-menu a:hover {
    background-color: rgba(139, 64, 73, 0.1);
    color: var(--primary-color);
    padding-left: 3.75rem;
}
</style>