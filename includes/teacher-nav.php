<?php
// Teacher Navigation - Modern Collapsible Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
$profile_image = !empty($_SESSION['profile_pic']) 
    ? BASE_URL . 'uploads/profile/' . $_SESSION['profile_pic'] 
    : BASE_URL . 'assets/img/default-profile.png';
?>

<!-- Top Navigation Bar -->
<header class="topbar">
    <div class="menu-toggle" id="menuToggle">
        ☰
    </div>
    <div class="topbar-title">indEx</div>
    <div class="profile-icon">
        <a href="<?php echo BASE_URL; ?>teacher/profile.php">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
        </a>
    </div>
</header>

<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                     Dashboard
                </a>
            </li>

            <!-- My Courses -->
            <li class="nav-item nav-dropdown">
                <a href="#" onclick="return false;">
                     My Courses
                </a>
                <ul class="nav-dropdown-menu">
                    <li><a href="<?php echo BASE_URL; ?>teacher/my-courses.php">All Courses</a></li>
                    <li><a href="<?php echo BASE_URL; ?>teacher/create-class.php">Create New Course</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/archive.php" class="<?php echo $current_page == 'archive.php' ? 'active' : ''; ?>">
                     Archive
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/audit-trail.php" class="<?php echo $current_page == 'audit-trail.php' ? 'active' : ''; ?>">
                     Audit Trail
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/profile.php" class="<?php echo $current_page == 'teacher/profile.php' ? 'active' : ''; ?>">
                     Profile
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>teacher/change-password.php" class="<?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>">
                     Change Password
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>auth/logout.php" onclick="return confirm('Are you sure you want to logout?');">
                     Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- STYLES -->
<style>
/* ======== GENERAL ======== */
:root {
    --primary: #6b1d1d;
    --accent: #a93c3c;
    --text: #f8f8f8;
    --bg: #ffffff;
}

body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: var(--bg);
}

/* ======== TOPBAR ======== */
.topbar {
    height: 60px;
    background: var(--primary);
    color: var(--text);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.menu-toggle {
    font-size: 1.5rem;
    cursor: pointer;
    user-select: none;
    transition: color 0.3s ease;
}

.menu-toggle:hover {
    color: var(--accent);
}

.topbar-title {
    font-weight: 700;
    font-size: 1.2rem;
    letter-spacing: 1px;
}

.profile-icon img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--accent);
    transition: transform 0.2s ease;
}

.profile-icon img:hover {
    transform: scale(1.05);
}

/* ======== SIDEBAR ======== */
.sidebar {
    position: fixed;
    top: 60px;
    left: 0;
    height: calc(100vh - 60px);
    width: 60px;
    background: var(--primary);
    overflow: hidden;
    transition: width 0.3s ease;
    z-index: 999;
}

.sidebar:hover {
    width: 220px;
}

.sidebar-nav {
    padding-top: 1rem;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav a {
    display: block;
    padding: 0.9rem 1rem;
    color: var(--text);
    text-decoration: none;
    white-space: nowrap;
    font-size: 0.95rem;
    transition: background 0.2s ease, padding-left 0.3s ease;
}

.sidebar-nav a:hover {
    background: var(--accent);
    padding-left: 1.5rem;
}

.sidebar-nav .active {
    background: var(--accent);
}

/* ======== DROPDOWN ======== */
.nav-dropdown {
    position: relative;
}

.nav-dropdown-menu {
    list-style: none;
    padding-left: 0;
    margin: 0;
    max-height: 0;
    overflow: hidden;
    background: rgba(255,255,255,0.08);
    transition: max-height 0.3s ease;
}

.nav-dropdown:hover .nav-dropdown-menu {
    max-height: 150px;
}

.nav-dropdown-menu a {
    padding-left: 2rem;
    font-size: 0.9rem;
}

/* ======== RESPONSIVE ======== */
@media (max-width: 768px) {
    .sidebar {
        width: 0;
    }

    .sidebar:hover {
        width: 200px;
    }

    .topbar-title {
        font-size: 1rem;
    }
}
</style>
