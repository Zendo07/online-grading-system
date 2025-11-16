<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check if user is teacher
function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

// Check if user is student
function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

// Redirect to appropriate dashboard based on role
function redirectToDashboard() {
    if (isTeacher()) {
        header('Location: ' . BASE_URL . 'teacher/dashboard.php');
    } elseif (isStudent()) {
        header('Location: ' . BASE_URL . 'student/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'auth/login.php');
    }
    exit();
}

// Require teacher role
function requireTeacher() {
    requireLogin();
    if (!isTeacher()) {
        header('Location: ' . BASE_URL . 'student/dashboard.php');
        exit();
    }
}

// Require student role
function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: ' . BASE_URL . 'teacher/dashboard.php');
        exit();
    }
}

// Check session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity'];
        
        if ($elapsed_time > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Get current user data
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT user_id, email, full_name, role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function logout() {
    session_start();
    $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'You have been logged out successfully.'
    ];

    session_unset();
    session_destroy();

    header('Location: ' . BASE_URL . 'index.php');
    exit();
}
