<?php
// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate unique class code
function generateClassCode($prefix = 'PSU') {
    return strtoupper($prefix . rand(100, 999));
}

// Check if class code exists
function isClassCodeUnique($conn, $class_code) {
    $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_code = ?");
    $stmt->execute([$class_code]);
    return $stmt->rowCount() === 0;
}

// Generate unique class code that doesn't exist
function generateUniqueClassCode($conn, $prefix = 'PSU') {
    do {
        $class_code = generateClassCode($prefix);
    } while (!isClassCodeUnique($conn, $class_code));
    
    return $class_code;
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Log audit trail
function logAudit($conn, $user_id, $action, $action_type, $table_affected = null, $record_id = null, $description = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare(
        "INSERT INTO audit_logs (user_id, action, action_type, table_affected, record_id, description, ip_address, user_agent) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
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
}

// Format date
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime, $format = 'F j, Y g:i A') {
    return date($format, strtotime($datetime));
}

// Calculate percentage
function calculatePercentage($score, $max_score) {
    if ($max_score == 0) return 0;
    return round(($score / $max_score) * 100, 2);
}

// Get letter grade from percentage
function getLetterGrade($percentage) {
    if ($percentage >= 97) return 'A+';
    if ($percentage >= 93) return 'A';
    if ($percentage >= 90) return 'A-';
    if ($percentage >= 87) return 'B+';
    if ($percentage >= 83) return 'B';
    if ($percentage >= 80) return 'B-';
    if ($percentage >= 77) return 'C+';
    if ($percentage >= 73) return 'C';
    if ($percentage >= 70) return 'C-';
    if ($percentage >= 67) return 'D+';
    if ($percentage >= 63) return 'D';
    if ($percentage >= 60) return 'D-';
    return 'F';
}

// Check if student is enrolled in class
function isStudentEnrolled($conn, $student_id, $class_id) {
    $stmt = $conn->prepare(
        "SELECT enrollment_id FROM enrollments 
         WHERE student_id = ? AND class_id = ? AND status = 'active'"
    );
    $stmt->execute([$student_id, $class_id]);
    return $stmt->rowCount() > 0;
}

// Get class by code
function getClassByCode($conn, $class_code) {
    $stmt = $conn->prepare("SELECT * FROM classes WHERE class_code = ? AND status = 'active'");
    $stmt->execute([$class_code]);
    return $stmt->fetch();
}

// Display success message
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, 
        'message' => $message
    ];
}

// Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Redirect with message
function redirectWithMessage($url, $type, $message) {
    setFlashMessage($type, $message);
    header('Location: ' . $url);
    exit();
}

// Upload profile picture
function uploadProfilePicture($file, $user_id) {
    $upload_dir = __DIR__ . '/../uploads/profiles/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF allowed.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum 5MB allowed.'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred. Code: ' . $file['error']];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . strtolower($extension);
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Set proper permissions
        chmod($filepath, 0644);
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to save file to server.'];
}

function getProfilePicture($profile_picture, $full_name = '') {
    if (empty($profile_picture) || is_null($profile_picture)) {
        return BASE_URL . 'assets/images/default-avatar.jpg';
    }
    
    $file_path = __DIR__ . '/../uploads/profiles/' . $profile_picture;
    
    if (file_exists($file_path)) {
        return BASE_URL . 'uploads/profiles/' . $profile_picture;
    }
    
    return BASE_URL . 'assets/images/default-avatar.jpg';
}

// Calculate overall grade for a student in a class
function calculateOverallGrade($conn, $student_id, $class_id) {
    $stmt = $conn->prepare("
        SELECT 
            grading_period,
            AVG(percentage) as period_average
        FROM grades
        WHERE student_id = ? AND class_id = ?
        GROUP BY grading_period
    ");
    $stmt->execute([$student_id, $class_id]);
    $periods = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (empty($periods)) {
        return null;
    }
    
    // Calculate weighted average (Midterm 50%, Finals 50%)
    $midterm = $periods['midterm'] ?? 0;
    $finals = $periods['finals'] ?? 0;
    
    $overall = ($midterm * 0.50) + ($finals * 0.50);
    
    return [
        'midterm' => $midterm,
        'finals' => $finals,
        'overall' => round($overall, 2),
        'letter_grade' => getLetterGrade($overall),
        'status' => $overall >= 75 ? 'Passed' : 'Failed'
    ];
}

function getMissingActivities($conn, $student_id, $class_id) {
    // Get all activities in the class
    $stmt = $conn->prepare("
        SELECT DISTINCT activity_name, activity_type, grading_period, max_score
        FROM grades
        WHERE class_id = ?
    ");
    $stmt->execute([$class_id]);
    $all_activities = $stmt->fetchAll();
    
    // Get student's submitted activities
    $stmt = $conn->prepare("
        SELECT activity_name, grading_period
        FROM grades
        WHERE student_id = ? AND class_id = ?
    ");
    $stmt->execute([$student_id, $class_id]);
    $submitted = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $missing = [];
    foreach ($all_activities as $activity) {
        $key = $activity['activity_name'] . '_' . $activity['grading_period'];
        if (!isset($submitted[$activity['activity_name']])) {
            $missing[] = $activity;
        }
    }
    
    return $missing;
}
?>