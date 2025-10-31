<?php
/**
 * Additional Helper Functions for Online Grading System
 * ADD THESE FUNCTIONS to your EXISTING includes/functions.php file
 */

/**
 * Get profile picture URL or generate avatar
 */
if (!function_exists('getProfilePicture')) {
    function getProfilePicture($profile_picture = '', $full_name = '') {
        if (!empty($profile_picture)) {
            $file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($profile_picture, '/');
            if (file_exists($file_path)) {
                return BASE_URL . $profile_picture;
            }
        }
        return generateAvatarDataURL($full_name);
    }
}

/**
 * Generate avatar image with user initials
 */
if (!function_exists('generateAvatarDataURL')) {
    function generateAvatarDataURL($name) {
        $names = explode(' ', trim($name));
        $initials = '';
        
        if (count($names) >= 2) {
            $initials = strtoupper(substr($names[0], 0, 1) . substr($names[count($names) - 1], 0, 1));
        } else if (count($names) === 1) {
            $initials = strtoupper(substr($names[0], 0, 2));
        } else {
            $initials = 'U';
        }
        
        $colors = [
            ['bg' => '#7b2d26', 'text' => '#ffffff'],
            ['bg' => '#D4A373', 'text' => '#ffffff'],
            ['bg' => '#5a1f18', 'text' => '#ffffff'],
            ['bg' => '#a74c42', 'text' => '#ffffff'],
            ['bg' => '#8b4049', 'text' => '#ffffff'],
            ['bg' => '#6b4423', 'text' => '#ffffff'],
        ];
        
        $colorIndex = strlen($name) % count($colors);
        $color = $colors[$colorIndex];
        
        $svg = '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="200" height="200" fill="' . $color['bg'] . '"/>
            <text x="50%" y="50%" 
                  font-family="Poppins, Arial, sans-serif" 
                  font-size="80" 
                  font-weight="600" 
                  fill="' . $color['text'] . '" 
                  text-anchor="middle" 
                  dy=".35em">' . htmlspecialchars($initials) . '</text>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

/**
 * Format date for display
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'M d, Y') {
        if (empty($date)) return 'N/A';
        try {
            $dt = new DateTime($date);
            return $dt->format($format);
        } catch (Exception $e) {
            return 'Invalid Date';
        }
    }
}

/**
 * Format date with time
 */
if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime) {
        return formatDate($datetime, 'M d, Y \a\t g:i A');
    }
}

/**
 * Get time ago format
 */
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (empty($datetime)) return 'N/A';
        try {
            $time = strtotime($datetime);
            $current = time();
            $seconds = $current - $time;
            
            if ($seconds < 60) return 'Just now';
            else if ($seconds < 3600) {
                $minutes = floor($seconds / 60);
                return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
            } else if ($seconds < 86400) {
                $hours = floor($seconds / 3600);
                return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } else if ($seconds < 604800) {
                $days = floor($seconds / 86400);
                return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            } else if ($seconds < 2592000) {
                $weeks = floor($seconds / 604800);
                return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
            } else if ($seconds < 31536000) {
                $months = floor($seconds / 2592000);
                return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
            } else {
                $years = floor($seconds / 31536000);
                return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
            }
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
}

/**
 * Get and clear flash message
 */
if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

/**
 * Redirect with flash message
 */
if (!function_exists('redirectWithMessage')) {
    function redirectWithMessage($url, $type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
        header('Location: ' . $url);
        exit();
    }
}

/**
 * Sanitize input data
 */
if (!function_exists('sanitize')) {
    function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

/**
 * Log audit trail
 */
if (!function_exists('logAudit')) {
    function logAudit($conn, $user_id, $action, $action_type, $table_affected = null, $record_id = null, $description = null) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO audit_logs 
                (user_id, action, action_type, table_affected, record_id, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->execute([
                $user_id,
                $action,
                $action_type,
                $table_affected,
                $record_id,
                $description,
                $ip_address,
                $user_agent
            ]);
        } catch (PDOException $e) {
            error_log("Audit Log Error: " . $e->getMessage());
        }
    }
}

/**
 * Generate unique class code
 */
if (!function_exists('generateUniqueClassCode')) {
    function generateUniqueClassCode($conn) {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM classes WHERE class_code = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);
        
        return $code;
    }
}

/**
 * Get class by code
 */
if (!function_exists('getClassByCode')) {
    function getClassByCode($conn, $code) {
        $stmt = $conn->prepare("SELECT * FROM classes WHERE class_code = ? AND status = 'active'");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
}

/**
 * Check if student is enrolled in class
 */
if (!function_exists('isStudentEnrolled')) {
    function isStudentEnrolled($conn, $student_id, $class_id) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM enrollments 
            WHERE student_id = ? AND class_id = ? AND status = 'active'
        ");
        $stmt->execute([$student_id, $class_id]);
        return $stmt->fetchColumn() > 0;
    }
}

/**
 * Check if user is logged in
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }
}

/**
 * Require user to be logged in
 */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            redirectWithMessage(BASE_URL . 'auth/login.php', 'warning', 'Please login to continue.');
        }
    }
}

/**
 * Require user to be a student
 */
if (!function_exists('requireStudent')) {
    function requireStudent() {
        requireLogin();
        if ($_SESSION['role'] !== 'student') {
            redirectWithMessage(BASE_URL . 'index.php', 'danger', 'Access denied.');
        }
    }
}

/**
 * Require user to be a teacher
 */
if (!function_exists('requireTeacher')) {
    function requireTeacher() {
        requireLogin();
        if ($_SESSION['role'] !== 'teacher') {
            redirectWithMessage(BASE_URL . 'index.php', 'danger', 'Access denied.');
        }
    }
}
?>