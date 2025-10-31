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
    <title>Audit Trail - Online Grading System</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>style.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>dashboard.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/student-nav.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="page-title-section">
                    <h1>Audit Trail</h1>
                    <p class="breadcrumb">Home / Audit Trail</p>
                </div>
            </header>
            
            <div class="dashboard-content">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Activity Log (Last 100 entries)</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($logs) > 0): ?>
                            <div class="data-table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo match($log['action_type']) {
                                                            'create' => 'success',
                                                            'update' => 'info',
                                                            'delete' => 'danger',
                                                            'login' => 'success',
                                                            'logout' => 'secondary',
                                                            'join' => 'success',
                                                            default => 'info'
                                                        };
                                                    ?>">
                                                        <?php echo ucfirst($log['action_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                                                <td><?php echo formatDateTime($log['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📜</div>
                                <h3>No Activity Yet</h3>
                                <p>Your activity log is empty.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_PATH; ?>main.js"></script>
</body>
</html>