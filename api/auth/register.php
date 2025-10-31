<?php
// auth/register.php - Role Router

// If a role is already passed in URL, redirect immediately
if (isset($_GET['role'])) {
    $role = strtolower($_GET['role']);

    if ($role === 'teacher' || $role === 'professor') {
        header('Location: teacher-register.php');
        exit;
    } elseif ($role === 'student') {
        header('Location: student-register.php');
        exit;
    }
}

// If no role specified, redirect to login
header('Location: login.php');
exit;
?>