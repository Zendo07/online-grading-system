<?php
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

header('Location: login.php');
exit;
?>