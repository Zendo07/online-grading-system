<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireStudent();

$student_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            al.log_id,
            al.action,
            al.action_type,
            al.description,
            al.ip_address,
            al.user_agent,
            al.created_at,
            c.class_name,
            c.subject,
            c.section,
            c.class_code,
            u.full_name as teacher_name
        FROM audit_logs al
        LEFT JOIN enrollments e ON al.user_id = e.student_id 
            AND al.action_type IN ('join', 'update')
            AND al.description LIKE CONCAT('%', e.class_id, '%')
        LEFT JOIN classes c ON e.class_id = c.class_id
        LEFT JOIN users u ON c.teacher_id = u.user_id
        WHERE al.user_id = ? 
        ORDER BY al.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute([$student_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Audit Trail Error: " . $e->getMessage());
    $logs = [];
}

function formatLogEntry($log) {
    $action = strtolower($log['action'] ?? '');
    $actionType = strtolower($log['action_type'] ?? '');
    $description = $log['description'] ?? '';
    
    $descJson = json_decode($description, true);
    
    $className = $log['class_name'] ?? '';
    $subject = $log['subject'] ?? '';
    $section = $log['section'] ?? '';
    $teacherName = $log['teacher_name'] ?? '';
    
    if (is_array($descJson)) {
        $className = $className ?: ($descJson['class_name'] ?? '');
        $subject = $subject ?: ($descJson['subject'] ?? '');
        $section = $section ?: ($descJson['section'] ?? '');
        $teacherName = $teacherName ?: ($descJson['teacher_name'] ?? '');
    }
    
    if (empty($className) && !empty($description)) {
        if (preg_match('/class[:\s]+([^-\(,]+)/i', $description, $matches)) {
            $className = trim($matches[1]);
        }
    }
    
    $actionMap = [
        // Login/Logout - Check action_type first, then fallback to text
        'login' => ['label' => 'Logged In', 'icon' => 'sign-in-alt'],
        'logout' => ['label' => 'Logged Out', 'icon' => 'sign-out-alt'],
        
        // Class actions - FIXED: Check action_type for accurate detection
        'join' => ['label' => 'Joined Class', 'icon' => 'user-plus'],
        'unenroll' => ['label' => 'Unenrolled from Class', 'icon' => 'user-minus'],
        'drop' => ['label' => 'Unenrolled from Class', 'icon' => 'user-minus'],
        'leave' => ['label' => 'Unenrolled from Class', 'icon' => 'user-minus'],
        
        // View actions
        'view' => ['label' => 'Viewed Class', 'icon' => 'eye'],
        'visit' => ['label' => 'Viewed Class', 'icon' => 'eye'],
        
        // Profile actions
        'change_profile' => ['label' => 'Changed Profile Picture', 'icon' => 'camera'],
        'profile_picture' => ['label' => 'Changed Profile Picture', 'icon' => 'camera'],
        
        // Password actions
        'forgot_pass' => ['label' => 'Forgot Password', 'icon' => 'unlock-alt'],
        'change_pass' => ['label' => 'Changed Password', 'icon' => 'key'],
        'password_reset' => ['label' => 'Forgot Password', 'icon' => 'unlock-alt'],
        'password_change' => ['label' => 'Changed Password', 'icon' => 'key'],
    ];
    
    $config = null;
    
    if (isset($actionMap[$actionType])) {
        $config = $actionMap[$actionType];
    }
    
    if (!$config && isset($actionMap[strtolower($action)])) {
        $config = $actionMap[strtolower($action)];
    }
    
    if (!$config) {
        $searchText = strtolower($action . ' ' . $description . ' ' . $actionType);
        
        // CRITICAL: Check for unenroll/drop BEFORE join to avoid false positives
        if (stripos($searchText, 'unenroll') !== false || 
            stripos($searchText, 'dropped') !== false || 
            stripos($searchText, 'left') !== false ||
            stripos($searchText, 'leave') !== false) {
            $config = ['label' => 'Unenrolled from Class', 'icon' => 'user-minus'];
        }
        elseif (stripos($searchText, 'join') !== false || 
                stripos($searchText, 'enroll') !== false) {
            $config = ['label' => 'Joined Class', 'icon' => 'user-plus'];
        }
        elseif (stripos($searchText, 'logged in') !== false || 
                stripos($searchText, 'login') !== false) {
            $config = ['label' => 'Logged In', 'icon' => 'sign-in-alt'];
        }
        elseif (stripos($searchText, 'logged out') !== false || 
                stripos($searchText, 'logout') !== false) {
            $config = ['label' => 'Logged Out', 'icon' => 'sign-out-alt'];
        }
        elseif (stripos($searchText, 'view') !== false || 
                stripos($searchText, 'visit') !== false) {
            $config = ['label' => 'Viewed Class', 'icon' => 'eye'];
        }
        elseif (stripos($searchText, 'profile picture') !== false || 
                stripos($searchText, 'change_profile') !== false) {
            $config = ['label' => 'Changed Profile Picture', 'icon' => 'camera'];
        }
        elseif (stripos($searchText, 'forgot password') !== false || 
                stripos($searchText, 'password reset') !== false) {
            $config = ['label' => 'Forgot Password', 'icon' => 'unlock-alt'];
        }
        elseif (stripos($searchText, 'changed password') !== false || 
                stripos($searchText, 'change_pass') !== false) {
            $config = ['label' => 'Changed Password', 'icon' => 'key'];
        }
    }
    
    if (!$config) {
        $config = ['label' => 'Activity', 'icon' => 'circle'];
    }
    
    return [
        'icon' => $config['icon'],
        'label' => $config['label'],
        'className' => $className,
        'subject' => $subject,
        'section' => $section,
        'teacherName' => $teacherName,
        'hasClassInfo' => !empty($className),
        'timestamp' => $log['created_at'],
    ];
}

$formattedLogs = array_map('formatLogEntry', $logs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity History - indEx</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-pages/audit-trail.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="audit-container">
                <div class="audit-header">
                    <h1><i class="fas fa-history"></i> Activity History</h1>
                    <p>Your recent account activity and course interactions</p>
                </div>
                
                <div class="timeline-container">
                    <?php if (count($formattedLogs) > 0): ?>
                        <?php foreach ($formattedLogs as $log): 
                            $timestamp = strtotime($log['timestamp']);
                            $time = date('g:i A', $timestamp);
                            $date = date('M j, Y', $timestamp);
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-marker">
                                <i class="fas fa-<?php echo $log['icon']; ?>"></i>
                            </div>
                            
                            <div class="timeline-content">
                                <div class="timeline-time">[<?php echo $time; ?> | <?php echo $date; ?>]</div>
                                
                                <div class="timeline-action">
                                    <strong><?php echo htmlspecialchars($log['label']); ?></strong>
                                    
                                    <?php if ($log['hasClassInfo']): ?>
                                        — <?php echo htmlspecialchars($log['className']); ?>
                                        <?php if (!empty($log['section'])): ?>
                                            (<?php echo htmlspecialchars($log['section']); ?>)
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($log['hasClassInfo'] && (!empty($log['subject']) || !empty($log['teacherName']))): ?>
                                <div class="timeline-meta">
                                    <?php if (!empty($log['subject'])): ?>
                                        <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($log['subject']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($log['teacherName'])): ?>
                                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($log['teacherName']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No Activity Yet</h3>
                            <p>Your activity history will appear here as you use the platform</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>