<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Require student access
requireStudent();

$student_id = $_SESSION['user_id'];

// Get audit logs
try {
    $stmt = $conn->prepare("
        SELECT * FROM audit_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 100
    ");
    $stmt->execute([$student_id]);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Audit Trail Error: " . $e->getMessage());
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - indEx</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>navigation.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>student-pages/log-history.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <div class="audit-trail-container">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="page-title-section">
                            <h1 class="page-title">Audit Trail</h1>
                            <p class="page-subtitle">Track your activity history and system interactions</p>
                        </div>
                    </div>
                </div>

                <!-- Logs Card -->
                <div class="logs-card">
                    <div class="logs-header">
                        <div class="header-left">
                            <i class="fas fa-list-alt"></i>
                            <div>
                                <h3>Activity Log</h3>
                                <p>Last 100 entries</p>
                            </div>
                        </div>
                        <div class="header-right">
                            <span class="total-badge">
                                <i class="fas fa-layer-group"></i>
                                <?php echo count($logs); ?> Total
                            </span>
                        </div>
                    </div>

                    <div class="logs-list">
                        <?php if (count($logs) > 0): ?>
                            <?php 
                            foreach ($logs as $log): 
                                $actionType = $log['action_type'] ?? 'other';
                                $iconMap = [
                                    'login' => 'sign-in-alt',
                                    'logout' => 'sign-out-alt',
                                    'create' => 'plus-circle',
                                    'update' => 'edit',
                                    'delete' => 'trash-alt',
                                    'join' => 'user-plus',
                                    'other' => 'info-circle'
                                ];
                                $icon = $iconMap[$actionType] ?? 'info-circle';
                            ?>
                                <div class="log-item">
                                    <div class="log-icon <?php echo $actionType; ?>">
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="log-content">
                                        <div class="log-header">
                                            <div class="log-action">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </div>
                                            <span class="log-badge badge-<?php 
                                                echo match($actionType) {
                                                    'create', 'join', 'login' => 'success',
                                                    'update' => 'info',
                                                    'delete' => 'danger',
                                                    'logout' => 'secondary',
                                                    default => 'info'
                                                };
                                            ?>">
                                                <?php echo ucfirst($actionType); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($log['description'])): ?>
                                            <div class="log-description">
                                                <?php echo htmlspecialchars($log['description']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="log-footer">
                                            <div class="log-time">
                                                <i class="fas fa-clock"></i>
                                                <?php echo formatDateTime($log['created_at']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3>No Activity Yet</h3>
                                <p>Your activity log is empty. Start using the system to see your history here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo JS_PATH; ?>main.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo JS_PATH; ?>navigation.js?v=<?php echo time(); ?>"></script>
</body>
</html>