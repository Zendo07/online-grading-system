<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Log the logout if user is logged in
if (isset($_SESSION['user_id'])) {
    logAudit($conn, $_SESSION['user_id'], 'User logged out', 'logout', 'users', $_SESSION['user_id'], 'User logged out');
}

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header('Location: ' . BASE_URL . 'index.php?logged_out=1');
exit();
?>